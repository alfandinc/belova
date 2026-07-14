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
    $periodTarget = (float) (\Illuminate\Support\Facades\DB::table('finance_revenue_target')
        ->where('periode_tahun', $selectedYear)
        ->where('periode_bulan', $selectedMonth)
        ->sum('target_amount') ?? 0);
    $periodTargetPercentage = $periodTarget > 0 ? ($periodRevenue / $periodTarget) * 100 : null;

    $categoryRevenue = (object) (\Illuminate\Support\Facades\DB::table('finance_invoice_items as fii')
        ->join('finance_invoices as fi', 'fi.id', '=', 'fii.invoice_id')
        ->whereNotNull('fi.payment_date')
        ->whereIn('fi.status', ['paid', 'partial'])
        ->whereBetween('fi.payment_date', [$periodStart, $periodEnd])
        ->selectRaw("COALESCE(SUM(CASE
            WHEN fii.billable_type LIKE '%RiwayatTindakan%'
                OR fii.billable_type LIKE '%Tindakan%'
                OR LOWER(COALESCE(fii.name, '')) LIKE '%tindakan%'
            THEN fii.final_amount
            ELSE 0
        END), 0) as tindakan_total,
        COALESCE(SUM(CASE
            WHEN fii.billable_type LIKE '%Resep%'
                OR fii.billable_type LIKE '%Obat%'
                OR LOWER(COALESCE(fii.name, '')) LIKE '%obat%'
                OR LOWER(COALESCE(fii.name, '')) LIKE '%resep%'
                OR LOWER(COALESCE(fii.name, '')) LIKE '%produk%'
            THEN fii.final_amount
            ELSE 0
        END), 0) as obat_produk_total,
        COALESCE(SUM(CASE
            WHEN fii.billable_type LIKE '%RiwayatTindakan%'
                OR fii.billable_type LIKE '%Tindakan%'
                OR LOWER(COALESCE(fii.name, '')) LIKE '%tindakan%'
                OR fii.billable_type LIKE '%Resep%'
                OR fii.billable_type LIKE '%Obat%'
                OR LOWER(COALESCE(fii.name, '')) LIKE '%obat%'
                OR LOWER(COALESCE(fii.name, '')) LIKE '%resep%'
                OR LOWER(COALESCE(fii.name, '')) LIKE '%produk%'
            THEN 0
            ELSE fii.final_amount
        END), 0) as lain_lain_total")
        ->first());

    $categoryRevenueLastYear = (object) (\Illuminate\Support\Facades\DB::table('finance_invoice_items as fii')
        ->join('finance_invoices as fi', 'fi.id', '=', 'fii.invoice_id')
        ->whereNotNull('fi.payment_date')
        ->whereIn('fi.status', ['paid', 'partial'])
        ->whereBetween('fi.payment_date', [$periodStart->copy()->subYear(), $periodEnd->copy()->subYear()])
        ->selectRaw("COALESCE(SUM(CASE
            WHEN fii.billable_type LIKE '%RiwayatTindakan%'
                OR fii.billable_type LIKE '%Tindakan%'
                OR LOWER(COALESCE(fii.name, '')) LIKE '%tindakan%'
            THEN fii.final_amount
            ELSE 0
        END), 0) as tindakan_total,
        COALESCE(SUM(CASE
            WHEN fii.billable_type LIKE '%Resep%'
                OR fii.billable_type LIKE '%Obat%'
                OR LOWER(COALESCE(fii.name, '')) LIKE '%obat%'
                OR LOWER(COALESCE(fii.name, '')) LIKE '%resep%'
                OR LOWER(COALESCE(fii.name, '')) LIKE '%produk%'
            THEN fii.final_amount
            ELSE 0
        END), 0) as obat_produk_total,
        COALESCE(SUM(CASE
            WHEN fii.billable_type LIKE '%RiwayatTindakan%'
                OR fii.billable_type LIKE '%Tindakan%'
                OR LOWER(COALESCE(fii.name, '')) LIKE '%tindakan%'
                OR fii.billable_type LIKE '%Resep%'
                OR fii.billable_type LIKE '%Obat%'
                OR LOWER(COALESCE(fii.name, '')) LIKE '%obat%'
                OR LOWER(COALESCE(fii.name, '')) LIKE '%resep%'
                OR LOWER(COALESCE(fii.name, '')) LIKE '%produk%'
            THEN 0
            ELSE fii.final_amount
        END), 0) as lain_lain_total")
        ->first());

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

    $categoryPalette = [
        'tindakan' => '#3b82f6',
        'obat_produk' => '#22c55e',
        'lain_lain' => '#f59e0b',
    ];

    $categoryTrend = [
        'tindakan' => $formatTrend((float) ($categoryRevenue->tindakan_total ?? 0), (float) ($categoryRevenueLastYear->tindakan_total ?? 0)),
        'obat_produk' => $formatTrend((float) ($categoryRevenue->obat_produk_total ?? 0), (float) ($categoryRevenueLastYear->obat_produk_total ?? 0)),
        'lain_lain' => $formatTrend((float) ($categoryRevenue->lain_lain_total ?? 0), (float) ($categoryRevenueLastYear->lain_lain_total ?? 0)),
    ];

    $categoryGrandTotal = (float) ($categoryRevenue->tindakan_total ?? 0) + (float) ($categoryRevenue->obat_produk_total ?? 0) + (float) ($categoryRevenue->lain_lain_total ?? 0);
    $categoryShares = [
        'tindakan' => $categoryGrandTotal > 0 ? (((float) ($categoryRevenue->tindakan_total ?? 0)) / $categoryGrandTotal) * 100 : 0,
        'obat_produk' => $categoryGrandTotal > 0 ? (((float) ($categoryRevenue->obat_produk_total ?? 0)) / $categoryGrandTotal) * 100 : 0,
        'lain_lain' => $categoryGrandTotal > 0 ? (((float) ($categoryRevenue->lain_lain_total ?? 0)) / $categoryGrandTotal) * 100 : 0,
    ];

    $categoryOffset = 0;
    $categorySegments = [];
    foreach (['tindakan', 'obat_produk', 'lain_lain'] as $categoryKey) {
        if (($categoryShares[$categoryKey] ?? 0) <= 0) {
            continue;
        }

        $segmentEnd = min(100, $categoryOffset + $categoryShares[$categoryKey]);
        $categorySegments[] = $categoryPalette[$categoryKey] . ' ' . number_format($categoryOffset, 4, '.', '') . '% ' . number_format($segmentEnd, 4, '.', '') . '%';
        $categoryOffset = $segmentEnd;
    }

    $categoryDonutStyle = !empty($categorySegments)
        ? 'background: conic-gradient(' . implode(', ', $categorySegments) . ');'
        : 'background: #e5e7eb;';
