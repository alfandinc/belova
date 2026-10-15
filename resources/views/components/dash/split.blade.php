{{--
    Per-clinic split of one number (from App\Support\Dashboard\Clinics::split): a thin stacked bar in the
    clinic colors and every clinic's value next to its color, so color never carries the number alone.
--}}
@props(['items' => [], 'bar' => true])

@php
    $items = collect($items);
    $total = $items->sum('amount');
@endphp

@if ($items->isNotEmpty())
    <div {{ $attributes->merge(['class' => 'dw-split']) }}>
        @if ($bar)
            <div class="dw-stack is-thin" role="img" aria-label="{{ $items->map(fn ($i) => $i['label'] . ' ' . $i['value'])->implode(', ') }}">
                @if ($total > 0)
                    @foreach ($items->filter(fn ($i) => $i['pct'] > 0) as $item)
                        <span class="dw-stack-seg" style="width: {{ $item['pct'] }}%; background: {{ $item['color'] }};"
                            title="{{ $item['label'] }}: {{ $item['value'] }} ({{ \App\Support\Dashboard\Fmt::pct($item['pct']) }})"></span>
                    @endforeach
                @else
                    <span class="dw-stack-seg is-empty"></span>
                @endif
            </div>
        @endif
        <ul class="dw-split-list">
            @foreach ($items as $item)
                <li class="{{ $item['amount'] == 0 ? 'is-zero' : '' }}">
                    <i class="dw-dot" style="background: {{ $item['color'] }}"></i>
                    <span class="dw-split-label">{{ $item['label'] }}</span>
                    <span class="dw-split-value">{{ $item['value'] }}</span>
                </li>
            @endforeach
        </ul>
    </div>
@endif
