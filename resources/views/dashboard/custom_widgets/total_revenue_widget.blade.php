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

    $sumRevenue = function ($startDate, $endDate) {
        return (float) (\Illuminate\Support\Facades\DB::table('finance_invoices')
            ->whereNotNull('payment_date')
            ->whereIn('status', ['paid', 'partial'])
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->sum('total_amount') ?? 0);
    };

    $monthlyRevenue = $sumRevenue($monthStart, $now);
    $monthlyRevenueLastYear = $sumRevenue($lastYearMonthStart, $sameDayLastYear);

    $yearlyRevenue = $sumRevenue($yearStart, $now);
    $yearlyRevenueLastYear = $sumRevenue($lastYearStart, $sameDayLastYear);
    $periodRevenue = $sumRevenue($periodStart, $periodEnd);

    $periodRangeDays = max(1, $periodStart->copy()->startOfDay()->diffInDays($periodEnd->copy()->startOfDay()) + 1);
    $previousPeriodEnd = $periodStart->copy()->subDay()->endOfDay();
    $previousPeriodStart = $previousPeriodEnd->copy()->subDays($periodRangeDays - 1)->startOfDay();
    $previousPeriodRevenue = $sumRevenue($previousPeriodStart, $previousPeriodEnd);
    $averagePerDay = $periodRangeDays > 0 ? ($periodRevenue / $periodRangeDays) : 0;

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
    $periodTrend = $formatTrend($periodRevenue, $previousPeriodRevenue);
@endphp

<div class="card h-100 border-0 shadow-sm" style="border-radius: 18px; overflow: hidden;">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start flex-wrap mb-2">
            <div class="pr-3 mb-2 mb-md-0">
                
                <h5 class="card-title mb-1">{{ $widget->widget_name ?? 'Total Revenue' }}</h5>
                
            </div>
            <div class="text-md-right">
                <div class="small text-muted">Periode aktif</div>
                <div class="font-weight-bold text-dark">{{ $periodStart->translatedFormat('d M Y') }} - {{ $periodEnd->translatedFormat('d M Y') }}</div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="h-100 p-3" style="border-radius: 14px; border: 1px solid rgba(148, 163, 184, 0.14); background: #fbfdff;">
                    <div class="text-muted small text-uppercase font-weight-bold mb-1">Revenue Periode</div>
                    <div class="font-weight-bold text-dark mb-1" style="font-size: 2.1rem; line-height: 1.15;">{{ $formatCurrency($periodRevenue) }}</div>
                    <div class="d-flex align-items-center flex-wrap">
                        <span class="small {{ $periodTrend['class'] }} font-weight-bold mr-2">
                            <i class="{{ $periodTrend['icon'] }} mr-1"></i>{{ $periodTrend['label'] }}
                        </span>
                        <span class="small text-muted">vs periode sebelumnya</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>