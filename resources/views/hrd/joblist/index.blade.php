@extends('layouts.hrd.app')

@section('navbar')
    @include('layouts.hrd.navbar-joblist')
@endsection

@section('content')
<div class="container-fluid">
    <div class="d-flex align-items-center mb-3">
        <h3 class="mb-0"><strong>JOB LIST</strong></h3>
        <div class="ml-3 d-flex align-items-center flex-fill"></div>
        <div class="ml-3">
            <div class="d-flex justify-content-end">
                <div>
                    <button id="btnAddJob" class="btn btn-primary">Tambah Job</button>
                </div>
            </div>
            <div class="d-flex justify-content-end mt-2">
                <div class="form-inline">
                    <div class="form-check mr-3">
                        <input class="form-check-input" type="checkbox" id="filter_hide_done" checked />
                        <label class="form-check-label small ml-2" for="filter_hide_done">SEMBUNYIKAN DONE</label>
                    </div>
                    <div class="mr-3">
                        @php
                            $user = Auth::user();
                            $isManager = $user && $user->hasRole('Manager');
                            $isGlobalRole = $user && $user->hasAnyRole(['Ceo','Admin','Hrd']);
                            $isEmployeeOnly = $user && !$isManager && !$isGlobalRole;
                            $defaultForManager = '';
                            if ($isEmployeeOnly) $defaultForManager = '0';
                            if ($isGlobalRole) $defaultForManager = '1';
                        @endphp
                        <select id="filter_for_manager" class="form-control form-control-sm" @if($isEmployeeOnly) disabled title="Locked to Non-Manager" @endif>
                            <option value="" @if($defaultForManager === '') selected @endif>All</option>
                            <option value="1" @if($defaultForManager === '1') selected @endif>For Manager Only</option>
                            <option value="0" @if($defaultForManager === '0') selected @endif>Non-Manager Only</option>
                        </select>
                    </div>
                    <div>
                        @php $user = Auth::user(); $userDivisionId = optional($user->employee)->division_id; $isGlobalRole = $user && $user->hasAnyRole(['Ceo','Admin','Hrd']); @endphp
                        @if($user && $user->hasAnyRole(['Hrd','Admin','Manager','Ceo']))
                            <select id="filter_division" class="form-control form-control-sm">
                                <option value="" @if($isGlobalRole) selected @endif>Semua Division</option>
                                @foreach($divisions as $d)
                                    <option value="{{ $d->id }}" @if(!$isGlobalRole && $d->id == $userDivisionId) selected @endif>{{ $d->name }}</option>
                                @endforeach
                            </select>
                        @else
                            <select id="filter_division" class="form-control form-control-sm" disabled title="Division locked">
                                @if($userDivisionId)
                                    @php $current = $divisions->firstWhere('id', $userDivisionId); @endphp
                                    <option value="{{ $userDivisionId }}">{{ $current?->name ?? 'Division' }}</option>
                                @else
                                    <option value="">- Tidak ada Division -</option>
                                @endif
                            </select>
                        @endif
                    </div>
                    <div class="ml-3">
                        <input id="filter_created_range" class="form-control form-control-sm" placeholder="Created At range" autocomplete="off" />
                    </div>
                </div>
            </div>
            </div>
        </div>
    </div>

    <table class="table table-striped" id="joblist-table" style="width:100%">
        <thead>
            <tr>
                <th>No</th>
                <th>Title</th>
                <th>Status</th>
                <th>Due Date</th>
                <th>Catatan</th>
                <th>Actions</th>
            </tr>
        </thead>
    </table>
</div>

