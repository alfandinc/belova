@php
    use App\Support\Dashboard\Fmt;
    $data = $widget->data;
    $t = $data['total'];
@endphp

<x-dash.card :widget="$widget" :subtitle="'Per ' . $data['refLabel'] . ' · garis tipis pada bar = posisi target seharusnya hari ini'">
    <div class="dw-clinics is-columns">
        @foreach ($data['rows'] as $row)
            <x-dash.clinic :klinik="$row->klinik" :meta="Fmt::pct($row->share) . ' omset bulan ini'">
                <div class="dw-stats">
                    <x-dash.stat :label="'Omset ' . $data['monthLabel'] . ' (MTD)'" :value="Fmt::rp($row->mtd)" :trend="$row->mom" trend-label="vs bln lalu" />
                </div>
                <div class="dw-section-label">Target {{ $data['monthLabel'] }}</div>
                <x-dash.meter :pct="$row->monthPct" :pace="$data['pace']" />
                @if ($row->monthTarget > 0)
                    <div class="dw-stat-meta">{{ Fmt::rpShort($row->mtd) }} / {{ Fmt::rpShort($row->monthTarget) }}</div>
                    <x-dash.target-gap :gap="$row->monthGap" />
                @endif

                <div class="dw-stats mt-3">
                    <x-dash.stat label="Omset YTD" :value="Fmt::rp($row->ytd)" :trend="$row->yoy" trend-label="vs thn lalu" />
                </div>
                <div class="dw-section-label">Target {{ $data['ytdTargetLabel'] }}</div>
                <x-dash.meter :pct="$row->ytdPct" />
                @if ($row->ytdTarget > 0)
                    <div class="dw-stat-meta">{{ Fmt::rpShort($row->ytd) }} / {{ Fmt::rpShort($row->ytdTarget) }}</div>
                    <x-dash.target-gap :gap="$row->ytdGap" />
                @endif
            </x-dash.clinic>
        @endforeach
    </div>

    <div class="dw-section-label">Semua klinik</div>
    <div class="dw-stats">
        <x-dash.stat :label="'Omset ' . $data['monthLabel'] . ' (MTD)'" :value="Fmt::rp($t->mtd)" :trend="$t->mom" trend-label="vs bln lalu"
            :meta="$t->monthPct !== null ? 'Target ' . Fmt::pct($t->monthPct) . ' tercapai' : null" />
        <x-dash.stat label="Omset YTD" :value="Fmt::rp($t->ytd)" :trend="$t->yoy" trend-label="vs thn lalu"
            :meta="$t->ytdPct !== null ? 'Target ' . Fmt::pct($t->ytdPct) . ' tercapai' : null" />
    </div>
    @if ($t->monthGap || $t->ytdGap)
        <div class="dw-stats">
            <div><x-dash.target-gap :gap="$t->monthGap" /></div>
            <div><x-dash.target-gap :gap="$t->ytdGap" /></div>
        </div>
    @endif
    <p class="dw-note">MTD dibanding tanggal yang sama bulan lalu; YTD dibanding tanggal yang sama tahun lalu. Omset = invoice lunas/sebagian berdasarkan tanggal bayar.</p>
</x-dash.card>
