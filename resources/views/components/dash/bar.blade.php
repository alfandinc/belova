{{--
    Labeled horizontal bar; the value is always printed, so color never carries the number alone.
    $marker (0-100) draws a thin tick, e.g. where a target should be by today.
--}}
@props(['label', 'value', 'pct' => 0, 'color' => null, 'sub' => null, 'href' => null, 'marker' => null])

@php
    $pct = max(0, min(100, (float) $pct));
    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }} @if ($href) href="{{ $href }}" @endif {{ $attributes->merge(['class' => 'dw-bar']) }} title="{{ $label }}: {{ $value }}">
    <div class="dw-bar-top">
        <span class="dw-bar-label">
            @if ($color)
                <i class="dw-dot" style="background: {{ $color }}"></i>
            @endif
            {{ $label }}
        </span>
        <span class="dw-bar-value">{{ $value }}</span>
    </div>
    <div class="dw-bar-track {{ $marker !== null ? 'has-marker' : '' }}">
        <div class="dw-bar-fill" style="width: {{ $pct }}%; {{ $color ? 'background: ' . $color . ';' : '' }}"></div>
        @if ($marker !== null)
            <span class="dw-meter-pace" style="left: {{ max(0, min(100, (float) $marker)) }}%"></span>
        @endif
    </div>
    @if ($sub)
        <div class="dw-bar-sub">{{ $sub }}</div>
    @endif
</{{ $tag }}>
