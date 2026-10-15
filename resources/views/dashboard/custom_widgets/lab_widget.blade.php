@php
    use App\Support\Dashboard\Fmt;
    $data = $widget->data;
@endphp

<x-dash.card :widget="$widget" subtitle="Permintaan pemeriksaan yang belum selesai" :href="route('erm.rawatjalans.index')" href-label="Buka Rawat Jalan">
    <div class="dw-section-label">Semua klinik</div>
    <div class="dw-stats">
        <x-dash.stat label="Menunggu" :value="Fmt::num($data['requested'])" :tone="$data['requested'] > 0 ? 'warning' : null" meta="belum diproses" />
        <x-dash.stat label="Diproses" :value="Fmt::num($data['processing'])" meta="sedang dikerjakan" />
        <x-dash.stat label="Selesai hari ini" :value="Fmt::num($data['completedToday'])" meta="pemeriksaan" />
    </div>

    <div class="dw-section-label">Per klinik</div>
    <div class="dw-clinics">
        @foreach ($data['clinics'] as $c)
            <x-dash.clinic :klinik="$c['klinik']">
                <div class="dw-stats">
                    <x-dash.stat label="Menunggu" :value="Fmt::num($c['requested'])" :tone="$c['requested'] > 0 ? 'warning' : null" />
                    <x-dash.stat label="Diproses" :value="Fmt::num($c['processing'])" />
                    <x-dash.stat label="Selesai hari ini" :value="Fmt::num($c['completedToday'])" />
                </div>
                @if (!empty($c['open']))
                    <div class="dw-section-label">Antrian lab · terlama dulu</div>
                    <ul class="dw-list">
                        @foreach ($c['open'] as $row)
                            <li>
                                <a href="{{ route('erm.elab.create', $row->visitationId) }}" class="dw-item">
                                    <span class="dw-item-icon"><i data-feather="thermometer"></i></span>
                                    <span class="dw-item-main">
                                        <span class="dw-item-title d-block">{{ $row->pasien }}</span>
                                        <span class="dw-item-meta d-block">
                                            {{ $row->tests }} pemeriksaan{{ $row->dokter ? ' · ' . $row->dokter : '' }}
                                            · diminta {{ $row->since->translatedFormat($row->old ? 'd M, H:i' : 'H:i') }}
                                        </span>
                                    </span>
                                    @if ($row->processing)
                                        <x-dash.pill tone="info" icon="loader">Diproses</x-dash.pill>
                                    @elseif ($row->old)
                                        <x-dash.pill tone="critical">Sejak {{ $row->since->translatedFormat('d M') }}</x-dash.pill>
                                    @else
                                        <x-dash.pill tone="warning">Menunggu</x-dash.pill>
                                    @endif
                                </a>
                            </li>
                        @endforeach
                    </ul>
                    @if ($c['more'] > 0)
                        <p class="dw-note mt-1">+{{ $c['more'] }} permintaan lainnya.</p>
                    @endif
                @endif
            </x-dash.clinic>
        @endforeach
    </div>
</x-dash.card>
