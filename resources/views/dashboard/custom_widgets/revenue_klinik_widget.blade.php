@php
    $periodEnd = ($dashboardFilter['period_end'] ?? \Carbon\Carbon::now())->copy();
    $periodStart = ($dashboardFilter['period_start'] ?? $periodEnd->copy()->startOfMonth())->copy();
    $periodLabel = $periodStart->format('d M Y') . ' - ' . $periodEnd->format('d M Y');

    $revenueExpression = "COALESCE(SUM(CASE WHEN LOWER(COALESCE(ft.jenis_transaksi, 'in')) = 'out' THEN -COALESCE(ft.jumlah, 0) ELSE COALESCE(ft.jumlah, 0) END), 0)";

    $revenueByClinic = \Illuminate\Support\Facades\DB::table('finance_transactions as ft')
        ->join('erm_visitations as v', 'v.id', '=', 'ft.visitation_id')
        ->join('erm_klinik as k', 'k.id', '=', 'v.klinik_id')
        ->whereBetween('ft.tanggal', [$periodStart, $periodEnd])
        ->groupBy('k.id', 'k.nama')
        ->selectRaw('k.id, k.nama, ' . $revenueExpression . ' as revenue_total')
        ->orderByDesc('revenue_total')
        ->get()
        ->keyBy('id');

    $clinics = \App\Models\ERM\Klinik::query()
        ->orderBy('nama')
        ->get(['id', 'nama'])
        ->map(function ($clinic) use ($revenueByClinic) {
            $clinic->revenue_total = (float) optional($revenueByClinic->get($clinic->id))->revenue_total;

            return $clinic;
        })
        ->sortByDesc('revenue_total')
        ->values();

    $grandTotal = (float) $clinics->sum('revenue_total');

    $formatCurrency = function ($amount) {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    };
@endphp

<div class="card h-100 shadow-sm border-0">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
                <h5 class="card-title mb-1">{{ $widget->widget_name ?? 'Revenue Klinik' }}</h5>
                <p class="text-muted mb-0">Pendapatan per klinik untuk periode {{ $periodLabel }}.</p>
            </div>
            <span class="badge badge-primary">Klinik</span>
        </div>

        <div class="border rounded bg-light px-3 py-2 mb-3 d-flex justify-content-between align-items-center flex-wrap">
            <span class="small text-muted">Total revenue seluruh klinik</span>
            <span class="h5 mb-0 font-weight-bold text-primary">{{ $formatCurrency($grandTotal) }}</span>
        </div>

        @if ($clinics->isEmpty())
            <div class="alert alert-light border mb-0">
                Belum ada data klinik untuk ditampilkan.
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="border-0 text-muted small text-uppercase">Klinik</th>
                            <th class="border-0 text-muted small text-uppercase text-right">Revenue</th>
                            <th class="border-0 text-muted small text-uppercase text-right">Kontribusi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($clinics as $clinic)
                            @php
                                $share = $grandTotal > 0 ? ($clinic->revenue_total / $grandTotal) * 100 : 0;
                            @endphp
                            <tr>
                                <td class="align-middle">
                                    <div class="font-weight-bold text-dark">{{ $clinic->nama }}</div>
                                </td>
                                <td class="align-middle text-right font-weight-bold {{ $clinic->revenue_total >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ $formatCurrency($clinic->revenue_total) }}
                                </td>
                                <td class="align-middle text-right text-muted">
                                    {{ number_format($share, 1, ',', '.') }}%
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>