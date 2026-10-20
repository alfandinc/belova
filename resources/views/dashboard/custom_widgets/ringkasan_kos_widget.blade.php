@php
    use App\Support\Dashboard\Fmt;
    $data = $widget->data;
@endphp

<x-dash.card :widget="$widget" :subtitle="$data['totalRooms'] . ' kamar · status hari ini, kas sesuai periode'"
    :href="route('bcl.rooms')" href-label="Buka Daftar Kamar">
    <div class="dw-stats">
        <x-dash.stat label="Okupansi" :value="Fmt::pct($data['occupancy'])"
            :meta="$data['filled'] . ' dari ' . $data['totalRooms'] . ' kamar terisi'" />
        <x-dash.stat label="Kas masuk" :value="Fmt::rp($data['cashIn'])" :trend="$data['cashInTrend']" trend-label="vs periode lalu"
            :meta="'Sewa ' . Fmt::rp($data['rentIncome'])" />
        <x-dash.stat label="Kas keluar" :value="Fmt::rp($data['cashOut'])" :trend="$data['cashOutTrend']" trend-label="vs periode lalu"
            :meta="'Selisih ' . ($data['net'] < 0 ? '−' : '') . Fmt::rp(abs($data['net']))" />
        <x-dash.stat label="Piutang sewa" :value="Fmt::rp($data['receivable'])" :tone="$data['receivable'] > 0 ? 'warning' : null"
            :meta="$data['unpaidCount'] . ' transaksi belum lunas'" />
    </div>

    <div class="dw-section-label">Status kamar hari ini</div>
    <x-dash.stack :segments="$data['segments']" />

    <div class="dw-section-label">Perlu tindakan</div>
    @if (empty($data['attention']))
        <x-dash.empty icon="check-circle" title="Semua beres: tidak ada sewa lewat, tagihan, atau perawatan tertunda" />
    @else
        <ul class="dw-list">
            @foreach ($data['attention'] as $item)
                <li>
                    <a href="{{ $item['href'] }}" class="dw-item">
                        <span class="dw-item-icon"><i data-feather="{{ $item['icon'] }}"></i></span>
                        <span class="dw-item-main">
                            <span class="dw-item-title d-block">{{ $item['title'] }}</span>
                            <span class="dw-item-meta d-block">{{ $item['meta'] }}</span>
                        </span>
                        <x-dash.pill :tone="$item['tone']">{{ $item['count'] }}</x-dash.pill>
                    </a>
                </li>
            @endforeach
        </ul>
    @endif
    <x-slot:footer>Pembanding: {{ $data['previousLabel'] }}</x-slot:footer>
</x-dash.card>
