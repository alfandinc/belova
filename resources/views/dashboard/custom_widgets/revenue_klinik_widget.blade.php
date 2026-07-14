@php
    $periodEnd = ($dashboardFilter['period_end'] ?? \Carbon\Carbon::now())->copy();
    $periodStart = ($dashboardFilter['period_start'] ?? $periodEnd->copy()->startOfMonth())->copy();
    $selectedYear = (int) ($dashboardFilter['year'] ?? $periodEnd->year);
    $selectedMonth = (int) ($dashboardFilter['month'] ?? $periodEnd->month);
    $previousPeriodStart = $periodStart->copy()->subYear();
    $previousPeriodEnd = $periodEnd->copy()->subYear();

    $palette = ['#8b5cf6', '#3b82f6', '#22c55e', '#f59e0b', '#ef4444', '#14b8a6'];

    $targetsByClinic = \Illuminate\Support\Facades\DB::table('finance_revenue_target')
        ->where('periode_tahun', $selectedYear)
        ->where('periode_bulan', $selectedMonth)
        ->pluck('target_amount', 'klinik_id');

    $revenueByClinic = \Illuminate\Support\Facades\DB::table('finance_invoices as fi')
        ->join('erm_visitations as v', 'v.id', '=', 'fi.visitation_id')
        ->join('erm_klinik as k', 'k.id', '=', 'v.klinik_id')
        ->whereNotNull('fi.payment_date')
        ->whereIn('fi.status', ['paid', 'partial'])
        ->whereBetween('fi.payment_date', [$periodStart, $periodEnd])
        ->groupBy('k.id', 'k.nama')
        ->selectRaw('k.id, k.nama, COALESCE(SUM(fi.total_amount), 0) as revenue_total, COUNT(fi.id) as invoice_count')
        ->orderByDesc('revenue_total')
        ->get()
        ->keyBy('id');

    $previousRevenueByClinic = \Illuminate\Support\Facades\DB::table('finance_invoices as fi')
        ->join('erm_visitations as v', 'v.id', '=', 'fi.visitation_id')
        ->join('erm_klinik as k', 'k.id', '=', 'v.klinik_id')
        ->whereNotNull('fi.payment_date')
        ->whereIn('fi.status', ['paid', 'partial'])
        ->whereBetween('fi.payment_date', [$previousPeriodStart, $previousPeriodEnd])
        ->groupBy('k.id', 'k.nama')
        ->selectRaw('k.id, k.nama, COALESCE(SUM(fi.total_amount), 0) as revenue_total, COUNT(fi.id) as invoice_count')
        ->get()
        ->keyBy('id');

    $categoryRevenueByClinic = \Illuminate\Support\Facades\DB::table('finance_invoice_items as fii')
        ->join('finance_invoices as fi', 'fi.id', '=', 'fii.invoice_id')
        ->join('erm_visitations as v', 'v.id', '=', 'fi.visitation_id')
        ->join('erm_klinik as k', 'k.id', '=', 'v.klinik_id')
        ->whereNotNull('fi.payment_date')
        ->whereIn('fi.status', ['paid', 'partial'])
        ->whereBetween('fi.payment_date', [$periodStart, $periodEnd])
        ->groupBy('k.id')
        ->selectRaw("k.id,
            COALESCE(SUM(CASE
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
        ->get()
        ->keyBy('id');

    $previousCategoryRevenueByClinic = \Illuminate\Support\Facades\DB::table('finance_invoice_items as fii')
        ->join('finance_invoices as fi', 'fi.id', '=', 'fii.invoice_id')
        ->join('erm_visitations as v', 'v.id', '=', 'fi.visitation_id')
        ->join('erm_klinik as k', 'k.id', '=', 'v.klinik_id')
        ->whereNotNull('fi.payment_date')
        ->whereIn('fi.status', ['paid', 'partial'])
        ->whereBetween('fi.payment_date', [$previousPeriodStart, $previousPeriodEnd])
        ->groupBy('k.id')
        ->selectRaw("k.id,
            COALESCE(SUM(CASE
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
        ->get()
        ->keyBy('id');

    $formatTrend = function ($current, $previous) {
        $difference = (float) $current - (float) $previous;

        if (abs($difference) < 0.005) {
            return [
                'direction' => 'flat',
                'icon' => 'fas fa-minus',
                'class' => 'text-muted',
                'label' => '0,0%',
            ];
        }

        if (abs((float) $previous) < 0.005) {
            return [
                'direction' => $difference > 0 ? 'up' : 'down',
                'icon' => $difference > 0 ? 'fas fa-arrow-up' : 'fas fa-arrow-down',
                'class' => 'text-muted',
                'label' => 'N/A',
            ];
        }

        $percent = ($difference / abs((float) $previous)) * 100;

        return [
            'direction' => $difference > 0 ? 'up' : 'down',
            'icon' => $difference > 0 ? 'fas fa-arrow-up' : 'fas fa-arrow-down',
            'class' => $difference > 0 ? 'text-success' : 'text-danger',
            'label' => number_format(abs($percent), 1, ',', '.') . '%',
        ];
    };

    $clinics = \App\Models\ERM\Klinik::query()
        ->orderBy('nama')
        ->get(['id', 'nama'])
        ->map(function ($clinic) use ($revenueByClinic, $previousRevenueByClinic, $targetsByClinic, $categoryRevenueByClinic, $previousCategoryRevenueByClinic, $formatTrend) {
            $clinicName = strtolower((string) ($clinic->nama ?? ''));

            $clinic->revenue_total = (float) optional($revenueByClinic->get($clinic->id))->revenue_total;
            $clinic->previous_revenue_total = (float) optional($previousRevenueByClinic->get($clinic->id))->revenue_total;
            $clinic->invoice_count = (int) optional($revenueByClinic->get($clinic->id))->invoice_count;
            $clinic->avg_invoice = $clinic->invoice_count > 0 ? ($clinic->revenue_total / $clinic->invoice_count) : 0;
            $clinic->target_amount = (float) ($targetsByClinic[$clinic->id] ?? 0);
            $clinic->target_percentage = $clinic->target_amount > 0 ? ($clinic->revenue_total / $clinic->target_amount) * 100 : null;
            $clinic->target_gap = $clinic->target_amount > 0 ? max(0, $clinic->target_amount - $clinic->revenue_total) : null;
            $clinic->tindakan_total = (float) optional($categoryRevenueByClinic->get($clinic->id))->tindakan_total;
            $clinic->obat_produk_total = (float) optional($categoryRevenueByClinic->get($clinic->id))->obat_produk_total;
            $clinic->lain_lain_total = (float) optional($categoryRevenueByClinic->get($clinic->id))->lain_lain_total;
            $clinic->previous_tindakan_total = (float) optional($previousCategoryRevenueByClinic->get($clinic->id))->tindakan_total;
            $clinic->previous_obat_produk_total = (float) optional($previousCategoryRevenueByClinic->get($clinic->id))->obat_produk_total;
            $clinic->previous_lain_lain_total = (float) optional($previousCategoryRevenueByClinic->get($clinic->id))->lain_lain_total;
            $clinic->revenue_trend = $formatTrend($clinic->revenue_total, $clinic->previous_revenue_total);
            $clinic->tindakan_trend = $formatTrend($clinic->tindakan_total, $clinic->previous_tindakan_total);
            $clinic->obat_produk_trend = $formatTrend($clinic->obat_produk_total, $clinic->previous_obat_produk_total);
            $clinic->lain_lain_trend = $formatTrend($clinic->lain_lain_total, $clinic->previous_lain_lain_total);
            $clinic->logo_path = null;
            $clinic->brand_color = null;
            $clinic->brand_shadow = 'rgba(15, 23, 42, 0.12)';

            if (str_contains($clinicName, 'skin')) {
                $clinic->logo_path = asset('img/logo-belovaskin.png');
                $clinic->brand_color = '#8b5cf6';
                $clinic->brand_shadow = 'rgba(139, 92, 246, 0.28)';
            } elseif (str_contains($clinicName, 'premiere')) {
                $clinic->logo_path = asset('img/logo-premiere.png');
                $clinic->brand_color = '#2563eb';
                $clinic->brand_shadow = 'rgba(37, 99, 235, 0.28)';
            } elseif (str_contains($clinicName, 'dental')) {
                $clinic->logo_path = asset('img/logo-dental.png');
                $clinic->brand_color = '#22c55e';
                $clinic->brand_shadow = 'rgba(34, 197, 94, 0.28)';
            }

            return $clinic;
        })
        ->sortByDesc('revenue_total')
        ->values();

    $grandTotal = (float) $clinics->sum('revenue_total');
    $categoryPalette = [
        'tindakan' => '#3b82f6',
        'obat_produk' => '#22c55e',
        'lain_lain' => '#f59e0b',
    ];

    $clinics = $clinics->values()->map(function ($clinic, $index) use ($grandTotal, $palette, $categoryPalette) {
        $clinic->share = $grandTotal > 0 ? ($clinic->revenue_total / $grandTotal) * 100 : 0;
        $clinic->color = $clinic->brand_color ?: $palette[$index % count($palette)];

        $categoryTotal = max(0, (float) $clinic->tindakan_total) + max(0, (float) $clinic->obat_produk_total) + max(0, (float) $clinic->lain_lain_total);
        $clinic->tindakan_share = $categoryTotal > 0 ? ($clinic->tindakan_total / $categoryTotal) * 100 : 0;
        $clinic->obat_produk_share = $categoryTotal > 0 ? ($clinic->obat_produk_total / $categoryTotal) * 100 : 0;
        $clinic->lain_lain_share = $categoryTotal > 0 ? ($clinic->lain_lain_total / $categoryTotal) * 100 : 0;

        $categoryOffset = 0;
        $categorySegments = [];
        foreach ([
            ['key' => 'tindakan', 'share' => $clinic->tindakan_share],
            ['key' => 'obat_produk', 'share' => $clinic->obat_produk_share],
            ['key' => 'lain_lain', 'share' => $clinic->lain_lain_share],
        ] as $segment) {
            if ($segment['share'] <= 0) {
                continue;
            }

            $segmentEnd = min(100, $categoryOffset + $segment['share']);
            $categorySegments[] = $categoryPalette[$segment['key']] . ' ' . number_format($categoryOffset, 4, '.', '') . '% ' . number_format($segmentEnd, 4, '.', '') . '%';
            $categoryOffset = $segmentEnd;
        }

        $clinic->category_donut_style = !empty($categorySegments)
            ? 'background: conic-gradient(' . implode(', ', $categorySegments) . ');'
            : 'background: #e5e7eb;';

        return $clinic;
    });

    $formatCurrency = function ($amount) {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    };
@endphp

<div class="card h-100 border-0 shadow-sm dashboard-widget-card" style="border-radius: 18px; overflow: hidden;">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start flex-wrap mb-3">
            <div>
                <h5 class="card-title mb-1">{{ $widget->widget_name ?? 'Revenue Klinik' }}</h5>
                {{-- <p class="text-muted mb-0">Revenue accrual per klinik berdasarkan tanggal payment invoice untuk periode {{ $periodLabel }}.</p> --}}
            </div>
            {{-- <span class="badge badge-primary px-3 py-2" style="border-radius: 999px;">Klinik</span> --}}
        </div>

        @if ($clinics->isEmpty())
            <div class="alert alert-light border mb-0">
                Belum ada data klinik untuk ditampilkan.
            </div>
        @else
            <div class="row">
                @foreach ($clinics->take(6) as $clinic)
                    <div class="col-md-6 col-xl-4 mb-3">
                        <div class="h-100 p-3" style="border-radius: 18px; border: 1px solid rgba(148, 163, 184, 0.16); background: #ffffff; box-shadow: inset 0 1px 0 rgba(255,255,255,0.6);">
                                    <div class="d-flex align-items-start mb-3">
                                        <div class="mr-3 d-flex align-items-center justify-content-center text-white" style="width: 56px; height: 56px; border-radius: 16px; background: linear-gradient(135deg, {{ $clinic->color }} 0%, {{ $clinic->color }}dd 100%); box-shadow: 0 12px 24px {{ $clinic->brand_shadow }}; border: 1px solid rgba(255, 255, 255, 0.28);">
                                            @if (!empty($clinic->logo_path))
                                                <img src="{{ $clinic->logo_path }}" alt="{{ $clinic->nama }}" style="max-width: 40px; max-height: 40px; object-fit: contain; filter: brightness(0) invert(1);">
                                            @else
                                                <i class="fas fa-clinic-medical" style="font-size: 20px;"></i>
                                            @endif
                                        </div>
                                        <div class="pt-1">
                                            <div class="font-weight-bold" style="color: {{ $clinic->color }}; font-size: 15px; line-height: 1.3;">{{ $clinic->nama }}</div>
                                        </div>
                                    </div>

                                    <div class="mb-2">
                                        <div class="text-muted" style="font-size: 12px;">Revenue</div>
                                        <div class="h4 mb-0 font-weight-bold text-dark">{{ $formatCurrency($clinic->revenue_total) }}</div>
                                        <div class="small {{ $clinic->revenue_trend['class'] }} font-weight-bold mt-1">
                                            <i class="{{ $clinic->revenue_trend['icon'] }} mr-1"></i>{{ $clinic->revenue_trend['label'] }}
                                            <span class="text-muted font-weight-normal">vs periode tahun lalu</span>
                                        </div>
                                    </div>

                                    <div class="mt-3 pt-3" style="border-top: 1px dashed rgba(148, 163, 184, 0.35);">
                                        <div class="text-uppercase text-muted font-weight-bold mb-2" style="font-size: 10px; letter-spacing: 0.04em;">Invoice Data</div>
                                        <div class="row">
                                            <div class="col-6">
                                                <div class="text-muted" style="font-size: 11px;">Invoice</div>
                                                <div class="font-weight-bold text-dark">{{ number_format($clinic->invoice_count, 0, ',', '.') }}</div>
                                            </div>
                                            <div class="col-6">
                                                <div class="text-muted" style="font-size: 11px;">Avg Invoice</div>
                                                <div class="font-weight-bold text-dark">{{ $formatCurrency($clinic->avg_invoice) }}</div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-3 pt-3" style="border-top: 1px dashed rgba(148, 163, 184, 0.35);">
                                        <div class="text-uppercase text-muted font-weight-bold mb-2" style="font-size: 10px; letter-spacing: 0.04em;">Target Data</div>
                                        <div class="row align-items-start">
                                            <div class="col-md-4 col-6 mb-2 mb-md-0">
                                                <div class="text-muted" style="font-size: 11px;">Target</div>
                                                <div class="font-weight-bold text-dark">{{ $clinic->target_amount > 0 ? $formatCurrency($clinic->target_amount) : '-' }}</div>
                                            </div>
                                            <div class="col-md-4 col-6 mb-2 mb-md-0">
                                                <div class="text-muted" style="font-size: 11px;">% Target</div>
                                                <div class="font-weight-bold {{ $clinic->target_percentage !== null && $clinic->target_percentage >= 100 ? 'text-success' : 'text-dark' }}">
                                                    {{ $clinic->target_percentage !== null ? number_format($clinic->target_percentage, 1, ',', '.') . '%' : '-' }}
                                                </div>
                                            </div>
                                            <div class="col-md-4 col-12">
                                                <div class="text-muted" style="font-size: 11px;">Sisa ke Target</div>
                                                <div class="font-weight-bold {{ $clinic->target_gap !== null && $clinic->target_gap <= 0 ? 'text-success' : 'text-dark' }}" style="font-size: 11px;">
                                                    @if ($clinic->target_amount <= 0)
                                                        -
                                                    @elseif ($clinic->target_gap <= 0)
                                                        Target tercapai
                                                    @else
                                                        {{ $formatCurrency($clinic->target_gap) }}
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mt-2" style="height: 6px; border-radius: 999px; background: #eef2ff; overflow: hidden;">
                                            <div style="height: 100%; width: {{ $clinic->target_percentage !== null ? min(100, $clinic->target_percentage) : 0 }}%; background: {{ $clinic->color }};"></div>
                                        </div>
                                    </div>

                                    <div class="mt-3 pt-3" style="border-top: 1px dashed rgba(148, 163, 184, 0.35);">
                                        <div class="text-uppercase text-muted font-weight-bold mb-2" style="font-size: 10px; letter-spacing: 0.04em;">Kategori Data</div>
                                        <div class="d-flex align-items-center">
                                            <div class="mr-3 d-flex align-items-center justify-content-center" style="width: 68px; height: 68px; border-radius: 50%; {{ $clinic->category_donut_style }}">
                                                <div class="d-flex flex-column align-items-center justify-content-center text-center bg-white" style="width: 42px; height: 42px; border-radius: 50%; box-shadow: inset 0 0 0 1px rgba(148, 163, 184, 0.14);">
                                                    <div class="text-muted" style="font-size: 8px; line-height: 1;">MIX</div>
                                                    <div class="font-weight-bold text-dark" style="font-size: 10px; line-height: 1.2;">{{ number_format($clinic->share, 0, ',', '.') }}%</div>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="d-flex align-items-center justify-content-between mb-1">
                                                    <span class="text-muted" style="font-size: 11px;"><span class="d-inline-block mr-1" style="width: 8px; height: 8px; border-radius: 50%; background: {{ $categoryPalette['tindakan'] }};"></span>Tindakan</span>
                                                    <span class="font-weight-bold text-dark text-right" style="font-size: 11px;">
                                                        {{ $formatCurrency($clinic->tindakan_total) }} <span class="text-muted">({{ number_format($clinic->tindakan_share, 1, ',', '.') }}%)</span>
                                                        <span class="d-block {{ $clinic->tindakan_trend['class'] }}" style="font-size: 10px; line-height: 1.2;">
                                                            <i class="{{ $clinic->tindakan_trend['icon'] }} mr-1"></i>{{ $clinic->tindakan_trend['label'] }}
                                                        </span>
                                                    </span>
                                                </div>
                                                <div class="d-flex align-items-center justify-content-between mb-1">
                                                    <span class="text-muted" style="font-size: 11px;"><span class="d-inline-block mr-1" style="width: 8px; height: 8px; border-radius: 50%; background: {{ $categoryPalette['obat_produk'] }};"></span>Obat/Produk</span>
                                                    <span class="font-weight-bold text-dark text-right" style="font-size: 11px;">
                                                        {{ $formatCurrency($clinic->obat_produk_total) }} <span class="text-muted">({{ number_format($clinic->obat_produk_share, 1, ',', '.') }}%)</span>
                                                        <span class="d-block {{ $clinic->obat_produk_trend['class'] }}" style="font-size: 10px; line-height: 1.2;">
                                                            <i class="{{ $clinic->obat_produk_trend['icon'] }} mr-1"></i>{{ $clinic->obat_produk_trend['label'] }}
                                                        </span>
                                                    </span>
                                                </div>
                                                <div class="d-flex align-items-center justify-content-between">
                                                    <span class="text-muted" style="font-size: 11px;"><span class="d-inline-block mr-1" style="width: 8px; height: 8px; border-radius: 50%; background: {{ $categoryPalette['lain_lain'] }};"></span>Lain-lain</span>
                                                    <span class="font-weight-bold text-dark text-right" style="font-size: 11px;">
                                                        {{ $formatCurrency($clinic->lain_lain_total) }} <span class="text-muted">({{ number_format($clinic->lain_lain_share, 1, ',', '.') }}%)</span>
                                                        <span class="d-block {{ $clinic->lain_lain_trend['class'] }}" style="font-size: 10px; line-height: 1.2;">
                                                            <i class="{{ $clinic->lain_lain_trend['icon'] }} mr-1"></i>{{ $clinic->lain_lain_trend['label'] }}
                                                        </span>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                @endforeach
            </div>
        @endif
    </div>
</div>