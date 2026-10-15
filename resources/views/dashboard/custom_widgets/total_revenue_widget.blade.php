@php
    use App\Support\Dashboard\Fmt;
    $data = $widget->data;
    $onPace = $data['monthTargetPct'] !== null && $data['monthTargetPct'] >= $data['pace'];
@endphp

<x-dash.card :widget="$widget" :subtitle="'Per ' . $data['refLabel'] . ' · hari ke-' . $data['day'] . ' dari ' . $data['daysInMonth']"
    :href="route('finance.billing.index')" href-label="Buka Billing">
    <div class="dw-stats">
        <x-dash.stat class="is-hero" :label="'Omset bulan ini (MTD) · ' . $data['monthLabel']" :value="Fmt::rp($data['mtd'])"
            :trend="$data['mtdMoM']" :clinics="$data['mtdClinics']" :trend-label="'vs ' . $data['mtdMoMLabel']"
            :meta="$data['mtdYoY']['dir'] === 'new' ? 'Belum ada data bulan yang sama tahun lalu' : 'YoY ' . ($data['mtdYoY']['dir'] === 'down' ? '−' : ($data['mtdYoY']['dir'] === 'up' ? '+' : '')) . $data['mtdYoY']['label'] . ' vs bulan yang sama tahun lalu'" />
        <x-dash.stat class="is-hero" label="Omset tahun ini (YTD)" :value="Fmt::rp($data['ytd'])"
            :trend="$data['ytdYoY']" :clinics="$data['ytdClinics']" :trend-label="'vs ' . $data['ytdLastYearLabel']" />
    </div>

    <div class="dw-section-label">Target {{ $data['monthLabel'] }}</div>
    @if ($data['monthTarget'] > 0)
        <div class="d-flex justify-content-between align-items-center mb-2" style="gap: 8px;">
            @if ($data['monthTargetPct'] >= 100)
                <x-dash.pill tone="good">Target tercapai</x-dash.pill>
            @elseif ($onPace)
                <x-dash.pill tone="good" icon="trending-up">Sesuai jalur</x-dash.pill>
            @else
                <x-dash.pill tone="warning" icon="trending-down">Di bawah jalur</x-dash.pill>
            @endif
            <span class="dw-stat-meta mt-0">Garis tipis = seharusnya ±{{ Fmt::pct($data['pace']) }} di hari ke-{{ $data['day'] }}</span>
        </div>
        <x-dash.bar :label="'Tercapai ' . Fmt::pct($data['monthTargetPct'], 1)"
            :value="Fmt::rpShort($data['mtd']) . ' / ' . Fmt::rpShort($data['monthTarget'])"
            :pct="$data['monthTargetPct']"
            :color="$data['monthTargetPct'] >= 100 ? 'var(--dw-good)' : null"
            :marker="$data['pace']"
            :sub="'Proyeksi akhir bulan ' . Fmt::rp($data['projection']) . ' (' . Fmt::pct($data['projectionPct']) . ' dari target)'" />
    @else
        <p class="dw-note mt-0">Target {{ $data['monthLabel'] }} belum diisi. Proyeksi akhir bulan: {{ Fmt::rp($data['projection']) }}.</p>
    @endif

    {{-- In January the year target equals the month target, so it is only shown from February --}}
    @if ($data['ytdTarget'] > 0 && $data['ytdTarget'] != $data['monthTarget'])
        <div class="mt-3">
            <x-dash.bar :label="'Target ' . $data['ytdTargetLabel'] . ' · ' . Fmt::pct($data['ytdTargetPct'], 1)"
                :value="Fmt::rpShort($data['ytd']) . ' / ' . Fmt::rpShort($data['ytdTarget'])"
                :pct="$data['ytdTargetPct']"
                :color="$data['ytdTargetPct'] >= 100 ? 'var(--dw-good)' : null" />
        </div>
    @endif

    <div class="dw-section-label">Omset kumulatif per hari</div>
    @if ($data['chart']['hasData'])
        <div class="dw-chart" data-dash-chart='@json($data['chart'])' role="img" aria-label="Omset kumulatif bulan ini dibanding bulan lalu dan target"></div>
    @else
        <x-dash.empty icon="bar-chart-2" title="Belum ada pembayaran bulan ini dan bulan lalu" />
    @endif
</x-dash.card>
