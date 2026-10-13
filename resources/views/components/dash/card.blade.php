{{-- Shell of every dashboard widget: header (icon, title, subtitle, optional actions), body, footer link. --}}
@props(['widget', 'subtitle' => null, 'href' => null, 'hrefLabel' => 'Lihat semua'])

<section {{ $attributes->merge(['class' => 'dw']) }} aria-label="{{ $widget->widget_name }}">
    <header class="dw-head">
        <div class="dw-head-main">
            <span class="dw-icon" aria-hidden="true"><i data-feather="{{ $widget->icon ?? 'grid' }}"></i></span>
            <div class="dw-head-text">
                <h3 class="dw-title">{{ $widget->widget_name }}</h3>
                @if ($subtitle)
                    <div class="dw-sub">{{ $subtitle }}</div>
                @endif
            </div>
        </div>
        @isset($actions)
            <div class="dw-actions">{{ $actions }}</div>
        @endisset
    </header>

    <div class="dw-body">
        {{ $slot }}
    </div>

    @if ($href || isset($footer))
        <footer class="dw-foot">
            <span class="dw-foot-note">{{ $footer ?? '' }}</span>
            @if ($href)
                <a href="{{ $href }}" class="dw-link">{{ $hrefLabel }} <i data-feather="arrow-right"></i></a>
            @endif
        </footer>
    @endif
</section>
