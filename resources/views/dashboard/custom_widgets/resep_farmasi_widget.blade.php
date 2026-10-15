@php
    use App\Support\Dashboard\Fmt;
    $data = $widget->data;
@endphp

<x-dash.card :widget="$widget" :subtitle="$data['todayLabel']" :href="url('/erm/eresepfarmasi')" href-label="Buka E-Resep">
    @if ($data['total'] === 0)
        <x-dash.empty icon="file-text" title="Belum ada resep hari ini" />
    @else
        <div class="dw-section-label">Semua klinik</div>
        <div class="dw-stats">
            <x-dash.stat label="Perlu diproses" :value="Fmt::num($data['pending'])" :tone="$data['pending'] > 0 ? 'warning' : 'good'" meta="belum dikirim ke billing" />
            <x-dash.stat label="Sudah diproses" :value="Fmt::num($data['processed'])" :meta="'dari ' . Fmt::num($data['total']) . ' resep'" />
        </div>

        <div class="dw-section-label">Per klinik</div>
        <div class="dw-clinics">
            @foreach ($data['clinics'] as $c)
                <x-dash.clinic :klinik="$c['klinik']" :meta="Fmt::num($c['total']) . ' resep'">
                    @if ($c['total'] === 0)
                        <div class="dw-clinic-empty">Belum ada resep hari ini.</div>
                    @else
                        <div class="dw-stats">
                            <x-dash.stat label="Perlu diproses" :value="Fmt::num($c['pending'])" :tone="$c['pending'] > 0 ? 'warning' : 'good'" />
                            <x-dash.stat label="Sudah diproses" :value="Fmt::num($c['processed'])" :meta="Fmt::pct(Fmt::share($c['processed'], $c['total']))" />
                        </div>
                        @if (!empty($c['waiting']))
                            <div class="dw-section-label">Menunggu farmasi · terlama dulu</div>
                            <ul class="dw-list">
                                @foreach ($c['waiting'] as $row)
                                    <li class="dw-item">
                                        <span class="dw-item-lead">{{ $row->no_antrian ?? '–' }}<small>antri</small></span>
                                        <span class="dw-item-main">
                                            <span class="dw-item-title d-block">{{ $row->pasien ?? 'Pasien' }}</span>
                                            @if ($row->dokter)
                                                <span class="dw-item-meta d-block">{{ $row->dokter }}</span>
                                            @endif
                                        </span>
                                        <span class="dw-count">{{ $row->items }} item</span>
                                    </li>
                                @endforeach
                            </ul>
                            @if ($c['more'] > 0)
                                <p class="dw-note mt-1">+{{ $c['more'] }} resep lainnya.</p>
                            @endif
                        @endif
                    @endif
                </x-dash.clinic>
            @endforeach
        </div>
    @endif
</x-dash.card>
