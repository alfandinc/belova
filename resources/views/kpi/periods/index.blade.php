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

    <div class="row">
        <div class="col-md-6">
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

        <div class="col-md-6">
            <div class="card">
                <div class="card-body p-2">
                    <h5 class="mb-3">Period Details</h5>
                    <div id="periodDetails">
                        <div class="text-muted">Select a period on the left and click <strong>Scores</strong> to view pending scores and per-employee results.</div>
                    </div>
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
        return Math.round(v) + '%';
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
            $('#previewSummary').text('Proposals: ' + (res.counts.proposals || previewData.length));

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
                $item.append('<div><strong>' + (ev.evaluator_employee_name || '-') + '</strong><div class="small text-muted">' + (ev.evaluator_position_name ? ev.evaluator_position_name : (ev.evaluator_position_id ? ('Pos ID: ' + ev.evaluator_position_id) : '')) + '</div></div>');
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

            // show category weight badges at top for this evaluatee (replace proposals text)
            var summaryHtml = '';
            Object.keys(catMeta).forEach(function(c){
                var w = catMeta[c].catWeight || 0;
                summaryHtml += '<span class="badge badge-pill badge-info mr-1">' + c + ': ' + fmtPct(w) + '</span>';
            });
            $('#previewSummary').html(summaryHtml);

            // iterate categories and their items, render category once with rowspan and show weights
            catOrder.forEach(function(c){
                var items = cats[c];
                items.forEach(function(row, idxInCat){
                    var rowHtml = '<tr>';
                    if (firstEval) {
                        rowHtml += '<td rowspan="'+totalRows+'"><div>' + (row.evaluatee_name || '') + '</div><div class="small text-muted">' + (row.evaluatee_position_name || '-') + '</div></td>';
                        firstEval = false;
                    }
                    if (idxInCat === 0) {
                        var meta = catMeta[c] || { catWeight: 0, totalIndicatorWeight: 0 };
                        var indOk = Math.round(meta.totalIndicatorWeight) === 100;
                        rowHtml += '<td rowspan="'+items.length+'"><strong>' + (c) + '</strong><div class="small text-muted">Indicators total: <span class="' + (indOk ? 'text-success' : 'text-danger') + '">' + fmtPct(meta.totalIndicatorWeight) + '</span></div></td>';
                    }
                    rowHtml += '<td>' + (row.indicator_name || '') + ' <span class="small text-muted">(' + (row.indicator_weight !== undefined ? fmtPct(row.indicator_weight) : '') + ')</span></td>';
                    rowHtml += '</tr>';
                    $tbody.append(rowHtml);
                });
            });
        });
    });

    $('#confirmStartBtn').on('click', function(){
        if (!previewPeriodId) return;
        $.ajax({ url: '/kpi/periods/' + previewPeriodId + '/start', type: 'POST', data: { _token: $('meta[name="csrf-token"]').attr('content') }, success: function(r){ $('#previewModal').modal('hide'); periodsTable.ajax.reload(null,false); Swal.fire({icon:'success', title:'Started', text: r.message}); }, error: function(xhr){ showAjaxError(xhr, 'Failed to start assessment'); } });
    });

    // open period
    $(document).on('click', '.btn-open-period', function(){
        var id = $(this).data('id');
        if (!id) return;
        $.ajax({ url: '/kpi/periods/' + id + '/open', type: 'POST', data: { _token: $('meta[name="csrf-token"]').attr('content') }, success: function(r){ periodsTable.ajax.reload(null,false); Swal.fire({icon:'success', title:'Opened', text: r.message}); }, error: function(xhr){ showAjaxError(xhr, 'Failed to open period'); } });
    });

    // show details for a period (populate right panel)
    $(document).on('click', '.btn-scores-period', function(){
        var id = $(this).data('id');
        if (!id) return;
        $('#periodDetails').html('<div class="text-center py-4">Loading...</div>');
        $.get('/kpi/periods/' + id + '/details', function(res){
            if (!res.success) return showAjaxError({ responseJSON: res }, 'Failed to load details');
            var html = '';
            if (!res.data || res.data.length === 0) {
                html = '<div class="alert alert-info">No assessments found for this period.</div>';
            } else {
                html += '<div class="table-responsive"><table class="table table-sm table-bordered"><thead><tr><th>Employee</th><th class="text-right">Total Score</th><th class="text-center">Status</th><th class="text-center">Action</th></tr></thead><tbody>';
                periodDetailsData = res.data || [];
                res.data.forEach(function(e){
                    var done = parseInt(e.done_count || 0, 10);
                    var total = parseInt(e.total_count || 0, 10);
                    var statusText = done + '/' + total;
                    var badgeClass = 'badge-danger';
                    if (total > 0 && done === total) badgeClass = 'badge-success';
                    else if (done === 0) badgeClass = 'badge-danger';
                    else badgeClass = 'badge-warning';

                    html += '<tr>';
                    html += '<td>' + (e.evaluatee_name || '-') + (e.evaluatee_position ? '<div class="small text-muted">' + e.evaluatee_position + '</div>' : '') + '</td>';
                    html += '<td class="text-right"><strong>' + (e.total_score !== null ? e.total_score : '-') + '</strong></td>';
                    html += '<td class="text-center"><span class="' + badgeClass + '" style="padding:6px 8px;border-radius:6px;">' + statusText + '</span></td>';
                    html += '<td class="text-center"><button class="btn btn-sm btn-info btn-evaluatee-details" data-row-key="' + e.row_key + '">Details</button></td>';
                    html += '</tr>';
                });
                html += '</tbody></table></div>';
            }
            $('#periodDetails').html(html);
        }).fail(function(xhr){ showAjaxError(xhr, 'Failed to load details'); });
    });

    // store last loaded details data
    var periodDetailsData = [];

    // show modal with evaluator details for a specific evaluatee
    $(document).on('click', '.btn-evaluatee-details', function(){
        var rowKey = String($(this).data('row-key'));
        var row = (periodDetailsData || []).find(function(r){ return String(r.row_key) == rowKey; });
        if (!row) return Swal.fire('Info', 'No details available', 'info');

        // Build a single clean table with header once: Evaluator | Indicator | Weight % | Score | Weighted Score | Notes
        var html = '<div class="table-responsive"><table class="table table-sm table-bordered"><thead><tr><th>Evaluator</th><th>Indicator</th><th class="text-right">Weight %</th><th class="text-right">Score</th><th class="text-right">Weighted Score</th><th>Notes</th></tr></thead><tbody>';
        row.evaluations.forEach(function(a){
            var scores = (a.scores || []);
            var evaluatorHtml = (a.evaluator_name || '-');
            var rowClass = (a.status === 'pending') ? 'table-warning' : '';
            if (scores.length === 0) {
                html += '<tr' + (rowClass ? (' class="' + rowClass + '"') : '') + '>';
                html += '<td>' + evaluatorHtml + '</td>';
                html += '<td>-</td><td class="text-right">-</td><td>-</td><td class="text-right">-</td>';
                html += '</tr>';
            } else {
                scores.forEach(function(s, idx){
                    html += '<tr' + (rowClass ? (' class="' + rowClass + '"') : '') + '>';
                    if (idx === 0) {
                        html += '<td rowspan="' + scores.length + '">' + evaluatorHtml + '</td>';
                    }
                    var noteText = s.notes ? s.notes : '';
                    var scoreText = '-';
                    if (s.score !== null && s.score !== undefined && s.score !== '') {
                        var n = Number(s.score);
                        scoreText = isFinite(n) ? String(Math.round(n)) : s.score;
                    }
                    var weighted = '-';
                    if (s.final_calculated_score !== null && s.final_calculated_score !== undefined && s.final_calculated_score !== '') {
                        var w = Number(s.final_calculated_score);
                        weighted = isFinite(w) ? String(Math.round(w)) : s.final_calculated_score;
                    }
                    // indicator weight (percent)
                    var weightPct = '';
                    if (s.indicator_weight !== null && s.indicator_weight !== undefined && s.indicator_weight !== '') {
                        var iw = Number(s.indicator_weight);
                        weightPct = isFinite(iw) ? String(Math.round(iw)) + '%' : s.indicator_weight;
                    } else if (s.weight !== null && s.weight !== undefined && s.weight !== '') {
                        var iw2 = Number(s.weight);
                        weightPct = isFinite(iw2) ? String(Math.round(iw2)) + '%' : s.weight;
                    } else {
                        weightPct = '-';
                    }
                    html += '<td>' + (s.indicator_name || '') + '</td>';
                    html += '<td class="text-right">' + weightPct + '</td>';
                    html += '<td class="text-right">' + scoreText + '</td>';
                    html += '<td class="text-right">' + weighted + '</td>';
                    html += '<td>' + (noteText ? noteText : '-') + '</td>';
                    html += '</tr>';
                });
            }
        });
        html += '</tbody></table></div>';

        // show in modal
        var $m = $('#evaluateeDetailsModal');
        if (!$m.length) {
            $('body').append('\n<div class="modal fade" id="evaluateeDetailsModal" tabindex="-1" role="dialog">\n  <div class="modal-dialog modal-xl" role="document">\n    <div class="modal-content">\n      <div class="modal-header">\n        <h5 class="modal-title">Evaluatee Details</h5>\n        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>\n      </div>\n      <div class="modal-body" id="evaluateeDetailsModalBody"></div>\n    </div>\n  </div>\n</div>\n');
            $m = $('#evaluateeDetailsModal');
        }
        $('#evaluateeDetailsModalBody').html(html);
        $('#evaluateeDetailsModal').modal('show');
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
