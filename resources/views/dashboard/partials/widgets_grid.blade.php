@if ($dashboardWidgets->isEmpty())
    <div class="dsh-empty dsh-widgets-empty">
        <div class="dsh-empty-icon"><i data-feather="grid"></i></div>
        <div class="dsh-empty-title">Belum ada widget</div>
        <p class="dsh-empty-text">Role Anda belum diberi akses ke widget dashboard mana pun.</p>
    </div>
@else
    {{-- Widgets flow left to right in config order and wrap by their column span. A widget with a data
         class starts as a skeleton and is filled from dashboard.widget; one without (e.g. Buku Menu)
         renders right away. --}}
    <div class="row">
        @foreach ($dashboardWidgets as $widget)
            <div class="col-xl-{{ $widget->column_span }} col-12 mb-4"
                 data-widget-slot="{{ $widget->key }}"
                 data-uses-period="{{ $widget->uses_period ? 1 : 0 }}"
                 @if ($widget->class) data-widget-url="{{ route('dashboard.widget', $widget->key) }}" @endif>
                @if ($widget->class)
                    <x-dash.card :widget="$widget" class="dw-skeleton" aria-busy="true">
                        <div class="dw-skel-line" style="width: 40%"></div>
                        <div class="dw-skel-block"></div>
                        <div class="dw-skel-line" style="width: 85%"></div>
                        <div class="dw-skel-line" style="width: 65%"></div>
                    </x-dash.card>
                @else
                    @include('dashboard.partials.widget', ['widget' => $widget, 'dashboardFilter' => $dashboardFilter])
                @endif
            </div>
        @endforeach
    </div>
@endif
