@php
    use App\Support\Dashboard\Fmt;
    $data = $widget->data;
    $maxKlinik = max(1, collect($data['perKlinik'])->max('total'));
@endphp

<x-dash.card :widget="$widget" :subtitle="$data['todayLabel'] . ($data['ownPatients'] ? ' · pasien Anda' : '')"
    :href="route('erm.rawatjalans.index')" href-label="Buka Rawat Jalan">
    @if ($data['total'] === 0 && $data['batal'] === 0)
        <x-dash.empty icon="calendar" title="Belum ada kunjungan hari ini" />
    @else
        <div class="dw-stats">
            <x-dash.stat label="Menunggu diperiksa" :value="Fmt::num($data['menunggu'])" :tone="$data['menunggu'] > 0 ? 'warning' : null" meta="sudah datang" />
            <x-dash.stat label="Selesai" :value="Fmt::num($data['selesai'])" :meta="Fmt::pct($data['progress']) . ' dari ' . Fmt::num($data['total'])" />
            <x-dash.stat label="Belum datang" :value="Fmt::num($data['belumDatang'])" :meta="$data['batal'] > 0 ? Fmt::num($data['batal']) . ' dibatalkan' : 'terdaftar'" />
        </div>

        @if (count($data['perKlinik']) > 1)
            <div class="dw-section-label">Per klinik</div>
            <div class="dw-bars">
                @foreach ($data['perKlinik'] as $klinik)
                    <x-dash.bar :label="$klinik->nama" :value="Fmt::num($klinik->total) . ' pasien'"
                        :pct="$klinik->total / $maxKlinik * 100"
                        :sub="Fmt::num($klinik->selesai) . ' selesai, ' . Fmt::num($klinik->total - $klinik->selesai) . ' belum'" />
                @endforeach
            </div>
        @endif

        <div class="dw-section-label">Antrian menunggu</div>
        @if (empty($data['waiting']))
            <p class="dw-note mt-0">Tidak ada pasien yang sedang menunggu.</p>
        @else
            <ul class="dw-list">
                @foreach ($data['waiting'] as $row)
                    <li class="dw-item">
                        <span class="dw-item-lead">{{ $row->no_antrian ?? '–' }}<small>antri</small></span>
                        <span class="dw-item-main">
                            <span class="dw-item-title d-block">{{ $row->pasien ?? 'Pasien' }}</span>
                            <span class="dw-item-meta d-block">
                                {{ collect([$data['ownPatients'] ? null : $row->dokter, $row->klinik])->filter()->implode(' · ') }}
                            </span>
                        </span>
                        @if ($row->waktu_kunjungan)
                            <span class="dw-item-aside">{{ substr((string) $row->waktu_kunjungan, 0, 5) }}</span>
                        @endif
                    </li>
                @endforeach
            </ul>
            @if ($data['menunggu'] > count($data['waiting']))
                <p class="dw-note">+{{ $data['menunggu'] - count($data['waiting']) }} pasien lainnya menunggu.</p>
            @endif
        @endif
    @endif
</x-dash.card>
