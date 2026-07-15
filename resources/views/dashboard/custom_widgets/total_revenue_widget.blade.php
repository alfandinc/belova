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

    $clinicPalette = ['#8b5cf6', '#3b82f6', '#22c55e', '#f59e0b', '#ef4444', '#14b8a6'];

    $revenueByClinic = \Illuminate\Support\Facades\DB::table('finance_invoices as fi')
        ->join('erm_visitations as v', 'v.id', '=', 'fi.visitation_id')
        ->leftJoin('erm_klinik as k', 'k.id', '=', 'v.klinik_id')
        ->whereNotNull('fi.payment_date')
        ->whereIn('fi.status', ['paid', 'partial'])
        ->whereBetween('fi.payment_date', [$periodStart, $periodEnd])
        ->groupBy('v.klinik_id', 'k.nama')
        ->selectRaw("v.klinik_id, COALESCE(k.nama, 'Tanpa Klinik') as nama, COALESCE(SUM(fi.total_amount), 0) as revenue_total")
        ->orderByDesc('revenue_total')
        ->get();

    $revenueByClinicLastYear = \Illuminate\Support\Facades\DB::table('finance_invoices as fi')
        ->join('erm_visitations as v', 'v.id', '=', 'fi.visitation_id')
        ->leftJoin('erm_klinik as k', 'k.id', '=', 'v.klinik_id')
        ->whereNotNull('fi.payment_date')
        ->whereIn('fi.status', ['paid', 'partial'])
        ->whereBetween('fi.payment_date', [$periodStart->copy()->subYear(), $periodEnd->copy()->subYear()])
        ->groupBy('v.klinik_id', 'k.nama')
        ->selectRaw("v.klinik_id, COALESCE(k.nama, 'Tanpa Klinik') as nama, COALESCE(SUM(fi.total_amount), 0) as revenue_total")
        ->get()
        ->keyBy(function ($clinic) {
            return (string) ($clinic->klinik_id ?? 'unknown');
        });

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

    $clinics = $revenueByClinic
        ->values()
        ->map(function ($clinic, $index) use ($periodRevenue, $revenueByClinicLastYear, $formatTrend, $clinicPalette) {
            $clinicId = (string) ($clinic->klinik_id ?? 'unknown');
            $clinicName = strtolower((string) ($clinic->nama ?? ''));

            $clinic->share = $periodRevenue > 0 ? (((float) ($clinic->revenue_total ?? 0)) / $periodRevenue) * 100 : 0;
            $clinic->trend = $formatTrend(
                (float) ($clinic->revenue_total ?? 0),
                (float) optional($revenueByClinicLastYear->get($clinicId))->revenue_total
            );
            $clinic->color = $clinicPalette[$index % count($clinicPalette)];

            if (str_contains($clinicName, 'skin')) {
                $clinic->color = '#8b5cf6';
            } elseif (str_contains($clinicName, 'premiere')) {
                $clinic->color = '#2563eb';
            } elseif (str_contains($clinicName, 'dental')) {
                $clinic->color = '#22c55e';
            }

            return $clinic;
        })
        ->values();

    $clinicSegments = [];
    $clinicOffset = 0;
    foreach ($clinics as $clinic) {
        if (($clinic->share ?? 0) <= 0) {
            continue;
        }

        $segmentEnd = min(100, $clinicOffset + $clinic->share);
        $clinicSegments[] = $clinic->color . ' ' . number_format($clinicOffset, 4, '.', '') . '% ' . number_format($segmentEnd, 4, '.', '') . '%';
        $clinicOffset = $segmentEnd;
    }

    $clinicDonutStyle = !empty($clinicSegments)
        ? 'background: conic-gradient(' . implode(', ', $clinicSegments) . ');'
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
                    <div class="small text-uppercase text-muted font-weight-bold mb-3">Komposisi Revenue Klinik</div>

                    <div class="d-flex justify-content-center align-items-center mb-3">
                        <div class="position-relative d-flex align-items-center justify-content-center" style="width: 180px; height: 180px; border-radius: 50%; {{ $clinicDonutStyle }}">
                            <div class="d-flex flex-column align-items-center justify-content-center text-center bg-white" style="width: 108px; height: 108px; border-radius: 50%; box-shadow: inset 0 0 0 1px rgba(148, 163, 184, 0.12);">
                                <div class="text-muted" style="font-size: 11px;">TOTAL</div>
                                <div class="font-weight-bold text-dark">{{ $formatCurrency($periodRevenue) }}</div>
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
                        @forelse ($clinics as $clinic)
                            <div class="d-flex align-items-center justify-content-between {{ !$loop->last ? 'mb-1' : '' }}">
                                <span class="text-muted d-flex align-items-center" style="font-size: 11px;">
                                    <span class="mr-2" style="width: 10px; height: 10px; border-radius: 50%; background: {{ $clinic->color }};"></span>
                                    {{ $clinic->nama }}
                                    <span class="ml-2 font-weight-bold" style="color: #94a3b8;">{{ number_format($clinic->share, 1, ',', '.') }}%</span>
                                </span>
                                <span class="font-weight-bold text-dark text-right" style="font-size: 12px;">
                                    {{ $formatCurrency((float) ($clinic->revenue_total ?? 0)) }}
                                    <span class="d-block small {{ $clinic->trend['class'] }} font-weight-bold" style="font-size: 10px; line-height: 1.2;">
                                        <i class="{{ $clinic->trend['icon'] }} mr-1"></i>{{ $clinic->trend['label'] }} <span class="text-muted font-weight-normal">vs periode tahun lalu</span>
                                    </span>
                                </span>
                            </div>
                        @empty
                            <div class="small text-muted">Belum ada revenue klinik pada periode ini.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>