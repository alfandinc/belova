{{--
    One headline number. $trend comes from App\Support\Dashboard\Fmt::trend().
    $clinics (from App\Support\Dashboard\Clinics::split) adds the number split per clinic in the clinic colors.
--}}
@props(['label', 'value', 'meta' => null, 'trend' => null, 'trendLabel' => null, 'tone' => null, 'clinics' => null])

@php
    $trendClass = $trend ? match ($trend['good']) { true => 'is-good', false => 'is-bad', default => 'is-flat' } : null;
    $trendIcon = $trend ? match ($trend['dir']) { 'up' => 'arrow-up-right', 'down' => 'arrow-down-right', default => 'minus' } : null;
@endphp

<div {{ $attributes->merge(['class' => 'dw-stat' . ($tone ? ' is-' . $tone : '')]) }}>
    <div class="dw-stat-label">{{ $label }}</div>
    <div class="dw-stat-value">{{ $value }}</div>
    @if ($trend && $trend['dir'] === 'new')
        <div class="dw-trend is-flat"><i data-feather="minus"></i><span class="ml-0">belum ada pembanding{{ $trendLabel ? ' (' . $trendLabel . ')' : '' }}</span></div>
    @elseif ($trend)
        <div class="dw-trend {{ $trendClass }}">
            <i data-feather="{{ $trendIcon }}"></i>{{ $trend['label'] }}
            @if ($trendLabel)
                <span>{{ $trendLabel }}</span>
            @endif
        </div>
    @endif
    @if ($meta)
        <div class="dw-stat-meta">{{ $meta }}</div>
    @endif
    @if ($clinics)
        <x-dash.split :items="$clinics" />
    @endif
</div>
