{{-- Inline change vs a comparison (from Fmt::trend): arrow + percent, green when good, red when bad. --}}
@props(['trend', 'label' => null])

@php
    $class = match ($trend['good']) { true => 'is-good', false => 'is-bad', default => 'is-flat' };
    $icon = match ($trend['dir']) { 'up' => 'arrow-up-right', 'down' => 'arrow-down-right', default => 'minus' };
@endphp

@if ($trend['dir'] === 'new')
    <span {{ $attributes->merge(['class' => 'dw-trend dw-delta is-flat']) }} title="Periode pembanding belum ada datanya">
        <i data-feather="minus"></i><span class="ml-0">tanpa pembanding</span>
    </span>
@else
    <span {{ $attributes->merge(['class' => 'dw-trend dw-delta ' . $class]) }} title="{{ $label }}">
        <i data-feather="{{ $icon }}"></i>{{ $trend['label'] }}@if ($label)<span>{{ $label }}</span>@endif
    </span>
@endif
