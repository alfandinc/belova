@php
    use App\Support\Dashboard\Fmt;
    $data = $widget->data;
    $maxMethod = max(1, collect($data['methods'])->max('total'));
@endphp

<x-dash.card :widget="$widget" :subtitle="$data['todayLabel']" :href="route('finance.billing.index')" href-label="Buka Billing">
    <div class="dw-stats">
        <x-dash.stat class="is-hero" label="Diterima hari ini" :value="Fmt::rp($data['total'])"
            :trend="$data['trend']" trend-label="vs kemarin" :meta="Fmt::num($data['invoices']) . ' invoice dibayar'" :clinics="$data['total'] > 0 ? $data['totalClinics'] : null" />
    </div>
    <div class="dw-stats mt-2">
        <x-dash.stat label="Invoice belum dibayar" :value="Fmt::num($data['unpaidCount'])" :tone="$data['unpaidCount'] > 0 ? 'warning' : null"
            :meta="Fmt::rpShort($data['unpaidAmount'])" :clinics="$data['unpaidCount'] > 0 ? $data['unpaidClinics'] : null" />
        <x-dash.stat label="Piutang berjalan" :value="Fmt::num($data['piutangCount'])" :meta="'sisa ' . Fmt::rpShort($data['piutangSisa'])" />
    </div>

    <div class="dw-section-label">Metode pembayaran hari ini</div>
    @if (empty($data['methods']))
        <p class="dw-note mt-0">Belum ada pembayaran hari ini.</p>
    @else
        <div class="dw-bars">
            @foreach ($data['methods'] as $method)
                <div>
                    <x-dash.bar :label="$method['label']" :value="Fmt::rp($method['total'])" :pct="$method['total'] / $maxMethod * 100"
                        :sub="Fmt::num($method['invoices']) . ' invoice'" />
                    <x-dash.split :items="collect($method['clinics'])->where('amount', '>', 0)->values()->all()" :bar="false" class="mt-1" />
                </div>
            @endforeach
        </div>
    @endif

    @if (!empty($data['unpaid']))
        <div class="dw-section-label">Belum dibayar · terbaru</div>
        <ul class="dw-list">
            @foreach ($data['unpaid'] as $row)
                <li class="dw-item">
                    <span class="dw-item-main">
                        <span class="dw-item-title d-block">{{ $row->pasien ?? 'Tanpa pasien' }}</span>
                        <span class="dw-item-meta d-block"><i class="dw-dot" style="background: {{ \App\Support\Dashboard\Clinics::color($row->klinik_id) }}" title="{{ \App\Support\Dashboard\Clinics::short($row->klinik_id) }}"></i> {{ $row->invoice_number }} · {{ \Carbon\Carbon::parse($row->created_at)->translatedFormat('d M Y') }}</span>
                    </span>
                    <span class="dw-item-aside">{{ Fmt::rp($row->total_amount) }}</span>
                </li>
            @endforeach
        </ul>
    @endif
</x-dash.card>
