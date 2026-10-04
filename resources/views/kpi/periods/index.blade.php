@extends('layouts.hrd.app')

@section('title', 'KPI | Periods')

@section('navbar')
    @include('layouts.kpi.navbar')
@endsection

@section('content')
<div class="container-fluid px-2">
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-1">KPI Periods</h4>
                <div class="text-muted small">Manage KPI assessment periods</div>
            </div>
            <div>
                <button id="btnAddPeriod" class="btn btn-primary btn-sm">Create Period</button>
            </div>
        </div>
    </div>

    <style>
        #periodsTable tbody tr.period-selected td { background-color: rgba(0, 123, 255, .08); }
        .kpi-stat { border: 1px solid rgba(0, 0, 0, .08); border-radius: 6px; padding: 8px 12px; height: 100%; }
        .kpi-stat .kpi-stat-value { font-size: 1.25rem; font-weight: 600; line-height: 1.2; }
        .kpi-stat .kpi-stat-label { font-size: .75rem; color: #8a94a6; }
        .kpi-progress { height: 6px; }
        #detailsTable tbody tr { cursor: pointer; }
        #detailsTable td { vertical-align: middle; }
        .kpi-eval-card .card-header { padding: .6rem .75rem; }
        .kpi-eval-card .kpi-caret { display: inline-block; width: 14px; color: #8a94a6; transition: transform .15s; }
        .kpi-eval-card .card-header.collapsed .kpi-caret { transform: rotate(-90deg); }
        .kpi-score-table { width: 100%; margin: 0; table-layout: fixed; border-collapse: collapse; }
        .kpi-score-table th, .kpi-score-table td { padding: .45rem .6rem; vertical-align: middle; border-top: 1px solid rgba(0, 0, 0, .06); }
        .kpi-score-table thead th { border-top: 0; font-size: .72rem; font-weight: 500; color: #8a94a6; text-transform: uppercase; letter-spacing: .03em; }
        .kpi-score-table .kpi-num { text-align: right; }
        .kpi-score-table .kpi-mid { text-align: center; }
        .kpi-score-table tr.kpi-category-row td { background: rgba(0, 0, 0, .035); font-weight: 600; }
        .kpi-score-table .kpi-sub { font-size: .75rem; color: #8a94a6; font-weight: 400; }
        .kpi-score-table .kpi-note { font-size: .78rem; color: #6c757d; font-style: italic; margin-top: 2px; }
        .kpi-score-pill { display: inline-block; min-width: 54px; padding: 1px 8px; border-radius: 10px; background: rgba(0, 0, 0, .06); font-weight: 600; }
        .kpi-score-pill small { font-weight: 400; color: #8a94a6; }
    </style>

    <div class="row">
        <div class="col-lg-5">
            <div class="card">
                <div class="card-body p-2">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped w-100" id="periodsTable">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Name</th>
                                    <th>Period</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h5 class="mb-0" id="detailsTitle">Period Details</h5>
                            <div class="small text-muted" id="detailsSubtitle">Klik <strong>Scores</strong> pada periode di kiri untuk melihat hasil penilaian.</div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary d-none" id="btnRefreshDetails" title="Muat ulang"><i class="fas fa-sync-alt"></i></button>
                    </div>

                    <div id="detailsBody" class="d-none">
                        <div class="row mb-3" id="detailsStats"></div>

                        <div class="d-flex flex-wrap align-items-center mb-2">
                            <input type="search" id="detailsSearch" class="form-control form-control-sm mr-2 mb-1" style="max-width:240px" placeholder="Cari karyawan / posisi...">
                            <div class="btn-group btn-group-sm mb-1" role="group" id="detailsFilter">
                                <button type="button" class="btn btn-outline-secondary active" data-filter="all">Semua <span class="badge badge-light" data-count="all"></span></button>
                                <button type="button" class="btn btn-outline-secondary" data-filter="pending">Belum selesai <span class="badge badge-light" data-count="pending"></span></button>
                                <button type="button" class="btn btn-outline-secondary" data-filter="done">Selesai <span class="badge badge-light" data-count="done"></span></button>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0" id="detailsTable">
                                <thead>
                                    <tr>
                                        <th>Karyawan</th>
                                        <th style="width:170px">Progress</th>
                                        <th class="text-right" style="width:110px">Skor</th>
                                        <th style="width:40px"></th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>

                    <div id="detailsMessage"></div>
                </div>
            </div>
        </div>
    </div>
</div>

            <!-- Preview Modal (wider, two-column) -->
            <div class="modal fade" id="previewModal" tabindex="-1" role="dialog" aria-labelledby="previewModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="previewModalLabel">Preview Assessments to Generate</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        </div>
                        <div class="modal-body">
                            <div id="previewCounts" class="mb-2"></div>
                            <div id="previewWarnings"></div>
                            <div class="row">
                                <div class="col-md-4 border-right" style="max-height:60vh; overflow:auto;">
                                    <h6>Evaluators</h6>
                                    <div class="list-group" id="evaluatorList"></div>
                                </div>
                                <div class="col-md-8" style="max-height:60vh; overflow:auto;">
                                    <div id="previewSummary" class="mb-2"></div>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered" id="evaluatorIndicatorsTable">
                                            <thead>
                                                        <tr>
                                                            <th>Evaluatee</th>
                                                            <th>Category</th>
                                                            <th>Indicator</th>
                                                        </tr>
                                            </thead>
                                            <tbody></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="button" id="confirmStartBtn" class="btn btn-primary">Confirm & Generate</button>
                        </div>
                    </div>
                </div>
            </div>

<!-- Modal -->
<div class="modal fade" id="periodModal" tabindex="-1" role="dialog" aria-labelledby="periodModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="periodForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="periodModalLabel">Create KPI Period</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="period_id">
                    @php
                        $monthNames = ['January','February','March','April','May','June','July','August','September','October','November','December'];
                    @endphp
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-0">
                                <label>Month</label>
                                <select id="period_month" name="month" class="form-control form-control-sm">
                                    @foreach($monthNames as $i => $name)
                                        @php $val = $i + 1; @endphp
                                        <option value="{{ $val }}" {{ $val == date('n') ? 'selected' : '' }}>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-0">
                                <label>Year</label>
                                <input type="number" id="period_year" name="year" class="form-control form-control-sm" value="{{ date('Y') }}">
                            </div>
                        </div>
                    </div>
                    <div class="form-group mt-2">
                        <label>Period Name</label>
                        <input type="text" id="period_name" name="period_name" class="form-control form-control-sm" required placeholder="e.g. Mid 2026 or Custom Label">
                    </div>
                    <!-- Only month and year are required for create; status/timestamps default server-side -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(function(){
    var periodsTable = $('#periodsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('kpi.periods.data') }}",
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'name', name: 'name' },
            { data: 'period', name: 'period' },
            { data: 'status', name: 'status' },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ]
    });

    $('#btnAddPeriod').on('click', function(){
        $('#periodForm')[0].reset();
        $('#period_id').val('');
        $('#periodModalLabel').text('Create KPI Period');
        // set default month/year to current
        $('#period_month').val(new Date().getMonth() + 1);
        $('#period_year').val(new Date().getFullYear());
        // clear period_name so user must enter it
        $('#period_name').val('').removeData('generated');
        $('#periodModal').modal('show');
    });
    // Don't auto-change period_name when month/year change; user must provide period_name (required)

    $('#periodForm').on('submit', function(e){
        e.preventDefault();
        var id = $('#period_id').val();
        var url = id ? ('/kpi/periods/' + id) : '{{ route('kpi.periods.store') }}';
        var method = id ? 'PUT' : 'POST';
        var data = $(this).serialize();
        if (method === 'PUT') data += '&_method=PUT';

        $.ajax({
            url: url,
            type: 'POST',
            data: data,
            success: function(res){
                $('#periodModal').modal('hide');
                periodsTable.ajax.reload(null, false);
                Swal.fire({icon:'success', title:'Saved', text: res.message});
            },
            error: function(xhr){ showAjaxError(xhr, 'Failed to save period'); }
        });
    });

    $(document).on('click', '.btn-edit-period', function(){
        var id = $(this).data('id');
        $.ajax({
            url: '/kpi/periods/' + id,
            type: 'GET',
            success: function(res){
                if (!res.success) return showAjaxError({ responseJSON: res }, 'Failed to load period');
                var p = res.data;
                $('#period_id').val(p.id);
                $('#period_month').val(p.month);
                $('#period_year').val(p.year);
                $('#period_name').val(p.period_name || '');
                $('#periodModalLabel').text('Edit KPI Period');
                $('#periodModal').modal('show');
            },
            error: function(xhr){ showAjaxError(xhr, 'Failed to load period'); }
        });
    });

    $(document).on('click', '.btn-delete-period', function(){
        var id = $(this).data('id');
        Swal.fire({icon:'warning', title:'Delete period?', showCancelButton:true}).then(function(res){
            if (!res.value) return;
            $.ajax({
                url: '/kpi/periods/' + id,
                type: 'POST',
                data: { _method: 'DELETE', _token: $('meta[name="csrf-token"]').attr('content') },
                success: function(r){ periodsTable.ajax.reload(null,false); Swal.fire({icon:'success', title:'Deleted', text: r.message}); },
                error: function(xhr){ showAjaxError(xhr, 'Failed to delete'); }
            });
        });
    });

    // Start assessment -> preview flow (two-column modal)
    var previewPeriodId = null;
    var previewData = [];
    function fmtPct(n){
        if (n === undefined || n === null || n === '') return '';
        var v = parseFloat(n);
        if (isNaN(v)) return n;
        return (Math.round(v * 100) / 100) + '%';
    }

    function renderStars(score){
        if (score === undefined || score === null || score === '') return '-';
        var s = parseInt(score,10);
        if (isNaN(s) || s <= 0) return '-';
        s = Math.max(0, Math.min(5, s));
        var out = '';
        for (var i=1;i<=5;i++){
            if (i<=s) out += '<span class="text-warning">&#9733;</span>'; else out += '<span class="text-muted">&#9734;</span>';
        }
        return out;
    }

    $(document).on('click', '.btn-start-period', function(){
        var id = $(this).data('id');
        previewPeriodId = id;
        // fetch preview
        $.get('/kpi/periods/' + id + '/start/preview', function(res){
            if (!res.success) return showAjaxError({ responseJSON: res }, 'Failed to preview');
            previewData = res.data || [];
            var counts = res.counts || {};
            var warnings = res.warnings || [];

            $('#previewCounts').html(
                '<span class="badge badge-primary mr-1">' + (counts.evaluatees || 0) + ' karyawan dinilai</span>'
                + '<span class="badge badge-primary mr-1">' + (counts.assessments || 0) + ' assessment</span>'
                + '<span class="badge badge-primary">' + (counts.proposals || 0) + ' indikator penilaian</span>'
            );

            if (warnings.length) {
                var warningHtml = '<div class="alert alert-warning py-2 small">'
                    + '<strong><i class="fas fa-exclamation-triangle mr-1"></i>' + warnings.length + ' hal perlu dicek sebelum mulai:</strong>'
                    + '<ul class="mb-0 pl-3 mt-1" style="max-height:140px; overflow:auto;">';
                warnings.forEach(function(w){
                    warningHtml += '<li>' + escapeHtml(w.message) + (w.count > 1 ? ' <span class="badge badge-light">' + w.count + ' karyawan</span>' : '') + '</li>';
                });
                warningHtml += '</ul></div>';
                $('#previewWarnings').html(warningHtml);
            } else {
                $('#previewWarnings').html('<div class="alert alert-success py-2 small"><i class="fas fa-check-circle mr-1"></i>Semua indikator punya penilai dan bobotnya 100%.</div>');
            }

            $('#confirmStartBtn').prop('disabled', previewData.length === 0 || res.status !== 'draft');
            $('#previewSummary').empty();

            // build unique evaluators list
            var evaluatorsMap = {};
            previewData.forEach(function(r){
                var key = r.evaluator_employee_id ? ('emp_' + r.evaluator_employee_id) : ('pos_' + r.evaluator_position_id);
                if (!evaluatorsMap[key]) {
                    evaluatorsMap[key] = {
                        key: key,
                        evaluator_employee_id: r.evaluator_employee_id,
                        evaluator_employee_name: r.evaluator_employee_name || r.evaluator_position_name || ('Position ' + r.evaluator_position_id),
                        evaluator_position_id: r.evaluator_position_id,
                        evaluator_position_name: r.evaluator_position_name || (r.evaluator_position_id ? ('Position ' + r.evaluator_position_id) : ''),
                        count: 0
                    };
                }
                evaluatorsMap[key].count++;
            });

            var $list = $('#evaluatorList').empty();
            Object.keys(evaluatorsMap).forEach(function(k){
                var ev = evaluatorsMap[k];
                var $item = $('<div class="list-group-item d-flex justify-content-between align-items-center" style="cursor:pointer;"></div>');
                $item.attr('data-key', ev.key);
                $item.append('<div><strong>' + escapeHtml(ev.evaluator_employee_name || '-') + '</strong><div class="small text-muted">' + escapeHtml(ev.evaluator_position_name ? ev.evaluator_position_name : (ev.evaluator_position_id ? ('Pos ID: ' + ev.evaluator_position_id) : '')) + '</div></div>');
                $item.append('<div><button class="btn btn-sm btn-outline-primary btn-evaluator-detail" data-key="'+ev.key+'">Detail ('+ev.count+')</button></div>');
                $list.append($item);
            });

            // auto-select first
            $('#evaluatorList .list-group-item').first().trigger('click');
            $('#previewModal').modal('show');
        }).fail(function(xhr){ showAjaxError(xhr, 'Failed to load preview'); });
    });

    // show indicators for selected evaluator
    $(document).on('click', '#evaluatorList .list-group-item, .btn-evaluator-detail', function(e){
        var key = $(this).data('key') || $(this).closest('.list-group-item').data('key');
        if (!key) return;
        $('#evaluatorList .list-group-item').removeClass('active');
        $('#evaluatorList .list-group-item[data-key="'+key+'"]').addClass('active');

        var rows = previewData.filter(function(r){
            var k = r.evaluator_employee_id ? ('emp_' + r.evaluator_employee_id) : ('pos_' + r.evaluator_position_id);
            return k === key;
        });

        var $tbody = $('#evaluatorIndicatorsTable tbody').empty();
        // group by evaluatee + position so multi-position employees show separately
        var byEvaluatee = {};
        rows.forEach(function(row){
            var k = row.evaluatee_id + '::' + (row.evaluatee_position_id || '') + '::' + (row.evaluatee_name || '');
            byEvaluatee[k] = byEvaluatee[k] || [];
            byEvaluatee[k].push(row);
        });
        Object.keys(byEvaluatee).forEach(function(k){
            var group = byEvaluatee[k];
            // group by category to preserve category ordering
            var cats = {};
            var catOrder = [];
            group.forEach(function(r){
                var cat = r.category_name || 'Uncategorized';
                if (!cats[cat]) { cats[cat] = []; catOrder.push(cat); }
                cats[cat].push(r);
            });

            var totalRows = group.length;
            var firstEval = true;
            // precompute category weights and indicator totals
            var catMeta = {};
            catOrder.forEach(function(c){
                var items = cats[c];
                var catWeight = items[0] && items[0].category_weight !== undefined ? items[0].category_weight : 0;
                var totalIndicatorWeight = 0;
                items.forEach(function(it){ totalIndicatorWeight += (it.indicator_weight !== undefined ? parseFloat(it.indicator_weight) : 0); });
                catMeta[c] = { catWeight: catWeight, totalIndicatorWeight: totalIndicatorWeight };
            });

            // iterate categories and their items, render category once with rowspan and show weights
            catOrder.forEach(function(c){
                var items = cats[c];
                items.forEach(function(row, idxInCat){
                    var rowHtml = '<tr>';
                    if (firstEval) {
                        rowHtml += '<td rowspan="'+totalRows+'"><div>' + escapeHtml(row.evaluatee_name || '') + '</div><div class="small text-muted">' + escapeHtml(row.evaluatee_position_name || '-') + '</div></td>';
                        firstEval = false;
                    }
                    if (idxInCat === 0) {
                        var meta = catMeta[c] || { catWeight: 0, totalIndicatorWeight: 0 };
                        var indOk = Math.abs(meta.totalIndicatorWeight - 100) <= 0.001;
                        rowHtml += '<td rowspan="'+items.length+'"><strong>' + escapeHtml(c) + '</strong>'
                            + '<div class="small text-muted">Bobot kategori: ' + fmtPct(meta.catWeight) + '</div>'
                            + '<div class="small text-muted">Total indikator: <span class="' + (indOk ? 'text-success' : 'text-danger') + '">' + fmtPct(meta.totalIndicatorWeight) + '</span></div></td>';
                    }
                    rowHtml += '<td>' + escapeHtml(row.indicator_name || '') + ' <span class="small text-muted">(' + (row.indicator_weight !== undefined ? fmtPct(row.indicator_weight) : '') + ')</span></td>';
                    rowHtml += '</tr>';
                    $tbody.append(rowHtml);
                });
            });
        });
    });

    $('#confirmStartBtn').on('click', function(){
        if (!previewPeriodId) return;
        var $btn = $(this);
        var originalText = $btn.text();
        // disabled while generating, so a double click cannot start the period twice
        $btn.prop('disabled', true).text('Generating...');
        $.ajax({
            url: '/kpi/periods/' + previewPeriodId + '/start',
            type: 'POST',
            data: { _token: $('meta[name="csrf-token"]').attr('content') },
            success: function(r){
                $('#previewModal').modal('hide');
                periodsTable.ajax.reload(null,false);
                Swal.fire({icon:'success', title:'Started', text: r.message});
            },
            error: function(xhr){
                $btn.prop('disabled', false);
                showAjaxError(xhr, 'Failed to start assessment');
            },
            complete: function(){
                $btn.text(originalText);
            }
        });
    });

    // open period
    $(document).on('click', '.btn-open-period', function(){
        var id = $(this).data('id');
        if (!id) return;
        $.ajax({ url: '/kpi/periods/' + id + '/open', type: 'POST', data: { _token: $('meta[name="csrf-token"]').attr('content') }, success: function(r){ periodsTable.ajax.reload(null,false); Swal.fire({icon:'success', title:'Opened', text: r.message}); }, error: function(xhr){ showAjaxError(xhr, 'Failed to open period'); } });
    });

    var periodDetailsData = [];
    var currentPeriodDetailsId = null;
    var activeEvaluateeRowKey = null;
    var detailsFilter = 'all';
    var canManageEvaluationScores = @json((bool) (auth()->user()?->hasAnyRole(['Hrd', 'Admin']) ?? false));

    var assessmentTypeLabels = {
        direct_parent: { label: 'Atasan langsung', badge: 'badge-primary', order: 1 },
        specific_position: { label: 'Penilai khusus', badge: 'badge-success', order: 2 },
        bottom_up: { label: 'Bawahan', badge: 'badge-warning', order: 3 }
    };

    function escapeHtml(text) {
        if (text === null || text === undefined) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function fmtNum(n) {
        if (n === null || n === undefined || n === '') return '-';
        var v = Number(n);
        return isFinite(v) ? String(Math.round(v * 100) / 100) : String(n);
    }

    function rowState(row) {
        var done = parseInt(row.done_count || 0, 10);
        var total = parseInt(row.total_count || 0, 10);
        if (total > 0 && done === total) return 'done';
        return done === 0 ? 'none' : 'partial';
    }

    function progressHtml(done, total) {
        var pct = total > 0 ? Math.round(done / total * 100) : 0;
        var barClass = done === 0 ? 'bg-secondary' : (done === total ? 'bg-success' : 'bg-warning');
        return '<div class="d-flex align-items-center">'
            + '<div class="progress kpi-progress flex-grow-1 mr-2"><div class="progress-bar ' + barClass + '" style="width:' + pct + '%"></div></div>'
            + '<span class="small text-nowrap">' + done + '/' + total + '</span>'
            + '</div>';
    }

    function scoreHtml(row) {
        var state = rowState(row);
        if (state === 'none') return '<span class="text-muted">-</span>';
        var value = '<strong>' + fmtNum(row.total_score) + '</strong>';
        return state === 'partial' ? value + '<div class="small text-muted">sementara</div>' : value;
    }

    function statTile(value, label, extra) {
        return '<div class="col-4"><div class="kpi-stat"><div class="kpi-stat-value">' + value + '</div>'
            + '<div class="kpi-stat-label">' + label + '</div>' + (extra || '') + '</div></div>';
    }

    function renderDetailsSummary(period) {
        var rows = periodDetailsData;
        var done = 0, total = 0, completed = [], counts = { all: rows.length, pending: 0, done: 0 };

        rows.forEach(function (r) {
            done += parseInt(r.done_count || 0, 10);
            total += parseInt(r.total_count || 0, 10);
            if (rowState(r) === 'done') {
                counts.done++;
                completed.push(Number(r.total_score) || 0);
            } else {
                counts.pending++;
            }
        });

        var average = completed.length ? fmtNum(completed.reduce(function (a, b) { return a + b; }, 0) / completed.length) : '-';
        var statusClass = { draft: 'secondary', started: 'info', open: 'success', closed: 'danger' }[period.status] || 'secondary';

        $('#detailsTitle').html(escapeHtml(period.name || 'Period') + ' <span class="badge badge-' + statusClass + ' align-middle">' + escapeHtml(period.status || '') + '</span>');
        $('#detailsSubtitle').text(period.label || '');
        $('#detailsStats').html(
            statTile(rows.length, 'Karyawan dinilai')
            + statTile(done + '/' + total, 'Penilaian selesai', '<div class="progress kpi-progress mt-1"><div class="progress-bar bg-success" style="width:' + (total ? Math.round(done / total * 100) : 0) + '%"></div></div>')
            + statTile(average, 'Rata-rata skor (' + completed.length + ' selesai)')
        );

        $.each(counts, function (key, value) {
            $('#detailsFilter [data-count="' + key + '"]').text(value);
        });
    }

    function renderDetailsTable() {
        var term = ($('#detailsSearch').val() || '').trim().toLowerCase();
        var html = '';

        periodDetailsData.forEach(function (r) {
            var state = rowState(r);
            if (detailsFilter === 'done' && state !== 'done') return;
            if (detailsFilter === 'pending' && state === 'done') return;

            var positions = r.evaluatee_positions || [];
            if (term && ((r.evaluatee_name || '') + ' ' + positions.join(' ')).toLowerCase().indexOf(term) === -1) return;

            html += '<tr class="btn-evaluatee-details" data-row-key="' + escapeHtml(r.row_key) + '">'
                + '<td><div class="font-weight-bold">' + escapeHtml(r.evaluatee_name || '-') + '</div>'
                + '<div class="small text-muted">' + positions.map(escapeHtml).join(' &middot; ') + '</div></td>'
                + '<td>' + progressHtml(parseInt(r.done_count || 0, 10), parseInt(r.total_count || 0, 10)) + '</td>'
                + '<td class="text-right">' + scoreHtml(r) + '</td>'
                + '<td class="text-right text-muted"><i class="fas fa-chevron-right"></i></td>'
                + '</tr>';
        });

        $('#detailsTable tbody').html(html || '<tr><td colspan="4" class="text-center text-muted py-3">Tidak ada karyawan yang cocok.</td></tr>');
    }

    function loadPeriodDetails(id, callback) {
        currentPeriodDetailsId = id;
        highlightSelectedPeriod();
        $('#btnRefreshDetails').removeClass('d-none').prop('disabled', true);
        $('#detailsMessage').html('<div class="text-center text-muted py-4"><span class="spinner-border spinner-border-sm mr-2"></span>Memuat...</div>');

        $.get('/kpi/periods/' + id + '/details', function (res) {
            if (!res.success) return showAjaxError({ responseJSON: res }, 'Failed to load details');
            periodDetailsData = res.data || [];
            renderDetailsSummary(res.period || {});

            if (!periodDetailsData.length) {
                $('#detailsBody').addClass('d-none');
                $('#detailsMessage').html('<div class="alert alert-info mb-0">Belum ada assessment untuk periode ini. Klik <strong>Start</strong> untuk membuatnya.</div>');
            } else {
                $('#detailsMessage').empty();
                $('#detailsBody').removeClass('d-none');
                renderDetailsTable();
            }

            if (typeof callback === 'function') callback();
        }).fail(function (xhr) {
            $('#detailsMessage').empty();
            showAjaxError(xhr, 'Failed to load details');
        }).always(function () {
            $('#btnRefreshDetails').prop('disabled', false);
        });
    }

    function highlightSelectedPeriod() {
        $('#periodsTable tbody tr').removeClass('period-selected');
        if (currentPeriodDetailsId) {
            $('#periodsTable .btn-scores-period[data-id="' + currentPeriodDetailsId + '"]').closest('tr').addClass('period-selected');
        }
    }

    periodsTable.on('draw', highlightSelectedPeriod);

    $(document).on('click', '.btn-scores-period', function () {
        var id = $(this).data('id');
        if (!id) return;
        $('#detailsSearch').val('');
        loadPeriodDetails(id);
    });

    $('#btnRefreshDetails').on('click', function () {
        if (currentPeriodDetailsId) loadPeriodDetails(currentPeriodDetailsId);
    });

    $('#detailsSearch').on('input', renderDetailsTable);

    $('#detailsFilter').on('click', 'button', function () {
        detailsFilter = $(this).data('filter');
        $('#detailsFilter button').removeClass('active');
        $(this).addClass('active');
        renderDetailsTable();
    });

    var evaluationsById = {};

    function hasScoreValue(a, s) {
        return a.status === 'done' && s.score !== null && s.score !== undefined && s.score !== '';
    }

    function groupScoresByCategory(scores) {
        var order = [];
        var groups = {};
        (scores || []).forEach(function (s) {
            var key = s.category_name || 'Uncategorized';
            if (!groups[key]) {
                groups[key] = { name: key, weight: Number(s.category_weight) || 0, items: [] };
                order.push(key);
            }
            groups[key].items.push(s);
        });
        // heaviest category first, then by name
        return order.map(function (k) { return groups[k]; }).sort(function (x, y) {
            return (y.weight - x.weight) || x.name.localeCompare(y.name);
        });
    }

    function categorySubtotal(a, group) {
        if (a.status !== 'done') return null;
        return group.items.reduce(function (sum, s) { return sum + (Number(s.final_calculated_score) || 0); }, 0);
    }

    function categoryHeaderRow(a, group, colspan) {
        var subtotal = categorySubtotal(a, group);
        return '<tr class="kpi-category-row">'
            + '<td colspan="' + (colspan - 1) + '">' + escapeHtml(group.name)
                + ' <span class="kpi-sub">&middot; bobot ' + fmtNum(group.weight) + '%' + (group.weight === 0 ? ' (tidak dihitung)' : '') + '</span></td>'
            + '<td class="kpi-num">' + (subtotal === null ? '' : fmtNum(subtotal)) + '</td>'
            + '</tr>';
    }

    function indicatorCellHtml(s, withNote) {
        return '<div class="text-break">' + escapeHtml(s.indicator_name || '') + ' <span class="kpi-sub">' + fmtNum(s.indicator_weight) + '%</span></div>'
            + (withNote && s.notes ? '<div class="kpi-note text-break">&ldquo;' + escapeHtml(s.notes) + '&rdquo;</div>' : '');
    }

    // read-only scores of one evaluation: indicator (weight + note) | nilai | skor
    function evaluationReadHtml(a) {
        var html = '<table class="kpi-score-table">'
            + '<colgroup><col><col style="width:110px"><col style="width:80px"></colgroup>'
            + '<thead><tr><th>Indikator</th><th class="kpi-mid">Nilai</th><th class="kpi-num">Skor</th></tr></thead><tbody>';

        groupScoresByCategory(a.scores).forEach(function (group) {
            html += categoryHeaderRow(a, group, 3);

            group.items.forEach(function (s) {
                var scored = hasScoreValue(a, s);
                html += '<tr>'
                    + '<td>' + indicatorCellHtml(s, true) + '</td>'
                    + '<td class="kpi-mid">' + (scored ? '<span class="kpi-score-pill">' + fmtNum(s.score) + ' <small>/ 5</small></span>' : '<span class="text-muted">&ndash;</span>') + '</td>'
                    + '<td class="kpi-num">' + (scored ? fmtNum(s.final_calculated_score) : '<span class="text-muted">&ndash;</span>') + '</td>'
                    + '</tr>';
            });
        });

        return html + '</tbody></table>';
    }

    // editable form of one evaluation (HRD / Admin): indicator | nilai input | skor | catatan input
    function evaluationEditHtml(a) {
        var html = '<form class="edit-evaluation-form" data-assessment-id="' + a.assessment_id + '">'
            + '<input type="hidden" name="_token" value="{{ csrf_token() }}">'
            + '<table class="kpi-score-table mb-2">'
            + '<colgroup><col><col style="width:110px"><col style="width:80px"><col style="width:34%"></colgroup>'
            + '<thead><tr><th>Indikator</th><th class="kpi-mid">Nilai (1-5)</th><th class="kpi-num">Skor</th><th>Catatan</th></tr></thead><tbody>';

        groupScoresByCategory(a.scores).forEach(function (group) {
            html += '<tr class="kpi-category-row"><td colspan="4">' + escapeHtml(group.name)
                + ' <span class="kpi-sub">&middot; bobot ' + fmtNum(group.weight) + '%</span></td></tr>';

            group.items.forEach(function (s) {
                var scored = hasScoreValue(a, s);
                html += '<tr>'
                    + '<td>' + indicatorCellHtml(s, false) + '</td>'
                    + '<td><input type="number" step="0.01" min="1" max="5" class="form-control form-control-sm text-center" name="scores[' + s.indicator_id + ']"'
                        + ' value="' + (scored ? escapeHtml(s.score) : '') + '" data-indicator-weight="' + escapeHtml(s.indicator_weight) + '" data-category-weight="' + escapeHtml(s.category_weight) + '" required></td>'
                    + '<td class="kpi-num weighted-score-value">' + (scored ? fmtNum(s.final_calculated_score) : '&ndash;') + '</td>'
                    + '<td><textarea class="form-control form-control-sm" name="notes[' + s.indicator_id + ']" rows="1" placeholder="Catatan (opsional)">' + escapeHtml(s.notes || '') + '</textarea></td>'
                    + '</tr>';
            });
        });

        return html + '</tbody></table>'
            + '<div class="text-right">'
            + '<button type="button" class="btn btn-light btn-sm mr-1 btn-cancel-edit-evaluation" data-assessment-id="' + a.assessment_id + '">Batal</button>'
            + '<button type="submit" class="btn btn-primary btn-sm">' + (a.status === 'done' ? 'Simpan Perubahan' : 'Simpan Nilai') + '</button>'
            + '</div></form>';
    }

    // per category: average each indicator over the evaluators who finished (per evaluatee position),
    // sum it into the category, then average over positions; the same formula as the final score
    function categorySummary(row) {
        var categories = {};
        var byPosition = {};

        (row.evaluations || []).forEach(function (a) {
            var posKey = a.evaluatee_position || '-';
            byPosition[posKey] = byPosition[posKey] || [];
            byPosition[posKey].push(a);

            (a.scores || []).forEach(function (s) {
                var name = s.category_name || 'Uncategorized';
                var c = categories[name] = categories[name] || { name: name, weight: Number(s.category_weight) || 0, evaluators: {}, rawSum: 0, rawCount: 0, contribution: 0 };
                c.evaluators[a.assessment_id] = { name: a.evaluator_name, done: a.status === 'done' };
                if (hasScoreValue(a, s)) {
                    c.rawSum += Number(s.score) || 0;
                    c.rawCount++;
                }
            });
        });

        var positionKeys = Object.keys(byPosition);
        positionKeys.forEach(function (posKey) {
            var perIndicator = {};
            byPosition[posKey].forEach(function (a) {
                if (a.status !== 'done') return;
                (a.scores || []).forEach(function (s) {
                    var key = (s.category_name || 'Uncategorized') + '::' + s.indicator_id;
                    perIndicator[key] = perIndicator[key] || { category: s.category_name || 'Uncategorized', values: [] };
                    perIndicator[key].values.push(Number(s.final_calculated_score) || 0);
                });
            });
            $.each(perIndicator, function (_, item) {
                var avg = item.values.reduce(function (x, y) { return x + y; }, 0) / item.values.length;
                categories[item.category].contribution += avg / positionKeys.length;
            });
        });

        return Object.keys(categories).map(function (k) { return categories[k]; }).sort(function (x, y) {
            return (y.weight - x.weight) || x.name.localeCompare(y.name);
        });
    }

    function categorySummaryHtml(row) {
        var html = '<table class="table table-sm table-bordered mb-3">'
            + '<thead class="small text-muted"><tr><th>Kategori</th><th class="text-right" style="width:70px">Bobot</th><th>Penilai</th>'
            + '<th class="text-center" style="width:110px">Rata-rata nilai</th><th class="text-right" style="width:90px">Kontribusi</th></tr></thead><tbody>';

        categorySummary(row).forEach(function (c) {
            var evaluators = $.map(c.evaluators, function (e) {
                return '<span class="mr-2 text-nowrap">' + (e.done ? '<i class="fas fa-check-circle text-success"></i>' : '<i class="far fa-clock text-warning"></i>')
                    + ' ' + escapeHtml(e.name || '-') + '</span>';
            }).join('');

            html += '<tr>'
                + '<td class="font-weight-bold">' + escapeHtml(c.name) + '</td>'
                + '<td class="text-right">' + fmtNum(c.weight) + '%</td>'
                + '<td class="small">' + evaluators + '</td>'
                + '<td class="text-center">' + (c.rawCount ? '<strong>' + fmtNum(c.rawSum / c.rawCount) + '</strong><span class="small text-muted"> / 5</span>' : '<span class="text-muted">-</span>') + '</td>'
                + '<td class="text-right">' + (c.rawCount ? '<strong>' + fmtNum(c.contribution) + '</strong>' : '<span class="text-muted">-</span>')
                + (c.weight === 0 ? '<div class="small text-muted">tidak dihitung</div>' : '') + '</td>'
                + '</tr>';
        });

        return html + '</tbody></table>';
    }

    function renderEvaluateeDetailsModal(rowKey) {
        var row = (periodDetailsData || []).find(function (r) { return String(r.row_key) === String(rowKey); });
        if (!row) return Swal.fire('Info', 'No details available', 'info');

        activeEvaluateeRowKey = String(rowKey);
        evaluationsById = {};
        var positions = row.evaluatee_positions || [];
        var multiPosition = positions.length > 1;
        var done = parseInt(row.done_count || 0, 10);
        var total = parseInt(row.total_count || 0, 10);

        var html = '<div class="d-flex flex-wrap justify-content-between align-items-start mb-3">'
            + '<div><h5 class="mb-0">' + escapeHtml(row.evaluatee_name || '-') + '</h5>'
            + '<div class="small text-muted">' + positions.map(escapeHtml).join(' &middot; ') + '</div></div>'
            + '<div class="text-right"><div class="small text-muted">Skor akhir' + (rowState(row) === 'partial' ? ' (sementara)' : '') + '</div>'
            + '<div style="font-size:1.6rem; font-weight:600; line-height:1">' + (done ? fmtNum(row.total_score) : '-') + '</div>'
            + '<div style="width:160px" class="ml-auto mt-1">' + progressHtml(done, total) + '</div></div>'
            + '</div>';

        html += '<h6 class="text-muted small text-uppercase mb-2">Ringkasan per kategori</h6>' + categorySummaryHtml(row);

        var evaluations = (row.evaluations || []).slice().sort(function (x, y) {
            var ox = (assessmentTypeLabels[x.assessment_type] || {}).order || 9;
            var oy = (assessmentTypeLabels[y.assessment_type] || {}).order || 9;
            return ox - oy
                || String(x.evaluatee_position || '').localeCompare(String(y.evaluatee_position || ''))
                || String(x.evaluator_name || '').localeCompare(String(y.evaluator_name || ''));
        });

        html += '<h6 class="text-muted small text-uppercase mb-2">Detail per penilai</h6>';

        evaluations.forEach(function (a) {
            evaluationsById[a.assessment_id] = a;
            var type = assessmentTypeLabels[a.assessment_type] || { label: a.assessment_type || '-', badge: 'badge-secondary' };
            var isDone = a.status === 'done';
            var bodyId = 'evalBody' + a.assessment_id;
            var categoryNames = groupScoresByCategory(a.scores).map(function (g) { return escapeHtml(g.name); }).join(', ');
            var expanded = false; // collapsed by default; click the header to open

            html += '<div class="card kpi-eval-card mb-2 ' + (isDone ? '' : 'border-warning') + '">'
                + '<div class="card-header d-flex justify-content-between align-items-center' + (expanded ? '' : ' collapsed') + '" data-toggle="collapse" data-target="#' + bodyId + '" style="cursor:pointer">'
                + '<div class="d-flex align-items-start mr-2" style="min-width:0">'
                    + '<span class="kpi-caret mr-1">&#9662;</span>'
                    + '<div style="min-width:0">'
                        + '<div><strong>' + escapeHtml(a.evaluator_name || '-') + '</strong>'
                        + (a.evaluator_position ? ' <span class="small text-muted">&middot; ' + escapeHtml(a.evaluator_position) + '</span>' : '')
                        + ' <span class="badge ' + type.badge + ' ml-1 align-middle">' + escapeHtml(type.label) + '</span></div>'
                        + '<div class="small text-muted mt-1">' + categoryNames
                        + (multiPosition && a.evaluatee_position ? ' &middot; untuk posisi ' + escapeHtml(a.evaluatee_position) : '') + '</div>'
                    + '</div>'
                + '</div>'
                + '<div class="text-right text-nowrap">'
                    + (isDone
                        ? '<div class="font-weight-bold" style="font-size:1.1rem; line-height:1">' + fmtNum(a.total_score) + '</div><span class="badge badge-success">Selesai</span>'
                        : '<span class="badge badge-warning">Belum dinilai</span>')
                + '</div></div>'
                + '<div id="' + bodyId + '" class="collapse' + (expanded ? ' show' : '') + '">'
                + '<div class="card-body p-2 evaluation-body" data-assessment-id="' + a.assessment_id + '">'
                + ((a.scores || []).length ? evaluationReadHtml(a) : '<div class="text-muted">Tidak ada indikator.</div>')
                + (canManageEvaluationScores && (a.scores || []).length
                    ? '<div class="text-right mt-2"><button type="button" class="btn btn-outline-primary btn-sm btn-edit-evaluation" data-assessment-id="' + a.assessment_id + '"><i class="fas fa-pen mr-1"></i>' + (isDone ? 'Ubah nilai' : 'Isi nilai') + '</button></div>'
                    : '')
                + '</div></div></div>';
        });

        var $m = $('#evaluateeDetailsModal');
        if (!$m.length) {
            $('body').append('<div class="modal fade" id="evaluateeDetailsModal" tabindex="-1" role="dialog"><div class="modal-dialog modal-xl" role="document"><div class="modal-content">'
                + '<div class="modal-header"><h5 class="modal-title">Detail Penilaian</h5><button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>'
                + '<div class="modal-body" id="evaluateeDetailsModalBody"></div></div></div></div>');
        }
        $('#evaluateeDetailsModalBody').html(html);
        $('#evaluateeDetailsModal').modal('show');
    }

    $(document).on('click', '.btn-edit-evaluation', function () {
        var a = evaluationsById[$(this).data('assessment-id')];
        if (!a) return;
        $(this).closest('.evaluation-body').html(evaluationEditHtml(a)).find('input[name^="scores["]').first().trigger('focus');
    });

    $(document).on('click', '.btn-cancel-edit-evaluation', function () {
        var a = evaluationsById[$(this).data('assessment-id')];
        if (!a) return;
        $(this).closest('.evaluation-body').html(
            evaluationReadHtml(a)
            + '<div class="text-right mt-2"><button type="button" class="btn btn-outline-primary btn-sm btn-edit-evaluation" data-assessment-id="' + a.assessment_id + '"><i class="fas fa-pen mr-1"></i>' + (a.status === 'done' ? 'Ubah nilai' : 'Isi nilai') + '</button></div>'
        );
    });

    function recalcWeightedScore($input) {
        var score = parseFloat($input.val());
        var indicatorWeight = parseFloat($input.attr('data-indicator-weight'));
        var categoryWeight = parseFloat($input.attr('data-category-weight'));
        var $cell = $input.closest('tr').find('.weighted-score-value');

        if (isNaN(score) || isNaN(indicatorWeight) || isNaN(categoryWeight)) {
            $cell.text('-');
            return;
        }

        $cell.text(fmtNum((score / 5) * (indicatorWeight / 100) * categoryWeight));
    }

    $(document).on('click', '.btn-evaluatee-details', function () {
        renderEvaluateeDetailsModal(String($(this).data('row-key')));
    });

    $(document).on('input change', '.edit-evaluation-form input[name^="scores["]', function () {
        recalcWeightedScore($(this));
    });

    $(document).on('submit', '.edit-evaluation-form', function (e) {
        e.preventDefault();
        var $form = $(this);
        var assessmentId = $form.data('assessment-id');
        if (!assessmentId) return;

        var $btn = $form.find('button[type="submit"]').prop('disabled', true);
        $.ajax({
            url: '/kpi/assessments/' + assessmentId + '/submit',
            type: 'POST',
            data: $form.serialize(),
            success: function (res) {
                if (!currentPeriodDetailsId || !activeEvaluateeRowKey) {
                    Swal.fire({ icon: 'success', title: 'Saved', text: res.message || 'Assessment updated.' });
                    return;
                }

                loadPeriodDetails(currentPeriodDetailsId, function () {
                    renderEvaluateeDetailsModal(activeEvaluateeRowKey);
                    Swal.fire({ icon: 'success', title: 'Saved', text: res.message || 'Assessment updated.' });
                });
            },
            error: function (xhr) {
                showAjaxError(xhr, 'Failed to save assessment');
            },
            complete: function () {
                $btn.prop('disabled', false);
            }
        });
    });

    // close period
    $(document).on('click', '.btn-close-period', function(){
        var id = $(this).data('id');
        if (!id) return;
        Swal.fire({ icon: 'warning', title: 'Close period?', showCancelButton: true }).then(function(resp){ if (!resp.value) return; $.ajax({ url: '/kpi/periods/' + id + '/close', type: 'POST', data: { _token: $('meta[name="csrf-token"]').attr('content') }, success: function(r){ periodsTable.ajax.reload(null,false); Swal.fire({icon:'success', title:'Closed', text: r.message}); }, error: function(xhr){ showAjaxError(xhr, 'Failed to close period'); } }); });
    });

    function showAjaxError(xhr, fallback) {
        var msg = fallback;
        try { msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : (xhr.responseJSON && xhr.responseJSON.errors ? Object.values(xhr.responseJSON.errors).flat()[0] : fallback); } catch(e){}
        Swal.fire({icon:'error', title:'Error', text: msg});
    }
});
</script>
@endsection
