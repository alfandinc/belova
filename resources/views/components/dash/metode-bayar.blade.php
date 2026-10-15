{{--
    Omset by the visit's metode bayar (App\Support\Dashboard\Revenue::metodeBayarSplit): Umum vs Asuransi with
    nominal and percent, then every insurer under Asuransi.
--}}
@props(['split', 'color' => null])

@php
    use App\Support\Dashboard\Fmt;
    $line = fn ($part) => Fmt::rp($part['total']) . ' · ' . Fmt::pct($part['pct'], 1);
@endphp

<div {{ $attributes->merge(['class' => 'dw-bars']) }}>
    <x-dash.bar label="Umum" :value="$line($split['umum'])" :pct="$split['umum']['pct']" :color="$color"
        :sub="Fmt::num($split['umum']['invoices']) . ' invoice'" />
    <x-dash.bar label="Asuransi" :value="$line($split['asuransi'])" :pct="$split['asuransi']['pct']" :color="$color"
        :sub="Fmt::num($split['asuransi']['invoices']) . ' invoice'" />
    @if (!empty($split['insurers']))
        <div class="dw-bars dw-bars-nested">
            @foreach ($split['insurers'] as $insurer)
                <x-dash.bar :label="$insurer['label']" :value="$line($insurer)" :pct="$insurer['pct']"
                    :sub="Fmt::num($insurer['invoices']) . ' invoice'" />
            @endforeach
        </div>
    @endif
</div>