<style>
/* blinking warning */
.blink { color: #dc3545; font-weight: 600; display:inline-block; animation: blinker 1s linear infinite; }
@keyframes blinker { 50% { opacity: 0; } }
/* division badges under title */
.badge-division { margin-right: 6px; margin-top: 2px; }
/* priority badge spacing */
.badge-priority { margin-right: 8px; }
/* wider notes column and wrapping */
.notes-col { white-space: normal; word-break: break-word; min-width: 320px; }
</style>

<!-- Modal -->
<div class="modal fade" id="jobModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="jobModalLabel">Tambah Job</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
      <div class="modal-body">
        <form id="jobForm">
            @csrf
            <input type="hidden" name="id" id="job_id" />
            <div class="form-group">
                <label>Title</label>
                <input type="text" name="title" id="title" class="form-control" required />
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" id="description" class="form-control"></textarea>
            </div>
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Status</label>
                    <!-- Status is locked in the modal. Use the row actions (Dibaca / Selesai) to change status. -->
                    <select id="status" class="form-control" disabled>
                        <option value="delegated" selected>Delegated</option>
                        <option value="progress">Progress</option>
                        <option value="done">Done</option>
                        <option value="canceled">Canceled</option>
                    </select>
                    <!-- Hidden field contains the status that will actually be submitted -->
                    <input type="hidden" name="status" id="status_hidden" value="delegated" />
                    <small class="form-text text-muted">Status dikunci — gunakan tombol "Dibaca" atau "Selesai" pada daftar untuk mengubah status.</small>
                </div>
                <div class="form-group col-md-6">
                    <label>Priority</label>
                    <select name="priority" id="priority" class="form-control">
                        <option value="low">Low</option>
                        <option value="normal" selected>Normal</option>
                        <option value="important">Important</option>
                        <option value="very_important">Very Important</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Division</label>
                    @php $user = Auth::user(); $userDivisionId = optional($user->employee)->division_id; @endphp
                    @if($user && $user->hasAnyRole(['Hrd','Admin','Manager','Ceo']))
                        <select name="divisions[]" id="divisions" class="form-control" multiple style="height:120px;">
                            @foreach($divisions as $d)
                                <option value="{{ $d->id }}" @if($d->id == $userDivisionId) selected @endif>{{ $d->name }}</option>
                            @endforeach
                        </select>
                        <div class="form-check mt-2">
                            <input type="checkbox" class="form-check-input" id="all_divisions" name="all_divisions" value="1">
                            <label for="all_divisions" class="form-check-label">All Divisions</label>
                        </div>
                        <div class="form-check mt-2">
                            <input type="checkbox" class="form-check-input" id="for_manager" name="for_manager" value="1">
                            <label for="for_manager" class="form-check-label">For Manager Only</label>
                        </div>
                        <small class="form-text text-muted">Pilih beberapa division dengan Ctrl/Cmd atau centang "All Divisions" untuk menugaskan ke semua division.</small>
                    @else
                        {{-- Non-privileged users: show disabled select but include hidden input so form serialize() sends division_id --}}
                        <select id="division_id" class="form-control" disabled title="Division locked">
                            @if($userDivisionId)
                                @php $current = $divisions->firstWhere('id', $userDivisionId); @endphp
                                <option value="{{ $userDivisionId }}">{{ $current?->name ?? 'Division' }}</option>
                            @else
                                <option value="">- Tidak ada Division -</option>
                            @endif
                        </select>
                        <input type="hidden" name="division_id" id="division_id_hidden" value="{{ $userDivisionId ?? '' }}" />
                    @endif
                </div>
                <div class="form-group col-md-6">
                    <label>Due Date</label>
                    <input type="text" name="due_date" id="due_date" class="form-control" placeholder="DD-MM-YYYY" autocomplete="off" />
                    <div id="documents-section" class="mt-3" style="display:none;">
                        <label>Dokumen (images or documents) — max 10MB each</label>
                        <input type="file" name="dokumen[]" id="dokumen" class="form-control-file" multiple />
                        <div id="existing-documents" class="mt-2"></div>
                    </div>
                </div>
            </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-primary" id="saveJobBtn">Simpan</button>
      </div>
    </div>
  </div>
</div>

<!-- Upload Documents Modal (for inline 'done' flow) -->
<div class="modal fade" id="uploadDocumentsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Upload Dokumen</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="upload-doc-job-id" />
                <div class="form-group">
                    <label>Pilih file (maks 10MB masing-masing)</label>
                    <input type="file" id="upload-doc-input" class="form-control-file" multiple />
                    <small class="form-text text-muted">Anda dapat menempelkan (paste) gambar langsung ke modal menggunakan Ctrl+V / Paste.</small>
                    <div id="upload-previews" class="mt-2 d-flex flex-wrap"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="uploadDocBtn">Unggah</button>
            </div>
        </div>
    </div>
</div>

            <!-- View Job Modal -->
            <div class="modal fade" id="viewJobModal" tabindex="-1" role="dialog">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="viewJobTitle">Judul</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <label class="font-weight-bold">Deskripsi</label>
                            <div id="viewJobDescription" style="white-space:pre-wrap;" class="mt-1"></div>

                            <div id="viewNotesSection" class="mt-3">
                                <label>Catatan</label>
                                <textarea id="view-notes" class="form-control" rows="4" placeholder="Tambahkan catatan..."></textarea>
                            </div>

                            <div id="viewJobDocuments" class="mt-3">
                                <!-- Documents will be populated here -->
                            </div>

                            <div id="viewUploadSection" class="mt-3">
                                <label>Unggah Bukti (maks 10MB masing-masing)</label>
                                <input type="file" id="view-upload-input" class="form-control-file" multiple />
                                <small class="form-text text-muted">Pilih file untuk menambahkan bukti ke job ini.</small>
                                <div id="view-upload-previews" class="mt-2 d-flex flex-wrap"></div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                            <button type="button" class="btn btn-success mr-2" id="viewSaveNotesBtn">Simpan Catatan</button>
                            <button type="button" class="btn btn-primary" id="viewUploadBtn">Unggah</button>
                        </div>
                    </div>
                </div>
            </div>

@endsection

@section('scripts')
<script>
$(function(){
    // Ensure CSRF token is sent with all AJAX requests
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // Ensure any inline selects rendered by the server are disabled after each draw
    try {
        table.on('draw', function(){
            $('.job-status-select').prop('disabled', true).hide();
            $('.job-status-select').each(function(){
                var $s = $(this);
                var orig = $s.data('original');
                if (typeof orig !== 'undefined' && orig !== null) $s.val(orig);
            });
        });
    } catch(e) { /* ignore if table not ready */ }
    // Apply query params to filters (so dashboard links can open filtered list)
    try {
        var urlParams = new URLSearchParams(window.location.search);
        var qDivision = urlParams.get('division_id');
        var qStatus = urlParams.get('status');
        if (qDivision) $('#filter_division').val(qDivision);
        if (qStatus) $('#filter_status').val(qStatus);
    } catch (e) { /* ignore */ }

    // Mirror server-side computed user division id for modal behavior
    var userDivisionId = @json($userDivisionId ?? null);
    // Initialize Select2 for nicer multi-select (if available)
    if ($('#divisions').length && $.fn.select2) {
        $('#divisions').select2({
            placeholder: '-- Pilih Division --',
            width: '100%'
        });
    }

    // Toggle multi-select when All Divisions checkbox changes
    $(document).on('change', '#all_divisions', function(){
        var checked = $(this).is(':checked');
        if ($('#divisions').hasClass('select2-hidden-accessible')) {
            $('#divisions').prop('disabled', checked).trigger('change.select2');
        } else {
            $('#divisions').prop('disabled', checked).trigger('change');
        }
    });

    // Initialize due_date as a single-date picker using daterangepicker
    try {
        if ($.fn.daterangepicker) {
            $('#due_date').daterangepicker({
                singleDatePicker: true,
                showDropdowns: true,
                autoUpdateInput: false,
                locale: { format: 'DD-MM-YYYY' }
            });

            $('#due_date').on('apply.daterangepicker', function(ev, picker){
                $(this).val(picker.startDate.format('DD-MM-YYYY'));
            });
            $('#due_date').on('cancel.daterangepicker', function(ev, picker){
                $(this).val('');
            });

            // Created At range filter (top toolbar)
            $('#filter_created_range').daterangepicker({
                autoUpdateInput: false,
                showDropdowns: true,
                locale: { format: 'DD-MM-YYYY' }
            });
            $('#filter_created_range').on('apply.daterangepicker', function(ev, picker){
                $(this).val(picker.startDate.format('DD-MM-YYYY') + ' - ' + picker.endDate.format('DD-MM-YYYY'));
                try { table.ajax.reload(); } catch(e){}
            });
            $('#filter_created_range').on('cancel.daterangepicker', function(ev, picker){
                $(this).val('');
                try { table.ajax.reload(); } catch(e){}
            });
        }
    } catch (e) { /* ignore if plugin missing */ }

    // Helper: unique color per division via stable hash → HSL hue
    function divisionColor(name) {
        try {
            var s = (name || '').toString();
            var h = 0;
            for (var i = 0; i < s.length; i++) { h = (h * 31 + s.charCodeAt(i)) >>> 0; }
            var hue = h % 360;
            var sat = 60; // keep readable
            var light = 45; // mid lightness for contrast
            return 'hsl(' + hue + ',' + sat + '%,' + light + '%)';
        } catch (e) { return '#6c757d'; }
    }

    function renderDivisionBadges(row) {
        var divStr = (row && row.division_name) ? row.division_name : '';
        if (!divStr) return '';
        // Special case for "All Divisions"
        if (divStr.trim().toLowerCase() === 'all divisions') {
            return '<span class="badge badge-division" style="background-color:#6c757d;color:#fff">All Divisions</span>';
        }
        var parts = divStr.split(',');
        var html = '';
        for (var i = 0; i < parts.length; i++) {
            var name = parts[i].trim();
            if (!name) continue;
            var bg = divisionColor(name);
            // Decide text color (simple threshold on lightness from HSL, assume ~45)
            var color = '#fff';
            html += '<span class="badge badge-division" style="background-color:' + bg + ';color:' + color + '">' + $('<div/>').text(name).html() + '</span>';
        }
        return html;
    }

    function renderPriorityBadge(row) {
        var p = (row && row.priority) ? row.priority : '';
        if (!p) return '';
        var label = p.replace(/_/g, ' ');
        label = label.charAt(0).toUpperCase() + label.slice(1);
        var cls = 'badge-secondary';
        if (p === 'very_important') cls = 'badge-danger';
        else if (p === 'important') cls = 'badge-warning';
        else if (p === 'normal') cls = 'badge-info';
        else if (p === 'low') cls = 'badge-secondary';
        return '<span class="badge ' + cls + ' badge-priority">' + $('<div/>').text(label).html() + '</span>';
    }

    var table = $('#joblist-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{!! route("hrd.joblist.data") !!}',
            data: function(d) {
                d.status = $('#filter_status').val();
                d.division_id = $('#filter_division').val();
                    d.hide_done = $('#filter_hide_done').length ? ($('#filter_hide_done').is(':checked') ? 1 : 0) : 0;
                // send for_manager filter: '' => all, '1' => only manager items, '0' => non-manager items
                d.for_manager = $('#filter_for_manager').length ? $('#filter_for_manager').val() : '';
                // Created At range -> send as YYYY-MM-DD
                try {
                    var drc = $('#filter_created_range').data('daterangepicker');
                    if (drc && $('#filter_created_range').val()) {
                        d.created_start = drc.startDate.format('YYYY-MM-DD');
                        d.created_end = drc.endDate.format('YYYY-MM-DD');
                    } else {
                        // Fallback: parse "DD-MM-YYYY - DD-MM-YYYY" if plugin not available
                        var v = ($('#filter_created_range').val() || '').trim();
                        if (v && v.indexOf(' - ') > -1) {
                            var parts = v.split(' - ');
                            var m1 = moment(parts[0], 'DD-MM-YYYY', true);
                            var m2 = moment(parts[1], 'DD-MM-YYYY', true);
                            d.created_start = m1.isValid() ? m1.format('YYYY-MM-DD') : '';
                            d.created_end = m2.isValid() ? m2.format('YYYY-MM-DD') : '';
                        } else {
                            d.created_start = '';
                            d.created_end = '';
                        }
                    }
                } catch(e) {
                    d.created_start = '';
                    d.created_end = '';
                }
            }
        },
        // Show all entries by default; include 'All' option in lengthMenu
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'All']],
        pageLength: -1,
        columns: [
            { data: 'id', name: 'id', render: function(data, type, row, meta) { return meta.row + meta.settings._iDisplayStart + 1; } },
            { data: 'title', name: 'title', render: function(data, type, row){
                    var titleEsc = $('<div/>').text(data || '').html();
                    var badges = renderDivisionBadges(row);
                    if (type === 'display') {
                        return '<div class="job-title-cell"><div class="font-weight-bold">' + titleEsc + '</div>' + (badges ? '<div class="mt-1">' + badges + '</div>' : '') + '</div>';
                    }
                    return titleEsc;
                }
            },
            { data: 'status_control', name: 'status', orderable: true, searchable: true },
            { data: 'due_date_display', name: 'due_date', orderable: true, searchable: false, render: function(data, type, row){
                    var html = (data || '');
                    var pbadge = renderPriorityBadge(row);
                    if (type === 'display') {
                        return '<div class="due-date-cell"><div class="font-weight-bold">' + html + '</div>' + (pbadge ? '<div class="mt-1">' + pbadge + '</div>' : '') + '</div>';
                    }
                    return html;
                }
            },
            { data: 'notes', name: 'notes', orderable: false, searchable: true, className: 'notes-col', width: '30%', render: function(data, type, row){
                    if (!data) return '';
                    try {
                        var display = data.length > 120 ? data.substring(0,120) + '...' : data;
                        var displayEsc = $('<div/>').text(display).html();
                        var titleEsc = $('<div/>').text(data).html();
                        return '<div title="' + titleEsc + '">' + displayEsc + '</div>';
                    } catch(e) {
                        return $('<div/>').text(data).html();
                    }
                }
            },
            { data: 'actions', name: 'actions', orderable:false, searchable:false }
        ]
    });

    // pending completion state: when a job is to be marked done but requires upload first
    var pendingCompleteJobId = null;
    var pendingCompleteSelectElem = null;
    var pendingCompleteOrig = null;
    // fallback store for pasted files when browser doesn't allow setting input.files
    var pastedFiles = [];

    // Show/hide documents section when status == 'done'
    function toggleDocumentsSection() {
        var val = $('#status').val();
        if (val === 'done') {
            $('#documents-section').show();
        } else {
            $('#documents-section').hide();
            // clear file input when hidden
            $('#dokumen').val('');
        }
    }
    $(document).on('change', '#status', toggleDocumentsSection);

    

    $('#btnAddJob').on('click', function(){
        $('#jobForm')[0].reset();
        $('#job_id').val('');
        $('#jobModalLabel').text('Tambah Job');
        // Ensure hidden mirror is set for non-privileged users
        if (typeof userDivisionId !== 'undefined' && $('#division_id_hidden').length) {
            $('#division_id_hidden').val(userDivisionId);
        }
        // reset multi-select and all_divisions checkbox if present
        if ($('#all_divisions').length) {
            $('#all_divisions').prop('checked', false);
        }
        if ($('#divisions').length) {
            if ($('#divisions').hasClass('select2-hidden-accessible')) {
                $('#divisions').prop('disabled', false).val(null).trigger('change.select2');
            } else {
                $('#divisions').prop('disabled', false).val([]).trigger('change');
            }
        }
        if ($('#for_manager').length) {
            $('#for_manager').prop('checked', false);
        }
        // Clear daterangepicker input for new job
        if ($('#due_date').length) {
            $('#due_date').val('');
            var dr = $('#due_date').data('daterangepicker');
            if (dr) { dr.setStartDate(moment()); dr.setEndDate(moment()); }
        }
        // ensure status hidden field has default and keep select disabled
        try { $('#status').val('delegated'); } catch(e){}
        try { $('#status_hidden').val('delegated'); } catch(e){}
        try { $('#status').prop('disabled', true); } catch(e){}
        $('#jobModal').modal('show');
        // ensure documents section hidden for new job default
        toggleDocumentsSection();
    });

    // reload table when filter changes
    $('#filter_status').on('change', function(){
        table.ajax.reload();
    });
    $('#filter_hide_done').on('change', function(){
        table.ajax.reload();
    });
    $('#filter_division').on('change', function(){
        table.ajax.reload();
    });
    $('#filter_for_manager').on('change', function(){
        table.ajax.reload();
    });

        $('#saveJobBtn').on('click', function(){
        var id = $('#job_id').val();
        var url = id ? '/hrd/joblist/' + id : '/hrd/joblist';
        var method = 'POST';

        // Build payload. Use direct select value for multi-select to avoid serialize issues.
        var formArray = $('#jobForm').serializeArray();
        var payload = {};
        formArray.forEach(function(item){
            payload[item.name] = item.value;
        });

        // If divisions multi-select exists, prefer its value array
        if ($('#divisions').length) {
            var sel = $('#divisions').val();
            // make sure we send an array (or null)
            payload.divisions = Array.isArray(sel) ? sel : (sel ? [sel] : []);
        }
        // Normalize checkboxes
        payload.all_divisions = ($('#all_divisions').is(':checked') ? 1 : 0);
        payload.for_manager = ($('#for_manager').is(':checked') ? 1 : 0);

        if (payload.due_date) {
            try {
                var m = moment(payload.due_date, 'DD-MM-YYYY', true);
                if (m.isValid()) {
                    payload.due_date = m.format('YYYY-MM-DD');
                }
            } catch(e){ /* ignore */ }
        }

        // If there are files selected, use FormData to send multipart request
        var files = $('#dokumen')[0] ? $('#dokumen')[0].files : null;
        if (files && files.length) {
            var fd = new FormData();
            // append simple fields
            Object.keys(payload).forEach(function(k){
                var v = payload[k];
                // skip divisions (we'll append separately)
                if (k === 'divisions') return;
                fd.append(k, v);
            });
            // append divisions array
            if (Array.isArray(payload.divisions)) {
                payload.divisions.forEach(function(d){ fd.append('divisions[]', d); });
            }
            // append files
            for (var i=0;i<files.length;i++) {
                fd.append('dokumen[]', files[i]);
            }
            // include checkboxes explicitly
            fd.append('all_divisions', payload.all_divisions);
            fd.append('for_manager', payload.for_manager);

            $.ajax({
                url: url,
                method: method,
                data: fd,
                cache: false,
                contentType: false,
                processData: false,
                success: function(res){
                    $('#jobModal').modal('hide');
                    table.ajax.reload(null, false);
                    Swal.fire({icon: 'success', title: 'Berhasil'});
                },
                error: function(xhr){
                    var msg = 'Terjadi kesalahan';
                    if (xhr.responseJSON && xhr.responseJSON.errors) {
                        msg = Object.values(xhr.responseJSON.errors).map(function(v){ return v.join(', '); }).join('\n');
                    }
                    Swal.fire({icon:'error', text: msg});
                }
            });
            return;
        }

        // fallback: no files -> send as before
        $.ajax({
            url: url,
            method: method,
            data: payload,
            success: function(res){
                $('#jobModal').modal('hide');
                table.ajax.reload(null, false);
                Swal.fire({icon: 'success', title: 'Berhasil'});
            },
            error: function(xhr){
                var msg = 'Terjadi kesalahan';
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    msg = Object.values(xhr.responseJSON.errors).map(function(v){ return v.join(', '); }).join('\n');
                }
                Swal.fire({icon:'error', text: msg});
            }
        });
    });

    // Edit
    $('#joblist-table').on('click', '.btn-edit-job', function(){
        var id = $(this).data('id');
        $.get('/hrd/joblist/' + id, function(res){
            var data = res.data;
            $('#job_id').val(data.id);
            $('#title').val(data.title);
            $('#description').val(data.description);
            $('#status').val(data.status);
            $('#status_hidden').val(data.status);
            // keep status select locked in modal
            $('#status').prop('disabled', true);
            $('#priority').val(data.priority);
            // Populate divisions multi-select or legacy hidden input
            if ($('#divisions').length) {
                var divIds = [];
                if (data.all_divisions) {
                    // do not pre-select any when All Divisions active; just check the box
                    divIds = [];
                } else if (Array.isArray(data.divisions) && data.divisions.length) {
                    divIds = data.divisions.map(function(d){ return d.id; });
                } else if (data.division_id) {
                    divIds = [data.division_id];
                }
                if ($('#divisions').hasClass('select2-hidden-accessible')) {
                    $('#divisions').val(divIds).trigger('change.select2');
                } else {
                    $('#divisions').val(divIds).trigger('change');
                }
                $('#all_divisions').prop('checked', !!data.all_divisions);
                $('#for_manager').prop('checked', !!data.for_manager);
                // disable select when all_divisions checked
                if ($('#divisions').hasClass('select2-hidden-accessible')) {
                    $('#divisions').prop('disabled', !!data.all_divisions).trigger('change.select2');
                } else {
                    $('#divisions').prop('disabled', !!data.all_divisions).trigger('change');
                }
            }
            if ($('#division_id_hidden').length) {
                var legacyId = data.division_id || (Array.isArray(data.divisions) && data.divisions.length ? data.divisions[0].id : '');
                $('#division_id_hidden').val(legacyId);
                var $sel = $('#division_id');
                if ($sel.prop('disabled')) {
                    $sel.html('<option value="'+legacyId+'">'+(data.division_name || legacyId)+'</option>');
                }
            }
            // Set daterangepicker date for edit modal
            if (data.due_date && $('#due_date').length) {
                // display in DD-MM-YYYY but backend stores YYYY-MM-DD; format accordingly
                try {
                    var m = moment(data.due_date, 'YYYY-MM-DD');
                    $('#due_date').val(m.isValid() ? m.format('DD-MM-YYYY') : data.due_date);
                } catch(e) {
                    $('#due_date').val(data.due_date);
                }
                var dr = $('#due_date').data('daterangepicker');
                if (dr) {
                    try { dr.setStartDate(moment(data.due_date, 'YYYY-MM-DD')); dr.setEndDate(moment(data.due_date, 'YYYY-MM-DD')); } catch(e){/*ignore*/}
                }
            } else if ($('#due_date').length) {
                $('#due_date').val('');
            }
            // populate existing documents list (if any)
            $('#existing-documents').html('');
            if (data.documents && Array.isArray(data.documents) && data.documents.length) {
                var list = '<ul class="list-unstyled small">';
                data.documents.forEach(function(p, idx){
                    var url = (p.indexOf('http') === 0) ? p : ('/hrd/joblist/' + data.id + '/document/' + idx);
                    var fileName = p.split('/').pop();
                    list += '<li><a href="'+url+'" target="_blank">' + fileName + '</a></li>';
                });
                list += '</ul>';
                $('#existing-documents').html(list);
            }
            toggleDocumentsSection();
            $('#jobModalLabel').text('Edit Job');
            $('#jobModal').modal('show');
        });
    });

    // Delete
    $('#joblist-table').on('click', '.btn-delete-job', function(){
        var id = $(this).data('id');
        Swal.fire({
            title: 'Konfirmasi',
            text: 'Hapus job ini?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Hapus'
        }).then(function(result){
            if (result.value) {
                $.ajax({ url: '/hrd/joblist/' + id, method: 'DELETE', success: function(){
                    table.ajax.reload(null, false);
                    Swal.fire({icon:'success', title:'Terhapus'});
                }});
            }
        });
    });

    // Mark as read (Dibaca)
    $('#joblist-table').on('click', '.btn-dibaca', function(){
        var id = $(this).data('id');
        $.ajax({
            url: '/hrd/joblist/' + id + '/dibaca',
            method: 'POST',
            success: function(res){
                if (res.success) {
                    table.ajax.reload(null, false);
                    Swal.fire({icon:'success', title:'Tercatat sebagai dibaca'});
                } else {
                    Swal.fire({icon:'error', text:'Gagal mencatat pembacaan'});
                }
            },
            error: function(){
                Swal.fire({icon:'error', text:'Terjadi kesalahan'});
            }
        });
    });

    // View (Lihat) - show title and description in modal
    $('#joblist-table').on('click', '.btn-lihat', function(){
        var id = $(this).data('id');
        $.get('/hrd/joblist/' + id, function(res){
            if (res && res.data) {
                var data = res.data;
                $('#viewJobTitle').text(data.title || '');
                $('#viewJobDescription').text(data.description || '');
                // populate notes
                $('#view-notes').val(data.notes || '');
                // populate documents
                function renderDocs(docs) {
                    var $ct = $('#viewJobDocuments');
                    $ct.html('');
                    if (!docs || !docs.length) {
                        $ct.html('<div class="small text-muted">Tidak ada bukti terunggah.</div>');
                        return;
                    }
                    var list = '<ul class="list-unstyled small">';
                    docs.forEach(function(p, idx){
                        var url = (p.indexOf('http') === 0) ? p : ('/hrd/joblist/' + data.id + '/document/' + idx);
                        var fileName = p.split('/').pop();
                        list += '<li><a href="'+url+'" target="_blank">' + fileName + '</a></li>';
                    });
                    list += '</ul>';
                    $ct.html(list);
                }
                renderDocs(data.documents || []);
                // clear upload input and previews
                $('#view-upload-input').val('');
                $('#view-upload-previews').html('');
                // store job id on modal for use by upload handler
                $('#viewJobModal').data('job-id', data.id);
                $('#viewJobModal').modal('show');
            } else {
                Swal.fire({icon:'error', text: 'Gagal memuat data.'});
            }
        }).fail(function(){
            Swal.fire({icon:'error', text: 'Terjadi kesalahan saat memuat data.'});
        });
    });

    // Selesai button: mark done immediately without requiring upload
    $('#joblist-table').on('click', '.btn-selesai', function(){
        var id = $(this).data('id');
        Swal.fire({
            title: 'Konfirmasi',
            text: 'Tandai tugas ini selesai?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Selesai'
        }).then(function(result){
            if (!result.value) return;
            $.ajax({
                url: '/hrd/joblist/' + id + '/inline-update',
                method: 'POST',
                data: { status: 'done' },
                success: function(res){
                    try { table.ajax.reload(null, false); } catch(e){}
                    Swal.fire({icon: 'success', title: 'Ditandai Selesai'});
                },
                error: function(xhr){
                    var msg = 'Gagal menandai selesai';
                    try { if (xhr && xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message; } catch(e){}
                    Swal.fire({icon: 'error', text: msg});
                }
            });
        });
    });

    // Click badge: disabled inline editing — instruct user to use Dibaca / Selesai actions
    $(document).on('click', '.status-inline-badge', function(){
        Swal.fire({
            icon: 'info',
            title: 'Ubah Status',
            text: 'Untuk mengganti status gunakan tombol "Dibaca" atau "Selesai" pada baris terkait. Editing status langsung melalui badge/select dinonaktifkan.'
        });
        return;
    });

    // Helper: map status to badge class and label
    function statusToBadge(status) {
        var cls = 'badge-info';
        var label = status.charAt(0).toUpperCase() + status.slice(1);
        if (status === 'done') cls = 'badge-success';
        if (status === 'canceled') cls = 'badge-danger';
        // progress should be warning (yellow)
        if (status === 'progress') cls = 'badge-warning';
        // delegated should use info (blue)
        if (status === 'delegated') cls = 'badge-info';
        return { cls: cls, label: label };
    }

    // Inline selects are disabled — intercept change and revert, instruct user to use row actions
    $(document).on('change', '.job-status-select', function(){
        var $select = $(this);
        var orig = $select.data('original');
        // revert
        try { $select.val(orig); } catch(e){}
        $select.hide();
        $select.closest('div').find('.status-inline-badge').show();
        Swal.fire({icon:'info', title:'Ubah Status Dinonaktifkan', text: 'Gunakan tombol "Dibaca" atau "Selesai" di baris untuk mengubah status.'});
        return;
    });

    // Upload documents modal handler
    $(document).on('click', '#uploadDocBtn', function(){
        var id = $('#upload-doc-job-id').val();
        // combine files from input.files and pastedFiles fallback
        var inputEl = $('#upload-doc-input')[0];
        var inputFiles = inputEl ? inputEl.files : null;
        var totalFilesCount = (inputFiles ? inputFiles.length : 0) + (pastedFiles ? pastedFiles.length : 0);
        if (!totalFilesCount) {
            Swal.fire({icon:'warning', text: 'Pilih file terlebih dahulu'});
            return;
        }
        var fd = new FormData();
        if (inputFiles && inputFiles.length) {
            for (var i=0;i<inputFiles.length;i++) fd.append('dokumen[]', inputFiles[i]);
        }
        if (pastedFiles && pastedFiles.length) {
            for (var j=0;j<pastedFiles.length;j++) fd.append('dokumen[]', pastedFiles[j]);
        }
        $.ajax({
            url: '/hrd/joblist/' + id + '/upload-documents',
            method: 'POST',
            data: fd,
            cache: false,
            contentType: false,
            processData: false,
            success: function(res){
                // If this upload was part of a pending 'complete' action, then mark status as done AFTER successful upload
                if (pendingCompleteJobId && pendingCompleteJobId.toString() === id.toString()) {
                    // call inline-update to mark done
                    $.ajax({
                        url: '/hrd/joblist/' + id + '/inline-update',
                        method: 'POST',
                        data: { status: 'done' },
                        success: function(ir){
                            $('#uploadDocumentsModal').modal('hide');
                            pendingCompleteJobId = null;
                            // clear pastedFiles after successful upload
                            pastedFiles = [];
                            // clear input files as well
                            try { if (inputEl) inputEl.value = null; } catch(e){}
                            // ensure select shows updated badge if inline-select was used
                            if (pendingCompleteSelectElem) {
                                var $sel = pendingCompleteSelectElem;
                                var info = statusToBadge('done');
                                var $container = $sel.closest('div');
                                var $badge = $container.find('.status-inline-badge');
                                $badge.text(info.label).removeClass('badge-info badge-success badge-danger').addClass(info.cls);
                                $sel.hide();
                                $badge.show();
                                pendingCompleteSelectElem = null;
                            }
                            table.ajax.reload(null, false);
                            Swal.fire({icon:'success', text: 'Tugas ditandai selesai dan dokumen tersimpan.'});
                        },
                        error: function(){
                            Swal.fire({icon:'error', text: 'Gagal menandai tugas sebagai selesai setelah upload.'});
                        }
                    });
                } else {
                    // normal upload flow
                    $('#uploadDocumentsModal').modal('hide');
                    // clear pastedFiles and previews
                    pastedFiles = [];
                    $('#upload-previews').html('');
                    table.ajax.reload(null, false);
                    Swal.fire({icon:'success', text: 'Dokumen berhasil diunggah'});
                }
            },
            error: function(xhr){
                var msg = 'Terjadi kesalahan';
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    msg = Object.values(xhr.responseJSON.errors).map(function(v){ return v.join(', '); }).join('\n');
                }
                Swal.fire({icon:'error', text: msg});
            }
        });
    });

    // Paste-to-upload: when modal open, listen for paste events and accept image(s)
    $('#uploadDocumentsModal').on('shown.bs.modal', function(){
        // clear previews when opening
        $('#upload-previews').html('');
    });

    $('#uploadDocumentsModal').on('paste', function(e){
        var clipboard = (e.originalEvent || e).clipboardData;
        if (!clipboard) return;
        var items = clipboard.items || [];
        var filesToAdd = [];
        for (var i=0;i<items.length;i++) {
            var it = items[i];
            if (it.kind === 'file' && it.type.indexOf('image/') === 0) {
                var f = it.getAsFile();
                if (f) filesToAdd.push(f);
            }
        }
        if (!filesToAdd.length) return;
        // Attempt to merge with existing input.files using DataTransfer. If not supported
        // we store pasted files in `pastedFiles` so they are included during upload.
        var dtSupported = true;
        try {
            var dt = new DataTransfer();
            var currentFiles = $('#upload-doc-input')[0].files;
            for (var k=0;k<currentFiles.length;k++) dt.items.add(currentFiles[k]);
            filesToAdd.forEach(function(ff){ dt.items.add(ff); });
            // try to assign; may throw in some browsers
            $('#upload-doc-input')[0].files = dt.files;
        } catch (ex) {
            dtSupported = false;
            // push into fallback array
            filesToAdd.forEach(function(ff){ pastedFiles.push(ff); });
        }

        // create previews for filesToAdd
        filesToAdd.forEach(function(ff){
            try {
                var url = URL.createObjectURL(ff);
                var $img = $('<div class="mr-2 mb-2" style="width:96px;height:64px;overflow:hidden;border:1px solid #e9ecef;border-radius:4px;"></div>');
                var $i = $('<img />').attr('src', url).css({width:'100%',height:'100%',objectFit:'cover'});
                $img.append($i);
                $('#upload-previews').append($img);
            } catch(e){ /* ignore preview errors */ }
        });
        if (dtSupported) {
            toastr && toastr.info && toastr.info('Gambar ditempel dan ditambahkan ke file input');
        } else {
            // Inform the user gently: paste accepted but will be uploaded via fallback
            toastr && toastr.info ? toastr.info('Gambar ditempel (fallback) — akan terunggah saat Anda klik Unggah') : null;
        }
    });

    // Preview files selected in the view modal upload input
    $(document).on('change', '#view-upload-input', function(){
        var files = this.files || [];
        var $pre = $('#view-upload-previews');
        $pre.html('');
        for (var i=0;i<files.length;i++) {
            try {
                var url = URL.createObjectURL(files[i]);
                var $img = $('<div class="mr-2 mb-2" style="width:96px;height:64px;overflow:hidden;border:1px solid #e9ecef;border-radius:4px;"></div>');
                var $i = $('<img />').attr('src', url).css({width:'100%',height:'100%',objectFit:'cover'});
                $img.append($i);
                $pre.append($img);
            } catch(e) { /* ignore */ }
        }
    });

    // Upload from view modal
    $(document).on('click', '#viewUploadBtn', function(){
        var id = $('#viewJobModal').data('job-id');
        if (!id) { Swal.fire({icon:'error', text:'Job ID tidak ditemukan.'}); return; }
        var input = $('#view-upload-input')[0];
        var files = input ? input.files : null;
        if (!files || !files.length) {
            Swal.fire({icon:'warning', text:'Pilih file terlebih dahulu.'});
            return;
        }
        var fd = new FormData();
        for (var i=0;i<files.length;i++) fd.append('dokumen[]', files[i]);
        $.ajax({
            url: '/hrd/joblist/' + id + '/upload-documents',
            method: 'POST',
            data: fd,
            cache: false,
            contentType: false,
            processData: false,
            success: function(res){
                // reload documents list by fetching job
                $.get('/hrd/joblist/' + id, function(r){
                    if (r && r.data) {
                        var docs = r.data.documents || [];
                        var $ct = $('#viewJobDocuments');
                        if (!docs.length) {
                            $ct.html('<div class="small text-muted">Tidak ada bukti terunggah.</div>');
                        } else {
                            var list = '<ul class="list-unstyled small">';
                            docs.forEach(function(p, idx){
                                var url = (p.indexOf('http') === 0) ? p : ('/hrd/joblist/' + id + '/document/' + idx);
                                var fileName = p.split('/').pop();
                                list += '<li><a href="'+url+'" target="_blank">' + fileName + '</a></li>';
                            });
                            list += '</ul>';
                            $ct.html(list);
                        }
                        $('#view-upload-input').val('');
                        $('#view-upload-previews').html('');
                        table.ajax.reload(null, false);
                        Swal.fire({icon:'success', text:'Dokumen berhasil diunggah.'});
                    }
                }).fail(function(){
                    Swal.fire({icon:'success', text:'Dokumen berhasil diunggah.'});
                    table.ajax.reload(null, false);
                });
            },
            error: function(xhr){
                var msg = 'Terjadi kesalahan saat mengunggah';
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    msg = Object.values(xhr.responseJSON.errors).map(function(v){ return v.join(', '); }).join('\n');
                }
                Swal.fire({icon:'error', text: msg});
            }
        });
    });

    // Save notes from view modal
    $(document).on('click', '#viewSaveNotesBtn', function(){
        var id = $('#viewJobModal').data('job-id');
        if (!id) { Swal.fire({icon:'error', text:'Job ID tidak ditemukan.'}); return; }
        var notes = $('#view-notes').val();
        $.ajax({
            url: '/hrd/joblist/' + id + '/notes',
            method: 'POST',
            data: { notes: notes },
            success: function(res){
                if (res && res.success) {
                    table.ajax.reload(null, false);
                    Swal.fire({icon:'success', text:'Catatan berhasil disimpan.'});
                } else {
                    Swal.fire({icon:'error', text:'Gagal menyimpan catatan.'});
                }
            },
            error: function(xhr){
                var msg = 'Terjadi kesalahan saat menyimpan catatan';
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    msg = Object.values(xhr.responseJSON.errors).map(function(v){ return v.join(', '); }).join('\n');
                }
                Swal.fire({icon:'error', text: msg});
            }
        });
    });

    // If modal is closed without uploading while a completion was pending, revert select and clear pending
    $('#uploadDocumentsModal').on('hidden.bs.modal', function(){
        if (pendingCompleteJobId) {
            // revert inline select to original value if present
            if (pendingCompleteSelectElem) {
                var $sel = pendingCompleteSelectElem;
                $sel.val(pendingCompleteOrig);
                $sel.hide();
                $sel.closest('div').find('.status-inline-badge').show();
                pendingCompleteSelectElem = null;
            }
            pendingCompleteJobId = null;
            pendingCompleteOrig = null;
        }
        // clear pastedFiles and previews when modal closed
        pastedFiles = [];
        $('#upload-previews').html('');
    });

    // If user clicks away without changing, hide select and show badge
    $(document).on('blur', '.job-status-select', function(){
        var $select = $(this);
        // small timeout to allow change event to fire first when applicable
        setTimeout(function(){
            if ($select.is(':visible')) {
                var orig = $select.data('original');
                $select.val(orig);
                $select.hide();
                $select.closest('div').find('.status-inline-badge').show();
            }
        }, 200);
    });
});
</script>
@endsection
