{{--
    100% stacked bar with a legend that repeats each value, e.g. composition of a total.
    $segments: list of ['label' => ..., 'value' => display text, 'pct' => 0-100, 'color' => hex]
--}}
@props(['segments' => [], 'legend' => true])

@php
    $visible = collect($segments)->filter(fn ($s) => ($s['pct'] ?? 0) > 0)->values();
@endphp

<div {{ $attributes->merge(['class' => 'dw-stack-wrap']) }}>
    <div class="dw-stack" role="img" aria-label="{{ collect($segments)->map(fn ($s) => $s['label'] . ' ' . $s['value'])->implode(', ') }}">
        @forelse ($visible as $segment)
            <span class="dw-stack-seg" style="width: {{ $segment['pct'] }}%; background: {{ $segment['color'] }};" title="{{ $segment['label'] }}: {{ $segment['value'] }} ({{ \App\Support\Dashboard\Fmt::pct($segment['pct'], 1) }})"></span>
        @empty
            <span class="dw-stack-seg is-empty"></span>
        @endforelse
    </div>
    @if ($legend)
        <ul class="dw-legend">
            @foreach ($segments as $segment)
                <li>
                    <i class="dw-dot" style="background: {{ $segment['color'] }}"></i>
                    <span class="dw-legend-label">{{ $segment['label'] }}</span>
                    <span class="dw-legend-value">{{ $segment['value'] }}</span>
                    <span class="dw-legend-pct">{{ \App\Support\Dashboard\Fmt::pct($segment['pct']) }}</span>
                </li>
            @endforeach
        </ul>
    @endif
</div>
