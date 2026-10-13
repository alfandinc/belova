{{-- Status label: always icon + text, never color alone. tone: good | warning | critical | info | neutral --}}
@props(['tone' => 'neutral', 'icon' => null])

@php
    $icon = $icon ?? match ($tone) {
        'good' => 'check-circle',
        'warning' => 'alert-triangle',
        'critical' => 'alert-octagon',
        'info' => 'clock',
        default => null,
    };
@endphp

<span {{ $attributes->merge(['class' => 'dw-pill is-' . $tone]) }}>
    @if ($icon)
        <i data-feather="{{ $icon }}"></i>
    @endif
    {{ $slot }}
</span>
