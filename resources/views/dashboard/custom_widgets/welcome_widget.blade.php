<div class="card h-100 shadow-sm">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
                <h5 class="card-title mb-1">{{ $widget->widget_name ?? 'Welcome Widget' }}</h5>
                <p class="text-muted mb-0">Contoh widget sederhana untuk memastikan mapping dashboard berjalan.</p>
            </div>
            <span class="badge badge-primary">Sample</span>
        </div>

        <div class="alert alert-light border mb-0">
            <strong>Halo, {{ auth()->user()->name ?? 'User' }}</strong><br>
            Widget ini tampil karena posisi karyawan Anda sudah dipetakan ke widget yang aktif.
        </div>
    </div>
</div>