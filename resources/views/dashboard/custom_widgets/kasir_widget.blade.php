@php
    use App\Support\Dashboard\Fmt;
    $data = $widget->data;
@endphp

<x-dash.card :widget="$widget" :subtitle="$data['todayLabel']" :href="route('finance.billing.index')" href-label="Buka Billing">
    <div class="dw-section-label">Semua klinik</div>
    <div class="dw-stats">
        <x-dash.stat class="is-hero" label="Diterima hari ini" :value="Fmt::rp($data['total'])"
            :trend="$data['trend']" trend-label="vs kemarin" :meta="Fmt::num($data['invoices']) . ' invoice dibayar'" />
    </div>
    <div class="dw-stats mt-2">
        <x-dash.stat label="Invoice belum dibayar" :value="Fmt::num($data['unpaidCount'])" :tone="$data['unpaidCount'] > 0 ? 'warning' : null"
            :meta="Fmt::rpShort($data['unpaidAmount'])" />
        <x-dash.stat label="Piutang berjalan" :value="Fmt::num($data['piutangCount'])" :meta="'sisa ' . Fmt::rpShort($data['piutangSisa'])" />
    </div>
    @if ($data['metodeBayar'])
        <div class="dw-section-label">Metode bayar hari ini · Umum / Asuransi</div>
        <x-dash.metode-bayar :split="$data['metodeBayar']" />
    @endif

    <div class="dw-section-label">Per klinik</div>
    <div class="dw-clinics">
        @foreach ($data['clinics'] as $c)
            <x-dash.clinic :klinik="$c['klinik']">
                <x-slot:aside><x-dash.delta :trend="$c['trend']" label="vs kemarin" class="mt-0" /></x-slot:aside>
                <div class="dw-stats">
                    <x-dash.stat label="Diterima hari ini" :value="Fmt::rp($c['total'])" :meta="Fmt::num($c['invoices']) . ' invoice'" />
                    <x-dash.stat label="Belum dibayar" :value="Fmt::num($c['unpaidCount'])" :tone="$c['unpaidCount'] > 0 ? 'warning' : null"
                        :meta="Fmt::rpShort($c['unpaidAmount'])" />
                </div>

                @if ($c['metodeBayar'])
                    <div class="dw-section-label">Metode bayar · Umum / Asuransi</div>
                    <x-dash.metode-bayar :split="$c['metodeBayar']" :color="$c['klinik']->color" />
                @endif

                @if (!empty($c['unpaid']))
                    <div class="dw-section-label">Belum dibayar · terbaru</div>
                    <ul class="dw-list">
                        @foreach ($c['unpaid'] as $row)
                            <li class="dw-item">
                                <span class="dw-item-main">
                                    <span class="dw-item-title d-block">{{ $row->pasien ?? 'Tanpa pasien' }}</span>
                                    <span class="dw-item-meta d-block">{{ $row->invoice_number }} · {{ \Carbon\Carbon::parse($row->created_at)->translatedFormat('d M Y') }}</span>
                                </span>
                                <span class="dw-item-aside">{{ Fmt::rp($row->total_amount) }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-dash.clinic>
        @endforeach
    </div>
</x-dash.card>
