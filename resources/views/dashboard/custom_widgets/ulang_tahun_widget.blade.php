@php
    $data = $widget->data;
    $initials = fn ($name) => collect(preg_split('/\s+/', trim($name)))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
@endphp

<x-dash.card :widget="$widget" :subtitle="$data['todayCount'] > 0 ? $data['todayCount'] . ' rekan berulang tahun hari ini' : $data['daysAhead'] . ' hari ke depan'">
    @if (empty($data['people']))
        <x-dash.empty icon="gift" title="Tidak ada ulang tahun minggu ini" />
    @else
        <ul class="dw-list">
            @foreach ($data['people'] as $person)
                <li class="dw-item">
                    <span class="dw-avatar">{{ $initials($person->nama) }}</span>
                    <span class="dw-item-main">
                        <span class="dw-item-title d-block">{{ $person->nama }}</span>
                        <span class="dw-item-meta d-block">{{ $person->date->translatedFormat('l, d M') }} · {{ $person->age }} tahun</span>
                    </span>
                    @if ($person->days === 0)
                        <x-dash.pill tone="good" icon="gift">Hari ini</x-dash.pill>
                    @elseif ($person->days === 1)
                        <x-dash.pill tone="info" icon="calendar">Besok</x-dash.pill>
                    @else
                        <x-dash.pill tone="neutral" icon="calendar">{{ $person->days }} hari lagi</x-dash.pill>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif
</x-dash.card>
