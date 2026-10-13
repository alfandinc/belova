{{--
    Compact target meter for tables: percent achieved, a bar, and (optionally) a tick where the
    pace says it should be today. Green once the target is reached.
--}}
@props(['pct' => null, 'pace' => null])

@if ($pct === null)
    <span class="dw-stat-meta">Belum ada target</span>
@else
    @php($fill = max(0, min(100, (float) $pct)))
    <div {{ $attributes->merge(['class' => 'dw-meter' . ($pct >= 100 ? ' is-done' : ($pace !== null && $pct < $pace ? ' is-behind' : ''))]) }}
         title="Tercapai {{ \App\Support\Dashboard\Fmt::pct($pct, 1) }}{{ $pace !== null ? ' · seharusnya ±' . \App\Support\Dashboard\Fmt::pct($pace) : '' }}">
        <span class="dw-meter-value">{{ \App\Support\Dashboard\Fmt::pct($pct) }}</span>
        <span class="dw-meter-track">
            <span class="dw-meter-fill" style="width: {{ $fill }}%"></span>
            @if ($pace !== null)
                <span class="dw-meter-pace" style="left: {{ max(0, min(100, $pace)) }}%"></span>
            @endif
        </span>
    </div>
@endif
