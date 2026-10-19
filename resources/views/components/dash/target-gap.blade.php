{{--
    What a target still needs (RevenueTargets::gap): the shortfall and the omset per day over the days
    left, "tercapai" once reached, or the final shortfall when the target's months are over.
--}}
@props(['gap' => null])

@if ($gap)
    @php($fmt = \App\Support\Dashboard\Fmt::class)
    <div {{ $attributes->merge(['class' => 'dw-gap' . ($gap['reached'] ? ' is-done' : '')]) }}>
        @if ($gap['reached'])
            <i data-feather="check-circle"></i>
            <span>Target tercapai{!! $gap['over'] > 0 ? ' · lebih <strong>' . e($fmt::rp($gap['over'])) . '</strong>' : '' !!}</span>
        @elseif ($gap['ended'])
            <i data-feather="flag"></i>
            <span>Kurang <strong>{{ $fmt::rp($gap['need']) }}</strong> · periode target sudah berakhir</span>
        @else
            <i data-feather="target"></i>
            <span>Kurang <strong>{{ $fmt::rp($gap['need']) }}</strong> · perlu <strong>{{ $fmt::rp($gap['perDay']) }}/hari</strong>
                selama {{ $gap['daysLeft'] }} hari lagi</span>
        @endif
    </div>
@endif
