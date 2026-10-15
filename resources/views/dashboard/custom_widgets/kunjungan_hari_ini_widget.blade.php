@php
    use App\Support\Dashboard\Fmt;
    $data = $widget->data;
@endphp

<x-dash.card :widget="$widget" :subtitle="$data['todayLabel'] . ($data['ownPatients'] ? ' · pasien Anda' : '')"
    :href="route('erm.rawatjalans.index')" href-label="Buka Rawat Jalan">
    @if ($data['total'] === 0 && $data['batal'] === 0)
        <x-dash.empty icon="calendar" title="Belum ada kunjungan hari ini" />
    @else
        <div class="dw-section-label">Semua klinik</div>
        <div class="dw-stats">
            <x-dash.stat label="Menunggu diperiksa" :value="Fmt::num($data['menunggu'])" :tone="$data['menunggu'] > 0 ? 'warning' : null" meta="sudah datang" />
            <x-dash.stat label="Selesai" :value="Fmt::num($data['selesai'])" :meta="Fmt::pct($data['progress']) . ' dari ' . Fmt::num($data['total'])" />
            <x-dash.stat label="Belum datang" :value="Fmt::num($data['belumDatang'])" :meta="$data['batal'] > 0 ? Fmt::num($data['batal']) . ' dibatalkan' : 'terdaftar'" />
        </div>

        <div class="dw-section-label">Per klinik</div>
        <div class="dw-clinics">
            @foreach ($data['clinics'] as $c)
                <x-dash.clinic :klinik="$c['klinik']" :meta="Fmt::num($c['total']) . ' pasien'">
                    @if ($c['total'] === 0 && $c['batal'] === 0)
                        <div class="dw-clinic-empty">Belum ada kunjungan hari ini.</div>
                    @else
                        <div class="dw-stats">
                            <x-dash.stat label="Menunggu" :value="Fmt::num($c['menunggu'])" :tone="$c['menunggu'] > 0 ? 'warning' : null" />
                            <x-dash.stat label="Selesai" :value="Fmt::num($c['selesai'])" :meta="Fmt::pct($c['progress'])" />
                            <x-dash.stat label="Belum datang" :value="Fmt::num($c['belumDatang'])" :meta="$c['batal'] > 0 ? Fmt::num($c['batal']) . ' batal' : null" />
                        </div>
                        @if (!empty($c['waiting']))
                            <div class="dw-section-label">Antrian menunggu</div>
                            <ul class="dw-list">
                                @foreach ($c['waiting'] as $row)
                                    <li class="dw-item">
                                        <span class="dw-item-lead">{{ $row->no_antrian ?? '–' }}<small>antri</small></span>
                                        <span class="dw-item-main">
                                            <span class="dw-item-title d-block">{{ $row->pasien ?? 'Pasien' }}</span>
                                            @if (!$data['ownPatients'] && $row->dokter)
                                                <span class="dw-item-meta d-block">{{ $row->dokter }}</span>
                                            @endif
                                        </span>
                                        @if ($row->waktu_kunjungan)
                                            <span class="dw-item-aside">{{ substr((string) $row->waktu_kunjungan, 0, 5) }}</span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                            @if ($c['moreWaiting'] > 0)
                                <p class="dw-note mt-1">+{{ $c['moreWaiting'] }} pasien lainnya menunggu.</p>
                            @endif
                        @endif
                    @endif
                </x-dash.clinic>
            @endforeach
        </div>
    @endif
</x-dash.card>
