{{--
    One clinic's section inside a widget: logo (or a colored initial), name and the clinic's color as a
    stripe, so each clinic's numbers stay together. $klinik from App\Support\Dashboard\Clinics.
--}}
@props(['klinik', 'meta' => null])

<section {{ $attributes->merge(['class' => 'dw-clinic']) }} style="--dw-clinic: {{ $klinik->color }};" aria-label="{{ $klinik->nama }}">
    <header class="dw-clinic-head">
        @if ($klinik->logo)
            <img src="{{ $klinik->logo }}" alt="" class="dw-clinic-logo" loading="lazy">
        @else
            <span class="dw-clinic-logo is-initial" aria-hidden="true">{{ mb_substr($klinik->short, 0, 1) }}</span>
        @endif
        <span class="dw-clinic-name" title="{{ $klinik->nama }}">{{ $klinik->short }}</span>
        @if ($meta)
            <span class="dw-clinic-meta">{{ $meta }}</span>
        @endif
        @isset($aside)
            <span class="dw-clinic-aside">{{ $aside }}</span>
        @endisset
    </header>
    <div class="dw-clinic-body">
        {{ $slot }}
    </div>
</section>
