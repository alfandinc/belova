@php
    use App\Support\Dashboard\Fmt;
    $data = $widget->data;
    $qty = fn ($v) => Fmt::num($v, floor($v) == $v ? 0 : 1);
@endphp

<x-dash.card :widget="$widget" subtitle="Status stok semua gudang (tanpa gudang expired)" :href="route('erm.stok-gudang.index')" href-label="Manajemen Stok">
    <div class="dw-stats">
        <x-dash.stat label="Kritis" :value="Fmt::num($data['kritis'])" :tone="$data['kritis'] > 0 ? 'critical' : null" meta="segera pesan" />
        <x-dash.stat label="Perlu pesan" :value="Fmt::num($data['pesan'])" :tone="$data['pesan'] > 0 ? 'warning' : null"
            :meta="$data['menunggu'] > 0 ? Fmt::num($data['menunggu']) . ' menunggu barang' : 'di bawah titik pesan'" />
        <x-dash.stat label="Kedaluwarsa" :value="Fmt::num($data['expired'])" :tone="$data['expired'] > 0 ? 'critical' : null"
            :meta="Fmt::num($data['expiringSoon']) . ' batch ≤ ' . $data['expiryDays'] . ' hari'" />
    </div>

    <div class="dw-section-label">Perlu dipesan</div>
    @if (empty($data['reorder']))
        <p class="dw-note mt-0">Tidak ada obat yang perlu dipesan.</p>
    @else
        <ul class="dw-list">
            @foreach ($data['reorder'] as $row)
                <li class="dw-item">
                    <span class="dw-item-main">
                        <span class="dw-item-title d-block">{{ $row->nama }}@if ($row->wajib) <span class="text-danger" title="Wajib ada">*</span>@endif</span>
                        <span class="dw-item-meta d-block">
                            Sisa {{ $qty($row->stok) }} {{ $row->satuan }}
                            @if ($row->days !== null) · cukup {{ $row->days }} hari @endif
                            @if ($row->saran > 0) · saran pesan {{ $qty($row->saran) }} @endif
                        </span>
                    </span>
                    @if ($row->status === 'kritis')
                        <x-dash.pill tone="critical">Kritis</x-dash.pill>
                    @else
                        <x-dash.pill tone="warning">Perlu pesan</x-dash.pill>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif

    <div class="dw-section-label">Kedaluwarsa ≤ {{ $data['expiryDays'] }} hari</div>
    @if (empty($data['expiring']))
        <p class="dw-note mt-0">Tidak ada batch yang segera kedaluwarsa.</p>
    @else
        <ul class="dw-list">
            @foreach ($data['expiring'] as $row)
                <li class="dw-item">
                    <span class="dw-item-main">
                        <span class="dw-item-title d-block">{{ $row->nama }}</span>
                        <span class="dw-item-meta d-block">
                            {{ collect([$row->batch ? 'Batch ' . $row->batch : null, $row->gudang, 'stok ' . $qty($row->stok)])->filter()->implode(' · ') }}
                        </span>
                    </span>
                    @if ($row->days < 0)
                        <x-dash.pill tone="critical">Lewat {{ $row->date->translatedFormat('d M') }}</x-dash.pill>
                    @else
                        <x-dash.pill tone="warning">{{ $row->date->translatedFormat('d M Y') }}</x-dash.pill>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif
</x-dash.card>
