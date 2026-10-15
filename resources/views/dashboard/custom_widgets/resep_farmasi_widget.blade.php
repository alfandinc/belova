@php
    use App\Support\Dashboard\Fmt;
    $data = $widget->data;
@endphp

<x-dash.card :widget="$widget" :subtitle="$data['todayLabel']" :href="url('/erm/eresepfarmasi')" href-label="Buka E-Resep">
    @if ($data['total'] === 0)
        <x-dash.empty icon="file-text" title="Belum ada resep hari ini" />
    @else
        <div class="dw-stats">
            <x-dash.stat label="Perlu diproses" :value="Fmt::num($data['pending'])" :tone="$data['pending'] > 0 ? 'warning' : 'good'" meta="belum dikirim ke billing" :clinics="$data['pendingClinics']" />
            <x-dash.stat label="Sudah diproses" :value="Fmt::num($data['processed'])" :meta="'dari ' . Fmt::num($data['total']) . ' resep'" :clinics="$data['processedClinics']" />
        </div>

        <div class="mt-3">
            <x-dash.bar label="Progres hari ini" :value="Fmt::pct(Fmt::share($data['processed'], $data['total']))"
                :pct="Fmt::share($data['processed'], $data['total'])" :color="$data['pending'] === 0 ? 'var(--dw-good)' : null" />
        </div>

        <div class="dw-section-label">Menunggu farmasi · terlama dulu</div>
        @if (empty($data['waiting']))
            <p class="dw-note mt-0">Semua resep hari ini sudah diproses.</p>
        @else
            <ul class="dw-list">
                @foreach ($data['waiting'] as $row)
                    <li class="dw-item">
                        <span class="dw-item-lead">{{ $row->no_antrian ?? '–' }}<small>antri</small></span>
                        <span class="dw-item-main">
                            <span class="dw-item-title d-block">{{ $row->pasien ?? 'Pasien' }}</span>
                            <span class="dw-item-meta d-block">{{ collect([$row->dokter, $row->klinik])->filter()->implode(' · ') }}</span>
                        </span>
                        <span class="dw-count">{{ $row->items }} item</span>
                    </li>
                @endforeach
            </ul>
            @if ($data['pending'] > count($data['waiting']))
                <p class="dw-note">+{{ $data['pending'] - count($data['waiting']) }} resep lainnya.</p>
            @endif
        @endif
    @endif
</x-dash.card>
