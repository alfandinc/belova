<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Dashboard\DashboardController as CustomDashboardController;
use App\Models\DailyJournalTask;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class CeoDashboardController extends Controller
{
    /**
     * Display the CEO Dashboard landing page.
     */
    public function index(Request $request)
    {
        return app(CustomDashboardController::class)->index($request);
    }

    /**
     * Show daily journal tasks for CEO review in a DataTable.
     */
    public function reportedDailyTasks(Request $request)
    {
        if ($request->ajax()) {
            $query = DailyJournalTask::query()
                ->with(['user.employee.division', 'fromUser'])
                ->select('daily_journal_tasks.*');

            // Apply date range filter (task_date) when provided
            if ($request->filled('start') && $request->filled('end')) {
                try {
                    $start = \Illuminate\Support\Carbon::parse($request->input('start'))->startOfDay()->toDateString();
                    $end = \Illuminate\Support\Carbon::parse($request->input('end'))->endOfDay()->toDateString();
                    $query->whereBetween('task_date', [$start, $end]);
                } catch (\Exception $e) {
                    // ignore invalid date formats
                }
            }

            // Apply division filter when provided
            if ($request->filled('division_id')) {
                $divisionId = $request->input('division_id');
                $query->whereHas('user.employee.division', function ($q) use ($divisionId) {
                    $q->where('id', $divisionId);
                });
            }

            return DataTables::eloquent($query)
                ->filter(function ($query) use ($request) {
                    $search = trim((string) data_get($request->input('search'), 'value'));

                    if ($search === '') {
                        return;
                    }

                    $query->where(function ($builder) use ($search) {
                        $builder
                            ->where('title', 'like', "%{$search}%")
                            ->orWhere('note', 'like', "%{$search}%")
                            ->orWhere('status', 'like', "%{$search}%")
                            ->orWhereHas('user', function ($userQuery) use ($search) {
                                $userQuery
                                    ->where('name', 'like', "%{$search}%")
                                    ->orWhere('email', 'like', "%{$search}%");
                            })
                            ->orWhereHas('user.employee', function ($employeeQuery) use ($search) {
                                $employeeQuery
                                    ->where('nama', 'like', "%{$search}%")
                                    ->orWhereHas('division', function ($divisionQuery) use ($search) {
                                        $divisionQuery->where('name', 'like', "%{$search}%");
                                    });
                            })
                            ->orWhereHas('fromUser', function ($fromUserQuery) use ($search) {
                                $fromUserQuery->where('name', 'like', "%{$search}%");
                            });
                    });
                }, true)
                ->addColumn('employee_name', function (DailyJournalTask $task) {
                    return optional($task->user->employee)->nama ?: optional($task->user)->name ?: '-';
                })
                ->addColumn('division_name', function (DailyJournalTask $task) {
                    return optional(optional($task->user)->employee?->division)->name ?: '-';
                })
                ->addColumn('assigned_by', function (DailyJournalTask $task) {
                    return optional($task->fromUser)->name ?: '-';
                })
                ->editColumn('task_date', function (DailyJournalTask $task) {
                    return optional($task->task_date)->format('d M Y') ?: '-';
                })
                ->editColumn('deadline_date', function (DailyJournalTask $task) {
                    return optional($task->deadline_date)->format('d M Y') ?: '-';
                })
                ->editColumn('scheduled_time', function (DailyJournalTask $task) {
                    return $task->scheduled_time ? substr((string) $task->scheduled_time, 0, 5) : '-';
                })
                ->editColumn('status', function (DailyJournalTask $task) {
                    $badgeClass = match ($task->status) {
                        'done' => 'success',
                        'in_progress' => 'warning',
                        'skipped' => 'secondary',
                        default => 'info',
                    };

                    $label = str_replace('_', ' ', ucfirst($task->status));

                    return '<span class="badge badge-soft-' . $badgeClass . '">' . e($label) . '</span>';
                })
                ->addColumn('updated_at_display', function (DailyJournalTask $task) {
                    return optional($task->updated_at)->format('d M Y H:i') ?: '-';
                })
                ->rawColumns(['status'])
                ->make(true);
        }

        // Provide divisions list for the filters
        $divisions = \App\Models\HRD\Division::orderBy('name')->get(['id', 'name']);
        return view('ceodashboard.reported-daily-tasks', compact('divisions'));
    }

    /**
     * Premiere Belova statistics (visits where klinik_id = 1)
     */
    public function premiereBelova(Request $request)
    {
        return $this->renderClinicDashboard($request, 1, 'Premiere Belova', 'ceodashboard.premiere-belova');
    }

    public function belovaSkin(Request $request)
    {
        return $this->renderClinicDashboard($request, 2, 'Belova Skin', 'ceodashboard.belova-skin');
    }

    public function belovaDental(Request $request)
    {
        return $this->renderClinicDashboard($request, 3, 'Belova Dental Care', 'ceodashboard.belova-dental');
    }

    public function belovaCenterLiving(Request $request)
    {
        $now = \Illuminate\Support\Carbon::now();
        $currentYear = (int) $now->format('Y');
        $selectedYear = (int) $request->query('year', $currentYear);

        if ($selectedYear < 2020 || $selectedYear > ($currentYear + 1)) {
            $selectedYear = $currentYear;
        }

        $today = $now->copy()->startOfDay();
        $next30Days = $today->copy()->addDays(30)->endOfDay();

        $rooms = \App\Models\BCL\Rooms::with(['category', 'renter'])->get();
        $roomTotal = $rooms->count();
        $occupiedRooms = $rooms->filter(function ($room) {
            return $room->renter !== null;
        })->count();
        $vacantRooms = max(0, $roomTotal - $occupiedRooms);
        $occupancyRate = $roomTotal > 0 ? round(($occupiedRooms / $roomTotal) * 100, 1) : 0.0;

        $activeRentalQuery = \App\Models\BCL\tr_renter::with(['renter', 'room'])
            ->whereDate('tgl_mulai', '<=', $today->toDateString())
            ->whereDate('tgl_selesai', '>', $today->toDateString());

        $activeRenters = (clone $activeRentalQuery)->distinct('id_renter')->count('id_renter');
        $totalRenters = \App\Models\BCL\renter::count();
        $totalDepositBalance = (float) (\App\Models\BCL\renter::sum('deposit_balance') ?: 0);

        $upcomingCheckouts = \App\Models\BCL\tr_renter::with(['renter', 'room'])
            ->whereBetween('tgl_selesai', [$today->toDateString(), $next30Days->toDateString()])
            ->orderBy('tgl_selesai')
            ->limit(10)
            ->get()
            ->map(function ($transaction) use ($today) {
                $checkoutDate = \Illuminate\Support\Carbon::parse($transaction->tgl_selesai);
                return [
                    'renter_name' => optional($transaction->renter)->nama ?: '-',
                    'room_name' => optional($transaction->room)->room_name ?: '-',
                    'checkout_date' => $checkoutDate->format('Y-m-d'),
                    'days_left' => max(0, $today->diffInDays($checkoutDate, false)),
                ];
            })
            ->values()
            ->all();

        $longestRenters = \Illuminate\Support\Facades\DB::table('bcl_tr_renter')
            ->select('id_renter', \Illuminate\Support\Facades\DB::raw('SUM(DATEDIFF(tgl_selesai, tgl_mulai)) AS total_lama_sewa'))
            ->groupBy('id_renter')
            ->orderByDesc('total_lama_sewa')
            ->limit(10)
            ->get()
            ->map(function ($row) {
                $renter = \App\Models\BCL\renter::find($row->id_renter);
                return [
                    'renter_name' => $renter?->nama ?: 'Renter #' . $row->id_renter,
                    'total_days' => (int) ($row->total_lama_sewa ?? 0),
                ];
            })
            ->values()
            ->all();

        $unpaidTransactions = \App\Models\BCL\Fin_jurnal::leftJoin('bcl_tr_renter', 'bcl_tr_renter.trans_id', '=', 'bcl_fin_jurnal.doc_id')
            ->leftJoin('bcl_renter', 'bcl_renter.id', '=', 'bcl_tr_renter.id_renter')
            ->select(
                \Illuminate\Support\Facades\DB::raw('bcl_fin_jurnal.doc_id as doc_id'),
                \Illuminate\Support\Facades\DB::raw('MAX(bcl_fin_jurnal.tanggal) as tanggal'),
                \Illuminate\Support\Facades\DB::raw('MAX(bcl_renter.nama) as renter_name'),
                \Illuminate\Support\Facades\DB::raw('MAX(bcl_tr_renter.harga) as harga'),
                \Illuminate\Support\Facades\DB::raw('IFNULL(SUM(kredit),0) AS dibayar'),
                \Illuminate\Support\Facades\DB::raw('IFNULL(MAX(bcl_tr_renter.harga) - SUM(kredit),0) AS kurang')
            )
            ->where('bcl_fin_jurnal.identity', 'regexp', 'pemasukan|sewa kamar|upgrade kamar')
            ->groupBy('bcl_fin_jurnal.doc_id')
            ->havingRaw('(MAX(bcl_tr_renter.harga) - SUM(kredit)) > 0')
            ->orderByRaw('MAX(bcl_fin_jurnal.tanggal) DESC')
            ->get();

        $unpaidSummary = [
            'count' => (int) $unpaidTransactions->count(),
            'outstanding_total' => (float) $unpaidTransactions->sum('kurang'),
            'items' => $unpaidTransactions->take(10)->map(function ($row) {
                return [
                    'doc_id' => $row->doc_id,
                    'tanggal' => $row->tanggal,
                    'renter_name' => $row->renter_name ?: '-',
                    'kurang' => (float) ($row->kurang ?? 0),
                ];
            })->values()->all(),
        ];

        $inventoryRows = \App\Models\BCL\Inventory::leftJoin('bcl_rooms', 'bcl_rooms.id', '=', 'bcl_inventories.assigned_to')
            ->leftJoin('bcl_fin_jurnal', function ($join) {
                $join->on('bcl_fin_jurnal.kode_subledger', 'like', 'bcl_inventories.inv_number');
            })
            ->select(
                \Illuminate\Support\Facades\DB::raw('bcl_inventories.inv_number as inv_number'),
                \Illuminate\Support\Facades\DB::raw('MAX(bcl_inventories.id) as inventory_id'),
                \Illuminate\Support\Facades\DB::raw('MAX(bcl_inventories.name) as inv_name'),
                \Illuminate\Support\Facades\DB::raw('MAX(bcl_inventories.assigned_to) as assigned_to'),
                \Illuminate\Support\Facades\DB::raw('MAX(bcl_rooms.room_name) as room_name'),
                \Illuminate\Support\Facades\DB::raw('MAX(bcl_inventories.maintanance_cycle) as maintanance_cycle'),
                \Illuminate\Support\Facades\DB::raw('MAX(bcl_inventories.maintanance_period) as maintanance_period'),
                \Illuminate\Support\Facades\DB::raw('MAX(bcl_fin_jurnal.tanggal) as last_maintanance')
            )
            ->groupBy('bcl_inventories.inv_number')
            ->get();

        $maintenanceDueItems = [];
        foreach ($inventoryRows as $item) {
            $nextMaintenance = null;
            $daysLeft = null;
            if ($item->last_maintanance && $item->maintanance_cycle && $item->maintanance_period) {
                $period = (int) $item->maintanance_period;
                $baseDate = \Illuminate\Support\Carbon::parse($item->last_maintanance);

                if ($item->maintanance_cycle === 'Minggu') {
                    $nextMaintenance = $baseDate->copy()->addWeeks($period);
                } elseif ($item->maintanance_cycle === 'Bulan') {
                    $nextMaintenance = $baseDate->copy()->addMonths($period);
                } elseif ($item->maintanance_cycle === 'Tahun') {
                    $nextMaintenance = $baseDate->copy()->addYears($period);
                }

                if ($nextMaintenance) {
                    $daysLeft = $today->diffInDays($nextMaintenance, false);
                }
            }

            if ($nextMaintenance && $daysLeft <= 7) {
                $maintenanceDueItems[] = [
                    'name' => $item->inv_name ?: ('Inventory ' . $item->inv_number),
                    'room_name' => $item->room_name ?: 'Unassigned',
                    'next_maintenance' => $nextMaintenance->format('Y-m-d'),
                    'days_left' => (int) $daysLeft,
                ];
            }
        }

        usort($maintenanceDueItems, function ($left, $right) {
            return $left['days_left'] <=> $right['days_left'];
        });

        $totalInventories = \App\Models\BCL\Inventory::count();
        $assignedInventories = \App\Models\BCL\Inventory::whereNotNull('assigned_to')->count();
        $unassignedInventories = max(0, $totalInventories - $assignedInventories);

        $roomRevenueRows = \App\Models\BCL\Rooms::leftJoin('bcl_tr_renter', function ($join) use ($selectedYear) {
            $join->on('bcl_tr_renter.room_id', '=', 'bcl_rooms.id');
            $join->where(\Illuminate\Support\Facades\DB::raw('YEAR(bcl_tr_renter.tgl_mulai)'), '=', $selectedYear);
        })
            ->select('bcl_rooms.id', 'bcl_rooms.room_name', \Illuminate\Support\Facades\DB::raw('COALESCE(SUM(bcl_tr_renter.harga),0) as total_value'))
            ->groupBy('bcl_rooms.id', 'bcl_rooms.room_name')
            ->orderByDesc('total_value')
            ->orderBy('bcl_rooms.room_name')
            ->get();

        $revenueChart = [
            'labels' => [],
            'income' => [],
            'expense' => [],
            'net' => [],
        ];
        $monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

        $incomeRows = \App\Models\BCL\Fin_jurnal::selectRaw('MONTH(tanggal) as month_num, SUM(COALESCE(kredit,0)) as total')
            ->whereYear('tanggal', $selectedYear)
            ->where(function ($query) {
                $query->where('identity', 'regexp', 'pemasukan|sewa kamar|upgrade kamar')
                    ->orWhere('kode_akun', '4-10101');
            })
            ->groupBy('month_num')
            ->pluck('total', 'month_num')
            ->toArray();

        $expenseRows = \App\Models\BCL\Fin_jurnal::selectRaw('MONTH(tanggal) as month_num, SUM(COALESCE(debet,0)) as total')
            ->whereYear('tanggal', $selectedYear)
            ->where(function ($query) {
                $query->where('identity', 'Pengeluaran')
                    ->orWhereIn('kode_akun', ['5-10101', '5-10102']);
            })
            ->groupBy('month_num')
            ->pluck('total', 'month_num')
            ->toArray();

        for ($month = 1; $month <= 12; $month++) {
            $income = (float) ($incomeRows[$month] ?? 0);
            $expense = (float) ($expenseRows[$month] ?? 0);
            $revenueChart['labels'][] = $monthNames[$month - 1];
            $revenueChart['income'][] = $income;
            $revenueChart['expense'][] = $expense;
            $revenueChart['net'][] = $income - $expense;
        }

        $totalIncome = array_sum($revenueChart['income']);
        $totalExpense = array_sum($revenueChart['expense']);
        $netRevenue = $totalIncome - $totalExpense;

        $roomFloorStats = $rooms->groupBy(function ($room) {
            return $room->floor !== null ? 'Floor ' . $room->floor : 'Unknown';
        })->map(function ($items, $floor) {
            $occupied = $items->filter(function ($room) {
                return $room->renter !== null;
            })->count();

            return [
                'floor' => $floor,
                'total' => $items->count(),
                'occupied' => $occupied,
                'vacant' => max(0, $items->count() - $occupied),
            ];
        })->values()->all();

        $roomCategoryStats = $rooms->groupBy(function ($room) {
            return optional($room->category)->category_name ?: 'Uncategorized';
        })->map(function ($items, $category) {
            return [
                'name' => $category,
                'count' => $items->count(),
            ];
        })->sortByDesc('count')->values()->all();

        $inventoryByRoom = \App\Models\BCL\Inventory::leftJoin('bcl_rooms', 'bcl_rooms.id', '=', 'bcl_inventories.assigned_to')
            ->selectRaw("COALESCE(NULLIF(TRIM(bcl_rooms.room_name), ''), 'Unassigned') as room_name, COUNT(*) as total")
            ->groupBy('room_name')
            ->orderByDesc('total')
            ->orderBy('room_name')
            ->limit(10)
            ->get()
            ->map(function ($row) {
                return [
                    'name' => (string) $row->room_name,
                    'count' => (int) $row->total,
                ];
            })->values()->all();

        $renterMoveInRows = \App\Models\BCL\tr_renter::selectRaw('MONTH(tgl_mulai) as month_num, COUNT(*) as total')
            ->whereYear('tgl_mulai', $selectedYear)
            ->groupBy('month_num')
            ->pluck('total', 'month_num')
            ->toArray();

        $renterMoveInChart = ['labels' => $monthNames, 'counts' => []];
        for ($month = 1; $month <= 12; $month++) {
            $renterMoveInChart['counts'][] = (int) ($renterMoveInRows[$month] ?? 0);
        }

        return view('ceodashboard.belova-center-living', [
            'selectedYear' => $selectedYear,
            'currentYear' => $currentYear,
            'overview' => [
                'room_total' => $roomTotal,
                'occupied_rooms' => $occupiedRooms,
                'vacant_rooms' => $vacantRooms,
                'occupancy_rate' => $occupancyRate,
                'active_renters' => $activeRenters,
                'total_renters' => $totalRenters,
                'total_income' => $totalIncome,
                'total_expense' => $totalExpense,
                'net_revenue' => $netRevenue,
                'total_deposit_balance' => $totalDepositBalance,
                'unpaid_count' => $unpaidSummary['count'],
                'unpaid_total' => $unpaidSummary['outstanding_total'],
                'inventories_total' => $totalInventories,
                'inventories_assigned' => $assignedInventories,
                'inventories_unassigned' => $unassignedInventories,
                'maintenance_due_count' => count($maintenanceDueItems),
            ],
            'revenueChart' => $revenueChart,
            'roomRevenueRows' => $roomRevenueRows->take(10)->map(function ($row) {
                return [
                    'room_name' => $row->room_name,
                    'total_value' => (float) ($row->total_value ?? 0),
                ];
            })->values()->all(),
            'roomFloorStats' => $roomFloorStats,
            'roomCategoryStats' => $roomCategoryStats,
            'longestRenters' => $longestRenters,
            'upcomingCheckouts' => $upcomingCheckouts,
            'unpaidSummary' => $unpaidSummary,
            'maintenanceDueItems' => array_slice($maintenanceDueItems, 0, 10),
            'inventoryByRoom' => $inventoryByRoom,
            'renterMoveInChart' => $renterMoveInChart,
        ]);
    }

    private function renderClinicDashboard(Request $request, int $clinicId, string $socialBrand, string $viewName)
    {
        $now = \Illuminate\Support\Carbon::now();
        $currentYear = (int) $now->format('Y');

        $startDateInput = $request->query('start_date');
        $endDateInput = $request->query('end_date');

        $rangeStart = null;
        $rangeEnd = null;
        if ($startDateInput && $endDateInput) {
            try {
                $rangeStart = \Illuminate\Support\Carbon::parse($startDateInput)->startOfDay();
                $rangeEnd = \Illuminate\Support\Carbon::parse($endDateInput)->endOfDay();
                if ($rangeStart->gt($rangeEnd)) {
                    [$rangeStart, $rangeEnd] = [$rangeEnd->copy()->startOfDay(), $rangeStart->copy()->endOfDay()];
                }
            } catch (\Exception $e) {
                $rangeStart = null;
                $rangeEnd = null;
            }
        }

        if (!$rangeStart || !$rangeEnd) {
            $rangeStart = $now->copy()->startOfYear();
            $rangeEnd = $now->copy()->endOfDay();
        }

        $visitTypeFilter = $this->parseVisitTypeFilter($request);

        $buildGroupedPayload = function ($rangeStart, $rangeEnd) use ($clinicId, $visitTypeFilter) {
            $seriesData = [];
            $revData = [];
            $bucketLabels = [];
            $bucketRanges = [];
            $rangeDays = $rangeStart->copy()->startOfDay()->diffInDays($rangeEnd->copy()->startOfDay()) + 1;

            if ($rangeDays <= 14) {
                $groupBy = 'day';
            } elseif ($rangeDays <= 60) {
                $groupBy = 'week';
            } else {
                $groupBy = 'month';
            }

            if ($groupBy === 'day') {
                $periodStart = $rangeStart->copy()->startOfDay();
                $periodEnd = $rangeEnd->copy()->startOfDay();

                $rows = \Illuminate\Support\Facades\DB::table('erm_visitations as v')
                    ->selectRaw('DATE(v.tanggal_visitation) as bucket, COUNT(*) as total')
                    ->where('v.klinik_id', $clinicId)
                    ->where('v.status_kunjungan', 2)
                    ->whereBetween('v.tanggal_visitation', [$rangeStart->toDateString(), $rangeEnd->toDateString()])
                    ->groupBy('bucket')
                    ->orderBy('bucket')
                    ->pluck('total', 'bucket')
                    ->toArray();

                try {
                    $revRowsQuery = \Illuminate\Support\Facades\DB::table('erm_visitations as v')
                            ->join('finance_transactions as ft', 'ft.visitation_id', '=', 'v.id')
                            ->selectRaw("DATE(ft.tanggal) as bucket, SUM(CASE WHEN LOWER(COALESCE(ft.jenis_transaksi, 'in')) = 'out' THEN -COALESCE(ft.jumlah, 0) ELSE COALESCE(ft.jumlah, 0) END) as revenue")
                        ->where('v.klinik_id', $clinicId)
                        ->where('v.status_kunjungan', 2)
                            ->whereBetween('ft.tanggal', [$rangeStart->toDateTimeString(), $rangeEnd->toDateTimeString()]);

                    $this->applyVisitTypeFilter($revRowsQuery, $visitTypeFilter);

                    $revRows = $revRowsQuery
                        ->groupBy('bucket')
                        ->orderBy('bucket')
                        ->pluck('revenue', 'bucket')
                        ->toArray();
                } catch (\Exception $e) {
                    $revRows = [];
                }

                $cursor = $periodStart->copy();
                while ($cursor->lte($periodEnd)) {
                    $bucketKey = $cursor->format('Y-m-d');
                    $seriesData[] = isset($rows[$bucketKey]) ? (int) $rows[$bucketKey] : 0;
                    $revData[] = isset($revRows[$bucketKey]) ? (float) $revRows[$bucketKey] : 0.0;
                    $bucketLabels[] = $cursor->format('d M Y');
                    $bucketRanges[] = [
                        'start' => $bucketKey,
                        'end' => $bucketKey,
                    ];
                    $cursor->addDay();
                }
            } elseif ($groupBy === 'week') {
                $periodStart = $rangeStart->copy()->startOfWeek();
                $periodEnd = $rangeEnd->copy()->endOfWeek();

                $rows = \Illuminate\Support\Facades\DB::table('erm_visitations as v')
                    ->selectRaw("YEARWEEK(v.tanggal_visitation, 3) as bucket, MIN(DATE(v.tanggal_visitation)) as bucket_date, COUNT(*) as total")
                    ->where('v.klinik_id', $clinicId)
                    ->where('v.status_kunjungan', 2)
                    ->whereBetween('v.tanggal_visitation', [$rangeStart->toDateString(), $rangeEnd->toDateString()])
                    ->groupBy('bucket')
                    ->orderBy('bucket_date')
                    ->get()
                    ->keyBy('bucket');

                try {
                    $revRowsQuery = \Illuminate\Support\Facades\DB::table('erm_visitations as v')
                            ->join('finance_transactions as ft', 'ft.visitation_id', '=', 'v.id')
                            ->selectRaw("YEARWEEK(ft.tanggal, 3) as bucket, SUM(CASE WHEN LOWER(COALESCE(ft.jenis_transaksi, 'in')) = 'out' THEN -COALESCE(ft.jumlah, 0) ELSE COALESCE(ft.jumlah, 0) END) as revenue")
                        ->where('v.klinik_id', $clinicId)
                        ->where('v.status_kunjungan', 2)
                            ->whereBetween('ft.tanggal', [$rangeStart->toDateTimeString(), $rangeEnd->toDateTimeString()]);

                    $this->applyVisitTypeFilter($revRowsQuery, $visitTypeFilter);

                    $revRows = $revRowsQuery
                        ->groupBy('bucket')
                        ->pluck('revenue', 'bucket')
                        ->toArray();
                } catch (\Exception $e) {
                    $revRows = [];
                }

                $cursor = $periodStart->copy();
                while ($cursor->lte($periodEnd)) {
                    $bucketKey = (int) $cursor->format('oW');
                    $weekEnd = $cursor->copy()->endOfWeek();
                    $displayStart = $cursor->copy()->lt($rangeStart) ? $rangeStart->copy() : $cursor->copy();
                    $displayEnd = $weekEnd->copy()->gt($rangeEnd) ? $rangeEnd->copy() : $weekEnd->copy();

                    $seriesData[] = isset($rows[$bucketKey]) ? (int) $rows[$bucketKey]->total : 0;
                    $revData[] = isset($revRows[$bucketKey]) ? (float) $revRows[$bucketKey] : 0.0;
                    $bucketLabels[] = $displayStart->format('d M') . ' - ' . $displayEnd->format('d M');
                    $bucketRanges[] = [
                        'start' => $displayStart->toDateString(),
                        'end' => $displayEnd->toDateString(),
                    ];
                    $cursor->addWeek();
                }
            } else {
                $periodStart = $rangeStart->copy()->startOfMonth();
                $periodEnd = $rangeEnd->copy()->endOfMonth();

                $rows = \Illuminate\Support\Facades\DB::table('erm_visitations as v')
                    ->selectRaw("DATE_FORMAT(v.tanggal_visitation, '%Y-%m-01') as bucket, COUNT(*) as total")
                    ->where('v.klinik_id', $clinicId)
                    ->where('v.status_kunjungan', 2)
                    ->whereBetween('v.tanggal_visitation', [$rangeStart->toDateString(), $rangeEnd->toDateString()])
                    ->groupBy('bucket')
                    ->orderBy('bucket')
                    ->pluck('total', 'bucket')
                    ->toArray();

                try {
                    $revRowsQuery = \Illuminate\Support\Facades\DB::table('erm_visitations as v')
                            ->join('finance_transactions as ft', 'ft.visitation_id', '=', 'v.id')
                            ->selectRaw("DATE_FORMAT(ft.tanggal, '%Y-%m-01') as bucket, SUM(CASE WHEN LOWER(COALESCE(ft.jenis_transaksi, 'in')) = 'out' THEN -COALESCE(ft.jumlah, 0) ELSE COALESCE(ft.jumlah, 0) END) as revenue")
                        ->where('v.klinik_id', $clinicId)
                        ->where('v.status_kunjungan', 2)
                            ->whereBetween('ft.tanggal', [$rangeStart->toDateTimeString(), $rangeEnd->toDateTimeString()]);

                    $this->applyVisitTypeFilter($revRowsQuery, $visitTypeFilter);

                    $revRows = $revRowsQuery
                        ->groupBy('bucket')
                        ->orderBy('bucket')
                        ->pluck('revenue', 'bucket')
                        ->toArray();
                } catch (\Exception $e) {
                    $revRows = [];
                }

                $cursor = $periodStart->copy();
                while ($cursor->lte($periodEnd)) {
                    $bucketKey = $cursor->format('Y-m-01');
                    $monthEnd = $cursor->copy()->endOfMonth();
                    $displayStart = $cursor->copy()->lt($rangeStart) ? $rangeStart->copy() : $cursor->copy();
                    $displayEnd = $monthEnd->copy()->gt($rangeEnd) ? $rangeEnd->copy() : $monthEnd->copy();

                    $seriesData[] = isset($rows[$bucketKey]) ? (int) $rows[$bucketKey] : 0;
                    $revData[] = isset($revRows[$bucketKey]) ? (float) $revRows[$bucketKey] : 0.0;
                    $bucketLabels[] = $displayStart->format('d M') . ' - ' . $displayEnd->format('d M');
                    $bucketRanges[] = [
                        'start' => $displayStart->toDateString(),
                        'end' => $displayEnd->toDateString(),
                    ];
                    $cursor->addMonth();
                }
            }

            return [
                'series' => [[
                    'name' => ucfirst($groupBy) . 'ly Visits',
                    'data' => $seriesData,
                ]],
                'revenues' => [$revData],
                'bucket_labels' => $bucketLabels,
                'bucket_ranges' => $bucketRanges,
                'start_date' => $rangeStart->toDateString(),
                'end_date' => $rangeEnd->toDateString(),
                'filters' => [
                    'start_date' => $rangeStart->toDateString(),
                    'end_date' => $rangeEnd->toDateString(),
                    'group_by' => $groupBy,
                    'visit_type' => $visitTypeFilter ? (string) $visitTypeFilter : 'all',
                ],
            ];
        };

        $initial = $buildGroupedPayload($rangeStart, $rangeEnd);
        $startDate = $initial['start_date'];
        $endDate = $initial['end_date'];

        // compute simple patient retention/new stats for the selected clinic within the selected period
        $stats = [
            'total_patients' => 0,
            'new' => 0,
            'returning' => 0,
            'retention_rate' => 0.0,
            'payment_methods' => [],
            'revenue_total' => 0.0,
            'avg_revenue_per_visit' => 0.0,
            'avg_revenue_per_day' => 0.0,
            'revenue_by_patient_status' => [],
            'revenue_by_spend_class' => [],
            'total_visits' => 0,
            'avg_per_day' => 0.0,
            'avg_per_week' => 0.0,
            'peak_day' => null,
            'peak_week' => null,
            'peak_month' => null,
            'top_doctors' => [],
            'top_doctor_revenue' => [],
            'top_patient_revenue' => [],
            'top_obat_revenue' => [],
            'top_obat_revenue_total' => 0.0,
            'top_treatment_revenue' => [],
            'top_treatment_revenue_total' => 0.0,
            'patient_demographics' => [
                'gender' => ['male' => 0, 'female' => 0, 'other' => 0],
                'age' => [
                    'buckets' => ['0-17' => 0, '18-30' => 0, '31-45' => 0, '46-60' => 0, '61+' => 0],
                    'average' => null,
                ],
            ],
            'medicine' => [
                'total_prescription_items' => 0,
                'total_medicine_qty' => 0,
                'top_obats' => [],
            ],
            'tindakan' => [
                'total_tindakan' => 0,
                'top_tindakans' => [],
            ],
            'vaksin' => [
                'total_visits' => 0,
                'total_tindakan' => 0,
                'visits' => [],
            ],
            'laboratorium' => [
                'total_requests' => 0,
                'completed_requests' => 0,
                'top_labs' => [],
            ],
            'social_media' => [
                'total_plans' => 0,
                'published_plans' => 0,
                'scheduled_plans' => 0,
                'total_reports' => 0,
                'total_interactions' => 0,
                'total_reach' => 0,
                'total_impressions' => 0,
                'avg_eri' => 0.0,
                'avg_err' => 0.0,
                'status_breakdown' => [],
                'platform_breakdown' => [],
                'jenis_breakdown' => [],
                'publish_trend' => [
                    'labels' => [],
                    'counts' => [],
                ],
                'interaction_trend' => [
                    'labels' => [],
                    'interactions' => [],
                    'reach' => [],
                ],
                'top_content' => [],
            ],
        ];
        try {
            $visQ = \Illuminate\Support\Facades\DB::table('erm_visitations')
                ->where('klinik_id', $clinicId)
                ->where('status_kunjungan', 2)
                ->whereBetween('tanggal_visitation', [$startDate, $endDate]);

            $rangeStartCarbon = \Illuminate\Support\Carbon::parse($startDate)->startOfDay();
            $rangeEndCarbon = \Illuminate\Support\Carbon::parse($endDate)->endOfDay();
            $rangeDays = $rangeStartCarbon->diffInDays($rangeEndCarbon) + 1;

            $patientIds = $visQ->pluck('pasien_id')->unique()->filter()->values()->all();
            $total = count($patientIds);
            $stats['total_patients'] = (int)$total;

            $totalVisits = (clone $visQ)->count();
            $stats['total_visits'] = (int) $totalVisits;
            $stats['avg_per_day'] = $rangeDays > 0 ? round($totalVisits / $rangeDays, 1) : 0.0;
            $totalRevenue = 0.0;
            foreach (($initial['revenues'][0] ?? []) as $value) {
                $totalRevenue += (float) $value;
            }

            $revenueVisitCount = $totalVisits;
            if ($visitTypeFilter !== null) {
                $revenueVisitCount = (int) \Illuminate\Support\Facades\DB::table('erm_visitations')
                    ->where('klinik_id', $clinicId)
                    ->where('status_kunjungan', 2)
                    ->where('jenis_kunjungan', $visitTypeFilter)
                    ->whereBetween('tanggal_visitation', [$startDate, $endDate])
                    ->count();
            }

            $stats['revenue_total'] = round($totalRevenue, 2);
            $stats['avg_revenue_per_visit'] = $revenueVisitCount > 0 ? round($totalRevenue / $revenueVisitCount, 2) : 0.0;
            $stats['avg_revenue_per_day'] = $rangeDays > 0 ? round($totalRevenue / $rangeDays, 2) : 0.0;

            $weekCursor = $rangeStartCarbon->copy()->startOfWeek();
            $weekEndBoundary = $rangeEndCarbon->copy()->endOfWeek();
            $weekBuckets = 0;
            while ($weekCursor->lte($weekEndBoundary)) {
                $weekBuckets++;
                $weekCursor->addWeek();
            }
            $stats['avg_per_week'] = $weekBuckets > 0 ? round($totalVisits / $weekBuckets, 1) : 0.0;

            $newCount = 0;
            $returningCount = 0;
            if ($total > 0) {
                $firstDates = \Illuminate\Support\Facades\DB::table('erm_visitations')
                    ->selectRaw('pasien_id, MIN(tanggal_visitation) as first_date')
                    ->where('klinik_id', $clinicId)
                    ->where('status_kunjungan', 2)
                    ->whereIn('pasien_id', $patientIds)
                    ->groupBy('pasien_id')
                    ->pluck('first_date', 'pasien_id')
                    ->toArray();

                foreach ($patientIds as $pid) {
                    $fd = isset($firstDates[$pid]) ? $firstDates[$pid] : null;
                    if (!$fd) { $newCount++; continue; }
                    try {
                        $firstDt = \Illuminate\Support\Carbon::parse($fd)->toDateString();
                        if ($firstDt >= $startDate) $newCount++; else $returningCount++;
                    } catch (\Exception $e) { $newCount++; }
                }
            }

            $genderCounts = ['male' => 0, 'female' => 0, 'other' => 0];
            $ageBuckets = [
                '0-17' => 0,
                '18-30' => 0,
                '31-45' => 0,
                '46-60' => 0,
                '61+' => 0,
            ];
            $ages = [];

            if (!empty($patientIds)) {
                $pasiens = \App\Models\ERM\Pasien::whereIn('id', $patientIds)->get(['id', 'tanggal_lahir', 'gender']);
                foreach ($pasiens as $pasien) {
                    $gender = strtolower(trim((string) $pasien->gender));
                    $maleValues = ['l', 'm', 'male', 'man', 'laki-laki', 'laki laki', 'laki', 'pria'];
                    $femaleValues = ['p', 'f', 'female', 'woman', 'perempuan', 'wanita'];

                    if (in_array($gender, $maleValues, true)) {
                        $genderCounts['male']++;
                    } elseif (in_array($gender, $femaleValues, true)) {
                        $genderCounts['female']++;
                    } else {
                        $genderCounts['other']++;
                    }

                    if ($pasien->tanggal_lahir) {
                        try {
                            $age = \Illuminate\Support\Carbon::parse($pasien->tanggal_lahir)->age;
                            $ages[] = $age;
                            if ($age <= 17) $ageBuckets['0-17']++;
                            elseif ($age <= 30) $ageBuckets['18-30']++;
                            elseif ($age <= 45) $ageBuckets['31-45']++;
                            elseif ($age <= 60) $ageBuckets['46-60']++;
                            else $ageBuckets['61+']++;
                        } catch (\Exception $e) {
                            // ignore invalid tanggal_lahir
                        }
                    }
                }
            }

            $stats['patient_demographics'] = [
                'gender' => $genderCounts,
                'age' => [
                    'buckets' => $ageBuckets,
                    'average' => !empty($ages) ? round(array_sum($ages) / count($ages), 1) : null,
                ],
            ];

            $stats['new'] = (int)$newCount;
            $stats['returning'] = (int)$returningCount;
            $ret = 0.0;
            if ($total > 0) {
                $ret = ($returningCount / $total) * 100.0;
                $ret = round($ret, 1);
            }
            $stats['retention_rate'] = $ret;
            // compute jenis_kunjungan breakdown for the same period (Konsultasi=1, Beli Produk=2, Lab=3)
            try {
                $jenisRows = \Illuminate\Support\Facades\DB::table('erm_visitations')
                    ->selectRaw('jenis_kunjungan, count(*) as cnt')
                    ->where('klinik_id', $clinicId)
                    ->where('status_kunjungan', 2)
                    ->whereBetween('tanggal_visitation', [$startDate, $endDate])
                    ->groupBy('jenis_kunjungan')
                    ->pluck('cnt', 'jenis_kunjungan')
                    ->toArray();

                $stats['jenis'] = [
                    'konsultasi' => isset($jenisRows[1]) ? (int)$jenisRows[1] : 0,
                    'beli_produk' => isset($jenisRows[2]) ? (int)$jenisRows[2] : 0,
                    'lab' => isset($jenisRows[3]) ? (int)$jenisRows[3] : 0,
                ];
            } catch (\Exception $e) {
                $stats['jenis'] = ['konsultasi' => 0, 'beli_produk' => 0, 'lab' => 0];
            }

            try {
                $paymentRows = \Illuminate\Support\Facades\DB::table('erm_visitations as v')
                    ->leftJoin('erm_metode_bayar as mb', 'v.metode_bayar_id', '=', 'mb.id')
                    ->selectRaw("COALESCE(NULLIF(TRIM(mb.nama), ''), 'Tanpa Metode') as metode, COUNT(*) as cnt")
                    ->where('v.klinik_id', $clinicId)
                    ->where('v.status_kunjungan', 2)
                    ->whereBetween('v.tanggal_visitation', [$startDate, $endDate])
                    ->groupBy('metode')
                    ->orderByDesc('cnt')
                    ->orderBy('metode')
                    ->get();

                $stats['payment_methods'] = $paymentRows->map(function ($row) {
                    return [
                        'name' => (string) ($row->metode ?? 'Tanpa Metode'),
                        'count' => (int) ($row->cnt ?? 0),
                    ];
                })->values()->all();
            } catch (\Exception $e) {
                $stats['payment_methods'] = [];
            }

            try {
                $statusRevenueRows = \Illuminate\Support\Facades\DB::table('finance_transactions as ft')
                    ->join('erm_visitations as v', 'ft.visitation_id', '=', 'v.id')
                    ->leftJoin('erm_pasiens as p', 'v.pasien_id', '=', 'p.id')
                    ->selectRaw("COALESCE(NULLIF(TRIM(p.status_pasien), ''), 'Regular') as status_pasien")
                    ->selectRaw("SUM(CASE WHEN LOWER(COALESCE(ft.jenis_transaksi, 'in')) = 'out' THEN -COALESCE(ft.jumlah, 0) ELSE COALESCE(ft.jumlah, 0) END) as revenue")
                    ->where('v.klinik_id', $clinicId)
                    ->where('v.status_kunjungan', 2)
                    ->whereBetween('ft.tanggal', [$rangeStartCarbon->toDateTimeString(), $rangeEndCarbon->toDateTimeString()]);

                $this->applyVisitTypeFilter($statusRevenueRows, $visitTypeFilter);

                $statusRevenueRows = $statusRevenueRows
                    ->groupBy('p.status_pasien')
                    ->orderByDesc('revenue')
                    ->get();

                $stats['revenue_by_patient_status'] = $statusRevenueRows->map(function ($row) use ($totalRevenue) {
                    $revenue = round((float) ($row->revenue ?? 0), 2);
                    return [
                        'status' => (string) ($row->status_pasien ?? 'Regular'),
                        'revenue' => $revenue,
                        'percentage' => $totalRevenue > 0 ? round(($revenue / $totalRevenue) * 100, 1) : 0.0,
                    ];
                })->values()->all();
            } catch (\Exception $e) {
                $stats['revenue_by_patient_status'] = [];
            }

            try {
                $visitRevenueRows = \Illuminate\Support\Facades\DB::table('finance_transactions as ft')
                    ->join('erm_visitations as v', 'ft.visitation_id', '=', 'v.id')
                    ->selectRaw('v.id as visitation_id')
                    ->selectRaw("SUM(CASE WHEN LOWER(COALESCE(ft.jenis_transaksi, 'in')) = 'out' THEN -COALESCE(ft.jumlah, 0) ELSE COALESCE(ft.jumlah, 0) END) as revenue")
                    ->where('v.klinik_id', $clinicId)
                    ->where('v.status_kunjungan', 2)
                    ->whereBetween('ft.tanggal', [$rangeStartCarbon->toDateTimeString(), $rangeEndCarbon->toDateTimeString()]);

                $this->applyVisitTypeFilter($visitRevenueRows, $visitTypeFilter);

                $visitRevenueRows = $visitRevenueRows
                    ->groupBy('v.id')
                    ->get();

                $spendClasses = [
                    [
                        'key' => 'entry_level',
                        'label' => 'Kelas Bawah',
                        'subtitle' => 'Entry Level',
                        'range' => 'Rp 100.000 - Rp 350.000',
                        'min' => 100000,
                        'max' => 350000,
                    ],
                    [
                        'key' => 'middle_market',
                        'label' => 'Kelas Menengah',
                        'subtitle' => 'Middle Market',
                        'range' => 'Rp 350.000 - Rp 1.500.000',
                        'min' => 350000,
                        'max' => 1500000,
                    ],
                    [
                        'key' => 'high_end',
                        'label' => 'Kelas Atas',
                        'subtitle' => 'Premium / High-End',
                        'range' => '> Rp 1.500.000',
                        'min' => 1500000,
                        'max' => null,
                    ],
                ];

                $counts = [
                    'entry_level' => 0.0,
                    'middle_market' => 0.0,
                    'high_end' => 0.0,
                ];

                foreach ($visitRevenueRows as $row) {
                    $revenue = (float) ($row->revenue ?? 0);
                    if ($revenue >= 100000 && $revenue < 350000) {
                        $counts['entry_level'] += $revenue;
                    } elseif ($revenue >= 350000 && $revenue <= 1500000) {
                        $counts['middle_market'] += $revenue;
                    } elseif ($revenue > 1500000) {
                        $counts['high_end'] += $revenue;
                    }
                }

                $classifiedRevenue = array_sum($counts);
                $stats['revenue_by_spend_class'] = array_map(function ($class) use ($counts, $classifiedRevenue) {
                    $revenue = round((float) ($counts[$class['key']] ?? 0), 2);
                    return [
                        'key' => $class['key'],
                        'label' => $class['label'],
                        'subtitle' => $class['subtitle'],
                        'range' => $class['range'],
                        'revenue' => $revenue,
                        'percentage' => $classifiedRevenue > 0 ? round(($revenue / $classifiedRevenue) * 100, 1) : 0.0,
                    ];
                }, $spendClasses);
            } catch (\Exception $e) {
                $stats['revenue_by_spend_class'] = [];
            }

            try {
                $medicineBase = \Illuminate\Support\Facades\DB::table('erm_resepfarmasi as r')
                    ->join('erm_visitations as v', 'r.visitation_id', '=', 'v.id')
                    ->where('v.klinik_id', $clinicId)
                    ->where('v.status_kunjungan', 2)
                    ->whereBetween('v.tanggal_visitation', [$startDate, $endDate]);

                $this->applyVisitTypeFilter($medicineBase, $visitTypeFilter);

                $stats['medicine']['total_prescription_items'] = (int) (clone $medicineBase)->count();
                $stats['medicine']['total_medicine_qty'] = (int) ((clone $medicineBase)->sum(\Illuminate\Support\Facades\DB::raw('COALESCE(r.jumlah, 0)')) ?: 0);

                $topObatRows = (clone $medicineBase)
                    ->leftJoin('erm_obat as o', 'r.obat_id', '=', 'o.id')
                    ->selectRaw('r.obat_id as obat_id, COALESCE(NULLIF(TRIM(o.nama), \'\'), CONCAT(\'Obat \' , r.obat_id)) as name, SUM(COALESCE(r.jumlah,0)) as total')
                    ->groupBy('r.obat_id', 'o.nama')
                    ->orderByRaw('SUM(COALESCE(r.jumlah,0)) desc')
                    ->orderBy('name')
                    ->limit(10)
                    ->get();

                $stats['medicine']['top_obats'] = $topObatRows->map(function ($row) {
                    return [
                        'id' => $row->obat_id,
                        'name' => (string) ($row->name ?? 'Obat'),
                        'qty' => (int) ($row->total ?? 0),
                    ];
                })->values()->all();

                $obatRevenueExpr = "SUM(GREATEST(0, (COALESCE(fb.jumlah, 0) * COALESCE(NULLIF(fb.qty, 0), 1)) - CASE WHEN fb.diskon_type = '%' THEN ((COALESCE(fb.jumlah, 0) * COALESCE(NULLIF(fb.qty, 0), 1)) * COALESCE(fb.diskon, 0) / 100) ELSE COALESCE(fb.diskon, 0) END))";

                $topObatRevenueBase = \Illuminate\Support\Facades\DB::table('finance_billing as fb')
                    ->join('erm_visitations as v', 'fb.visitation_id', '=', 'v.id')
                    ->join('erm_resepfarmasi as r', 'fb.billable_id', '=', 'r.id')
                    ->leftJoin('erm_obat as o', 'r.obat_id', '=', 'o.id')
                    ->where('fb.billable_type', 'App\\Models\\ERM\\ResepFarmasi')
                    ->where('v.klinik_id', $clinicId)
                    ->where('v.status_kunjungan', 2)
                    ->whereBetween('v.tanggal_visitation', [$startDate, $endDate]);

                $this->applyVisitTypeFilter($topObatRevenueBase, $visitTypeFilter);

                $stats['top_obat_revenue_total'] = round((float) ((clone $topObatRevenueBase)
                    ->selectRaw($obatRevenueExpr . ' as total_revenue')
                    ->value('total_revenue') ?? 0), 2);

                $topObatRevenueRows = $topObatRevenueBase
                    ->selectRaw('r.obat_id as obat_id')
                    ->selectRaw("COALESCE(NULLIF(TRIM(o.nama), ''), CONCAT('Obat ', COALESCE(r.obat_id, '-'))) as obat_name")
                    ->selectRaw($obatRevenueExpr . ' as revenue')
                    ->groupBy('r.obat_id', 'o.nama')
                    ->orderByDesc('revenue')
                    ->orderBy('obat_name')
                    ->limit(10)
                    ->get();

                $stats['top_obat_revenue'] = $topObatRevenueRows->map(function ($row) {
                    return [
                        'id' => $row->obat_id,
                        'name' => (string) ($row->obat_name ?? 'Obat'),
                        'revenue' => round((float) ($row->revenue ?? 0), 2),
                    ];
                })->values()->all();
            } catch (\Exception $e) {
                $stats['medicine'] = ['total_prescription_items' => 0, 'total_medicine_qty' => 0, 'top_obats' => []];
                $stats['top_obat_revenue'] = [];
                $stats['top_obat_revenue_total'] = 0.0;
            }

            try {
                $tindakanBase = \Illuminate\Support\Facades\DB::table('erm_riwayat_tindakan as r')
                    ->join('erm_visitations as v', 'r.visitation_id', '=', 'v.id')
                    ->where('v.klinik_id', $clinicId)
                    ->where('v.status_kunjungan', 2)
                    ->whereBetween('v.tanggal_visitation', [$startDate, $endDate]);

                $this->applyVisitTypeFilter($tindakanBase, $visitTypeFilter);

                $stats['tindakan']['total_tindakan'] = (int) (clone $tindakanBase)->count();

                $topTindakanRows = (clone $tindakanBase)
                    ->join('erm_tindakan as t', 'r.tindakan_id', '=', 't.id')
                    ->selectRaw('r.tindakan_id as tindakan_id, COALESCE(NULLIF(TRIM(t.nama), \'\'), CONCAT(\'Tindakan \' , r.tindakan_id)) as name, COUNT(*) as total')
                    ->groupBy('r.tindakan_id', 't.nama')
                    ->orderByRaw('COUNT(*) desc')
                    ->orderBy('name')
                    ->limit(10)
                    ->get();

                $stats['tindakan']['top_tindakans'] = $topTindakanRows->map(function ($row) {
                    return [
                        'id' => $row->tindakan_id,
                        'name' => (string) ($row->name ?? 'Tindakan'),
                        'count' => (int) ($row->total ?? 0),
                    ];
                })->values()->all();

                $treatmentRevenueExpr = "SUM(GREATEST(0, (COALESCE(fb.jumlah, 0) * COALESCE(NULLIF(fb.qty, 0), 1)) - CASE WHEN fb.diskon_type = '%' THEN ((COALESCE(fb.jumlah, 0) * COALESCE(NULLIF(fb.qty, 0), 1)) * COALESCE(fb.diskon, 0) / 100) ELSE COALESCE(fb.diskon, 0) END))";

                $topTreatmentRevenueBase = \Illuminate\Support\Facades\DB::table('finance_billing as fb')
                    ->join('erm_visitations as v', 'fb.visitation_id', '=', 'v.id')
                    ->join('erm_riwayat_tindakan as r', 'fb.billable_id', '=', 'r.id')
                    ->join('erm_tindakan as t', 'r.tindakan_id', '=', 't.id')
                    ->where('fb.billable_type', 'App\\Models\\ERM\\RiwayatTindakan')
                    ->where('v.klinik_id', $clinicId)
                    ->where('v.status_kunjungan', 2)
                    ->whereBetween('v.tanggal_visitation', [$startDate, $endDate]);

                $this->applyVisitTypeFilter($topTreatmentRevenueBase, $visitTypeFilter);

                $stats['top_treatment_revenue_total'] = round((float) ((clone $topTreatmentRevenueBase)
                    ->selectRaw($treatmentRevenueExpr . ' as total_revenue')
                    ->value('total_revenue') ?? 0), 2);

                $topTreatmentRevenueRows = $topTreatmentRevenueBase
                    ->selectRaw('r.tindakan_id as tindakan_id')
                    ->selectRaw("COALESCE(NULLIF(TRIM(t.nama), ''), CONCAT('Tindakan ', COALESCE(r.tindakan_id, '-'))) as tindakan_name")
                    ->selectRaw($treatmentRevenueExpr . ' as revenue')
                    ->groupBy('r.tindakan_id', 't.nama')
                    ->orderByDesc('revenue')
                    ->orderBy('tindakan_name')
                    ->limit(10)
                    ->get();

                $stats['top_treatment_revenue'] = $topTreatmentRevenueRows->map(function ($row) {
                    return [
                        'id' => $row->tindakan_id,
                        'name' => (string) ($row->tindakan_name ?? 'Tindakan'),
                        'revenue' => round((float) ($row->revenue ?? 0), 2),
                    ];
                })->values()->all();
            } catch (\Exception $e) {
                $stats['tindakan'] = ['total_tindakan' => 0, 'top_tindakans' => []];
                $stats['top_treatment_revenue'] = [];
                $stats['top_treatment_revenue_total'] = 0.0;
            }

            try {
                $vaksinBase = \Illuminate\Support\Facades\DB::table('erm_riwayat_tindakan as r')
                    ->join('erm_visitations as v', 'r.visitation_id', '=', 'v.id')
                    ->join('erm_tindakan as t', 'r.tindakan_id', '=', 't.id')
                    ->leftJoin('erm_pasiens as p', 'v.pasien_id', '=', 'p.id')
                    ->leftJoin('erm_dokters as d', 'v.dokter_id', '=', 'd.id')
                    ->leftJoin('users as u', 'd.user_id', '=', 'u.id')
                    ->where('v.klinik_id', $clinicId)
                    ->where('v.status_kunjungan', 2)
                    ->where('t.is_vaksin', true)
                    ->whereBetween('v.tanggal_visitation', [$startDate, $endDate]);

                $this->applyVisitTypeFilter($vaksinBase, $visitTypeFilter);

                $stats['vaksin']['total_tindakan'] = (int) (clone $vaksinBase)->count();
                $stats['vaksin']['total_visits'] = (int) (clone $vaksinBase)->distinct()->count('v.id');

                $vaksinVisitRows = (clone $vaksinBase)
                    ->selectRaw('v.id as visitation_id, v.tanggal_visitation')
                    ->selectRaw("COALESCE(NULLIF(TRIM(p.nama), ''), CONCAT('Pasien ID ', COALESCE(v.pasien_id, '-'))) as patient_name")
                    ->selectRaw("COALESCE(NULLIF(TRIM(u.name), ''), CONCAT('Dokter ID ', COALESCE(v.dokter_id, '-'))) as doctor_name")
                    ->selectRaw("GROUP_CONCAT(DISTINCT COALESCE(NULLIF(TRIM(t.nama), ''), CONCAT('Tindakan ', COALESCE(r.tindakan_id, '-'))) ORDER BY t.nama SEPARATOR ', ') as tindakan_names")
                    ->groupBy('v.id', 'v.tanggal_visitation', 'p.nama', 'u.name', 'v.pasien_id', 'v.dokter_id')
                    ->orderByDesc('v.tanggal_visitation')
                    ->orderByDesc('v.id')
                    ->get();

                $stats['vaksin']['visits'] = $vaksinVisitRows->map(function ($row) {
                    return [
                        'visitation_id' => (string) ($row->visitation_id ?? '-'),
                        'tanggal_visitation' => $row->tanggal_visitation,
                        'patient_name' => (string) ($row->patient_name ?? 'Pasien Tidak Diketahui'),
                        'doctor_name' => (string) ($row->doctor_name ?? 'Dokter Tidak Diketahui'),
                        'tindakan_names' => (string) ($row->tindakan_names ?? '-'),
                    ];
                })->values()->all();
            } catch (\Exception $e) {
                $stats['vaksin'] = ['total_visits' => 0, 'total_tindakan' => 0, 'visits' => []];
            }

            try {
                $labBase = \Illuminate\Support\Facades\DB::table('erm_lab_permintaan as lp')
                    ->join('erm_visitations as v', 'lp.visitation_id', '=', 'v.id')
                    ->where('v.klinik_id', $clinicId)
                    ->where('v.status_kunjungan', 2)
                    ->whereBetween('v.tanggal_visitation', [$startDate, $endDate]);

                $stats['laboratorium']['total_requests'] = (int) (clone $labBase)->count();
                $stats['laboratorium']['completed_requests'] = (int) ((clone $labBase)->where('lp.status', 'completed')->count());

                $topLabRows = (clone $labBase)
                    ->leftJoin('erm_lab_test as lt', 'lp.lab_test_id', '=', 'lt.id')
                    ->selectRaw('lp.lab_test_id as lab_test_id, COALESCE(NULLIF(TRIM(lt.nama), \'\'), CONCAT(\'Lab \' , lp.lab_test_id)) as name, COUNT(*) as total')
                    ->groupBy('lp.lab_test_id', 'lt.nama')
                    ->orderByRaw('COUNT(*) desc')
                    ->orderBy('name')
                    ->limit(10)
                    ->get();

                $stats['laboratorium']['top_labs'] = $topLabRows->map(function ($row) {
                    return [
                        'id' => $row->lab_test_id,
                        'name' => (string) ($row->name ?? 'Lab'),
                        'count' => (int) ($row->total ?? 0),
                    ];
                })->values()->all();
            } catch (\Exception $e) {
                $stats['laboratorium'] = ['total_requests' => 0, 'completed_requests' => 0, 'top_labs' => []];
            }

            try {
                $peakDayRow = \Illuminate\Support\Facades\DB::table('erm_visitations')
                    ->selectRaw('DATE(tanggal_visitation) as bucket, COUNT(*) as total')
                    ->where('klinik_id', $clinicId)
                    ->where('status_kunjungan', 2)
                    ->whereBetween('tanggal_visitation', [$startDate, $endDate])
                    ->groupBy('bucket')
                    ->orderByDesc('total')
                    ->orderBy('bucket')
                    ->first();

                if ($peakDayRow) {
                    $stats['peak_day'] = [
                        'label' => \Illuminate\Support\Carbon::parse($peakDayRow->bucket)->format('d M Y'),
                        'count' => (int) $peakDayRow->total,
                    ];
                }
            } catch (\Exception $e) {
                $stats['peak_day'] = null;
            }

            try {
                $weekRows = \Illuminate\Support\Facades\DB::table('erm_visitations')
                    ->selectRaw("YEARWEEK(tanggal_visitation, 3) as bucket, MIN(DATE(tanggal_visitation)) as first_date, COUNT(*) as total")
                    ->where('klinik_id', $clinicId)
                    ->where('status_kunjungan', 2)
                    ->whereBetween('tanggal_visitation', [$startDate, $endDate])
                    ->groupBy('bucket')
                    ->orderByDesc('total')
                    ->orderBy('first_date')
                    ->first();

                if ($weekRows) {
                    $weekStart = \Illuminate\Support\Carbon::parse($weekRows->first_date)->startOfWeek();
                    $weekEnd = $weekStart->copy()->endOfWeek();
                    if ($weekStart->lt($rangeStartCarbon)) {
                        $weekStart = $rangeStartCarbon->copy();
                    }
                    if ($weekEnd->gt($rangeEndCarbon)) {
                        $weekEnd = $rangeEndCarbon->copy();
                    }

                    $stats['peak_week'] = [
                        'label' => $weekStart->format('d M') . ' - ' . $weekEnd->format('d M Y'),
                        'count' => (int) $weekRows->total,
                    ];
                }
            } catch (\Exception $e) {
                $stats['peak_week'] = null;
            }

            try {
                $monthRows = \Illuminate\Support\Facades\DB::table('erm_visitations')
                    ->selectRaw("DATE_FORMAT(tanggal_visitation, '%Y-%m-01') as bucket, COUNT(*) as total")
                    ->where('klinik_id', $clinicId)
                    ->where('status_kunjungan', 2)
                    ->whereBetween('tanggal_visitation', [$startDate, $endDate])
                    ->groupBy('bucket')
                    ->orderByDesc('total')
                    ->orderBy('bucket')
                    ->first();

                if ($monthRows) {
                    $monthStart = \Illuminate\Support\Carbon::parse($monthRows->bucket)->startOfMonth();
                    $monthEnd = $monthStart->copy()->endOfMonth();
                    if ($monthStart->lt($rangeStartCarbon)) {
                        $monthStart = $rangeStartCarbon->copy();
                    }
                    if ($monthEnd->gt($rangeEndCarbon)) {
                        $monthEnd = $rangeEndCarbon->copy();
                    }

                    $stats['peak_month'] = [
                        'label' => $monthStart->format('d M') . ' - ' . $monthEnd->format('d M Y'),
                        'count' => (int) $monthRows->total,
                    ];
                }
            } catch (\Exception $e) {
                $stats['peak_month'] = null;
            }

            try {
                $doctorRows = \Illuminate\Support\Facades\DB::table('erm_visitations as v')
                    ->leftJoin('erm_dokters as d', 'v.dokter_id', '=', 'd.id')
                    ->leftJoin('users as u', 'd.user_id', '=', 'u.id')
                    ->selectRaw("COALESCE(NULLIF(TRIM(u.name), ''), CONCAT('Dokter ID ', COALESCE(v.dokter_id, '-'))) as dokter_name, COUNT(*) as cnt")
                    ->where('v.klinik_id', $clinicId)
                    ->where('v.status_kunjungan', 2)
                    ->whereBetween('v.tanggal_visitation', [$startDate, $endDate])
                    ->groupBy('v.dokter_id', 'u.name')
                    ->orderByDesc('cnt')
                    ->orderBy('dokter_name')
                    ->limit(5)
                    ->get();

                $stats['top_doctors'] = $doctorRows->map(function ($row) {
                    return [
                        'name' => (string) ($row->dokter_name ?? 'Dokter Tidak Diketahui'),
                        'count' => (int) ($row->cnt ?? 0),
                    ];
                })->values()->all();
            } catch (\Exception $e) {
                $stats['top_doctors'] = [];
            }

            try {
                $topDoctorRevenueRows = \Illuminate\Support\Facades\DB::table('finance_transactions as ft')
                    ->join('erm_visitations as v', 'ft.visitation_id', '=', 'v.id')
                    ->leftJoin('erm_dokters as d', 'v.dokter_id', '=', 'd.id')
                    ->leftJoin('users as u', 'd.user_id', '=', 'u.id')
                    ->selectRaw('v.dokter_id as dokter_id')
                    ->selectRaw("COALESCE(NULLIF(TRIM(u.name), ''), CONCAT('Dokter ID ', COALESCE(v.dokter_id, '-'))) as dokter_name")
                    ->selectRaw("SUM(CASE WHEN LOWER(COALESCE(ft.jenis_transaksi, 'in')) = 'out' THEN -COALESCE(ft.jumlah, 0) ELSE COALESCE(ft.jumlah, 0) END) as revenue")
                    ->where('v.klinik_id', $clinicId)
                    ->where('v.status_kunjungan', 2)
                    ->whereBetween('ft.tanggal', [$rangeStartCarbon->toDateTimeString(), $rangeEndCarbon->toDateTimeString()]);

                $this->applyVisitTypeFilter($topDoctorRevenueRows, $visitTypeFilter);

                $topDoctorRevenueRows = $topDoctorRevenueRows
                    ->groupBy('v.dokter_id', 'u.name')
                    ->orderByDesc('revenue')
                    ->orderBy('dokter_name')
                    ->limit(10)
                    ->get();

                $stats['top_doctor_revenue'] = $topDoctorRevenueRows->map(function ($row) {
                    return [
                        'id' => isset($row->dokter_id) ? (int) $row->dokter_id : null,
                        'name' => (string) ($row->dokter_name ?? 'Dokter Tidak Diketahui'),
                        'revenue' => round((float) ($row->revenue ?? 0), 2),
                    ];
                })->values()->all();
            } catch (\Exception $e) {
                $stats['top_doctor_revenue'] = [];
            }

            try {
                $topPatientRevenueRows = \Illuminate\Support\Facades\DB::table('finance_transactions as ft')
                    ->join('erm_visitations as v', 'ft.visitation_id', '=', 'v.id')
                    ->leftJoin('erm_pasiens as p', 'v.pasien_id', '=', 'p.id')
                    ->selectRaw('v.pasien_id as pasien_id')
                    ->selectRaw("COALESCE(NULLIF(TRIM(p.nama), ''), CONCAT('Pasien ID ', COALESCE(v.pasien_id, '-'))) as pasien_name")
                    ->selectRaw("COALESCE(NULLIF(TRIM(p.status_pasien), ''), 'Regular') as status_pasien")
                    ->selectRaw("SUM(CASE WHEN LOWER(COALESCE(ft.jenis_transaksi, 'in')) = 'out' THEN -COALESCE(ft.jumlah, 0) ELSE COALESCE(ft.jumlah, 0) END) as revenue")
                    ->where('v.klinik_id', $clinicId)
                    ->where('v.status_kunjungan', 2)
                    ->whereBetween('ft.tanggal', [$rangeStartCarbon->toDateTimeString(), $rangeEndCarbon->toDateTimeString()]);

                $this->applyVisitTypeFilter($topPatientRevenueRows, $visitTypeFilter);

                $topPatientRevenueRows = $topPatientRevenueRows
                    ->groupBy('v.pasien_id', 'p.nama', 'p.status_pasien')
                    ->orderByDesc('revenue')
                    ->orderBy('pasien_name')
                    ->limit(10)
                    ->get();

                $stats['top_patient_revenue'] = $topPatientRevenueRows->map(function ($row) {
                    return [
                        'id' => isset($row->pasien_id) ? (string) $row->pasien_id : null,
                        'name' => (string) ($row->pasien_name ?? 'Pasien Tidak Diketahui'),
                        'status_pasien' => (string) ($row->status_pasien ?? 'Regular'),
                        'revenue' => round((float) ($row->revenue ?? 0), 2),
                    ];
                })->values()->all();
            } catch (\Exception $e) {
                $stats['top_patient_revenue'] = [];
            }

            try {
                $socialPlans = \App\Models\Marketing\ContentPlan::query()
                    ->with(['reports' => function ($query) use ($rangeStartCarbon, $rangeEndCarbon) {
                        $query->whereBetween('recorded_at', [
                            $rangeStartCarbon->copy()->startOfDay(),
                            $rangeEndCarbon->copy()->endOfDay(),
                        ])->orderByDesc('recorded_at');
                    }])
                    ->where(function ($query) use ($socialBrand) {
                        $query->whereJsonContains('brand', $socialBrand)
                            ->orWhere('brand', 'like', '%' . $socialBrand . '%');
                    })
                    ->whereBetween('tanggal_publish', [
                        $rangeStartCarbon->copy()->startOfDay(),
                        $rangeEndCarbon->copy()->endOfDay(),
                    ])
                    ->orderBy('tanggal_publish')
                    ->get();

                $socialReports = $socialPlans->flatMap(function ($plan) {
                    return $plan->reports;
                })->values();

                $statusCounts = $socialPlans
                    ->map(function ($plan) {
                        $label = trim((string) ($plan->status ?? ''));
                        return $label !== '' ? ucwords(strtolower($label)) : 'Unknown';
                    })
                    ->countBy()
                    ->sortDesc();

                $platformCounts = $socialPlans
                    ->flatMap(function ($plan) {
                        $platforms = $plan->platform;
                        if (is_string($platforms)) {
                            $platforms = strpos($platforms, ',') !== false
                                ? array_map('trim', explode(',', $platforms))
                                : [trim($platforms)];
                        }

                        return collect(is_array($platforms) ? $platforms : [])
                            ->map(function ($platform) {
                                return trim((string) $platform);
                            })
                            ->filter();
                    })
                    ->countBy()
                    ->sortDesc();

                $jenisCounts = $socialPlans
                    ->flatMap(function ($plan) {
                        $jenis = $plan->jenis_konten;
                        if (is_string($jenis)) {
                            $jenis = strpos($jenis, ',') !== false
                                ? array_map('trim', explode(',', $jenis))
                                : [trim($jenis)];
                        }

                        return collect(is_array($jenis) ? $jenis : [])
                            ->map(function ($item) {
                                return trim((string) $item);
                            })
                            ->filter();
                    })
                    ->countBy()
                    ->sortDesc();

                $publishLabels = [];
                $publishCounts = [];
                $publishMap = $socialPlans
                    ->groupBy(function ($plan) {
                        return optional($plan->tanggal_publish)->format('Y-m');
                    })
                    ->map(function ($items) {
                        return $items->count();
                    });

                $publishCursor = $rangeStartCarbon->copy()->startOfMonth();
                $publishEnd = $rangeEndCarbon->copy()->startOfMonth();
                while ($publishCursor->lte($publishEnd)) {
                    $key = $publishCursor->format('Y-m');
                    $publishLabels[] = $key;
                    $publishCounts[] = (int) ($publishMap->get($key, 0));
                    $publishCursor->addMonth();
                }

                $interactionLabels = [];
                $interactionCounts = [];
                $interactionReach = [];
                $interactionMap = $socialReports
                    ->groupBy(function ($report) {
                        return optional($report->recorded_at)->format('Y-m');
                    })
                    ->map(function ($items) {
                        return [
                            'interactions' => (int) $items->sum(function ($report) {
                                return (int) ($report->likes ?? 0)
                                    + (int) ($report->comments ?? 0)
                                    + (int) ($report->saves ?? 0)
                                    + (int) ($report->shares ?? 0);
                            }),
                            'reach' => (int) $items->sum('reach'),
                        ];
                    });

                $interactionCursor = $rangeStartCarbon->copy()->startOfMonth();
                $interactionEnd = $rangeEndCarbon->copy()->startOfMonth();
                while ($interactionCursor->lte($interactionEnd)) {
                    $key = $interactionCursor->format('Y-m');
                    $interactionLabels[] = $key;
                    $monthStats = $interactionMap->get($key, ['interactions' => 0, 'reach' => 0]);
                    $interactionCounts[] = (int) ($monthStats['interactions'] ?? 0);
                    $interactionReach[] = (int) ($monthStats['reach'] ?? 0);
                    $interactionCursor->addMonth();
                }

                $topContent = $socialPlans
                    ->map(function ($plan) {
                        $reports = $plan->reports;
                        $likes = (int) $reports->sum('likes');
                        $comments = (int) $reports->sum('comments');
                        $saves = (int) $reports->sum('saves');
                        $shares = (int) $reports->sum('shares');

                        $platforms = $plan->platform;
                        if (is_string($platforms)) {
                            $platforms = strpos($platforms, ',') !== false
                                ? array_map('trim', explode(',', $platforms))
                                : [trim($platforms)];
                        }

                        return [
                            'title' => (string) ($plan->judul ?? 'Untitled'),
                            'status' => trim((string) ($plan->status ?? 'Unknown')) ?: 'Unknown',
                            'publish_date' => optional($plan->tanggal_publish)->format('Y-m-d H:i:s'),
                            'platforms' => array_values(array_filter(is_array($platforms) ? $platforms : [])),
                            'reports_count' => (int) $reports->count(),
                            'interactions' => $likes + $comments + $saves + $shares,
                            'reach' => (int) $reports->sum('reach'),
                            'impressions' => (int) $reports->sum('impressions'),
                        ];
                    })
                    ->sortByDesc('interactions')
                    ->take(10)
                    ->values()
                    ->all();

                $stats['social_media'] = [
                    'total_plans' => (int) $socialPlans->count(),
                    'published_plans' => (int) ($statusCounts->get('Published', 0)),
                    'scheduled_plans' => (int) ($statusCounts->get('Scheduled', 0)),
                    'total_reports' => (int) $socialReports->count(),
                    'total_interactions' => (int) $socialReports->sum(function ($report) {
                        return (int) ($report->likes ?? 0)
                            + (int) ($report->comments ?? 0)
                            + (int) ($report->saves ?? 0)
                            + (int) ($report->shares ?? 0);
                    }),
                    'total_reach' => (int) $socialReports->sum('reach'),
                    'total_impressions' => (int) $socialReports->sum('impressions'),
                    'avg_eri' => $socialReports->count() ? round((float) $socialReports->avg('eri'), 4) : 0.0,
                    'avg_err' => $socialReports->count() ? round((float) $socialReports->avg('err'), 4) : 0.0,
                    'status_breakdown' => $statusCounts->map(function ($count, $name) {
                        return ['name' => (string) $name, 'count' => (int) $count];
                    })->values()->all(),
                    'platform_breakdown' => $platformCounts->map(function ($count, $name) {
                        return ['name' => (string) $name, 'count' => (int) $count];
                    })->values()->all(),
                    'jenis_breakdown' => $jenisCounts->map(function ($count, $name) {
                        return ['name' => (string) $name, 'count' => (int) $count];
                    })->values()->all(),
                    'publish_trend' => [
                        'labels' => $publishLabels,
                        'counts' => $publishCounts,
                    ],
                    'interaction_trend' => [
                        'labels' => $interactionLabels,
                        'interactions' => $interactionCounts,
                        'reach' => $interactionReach,
                    ],
                    'top_content' => $topContent,
                ];
            } catch (\Exception $e) {
                $stats['social_media'] = [
                    'total_plans' => 0,
                    'published_plans' => 0,
                    'scheduled_plans' => 0,
                    'total_reports' => 0,
                    'total_interactions' => 0,
                    'total_reach' => 0,
                    'total_impressions' => 0,
                    'avg_eri' => 0.0,
                    'avg_err' => 0.0,
                    'status_breakdown' => [],
                    'platform_breakdown' => [],
                    'jenis_breakdown' => [],
                    'publish_trend' => ['labels' => [], 'counts' => []],
                    'interaction_trend' => ['labels' => [], 'interactions' => [], 'reach' => []],
                    'top_content' => [],
                ];
            }
        } catch (\Exception $e) {
            // ignore and keep zeros
        }

        $initial['stats'] = $stats;

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'series' => $initial['series'],
                'revenues' => ($initial['revenues'] ?? []),
                'bucket_labels' => ($initial['bucket_labels'] ?? []),
                'bucket_ranges' => ($initial['bucket_ranges'] ?? []),
                'stats' => $stats,
                'filters' => $initial['filters'],
            ]);
        }

        $dokterList = \App\Models\ERM\Dokter::where('klinik_id', $clinicId)
            ->orWhereHas('kliniks', function ($query) use ($clinicId) {
                $query->where('erm_klinik.id', $clinicId);
            })
            ->with(['user', 'spesialisasi', 'klinik'])
            ->orderBy('id')
            ->get();

        $treatmentSpecialties = \App\Models\ERM\Spesialisasi::query()
            ->select(['id', 'nama'])
            ->whereIn('id', function ($query) {
                $query->select('spesialis_id')
                    ->from('erm_tindakan')
                    ->whereNotNull('spesialis_id');
            })
            ->orderBy('nama')
            ->get();

        $obatCategories = \App\Models\ERM\Obat::query()
            ->withoutGlobalScopes()
            ->whereNotNull('kategori')
            ->where('kategori', '!=', '')
            ->orderBy('kategori')
            ->distinct()
            ->pluck('kategori')
            ->values();

        return view($viewName, compact('initial', 'currentYear', 'dokterList', 'treatmentSpecialties', 'obatCategories'));
    }

    /**
     * Return dokter data as JSON for AJAX requests.
     */
    public function dokterData(Request $request, $id)
    {
        $dokter = \App\Models\ERM\Dokter::with(['user','spesialisasi','klinik','mapping'])->find($id);
        if (!$dokter) {
            return response()->json(['ok' => false, 'message' => 'Dokter tidak ditemukan'], 404);
        }

        $photo = $dokter->photo ? asset('storage/' . ltrim($dokter->photo, '/')) : asset('img/avatar.png');

        $data = [
            'id' => $dokter->id,
            'name' => $dokter->user->name ?? null,
            'spesialisasi' => $dokter->spesialisasi->nama ?? null,
            'klinik' => $dokter->klinik->nama ?? null,
            'nik' => $dokter->nik ?? null,
            'sip' => $dokter->sip ?? null,
            'str' => $dokter->str ?? null,
            'no_hp' => $dokter->no_hp ?? null,
            'photo' => $photo,
        ];

        return response()->json(['ok' => true, 'data' => $data]);
    }

    public function dokterRevenueTransactions(Request $request, $id)
    {
        $dokter = \App\Models\ERM\Dokter::with('user')->find($id);
        if (!$dokter) {
            return response()->json(['ok' => false, 'message' => 'Dokter tidak ditemukan'], 404);
        }

        $now = \Illuminate\Support\Carbon::now();
        $startDt = $now->copy()->startOfYear();
        $endDt = $now->copy()->endOfDay();

        if ($request->has('all')) {
            $minDate = \Illuminate\Support\Facades\DB::table('finance_transactions as ft')
                ->join('erm_visitations as v', 'ft.visitation_id', '=', 'v.id')
                ->where('v.dokter_id', $id)
                ->where('v.status_kunjungan', 2)
                ->min('ft.tanggal');

            if ($minDate) {
                $startDt = \Illuminate\Support\Carbon::parse($minDate)->startOfDay();
            }
        } elseif ($request->has('start') && $request->has('end')) {
            try {
                $startDt = \Illuminate\Support\Carbon::parse($request->input('start'))->startOfDay();
                $endDt = \Illuminate\Support\Carbon::parse($request->input('end'))->endOfDay();
                if ($startDt->gt($endDt)) {
                    [$startDt, $endDt] = [$endDt->copy()->startOfDay(), $startDt->copy()->endOfDay()];
                }
            } catch (\Exception $e) {
                $startDt = $now->copy()->startOfYear();
                $endDt = $now->copy()->endOfDay();
            }
        }

        $visitTypeFilter = $this->parseVisitTypeFilter($request);

        $rows = \Illuminate\Support\Facades\DB::table('finance_transactions as ft')
            ->join('erm_visitations as v', 'ft.visitation_id', '=', 'v.id')
            ->leftJoin('erm_pasiens as p', 'v.pasien_id', '=', 'p.id')
            ->selectRaw('ft.id')
            ->selectRaw('ft.tanggal as transaction_date')
            ->selectRaw('DATE(v.tanggal_visitation) as visit_date')
            ->selectRaw("COALESCE(NULLIF(TRIM(p.nama), ''), CONCAT('Pasien ID ', COALESCE(v.pasien_id, '-'))) as pasien_name")
            ->selectRaw("CASE WHEN LOWER(COALESCE(ft.jenis_transaksi, 'in')) = 'out' THEN -COALESCE(ft.jumlah, 0) ELSE COALESCE(ft.jumlah, 0) END as amount")
            ->selectRaw('COALESCE(ft.metode_bayar, \'-\') as metode_bayar')
            ->where('v.dokter_id', $id)
            ->where('v.status_kunjungan', 2)
            ->whereBetween('ft.tanggal', [$startDt->toDateTimeString(), $endDt->toDateTimeString()]);

        $this->applyVisitTypeFilter($rows, $visitTypeFilter);

        $rows = $rows
            ->orderByDesc('ft.tanggal')
            ->orderByDesc('ft.id')
            ->limit(200)
            ->get();

        $transactions = $rows->map(function ($row) {
            return [
                'id' => (int) ($row->id ?? 0),
                'transaction_date' => $row->transaction_date ? \Illuminate\Support\Carbon::parse($row->transaction_date)->format('Y-m-d H:i') : null,
                'visit_date' => $row->visit_date ? \Illuminate\Support\Carbon::parse($row->visit_date)->format('Y-m-d') : null,
                'patient_name' => (string) ($row->pasien_name ?? 'Pasien Tidak Diketahui'),
                'amount' => round((float) ($row->amount ?? 0), 2),
                'payment_method' => (string) ($row->metode_bayar ?? '-'),
            ];
        })->values()->all();

        return response()->json([
            'ok' => true,
            'doctor' => [
                'id' => (int) $dokter->id,
                'name' => (string) ($dokter->user->name ?? ('Dokter ID ' . $dokter->id)),
            ],
            'filters' => [
                'start' => $startDt->toDateString(),
                'end' => $endDt->toDateString(),
                'visit_type' => $visitTypeFilter ? (string) $visitTypeFilter : 'all',
            ],
            'transactions' => $transactions,
        ]);
    }

    public function patientRevenueTransactions(Request $request, $id)
    {
        $pasien = \App\Models\ERM\Pasien::find($id);
        if (!$pasien) {
            return response()->json(['ok' => false, 'message' => 'Pasien tidak ditemukan'], 404);
        }

        $now = \Illuminate\Support\Carbon::now();
        $startDt = $now->copy()->startOfYear();
        $endDt = $now->copy()->endOfDay();

        if ($request->has('all')) {
            $minDate = \Illuminate\Support\Facades\DB::table('finance_transactions as ft')
                ->join('erm_visitations as v', 'ft.visitation_id', '=', 'v.id')
                ->where('v.pasien_id', $id)
                ->where('v.status_kunjungan', 2)
                ->min('ft.tanggal');

            if ($minDate) {
                $startDt = \Illuminate\Support\Carbon::parse($minDate)->startOfDay();
            }
        } elseif ($request->has('start') && $request->has('end')) {
            try {
                $startDt = \Illuminate\Support\Carbon::parse($request->input('start'))->startOfDay();
                $endDt = \Illuminate\Support\Carbon::parse($request->input('end'))->endOfDay();
                if ($startDt->gt($endDt)) {
                    [$startDt, $endDt] = [$endDt->copy()->startOfDay(), $startDt->copy()->endOfDay()];
                }
            } catch (\Exception $e) {
                $startDt = $now->copy()->startOfYear();
                $endDt = $now->copy()->endOfDay();
            }
        }

        $visitTypeFilter = $this->parseVisitTypeFilter($request);

        $rows = \Illuminate\Support\Facades\DB::table('finance_transactions as ft')
            ->join('erm_visitations as v', 'ft.visitation_id', '=', 'v.id')
            ->leftJoin('erm_dokters as d', 'v.dokter_id', '=', 'd.id')
            ->leftJoin('users as u', 'd.user_id', '=', 'u.id')
            ->selectRaw('ft.id')
            ->selectRaw('v.id as visitation_id')
            ->selectRaw('ft.tanggal as transaction_date')
            ->selectRaw('DATE(v.tanggal_visitation) as visit_date')
            ->selectRaw("COALESCE(NULLIF(TRIM(u.name), ''), CONCAT('Dokter ID ', COALESCE(v.dokter_id, '-'))) as dokter_name")
            ->selectRaw('v.jenis_kunjungan as jenis_kunjungan')
            ->selectRaw("CASE WHEN LOWER(COALESCE(ft.jenis_transaksi, 'in')) = 'out' THEN -COALESCE(ft.jumlah, 0) ELSE COALESCE(ft.jumlah, 0) END as amount")
            ->selectRaw('COALESCE(ft.metode_bayar, \'-\') as metode_bayar')
            ->where('v.pasien_id', $id)
            ->where('v.status_kunjungan', 2)
            ->whereBetween('ft.tanggal', [$startDt->toDateTimeString(), $endDt->toDateTimeString()]);

        $this->applyVisitTypeFilter($rows, $visitTypeFilter);

        $rows = $rows
            ->orderByDesc('ft.tanggal')
            ->orderByDesc('ft.id')
            ->limit(200)
            ->get();

        $transactions = $rows->map(function ($row) {
            $jenis = match ((int) ($row->jenis_kunjungan ?? 0)) {
                1 => 'Konsultasi',
                2 => 'Beli Produk',
                3 => 'Lab',
                default => '-',
            };

            return [
                'id' => (int) ($row->id ?? 0),
                'visitation_id' => (int) ($row->visitation_id ?? 0),
                'transaction_date' => $row->transaction_date ? \Illuminate\Support\Carbon::parse($row->transaction_date)->format('Y-m-d H:i') : null,
                'visit_date' => $row->visit_date ? \Illuminate\Support\Carbon::parse($row->visit_date)->format('Y-m-d') : null,
                'doctor_name' => (string) ($row->dokter_name ?? 'Dokter Tidak Diketahui'),
                'visit_type' => $jenis,
                'amount' => round((float) ($row->amount ?? 0), 2),
                'payment_method' => (string) ($row->metode_bayar ?? '-'),
            ];
        })->values()->all();

        return response()->json([
            'ok' => true,
            'patient' => [
                'id' => (string) $pasien->id,
                'name' => (string) ($pasien->nama ?? ('Pasien ID ' . $pasien->id)),
                'status_pasien' => (string) ($pasien->status_pasien ?? 'Regular'),
            ],
            'filters' => [
                'start' => $startDt->toDateString(),
                'end' => $endDt->toDateString(),
                'visit_type' => $visitTypeFilter ? (string) $visitTypeFilter : 'all',
            ],
            'transactions' => $transactions,
        ]);
    }

    public function clinicPatientRevenueRankings(Request $request, $clinicId)
    {
        $now = \Illuminate\Support\Carbon::now();
        $startDt = $now->copy()->startOfYear();
        $endDt = $now->copy()->endOfDay();

        if ($request->has('start') && $request->has('end')) {
            try {
                $startDt = \Illuminate\Support\Carbon::parse($request->input('start'))->startOfDay();
                $endDt = \Illuminate\Support\Carbon::parse($request->input('end'))->endOfDay();
                if ($startDt->gt($endDt)) {
                    [$startDt, $endDt] = [$endDt->copy()->startOfDay(), $startDt->copy()->endOfDay()];
                }
            } catch (\Exception $e) {
                $startDt = $now->copy()->startOfYear();
                $endDt = $now->copy()->endOfDay();
            }
        }

        $visitTypeFilter = $this->parseVisitTypeFilter($request);

        $rows = \Illuminate\Support\Facades\DB::table('finance_transactions as ft')
            ->join('erm_visitations as v', 'ft.visitation_id', '=', 'v.id')
            ->leftJoin('erm_pasiens as p', 'v.pasien_id', '=', 'p.id')
            ->selectRaw('v.pasien_id as pasien_id')
            ->selectRaw("COALESCE(NULLIF(TRIM(p.nama), ''), CONCAT('Pasien ID ', COALESCE(v.pasien_id, '-'))) as pasien_name")
            ->selectRaw("COALESCE(NULLIF(TRIM(p.status_pasien), ''), 'Regular') as status_pasien")
            ->selectRaw("SUM(CASE WHEN LOWER(COALESCE(ft.jenis_transaksi, 'in')) = 'out' THEN -COALESCE(ft.jumlah, 0) ELSE COALESCE(ft.jumlah, 0) END) as revenue")
            ->where('v.klinik_id', $clinicId)
            ->where('v.status_kunjungan', 2)
            ->whereBetween('ft.tanggal', [$startDt->toDateTimeString(), $endDt->toDateTimeString()]);

        $this->applyVisitTypeFilter($rows, $visitTypeFilter);

        $rows = $rows
            ->groupBy('v.pasien_id', 'p.nama', 'p.status_pasien')
            ->orderByDesc('revenue')
            ->orderBy('pasien_name')
            ->get();

        $rankings = $rows->map(function ($row) {
            return [
                'id' => isset($row->pasien_id) ? (string) $row->pasien_id : null,
                'name' => (string) ($row->pasien_name ?? 'Pasien Tidak Diketahui'),
                'status_pasien' => (string) ($row->status_pasien ?? 'Regular'),
                'revenue' => round((float) ($row->revenue ?? 0), 2),
            ];
        })->values()->all();

        return response()->json([
            'ok' => true,
            'clinic_id' => (int) $clinicId,
            'filters' => [
                'start' => $startDt->toDateString(),
                'end' => $endDt->toDateString(),
                'visit_type' => $visitTypeFilter ? (string) $visitTypeFilter : 'all',
            ],
            'rankings' => $rankings,
        ]);
    }

    public function clinicSpendClassVisits(Request $request, $clinicId, $classKey)
    {
        $classMap = [
            'entry_level' => [
                'label' => 'Kelas Bawah',
                'subtitle' => 'Entry Level',
                'range' => 'Rp 100.000 - Rp 350.000',
            ],
            'middle_market' => [
                'label' => 'Kelas Menengah',
                'subtitle' => 'Middle Market',
                'range' => 'Rp 350.000 - Rp 1.500.000',
            ],
            'high_end' => [
                'label' => 'Kelas Atas',
                'subtitle' => 'Premium / High-End',
                'range' => '> Rp 1.500.000',
            ],
        ];

        if (!isset($classMap[$classKey])) {
            return response()->json(['ok' => false, 'message' => 'Spend class tidak ditemukan'], 404);
        }

        $now = \Illuminate\Support\Carbon::now();
        $startDt = $now->copy()->startOfYear();
        $endDt = $now->copy()->endOfDay();

        if ($request->has('start') && $request->has('end')) {
            try {
                $startDt = \Illuminate\Support\Carbon::parse($request->input('start'))->startOfDay();
                $endDt = \Illuminate\Support\Carbon::parse($request->input('end'))->endOfDay();
                if ($startDt->gt($endDt)) {
                    [$startDt, $endDt] = [$endDt->copy()->startOfDay(), $startDt->copy()->endOfDay()];
                }
            } catch (\Exception $e) {
                $startDt = $now->copy()->startOfYear();
                $endDt = $now->copy()->endOfDay();
            }
        }

        $visitTypeFilter = $this->parseVisitTypeFilter($request);

        $revenueExpr = "SUM(CASE WHEN LOWER(COALESCE(ft.jenis_transaksi, 'in')) = 'out' THEN -COALESCE(ft.jumlah, 0) ELSE COALESCE(ft.jumlah, 0) END)";

        $rows = \Illuminate\Support\Facades\DB::table('finance_transactions as ft')
            ->join('erm_visitations as v', 'ft.visitation_id', '=', 'v.id')
            ->leftJoin('erm_pasiens as p', 'v.pasien_id', '=', 'p.id')
            ->leftJoin('erm_dokters as d', 'v.dokter_id', '=', 'd.id')
            ->leftJoin('users as u', 'd.user_id', '=', 'u.id')
            ->selectRaw('v.id as visitation_id')
            ->selectRaw('DATE(v.tanggal_visitation) as visit_date')
            ->selectRaw("COALESCE(NULLIF(TRIM(p.nama), ''), CONCAT('Pasien ID ', COALESCE(v.pasien_id, '-'))) as patient_name")
            ->selectRaw("COALESCE(NULLIF(TRIM(u.name), ''), CONCAT('Dokter ID ', COALESCE(v.dokter_id, '-'))) as doctor_name")
            ->selectRaw('v.jenis_kunjungan as visit_type_code')
            ->selectRaw($revenueExpr . ' as revenue')
            ->where('v.klinik_id', $clinicId)
            ->where('v.status_kunjungan', 2)
            ->whereBetween('ft.tanggal', [$startDt->toDateTimeString(), $endDt->toDateTimeString()])
            ->groupBy('v.id', 'v.tanggal_visitation', 'p.nama', 'v.pasien_id', 'u.name', 'v.dokter_id', 'v.jenis_kunjungan');

        $this->applyVisitTypeFilter($rows, $visitTypeFilter);

        if ($classKey === 'entry_level') {
            $rows->havingRaw($revenueExpr . ' >= ? AND ' . $revenueExpr . ' < ?', [100000, 350000]);
        } elseif ($classKey === 'middle_market') {
            $rows->havingRaw($revenueExpr . ' >= ? AND ' . $revenueExpr . ' <= ?', [350000, 1500000]);
        } else {
            $rows->havingRaw($revenueExpr . ' > ?', [1500000]);
        }

        $visits = $rows
            ->orderByDesc('revenue')
            ->orderByDesc('visit_date')
            ->limit(300)
            ->get()
            ->map(function ($row) {
                $visitType = match ((int) ($row->visit_type_code ?? 0)) {
                    1 => 'Konsultasi',
                    2 => 'Beli Produk',
                    3 => 'Lab',
                    default => '-',
                };

                return [
                    'visitation_id' => (int) ($row->visitation_id ?? 0),
                    'visit_date' => $row->visit_date ? \Illuminate\Support\Carbon::parse($row->visit_date)->format('Y-m-d') : null,
                    'patient_name' => (string) ($row->patient_name ?? 'Pasien Tidak Diketahui'),
                    'doctor_name' => (string) ($row->doctor_name ?? 'Dokter Tidak Diketahui'),
                    'visit_type' => $visitType,
                    'revenue' => round((float) ($row->revenue ?? 0), 2),
                ];
            })
            ->values()
            ->all();

        return response()->json([
            'ok' => true,
            'class' => [
                'key' => $classKey,
                'label' => $classMap[$classKey]['label'],
                'subtitle' => $classMap[$classKey]['subtitle'],
                'range' => $classMap[$classKey]['range'],
            ],
            'filters' => [
                'start' => $startDt->toDateString(),
                'end' => $endDt->toDateString(),
                'visit_type' => $visitTypeFilter ? (string) $visitTypeFilter : 'all',
            ],
            'visits' => $visits,
        ]);
    }

    public function clinicObatRevenueRankings(Request $request, $clinicId)
    {
        $now = \Illuminate\Support\Carbon::now();
        $startDt = $now->copy()->startOfYear();
        $endDt = $now->copy()->endOfDay();

        if ($request->has('start') && $request->has('end')) {
            try {
                $startDt = \Illuminate\Support\Carbon::parse($request->input('start'))->startOfDay();
                $endDt = \Illuminate\Support\Carbon::parse($request->input('end'))->endOfDay();
                if ($startDt->gt($endDt)) {
                    [$startDt, $endDt] = [$endDt->copy()->startOfDay(), $startDt->copy()->endOfDay()];
                }
            } catch (\Exception $e) {
                $startDt = $now->copy()->startOfYear();
                $endDt = $now->copy()->endOfDay();
            }
        }

        $visitTypeFilter = $this->parseVisitTypeFilter($request);
        $search = trim((string) $request->query('q', ''));
        $kategori = trim((string) $request->query('kategori', ''));
        $limit = max(1, min((int) $request->query('limit', 25), 100));
        $obatRevenueExpr = "SUM(GREATEST(0, (COALESCE(fb.jumlah, 0) * COALESCE(NULLIF(fb.qty, 0), 1)) - CASE WHEN fb.diskon_type = '%' THEN ((COALESCE(fb.jumlah, 0) * COALESCE(NULLIF(fb.qty, 0), 1)) * COALESCE(fb.diskon, 0) / 100) ELSE COALESCE(fb.diskon, 0) END))";

        $query = \Illuminate\Support\Facades\DB::table('finance_billing as fb')
            ->join('erm_visitations as v', 'fb.visitation_id', '=', 'v.id')
            ->join('erm_resepfarmasi as r', 'fb.billable_id', '=', 'r.id')
            ->leftJoin('erm_obat as o', 'r.obat_id', '=', 'o.id')
            ->where('fb.billable_type', 'App\\Models\\ERM\\ResepFarmasi')
            ->where('v.klinik_id', $clinicId)
            ->where('v.status_kunjungan', 2)
            ->whereBetween('v.tanggal_visitation', [$startDt->toDateString(), $endDt->toDateString()]);

        $this->applyVisitTypeFilter($query, $visitTypeFilter);

        if ($kategori !== '') {
            $query->where('o.kategori', $kategori);
        }

        if ($search !== '') {
            $query->whereRaw("LOWER(COALESCE(NULLIF(TRIM(o.nama), ''), CONCAT('Obat ', COALESCE(r.obat_id, '-')))) LIKE ?", ['%' . mb_strtolower($search) . '%']);
        }

        $totalRevenue = round((float) ((clone $query)
            ->selectRaw($obatRevenueExpr . ' as total_revenue')
            ->value('total_revenue') ?? 0), 2);

        $items = $query
            ->selectRaw('r.obat_id as obat_id')
            ->selectRaw("COALESCE(NULLIF(TRIM(o.nama), ''), CONCAT('Obat ', COALESCE(r.obat_id, '-'))) as obat_name")
            ->selectRaw($obatRevenueExpr . ' as revenue')
            ->groupBy('r.obat_id', 'o.nama')
            ->orderByDesc('revenue')
            ->orderBy('obat_name')
            ->limit($limit)
            ->get()
            ->map(function ($row) {
                return [
                    'id' => (int) ($row->obat_id ?? 0),
                    'name' => (string) ($row->obat_name ?? 'Obat'),
                    'revenue' => round((float) ($row->revenue ?? 0), 2),
                ];
            })
            ->values()
            ->all();

        return response()->json([
            'ok' => true,
            'filters' => [
                'start' => $startDt->toDateString(),
                'end' => $endDt->toDateString(),
                'visit_type' => $visitTypeFilter ? (string) $visitTypeFilter : 'all',
                'q' => $search,
                'kategori' => $kategori,
            ],
            'total_revenue' => $totalRevenue,
            'items' => $items,
        ]);
    }

    public function clinicTreatmentRevenueRankings(Request $request, $clinicId)
    {
        $now = \Illuminate\Support\Carbon::now();
        $startDt = $now->copy()->startOfYear();
        $endDt = $now->copy()->endOfDay();

        if ($request->has('start') && $request->has('end')) {
            try {
                $startDt = \Illuminate\Support\Carbon::parse($request->input('start'))->startOfDay();
                $endDt = \Illuminate\Support\Carbon::parse($request->input('end'))->endOfDay();
                if ($startDt->gt($endDt)) {
                    [$startDt, $endDt] = [$endDt->copy()->startOfDay(), $startDt->copy()->endOfDay()];
                }
            } catch (\Exception $e) {
                $startDt = $now->copy()->startOfYear();
                $endDt = $now->copy()->endOfDay();
            }
        }

        $visitTypeFilter = $this->parseVisitTypeFilter($request);
        $search = trim((string) $request->query('q', ''));
        $spesialisasiId = (int) $request->query('spesialisasi_id', 0);
        $limit = max(1, min((int) $request->query('limit', 25), 100));
        $treatmentRevenueExpr = "SUM(GREATEST(0, (COALESCE(fb.jumlah, 0) * COALESCE(NULLIF(fb.qty, 0), 1)) - CASE WHEN fb.diskon_type = '%' THEN ((COALESCE(fb.jumlah, 0) * COALESCE(NULLIF(fb.qty, 0), 1)) * COALESCE(fb.diskon, 0) / 100) ELSE COALESCE(fb.diskon, 0) END))";

        $query = \Illuminate\Support\Facades\DB::table('finance_billing as fb')
            ->join('erm_visitations as v', 'fb.visitation_id', '=', 'v.id')
            ->join('erm_riwayat_tindakan as rt', 'fb.billable_id', '=', 'rt.id')
            ->join('erm_tindakan as t', 'rt.tindakan_id', '=', 't.id')
            ->where('fb.billable_type', 'App\\Models\\ERM\\RiwayatTindakan')
            ->where('v.klinik_id', $clinicId)
            ->where('v.status_kunjungan', 2)
            ->whereBetween('v.tanggal_visitation', [$startDt->toDateString(), $endDt->toDateString()]);

        $this->applyVisitTypeFilter($query, $visitTypeFilter);

        if ($spesialisasiId > 0) {
            $query->where('t.spesialis_id', $spesialisasiId);
        }

        if ($search !== '') {
            $query->whereRaw("LOWER(COALESCE(NULLIF(TRIM(t.nama), ''), CONCAT('Tindakan ', COALESCE(rt.tindakan_id, '-')))) LIKE ?", ['%' . mb_strtolower($search) . '%']);
        }

        $totalRevenue = round((float) ((clone $query)
            ->selectRaw($treatmentRevenueExpr . ' as total_revenue')
            ->value('total_revenue') ?? 0), 2);

        $items = $query
            ->selectRaw('rt.tindakan_id as tindakan_id')
            ->selectRaw("COALESCE(NULLIF(TRIM(t.nama), ''), CONCAT('Tindakan ', COALESCE(rt.tindakan_id, '-'))) as tindakan_name")
            ->selectRaw($treatmentRevenueExpr . ' as revenue')
            ->groupBy('rt.tindakan_id', 't.nama')
            ->orderByDesc('revenue')
            ->orderBy('tindakan_name')
            ->limit($limit)
            ->get()
            ->map(function ($row) {
                return [
                    'id' => (int) ($row->tindakan_id ?? 0),
                    'name' => (string) ($row->tindakan_name ?? 'Tindakan'),
                    'revenue' => round((float) ($row->revenue ?? 0), 2),
                ];
            })
            ->values()
            ->all();

        return response()->json([
            'ok' => true,
            'filters' => [
                'start' => $startDt->toDateString(),
                'end' => $endDt->toDateString(),
                'visit_type' => $visitTypeFilter ? (string) $visitTypeFilter : 'all',
                'q' => $search,
                'spesialisasi_id' => $spesialisasiId,
            ],
            'total_revenue' => $totalRevenue,
            'items' => $items,
        ]);
    }

    public function clinicObatRevenueDetails(Request $request, $clinicId, $id)
    {
        $obat = \App\Models\ERM\Obat::find($id);
        if (!$obat) {
            return response()->json(['ok' => false, 'message' => 'Obat tidak ditemukan'], 404);
        }

        $now = \Illuminate\Support\Carbon::now();
        $startDt = $now->copy()->startOfYear();
        $endDt = $now->copy()->endOfDay();

        if ($request->has('start') && $request->has('end')) {
            try {
                $startDt = \Illuminate\Support\Carbon::parse($request->input('start'))->startOfDay();
                $endDt = \Illuminate\Support\Carbon::parse($request->input('end'))->endOfDay();
                if ($startDt->gt($endDt)) {
                    [$startDt, $endDt] = [$endDt->copy()->startOfDay(), $startDt->copy()->endOfDay()];
                }
            } catch (\Exception $e) {
                $startDt = $now->copy()->startOfYear();
                $endDt = $now->copy()->endOfDay();
            }
        }

        $visitTypeFilter = $this->parseVisitTypeFilter($request);
        $lineRevenueExpr = "GREATEST(0, (COALESCE(fb.jumlah, 0) * COALESCE(NULLIF(fb.qty, 0), 1)) - CASE WHEN fb.diskon_type = '%' THEN ((COALESCE(fb.jumlah, 0) * COALESCE(NULLIF(fb.qty, 0), 1)) * COALESCE(fb.diskon, 0) / 100) ELSE COALESCE(fb.diskon, 0) END)";

        $rows = \Illuminate\Support\Facades\DB::table('finance_billing as fb')
            ->join('erm_visitations as v', 'fb.visitation_id', '=', 'v.id')
            ->join('erm_resepfarmasi as r', 'fb.billable_id', '=', 'r.id')
            ->leftJoin('erm_pasiens as p', 'v.pasien_id', '=', 'p.id')
            ->leftJoin('erm_dokters as d', 'v.dokter_id', '=', 'd.id')
            ->leftJoin('users as u', 'd.user_id', '=', 'u.id')
            ->selectRaw('fb.id')
            ->selectRaw('DATE(v.tanggal_visitation) as visit_date')
            ->selectRaw("COALESCE(NULLIF(TRIM(p.nama), ''), CONCAT('Pasien ID ', COALESCE(v.pasien_id, '-'))) as patient_name")
            ->selectRaw("COALESCE(NULLIF(TRIM(u.name), ''), CONCAT('Dokter ID ', COALESCE(v.dokter_id, '-'))) as doctor_name")
            ->selectRaw('COALESCE(NULLIF(fb.qty, 0), 1) as qty')
            ->selectRaw($lineRevenueExpr . ' as revenue')
            ->where('fb.billable_type', 'App\\Models\\ERM\\ResepFarmasi')
            ->where('r.obat_id', $id)
            ->where('v.klinik_id', $clinicId)
            ->where('v.status_kunjungan', 2)
            ->whereBetween('v.tanggal_visitation', [$startDt->toDateString(), $endDt->toDateString()]);

        $this->applyVisitTypeFilter($rows, $visitTypeFilter);

        $details = $rows
            ->orderByDesc('v.tanggal_visitation')
            ->orderByDesc('fb.id')
            ->limit(300)
            ->get()
            ->map(function ($row) {
                return [
                    'id' => (int) ($row->id ?? 0),
                    'visit_date' => $row->visit_date ? \Illuminate\Support\Carbon::parse($row->visit_date)->format('Y-m-d') : null,
                    'patient_name' => (string) ($row->patient_name ?? 'Pasien Tidak Diketahui'),
                    'doctor_name' => (string) ($row->doctor_name ?? 'Dokter Tidak Diketahui'),
                    'qty' => (float) ($row->qty ?? 1),
                    'revenue' => round((float) ($row->revenue ?? 0), 2),
                ];
            })
            ->values()
            ->all();

        return response()->json([
            'ok' => true,
            'item' => [
                'type' => 'obat',
                'id' => (int) $obat->id,
                'name' => (string) ($obat->nama ?? ('Obat ' . $obat->id)),
            ],
            'filters' => [
                'start' => $startDt->toDateString(),
                'end' => $endDt->toDateString(),
                'visit_type' => $visitTypeFilter ? (string) $visitTypeFilter : 'all',
            ],
            'details' => $details,
        ]);
    }

    public function clinicTreatmentRevenueDetails(Request $request, $clinicId, $id)
    {
        $tindakan = \App\Models\ERM\Tindakan::find($id);
        if (!$tindakan) {
            return response()->json(['ok' => false, 'message' => 'Tindakan tidak ditemukan'], 404);
        }

        $now = \Illuminate\Support\Carbon::now();
        $startDt = $now->copy()->startOfYear();
        $endDt = $now->copy()->endOfDay();

        if ($request->has('start') && $request->has('end')) {
            try {
                $startDt = \Illuminate\Support\Carbon::parse($request->input('start'))->startOfDay();
                $endDt = \Illuminate\Support\Carbon::parse($request->input('end'))->endOfDay();
                if ($startDt->gt($endDt)) {
                    [$startDt, $endDt] = [$endDt->copy()->startOfDay(), $startDt->copy()->endOfDay()];
                }
            } catch (\Exception $e) {
                $startDt = $now->copy()->startOfYear();
                $endDt = $now->copy()->endOfDay();
            }
        }

        $visitTypeFilter = $this->parseVisitTypeFilter($request);
        $lineRevenueExpr = "GREATEST(0, (COALESCE(fb.jumlah, 0) * COALESCE(NULLIF(fb.qty, 0), 1)) - CASE WHEN fb.diskon_type = '%' THEN ((COALESCE(fb.jumlah, 0) * COALESCE(NULLIF(fb.qty, 0), 1)) * COALESCE(fb.diskon, 0) / 100) ELSE COALESCE(fb.diskon, 0) END)";

        $rows = \Illuminate\Support\Facades\DB::table('finance_billing as fb')
            ->join('erm_visitations as v', 'fb.visitation_id', '=', 'v.id')
            ->join('erm_riwayat_tindakan as rt', 'fb.billable_id', '=', 'rt.id')
            ->leftJoin('erm_pasiens as p', 'v.pasien_id', '=', 'p.id')
            ->leftJoin('erm_dokters as d', 'v.dokter_id', '=', 'd.id')
            ->leftJoin('users as u', 'd.user_id', '=', 'u.id')
            ->selectRaw('fb.id')
            ->selectRaw('DATE(v.tanggal_visitation) as visit_date')
            ->selectRaw("COALESCE(NULLIF(TRIM(p.nama), ''), CONCAT('Pasien ID ', COALESCE(v.pasien_id, '-'))) as patient_name")
            ->selectRaw("COALESCE(NULLIF(TRIM(u.name), ''), CONCAT('Dokter ID ', COALESCE(v.dokter_id, '-'))) as doctor_name")
            ->selectRaw('COALESCE(NULLIF(fb.qty, 0), 1) as qty')
            ->selectRaw($lineRevenueExpr . ' as revenue')
            ->where('fb.billable_type', 'App\\Models\\ERM\\RiwayatTindakan')
            ->where('rt.tindakan_id', $id)
            ->where('v.klinik_id', $clinicId)
            ->where('v.status_kunjungan', 2)
            ->whereBetween('v.tanggal_visitation', [$startDt->toDateString(), $endDt->toDateString()]);

        $this->applyVisitTypeFilter($rows, $visitTypeFilter);

        $details = $rows
            ->orderByDesc('v.tanggal_visitation')
            ->orderByDesc('fb.id')
            ->limit(300)
            ->get()
            ->map(function ($row) {
                return [
                    'id' => (int) ($row->id ?? 0),
                    'visit_date' => $row->visit_date ? \Illuminate\Support\Carbon::parse($row->visit_date)->format('Y-m-d') : null,
                    'patient_name' => (string) ($row->patient_name ?? 'Pasien Tidak Diketahui'),
                    'doctor_name' => (string) ($row->doctor_name ?? 'Dokter Tidak Diketahui'),
                    'qty' => (float) ($row->qty ?? 1),
                    'revenue' => round((float) ($row->revenue ?? 0), 2),
                ];
            })
            ->values()
            ->all();

        return response()->json([
            'ok' => true,
            'item' => [
                'type' => 'tindakan',
                'id' => (int) $tindakan->id,
                'name' => (string) ($tindakan->nama ?? ('Tindakan ' . $tindakan->id)),
            ],
            'filters' => [
                'start' => $startDt->toDateString(),
                'end' => $endDt->toDateString(),
                'visit_type' => $visitTypeFilter ? (string) $visitTypeFilter : 'all',
            ],
            'details' => $details,
        ]);
    }

    private function parseVisitTypeFilter(Request $request, string $key = 'visit_type'): ?int
    {
        $value = $request->query($key);

        if ($value === null || $value === '' || $value === 'all') {
            return null;
        }

        $visitType = (int) $value;

        return in_array($visitType, [1, 2, 3], true) ? $visitType : null;
    }

    private function applyVisitTypeFilter($query, ?int $visitType, string $column = 'v.jenis_kunjungan')
    {
        if ($visitType !== null) {
            $query->where($column, $visitType);
        }

        return $query;
    }

    /**
     * Return visitation statistics (visits per month) for the last 12 months for a dokter.
     */
    public function dokterVisitationStats(Request $request, $id)
    {
        // accept optional start/end query params (YYYY-MM-DD). If provided, use them; otherwise default to last 12 months
        $now = \Illuminate\Support\Carbon::now();
        if ($request->has('all')) {
            // all time requested: try to determine earliest visitation date for this dokter
                $minDate = \App\Models\ERM\Visitation::where('dokter_id', $id)->where('status_kunjungan', 2)->min('tanggal_visitation');
            if ($minDate) {
                $startDt = \Illuminate\Support\Carbon::parse($minDate)->startOfMonth();
                $endDt = $now->copy()->endOfMonth();
            } else {
                // fallback to last 12 months
                $startDt = $now->copy()->subMonths(11)->startOfMonth();
                $endDt = $now->copy()->endOfMonth();
            }
        } elseif ($request->has('start') && $request->has('end')) {
            try {
                // keep exact start/end days (do not expand to month) so we can detect same-month ranges
                $rawStart = $request->input('start');
                $rawEnd = $request->input('end');
                $startExact = \Illuminate\Support\Carbon::parse($rawStart)->startOfDay();
                $endExact = \Illuminate\Support\Carbon::parse($rawEnd)->endOfDay();
                // For building month-period if needed, keep month boundaries as well
                $startDt = $startExact->copy();
                $endDt = $endExact->copy();
            } catch (\Exception $e) {
                $startDt = $now->copy()->subMonths(11)->startOfMonth();
                $endDt = $now->copy()->endOfMonth();
            }
        } else {
            $startDt = $now->copy()->subMonths(11)->startOfMonth();
            $endDt = $now->copy()->endOfMonth();
        }

        // Determine whether we should aggregate by day or by month.
        // If user supplied exact start/end and they fall within the same calendar month, return daily buckets.
        $useDaily = false;
        if ($request->has('start') && $request->has('end')) {
            try {
                $startCheck = \Illuminate\Support\Carbon::parse($request->input('start'));
                $endCheck = \Illuminate\Support\Carbon::parse($request->input('end'));
                if ($startCheck->format('Y-m') === $endCheck->format('Y-m')) {
                    $useDaily = true;
                }
            } catch (\Exception $e) {
                $useDaily = false;
            }
        }

        // Build period format and labels
        if ($useDaily) {
            $periodFmt = "DATE(v.tanggal_visitation)";
            $start = \Illuminate\Support\Carbon::parse($request->input('start'))->startOfDay()->toDateString();
            $end = \Illuminate\Support\Carbon::parse($request->input('end'))->endOfDay()->toDateString();
            $period = new \Carbon\CarbonPeriod($start, '1 day', $end);
            $labels = array_map(function($d){ return $d->format('Y-m-d'); }, iterator_to_array($period));
        } else {
            $periodFmt = "DATE_FORMAT(v.tanggal_visitation, '%Y-%m')";
            $start = $startDt->toDateString();
            $end = $endDt->toDateString();
            $periodRange = new \Carbon\CarbonPeriod($startDt->copy()->startOfMonth(), '1 month', $endDt->copy()->endOfMonth());
            $labels = array_map(function($d){ return $d->format('Y-m'); }, iterator_to_array($periodRange));
        }

        // Query visit counts grouped by period and jenis_kunjungan
        $visQ = \Illuminate\Support\Facades\DB::table('erm_visitations as v')
            ->selectRaw("{$periodFmt} as period, v.jenis_kunjungan as jenis, count(*) as total")
            ->where('v.dokter_id', $id)
            ->where('v.status_kunjungan', 2);
        if ($start && $end) $visQ->whereBetween('v.tanggal_visitation', [$start, $end]);
        $visRows = $visQ->groupBy('period','jenis')->get();

        // Query konsultasi-with-lab counts grouped by period
        $labQ = \Illuminate\Support\Facades\DB::table('erm_visitations as v')
            ->join('erm_lab_permintaan as l', 'l.visitation_id', '=', 'v.id')
            ->selectRaw("{$periodFmt} as period, count(distinct v.id) as total")
            ->where('v.dokter_id', $id)
            ->where('v.status_kunjungan', 2)
            ->where('v.jenis_kunjungan', 1);
        if ($start && $end) $labQ->whereBetween('v.tanggal_visitation', [$start, $end]);
        $labRows = $labQ->groupBy('period')->get();

        // Build maps
        $mapJenis = [];
        foreach ($visRows as $r) {
            $p = $r->period;
            $j = (int)$r->jenis;
            if (!isset($mapJenis[$p])) $mapJenis[$p] = [];
            $mapJenis[$p][$j] = (int)$r->total;
        }
        $mapLab = [];
        foreach ($labRows as $r) { $mapLab[$r->period] = (int)$r->total; }

        // Prepare series arrays
        $seriesMap = [
            'Total' => [],
            'Konsultasi' => [],
            'Konsultasi (Tanpa Lab)' => [],
            'Konsultasi (Dengan Lab)' => [],
            'Beli Produk' => [],
            'Lab' => [],
        ];

        foreach ($labels as $labl) {
            $counts = isset($mapJenis[$labl]) ? $mapJenis[$labl] : [];
            $kons = isset($counts[1]) ? (int)$counts[1] : 0;
            $beli = isset($counts[2]) ? (int)$counts[2] : 0;
            $lab = isset($counts[3]) ? (int)$counts[3] : 0;
            $konsWithLab = isset($mapLab[$labl]) ? (int)$mapLab[$labl] : 0;
            $konsNoLab = max(0, $kons - $konsWithLab);
            $total = $kons + $beli + $lab;

            $seriesMap['Total'][] = $total;
            $seriesMap['Konsultasi'][] = $kons;
            $seriesMap['Konsultasi (Tanpa Lab)'][] = $konsNoLab;
            $seriesMap['Konsultasi (Dengan Lab)'][] = $konsWithLab;
            $seriesMap['Beli Produk'][] = $beli;
            $seriesMap['Lab'][] = $lab;
        }

        // Convert to ApexCharts series format
        $seriesOut = [];
        foreach ($seriesMap as $name => $arr) {
            $seriesOut[] = ['name' => $name, 'data' => $arr];
        }

        return response()->json(['ok' => true, 'labels' => $labels, 'series' => $seriesOut]);
    }

    /**
     * Return visitation breakdown by jenis_kunjungan for a dokter (all time).
     */
    public function dokterVisitationBreakdown(Request $request, $id)
    {
        // aggregated counts by jenis_kunjungan; allow optional start/end filter (YYYY-MM-DD)
            $query = \App\Models\ERM\Visitation::selectRaw("jenis_kunjungan, count(*) as total")->where('dokter_id', $id)->where('status_kunjungan', 2);
        if ($request->has('start') && $request->has('end')) {
            try {
                $s = \Illuminate\Support\Carbon::parse($request->input('start'))->toDateString();
                $e = \Illuminate\Support\Carbon::parse($request->input('end'))->toDateString();
                $query->whereBetween('tanggal_visitation', [$s, $e]);
            } catch (\Exception $e) {
                // ignore parsing errors and use all-time
            }
        }

        $counts = $query->groupBy('jenis_kunjungan')->pluck('total', 'jenis_kunjungan')->toArray();

        $mapping = [1 => 'Konsultasi', 2 => 'Beli Produk', 3 => 'Lab'];

        $breakdown = [];
        foreach ($mapping as $k => $label) {
            $breakdown[$k] = isset($counts[$k]) ? (int)$counts[$k] : 0;
        }

        // Further split Konsultasi into with/without LabPermintaan
        $konsultasiWithLab = 0;
        $konsultasiNoLab = 0;
        try {
            $visQ = \Illuminate\Support\Facades\DB::table('erm_visitations as v')
                ->where('v.dokter_id', $id)
                ->where('v.status_kunjungan', 2)
                ->where('v.jenis_kunjungan', 1);
            if ($request->has('start') && $request->has('end')) {
                $s = \Illuminate\Support\Carbon::parse($request->input('start'))->toDateString();
                $e = \Illuminate\Support\Carbon::parse($request->input('end'))->toDateString();
                $visQ->whereBetween('v.tanggal_visitation', [$s, $e]);
            }

            // count with lab (exists in erm_lab_permintaan)
            $withLab = (clone $visQ)->join('erm_lab_permintaan as l', 'l.visitation_id', '=', 'v.id')
                ->selectRaw('count(distinct v.id) as cnt')
                ->value('cnt');
            $konsultasiWithLab = (int)($withLab ?: 0);

            // total konsultasi from earlier breakdown for jenis_kunjungan=1
            $totalKons = $breakdown[1] ?? 0;
            $konsultasiNoLab = max(0, $totalKons - $konsultasiWithLab);
        } catch (\Exception $e) {
            // ignore DB errors and leave zeros
        }

        // expose both legacy numeric keys and new detailed keys
        $breakdown['konsultasi_with_lab'] = $konsultasiWithLab;
        $breakdown['konsultasi_no_lab'] = $konsultasiNoLab;

        $total = array_sum([($breakdown[1] ?? 0), ($breakdown[2] ?? 0), ($breakdown[3] ?? 0)]);

        return response()->json(['ok' => true, 'breakdown' => $breakdown, 'total' => (int)$total]);
    }

    /**
     * Return retention-like stats for a dokter: number of new patients (first visit in period),
     * returning patients (had earlier visits before period and also visited in period), and retention rate.
     */
    public function dokterRetentionStats(Request $request, $id)
    {
        $now = \Illuminate\Support\Carbon::now();
        // Determine date range: accept start/end (YYYY-MM-DD) or all=1, otherwise default to current month
        if ($request->has('all')) {
            $start = null; $end = null;
        } elseif ($request->has('start') && $request->has('end')) {
            try {
                $start = \Illuminate\Support\Carbon::parse($request->input('start'))->toDateString();
                $end = \Illuminate\Support\Carbon::parse($request->input('end'))->toDateString();
            } catch (\Exception $e) {
                $start = $now->copy()->startOfMonth()->toDateString();
                $end = $now->copy()->endOfMonth()->toDateString();
            }
        } else {
            $start = $now->copy()->startOfMonth()->toDateString();
            $end = $now->copy()->endOfMonth()->toDateString();
        }

        // collect pasien_ids that visited in the period (filter status_kunjungan = 2)
        $visQ = \App\Models\ERM\Visitation::where('dokter_id', $id)->where('status_kunjungan', 2);
        if ($start && $end) {
            $visQ->whereBetween('tanggal_visitation', [$start, $end]);
        }
        $pasienIds = $visQ->pluck('pasien_id')->unique()->filter()->values()->all();
        $total = count($pasienIds);

        $newCount = 0;
        $returningCount = 0;

        if ($total > 0) {
            // fetch first-ever visitation date per pasien for this dokter (across all time)
            $firstDates = \App\Models\ERM\Visitation::selectRaw('pasien_id, MIN(tanggal_visitation) as first_date')
                ->where('dokter_id', $id)
                ->where('status_kunjungan', 2)
                ->whereIn('pasien_id', $pasienIds)
                ->groupBy('pasien_id')
                ->pluck('first_date', 'pasien_id')
                ->toArray();

            foreach ($pasienIds as $pid) {
                $fd = isset($firstDates[$pid]) ? $firstDates[$pid] : null;
                if (!$fd) {
                    // defensively treat as new
                    $newCount++;
                    continue;
                }
                if ($start) {
                    // if first_date is on/after period start -> new, otherwise returning
                    try {
                        $firstDt = \Illuminate\Support\Carbon::parse($fd)->toDateString();
                        if ($firstDt >= $start) $newCount++; else $returningCount++;
                    } catch (\Exception $e) {
                        $newCount++;
                    }
                } else {
                    // no start (all time): by definition first visit falls inside period (all time) -> treat as new
                    $newCount++;
                }
            }
        }

        $retention = 0.0;
        if ($total > 0) {
            $retention = ($returningCount / $total) * 100.0;
            $retention = round($retention, 1);
        }

        return response()->json([
            'ok' => true,
            'total' => (int)$total,
            'new' => (int)$newCount,
            'returning' => (int)$returningCount,
            'retention_rate' => $retention,
        ]);
    }

    /**
     * Return tindakan statistics for a dokter: top tindakan by occurrence in the selected period.
     */
    public function dokterTindakanStats(Request $request, $id)
    {
        $now = \Illuminate\Support\Carbon::now();
        if ($request->has('all')) {
            $start = null; $end = null;
        } elseif ($request->has('start') && $request->has('end')) {
            try {
                $start = \Illuminate\Support\Carbon::parse($request->input('start'))->toDateString();
                $end = \Illuminate\Support\Carbon::parse($request->input('end'))->toDateString();
            } catch (\Exception $e) {
                $start = $now->copy()->startOfMonth()->toDateString();
                $end = $now->copy()->endOfMonth()->toDateString();
            }
        } else {
            $start = $now->copy()->startOfMonth()->toDateString();
            $end = $now->copy()->endOfMonth()->toDateString();
        }

        // Build query: join riwayat tindakan to visitations and tindakan metadata
        $q = \Illuminate\Support\Facades\DB::table('erm_riwayat_tindakan as r')
            ->join('erm_visitations as v', 'r.visitation_id', '=', 'v.id')
            ->join('erm_tindakan as t', 'r.tindakan_id', '=', 't.id')
            ->where('v.dokter_id', $id)
            ->where('v.status_kunjungan', 2);

        if ($start && $end) {
            $q->whereBetween('v.tanggal_visitation', [$start, $end]);
        }

        $rows = $q->selectRaw('r.tindakan_id as tindakan_id, t.nama as name, count(*) as total')
            ->groupBy('r.tindakan_id', 't.nama')
            ->orderByRaw('count(*) desc')
            ->limit(10)
            ->get();

        $tops = [];
        foreach ($rows as $r) {
            $tops[] = [
                'tindakan_id' => $r->tindakan_id,
                'name' => $r->name,
                'count' => (int)$r->total,
            ];
        }

        return response()->json(['ok' => true, 'tops' => $tops]);
    }

    /**
     * Return obat statistics for a dokter: top obat by total jumlah in resep farmasi for the selected period.
     */
    public function dokterObatStats(Request $request, $id)
    {
        $now = \Illuminate\Support\Carbon::now();
        if ($request->has('all')) {
            $start = null; $end = null;
        } elseif ($request->has('start') && $request->has('end')) {
            try {
                $start = \Illuminate\Support\Carbon::parse($request->input('start'))->toDateString();
                $end = \Illuminate\Support\Carbon::parse($request->input('end'))->toDateString();
            } catch (\Exception $e) {
                $start = $now->copy()->startOfMonth()->toDateString();
                $end = $now->copy()->endOfMonth()->toDateString();
            }
        } else {
            $start = $now->copy()->startOfMonth()->toDateString();
            $end = $now->copy()->endOfMonth()->toDateString();
        }

        // Build query: join resep farmasi to visitations and obat metadata
        // Filter obat stats by the visitation's dokter_id to match other statistik endpoints
        $q = \Illuminate\Support\Facades\DB::table('erm_resepfarmasi as r')
            ->join('erm_visitations as v', 'r.visitation_id', '=', 'v.id')
            ->leftJoin('erm_obat as o', 'r.obat_id', '=', 'o.id')
            ->where('v.dokter_id', $id)
            ->where('v.status_kunjungan', 2);

        if ($start && $end) {
            $q->whereBetween('v.tanggal_visitation', [$start, $end]);
        }

        $rows = $q->selectRaw('r.obat_id as obat_id, o.nama as name, SUM(COALESCE(r.jumlah,0)) as total')
            ->groupBy('r.obat_id', 'o.nama')
            ->orderByRaw('SUM(COALESCE(r.jumlah,0)) desc')
            ->limit(20)
            ->get();

        $tops = [];
        foreach ($rows as $r) {
            $tops[] = [
                'obat_id' => $r->obat_id,
                'name' => $r->name ?: ('Obat ' . $r->obat_id),
                'jumlah' => (int)$r->total,
            ];
        }

        return response()->json(['ok' => true, 'tops' => $tops]);
    }

    /**
     * Return patient-level statistics for a dokter: total unique patients (in date range),
     * gender distribution, age buckets and pasien status counts.
     */
    public function dokterPatientStats(Request $request, $id)
    {
        // Determine date range: accept start/end (YYYY-MM-DD) or all=1, otherwise default to current month
        $now = \Illuminate\Support\Carbon::now();
        if ($request->has('all')) {
            $start = null; $end = null;
        } elseif ($request->has('start') && $request->has('end')) {
            try {
                $start = \Illuminate\Support\Carbon::parse($request->input('start'))->toDateString();
                $end = \Illuminate\Support\Carbon::parse($request->input('end'))->toDateString();
            } catch (\Exception $e) {
                $start = $now->copy()->startOfMonth()->toDateString();
                $end = $now->copy()->endOfMonth()->toDateString();
            }
        } else {
            $start = $now->copy()->startOfMonth()->toDateString();
            $end = $now->copy()->endOfMonth()->toDateString();
        }

        // Build visitation query to collect pasien_ids (filter by status_kunjungan = 2)
        $visQ = \App\Models\ERM\Visitation::where('dokter_id', $id)->where('status_kunjungan', 2);
        if ($start && $end) {
            $visQ->whereBetween('tanggal_visitation', [$start, $end]);
        }

        $pasienIds = $visQ->pluck('pasien_id')->unique()->filter()->values()->all();

        $totalPatients = count($pasienIds);

        $genderCounts = ['male' => 0, 'female' => 0, 'other' => 0];
        $ageBuckets = [
            '0-17' => 0,
            '18-30' => 0,
            '31-45' => 0,
            '46-60' => 0,
            '61+' => 0,
        ];
        $ages = [];
        $statusCounts = [];

        if (!empty($pasienIds)) {
            $pasiens = \App\Models\ERM\Pasien::whereIn('id', $pasienIds)->get(['id','tanggal_lahir','gender','status_pasien']);
            foreach ($pasiens as $p) {
                // gender normalization (support Indonesian labels like 'Laki-laki' / 'Perempuan')
                $g = strtolower(trim((string)$p->gender));
                $maleValues = ['l','m','male','man','laki-laki','laki laki','laki','pria'];
                $femaleValues = ['p','f','female','woman','perempuan','wanita'];
                if (in_array($g, $maleValues, true)) $genderCounts['male']++;
                else if (in_array($g, $femaleValues, true)) $genderCounts['female']++;
                else $genderCounts['other']++;

                // age calculation
                if ($p->tanggal_lahir) {
                    try {
                        $age = \Illuminate\Support\Carbon::parse($p->tanggal_lahir)->age;
                        $ages[] = $age;
                        if ($age <= 17) $ageBuckets['0-17']++;
                        else if ($age <= 30) $ageBuckets['18-30']++;
                        else if ($age <= 45) $ageBuckets['31-45']++;
                        else if ($age <= 60) $ageBuckets['46-60']++;
                        else $ageBuckets['61+']++;
                    } catch (\Exception $e) {
                        // ignore invalid dates
                    }
                }

                // status_pasien counts
                $st = (string)($p->status_pasien ?? 'unknown');
                if (!isset($statusCounts[$st])) $statusCounts[$st] = 0;
                $statusCounts[$st]++;
            }
        }

        $avgAge = null;
        if (!empty($ages)) {
            $avgAge = round(array_sum($ages) / count($ages), 1);
        }

        return response()->json([
            'ok' => true,
            'totalPatients' => (int)$totalPatients,
            'gender' => $genderCounts,
            'age' => [
                'buckets' => $ageBuckets,
                'average' => $avgAge,
            ],
            'statusCounts' => $statusCounts,
        ]);
    }

    /**
     * Return top patients (by visit count) for a dokter within optional date range.
     */
    public function dokterTopPatients(Request $request, $id)
    {
        $now = \Illuminate\Support\Carbon::now();
        if ($request->has('all')) {
            $start = null; $end = null;
        } elseif ($request->has('start') && $request->has('end')) {
            try {
                $start = \Illuminate\Support\Carbon::parse($request->input('start'))->toDateString();
                $end = \Illuminate\Support\Carbon::parse($request->input('end'))->toDateString();
            } catch (\Exception $e) {
                $start = $now->copy()->startOfMonth()->toDateString();
                $end = $now->copy()->endOfMonth()->toDateString();
            }
        } else {
            $start = $now->copy()->startOfMonth()->toDateString();
            $end = $now->copy()->endOfMonth()->toDateString();
        }

        // Build visitation query and include a left join to invoices to calculate spend (only count paid invoices)
        $visQ = \App\Models\ERM\Visitation::from('erm_visitations as v')
            ->where('v.dokter_id', $id)
            ->where('v.status_kunjungan', 2)
            ->leftJoin('finance_invoices as inv', 'v.id', '=', 'inv.visitation_id');
        if ($start && $end) {
            $visQ->whereBetween('v.tanggal_visitation', [$start, $end]);
        }

        // Select pasien_id, visit count and sum of paid invoice amounts
        $select = "v.pasien_id, count(*) as total, SUM(CASE WHEN inv.amount_paid IS NOT NULL THEN inv.total_amount ELSE 0 END) as spend";
        $rowsQ = $visQ->selectRaw($select)
            ->groupBy('v.pasien_id');

        // sorting: support 'spend' or 'visits'
        $sort = $request->input('sort', 'visits');
        $dir = strtolower($request->input('dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        if ($sort === 'spend') {
            // order by the computed spend (use the same expression to be safe)
            $rowsQ->orderByRaw("SUM(CASE WHEN inv.amount_paid IS NOT NULL THEN inv.total_amount ELSE 0 END) $dir");
        } else {
            // default: order by visits (total)
            $rowsQ->orderByRaw("count(*) $dir");
        }

        $rows = $rowsQ->limit(10)->get()->toArray();

        $patientIds = array_map(function($r){ return $r['pasien_id']; }, $rows);
        $patients = [];
        if (!empty($patientIds)) {
            $pasiens = \App\Models\ERM\Pasien::whereIn('id', $patientIds)->get();
            foreach ($pasiens as $p) {
                $patients[$p->id] = $p;
            }
        }

        $tops = [];
        // convert rows into tops preserving visits and spend
        foreach ($rows as $r) {
            $pid = $r['pasien_id'];
            $p = isset($patients[$pid]) ? $patients[$pid] : null;
            $name = $p ? ($p->nama ?? $p->name ?? ($p->nama_lengkap ?? null)) : null;
            if (!$name) $name = 'Pasien ' . $pid;
            $tops[] = [
                'pasien_id' => $pid,
                'name' => $name,
                'visits' => (int)($r['total'] ?? 0),
                'spend' => (float)($r['spend'] ?? 0),
            ];
        }

        return response()->json(['ok' => true, 'tops' => $tops]);
    }

    /**
     * Return lab statistics for a dokter: top lab tests by number of completed lab requests
     * within an optional date range. Only counts lab requests with status = 'completed'
     * and visitations where status_kunjungan = 2 (consistent with other statistik endpoints).
     */
    public function dokterLabStats(Request $request, $id)
    {
        $now = \Illuminate\Support\Carbon::now();
        if ($request->has('all')) {
            $start = null; $end = null;
        } elseif ($request->has('start') && $request->has('end')) {
            try {
                $start = \Illuminate\Support\Carbon::parse($request->input('start'))->toDateString();
                $end = \Illuminate\Support\Carbon::parse($request->input('end'))->toDateString();
            } catch (\Exception $e) {
                $start = $now->copy()->startOfMonth()->toDateString();
                $end = $now->copy()->endOfMonth()->toDateString();
            }
        } else {
            $start = $now->copy()->startOfMonth()->toDateString();
            $end = $now->copy()->endOfMonth()->toDateString();
        }

        // Build query: count completed lab_permintaan grouped by lab_test_id
        $q = \Illuminate\Support\Facades\DB::table('erm_lab_permintaan as lp')
            ->join('erm_visitations as v', 'lp.visitation_id', '=', 'v.id')
            ->leftJoin('erm_lab_test as lt', 'lp.lab_test_id', '=', 'lt.id')
            ->selectRaw('lp.lab_test_id as lab_test_id, COALESCE(lt.nama, "") as name, count(*) as total')
            ->where('v.dokter_id', $id)
            ->where('v.status_kunjungan', 2)
            ->where('lp.status', 'completed');

        if ($start && $end) {
            $q->whereBetween('v.tanggal_visitation', [$start, $end]);
        }

        $rows = $q->groupBy('lp.lab_test_id', 'lt.nama')
            ->orderByRaw('count(*) desc')
            ->limit(20)
            ->get();

        $tops = [];
        foreach ($rows as $r) {
            $tops[] = [
                'lab_test_id' => $r->lab_test_id,
                'name' => $r->name ?: ('Tes ' . $r->lab_test_id),
                'count' => (int)$r->total,
            ];
        }

        return response()->json(['ok' => true, 'tops' => $tops]);
    }
}
