@if (! $employeePosition)
    <div class="alert alert-warning">
        Dashboard belum bisa ditampilkan karena akun ini belum memiliki posisi utama karyawan.
    </div>
@elseif ($dashboardWidgets->isEmpty())
    <div class="alert alert-info">
        Belum ada widget yang dipetakan untuk posisi {{ $employeePosition->name }}.
    </div>
@else
    @foreach ($dashboardWidgets->groupBy(function ($widget) { return $widget->row_index ?? 1; })->sortKeys() as $rowWidgets)
        <div class="row">
            @foreach ($rowWidgets as $widget)
                <div class="col-lg-{{ $widget->column_span }} col-md-12 mb-4">
                    @if ($widget->view_exists)
                        @include($widget->resolved_view, ['widget' => $widget, 'dashboardFilter' => $dashboardFilter])
                    @else
                        <div class="card h-100 border-warning dashboard-widget-card">
                            <div class="card-body">
                                <h5 class="card-title mb-2">{{ $widget->widget_name }}</h5>
                                <p class="text-muted mb-2">{{ $widget->description ?: 'Widget belum memiliki deskripsi.' }}</p>
                                <div class="small text-warning">
                                    View widget tidak ditemukan: {{ $widget->component_path }}
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endforeach
@endif