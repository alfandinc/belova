@php
    use App\Support\Dashboard\Fmt;
    $data = $widget->data;
@endphp

<x-dash.card :widget="$widget" :subtitle="$data['periodLabel'] . ' · pasien yang pertama kali diperiksa'">
    <div class="dw-stats">
        <x-dash.stat label="Pasien baru" :value="Fmt::num($data['newCount'])" :trend="$data['trend']" :trend-label="'vs ' . $data['previousLabel']" />
        <x-dash.stat label="Lewat referral" :value="Fmt::pct($data['referredPct'])" meta="selain datang langsung" />
    </div>
    <div class="dw-stats mt-2">
        <x-dash.stat label="Belanja pasien baru" :value="Fmt::rpShort($data['newAvg'])" meta="rata-rata per pasien" />
        <x-dash.stat label="Belanja pasien lama" :value="Fmt::rpShort($data['oldAvg'])" meta="rata-rata per pasien" />
        <x-dash.stat label="Omset dari pasien baru" :value="Fmt::pct($data['newOmsetPct'])" meta="dari omset periode" />
    </div>

    <div class="dw-section-label">Pasien baru per klinik · klinik kunjungan pertama</div>
    <div class="dw-clinics is-columns is-compact">
        @foreach ($data['clinics'] as $c)
            <x-dash.clinic :klinik="$c['klinik']">
                <div class="dw-stat-value">{{ Fmt::num($c['count']) }}</div>
                <x-dash.delta :trend="$c['trend']" label="vs periode lalu" />
            </x-dash.clinic>
        @endforeach
    </div>

    <div class="dw-section-label">Sumber pasien baru</div>
    @if (empty($data['sources']))
        <p class="dw-note mt-0">Belum ada pasien baru pada periode ini.</p>
    @else
        <div class="dw-bars">
            @foreach ($data['sources'] as $source)
                <x-dash.bar :label="$source['label']" :value="Fmt::num($source['count']) . ' · ' . Fmt::pct($source['pct'])"
                    :pct="$source['pct']" :sub="'Omset ' . Fmt::rp($source['omset'])" />
            @endforeach
        </div>
    @endif

    <div class="dw-section-label">Pasien baru per bulan · 12 bulan terakhir</div>
    <div class="dw-chart" data-dash-chart='@json($data['chart'])' role="img" aria-label="Jumlah pasien baru per bulan"></div>
</x-dash.card>
