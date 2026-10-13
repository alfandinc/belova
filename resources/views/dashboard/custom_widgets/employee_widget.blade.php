@php
    use App\Support\Dashboard\Fmt;
    $data = $widget->data;
    $initials = fn ($name) => collect(preg_split('/\s+/', trim($name)))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
@endphp

<x-dash.card :widget="$widget" subtitle="Karyawan aktif saat ini" :href="route('hrd.employee.index')" href-label="Data Karyawan">
    <div class="dw-stats">
        <x-dash.stat label="Karyawan aktif" :value="Fmt::num($data['count'])" :meta="$data['averageAge'] ? 'rata-rata ' . Fmt::num($data['averageAge'], 1) . ' th' : null" />
        @if ($data['female'] + $data['male'] > 0)
            <x-dash.stat label="Perempuan / Laki-laki" :value="Fmt::num($data['female']) . ' / ' . Fmt::num($data['male'])"
                :meta="$data['count'] - $data['female'] - $data['male'] > 0 ? ($data['count'] - $data['female'] - $data['male']) . ' belum diisi' : null" />
        @else
            <x-dash.stat label="Perempuan / Laki-laki" value="–" meta="jenis kelamin belum diisi" />
        @endif
        <x-dash.stat label="Masuk periode ini" :value="Fmt::num($data['joinerCount'])" :meta="$data['periodLabel']" />
    </div>

    <div class="dw-section-label">Status kepegawaian</div>
    <x-dash.stack :segments="$data['statuses']" />

    <div class="dw-section-label">Kontrak berakhir ≤ {{ $data['contractDays'] }} hari</div>
    @if (empty($data['contracts']))
        <p class="dw-note mt-0">Tidak ada kontrak yang segera berakhir.</p>
    @else
        <ul class="dw-list">
            @foreach ($data['contracts'] as $contract)
                <li class="dw-item">
                    <span class="dw-avatar">{{ $initials($contract->nama) }}</span>
                    <span class="dw-item-main">
                        <span class="dw-item-title d-block">{{ $contract->nama }}</span>
                        <span class="dw-item-meta d-block">Berakhir {{ $contract->end->translatedFormat('d M Y') }}</span>
                    </span>
                    @if ($contract->days < 0)
                        <x-dash.pill tone="critical">Lewat {{ abs($contract->days) }} hari</x-dash.pill>
                    @elseif ($contract->days <= 7)
                        <x-dash.pill tone="critical">{{ $contract->days === 0 ? 'Hari ini' : $contract->days . ' hari lagi' }}</x-dash.pill>
                    @else
                        <x-dash.pill tone="warning">{{ $contract->days }} hari lagi</x-dash.pill>
                    @endif
                </li>
            @endforeach
        </ul>
        @if ($data['contractCount'] > count($data['contracts']))
            <p class="dw-note">+{{ $data['contractCount'] - count($data['contracts']) }} kontrak lainnya.</p>
        @endif
    @endif

    @if (!empty($data['joiners']))
        <div class="dw-section-label">Karyawan baru</div>
        <ul class="dw-list">
            @foreach ($data['joiners'] as $joiner)
                <li class="dw-item">
                    <span class="dw-avatar">{{ $initials($joiner->nama) }}</span>
                    <span class="dw-item-main">
                        <span class="dw-item-title d-block">{{ $joiner->nama }}</span>
                        <span class="dw-item-meta d-block">Masuk {{ $joiner->tanggal_masuk->translatedFormat('d M Y') }} · {{ ucfirst((string) $joiner->status) }}</span>
                    </span>
                </li>
            @endforeach
        </ul>
    @endif
</x-dash.card>
