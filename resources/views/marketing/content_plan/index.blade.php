@extends('layouts.marketing.app')

@section('title', 'Content Plan')

@section('navbar')
    @include('layouts.marketing.navbar')
@endsection

@push('styles')
<!-- Summernote CSS (Bootstrap 4) -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.18/summernote-bs4.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
<style>
/* Allow the Judul column to wrap into multiple lines and give it more room */
.judul-col{ 
    white-space: normal !important;
    word-break: break-word;
    max-width: 600px; /* allow wider Judul column */
}
/* Jam (time) column */
.jam-col{ 
    white-space: nowrap !important;
    width: 80px;
    text-align: center;
}
/* Ensure jam time is visually prominent */
.jam-col div strong { font-weight: 700; font-size: 1rem; }
table.contentPlanTable th.judul-col, table.contentPlanTable td.judul-col{
    white-space: normal !important;
}
/* Ensure action buttons have a smaller reserved width so Judul can expand */
.aksi-col{
    white-space: nowrap !important;
    min-width: 60px; /* tighter space for single action button */
    width:60px;
}
/* Normalize badge spacing inside content plan tables */
.contentPlanTable td .badge, .contentPlanTable th .badge {
    margin-right: 4px !important;
    margin-bottom: 4px;
    display: inline-block;
}
/* Subtle separators and cleaner spacing */
.contentPlanTable { border: none !important; border-collapse: separate !important; }
.contentPlanTable th, .contentPlanTable td { border: none !important; }
.contentPlanTable tbody td { border-bottom: 1px solid rgba(0,0,0,0.06) !important; padding: 12px 16px !important; background: transparent; }
.contentPlanTable tbody tr { background: #ffffff; }
.contentPlanTable tbody tr:hover { background: #f8f9fa; }
.table-responsive { padding: 0.5rem; }
.card .card-body.p-0 { padding: 0.25rem 0.5rem !important; }
/* Make Judul look clickable and accessible */
.contentPlanTable td.judul-col .judul-clickable {
    cursor: pointer;
    color: #0d6efd; /* bootstrap link color */
    text-decoration: none;
}
.contentPlanTable td.judul-col .judul-clickable:hover {
    text-decoration: underline;
    color: #0b5ed7;
}
.contentPlanTable td.judul-col .judul-clickable:focus {
    outline: 2px dashed rgba(13,110,253,0.35);
    outline-offset: 2px;
}
.contentPlanTable td.judul-col .judul-clickable .badge { cursor: default; }
.contentPlanTable .btn-edit { display: none !important; }

/* Small initials badge for Assigned To shown on the right of the title cell */
.assigned-name { vertical-align: middle; border-radius: 6px; font-size: .78rem; background: rgba(0,0,0,0.04); color: #212529; padding: .18rem .5rem; max-width: 160px; display: inline-block; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; }

/* Larger modal for Add/Edit Content Plan */
@media (min-width: 992px) {
    .modal-dialog.modal-xl { max-width: 1200px; }
}
@media (min-width: 1400px) {
    .modal-dialog.modal-xl { max-width: 1400px; }
}
/* Prevent horizontal scrollbar inside modal and make inner flex items wrap */
#contentPlanModal .modal-content, #contentPlanModal .modal-body, #contentPlanModal .tab-content { overflow-x: hidden; }
#contentPlanModal .row { flex-wrap: wrap; }
/* Override preview min-width which previously forced horizontal scroll */
#cb_preview { min-width: 0 !important; max-width: 100%; }
/* Ensure tables and inputs inside modal don't exceed container width */
#contentPlanModal table, #contentPlanModal .form-control, #contentPlanModal .select2-container { max-width: 100%; }

/* Separator between Judul (title) and badges inside the clickable area */
.contentPlanTable td.judul-col .judul-clickable > .mt-1 {
    border-top: 1px solid #e9ecef;
    margin-top: 8px;
    padding-top: 6px;
}
.contentPlanTable td.judul-col .judul-clickable .badge { margin-top: 4px; }

/* Blinking warning icon when publish time is past due */
.blink-warning { color: #dc3545; margin-left:6px; display:inline-block; }
@keyframes blink {
    0% { opacity: 1; }
    50% { opacity: 0.15; }
    100% { opacity: 1; }
}
.blink-warning.blink { animation: blink 1s linear infinite; }
/* Per-day stats color variants (appear next to the date) */
.day-stats { margin-left: 8px; }
.content-stats-row { gap: 8px; }
.stat-card { display:flex; align-items:center; gap:12px; padding:12px 14px; border-radius:8px; background:#ffffff; box-shadow:0 1px 3px rgba(0,0,0,0.04); border:1px solid rgba(0,0,0,0.04); }
.stat-icon { width:36px; height:36px; display:inline-flex; align-items:center; justify-content:center; border-radius:6px; color:#fff; font-size:1.05rem; }
.stat-number { font-size:1.25rem; font-weight:700; }
.stat-label { font-size:0.82rem; color:#6c757d; }
.stat-published .stat-icon { background:#28a745; }
.stat-scheduled .stat-icon { background:#ffc107; color:#212529; }
.stat-draft .stat-icon { background:#6c757d; }
.stat-cancelled .stat-icon { background:#dc3545; }
.stat-total .stat-icon { background:#0d6efd; }
.stat-card.small { padding:8px 10px; }
.day-stats.green { color: #28a745; }
.day-stats.yellow { color: #856404; /* darker yellow for contrast */ }
.day-stats.red { color: #dc3545; }
.day-stats.default { color: #6c757d; }
/* Compact header stat card (right corner) */
.header-stat-card {
    background: #ffffff;
    border: 1px solid rgba(0,0,0,0.06);
    padding: 8px 12px;
    border-radius: 10px;
    min-width: 170px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.header-stat-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.08); cursor: pointer; }
.header-stat-card .small { line-height: 1; }
.header-stat-card .stat-icon {
    width:36px; height:36px; border-radius:8px; display:inline-flex; align-items:center; justify-content:center; background: rgba(220,53,69,0.08); color:#dc3545; font-size:16px;
}
.header-stat-card .stat-text { margin-left:10px; text-align:right; }
.header-stat-card .stat-text .label { font-size:0.78rem; color:#6c757d; }
.header-stat-card .stat-text .value { font-size:1.1rem; font-weight:700; color:#dc3545; }
@media (max-width: 768px) {
    .header-stat-card { min-width: 120px; padding:6px 8px; }
    .header-stat-card .stat-text .label { display:none; }
}

/* Make header stat label bolder for emphasis */
.header-stat-card .stat-text .label { font-weight:700; color: #495057; }

/* Show value (count) before label in header stat */
.header-stat-card .stat-text { display:flex; align-items:center; gap:8px; text-align:left; }
.header-stat-card .stat-text .value { font-size:1.25rem; color:#dc3545; }
</style>
@endpush

@section('content')
<div class="container-fluid mt-4">
    
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Content Plan</h4>
            <div class="d-flex align-items-center">
                <!-- left spacer to keep title on the left -->
                <div></div>
                <!-- right-side: stat card + nav buttons -->
                <div style="margin-left:auto; display:flex; align-items:center; gap:12px; flex-wrap:nowrap;">
                    <div class="header-stat-card header-stat-terlewat" role="button" title="Klik untuk melihat Konten Terlewat">
                        <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
                        <div class="stat-text">
                            <div id="stat_terlewat" class="value">0</div>
                            <div class="label">Konten Terlewat</div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center" style="gap:8px; flex-wrap:nowrap;">
                        <button type="button" class="btn btn-light btn-sm me-2" id="btnPrevWeek" title="Previous week"><i class="fas fa-chevron-left"></i></button>
                        <div class="input-group input-group-sm" style="min-width:220px;">
                            <select id="monthPicker" class="form-control form-control-sm"></select>
                            <select id="weekPicker" class="form-control form-control-sm" style="max-width:110px">
                                <option value="1">Week 1</option>
                                <option value="2">Week 2</option>
                                <option value="3">Week 3</option>
                                <option value="4">Week 4</option>
                            </select>
                        </div>
                        <button type="button" class="btn btn-light btn-sm ms-2" id="btnNextWeek" title="Next week"><i class="fas fa-chevron-right"></i></button>
                    </div>
                </div>
            </div>
        </div>
            <div class="card-body">
            <div id="weekTables" class="row"></div>
        </div>
    </div>
</div>

@include('marketing.content_plan.partials.modal')
@include('marketing.content_plan.partials.content_report_modal')

<!-- Status picker modal -->
<div class="modal fade" id="statusModal" tabindex="-1" role="dialog" aria-labelledby="statusModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content">
            <form id="statusForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="statusModalLabel">Ubah Status</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="plan_id" value="">
                    <div class="form-group">
                        <label for="status_select">Pilih Status</label>
                        <select id="status_select" name="status" class="form-control">
                            <option value="Draft">Draft</option>
                            <option value="Scheduled">Scheduled</option>
                            <option value="Published">Published</option>
                            <option value="Revisi">Revisi</option>
                            <option value="Cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
</div>

<!-- Status list modal: shows items for selected status -->
<div class="modal fade" id="statusListModal" tabindex="-1" role="dialog" aria-labelledby="statusListModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="statusListModalLabel">Content items</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-sm" id="statusListTable">
                        <thead>
                            <tr>
                                <th style="min-width:200px">Day</th>
                                <th>Judul</th>
                                <th style="min-width:140px">Assigned</th>
                                <th style="min-width:120px">Aksi</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- Summernote JS (Bootstrap 4) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.18/summernote-bs4.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<script>
$(function() {
    // Status filter select2
    try { if ($.fn && $.fn.select2) { $('#filterStatus').select2({ width: '100%', placeholder: 'Pilih Status', allowClear: true, dropdownParent: $('#filterStatus').parent() }); } } catch(e) {}
    $('#filterStatus').on('change', function() {
        reloadAllTables();
    });
    // Konten Pilar filter select2
    try { if ($.fn && $.fn.select2) { $('#filterKontenPilar').select2({ width: '100%', placeholder: 'Pilih Konten Pilar', allowClear: true, dropdownParent: $('#filterKontenPilar').parent() }); } } catch(e) {}
    $('#filterKontenPilar').on('change', function() {
        reloadAllTables();
    });
    // Platform filter select2
    try { if ($.fn && $.fn.select2) { $('#filterPlatform').select2({ width: '100%', placeholder: 'Pilih Platform', allowClear: true, dropdownParent: $('#filterPlatform').parent() }); } } catch(e) {}
    $('#filterPlatform').on('change', function() {
        reloadAllTables();
    });
    // Brand filter select2
    try { if ($.fn && $.fn.select2) { $('#filterBrand').select2({ width: '100%', placeholder: 'Pilih Brand', allowClear: true, dropdownParent: $('#filterBrand').parent() }); } } catch(e) {}
    $('#filterBrand').on('change', function() {
        reloadAllTables();
    });
    // Date Range Picker for filter (default to current month)
    var _defaultStart = moment().startOf('isoWeek');
    var _defaultEnd = moment().endOf('isoWeek');
    $('#filterDateRange').daterangepicker({
        autoUpdateInput: true,
        startDate: _defaultStart,
        endDate: _defaultEnd,
        locale: {
            cancelLabel: 'Clear',
            format: 'DD/MM/YYYY'
        }
    });
    // set initial value to current month range
    $('#filterDateRange').val(_defaultStart.format('DD/MM/YYYY') + ' - ' + _defaultEnd.format('DD/MM/YYYY'));
    $('#filterDateRange').on('apply.daterangepicker', function(ev, picker) {
        $(this).val(picker.startDate.format('DD/MM/YYYY') + ' - ' + picker.endDate.format('DD/MM/YYYY'));
        // update internal week bounds to the selected range
        try {
            _defaultStart = picker.startDate.clone();
            _defaultEnd = picker.endDate.clone();
        } catch(e) { console.error(e); }
        // rebuild tables for the new start/end
        try {
            if (typeof contentPlanTables !== 'undefined' && contentPlanTables.length) {
                contentPlanTables.forEach(function(t){ try{ t.destroy(); }catch(e){} });
                contentPlanTables = [];
            }
        } catch(e) { console.error(e); }
        buildWeekTables();
        reloadAllTables();
    });
    $('#filterDateRange').on('cancel.daterangepicker', function(ev, picker) {
        $(this).val('');
        reloadAllTables();
    });

    // Collapse panel events: update toggle button label
    $('#filterPanel').on('shown.bs.collapse', function() {
        $('#btnToggleFilters').html('<i class="fas fa-filter me-1"></i> Hide Filters');
    });
    $('#filterPanel').on('hidden.bs.collapse', function() {
        $('#btnToggleFilters').html('<i class="fas fa-filter me-1"></i> Show Filters');
    });

    // Initialize toggle button label according to initial state
    if ($('#filterPanel').hasClass('show')) {
        $('#btnToggleFilters').html('<i class="fas fa-filter me-1"></i> Hide Filters');
    } else {
        $('#btnToggleFilters').html('<i class="fas fa-filter me-1"></i> Show Filters');
    }

    // Toggle filters when button clicked
    $('#btnToggleFilters').on('click', function() {
        $('#filterPanel').collapse('toggle');
    });
    // Reset all filters button
    $('#btnResetFilters').on('click', function() {
        console.log('Reset Filters clicked');
        // Clear multi-selects (set empty array) and single selects (set empty string)
        try {
            $('#filterBrand').val([]).trigger('change');
            $('#filterPlatform').val([]).trigger('change');
            $('#filterStatus').val('');
            $('#filterKontenPilar').val('').trigger('change');
            // trigger change for status if it uses select2
            try { $('#filterStatus').trigger('change'); } catch(e){}
        } catch(e) { console.error(e); }

        // Reset date range to empty (clear input). Also reset picker internal dates to defaults.
        try {
            var drp = $('#filterDateRange').data('daterangepicker');
            if (drp) {
                drp.setStartDate(_defaultStart);
                drp.setEndDate(_defaultEnd);
            }
            $('#filterDateRange').val('');
        } catch(e) { console.error(e); }

        // Reload table without changing pagination
        try {
            reloadAllTables(false);
        } catch(e) { console.error(e); }
    });
    // (Removed image reference inputs and preview per UI simplification)
    // Build 7 DataTables, one per day (Monday..Sunday) for the current ISO week
    var contentPlanTables = [];
    // map of assigned_to id => display name (used to show initials on cards)
    var assignedNameMap = {};
    try {
        $('#assigned_to option').each(function(){ var v = $(this).val(); var t = $(this).text() ? $(this).text().trim() : ''; if (v) assignedNameMap[v] = t; });
    } catch(e) { assignedNameMap = {}; }
    // helper: convert hex to rgba string
    function hexToRgba(hex, alpha){
        if (!hex) return 'rgba(0,0,0,'+alpha+')';
        hex = hex.replace('#','');
        if (hex.length === 3) hex = hex.split('').map(function(h){ return h+h; }).join('');
        var r = parseInt(hex.substring(0,2),16);
        var g = parseInt(hex.substring(2,4),16);
        var b = parseInt(hex.substring(4,6),16);
        return 'rgba('+r+','+g+','+b+','+alpha+')';
    }
    function buildWeekTables() {
        $('#weekTables').empty();
        var weekStart = _defaultStart.clone();

        // Build empty cards/tables for seven days first
            for (var i=0;i<7;i++){
            var d = weekStart.clone().add(i, 'days');
            var dayName = d.format('dddd');
            var dayFull = d.format('dddd, D MMMM YYYY');
            var key = d.format('YYYY-MM-DD');
            var tableId = 'contentPlanTable-' + i;
            var col = $('<div class="col-12 col-md-6 col-lg-4 mb-3"><div class="card"><div class="card-header d-flex align-items-center"><div><strong style="display:block">'+dayFull+'</strong></div><div class="ml-auto"><button type="button" class="btn btn-sm btn-outline-primary btn-add-card" data-date="'+key+'" title="Tambah Content Plan"><i class="fas fa-plus"></i></button></div></div><div class="card-body p-0"><div class="table-responsive"><table class="table table-bordered contentPlanTable" id="'+tableId+'" data-date="'+key+'" style="width:100%"><thead><tr><th class="jam-col">Time</th><th>Description</th></tr></thead></table></div></div></div></div>');
            $('#weekTables').append(col);
        }

        // Fetch entire week data in a single request
        var params = {
            date_start: _defaultStart.format('DD/MM/YYYY'),
            date_end: _defaultEnd.format('DD/MM/YYYY')
        };
        var brands = $('#filterBrand').val(); if (brands && brands.length) params.filter_brand = brands;
        var platforms = $('#filterPlatform').val(); if (platforms && platforms.length) params.filter_platform = platforms;
        var status = $('#filterStatus').val(); if (status) params.filter_status = status;
        var kontenPilar = $('#filterKontenPilar').val(); if (kontenPilar) params.filter_konten_pilar = kontenPilar;

        $.get('{{ route('marketing.content-plan.week') }}', params).done(function(resp){
            window._latestWeekResp = resp;
            var grouped = resp && resp.data ? resp.data : {};
            try { updateStatusStats(resp); } catch(e) {}
            // initialize each day's DataTable with client-side data
            for (var i=0;i<7;i++){
                try{
                    var d = weekStart.clone().add(i, 'days');
                    var key = d.format('YYYY-MM-DD');
                    var rows = grouped[key] || [];
                    var tableId = 'contentPlanTable-' + i;
                    (function(containerId, dataRows){
                        // Ensure any previous DataTable instance is destroyed before initializing
                        try { if ($.fn.dataTable.isDataTable('#'+containerId)) { $('#'+containerId).DataTable().destroy(); } } catch(e) {}
                        var dt = $('#'+containerId).DataTable({
                            data: dataRows,
                            destroy: true,
                            processing: false,
                            serverSide: false,
                            responsive: true,
                            scrollX: true,
                            autoWidth: false,
                            searching: false,
                            lengthChange: false,
                            paging: false,
                            info: false,
                            columns: [
                                { data: 'tanggal_publish', className: 'jam-col', render: function(data, type, row){
                                        var time = '';
                                        if (data) time = moment(data).locale('id').format('HH.mm');
                                        var status = row && row.status ? row.status : '';
                                        var map = { 'draft': {bg: '#6c757d', color: '#ffffff'}, 'scheduled': {bg: '#ffc107', color: '#212529'}, 'published': {bg: '#28a745', color: '#ffffff'}, 'revisi': {bg: '#dc3545', color: '#ffffff'}, 'cancelled': {bg: '#dc3545', color: '#ffffff'} };
                                        var sHtml = '';
                                        if (status) {
                                            var key = status.toLowerCase();
                                            var style = '';
                                            if (map[key]) style = 'background-color:' + map[key].bg + ';color:' + map[key].color + ';border-color:transparent;';
                                            // make status badge clickable to open status picker modal
                                            sHtml = '<div class="mt-1"><span class="badge status-badge" data-id="' + (row && row.id ? row.id : '') + '" style="' + style + ';cursor:pointer;">' + status + '</span></div>';
                                        }
                                        // show blinking warning if publish datetime is past and not published/cancelled
                                        var warnHtml = '';
                                        try {
                                            if (data) {
                                                var isPast = moment(data).isBefore(moment());
                                                var st = (status || '').toLowerCase();
                                                if (isPast && st !== 'published' && st !== 'cancelled') {
                                                    warnHtml = '<span class="blink-warning blink" title="Publish time passed"><i class="fas fa-exclamation-triangle"></i></span>';
                                                }
                                            }
                                        } catch(e) { }
                                        return '<div><strong>' + time + '</strong>' + warnHtml + '</div>' + sHtml;
                                    } },
                                { data: 'judul_html', className: 'judul-col', render: function(data, type, row){
                                        var html = data || '';
                                        if (row && row.platform_html) {
                                            html += '<div class="mt-1">' + row.platform_html + '</div>';
                                        }
                                        // Append assigned user initial (if available) to the right side
                                        try {
                                            var ass = row && row.assigned_to ? row.assigned_to : null;
                                            var assName = ass && assignedNameMap[ass] ? assignedNameMap[ass] : (row && row.assigned_to_name ? row.assigned_to_name : null);
                                            if (assName) {
                                                // show full name to the right of the title
                                                html += '<span class="assigned-name badge badge-light" title="'+assName+'" style="float:right;margin-left:8px;font-weight:600;padding:.25rem .6rem;font-size:.85rem">'+assName+'</span>';
                                            }
                                        } catch(e) {}
                                        // Wrap in a focusable, clickable container for visual affordance and accessibility
                                        return '<div class="judul-clickable" tabindex="0" role="button" title="Click to edit">' + html + '</div>';
                                    } }
                            ],
                            order: [[0,'asc']],
                            rowCallback: function(row, data, displayNum, displayIndex, dataIndex){
                                try {
                                    var brandColors = {
                                        'premiere belova': '#0d6efd',
                                        'belova skin': '#6f42c1',
                                        'belova dental care': '#ff66b2',
                                        'bcl': '#e83e8c',
                                        'dr fika': '#fd7e14'
                                    };
                                    var color = null;
                                    if (data && data.brand) {
                                        var b = data.brand;
                                        if (Array.isArray(b) && b.length) color = brandColors[b[0].toLowerCase()];
                                        else if (typeof b === 'string' && b.length) color = brandColors[b.toLowerCase()];
                                    }
                                    if (color) {
                                        $(row).css('background-color', hexToRgba(color, 0.06));
                                        $(row).find('td').first().css('border-left', '4px solid ' + color);
                                        // apply brand color to the Judul text for visual association
                                        try { $(row).find('td.judul-col .judul-clickable').css('color', color); } catch(e) {}
                                    } else {
                                        $(row).css('background-color', '');
                                        $(row).find('td').first().css('border-left', 'none');
                                        try { $(row).find('td.judul-col .judul-clickable').css('color', ''); } catch(e) {}
                                    }
                                    // attach data attributes for drag/drop handling
                                    try { $(row).attr('data-row-id', data.id); $(row).data('rowData', data); } catch(e) {}
                                } catch(e) { console.error(e); }
                            },
                            drawCallback: function(settings){
                                // Brief button injection removed — brief now lives in the Edit modal's Brief tab
                            }
                        });
                            contentPlanTables.push(dt);
                        // Update card header with (published/total) stats for the day
                        try {
                            var $tableEl = $('#'+containerId);
                            var $card = $tableEl.closest('.card');
                            var displayDate = d.format('dddd, D MMMM YYYY');
                            var totalCount = Array.isArray(dataRows) ? dataRows.length : 0;
                            var publishedCount = 0;
                            try { publishedCount = dataRows.filter(function(r){ return (r.status||'').toLowerCase() === 'published'; }).length; } catch(e) {}
                            var statCls = 'default';
                            if (totalCount > 0) {
                                if (publishedCount === totalCount) statCls = 'green';
                                else if (publishedCount === 0) statCls = 'red';
                                else statCls = 'yellow';
                            }
                            var statHtml = '<span class="day-stats ' + statCls + '">(' + publishedCount + '/' + totalCount + ')</span>';
                            $card.find('.card-header strong').html(displayDate + ' ' + statHtml);
                        } catch(e) { console.error('update header stats', e); }
                    })(tableId, rows);
                }catch(e){console.error('init day table', e);}
            }

            // enable drag & drop between day tables (requires jQuery UI)
            try {
                function enableRowDragDrop(){
                    if (!$.fn.sortable) return;
                    $('.contentPlanTable tbody').sortable({
                        connectWith: '.contentPlanTable tbody',
                        helper: function(e, ui){
                            ui.children().each(function(){ $(this).width($(this).width()); });
                            return ui;
                        },
                        start: function(e, ui){ ui.item.addClass('dragging'); },
                        stop: function(e, ui){ ui.item.removeClass('dragging'); },
                        receive: function(event, ui){
                            try {
                                var $moved = ui.item;
                                var rowData = $moved.data('rowData');
                                if (!rowData || !rowData.id) return;
                                var id = rowData.id;
                                var $targetTable = $(this).closest('table');
                                var targetDate = $targetTable.data('date'); // YYYY-MM-DD
                                if (!targetDate) { reloadAllTables(false); return; }
                                var $sourceTable = $(ui.sender).closest('table');
                                var sourceDt = $sourceTable.DataTable();
                                var targetDt = $targetTable.DataTable();
                                try { sourceDt.rows($moved).remove(); } catch(e) {}

                                // Fetch full plan from server so we can supply required fields for update
                                $.get('/marketing/content-plan/' + id).done(function(plan){
                                    try {
                                        // preserve the time from existing tanggal_publish if present, otherwise default to 09:00:00
                                        var timePart = '09:00:00';
                                        if (plan.tanggal_publish) {
                                            try { timePart = moment(plan.tanggal_publish).format('HH:mm:ss'); } catch(e) {}
                                        }
                                        var newTanggal = targetDate + ' ' + timePart; // 'YYYY-MM-DD HH:mm:ss'

                                        // Prepare payload using required fields the controller validates
                                        var payload = {
                                            judul: plan.judul || '',
                                            // brand may be array or string; prefer sending array when possible
                                            brand: plan.brand && Array.isArray(plan.brand) ? plan.brand : (plan.brand ? [plan.brand] : null),
                                            deskripsi: plan.deskripsi || null,
                                            caption: plan.caption || null,
                                            mention: plan.mention || null,
                                            tanggal_publish: newTanggal,
                                            platform: plan.platform && Array.isArray(plan.platform) ? plan.platform : (plan.platform ? (typeof plan.platform === 'string' && plan.platform.indexOf(',') !== -1 ? plan.platform.split(',').map(function(s){ return s.trim(); }) : [plan.platform]) : []),
                                            status: plan.status || 'Draft',
                                            konten_pilar: plan.konten_pilar || null,
                                            jenis_konten: plan.jenis_konten && Array.isArray(plan.jenis_konten) ? plan.jenis_konten : (plan.jenis_konten ? [plan.jenis_konten] : []),
                                            target_audience: plan.target_audience || null,
                                            link_asset: plan.link_asset || null,
                                            link_publikasi: plan.link_publikasi || null,
                                            catatan: plan.catatan || null,
                                            assigned_to: plan.assigned_to || null,
                                            _method: 'PUT'
                                        };

                                        // Remove null values to avoid sending unwanted nulls
                                        Object.keys(payload).forEach(function(k){ if (payload[k] === null) delete payload[k]; });

                                        $.ajax({ url: '/marketing/content-plan/' + id, method: 'POST', data: payload })
                                            .done(function(res){
                                                try { rowData.tanggal_publish = newTanggal; $moved.data('rowData', rowData); } catch(e){}
                                                try { targetDt.row.add(rowData).draw(false); } catch(e) { console.error(e); }
                                                try { reloadAllTables(false); } catch(e) {}
                                            }).fail(function(xhr){
                                                // Show server-side validation messages when available
                                                try {
                                                    var msg = 'Gagal memindahkan content plan.';
                                                    if (xhr && xhr.responseJSON) {
                                                        if (xhr.responseJSON.errors) {
                                                            msg = Object.values(xhr.responseJSON.errors).map(function(v){ return Array.isArray(v) ? v.join('<br>') : v; }).join('<br>');
                                                        } else if (xhr.responseJSON.message) {
                                                            msg = xhr.responseJSON.message;
                                                        }
                                                    }
                                                    reloadAllTables(false);
                                                    Swal.fire('Error', msg, 'error');
                                                } catch(e) { console.error('drag fail handler', e); reloadAllTables(false); Swal.fire('Error','Gagal memindahkan content plan.','error'); }
                                            });
                                    } catch(err) {
                                        console.error('prepare update after fetch', err);
                                        reloadAllTables(false);
                                        Swal.fire('Error','Gagal memindahkan content plan.','error');
                                    }
                                }).fail(function(xhr){
                                    // cannot fetch plan -> revert UI
                                    console.error('fetch plan for move failed', xhr);
                                    reloadAllTables(false);
                                    var msg = 'Gagal memindahkan content plan.';
                                    try { if (xhr && xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message; } catch(e) {}
                                    Swal.fire('Error', msg, 'error');
                                });
                            } catch(err) { console.error('receive drag', err); reloadAllTables(false); }
                        }
                    }).disableSelection();
                }
                enableRowDragDrop();
            } catch(e) { console.error('enable drag/drop', e); }
        }).fail(function(){
            // on fail, leave empty tables
            console.error('Failed to fetch week data');
        });
    }

    function getWeekParams() {
        var params = {
            date_start: _defaultStart.format('DD/MM/YYYY'),
            date_end: _defaultEnd.format('DD/MM/YYYY')
        };
        var brands = $('#filterBrand').val(); if (brands && brands.length) params.filter_brand = brands;
        var platforms = $('#filterPlatform').val(); if (platforms && platforms.length) params.filter_platform = platforms;
        var status = $('#filterStatus').val(); if (status) params.filter_status = status;
        var kontenPilar = $('#filterKontenPilar').val(); if (kontenPilar) params.filter_konten_pilar = kontenPilar;
        return params;
    }

    function updateStatusStats(resp) {
        try {
            // Instead of counting only items in the current week payload, request the global
            // 'terlewat' list from the controller and use its length so the header shows all overdue items.
            try {
                var url = "{{ route('marketing.content-plan.status_list') }}";
                $.getJSON(url, { status: 'terlewat' }).done(function(r){
                    var rows = (r && r.data) ? r.data : [];
                    $('#stat_terlewat').text(rows.length);
                }).fail(function(){
                    // fallback to zero on error
                    $('#stat_terlewat').text(0);
                });
            } catch(e) {
                console.error('updateStatusStats fetch global count', e);
            }
        } catch(e) { console.error('updateStatusStats', e); }
    }

    // Open status list modal by fetching data from the controller endpoint
    function openStatusList(statusDisplay) {
        try {
            var params = getWeekParams();
            if (statusDisplay && statusDisplay.toString().toLowerCase() !== 'all') params.status = statusDisplay;
            // If requesting 'terlewat' (overdue) we want the full dataset, not limited to the current week
            // so remove any date_start/date_end filters that getWeekParams() may have added.
            try {
                if (params.status && params.status.toString().toLowerCase() === 'terlewat') {
                    delete params.date_start; delete params.date_end;
                }
            } catch(e) {}
            var url = "{{ route('marketing.content-plan.status_list') }}";
            $.getJSON(url, params).done(function(resp){
                var rows = (resp && resp.data) ? resp.data : [];
                var $tb = $('#statusListTable tbody'); $tb.empty();
                if (!rows.length) {
                    $tb.append('<tr><td colspan="3" class="text-muted small">No items</td></tr>');
                } else {
                    rows.forEach(function(it){
                        var $tr = $('<tr>').attr('data-id', it.id);
                        // Put Day and Time together: day on top, time in muted small text below
                        var dayHtml = it.day + '<div class="text-muted small mt-1">' + (it.time || '') + '</div>';
                        // Build badges for status and brand to show under Judul
                        var status = it.status || '';
                        var statusBadge = '';
                        if (status) {
                            var sKey = status.toString().toLowerCase();
                            var sClass = 'badge-secondary';
                            if (sKey === 'published') sClass = 'badge-success';
                            else if (sKey === 'scheduled') sClass = 'badge-warning';
                            else if (sKey === 'draft') sClass = 'badge-secondary';
                            else if (sKey === 'revisi') sClass = 'badge-danger';
                            else if (sKey === 'cancelled') sClass = 'badge-danger';
                            statusBadge = '<span class="badge ' + sClass + '" style="margin-right:6px">' + status + '</span>';
                        }
                        var brand = it.brand || '';
                        var brandBadge = '';
                        if (brand) {
                            // choose a color for known brands, default primary
                            var bLower = brand.toString().toLowerCase();
                            var bStyle = '';
                            if (bLower.indexOf('belova skin') !== -1) bStyle = 'background:#6f42c1;color:#fff;';
                            else if (bLower.indexOf('belova dental') !== -1) bStyle = 'background:#ff66b2;color:#fff;';
                            else if (bLower.indexOf('premiere') !== -1) bStyle = 'background:#0d6efd;color:#fff;';
                            else if (bLower.indexOf('bcl') !== -1) bStyle = 'background:#e83e8c;color:#fff;';
                            else if (bLower.indexOf('dr fika') !== -1) bStyle = 'background:#fd7e14;color:#fff;';
                            else bStyle = '';
                            brandBadge = '<span class="badge badge-light" style="' + bStyle + ';margin-right:6px">' + brand + '</span>';
                        }

                        var judulHtml = $('<div>').text(it.judul).html();
                        var badgeRow = '<div class="mt-1">' + statusBadge + brandBadge + '</div>';
                        $tr.append($('<td>').html(dayHtml));
                        $tr.append($('<td>').html(judulHtml + badgeRow));
                        $tr.append($('<td>').text(it.assigned));
                        // action: reschedule button
                        var aksiHtml = '<button class="btn btn-sm btn-outline-primary btn-reschedule" data-id="'+it.id+'">Reschedule</button>';
                        $tr.append($('<td>').html(aksiHtml));
                        $tb.append($tr);
                    });
                }
                $('#statusListModal').modal('show');
            }).fail(function(err){ console.error('statusList fetch error', err); });
        } catch(e) { console.error('openStatusList', e); }
    }

    // wire stat-card clicks to open the matching list (delegated)
    $(document).on('click', '.stat-card', function(e){
        try {
            var $card = $(this);
            if ($card.hasClass('stat-terlewat')) openStatusList('terlewat');
            else openStatusList('all');
        } catch(e) { console.error('stat-card click', e); }
    });

    // header stat click (compact card)
    $(document).on('click', '.header-stat-terlewat', function(e){
        try { openStatusList('terlewat'); } catch(e){ console.error(e); }
    });

    // Reschedule button in status list: open edit modal and close the status list modal
    $(document).on('click', '.btn-reschedule', function(e){
        e.stopPropagation();
        var id = $(this).data('id');
        try { $('#statusListModal').modal('hide'); } catch(er) {}
        if (id) openEditModal(id);
    });

    function reloadAllTables(resetPaging){
        // Try to update existing DataTable instances in-place to avoid rebuilding DOM
        try {
            var params = getWeekParams();
            $.get('{{ route('marketing.content-plan.week') }}', params).done(function(resp){
                window._latestWeekResp = resp;
                var grouped = resp && resp.data ? resp.data : {};
                try { updateStatusStats(resp); } catch(e) {}
                // If we have the expected number of table instances (7), update them in-place
                if (Array.isArray(contentPlanTables) && contentPlanTables.length === 7) {
                    var weekStart = _defaultStart.clone();
                    contentPlanTables.forEach(function(dt, i){
                        try {
                            var d = weekStart.clone().add(i, 'days');
                            var key = d.format('YYYY-MM-DD');
                            var rows = grouped[key] || [];
                            // Replace data without destroying table to keep layout stable
                            dt.clear();
                            dt.rows.add(rows);
                            // Update header stats for this table
                            try {
                                var tableNode = dt.table().node();
                                var $tableEl = $(tableNode);
                                var $card = $tableEl.closest('.card');
                                var displayDate = d.format('dddd, D MMMM YYYY');
                                var totalCount = Array.isArray(rows) ? rows.length : 0;
                                var publishedCount = 0;
                                try { publishedCount = rows.filter(function(r){ return (r.status||'').toLowerCase() === 'published'; }).length; } catch(e) {}
                                var statCls = 'default';
                                if (totalCount > 0) {
                                    if (publishedCount === totalCount) statCls = 'green';
                                    else if (publishedCount === 0) statCls = 'red';
                                    else statCls = 'yellow';
                                }
                                var statHtml = '<span class="day-stats ' + statCls + '">(' + publishedCount + '/' + totalCount + ')</span>';
                                $card.find('.card-header strong').html(displayDate + ' ' + statHtml);
                            } catch(e) { console.error('update header stats (refresh)', e); }
                            // preserve paging if requested
                            if (resetPaging) dt.page('first');
                            dt.draw(false);
                        } catch(e) { console.error('refresh dt', e); }
                    });
                } else {
                    // Fallback: rebuild completely
                    try { if (Array.isArray(contentPlanTables) && contentPlanTables.length) { contentPlanTables.forEach(function(t){ try{ t.destroy(); }catch(e){} }); contentPlanTables = []; } } catch(e) { console.error(e); }
                    buildWeekTables();
                }
            }).fail(function(){
                console.error('Failed to fetch week data');
            });
        } catch(e) { console.error('reloadAllTables error', e); }
    }

    // build week tables initially
    buildWeekTables();

    // --- Month & Week picker logic ---
    function populateMonthPicker() {
        var $m = $('#monthPicker'); $m.empty();
        var now = moment();
        for (var i=0;i<12;i++){
            var d = now.clone().add(i, 'months');
            var val = d.format('YYYY-MM');
            var txt = d.format('MMMM YYYY');
            $m.append($('<option>').attr('value', val).text(txt));
        }
    }

    function getWeekNumberForDateInMonth(d) {
        var day = d.date();
        if (day <= 7) return 1;
        if (day <= 14) return 2;
        if (day <= 21) return 3;
        return 4;
    }

    function applyMonthWeekRange(monthVal, weekNum) {
        try {
            var parts = monthVal.split('-');
            var y = parseInt(parts[0],10); var m = parseInt(parts[1],10);
            var startDay = 1;
            if (weekNum === 1) startDay = 1;
            else if (weekNum === 2) startDay = 8;
            else if (weekNum === 3) startDay = 15;
            else startDay = 22;
            var start = moment([y, m-1, startDay]);
            var end = null;
            if (weekNum < 4) end = start.clone().add(6, 'days');
            else end = moment([y, m-1]).endOf('month');
            var monthEnd = moment([y,m-1]).endOf('month');
            if (end.isAfter(monthEnd)) end = monthEnd.clone();
            _defaultStart = start.clone();
            _defaultEnd = end.clone();
            try {
                var dr = $('#filterDateRange').data('daterangepicker');
                if (dr) {
                    dr.setStartDate(_defaultStart);
                    dr.setEndDate(_defaultEnd);
                    $('#filterDateRange').val(_defaultStart.format('DD/MM/YYYY') + ' - ' + _defaultEnd.format('DD/MM/YYYY'));
                }
            } catch(e){}
            if (typeof contentPlanTables !== 'undefined' && contentPlanTables.length===7) {
                reloadAllTables(true);
            } else {
                buildWeekTables();
                reloadAllTables();
            }
        } catch(e) { console.error('applyMonthWeekRange', e); }
    }

    try {
        populateMonthPicker();
        var today = moment();
        $('#monthPicker').val(today.format('YYYY-MM'));
        $('#weekPicker').val(getWeekNumberForDateInMonth(today));
    } catch(e) { console.error(e); }

    $('#monthPicker, #weekPicker').on('change', function(){
        var mv = $('#monthPicker').val(); var w = parseInt($('#weekPicker').val(),10);
        applyMonthWeekRange(mv, w);
    });

    $('#btnPrevWeek, #btnNextWeek').on('click', function(){
        setTimeout(function(){
            try {
                var cur = _defaultStart.clone();
                $('#monthPicker').val(cur.format('YYYY-MM'));
                $('#weekPicker').val(getWeekNumberForDateInMonth(cur));
            } catch(e){}
        }, 50);
    });

    // Auto-refresh: reload all tables every 10 seconds (pauses when tab is hidden)
    var autoRefreshInterval = 10000; // milliseconds
    var autoRefreshTimer = null;
    function startAutoRefresh() {
        try {
            if (autoRefreshTimer) clearInterval(autoRefreshTimer);
            autoRefreshTimer = setInterval(function(){
                try {
                    if (document.hidden) return; // don't refresh when tab not visible
                    reloadAllTables(false);
                } catch(e) { console.error('auto-refresh tick', e); }
            }, autoRefreshInterval);
        } catch(e) { console.error('startAutoRefresh', e); }
    }
    function stopAutoRefresh() {
        try { if (autoRefreshTimer) { clearInterval(autoRefreshTimer); autoRefreshTimer = null; } } catch(e) { }
    }
    // start auto-refresh by default
    startAutoRefresh();
    // stop/start auto-refresh when page visibility changes and when navigating away
    // Avoid using the 'unload' event directly because some browsers block it via Permissions Policy.
    document.addEventListener('visibilitychange', function(){
        if (document.hidden) stopAutoRefresh(); else startAutoRefresh();
    });
    window.addEventListener('pagehide', stopAutoRefresh);
    window.addEventListener('beforeunload', stopAutoRefresh);
    // Prev / Next week navigation
    $(document).on('click', '#btnPrevWeek', function() {
        try {
            _defaultStart = _defaultStart.clone().subtract(7, 'days');
            _defaultEnd = _defaultStart.clone().endOf('isoWeek');
            var drp = $('#filterDateRange').data('daterangepicker');
            if (drp) {
                drp.setStartDate(_defaultStart);
                drp.setEndDate(_defaultEnd);
            }
            $('#filterDateRange').val(_defaultStart.format('DD/MM/YYYY') + ' - ' + _defaultEnd.format('DD/MM/YYYY'));
            if (typeof contentPlanTables !== 'undefined' && contentPlanTables.length) {
                contentPlanTables.forEach(function(t){ try{ t.destroy(); }catch(e){} });
                contentPlanTables = [];
            }
            buildWeekTables();
        } catch(e) { console.error('PrevWeek error', e); }
    });

    $(document).on('click', '#btnNextWeek', function() {
        try {
            _defaultStart = _defaultStart.clone().add(7, 'days');
            _defaultEnd = _defaultStart.clone().endOf('isoWeek');
            var drp = $('#filterDateRange').data('daterangepicker');
            if (drp) {
                drp.setStartDate(_defaultStart);
                drp.setEndDate(_defaultEnd);
            }
            $('#filterDateRange').val(_defaultStart.format('DD/MM/YYYY') + ' - ' + _defaultEnd.format('DD/MM/YYYY'));
            if (typeof contentPlanTables !== 'undefined' && contentPlanTables.length) {
                contentPlanTables.forEach(function(t){ try{ t.destroy(); }catch(e){} });
                contentPlanTables = [];
            }
            buildWeekTables();
        } catch(e) { console.error('NextWeek error', e); }
    });

    // Global Add button handler removed — use per-day Add buttons instead

    // Add Content Plan from a specific day card header
    $(document).on('click', '.btn-add-card', function() {
        var date = $(this).data('date'); // expected YYYY-MM-DD
        $('#contentPlanForm')[0].reset();
        $('#contentPlanModalLabel').text('Tambah Content Plan');
        try { $('#tab-edit-tab').trigger('click'); } catch(e){}
        $('#contentPlanModal').modal('show');
        $('#contentPlanForm').attr('data-action', 'store');
        $('#contentPlanForm').attr('data-id', '');
        // only reset select2 inputs inside the modal to avoid triggering global filters
        $('#contentPlanModal .select2').val(null).trigger('change');
        $('#brand').val(null).trigger('change');
        $('#assigned_to').val(null).trigger('change');
        $('#konten_pilar').val(null).trigger('change');
        // Clear Brief-related fields so previous brief content doesn't persist
        try {
            // destroy any existing summernote instance to ensure clean init later
            try { if ($.fn && $.fn.summernote) { $('#cb_isi_konten').summernote('destroy'); } } catch(e) {}
            $('#cb_content_plan_id').val('');
            $('#cb_id').val('');
            $('#cb_headline').val('');
            $('#cb_sub_headline').val('');
            // clear summernote textarea
            try{ $('#cb_isi_konten').val(''); } catch(e) {}
            // clear visual preview and file input
            try { $('#cb_preview').empty(); $('#cb_visual_references').val(''); } catch(e) {}
            // reset temporary holder
            window._cbLoadedIsi = null;
        } catch(e) {}
        // clear link publikasi inputs
        $('#link_publikasi_wrapper').find('.link-input-row').remove();
        // default status to Scheduled when creating a new content plan
        try { $('#status').val('Scheduled').trigger('change'); } catch(e) {}
        // set tanggal_publish to the clicked date (default time 09:00)
        try {
            if (date) {
                $('#tanggal_publish_date').val(date);
                $('#tanggal_publish_time').val('09:00');
            }
        } catch(e) { console.error('set date on add-card', e); }
    });

    // Store/Update Content Plan
        $('#contentPlanForm').on('submit', function(e) {
        var $btn = $('#contentPlanModal .btn-save-plan');
        $btn.prop('disabled', true);
        var originalText = $btn.html();
        $btn.html('<span class="spinner-border spinner-border-sm mr-1"></span> Menyimpan...');
        e.preventDefault();
        let action = $(this).attr('data-action');
        let id = $(this).attr('data-id');
        let url = action === 'store' ? '{{ route('marketing.content-plan.store') }}' : `/marketing/content-plan/${id}`;
        let method = action === 'store' ? 'POST' : 'POST'; // Always POST, use _method for PUT
        let form = this;
            let formData = new FormData(form);
            // combine split date/time inputs into single tanggal_publish value
            try {
                var d = $('#tanggal_publish_date').val();
                var t = $('#tanggal_publish_time').val() || '09:00';
                if (d) {
                    var timePart = (t && t.length === 5) ? t + ':00' : (t || '09:00:00');
                    var combined = d + ' ' + timePart; // YYYY-MM-DD HH:mm:ss
                    formData.set('tanggal_publish', combined);
                }
            } catch(e) { console.error('combine tanggal_publish', e); }
        if (action === 'update') {
            formData.append('_method', 'PUT');
        }
        $.ajax({
            url: url,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
                // get saved plan id (response may include created object)
                var planId = null;
                try { planId = (res && res.data && res.data.id) ? res.data.id : (res && res.id ? res.id : null); } catch(e) { planId = null; }
                if (!planId && res && res.success && res.data && res.data.id) planId = res.data.id;
                if (!planId && !planId) {
                    // fallback to existing form id (update case)
                    try { planId = $('#contentPlanForm').attr('data-id') || null; } catch(e) {}
                }

                // ensure brief knows the content_plan_id
                try { if (planId) $('#cb_content_plan_id').val(planId); } catch(e) {}

                // Prepare promises: brief and report (if present)
                var briefPromise = $.Deferred().resolve().promise();
                try {
                    if (hasBriefContent()) {
                        // call submitBrief but don't auto-close modal yet
                        briefPromise = submitBrief(null, { closeOnComplete: false });
                    }
                } catch(e) { briefPromise = $.Deferred().resolve().promise(); }

                var reportPromise = $.Deferred().resolve().promise();
                try {
                    // determine whether report has meaningful data (likes/comments non-zero or cr_id present)
                    var crId = $('#cr_id').val();
                    var planIdForReport = $('#cr_content_plan_id').val() || planId;
                    var reportHasData = (crId || (parseInt($('#cr_likes').val()||0) || parseInt($('#cr_comments').val()||0) || parseInt($('#cr_saves').val()||0) || parseInt($('#cr_shares').val()||0) || parseInt($('#cr_reach').val()||0) || parseInt($('#cr_impressions').val()||0)) );
                    if (reportHasData) {
                        var fdReport = {
                            content_plan_id: planIdForReport,
                            likes: $('#cr_likes').val() || 0,
                            comments: $('#cr_comments').val() || 0,
                            saves: $('#cr_saves').val() || 0,
                            shares: $('#cr_shares').val() || 0,
                            reach: $('#cr_reach').val() || 0,
                            impressions: $('#cr_impressions').val() || 0,
                            recorded_at: $('#cr_recorded_at').val() ? $('#cr_recorded_at').val().replace('T',' ') : null
                        };
                        if (!crId) {
                            reportPromise = $.post('{{ route('marketing.content-report.store') }}', fdReport);
                        } else {
                            reportPromise = $.ajax({ url: `/marketing/content-report/${crId}`, method: 'PUT', data: fdReport });
                        }
                    }
                } catch(e) { reportPromise = $.Deferred().resolve().promise(); }

                // When both complete, close modal, reload tables and show success
                $.when(briefPromise, reportPromise).always(function(){
                    try { $('#contentPlanModal').modal('hide'); } catch(e) {}
                    reloadAllTables();
                    Swal.fire('Sukses', 'Data berhasil disimpan!', 'success');
                    $btn.prop('disabled', false);
                    $btn.html(originalText);
                });
            },
            error: function(xhr) {
                let msg = 'Terjadi kesalahan.';
                if(xhr.responseJSON && xhr.responseJSON.errors) {
                    msg = Object.values(xhr.responseJSON.errors).join('<br>');
                }
                Swal.fire('Error', msg, 'error');
                $btn.prop('disabled', false);
                $btn.html(originalText);
            }
        });
    });

    // Helper to open Edit modal by id
    function openEditModal(id) {
        if (!id) return;
        $.get(`/marketing/content-plan/${id}`, function(res) {
            let data = res;
            $('#contentPlanForm')[0].reset();
            $('#contentPlanModalLabel').text('Edit Content Plan');
            try { $('#tab-edit-tab').trigger('click'); } catch(e){}
            $('#contentPlanModal').modal('show');
            $('#contentPlanForm').attr('data-action', 'update');
            $('#contentPlanForm').attr('data-id', id);
            $('#judul').val(data.judul);
            if (Array.isArray(data.brand)) {
                $('#brand').val(data.brand).trigger('change');
            } else if (data.brand) {
                $('#brand').val([data.brand]).trigger('change');
            } else {
                $('#brand').val(null).trigger('change');
            }
            // Format tanggal_publish to separate date and time inputs
                try {
                    if (data.tanggal_publish) {
                        var m = moment(data.tanggal_publish);
                        $('#tanggal_publish_date').val(m.format('YYYY-MM-DD'));
                        $('#tanggal_publish_time').val(m.format('HH:mm'));
                    } else {
                        $('#tanggal_publish_date').val('');
                        $('#tanggal_publish_time').val('09:00');
                    }
                } catch(e) { console.error('set edit tanggal_publish', e); $('#tanggal_publish_date').val(''); $('#tanggal_publish_time').val('09:00'); }
            $('#platform').val(data.platform).trigger('change');
            $('#status').val(data.status);
            $('#jenis_konten').val(data.jenis_konten).trigger('change');
            $('#konten_pilar').val(data.konten_pilar).trigger('change');
            // assigned_to may be null
            try { $('#assigned_to').val(data.assigned_to || '').trigger('change'); } catch(e) {}
            $('#link_asset').val(data.link_asset);
            // render per-platform link inputs
            try { renderLinkInputs(data.platform, data.link_publikasi); } catch(e) {}
            // populate caption and mention if present
            try {
                $('#caption').val(data.caption || '');
                $('#mention').val(data.mention || '');
            } catch(e) {}
            // set hidden brief keys so submitBrief has content_plan_id and brief id if exists
            try { $('#cb_content_plan_id').val(data.id || ''); } catch(e) {}
            try { $('#cb_id').val((data.brief && data.brief.id) ? data.brief.id : (data.brief_id || '')); } catch(e) {}
            // Update brief tab indicator in case brief fields are present on this record
            try { updateBriefTabIndicator(); } catch(e) {}
                // Prepare brief fields if present in response for later summernote init
                try {
                    // destroy any existing summernote to ensure clean state
                    try { if ($.fn && $.fn.summernote) { $('#cb_isi_konten').summernote('destroy'); } } catch(e) {}
                    // clear textarea now; content will be applied from window._cbLoadedIsi when summernote initializes
                    try { $('#cb_isi_konten').val(''); } catch(e) {}
                    // Common response keys that may contain brief content
                    var briefHtml = data.isi_konten || data.cb_isi_konten || data.brief || data.brief_content || null;
                    if (briefHtml) {
                        // store temporarily; will be applied after summernote initializes
                        window._cbLoadedIsi = briefHtml;
                    } else {
                        window._cbLoadedIsi = null;
                    }
                    // populate headline/subheadline if available
                    try { if (data.headline) $('#cb_headline').val(data.headline); } catch(e) {}
                    try { if (data.sub_headline) $('#cb_sub_headline').val(data.sub_headline); } catch(e) {}
                    // render existing visual references (server-stored) into preview
                    try {
                        var serverImgs = [];
                        if (Array.isArray(data.visual_references)) serverImgs = data.visual_references;
                        else if (data.brief && Array.isArray(data.brief.visual_references)) serverImgs = data.brief.visual_references;
                        // clear any existing client-side preview state
                        try { window._cbDataTransfer = new DataTransfer(); } catch(e) { window._cbDataTransfer = {files: []}; }
                        var $preview = $('#cb_preview');
                        $preview.empty();
                        // track removed server images separately from client uploads
                        window._cbRemoved = [];
                        if (serverImgs && serverImgs.length) {
                            serverImgs.forEach(function(p, idx){
                                try {
                                    var src = (p && p.indexOf('http') === 0) ? p : ('/storage/' + p);
                                    var $wrap = $('<div class="position-relative border rounded bg-white" style="width:100%;height:220px;overflow:hidden;display:block;margin-bottom:12px"></div>');
                                    var $remove = $('<button type="button" class="btn btn-sm btn-danger position-absolute cb-server-remove" title="Hapus" style="top:6px;right:6px;padding:2px 6px">×</button>');
                                    // store original path on element for later removal
                                    $remove.data('path', p);
                                    var $img = $('<img>').attr('src', src).attr('data-full', src).css({'width':'100%','height':'100%','object-fit':'cover','cursor':'zoom-in'});
                                    $wrap.append($img).append($remove);
                                    $preview.append($wrap);
                                } catch(ie){ console.warn('render server img', ie); }
                            });
                        }
                    } catch(e) { console.error('render existing visual refs', e); }
                } catch(e) { console.error('prepare brief fields', e); }
        });
    }

    // Keep legacy button wiring but delegate to helper (button hidden in UI)
    $(document).on('click', '.btn-edit', function() {
        var id = $(this).data('id');
        openEditModal(id);
    });

    // Open edit modal when clicking the Judul cell
    $(document).on('click', '.contentPlanTable tbody', function(e){
        // delegated handler mounted on tbody; ignore if clicked element is a button/link inside the cell
    });
    $(document).on('click', '.contentPlanTable tbody td.judul-col', function(e){
        // prevent handling clicks on badges, links or buttons inside the cell
        if ($(e.target).is('a') || $(e.target).closest('button').length || $(e.target).is('button') || $(e.target).closest('.badge').length) return;
        var $td = $(this);
        var $tr = $td.closest('tr');
        // try to get id from DataTable row data
        try {
            var $table = $td.closest('table');
            if ($.fn.dataTable.isDataTable($table)) {
                var dt = $table.DataTable();
                var rowData = dt.row($tr).data() || {};
                var id = rowData.id || $tr.find('[data-id]').data('id');
                if (id) {
                    openEditModal(id);
                }
            }
        } catch(e) { console.error('open edit from judul', e); }
    });

    // allow keyboard activation on the judul clickable (Enter or Space)
    $(document).on('keydown', '.contentPlanTable tbody td.judul-col .judul-clickable', function(e){
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            var $clickable = $(this);
            var $tr = $clickable.closest('tr');
            try {
                var $table = $clickable.closest('table');
                if ($.fn.dataTable.isDataTable($table)) {
                    var dt = $table.DataTable();
                    var rowData = dt.row($tr).data() || {};
                    var id = rowData.id || $tr.find('[data-id]').data('id');
                    if (id) openEditModal(id);
                }
            } catch(err) { console.error('keyboard open edit', err); }
        }
    });

    // Tab activation: show/hide footer buttons depending on active tab
    function updateModalFooterButtons() {
        var action = $('#contentPlanForm').attr('data-action') || '';
        var briefTabActive = ($('#tab-brief').hasClass('show') || $('#tab-brief').hasClass('active'));
        if (briefTabActive) {
            $('.btn-save-plan').hide();
            // only show Save Brief when editing an existing content plan
            if (action && action.toLowerCase() !== 'store') {
                $('.btn-save-brief').show();
                $('.btn-save-brief').prop('disabled', false);
            } else {
                $('.btn-save-brief').hide();
                $('.btn-save-brief').prop('disabled', true);
            }
        } else {
            $('.btn-save-plan').show();
            $('.btn-save-brief').hide();
            $('.btn-save-brief').prop('disabled', true);
        }
    }
    // update when tabs are clicked (Bootstrap 4/5 compat)
    $('#tab-edit-tab').on('click shown.bs.tab shown.bs.dropdown', updateModalFooterButtons);
    $('#tab-brief-tab').on('click shown.bs.tab shown.bs.dropdown', updateModalFooterButtons);
    // ensure correct state when modal opens
    $('#contentPlanModal').on('shown.bs.modal', function(){ updateModalFooterButtons(); updateBriefTabIndicator(); });

    // Brief tab indicator: show a green check when brief content exists
    function hasBriefContent() {
        try {
            var h = $('#cb_headline').val() || '';
            var sh = $('#cb_sub_headline').val() || '';
            var isi = '';
            var $isiEl = $('#cb_isi_konten');
            if ($.fn && $.fn.summernote && $isiEl && $isiEl.data('summernote')) {
                try { isi = $isiEl.summernote('code') || ''; } catch(e) { isi = $isiEl.val() || ''; }
            } else {
                isi = $isiEl.val() || '';
            }
            var files = 0;
            var fi = $('#cb_visual_references')[0];
            if (fi && fi.files) files = fi.files.length;
            return $.trim(h) !== '' || $.trim(sh) !== '' || $.trim(isi) !== '' || files > 0;
        } catch(e) { return false; }
    }

    function updateBriefTabIndicator() {
        try {
            if (hasBriefContent()) { $('#tab-brief-tab .brief-check').show(); $('#tab-brief-tab').addClass('text-success'); }
            else { $('#tab-brief-tab .brief-check').hide(); $('#tab-brief-tab').removeClass('text-success'); }
        } catch(e) { }
    }

    // update indicator when brief fields change
    $(document).on('input', '#cb_headline, #cb_sub_headline, #cb_isi_konten', function(){ updateBriefTabIndicator(); });
    $(document).on('change', '#cb_visual_references', function(){ updateBriefTabIndicator(); });

    // Delete Content Plan
    $(document).on('click', '.btn-delete', function() {
        let id = $(this).data('id');
        Swal.fire({
            title: 'Yakin hapus?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, hapus!'
        }).then((result) => {
            if(result.isConfirmed) {
                $.ajax({
                    url: `/marketing/content-plan/${id}`,
                    method: 'DELETE',
                    success: function() {
                        reloadAllTables();
                        Swal.fire('Terhapus!', 'Data berhasil dihapus.', 'success');
                    },
                    error: function() {
                        Swal.fire('Error', 'Gagal menghapus data.', 'error');
                    }
                });
            }
        });
    });

    // Publish Content Plan (set status to 'Published') via existing inline-update endpoint
    $(document).on('click', '.btn-publish', function() {
        var id = $(this).data('id');
        if (!id) return;
        var $btn = $(this);
        Swal.fire({
            title: 'Publish content?',
            text: 'This will set the status to Published.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, publish'
        }).then(function(result){
            if (!result.isConfirmed) return;
            $btn.prop('disabled', true);
            $.post(`/marketing/content-plan/${id}/inline-update`, { status: 'Published' })
                .done(function() {
                    reloadAllTables(false);
                    Swal.fire('Terpublikasi!', 'Status diubah menjadi Published.', 'success');
                })
                .fail(function(xhr) {
                    var msg = 'Gagal mengubah status.';
                    if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                    Swal.fire('Error', msg, 'error');
                })
                .always(function(){
                    $btn.prop('disabled', false);
                });
        });
    });
        // Clickable status badge: open small modal to change status
        $(document).on('click', '.status-badge', function(e){
            e.stopPropagation();
            var id = $(this).data('id');
            var current = $(this).text().trim();
            $('#statusModal input[name="plan_id"]').val(id);
            try { $('#status_select').val(current); } catch(e) {}
            // ensure modal is attached to body to avoid z-index/overflow issues
            try { $('#statusModal').appendTo('body'); } catch(e) {}
            $('#statusModal').modal('show');
        });

        // Submit status change
        $('#statusForm').on('submit', function(e){
            e.preventDefault();
            var $btn = $('#statusForm button[type="submit"]');
            var id = $('#statusModal input[name="plan_id"]').val();
            var status = $('#status_select').val();
            $btn.prop('disabled', true).text('Menyimpan...');
            $.ajax({
                url: '/marketing/content-plan/' + id + '/inline-update',
                method: 'POST',
                data: { status: status },
            }).done(function(res){
                $('#statusModal').modal('hide');
                reloadAllTables(false);
                Swal.fire('Sukses', 'Status berhasil diperbarui.', 'success');
            }).fail(function(xhr){
                var msg = 'Gagal memperbarui status.';
                try {
                    if (xhr && xhr.responseJSON) {
                        if (xhr.responseJSON.errors) {
                            msg = Object.values(xhr.responseJSON.errors).map(function(v){ return Array.isArray(v) ? v.join('<br>') : v; }).join('<br>');
                        } else if (xhr.responseJSON.message) {
                            msg = xhr.responseJSON.message;
                        }
                    }
                } catch(e){}
                Swal.fire('Error', msg, 'error');
                reloadAllTables(false);
            }).always(function(){ $btn.prop('disabled', false).text('Simpan'); });
        });

    // Initialize modal select2s once for smoother rendering (guarded)
    try { if ($.fn && $.fn.select2) { $('#brand').select2({ dropdownParent: $('#contentPlanModal'), width: '100%', multiple: true, placeholder: 'Pilih Brand' }); } } catch(e) {}
    try { if ($.fn && $.fn.select2) { $('#platform').select2({ dropdownParent: $('#contentPlanModal'), width: '100%', multiple: true, placeholder: 'Pilih Platform' }); } } catch(e) {}
    try { if ($.fn && $.fn.select2) { $('#jenis_konten').select2({ dropdownParent: $('#contentPlanModal'), width: '100%', multiple: true, placeholder: 'Pilih Jenis Konten' }); } } catch(e) {}
    try { if ($.fn && $.fn.select2) { $('#status').select2({ dropdownParent: $('#contentPlanModal'), width: '100%' }); } } catch(e) {}
    try { if ($.fn && $.fn.select2) { $('#konten_pilar').select2({ dropdownParent: $('#contentPlanModal'), width: '100%', placeholder: 'Pilih Konten Pilar', allowClear: true }); } } catch(e) {}
    // Assigned to select2 (guarded)
    try { if ($.fn && $.fn.select2) { $('#assigned_to').select2({ dropdownParent: $('#contentPlanModal'), width: '100%', placeholder: 'Assign to', allowClear: true }); } } catch(e) {}

    // Render link_publikasi inputs for selected platforms inside the modal
    function renderLinkInputs(platforms, existingLinks) {
        var $wrap = $('#link_publikasi_wrapper');
        $wrap.find('.link-input-row').remove();
        if (!platforms) return;
        var arr = Array.isArray(platforms) ? platforms : (typeof platforms === 'string' ? (platforms.indexOf(',') !== -1 ? platforms.split(',').map(s=>s.trim()) : [platforms]) : []);
        // normalize existingLinks into a mapping {Platform: url}
        var map = {};
        if (existingLinks) {
            if (Array.isArray(existingLinks)) {
                if (existingLinks.length === arr.length) {
                    arr.forEach(function(p, i){ map[p] = existingLinks[i] || ''; });
                }
            } else if (typeof existingLinks === 'object') {
                map = existingLinks;
            } else if (typeof existingLinks === 'string') {
                var parts = [];
                if (existingLinks.indexOf('||') !== -1) parts = existingLinks.split('||').map(s=>s.trim());
                else if (existingLinks.indexOf(',') !== -1) parts = existingLinks.split(',').map(s=>s.trim());
                else parts = [existingLinks];
                if (parts.length === arr.length) {
                    arr.forEach(function(p,i){ map[p] = parts[i] || ''; });
                } else if (parts.length > 0) {
                    map[arr[0]] = parts[0];
                }
            }
        }

        arr.forEach(function(p){
            var val = map[p] || '';
            var safeName = p.replace(/\s+/g,'_');
            var $col = $(`<div class="col-md-6 mb-2 link-input-row">
                <label class="form-label">${p} - Link Publikasi</label>
                <input type="text" class="form-control" name="link_publikasi[${p}]" value="${val}">
            </div>`);
            $wrap.append($col);
        });
    }

    // update link inputs when platform selection changes inside modal
    $('#platform').on('change', function(){
        var val = $(this).val();
        renderLinkInputs(val, null);
    });

    // Setup CSRF for AJAX
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // Handle inline status change
    $(document).on('change', '.inline-status', function() {
        var $select = $(this);
        var id = $select.data('id');
        var value = $select.val();
        var original = $select.prop('disabled');
        $select.prop('disabled', true);
        // update styling immediately for better UX
        function applyStatusStyle($selectEl, val) {
            var map = {
                'draft': {bg: '#6c757d', color: '#ffffff'},
                'scheduled': {bg: '#ffc107', color: '#212529'},
                'published': {bg: '#28a745', color: '#ffffff'},
                'cancelled': {bg: '#dc3545', color: '#ffffff'}
            };
            var key = val ? val.toLowerCase() : '';
            var $wrap = $selectEl.closest('.inline-status-wrap');
            if ($wrap.length === 0) return;
            if (map[key]) {
                $wrap.css({'background-color': map[key].bg, 'color': map[key].color, 'border-color':'transparent'});
                // ensure select inherits text color
                $wrap.find('select').css({'color': map[key].color});
            } else {
                $wrap.css({'background-color':'', 'color':'', 'border-color':''});
                $wrap.find('select').css({'color':''});
            }
        }
        applyStatusStyle($select, value);

        $.post(`/marketing/content-plan/${id}/inline-update`, { status: value })
            .done(function(res) {
                // Optionally show a small toast or just reload the row
                reloadAllTables(false);
            })
            .fail(function(xhr) {
                var msg = 'Gagal menyimpan perubahan.';
                if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                Swal.fire('Error', msg, 'error');
                reloadAllTables(false);
            })
            .always(function() {
                $select.prop('disabled', false);
            });
    });
    
            // Statistics button removed — report modal is accessible via Edit modal's Report tab

    // Compute and display ERI/ERR in modal based on current numeric input values
    function computeAndShowERIERR() {
        var likes = parseInt($('#cr_likes').val() || 0, 10);
        var comments = parseInt($('#cr_comments').val() || 0, 10);
        var saves = parseInt($('#cr_saves').val() || 0, 10);
        var shares = parseInt($('#cr_shares').val() || 0, 10);
        var reach = parseInt($('#cr_reach').val() || 0, 10);
        var impressions = parseInt($('#cr_impressions').val() || 0, 10);
        var interactions = (likes || 0) + (comments || 0) + (saves || 0) + (shares || 0);
        var eri = (impressions > 0) ? (interactions / impressions) * 100 : 0;
        var err = (reach > 0) ? (interactions / reach) * 100 : 0;
        // display with 2 decimals
        $('#cr_eri').val(eri.toFixed(2));
        $('#cr_err').val(err.toFixed(2));
    }

    // Bind live calculation to input changes inside the modal
    $('#contentPlanModal').on('input', '#cr_likes, #cr_comments, #cr_saves, #cr_shares, #cr_reach, #cr_impressions', function() {
        computeAndShowERIERR();
    });

    // Render history table on the right side of the modal
    function renderHistory(reports) {
        var $tb = $('#cr_history_tbody');
        $tb.empty();
        if (!reports || reports.length === 0) {
            $tb.append('<tr><td colspan="4" class="text-muted small">No history</td></tr>');
            return;
        }
        // reports expected in descending order (newest first). Growth is computed
        // compared to the previous record (the next item in the array, which is older).
        reports.forEach(function(r, idx) {
            var when = r.recorded_at ? r.recorded_at : r.created_at;
            // format when using moment (local timezone)
            var whenFormatted = '';
            if (when) {
                try {
                    whenFormatted = moment(when).locale('id').format('D MMM YYYY') + '<br><small style="color:#6c757d">' + moment(when).format('HH.mm') + '</small>';
                } catch (e) {
                    whenFormatted = when;
                }
            }
            var eri = r.eri != null ? Number(r.eri) : 0;
            var err = r.err != null ? Number(r.err) : 0;

            // compute growth compared to previous (older) record
            var growthHtml = '<span class="text-muted">—</span>';
            var prev = reports[idx+1]; // older record
            if (prev && prev.eri != null) {
                var prevEri = Number(prev.eri);
                if (prevEri === 0) {
                    // cannot compute percent change from 0
                    growthHtml = '<span class="text-muted">N/A</span>';
                } else {
                    var change = ((eri - prevEri) / prevEri) * 100;
                    var up = change > 0;
                    var cls = up ? 'text-success' : (change < 0 ? 'text-danger' : 'text-muted');
                    var arrow = change > 0 ? '↑' : (change < 0 ? '↓' : '');
                    growthHtml = `<span class="${cls}">${arrow} ${Math.abs(change).toFixed(2)}%</span>`;
                }
            }

            var row = `<tr data-id="${r.id}" style="cursor:pointer"><td style="white-space:nowrap">${whenFormatted}</td><td>${eri.toFixed(2)}</td><td>${err.toFixed(2)}</td><td style="white-space:nowrap">${growthHtml}</td></tr>`;
            $tb.append(row);
        });
    }

    // Click a history row to load that report into the form for viewing/editing
    $('#cr_history_tbody').on('click', 'tr', function() {
        var id = $(this).data('id');
        if (!id) return;
        // mark selected row
        $('#cr_history_tbody tr').removeClass('table-active');
        $(this).addClass('table-active');
        // Fetch the report by id from server to get full fields (or use cr_reports cache if available)
        $.get(`/marketing/content-report/${id}`, function(report){
            if (report) {
                $('#cr_id').val(report.id);
                $('#cr_likes').val(report.likes || 0);
                $('#cr_comments').val(report.comments || 0);
                $('#cr_saves').val(report.saves || 0);
                $('#cr_shares').val(report.shares || 0);
                $('#cr_reach').val(report.reach || 0);
                $('#cr_impressions').val(report.impressions || 0);
                $('#cr_eri').val(report.eri != null ? Number(report.eri).toFixed(2) : '0.00');
                $('#cr_err').val(report.err != null ? Number(report.err).toFixed(2) : '0.00');
                if (report.recorded_at) {
                    var dt = report.recorded_at.replace(' ', 'T').slice(0,16);
                    $('#cr_recorded_at').val(dt);
                }
            }
        }).fail(function(){
            Swal.fire('Error','Gagal mengambil report','error');
        });
    });

    // 'New' button — clear the form and prepare to create a new report
    $('#cr_new_btn').on('click', function() {
        $('#cr_id').val('');
        $('#cr_likes').val(0);
        $('#cr_comments').val(0);
        $('#cr_saves').val(0);
        $('#cr_shares').val(0);
        $('#cr_reach').val(0);
        $('#cr_impressions').val(0);
        computeAndShowERIERR();
        // set recorded_at to now
        var now = new Date();
        var local = now.toISOString().slice(0,16);
        $('#cr_recorded_at').val(local);
        // visually deselect any selected history row
        $('#cr_history_tbody tr').removeClass('table-active');
    });

    // Submit content plan report (create or update)
    $('#contentPlanReportForm').on('submit', function(e) {
        e.preventDefault();
        var id = $('#cr_id').val();
        var planId = $('#cr_content_plan_id').val();
        var fd = {
            content_plan_id: planId,
            likes: $('#cr_likes').val() || 0,
            comments: $('#cr_comments').val() || 0,
            saves: $('#cr_saves').val() || 0,
            shares: $('#cr_shares').val() || 0,
            reach: $('#cr_reach').val() || 0,
            impressions: $('#cr_impressions').val() || 0,
            // engagement rate will be calculated server-side (eri/err).
            recorded_at: $('#cr_recorded_at').val() ? $('#cr_recorded_at').val().replace('T',' ') : null
        };

        if (!id) {
            // create
            $.post('{{ route('marketing.content-report.store') }}', fd)
                .done(function(res){
                    $('#contentPlanModal').modal('hide');
                    reloadAllTables(false);
                    Swal.fire('Sukses','Report disimpan','success');
                })
                .fail(function(xhr){
                    var msg = 'Gagal menyimpan report.';
                    if (xhr.responseJSON && xhr.responseJSON.errors) msg = Object.values(xhr.responseJSON.errors).join('<br>');
                    Swal.fire('Error', msg, 'error');
                });
        } else {
            // update
            $.ajax({
                url: `/marketing/content-report/${id}`,
                method: 'PUT',
                data: fd,
            }).done(function(res){
                $('#contentPlanModal').modal('hide');
                reloadAllTables(false);
                Swal.fire('Sukses','Report diperbarui','success');
            }).fail(function(xhr){
                var msg = 'Gagal memperbarui report.';
                if (xhr.responseJSON && xhr.responseJSON.errors) msg = Object.values(xhr.responseJSON.errors).join('<br>');
                Swal.fire('Error', msg, 'error');
            });
        }
    });

    // Add-brief button and its handler removed — brief is accessed via the Edit modal's Brief tab

    // Initialize Summernote when modal is shown (Bootstrap 4 event)
    $('#contentPlanModal').on('shown.bs.modal', function() {
        // initialize brief editor only when Brief tab is active
        if ($('#tab-brief').hasClass('show') || $('#tab-brief').hasClass('active')) {
            if ($.fn.summernote) {
                try { $('#cb_isi_konten').summernote('destroy'); } catch(e) {}
                try { $('#cb_isi_konten').summernote({height: 200}); } catch(e) { console.warn('summernote init failed', e); }
                try {
                    if (window._cbLoadedIsi) {
                        $('#cb_isi_konten').summernote('code', window._cbLoadedIsi);
                        window._cbLoadedIsi = null;
                        try { updateBriefTabIndicator(); } catch(e) {}
                    }
                } catch(e) { }
            }
        }
    });

    // Also initialize Summernote when the Brief tab is activated after modal is shown
    $('#tab-brief-tab').on('shown.bs.tab', function() {
        try {
            if ($.fn && $.fn.summernote) {
                var $el = $('#cb_isi_konten');
                // avoid re-initializing if already present
                if (!$el.data('summernote')) {
                    try { $el.summernote('destroy'); } catch(e) {}
                    try { $el.summernote({ height: 200 }); } catch(e) { console.warn('summernote init failed on tab show', e); }
                    try {
                        if (window._cbLoadedIsi) {
                            $el.summernote('code', window._cbLoadedIsi);
                            window._cbLoadedIsi = null;
                            try { updateBriefTabIndicator(); } catch(e) {}
                        }
                    } catch(e) {}
                }
            }
        } catch(e) { console.error('init summernote on tab show', e); }
    });

    // Destroy Summernote when modal is hidden to avoid duplicate instances
    $('#contentPlanModal').on('hidden.bs.modal', function() {
        if ($.fn.summernote) {
            try { $('#cb_isi_konten').summernote('destroy'); } catch(e) {}
        }
        // reset to Edit tab when modal closes
        try { $('#tab-edit-tab').trigger('click'); } catch(e){}
    });

    // Drop area interactions
    (function(){
        var $drop = $('#cb_drop_area');
        var $input = $('#cb_visual_references');

        $drop.on('click', function(){ $input.trigger('click'); });

        $drop.on('dragover', function(e){ e.preventDefault(); e.stopPropagation(); $drop.addClass('border-primary'); });
        $drop.on('dragleave drop', function(e){ e.preventDefault(); e.stopPropagation(); $drop.removeClass('border-primary'); });

        $drop.on('drop', function(e){
            e.preventDefault();
            e.stopPropagation();
            var dt = e.originalEvent.dataTransfer;
            if (!window._cbDataTransfer) window._cbDataTransfer = new DataTransfer();
            if (dt && dt.files && dt.files.length) {
                Array.from(dt.files).forEach(function(f){
                    if (f.type && f.type.indexOf('image') === 0) window._cbDataTransfer.items.add(f);
                });
            }
            renderCbPreview();
        });

        // Handle paste (paste screenshot directly into modal)
        function handlePaste(e){
            try {
                var clipboard = (e.originalEvent && e.originalEvent.clipboardData) || (e.clipboardData) || null;
                if (!clipboard) return;
                if (!window._cbDataTransfer) window._cbDataTransfer = new DataTransfer();
                var added = false;
                // Prefer items (gives access to files)
                if (clipboard.items && clipboard.items.length) {
                    Array.from(clipboard.items).forEach(function(item){
                        if (item.kind === 'file' && item.type.indexOf('image') === 0) {
                            var file = item.getAsFile();
                            if (file) { window._cbDataTransfer.items.add(file); added = true; }
                        }
                    });
                }
                // Fallback to clipboard.files
                if (!added && clipboard.files && clipboard.files.length) {
                    Array.from(clipboard.files).forEach(function(f){ if (f.type && f.type.indexOf('image') === 0) { window._cbDataTransfer.items.add(f); added = true; } });
                }
                if (added) {
                    renderCbPreview();
                    e.preventDefault();
                }
            } catch (err) {
                console.warn('paste handling failed', err);
            }
        }

        // Attach paste listener when merged modal is shown (only used for Brief tab)
        $('#contentPlanModal').on('shown.bs.modal', function(){
            if ($('#tab-brief').hasClass('show') || $('#tab-brief').hasClass('active')) $(document).on('paste.cb', handlePaste);
        });
        $('#contentPlanModal').on('hidden.bs.modal', function(){
            $(document).off('paste.cb', handlePaste);
        });

        $input.on('change', function(e){
            var files = Array.from(e.target.files || []);
            if (!window._cbDataTransfer) window._cbDataTransfer = new DataTransfer();
            files.forEach(function(f){ if (f.type && f.type.indexOf('image') === 0) window._cbDataTransfer.items.add(f); });
            renderCbPreview();
            // reset native input so user can re-select same file if needed
            $input.val('');
        });

        // Render preview thumbnails
        function renderCbPreview(){
            var $preview = $('#cb_preview');
            $preview.empty();
            var dt = window._cbDataTransfer || {files: []};
            Array.from(dt.files).forEach(function(file, idx){
                var reader = new FileReader();
                var $wrap = $('<div class="position-relative border rounded bg-white" style="width:100%;height:220px;overflow:hidden;display:block;margin-bottom:12px"></div>');
                var $remove = $('<button type="button" class="btn btn-sm btn-danger position-absolute" title="Hapus" style="top:6px;right:6px;padding:2px 6px">×</button>');
                $remove.on('click', function(){
                    // remove file from DataTransfer
                    var dt = window._cbDataTransfer;
                    var newDt = new DataTransfer();
                    Array.from(dt.files).forEach(function(f, i){ if (i !== idx) newDt.items.add(f); });
                    window._cbDataTransfer = newDt;
                    renderCbPreview();
                });
                reader.onload = function(e){
                    var $img = $('<img>').attr('src', e.target.result).attr('data-full', e.target.result).css({'width':'100%','height':'100%','object-fit':'cover','cursor':'zoom-in'});
                    $wrap.append($img).append($remove);
                    $preview.append($wrap);
                };
                reader.readAsDataURL(file);
            });
        }
    })();

    // Click on server-image remove buttons: mark server image for deletion and remove thumbnail
    $('#cb_preview').on('click', '.cb-server-remove', function(e){
        e.preventDefault();
        var $btn = $(this);
        var path = $btn.data('path');
        if (!path) { $btn.closest('div').remove(); return; }
        if (!window._cbRemoved) window._cbRemoved = [];
        window._cbRemoved.push(path);
        $btn.closest('div').remove();
        try { updateBriefTabIndicator(); } catch(e){}
    });

    // Click on any preview image: open full-size image in a new tab
    $('#cb_preview').on('click', 'img', function(){
        var src = $(this).data('full') || $(this).attr('src');
        if (!src) return;
        try {
            // Open in a new tab/window to let user view or download at native resolution
            window.open(src, '_blank');
        } catch (e) {
            // as a last resort, navigate current window
            window.location.href = src;
        }
    });

    // Submit brief (AJAX) — invoked by clicking the Save Brief button
    function submitBrief(e, opts){
        if (e && e.preventDefault) e.preventDefault();
        opts = opts || {};
        var closeOnComplete = opts.closeOnComplete !== undefined ? opts.closeOnComplete : true;
        var $btn = $('#contentPlanModal .btn-save-brief');
        // If called as part of master save, the button may be hidden; still show spinner state if present
        try { $btn.prop('disabled', true).text('Menyimpan...'); } catch(e) {}
        var fd = new FormData();
        fd.append('content_plan_id', $('#cb_content_plan_id').val());
        fd.append('headline', $('#cb_headline').val());
        fd.append('sub_headline', $('#cb_sub_headline').val());
        var isi = '';
        try { if ($.fn.summernote) isi = $('#cb_isi_konten').summernote('code'); else isi = $('#cb_isi_konten').val(); } catch(e) { isi = $('#cb_isi_konten').val(); }
        fd.append('isi_konten', isi);
        var dt = window._cbDataTransfer || {files: []};
        Array.from(dt.files).forEach(function(f, i){ fd.append('visual_references[]', f); });
        // include any server-side images the user removed
        try {
            if (window._cbRemoved && Array.isArray(window._cbRemoved)) {
                window._cbRemoved.forEach(function(p){ fd.append('remove_visual_references[]', p); });
            }
        } catch(e) {}
        var existingId = $('#cb_id').val();
        if (existingId) fd.append('id', existingId);

        var jq = $.ajax({
            url: '/marketing/content-brief',
            method: 'POST',
            data: fd,
            processData: false,
            contentType: false,
        });

        jq.done(function(res){
            if (closeOnComplete) {
                if (window._cbBsModal && typeof window._cbBsModal.hide === 'function') {
                    try { window._cbBsModal.hide(); } catch(e){ $('#contentPlanModal').modal('hide'); }
                } else {
                    try { $('#contentPlanModal').modal('hide'); } catch(e) { $('#contentPlanModal').removeClass('show').css('display','none'); $('body').removeClass('modal-open'); }
                }
                reloadAllTables(false);
                Swal.fire('Sukses', 'Content brief disimpan', 'success');
            }
        }).fail(function(xhr){
            var msg = 'Gagal menyimpan content brief.';
            if (xhr.responseJSON && xhr.responseJSON.errors) msg = Object.values(xhr.responseJSON.errors).join('<br>');
            Swal.fire('Error', msg, 'error');
        }).always(function(){
            try { $btn.prop('disabled', false).text('Simpan Brief'); } catch(e) {}
        });

        return jq;
    }

    // wire brief save button
    $(document).on('click', '.btn-save-brief', submitBrief);
});
</script>
@endpush
