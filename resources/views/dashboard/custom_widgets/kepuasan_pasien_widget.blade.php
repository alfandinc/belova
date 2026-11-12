@php
    use App\Support\Dashboard\Fmt;
    $data = $widget->data;
@endphp

<x-dash.card :widget="$widget" :subtitle="$data['periodLabel'] . ' · survei dari layar pasien di kasir'" :href="$data['reportUrl']" href-label="Laporan Kepuasan Pasien">
    @if ($data['total'] === 0)
        <x-dash.empty icon="smile" title="Belum ada survei" text="Belum ada pasien yang mengisi survei kepuasan pada periode ini." />
    @else
        <div class="dw-stats">
            <x-dash.stat label="Rata-rata rating" :value="Fmt::num($data['avg'], 2) . ' / 5'" :trend="$data['avgTrend']" :trend-label="'vs ' . $data['previousLabel']" />
            <x-dash.stat label="Pasien puas" :value="Fmt::pct($data['puasPct'])" meta="rating Puas & Sangat Puas" />
        </div>
        <div class="dw-stats mt-2">
            <x-dash.stat label="Survei terisi" :value="Fmt::num($data['total'])" />
            <x-dash.stat label="Tidak puas" :value="Fmt::num($data['tidakPuas'])" :tone="$data['tidakPuas'] > 0 ? 'critical' : null" meta="rating 1–2" />
            <x-dash.stat label="Keluhan tertulis" :value="Fmt::num($data['keluhanCount'])" :tone="$data['keluhanCount'] > 0 ? 'warning' : null" />
        </div>

        <div class="dw-section-label">Sebaran rating</div>
        <div class="dw-bars">
            @foreach ($data['distribution'] as $row)
                <x-dash.bar :label="$row['label']" :value="Fmt::num($row['count']) . ' · ' . Fmt::pct($row['pct'])" :pct="$row['pct']" />
            @endforeach
        </div>

        <div class="dw-section-label">Per klinik</div>
        <div class="dw-clinics is-columns is-compact">
            @foreach ($data['clinics'] as $c)
                <x-dash.clinic :klinik="$c['klinik']">
                    <div class="dw-stat-value">{{ Fmt::num($c['avg'], 2) }} / 5</div>
                    <div class="dw-stat-meta">{{ Fmt::num($c['total']) }} survei · {{ Fmt::pct($c['puasPct']) }} puas</div>
                </x-dash.clinic>
            @endforeach
        </div>

        <div class="dw-section-label">Keluhan & rating rendah terbaru</div>
        @if (empty($data['keluhan']))
            <p class="dw-note mt-0">Tidak ada keluhan atau rating rendah pada periode ini.</p>
        @else
            <ul class="dw-list">
                @foreach ($data['keluhan'] as $row)
                    <li class="dw-item">
                        <span class="dw-item-main">
                            <span class="dw-item-title d-block" style="white-space: normal;">{{ $row['text'] ?: 'Tanpa keterangan' }}</span>
                            <span class="dw-item-meta d-block">{{ $row['meta'] }}</span>
                        </span>
                        <x-dash.pill :tone="$row['tone']" :icon="false">{{ $row['label'] }}</x-dash.pill>
                    </li>
                @endforeach
            </ul>
        @endif
    @endif
</x-dash.card>
