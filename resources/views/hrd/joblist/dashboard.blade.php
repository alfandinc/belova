@extends('layouts.hrd.app')

@section('navbar')
    @include('layouts.hrd.navbar-joblist')
@endsection

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>JobList Dashboard</h4>
            <div class="d-flex justify-content-between align-items-center">
            <div class="mr-2">
                <input type="date" id="filter_start" class="form-control" />
            </div>
            <div class="mr-2">
                <input type="date" id="filter_end" class="form-control" />
            </div>
            <div>
                <button id="btnRefreshSummary" class="btn btn-primary">Refresh</button>
            </div>
        </div>
    </div>

    <div id="division-summary" class="mb-3">
        <!-- summary cards injected here -->
    </div>

    <style>
        /* small UI polish */
        .card.h-100 { border-radius: 6px; }
        .card.h-100 a { color: inherit; }
        .card.h-100:hover { transform: translateY(-4px); transition: all .12s ease-in-out; }
        .summary-tile .card { min-height: 80px; }
        .badge { font-size: 0.85em; }
    </style>

    <div id="details-container">
        <!-- optional: details list will appear here if needed -->
    </div>
</div>

@endsection

@section('scripts')
<script>
$(function(){
    function loadSummary(){
        var s = $('#filter_start').val();
        var e = $('#filter_end').val();
        // fetch summary
        $.get('{!! route("hrd.joblist.summary") !!}', { start_date: s, end_date: e }, function(res){
            if (!res.success) return;
            var html = '';
            if (res.data.length === 0) {
                html = '<div class="alert alert-secondary">No divisions found.</div>';
                $('#division-summary').html(html);
                return;
            }

            // per-division cards

            // per-division cards
            html += '<div class="row">';
            res.data.forEach(function(d){
                html += '<div class="col-sm-6 col-md-4 col-lg-3 mb-3">';
                html += '<a href="' + buildLink(d.division_id) + '" class="text-decoration-none">';
                html += '<div class="card h-100 shadow-sm">';
                html += '<div class="card-body">';
                html += '<div class="d-flex justify-content-between">';
                html += '<div><h6 class="mb-1 text-dark">' + escapeHtml(d.division_name) + '</h6><small class="text-muted">Division</small></div>';
                html += '<div class="text-right">';
                html += '<div class="h3 mb-0 font-weight-bold text-primary">' + (d.ongoing||0) + '</div>';
                html += '<small class="text-muted">Ongoing</small>';
                html += '</div></div>';
                html += '<hr class="my-2" />';
                html += '<div class="d-flex">';
                html += '<div class="mr-2"><span class="badge badge-success">Done: ' + (d.done||0) + '</span></div>';
                html += '<div><span class="badge badge-danger">Canceled: ' + (d.canceled||0) + '</span></div>';
                html += '</div>';
                html += '</div></div></a></div>';
            });
            html += '</div>';

            $('#division-summary').html(html);
        });
    }

    // (Totals removed — dashboard shows per-division cards only)

    function buildLink(divisionId){
        var params = new URLSearchParams();
        if (divisionId) params.set('division_id', divisionId);
        // default to show ongoing on index
        params.set('status', 'progress');
        return '{!! route("hrd.joblist.index") !!}' + '?' + params.toString();
    }

    function escapeHtml(str){
        if (!str) return '';
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
    }

    // default dates: first of month -> today
    var now = new Date();
    var today = now.toISOString().substr(0,10);
    var firstOfMonth = new Date(now.getFullYear(), now.getMonth(), 1).toISOString().substr(0,10);
    if (!$('#filter_start').val()) $('#filter_start').val(firstOfMonth);
    if (!$('#filter_end').val()) $('#filter_end').val(today);
    loadSummary();

    $('#btnRefreshSummary').on('click', function(){ loadSummary(); });
    $('#filter_start, #filter_end').on('change', function(){ loadSummary(); });
});
</script>
@endsection
