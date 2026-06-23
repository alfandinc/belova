@extends('layouts.admin.app')

@section('title', 'Admin Dashboard')

@section('navbar')
    @include('layouts.admin.navbar')
@endsection

@section('content')
<div class="container">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h2 class="mb-0">Admin Dashboard</h2>
        <div>
            <a href="{{ route('admin.users.create') ?? url('/admin/users/create') }}" class="btn btn-primary">Add New User</a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-3 mb-3">
            <div class="card text-white bg-primary h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title">Users</h5>
                            <h3 class="card-text">{{ $usersCount ?? '--' }}</h3>
                        </div>
                        <div>
                            <i data-feather="users" width="48" height="48"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent">
                    <a href="{{ route('admin.users.index') ?? url('/admin/users') }}" class="text-white">View all users &raquo;</a>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card text-white bg-success h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title">Roles</h5>
                            <h3 class="card-text">{{ $rolesCount ?? 0 }}</h3>
                        </div>
                        <div>
                            <i data-feather="shield" width="48" height="48"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent">
                    <a href="{{ route('admin.roles.index') ?? url('/admin/roles') }}" class="text-white">Manage roles &raquo;</a>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Activity (last 7 days)</h5>
                    <div id="admin-activity-chart"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Quick actions</h5>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('admin.users.create') ?? url('/admin/users/create') }}" class="btn btn-outline-primary">Create user</a>
                        <a href="{{ route('admin.roles.index') ?? url('/admin/roles') }}" class="btn btn-outline-success">Manage roles</a>
                        <a href="/admin/settings" class="btn btn-outline-secondary">Settings</a>
                    </div>
                    <hr />
                    @if(session('visit_import_status'))
                        <div class="alert alert-info mt-3">{{ session('visit_import_status') }}</div>
                    @endif
                    <form id="visit-import-form" action="{{ route('admin.visitations.import') }}" method="POST" enctype="multipart/form-data" class="mt-3">
                        @csrf
                        <div class="row g-2 align-items-center">
                            <div class="col-auto">
                                <input id="csv-file-input" type="file" name="csv_file" accept=".csv,text/csv" class="form-control" required>
                            </div>
                            <div class="col-auto">
                                <button id="preview-btn" type="submit" class="btn btn-primary">Preview & Import</button>
                            </div>
                            <div class="col-12 mt-2">
                                <small class="text-muted">CSV columns: <strong>id, tanggal_visit (dd/mm/YYYY), klinik_id, status_kunjungan, jenis_kunjungan</strong>. First row may be header.</small>
                            </div>
                        </div>
                    </form>

                    <!-- Preview Modal -->
                    <div class="modal fade" id="visitPreviewModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-xl modal-dialog-scrollable">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">CSV Preview</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div id="preview-loading" class="text-center my-3" style="display:none;">
                                        <div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div>
                                    </div>
                                    <div id="preview-error" class="alert alert-danger" style="display:none;"></div>
                                    <div id="preview-table-wrapper" style="display:none;">
                                        <p class="text-muted small">Showing up to 50 rows.</p>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-striped" id="preview-table">
                                                <thead></thead>
                                                <tbody></tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button id="confirm-import-btn" type="button" class="btn btn-primary">Import now</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function(){
        try {
            // Fetch activity data from server
            fetch("{{ route('admin.activity.data') }}")
                .then(function(resp){ return resp.json(); })
                .then(function(json){
                    var labels = json.labels && json.labels.length ? json.labels : ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
                    var data = json.data || [0,0,0,0,0,0,0];

                    var options = {
                        chart: { type: 'area', height: 240, toolbar: { show: false } },
                        series: [{ name: 'Signups', data: data }],
                        xaxis: { categories: labels },
                        colors: ['#556ee6'],
                        dataLabels: { enabled: false }
                    };

                    var chartEl = document.querySelector('#admin-activity-chart');
                    if (chartEl && typeof ApexCharts !== 'undefined') {
                        var chart = new ApexCharts(chartEl, options);
                        chart.render();
                    }
                })
                .catch(function(err){
                    console.warn('Failed to load activity data', err);
                });
        } catch (e) {
            console.warn('Chart rendering error', e);
        }
    });
