<div class="card h-100 shadow-sm dashboard-widget-card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
                <h5 class="card-title mb-1">{{ $widget->widget_name ?? 'Stats Overview' }}</h5>
                <p class="text-muted mb-0">Dummy statistik untuk uji tampilan widget multi-column.</p>
            </div>
            <span class="badge badge-success">Overview</span>
        </div>

        <div class="row text-center">
            <div class="col-4">
                <div class="border rounded p-3">
                    <div class="h4 mb-1">12</div>
                    <div class="text-muted small">Task</div>
                </div>
            </div>
            <div class="col-4">
                <div class="border rounded p-3">
                    <div class="h4 mb-1">5</div>
                    <div class="text-muted small">Alert</div>
                </div>
            </div>
            <div class="col-4">
                <div class="border rounded p-3">
                    <div class="h4 mb-1">98%</div>
                    <div class="text-muted small">Uptime</div>
                </div>
            </div>
        </div>
    </div>
</div>