{{-- One widget's content (inside its grid slot); also what DashboardController@widget returns --}}
@if ($widget->error || !$widget->view_exists)
    <x-dash.card :widget="$widget">
        <x-dash.empty icon="alert-triangle" title="Widget tidak bisa ditampilkan"
            :text="$widget->error ?: 'View widget tidak ditemukan: ' . $widget->component_path" />
    </x-dash.card>
@else
    @include($widget->resolved_view, ['widget' => $widget, 'dashboardFilter' => $dashboardFilter])
@endif
