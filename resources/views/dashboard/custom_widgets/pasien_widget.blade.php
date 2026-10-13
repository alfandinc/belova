@php
    use App\Support\Dashboard\Fmt;
    $data = $widget->data;
    $maxAge = max(1, collect($data['ageBars'])->max('count'));
    $maxSpend = max(1, collect($data['ageBars'])->max('spend'));
    $genderTotal = collect($data['gender'])->sum('count');
@endphp

<x-dash.card :widget="$widget" :subtitle="$data['periodLabel'] . ' · pasien yang sudah diperiksa'" :href="url('/erm/pasiens')" href-label="Data Pasien">
    <x-slot:actions>
        <select name="pasien_klinik_id" class="dw-select" data-dashboard-filter-input aria-label="Filter klinik">
            <option value="0">Semua klinik</option>
            @foreach ($data['kliniks'] as $klinik)
                <option value="{{ $klinik->id }}" @selected($data['klinikId'] === (int) $klinik->id)>{{ $klinik->nama }}</option>
            @endforeach
        </select>
    </x-slot:actions>

    @if ($data['patients'] === 0)
        <x-dash.empty icon="users" title="Belum ada pasien diperiksa pada periode ini" />
    @else
        <div class="dw-stats">
            <x-dash.stat label="Pasien" :value="Fmt::num($data['patients'])" :meta="Fmt::num($data['visits']) . ' kunjungan · ' . Fmt::num($data['visits'] / $data['patients'], 1) . 'x per pasien'" />
            <x-dash.stat label="Belanja rata-rata / pasien" :value="Fmt::rpShort($data['revenuePerPatient'])" :meta="'total ' . Fmt::rpShort($data['revenue'])" />
            <x-dash.stat label="Belanja rata-rata / kunjungan" :value="Fmt::rpShort($data['revenuePerVisit'])" />
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="dw-section-label">Usia{{ $data['averageAge'] ? ' · rata-rata ' . Fmt::num($data['averageAge'], 1) . ' th' : '' }}</div>
                <div class="dw-bars">
                    @foreach ($data['ageBars'] as $bar)
                        <x-dash.bar :label="$bar['label']" :value="Fmt::num($bar['count']) . ' · ' . Fmt::pct($bar['pct'])" :pct="$bar['count'] / $maxAge * 100" />
                    @endforeach
                </div>
                @if ($data['unknownAge'] > 0)
                    <p class="dw-note">{{ Fmt::num($data['unknownAge']) }} pasien tanpa tanggal lahir.</p>
                @endif
            </div>
            <div class="col-md-6">
                <div class="dw-section-label">Belanja rata-rata per usia</div>
                <div class="dw-bars">
                    @foreach ($data['ageBars'] as $bar)
                        <x-dash.bar :label="$bar['label']" :value="$bar['count'] > 0 ? Fmt::rpShort($bar['spend']) : '–'" :pct="$bar['spend'] / $maxSpend * 100" />
                    @endforeach
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="dw-section-label">Jenis kelamin</div>
                <x-dash.stack :segments="collect($data['gender'])->map(fn ($g) => [
                    'label' => $g['label'],
                    'value' => Fmt::num($g['count']),
                    'pct' => Fmt::share($g['count'], $genderTotal),
                    'color' => $g['color'],
                ])->all()" />
            </div>
            <div class="col-md-6">
                <div class="dw-section-label">Status pasien</div>
                <div class="dw-bars">
                    @foreach ($data['statuses'] as $status)
                        <x-dash.bar :label="$status['label']" :value="Fmt::num($status['count']) . ' · ' . Fmt::pct($status['pct'])" :pct="$status['pct']" />
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</x-dash.card>
