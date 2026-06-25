@php
    $periodEnd = ($dashboardFilter['period_end'] ?? \Carbon\Carbon::now())->copy();
    $periodStart = ($dashboardFilter['period_start'] ?? $periodEnd->copy()->startOfMonth())->copy();
    $selectedYear = (int) ($dashboardFilter['year'] ?? $periodEnd->year);
    $selectedMonth = (int) ($dashboardFilter['month'] ?? $periodEnd->month);

    $now = $periodEnd->copy();
    $monthStart = $periodStart->copy()->startOfMonth();
    $yearStart = \Carbon\Carbon::create($selectedYear, 1, 1)->startOfYear();
    $sameDayLastYear = $now->copy()->subYear();
    $lastYearMonthStart = $sameDayLastYear->copy()->startOfMonth();
    $lastYearStart = \Carbon\Carbon::create($selectedYear - 1, 1, 1)->startOfYear();

    $revenueExpression = "COALESCE(SUM(CASE WHEN LOWER(COALESCE(jenis_transaksi, 'in')) = 'out' THEN -COALESCE(jumlah, 0) ELSE COALESCE(jumlah, 0) END), 0)";

    $sumRevenue = function ($startDate, $endDate) use ($revenueExpression) {
        return (float) (\Illuminate\Support\Facades\DB::table('finance_transactions')
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->selectRaw($revenueExpression . ' as revenue')
            ->value('revenue') ?? 0);
    };

    $monthlyRevenue = $sumRevenue($monthStart, $now);
    $monthlyRevenueLastYear = $sumRevenue($lastYearMonthStart, $sameDayLastYear);

    $yearlyRevenue = $sumRevenue($yearStart, $now);
    $yearlyRevenueLastYear = $sumRevenue($lastYearStart, $sameDayLastYear);

    $formatCurrency = function ($amount) {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    };

    $formatTrend = function ($current, $previous) {
        $difference = $current - $previous;

        if (abs($difference) < 0.005) {
            return [
                'direction' => 'flat',
                'icon' => 'fas fa-minus',
                'class' => 'text-muted',
                'label' => '0,0%',
            ];
        }

        if (abs($previous) < 0.005) {
            return [
                'direction' => $difference > 0 ? 'up' : 'down',
                'icon' => $difference > 0 ? 'fas fa-arrow-up' : 'fas fa-arrow-down',
                'class' => 'text-muted',
                'label' => 'N/A',
            ];
        }

        $percent = ($difference / abs($previous)) * 100;

        return [
            'direction' => $difference > 0 ? 'up' : 'down',
            'icon' => $difference > 0 ? 'fas fa-arrow-up' : 'fas fa-arrow-down',
            'class' => $difference > 0 ? 'text-success' : 'text-danger',
            'label' => number_format(abs($percent), 1, ',', '.') . '%',
        ];
    };

    $monthlyTrend = $formatTrend($monthlyRevenue, $monthlyRevenueLastYear);
    $yearlyTrend = $formatTrend($yearlyRevenue, $yearlyRevenueLastYear);
@endphp

<div class="card h-100 shadow-sm border-0">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
                <h5 class="card-title mb-1">{{ $widget->widget_name ?? 'Total Revenue' }}</h5>
                <p class="text-muted mb-0">Ringkasan pendapatan bulan ini dan tahun berjalan.</p>
            </div>
            <span class="badge badge-success">Revenue</span>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3 mb-md-0">
                <div class="border rounded h-100 p-3 bg-light">
                    <div class="text-muted small mb-2">This Month</div>
                    <div class="h4 mb-1 font-weight-bold text-success">{{ $formatCurrency($monthlyRevenue) }}</div>
                    <div class="d-flex align-items-center justify-content-between mt-2">
                        <div class="small {{ $monthlyTrend['class'] }} font-weight-bold">
                            <i class="{{ $monthlyTrend['icon'] }} mr-1"></i>{{ $monthlyTrend['label'] }}
                        </div>
                        <div class="small text-muted">vs {{ \Carbon\Carbon::create($selectedYear - 1, $selectedMonth, 1)->translatedFormat('F Y') }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="border rounded h-100 p-3 bg-light">
                    <div class="text-muted small mb-2">This Year</div>
                    <div class="h4 mb-1 font-weight-bold text-primary">{{ $formatCurrency($yearlyRevenue) }}</div>
                    <div class="d-flex align-items-center justify-content-between mt-2">
                        <div class="small {{ $yearlyTrend['class'] }} font-weight-bold">
                            <i class="{{ $yearlyTrend['icon'] }} mr-1"></i>{{ $yearlyTrend['label'] }}
                        </div>
                        <div class="small text-muted">vs YTD {{ $selectedYear - 1 }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>