@endphp

<div class="card h-100 border-0 shadow-sm dashboard-widget-card" style="border-radius: 18px; overflow: hidden;">
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
            <div class="col-xl-7 mb-3 mb-xl-0">
                <div class="h-100 p-3" style="border-radius: 14px; border: 1px solid rgba(148, 163, 184, 0.14); background: #fbfdff;">
                    <div class="text-muted small text-uppercase font-weight-bold mb-1">Revenue Periode</div>
                    <div class="font-weight-bold text-dark mb-1" style="font-size: 2.1rem; line-height: 1.15;">{{ $formatCurrency($periodRevenue) }}</div>
                    <div class="d-flex align-items-center flex-wrap">
                        <span class="small {{ $periodTrend['class'] }} font-weight-bold mr-2">
                            <i class="{{ $periodTrend['icon'] }} mr-1"></i>{{ $periodTrend['label'] }}
                        </span>
                        <span class="small text-muted">vs periode sebelumnya</span>
                    </div>
                    <div class="row mt-3">
                        <div class="col-sm-6 mb-2 mb-sm-0">
                            <div class="text-muted" style="font-size: 12px;">Target Bulan Ini</div>
                            <div class="font-weight-bold text-dark">{{ $periodTarget > 0 ? $formatCurrency($periodTarget) : '-' }}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-muted" style="font-size: 12px;">Pencapaian Target</div>
                            <div class="font-weight-bold {{ $periodTargetPercentage !== null && $periodTargetPercentage >= 100 ? 'text-success' : 'text-dark' }}">
                                {{ $periodTargetPercentage !== null ? number_format($periodTargetPercentage, 1, ',', '.') . '%' : '-' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-5">
                <div class="h-100 p-3" style="border-radius: 14px; border: 1px solid rgba(148, 163, 184, 0.14); background: linear-gradient(180deg, #ffffff 0%, #fafaff 100%);">
                    <div class="small text-uppercase text-muted font-weight-bold mb-3">Komposisi Revenue</div>

                    <div class="d-flex justify-content-center align-items-center mb-3">
                        <div class="position-relative d-flex align-items-center justify-content-center" style="width: 180px; height: 180px; border-radius: 50%; {{ $categoryDonutStyle }}">
                            <div class="d-flex flex-column align-items-center justify-content-center text-center bg-white" style="width: 108px; height: 108px; border-radius: 50%; box-shadow: inset 0 0 0 1px rgba(148, 163, 184, 0.12);">
                                <div class="text-muted" style="font-size: 11px;">TOTAL</div>
                                <div class="font-weight-bold text-dark">{{ $formatCurrency($categoryGrandTotal) }}</div>
                                <div class="small {{ $periodTrend['class'] }} font-weight-bold" style="font-size: 10px;">
                                    <i class="{{ $periodTrend['icon'] }} mr-1"></i>{{ $periodTrend['label'] }}
                                </div>
                                <div class="text-muted" style="font-size: 11px;">
                                    {{ $periodTargetPercentage !== null ? number_format($periodTargetPercentage, 1, ',', '.') . '% target' : 'Target belum ada' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3 p-3" style="border-radius: 14px; background: rgba(241, 245, 249, 0.75);">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <span class="text-muted d-flex align-items-center" style="font-size: 11px;">
                                <span class="mr-2" style="width: 10px; height: 10px; border-radius: 50%; background: {{ $categoryPalette['tindakan'] }};"></span>
                                Tindakan
                                <span class="ml-2 font-weight-bold" style="color: #94a3b8;">{{ number_format($categoryShares['tindakan'], 1, ',', '.') }}%</span>
                            </span>
                            <span class="font-weight-bold text-dark" style="font-size: 12px;">{{ $formatCurrency((float) ($categoryRevenue->tindakan_total ?? 0)) }}</span>
                        </div>
                        <div class="small {{ $categoryTrend['tindakan']['class'] }} font-weight-bold mb-1" style="font-size: 10px; line-height: 1.2;">
                            <i class="{{ $categoryTrend['tindakan']['icon'] }} mr-1"></i>{{ $categoryTrend['tindakan']['label'] }} <span class="text-muted font-weight-normal">vs periode tahun lalu</span>
                        </div>

                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <span class="text-muted d-flex align-items-center" style="font-size: 11px;">
                                <span class="mr-2" style="width: 10px; height: 10px; border-radius: 50%; background: {{ $categoryPalette['obat_produk'] }};"></span>
                                Obat/Produk
                                <span class="ml-2 font-weight-bold" style="color: #94a3b8;">{{ number_format($categoryShares['obat_produk'], 1, ',', '.') }}%</span>
                            </span>
                            <span class="font-weight-bold text-dark" style="font-size: 12px;">{{ $formatCurrency((float) ($categoryRevenue->obat_produk_total ?? 0)) }}</span>
                        </div>
                        <div class="small {{ $categoryTrend['obat_produk']['class'] }} font-weight-bold mb-1" style="font-size: 10px; line-height: 1.2;">
                            <i class="{{ $categoryTrend['obat_produk']['icon'] }} mr-1"></i>{{ $categoryTrend['obat_produk']['label'] }} <span class="text-muted font-weight-normal">vs periode tahun lalu</span>
                        </div>

                        <div class="d-flex align-items-center justify-content-between">
                            <span class="text-muted d-flex align-items-center" style="font-size: 11px;">
                                <span class="mr-2" style="width: 10px; height: 10px; border-radius: 50%; background: {{ $categoryPalette['lain_lain'] }};"></span>
                                Lain-lain
                                <span class="ml-2 font-weight-bold" style="color: #94a3b8;">{{ number_format($categoryShares['lain_lain'], 1, ',', '.') }}%</span>
                            </span>
                            <span class="font-weight-bold text-dark" style="font-size: 12px;">{{ $formatCurrency((float) ($categoryRevenue->lain_lain_total ?? 0)) }}</span>
                        </div>
                        <div class="small {{ $categoryTrend['lain_lain']['class'] }} font-weight-bold" style="font-size: 10px; line-height: 1.2;">
                            <i class="{{ $categoryTrend['lain_lain']['icon'] }} mr-1"></i>{{ $categoryTrend['lain_lain']['label'] }} <span class="text-muted font-weight-normal">vs periode tahun lalu</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>