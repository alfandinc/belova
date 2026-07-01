@php
    $periodEnd = ($dashboardFilter['period_end'] ?? \Carbon\Carbon::now())->copy();
    $periodStart = ($dashboardFilter['period_start'] ?? $periodEnd->copy()->startOfMonth())->copy();
    $periodLabel = $periodStart->format('d M Y') . ' - ' . $periodEnd->format('d M Y');

    $palette = ['#8b5cf6', '#3b82f6', '#22c55e', '#f59e0b', '#ef4444', '#14b8a6'];

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

    $clinics = \App\Models\ERM\Klinik::query()
        ->orderBy('nama')
        ->get(['id', 'nama'])
        ->map(function ($clinic) use ($revenueByClinic) {
            $clinic->revenue_total = (float) optional($revenueByClinic->get($clinic->id))->revenue_total;
            $clinic->invoice_count = (int) optional($revenueByClinic->get($clinic->id))->invoice_count;
            $clinic->avg_invoice = $clinic->invoice_count > 0 ? ($clinic->revenue_total / $clinic->invoice_count) : 0;

            return $clinic;
        })
        ->sortByDesc('revenue_total')
        ->values();

    $grandTotal = (float) $clinics->sum('revenue_total');
    $donutStyle = 'background: #e5e7eb;';

    $clinics = $clinics->values()->map(function ($clinic, $index) use ($grandTotal, $palette) {
        $clinic->share = $grandTotal > 0 ? ($clinic->revenue_total / $grandTotal) * 100 : 0;
        $clinic->color = $palette[$index % count($palette)];

        return $clinic;
    });

    $offset = 0;
    $segments = [];
    foreach ($clinics as $clinic) {
        if ($clinic->share <= 0) {
            continue;
        }

        $end = min(100, $offset + $clinic->share);
        $segments[] = $clinic->color . ' ' . number_format($offset, 4, '.', '') . '% ' . number_format($end, 4, '.', '') . '%';
        $offset = $end;
    }

    if (!empty($segments)) {
        $donutStyle = 'background: conic-gradient(' . implode(', ', $segments) . ');';
    }

    $formatCurrency = function ($amount) {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    };
@endphp

<div class="card h-100 border-0 shadow-sm" style="border-radius: 18px; overflow: hidden;">
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
                <div class="col-xl-8 mb-4 mb-xl-0">
                    <div class="row">
                        @foreach ($clinics->take(3) as $clinic)
                            <div class="col-md-6 col-xl-4 mb-3">
                                <div class="h-100 p-3" style="border-radius: 18px; border: 1px solid rgba(148, 163, 184, 0.16); background: #ffffff; box-shadow: inset 0 1px 0 rgba(255,255,255,0.6);">
                                    <div class="d-flex align-items-start mb-3">
                                        <div class="mr-3 d-flex align-items-center justify-content-center text-white" style="width: 42px; height: 42px; border-radius: 14px; background: {{ $clinic->color }}; box-shadow: 0 10px 18px rgba(15, 23, 42, 0.12);">
                                            <i class="fas fa-clinic-medical"></i>
                                        </div>
                                        <div>
                                            <div class="small font-weight-bold" style="color: {{ $clinic->color }};">{{ $clinic->nama }}</div>
                                            <div class="text-muted" style="font-size: 12px;">Kontribusi {{ number_format($clinic->share, 1, ',', '.') }}%</div>
                                        </div>
                                    </div>

                                    <div class="mb-2">
                                        <div class="text-muted" style="font-size: 12px;">Revenue</div>
                                        <div class="h4 mb-0 font-weight-bold text-dark">{{ $formatCurrency($clinic->revenue_total) }}</div>
                                    </div>

                                    <div class="row mt-3">
                                        <div class="col-6">
                                            <div class="text-muted" style="font-size: 11px;">Invoice</div>
                                            <div class="font-weight-bold text-dark">{{ number_format($clinic->invoice_count, 0, ',', '.') }}</div>
                                        </div>
                                        <div class="col-6">
                                            <div class="text-muted" style="font-size: 11px;">Avg Invoice</div>
                                            <div class="font-weight-bold text-dark">{{ $formatCurrency($clinic->avg_invoice) }}</div>
                                        </div>
                                    </div>

                                    <div class="mt-3" style="height: 6px; border-radius: 999px; background: #eef2ff; overflow: hidden;">
                                        <div style="height: 100%; width: {{ min(100, $clinic->share) }}%; background: {{ $clinic->color }};"></div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="col-xl-4">
                    <div class="h-100 p-3" style="border-radius: 18px; border: 1px solid rgba(148, 163, 184, 0.16); background: linear-gradient(180deg, #ffffff 0%, #fafaff 100%);">
                        <div class="small text-uppercase text-muted font-weight-bold mb-3">Kontribusi Revenue</div>

                        <div class="d-flex justify-content-center align-items-center mb-3">
                            <div class="position-relative d-flex align-items-center justify-content-center" style="width: 180px; height: 180px; border-radius: 50%; {{ $donutStyle }}">
                                <div class="d-flex flex-column align-items-center justify-content-center text-center bg-white" style="width: 108px; height: 108px; border-radius: 50%; box-shadow: inset 0 0 0 1px rgba(148, 163, 184, 0.12);">
                                    <div class="text-muted" style="font-size: 11px;">TOTAL</div>
                                    <div class="font-weight-bold text-dark">{{ $formatCurrency($grandTotal) }}</div>
                                    <div class="text-muted" style="font-size: 11px;">100%</div>
                                </div>
                            </div>
                        </div>

                        @foreach ($clinics as $clinic)
                            <div class="d-flex align-items-center justify-content-between {{ !$loop->last ? 'mb-2' : '' }}">
                                <div class="d-flex align-items-center pr-2">
                                    <span class="mr-2" style="width: 10px; height: 10px; border-radius: 50%; background: {{ $clinic->color }};"></span>
                                    <span class="small text-dark">{{ $clinic->nama }}</span>
                                </div>
                                <span class="small font-weight-bold text-muted">{{ number_format($clinic->share, 1, ',', '.') }}%</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>