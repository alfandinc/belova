@php
    use App\Support\Dashboard\Fmt;
    $data = $widget->data;
@endphp

<x-dash.card :widget="$widget" :subtitle="$data['periodLabel'] . ' · kunjungan yang sudah diperiksa'">
    <div class="dw-stats">
        <x-dash.stat label="Pasien kembali" :value="Fmt::pct($data['returningPct'])"
            :meta="Fmt::num($data['returning']) . ' dari ' . Fmt::num($data['periodPatients']) . ' pasien periode ini'" />
        <x-dash.stat label="Retensi 90 hari" :value="$data['retention90'] !== null ? Fmt::pct($data['retention90']) : '–'"
            meta="pasien baru yang kembali ≤ 90 hari" />
        <x-dash.stat label="Frekuensi kunjungan" :value="Fmt::num($data['frequency'], 1) . 'x'" meta="per pasien, 12 bulan terakhir" />
        <x-dash.stat label="Berisiko hilang" :value="Fmt::num($data['atRisk'])" :tone="$data['atRisk'] > 0 ? 'warning' : null"
            meta="tidak kembali 6–12 bulan" />
    </div>

    <div class="dw-section-label">Kohort pasien baru · % yang kembali dalam …</div>
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
                @foreach ($data['cohorts'] as $cohort)
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
    <p class="dw-note">"–" berarti bulan itu belum cukup lama untuk diukur. Contoh: pasien baru bulan ini baru bisa dinilai 90 hari setelah akhir bulan.</p>
</x-dash.card>
