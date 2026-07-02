<div class="card h-100 shadow-sm dashboard-widget-card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
                <h5 class="card-title mb-1">{{ $widget->widget_name ?? 'Quick Links' }}</h5>
                <p class="text-muted mb-0">Contoh widget link cepat ke modul utama.</p>
            </div>
            <span class="badge badge-info">Links</span>
        </div>

        <div class="list-group list-group-flush">
            <a href="{{ route('dashboard.widgets.index') }}" class="list-group-item list-group-item-action px-0">
                Kelola Dashboard Widgets
            </a>
            <a href="{{ route('dashboard.index') }}" class="list-group-item list-group-item-action px-0">
                Kembali ke Dashboard
            </a>
            <a href="/" class="list-group-item list-group-item-action px-0">
                Main Menu
            </a>
        </div>
    </div>
</div>