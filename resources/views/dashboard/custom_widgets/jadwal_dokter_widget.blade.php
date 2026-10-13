@php
    $data = $widget->data;
    $initials = fn ($name) => collect(preg_split('/\s+/', trim(preg_replace('/^(dr|drg)\.?\s*/i', '', $name))))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
@endphp

<x-dash.card :widget="$widget" :subtitle="$data['todayLabel'] . ' · ' . $data['onNow'] . ' sedang praktik'"
    :href="route('hrd.dokter-schedule.index')" href-label="Jadwal Dokter">
    @if (empty($data['schedules']))
        <x-dash.empty icon="user-x" title="Tidak ada jadwal dokter hari ini" />
    @else
        <ul class="dw-list">
            @foreach ($data['schedules'] as $row)
                <li class="dw-item">
                    <span class="dw-avatar">{{ $initials($row->nama) }}</span>
                    <span class="dw-item-main">
                        <span class="dw-item-title d-block">{{ $row->nama }}</span>
                        <span class="dw-item-meta d-block">
                            {{ collect([$row->spesialisasi, $row->klinik])->filter()->implode(' · ') }}
                            · {{ $row->pasien }} pasien{{ $row->pasien ? ' (' . $row->selesai . ' selesai)' : '' }}
                        </span>
                    </span>
                    <span class="text-right">
                        <span class="dw-item-aside d-block mb-1">{{ $row->jam }}</span>
                        @if ($row->state === 'now')
                            <x-dash.pill tone="good" icon="activity">Praktik</x-dash.pill>
                        @elseif ($row->state === 'soon')
                            <x-dash.pill tone="info">Belum mulai</x-dash.pill>
                        @else
                            <x-dash.pill tone="neutral" icon="check">Selesai</x-dash.pill>
                        @endif
                    </span>
                </li>
            @endforeach
        </ul>
    @endif
</x-dash.card>
