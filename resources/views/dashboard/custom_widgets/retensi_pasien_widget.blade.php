@php
    use App\Support\Dashboard\Fmt;
    $data = $widget->data;
@endphp

<x-dash.card :widget="$widget" :subtitle="$data['periodLabel'] . ' · per klinik · kunjungan yang sudah diperiksa'">
    {{-- Per clinic only: the clinics are managed separately, a return counts only to the same clinic --}}
    <div class="dw-clinics">
        @foreach ($data['clinics'] as $c)
            <x-dash.clinic :klinik="$c['klinik']" :meta="Fmt::num($c['periodPatients']) . ' pasien periode ini'">
                <div class="dw-stats">
                    <x-dash.stat label="Pasien kembali" :value="$c['periodPatients'] > 0 ? Fmt::pct($c['returningPct']) : '–'"
                        :meta="Fmt::num($c['returning']) . ' dari ' . Fmt::num($c['periodPatients']) . ' pasien'" />
                    <x-dash.stat label="Retensi 90 hari" :value="$c['retention90'] !== null ? Fmt::pct($c['retention90']) : '–'"
                        meta="pasien baru yang kembali ≤ 90 hari" />
                    <x-dash.stat label="Frekuensi kunjungan" :value="$c['frequency'] > 0 ? Fmt::num($c['frequency'], 1) . 'x' : '–'"
                        meta="per pasien, 12 bulan terakhir" />
                    <x-dash.stat label="Berisiko hilang" :value="Fmt::num($c['atRisk'])" :tone="$c['atRisk'] > 0 ? 'warning' : null"
                        meta="tidak kembali 6–12 bulan" />
                </div>

                <details class="dw-retensi-cohort">
                    <summary class="dw-section-label">Kohort pasien baru · % yang kembali dalam …</summary>
                    @if (empty($c['cohorts']))
                        <p class="dw-note mt-0">Belum ada pasien baru di klinik ini dalam 6 bulan terakhir.</p>
                    @else
                        <div class="dw-table-wrap">
                            <table class="dw-grid-table dw-cohort" style="min-width: 420px;">
                                <thead>
                                    <tr>
                                        <th>Bulan pertama datang</th>
                                        <th class="text-right">Pasien baru</th>
                                        @foreach ($data['windows'] as $days)
                                            <th class="text-center">{{ $days }} hari</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($c['cohorts'] as $cohort)
                                        <tr>
                                            <td><span class="dw-cell-title">{{ $cohort['label'] }}</span></td>
                                            <td class="text-right"><span class="dw-cell-num">{{ Fmt::num($cohort['size']) }}</span></td>
                                            @foreach ($data['windows'] as $days)
                                                @php($value = $cohort['cells'][$days])
                                                @if ($value === null)
                                                    <td class="dw-heat is-na" title="Belum cukup waktu untuk diukur">–</td>
                                                @else
                                                    {{-- One hue, opacity by value; the number is always printed --}}
                                                    @php($alpha = 0.08 + min(100, $value) / 100 * 0.8)
                                                    <td class="dw-heat" style="background: rgba(var(--dw-heat-rgb), {{ number_format($alpha, 2, '.', '') }}); color: {{ $alpha > 0.5 ? '#fff' : 'var(--dsh-ink)' }};"
                                                        title="{{ Fmt::pct($value, 1) }} dari {{ $cohort['size'] }} pasien kembali dalam {{ $days }} hari">
                                                        {{ Fmt::pct($value) }}
                                                    </td>
                                                @endif
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </details>
            </x-dash.clinic>
        @endforeach
    </div>

    <p class="dw-note">Pasien dihitung kembali hanya kalau datang lagi ke klinik yang sama. "–" di kohort berarti bulan itu belum cukup lama untuk diukur, misalnya pasien baru bulan ini baru bisa dinilai 90 hari setelah akhir bulan.</p>
</x-dash.card>

@once
    <style>
        .dw-retensi-cohort { margin-top: 10px; }
        .dw-retensi-cohort > summary { cursor: pointer; list-style: none; margin: 0 0 6px; user-select: none; }
        .dw-retensi-cohort > summary::-webkit-details-marker { display: none; }
        .dw-retensi-cohort > summary::before { content: '▸'; display: inline-block; width: 1em; transition: transform .15s; }
        .dw-retensi-cohort[open] > summary::before { transform: rotate(90deg); }
    </style>
@endonce
