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
        $revenueAllocations = $this->buildMonthlyRevenueAllocations($rangeStart, $rangeEnd);
        $response->total_revenue = collect($revenueAllocations)->sum('recognized_revenue');

        $periodRevenue = [];
        $monthlyRevenue = [];

        foreach ($revenueAllocations as $allocation) {
            $periodLabel = $allocation['period_label'];
            $recognizedRevenue = (float) $allocation['recognized_revenue'];
            $monthKey = $allocation['month_key'];
            $monthLabel = $allocation['month_label'];
            $transactionId = (int) $allocation['transaction_id'];

            if (!isset($periodRevenue[$periodLabel])) {
                $periodRevenue[$periodLabel] = [
                    'label' => $periodLabel,
                    'total_transactions' => 0,
                    'total_revenue' => 0,
                    'transaction_ids' => [],
                ];
            }

            if (!in_array($transactionId, $periodRevenue[$periodLabel]['transaction_ids'], true)) {
                $periodRevenue[$periodLabel]['transaction_ids'][] = $transactionId;
                $periodRevenue[$periodLabel]['total_transactions']++;
            }
            $periodRevenue[$periodLabel]['total_revenue'] += $recognizedRevenue;

            if (!isset($monthlyRevenue[$monthKey])) {
                $monthlyRevenue[$monthKey] = [
                    'label' => $monthLabel,
                    'total_revenue' => 0,
                ];
            }

            $monthlyRevenue[$monthKey]['total_revenue'] += $recognizedRevenue;
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

        $items = $allocations->map(function (array $allocation) {
            return [
                'transaction_id' => $allocation['transaction_id'],
                'renter_name' => $allocation['renter_name'],
                'room_name' => $allocation['room_name'],
                'period_label' => $allocation['period_label'],
                'tgl_mulai' => Carbon::parse($allocation['tgl_mulai'])->format('d-m-Y'),
                'tgl_selesai' => Carbon::parse($allocation['tgl_selesai'])->format('d-m-Y'),
                'recognized_start_date' => Carbon::parse($allocation['recognized_start_date'])->format('d-m-Y'),
                'recognized_end_date' => Carbon::parse($allocation['recognized_end_date'])->format('d-m-Y'),
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