</script>
<script>
document.addEventListener('DOMContentLoaded', function(){
    var form = document.getElementById('visit-import-form');
    var fileInput = document.getElementById('csv-file-input');
    var previewModalEl = document.getElementById('visitPreviewModal');
    var previewModal = previewModalEl ? new bootstrap.Modal(previewModalEl) : null;
    var previewTable = document.querySelector('#preview-table');
    var previewWrapper = document.getElementById('preview-table-wrapper');
    var previewError = document.getElementById('preview-error');
    var previewLoading = document.getElementById('preview-loading');
    var confirmBtn = document.getElementById('confirm-import-btn');
    var previewBtn = document.getElementById('preview-btn');

    function setLoading(state, btn){
        if (!btn) return;
        if (state) {
            btn.disabled = true;
            btn.dataset.orig = btn.innerHTML;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';
        } else {
            btn.disabled = false;
            if (btn.dataset.orig) btn.innerHTML = btn.dataset.orig;
        }
    }

    function escapeHtml(s){
        if (s === null || s === undefined) return '';
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    form.addEventListener('submit', function(e){
        e.preventDefault();
        if (!fileInput.files || fileInput.files.length === 0) return alert('Please choose a CSV file');
        var file = fileInput.files[0];
        var fd = new FormData();
        fd.append('_token', document.querySelector('input[name="_token"]').value);
        fd.append('csv_file', file);

        previewError.style.display = 'none';
        previewWrapper.style.display = 'none';
        previewLoading.style.display = 'block';
        setLoading(true, previewBtn);

        fetch("{{ route('admin.visitations.preview') }}", {
            method: 'POST',
            body: fd,
            credentials: 'same-origin'
        }).then(function(resp){
            return resp.json();
        }).then(function(json){
            previewLoading.style.display = 'none';
            setLoading(false, previewBtn);
            if (json.error) {
                previewError.textContent = json.error;
                previewError.style.display = 'block';
                previewModal.show();
                return;
            }

            // build table
            var thead = previewTable.querySelector('thead');
            var tbody = previewTable.querySelector('tbody');
            thead.innerHTML = '';
            tbody.innerHTML = '';

            var headers = json.headers && json.headers.length ? json.headers : ['id','tanggal_visit','klinik_id','status_kunjungan','jenis_kunjungan'];
            var tr = document.createElement('tr');
            headers.forEach(function(h){ var th = document.createElement('th'); th.textContent = h; tr.appendChild(th); });
            thead.appendChild(tr);

            json.rows.forEach(function(row){
                var tr = document.createElement('tr');
                headers.forEach(function(h){
                    var td = document.createElement('td');
                    td.innerHTML = escapeHtml(row[h] ?? '');
                    tr.appendChild(td);
                });
                tbody.appendChild(tr);
            });

            previewWrapper.style.display = 'block';
            previewModal.show();
        }).catch(function(err){
            previewLoading.style.display = 'none';
            setLoading(false, previewBtn);
            previewError.textContent = 'Failed to preview file: '+(err.message||err);
            previewError.style.display = 'block';
            previewModal.show();
        });
    });

    confirmBtn.addEventListener('click', function(){
        if (!fileInput.files || fileInput.files.length === 0) return alert('No file selected');
        var file = fileInput.files[0];
        var fd = new FormData();
        fd.append('_token', document.querySelector('input[name="_token"]').value);
        fd.append('csv_file', file);

        setLoading(true, confirmBtn);
        fetch("{{ route('admin.visitations.import') }}", {
            method: 'POST',
            body: fd,
            credentials: 'same-origin'
        }).then(function(resp){
            return resp.text();
        }).then(function(text){
            setLoading(false, confirmBtn);
            // server redirects back with flash message; reload page to show it
            window.location.reload();
        }).catch(function(err){
            setLoading(false, confirmBtn);
            previewError.textContent = 'Import failed: '+(err.message||err);
            previewError.style.display = 'block';
        });
    });
});
</script>
@endsection
