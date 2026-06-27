<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Models\BCL\Fin_jurnal;
use App\Models\BCL\Inventory;
use App\Models\BCL\renter;
use App\Models\BCL\tr_renter;
use App\Models\BCL\Rooms;
use App\Http\Controllers\BCL\tr_renterController;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BCLDashboardController extends Controller
{
    // public function index()
    // {
        

    //     return view('bcl.home');
    // }
    public function index(Request $request)
    {
        if (!Auth::user() || !Auth::user()->hasAnyRole(['Kos','Admin'])) {
            return redirect('/')->with('error', 'Unauthorized access.');
        }

        [$startDate, $endDate] = $this->resolveDateRange($request);
        $response = $this->buildDashboardResponse($startDate, $endDate);

        $ranking_penyewa = app(tr_renterController::class)->ranking_penyewa();
        $response->ranking_penyewa = $ranking_penyewa;

        return view('bcl.home')->with('response', (object) $response);
    }

    public function data(Request $request)
    {
        if (!Auth::user() || !Auth::user()->hasAnyRole(['Kos','Admin'])) {
            return response()->json(['message' => 'Unauthorized access.'], 403);
        }

        [$startDate, $endDate] = $this->resolveDateRange($request);

        return response()->json($this->buildDashboardResponse($startDate, $endDate));
    }

    public function monthlyRevenueDetails(Request $request)
    {
        if (!Auth::user() || !Auth::user()->hasAnyRole(['Kos','Admin'])) {
            return response()->json(['message' => 'Unauthorized access.'], 403);
        }

        [$startDate, $endDate] = $this->resolveDateRange($request);
        $monthKey = (string) $request->input('month_key', '');

        if (!preg_match('/^\d{4}-\d{2}$/', $monthKey)) {
            return response()->json(['message' => 'Month key is invalid.'], 422);
        }

        return response()->json($this->buildMonthlyRevenueDetails($startDate, $endDate, $monthKey));
    }

    protected function resolveDateRange(Request $request): array
    {
        $now = Carbon::now();
        $defaultStart = $now->copy()->startOfYear();
        $defaultEnd = $now->copy()->endOfYear();

        try {
            $startDate = $request->filled('start_date')
                ? Carbon::parse($request->input('start_date'))->startOfDay()
                : $defaultStart;
        } catch (\Throwable $e) {
            $startDate = $defaultStart;
        }

        try {
            $endDate = $request->filled('end_date')
                ? Carbon::parse($request->input('end_date'))->endOfDay()
                : $defaultEnd;
        } catch (\Throwable $e) {
            $endDate = $defaultEnd;
        }

        if ($startDate->gt($endDate)) {
            [$startDate, $endDate] = [$endDate->copy()->startOfDay(), $startDate->copy()->endOfDay()];
        }

        return [$startDate, $endDate];
    }

    protected function buildDashboardResponse(Carbon $startDate, Carbon $endDate): \stdClass
    {
        $rangeStart = $startDate->copy()->startOfDay();
        $rangeEnd = $endDate->copy()->endOfDay();
        $now = Carbon::now();
        $isCurrentYearRange = $rangeStart->equalTo($now->copy()->startOfYear())
            && $rangeEnd->equalTo($now->copy()->endOfYear());

        $filterLabel = $isCurrentYearRange
            ? (string) $now->format('Y')
            : $rangeStart->format('d M Y') . ' - ' . $rangeEnd->format('d M Y');

        $data = Rooms::with('category')->with('renter')->get();
        $room_used = 0;
        foreach ($data as $value) {
            if ($value->renter != null) {
                $room_used++;
            }
        }
        $response = new \stdClass();
        $rooms = new \stdClass();
        $rooms->total = $data->count();
        $rooms->used = $room_used;
        $response->rooms = $rooms;
        $response->filter = (object) [
            'start_date' => $rangeStart->format('Y-m-d'),
            'end_date' => $rangeEnd->format('Y-m-d'),
            'label' => $filterLabel,
            'is_default_year' => $isCurrentYearRange,
        ];

        // Use aggregated columns to be compatible with ONLY_FULL_GROUP_BY
        $belum_lunas = Fin_jurnal::leftjoin('bcl_tr_renter', 'bcl_tr_renter.trans_id', '=', 'bcl_fin_jurnal.doc_id')
            ->leftjoin('bcl_renter', 'bcl_renter.id', '=', 'bcl_tr_renter.id_renter')
            ->select(
                DB::raw('bcl_fin_jurnal.doc_id as doc_id'),
                DB::raw('MAX(bcl_fin_jurnal.tanggal) as last_tanggal'),
                DB::raw('MAX(bcl_renter.nama) as nama'),
                DB::raw('MAX(bcl_renter.id) as renter_id'),
                DB::raw('MAX(bcl_tr_renter.harga) as harga'),
                DB::raw('IFNULL(SUM(kredit),0) AS dibayar'),
                DB::raw('IFNULL(MAX(bcl_tr_renter.harga) - SUM(kredit),0) AS kurang')
            )->where('bcl_fin_jurnal.identity', 'regexp', 'pemasukan|sewa kamar')
            ->groupBy('bcl_fin_jurnal.doc_id')
            ->havingRaw('(MAX(bcl_tr_renter.harga) - SUM(kredit)) > 0')
            ->orderByRaw('MAX(bcl_fin_jurnal.tanggal) DESC')
            ->get();
        $response->belum_lunas = $belum_lunas->count();

        // Select only aggregated or grouped columns to be compatible with ONLY_FULL_GROUP_BY
        $inventory = Inventory::leftjoin('bcl_rooms', 'bcl_rooms.id', '=', 'bcl_inventories.assigned_to')
            ->leftjoin('bcl_fin_jurnal', function ($join) {
                $join->on('bcl_fin_jurnal.kode_subledger', 'like', 'bcl_inventories.inv_number');
            })
            ->select(
                DB::raw('bcl_inventories.inv_number as inv_number'),
                DB::raw('MAX(bcl_inventories.id) as inventory_id'),
                DB::raw('MAX(bcl_inventories.name) as inv_name'),
                DB::raw('MAX(bcl_inventories.assigned_to) as assigned_to'),
                DB::raw('MAX(bcl_rooms.room_name) as room_name'),
                DB::raw('MAX(bcl_inventories.maintanance_cycle) as maintanance_cycle'),
                DB::raw('MAX(bcl_inventories.maintanance_period) as maintanance_period'),
                DB::raw('MAX(tanggal) as last_maintanance')
            )
            ->groupBy('bcl_inventories.inv_number')
            ->get();

        $needed_maintanance = 0;
        foreach ($inventory as $inventoryItem) {
            $next_maintanance = null;
            $remaining = null;
            if ($inventoryItem->last_maintanance != null && $inventoryItem->maintanance_cycle != null) {
                $period = (int) $inventoryItem->maintanance_period;
                if ($inventoryItem->maintanance_cycle == 'Minggu') {
                    $next_maintanance = Carbon::parse($inventoryItem->last_maintanance)->addWeeks($period)->format('Y-m-d');
                    $remaining = Carbon::parse(Carbon::now())->diffInDays($next_maintanance);
                } else if ($inventoryItem->maintanance_cycle == 'Bulan') {
                    $next_maintanance = Carbon::parse($inventoryItem->last_maintanance)->addMonths($period)->format('Y-m-d');
                    $remaining = Carbon::parse(Carbon::now())->diffInDays($next_maintanance);
                } else if ($inventoryItem->maintanance_cycle == 'Tahun') {
                    $next_maintanance = Carbon::parse($inventoryItem->last_maintanance)->addYears($period)->format('Y-m-d');
                    $remaining =  Carbon::parse(Carbon::now())->diffInDays($next_maintanance);
                }
            }
            if ($next_maintanance != null && $remaining <= 7) {
                $needed_maintanance++;
            }
        }
        $response->needed_maintanance = $needed_maintanance;
        $response->occupancy = $this->buildMonthlyOccupancyBreakdown($rangeStart, $rangeEnd, (int) $rooms->total);
        $response->room_occupancy_rankings = $this->buildRoomOccupancyRankings($rangeStart, $rangeEnd);
        $response->age_classification = $this->buildAgeClassification($rangeStart, $rangeEnd);
        $revenueAllocations = $this->buildMonthlyRevenueAllocations($rangeStart, $rangeEnd);
        $response->total_revenue = collect($revenueAllocations)->sum('recognized_revenue');

        $periodRevenue = [];
        $monthlyRevenue = [];
        $monthlyPackageBreakdown = [];
        $packageLabels = [];

        foreach ($revenueAllocations as $allocation) {
            $periodLabel = $allocation['period_label'];
            $recognizedRevenue = (float) $allocation['recognized_revenue'];
            $monthKey = $allocation['month_key'];
            $monthLabel = $allocation['month_label'];

            if (!isset($periodRevenue[$periodLabel])) {
                $periodRevenue[$periodLabel] = [
                    'label' => $periodLabel,
                    'total_transactions' => 0,
                    'total_revenue' => 0,
                ];
            }

            $periodRevenue[$periodLabel]['total_transactions']++;
            $periodRevenue[$periodLabel]['total_revenue'] += $recognizedRevenue;

            if (!isset($monthlyRevenue[$monthKey])) {
                $monthlyRevenue[$monthKey] = [
                    'label' => $monthLabel,
                    'total_revenue' => 0,
                ];
            }

            $monthlyRevenue[$monthKey]['total_revenue'] += $recognizedRevenue;

            if (!in_array($periodLabel, $packageLabels, true)) {
                $packageLabels[] = $periodLabel;
            }

            if (!isset($monthlyPackageBreakdown[$monthKey])) {
                $monthlyPackageBreakdown[$monthKey] = [
                    'month_key' => $monthKey,
                    'label' => $monthLabel,
                    'total_revenue' => 0,
                    'package_counts' => [],
                ];
            }

            if (!isset($monthlyPackageBreakdown[$monthKey]['package_counts'][$periodLabel])) {
                $monthlyPackageBreakdown[$monthKey]['package_counts'][$periodLabel] = 0;
            }

            $monthlyPackageBreakdown[$monthKey]['total_revenue'] += $recognizedRevenue;
            $monthlyPackageBreakdown[$monthKey]['package_counts'][$periodLabel]++;
        }

        $periodStats = new \stdClass();
        $periodStats->labels = [];
        $periodStats->counts = [];
        $periodStats->revenues = [];
        $periodStats->items = [];
        $periodStats->total_transactions = 0;
        $periodStats->total_revenue = 0;

        $periodRevenueRows = collect($periodRevenue)
            ->sort(function (array $left, array $right) {
                if ($left['total_revenue'] === $right['total_revenue']) {
                    return $right['total_transactions'] <=> $left['total_transactions'];
                }

                return $right['total_revenue'] <=> $left['total_revenue'];
            })
            ->values();

        foreach ($periodRevenueRows as $row) {
            $label = trim((string) $row['label']);
            $transactions = (int) $row['total_transactions'];
            $revenue = (float) $row['total_revenue'];

            $periodStats->labels[] = $label;
            $periodStats->counts[] = $transactions;
            $periodStats->revenues[] = round($revenue, 2);
            $periodStats->items[] = (object) [
                'label' => $label,
                'total_transactions' => $transactions,
                'total_revenue' => round($revenue, 2),
            ];
            $periodStats->total_transactions += $transactions;
            $periodStats->total_revenue += $revenue;
        }

        $periodStats->total_revenue = round($periodStats->total_revenue, 2);

        $response->period_stats = $periodStats;

        $monthlyRevenueStats = new \stdClass();
        $monthlyRevenueStats->labels = [];
        $monthlyRevenueStats->revenues = [];
        $monthlyRevenueStats->month_keys = [];

        ksort($monthlyRevenue);
        foreach ($monthlyRevenue as $monthKey => $month) {
            $monthlyRevenueStats->month_keys[] = $monthKey;
            $monthlyRevenueStats->labels[] = $month['label'];
            $monthlyRevenueStats->revenues[] = round((float) $month['total_revenue'], 2);
        }

        $response->monthly_revenue = $monthlyRevenueStats;

        natcasesort($packageLabels);
        $packageLabels = array_values($packageLabels);

        $monthlyPackageStats = new \stdClass();
        $monthlyPackageStats->package_labels = $packageLabels;
        $monthlyPackageStats->rows = [];

        ksort($monthlyPackageBreakdown);
        foreach ($monthlyPackageBreakdown as $monthKey => $monthData) {
            $rowCounts = [];
            foreach ($packageLabels as $packageLabel) {
                $rowCounts[$packageLabel] = (int) ($monthData['package_counts'][$packageLabel] ?? 0);
            }

            $monthlyPackageStats->rows[] = (object) [
                'month_key' => $monthKey,
                'label' => $monthData['label'],
                'total_revenue' => round((float) $monthData['total_revenue'], 2),
                'package_counts' => (object) $rowCounts,
            ];
        }

        $response->monthly_package_breakdown = $monthlyPackageStats;

        return $response;
    }

    protected function buildMonthlyRevenueAllocations(Carbon $rangeStart, Carbon $rangeEnd): array
    {
        $transactions = tr_renter::query()
            ->with(['renter:id,nama', 'room:id,room_name'])
            ->select('id', 'id_renter', 'room_id', 'lama_sewa', 'jangka_sewa', 'harga', 'tgl_mulai', 'tgl_selesai')
            ->whereDate('tgl_mulai', '<=', $rangeEnd->toDateString())
            ->get();

        $allocations = [];

        foreach ($transactions as $transaction) {
            $start = Carbon::parse($transaction->tgl_mulai)->startOfDay();
            if ($start->gt($rangeEnd)) {
                continue;
            }

            $rentalStartMonth = $start->copy()->startOfMonth();
            $totalMonths = $this->resolveRevenueDurationMonths((int) $transaction->lama_sewa, (string) $transaction->jangka_sewa);
            $rentalEndMonth = $rentalStartMonth->copy()->addMonths($totalMonths - 1);
            $recognizedPeriodEnd = $rentalEndMonth->copy()->endOfMonth();

            $overlapStartMonth = $rentalStartMonth->greaterThan($rangeStart->copy()->startOfMonth())
                ? $rentalStartMonth->copy()
                : $rangeStart->copy()->startOfMonth();
            $overlapEndMonth = $rentalEndMonth->lessThan($rangeEnd->copy()->startOfMonth())
                ? $rentalEndMonth->copy()
                : $rangeEnd->copy()->startOfMonth();

            if ($overlapStartMonth->gt($overlapEndMonth)) {
                continue;
            }

            $monthlyRevenue = (float) $transaction->harga / $totalMonths;

            $cursorMonth = $overlapStartMonth->copy();
            while ($cursorMonth->lte($overlapEndMonth)) {
                $allocations[] = [
                    'transaction_id' => (int) $transaction->id,
                    'renter_name' => data_get($transaction, 'renter.nama', '-'),
                    'room_name' => data_get($transaction, 'room.room_name', '-'),
                    'tgl_mulai' => $transaction->tgl_mulai,
                    'tgl_selesai' => $transaction->tgl_selesai,
                    'recognized_start_date' => $start->format('Y-m-d'),
                    'recognized_end_date' => $recognizedPeriodEnd->format('Y-m-d'),
                    'room_id' => (int) $transaction->room_id,
                    'period_label' => trim($transaction->lama_sewa . ' ' . $transaction->jangka_sewa),
                    'recognized_revenue' => $monthlyRevenue,
                    'month_key' => $cursorMonth->format('Y-m'),
                    'month_label' => $cursorMonth->translatedFormat('M Y'),
                ];

                $cursorMonth->addMonth();
            }
        }

        return $allocations;
    }

    protected function buildMonthlyOccupancyBreakdown(Carbon $rangeStart, Carbon $rangeEnd, int $totalRooms): \stdClass
    {
        $transactions = tr_renter::query()
            ->with(['renter:id,nama', 'room:id,room_name'])
            ->select('id', 'id_renter', 'room_id', 'lama_sewa', 'jangka_sewa', 'tgl_mulai', 'tgl_selesai')
            ->orderBy('tgl_mulai')
            ->get();

        $monthCursor = $rangeStart->copy()->startOfMonth();
        $lastMonth = $rangeEnd->copy()->startOfMonth();
        $rows = [];

        while ($monthCursor->lte($lastMonth)) {
            $monthKey = $monthCursor->format('Y-m');
            $snapshotDate = $monthCursor->copy()->endOfMonth()->gt($rangeEnd)
                ? $rangeEnd->copy()->endOfDay()
                : $monthCursor->copy()->endOfMonth()->endOfDay();

            $occupiedRooms = $transactions
                ->filter(function ($transaction) use ($snapshotDate) {
                    $start = Carbon::parse($transaction->tgl_mulai)->startOfDay();
                    $end = Carbon::parse($transaction->tgl_selesai)->startOfDay();

                    return $start->lte($snapshotDate) && $end->gt($snapshotDate);
                })
                ->pluck('room_id')
                ->filter()
                ->unique()
                ->count();

            $rows[$monthKey] = [
                'month_key' => $monthKey,
                'label' => $monthCursor->translatedFormat('M Y'),
                'occupied_rooms' => $occupiedRooms,
                'total_rooms' => $totalRooms,
                'occupancy_rate' => $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100, 1) : 0,
                'move_ins' => [],
                'move_outs' => [],
            ];

            $monthCursor->addMonth();
        }

        $transactionsByRoom = $transactions->groupBy('room_id');
        foreach ($transactionsByRoom as $roomTransactions) {
            $sortedRoomTransactions = $roomTransactions->sortBy('tgl_mulai')->values();
            $previousTransaction = null;

            foreach ($sortedRoomTransactions as $transaction) {
                $start = Carbon::parse($transaction->tgl_mulai)->startOfDay();
                $monthKey = $start->format('Y-m');

                if (!isset($rows[$monthKey])) {
                    $previousTransaction = $transaction;
                    continue;
                }

                $isNewMoveIn = false;
                if ($previousTransaction === null) {
                    $isNewMoveIn = true;
                } else {
                    $previousEnd = Carbon::parse($previousTransaction->tgl_selesai)->startOfDay();
                    $gapDays = $previousEnd->diffInDays($start, false);
                    $isDifferentRenter = (int) $previousTransaction->id_renter !== (int) $transaction->id_renter;
                    $isNewMoveIn = $isDifferentRenter && $gapDays >= 7;
                }

                if ($isNewMoveIn && $start->betweenIncluded($rangeStart, $rangeEnd)) {
                    $rows[$monthKey]['move_ins'][] = [
                        'renter_name' => data_get($transaction, 'renter.nama', '-'),
                        'room_name' => data_get($transaction, 'room.room_name', '-'),
                        'period_label' => trim($transaction->lama_sewa . ' ' . $transaction->jangka_sewa),
                        'start_date' => $start->format('d-m-Y'),
                        'end_date' => Carbon::parse($transaction->tgl_selesai)->format('d-m-Y'),
                    ];
                }

                $previousTransaction = $transaction;
            }
        }

        $transactionsByRenter = $transactions->groupBy('id_renter');
        foreach ($transactionsByRenter as $renterTransactions) {
            $sortedRenterTransactions = $renterTransactions->sortBy('tgl_mulai')->values();
            $count = $sortedRenterTransactions->count();

            for ($index = 0; $index < $count; $index++) {
                $transaction = $sortedRenterTransactions[$index];
                $end = Carbon::parse($transaction->tgl_selesai)->startOfDay();
                $monthKey = $end->format('Y-m');

                if (!isset($rows[$monthKey])) {
                    continue;
                }

                $nextTransaction = $sortedRenterTransactions[$index + 1] ?? null;
                $isMoveOut = false;

                if ($nextTransaction === null) {
                    $isMoveOut = true;
                } else {
                    $nextStart = Carbon::parse($nextTransaction->tgl_mulai)->startOfDay();
                    $gapDays = $end->diffInDays($nextStart, false);
                    $isMoveOut = $gapDays >= 7;
                }

                if ($isMoveOut && $end->betweenIncluded($rangeStart, $rangeEnd)) {
                    $rows[$monthKey]['move_outs'][] = [
                        'renter_name' => data_get($transaction, 'renter.nama', '-'),
                        'room_name' => data_get($transaction, 'room.room_name', '-'),
                        'period_label' => trim($transaction->lama_sewa . ' ' . $transaction->jangka_sewa),
                        'start_date' => Carbon::parse($transaction->tgl_mulai)->format('d-m-Y'),
                        'end_date' => $end->format('d-m-Y'),
                    ];
                }
            }
        }

        $result = new \stdClass();
        $result->rows = [];

        foreach ($rows as $row) {
            $row['move_in_count'] = count($row['move_ins']);
            $row['move_out_count'] = count($row['move_outs']);
            $result->rows[] = (object) [
                'month_key' => $row['month_key'],
                'label' => $row['label'],
                'occupied_rooms' => $row['occupied_rooms'],
                'total_rooms' => $row['total_rooms'],
                'occupancy_rate' => $row['occupancy_rate'],
                'move_in_count' => $row['move_in_count'],
                'move_out_count' => $row['move_out_count'],
                'move_ins' => $row['move_ins'],
                'move_outs' => $row['move_outs'],
            ];
        }

        return $result;
    }

    protected function buildRoomOccupancyRankings(Carbon $rangeStart, Carbon $rangeEnd): \stdClass
    {
        $rangeStartDay = $rangeStart->copy()->startOfDay();
        $rangeEndExclusive = $rangeEnd->copy()->addDay()->startOfDay();
        $totalDays = max(1, $rangeStartDay->diffInDays($rangeEndExclusive));

        $rooms = Rooms::query()->select('id', 'room_name')->orderBy('room_name')->get();
        $transactions = tr_renter::query()
            ->select('room_id', 'tgl_mulai', 'tgl_selesai')
            ->whereDate('tgl_mulai', '<=', $rangeEnd->toDateString())
            ->whereDate('tgl_selesai', '>', $rangeStart->toDateString())
            ->orderBy('tgl_mulai')
            ->get()
            ->groupBy('room_id');

        $rankings = $rooms->map(function ($room) use ($transactions, $rangeStartDay, $rangeEndExclusive, $totalDays) {
            $roomTransactions = $transactions->get($room->id, collect());
            $intervals = [];

            foreach ($roomTransactions as $transaction) {
                $start = Carbon::parse($transaction->tgl_mulai)->startOfDay();
                $endExclusive = Carbon::parse($transaction->tgl_selesai)->startOfDay();

                if ($endExclusive->lte($rangeStartDay) || $start->gte($rangeEndExclusive)) {
                    continue;
                }

                $intervals[] = [
                    'start' => $start->lt($rangeStartDay) ? $rangeStartDay->copy() : $start,
                    'end' => $endExclusive->gt($rangeEndExclusive) ? $rangeEndExclusive->copy() : $endExclusive,
                ];
            }

            usort($intervals, function (array $left, array $right) {
                return $left['start']->lt($right['start']) ? -1 : 1;
            });

            $merged = [];
            foreach ($intervals as $interval) {
                if (empty($merged)) {
                    $merged[] = $interval;
                    continue;
                }

                $lastIndex = count($merged) - 1;
                if ($interval['start']->lte($merged[$lastIndex]['end'])) {
                    if ($interval['end']->gt($merged[$lastIndex]['end'])) {
                        $merged[$lastIndex]['end'] = $interval['end'];
                    }
                    continue;
                }

                $merged[] = $interval;
            }

            $occupiedDays = 0;
            foreach ($merged as $interval) {
                $occupiedDays += $interval['start']->diffInDays($interval['end']);
            }

            return (object) [
                'room_name' => $room->room_name,
                'occupied_days' => $occupiedDays,
                'total_days' => $totalDays,
                'occupancy_rate' => round(($occupiedDays / $totalDays) * 100, 1),
            ];
        })->values();

        $highest = $rankings
            ->sort(function ($left, $right) {
                if ($left->occupancy_rate === $right->occupancy_rate) {
                    return strcmp($left->room_name, $right->room_name);
                }

                return $right->occupancy_rate <=> $left->occupancy_rate;
            })
            ->take(5)
            ->values();

        $lowest = $rankings
            ->sort(function ($left, $right) {
                if ($left->occupancy_rate === $right->occupancy_rate) {
                    return strcmp($left->room_name, $right->room_name);
                }

                return $left->occupancy_rate <=> $right->occupancy_rate;
            })
            ->take(5)
            ->values();

        $result = new \stdClass();
        $result->highest = $highest->all();
        $result->lowest = $lowest->all();

        return $result;
    }

    protected function buildAgeClassification(Carbon $rangeStart, Carbon $rangeEnd): \stdClass
    {
        $transactions = tr_renter::query()
            ->with(['renter:id,nama,birthday', 'room:id,room_name'])
            ->select('id_renter', 'room_id', 'tgl_mulai', 'tgl_selesai')
            ->whereDate('tgl_mulai', '<=', $rangeEnd->toDateString())
            ->whereDate('tgl_selesai', '>', $rangeStart->toDateString())
            ->orderByDesc('tgl_mulai')
            ->get();

        $latestByRenter = [];
        foreach ($transactions as $transaction) {
            $renterId = (int) $transaction->id_renter;
            if ($renterId <= 0 || isset($latestByRenter[$renterId])) {
                continue;
            }

            $latestByRenter[$renterId] = $transaction;
        }

        $groups = [
            'Anak (<18)' => [],
            '18-25 Tahun' => [],
            '26-35 Tahun' => [],
            '36-45 Tahun' => [],
            '46-55 Tahun' => [],
            '56+ Tahun' => [],
            'Tidak diketahui' => [],
        ];

        foreach ($latestByRenter as $transaction) {
            $renterModel = $transaction->renter;
            $birthday = data_get($renterModel, 'birthday');
            $age = null;

            if (!empty($birthday)) {
                try {
                    $age = Carbon::parse($birthday)->age;
                } catch (\Throwable $e) {
                    $age = null;
                }
            }

            $groupLabel = $this->resolveAgeGroupLabel($age);
            $groups[$groupLabel][] = [
                'name' => data_get($renterModel, 'nama', '-'),
                'age' => $age,
                'birthday' => !empty($birthday) ? Carbon::parse($birthday)->format('d-m-Y') : '-',
                'room_name' => data_get($transaction, 'room.room_name', '-'),
            ];
        }

        $result = new \stdClass();
        $result->items = [];
        $result->total = 0;

        foreach ($groups as $label => $members) {
            $result->items[] = (object) [
                'label' => $label,
                'count' => count($members),
                'members' => $members,
            ];
            $result->total += count($members);
        }

        return $result;
    }

    protected function resolveAgeGroupLabel(?int $age): string
    {
        if ($age === null || $age < 0) {
            return 'Tidak diketahui';
        }

        if ($age < 18) {
            return 'Anak (<18)';
        }

        if ($age <= 25) {
            return '18-25 Tahun';
        }

        if ($age <= 35) {
            return '26-35 Tahun';
        }

        if ($age <= 45) {
            return '36-45 Tahun';
        }

        if ($age <= 55) {
            return '46-55 Tahun';
        }

        return '56+ Tahun';
    }

    protected function resolveRevenueDurationMonths(int $duration, string $unit): int
    {
        $duration = max(1, $duration);
        $normalizedUnit = strtolower(trim($unit));

        if ($normalizedUnit === 'tahun') {
            return $duration * 12;
        }

        if ($normalizedUnit === 'bulan') {
            return $duration;
        }

        return 1;
    }

    protected function buildMonthlyRevenueDetails(Carbon $rangeStart, Carbon $rangeEnd, string $monthKey): array
    {
        $allocations = collect($this->buildMonthlyRevenueAllocations($rangeStart, $rangeEnd))
            ->filter(fn (array $allocation) => $allocation['month_key'] === $monthKey)
            ->values();

        $monthLabel = $allocations->isNotEmpty()
            ? (string) $allocations->first()['month_label']
            : Carbon::createFromFormat('Y-m', $monthKey)->translatedFormat('M Y');

        $items = $allocations->map(function (array $allocation) use ($rangeStart, $rangeEnd) {
            $recognizedStart = Carbon::parse($allocation['recognized_start_date'])->startOfDay();
            $recognizedEnd = Carbon::parse($allocation['recognized_end_date'])->endOfDay();
            $displayStart = $recognizedStart->lt($rangeStart) ? $rangeStart->copy() : $recognizedStart;
            $displayEnd = $recognizedEnd->gt($rangeEnd) ? $rangeEnd->copy() : $recognizedEnd;

            return [
                'transaction_id' => $allocation['transaction_id'],
                'renter_name' => $allocation['renter_name'],
                'room_name' => $allocation['room_name'],
                'period_label' => $allocation['period_label'],
                'tgl_mulai' => Carbon::parse($allocation['tgl_mulai'])->format('d-m-Y'),
                'tgl_selesai' => Carbon::parse($allocation['tgl_selesai'])->format('d-m-Y'),
                'recognized_start_date' => $displayStart->format('d-m-Y'),
                'recognized_end_date' => $displayEnd->format('d-m-Y'),
                'recognized_revenue' => round((float) $allocation['recognized_revenue'], 2),
            ];
        })->all();

        return [
            'month_key' => $monthKey,
            'month_label' => $monthLabel,
            'total_revenue' => round($allocations->sum('recognized_revenue'), 2),
            'total_items' => $allocations->count(),
            'items' => $items,
        ];
    }
}
