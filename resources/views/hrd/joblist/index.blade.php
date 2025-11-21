@extends('layouts.hrd.app')

@section('navbar')
    @include('layouts.hrd.navbar-joblist')
@endsection

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>Job List</h4>
        <div>
            <button id="btnAddJob" class="btn btn-primary">Tambah Job</button>
            <div class="d-inline-block ml-2">
                <select id="filter_status" class="form-control">
                    <option value="">Semua Status</option>
                    <option value="progress" selected>On Going</option>
                    <option value="done">Done</option>
                    <option value="canceled">Canceled</option>
                </select>
            </div>
            <div class="d-inline-block ml-2">
                @php
                    $user = Auth::user();
                    $isManager = $user && $user->hasRole('Manager');
                    // Treat Admin and Hrd the same as Ceo for filter/default behavior
                    $isGlobalRole = $user && $user->hasAnyRole(['Ceo','Admin','Hrd']);
                    $isEmployeeOnly = $user && !$isManager && !$isGlobalRole; // treat as non-manager employee
                    // Determine default selection:
                    // - Manager: show all (empty)
                    // - Employee (non-manager): locked to Non-Manager (0)
                    // - Ceo: default to Manager Only (1) but editable
                    $defaultForManager = '';
                    if ($isEmployeeOnly) $defaultForManager = '0';
                    if ($isGlobalRole) $defaultForManager = '1';
                @endphp
                <select id="filter_for_manager" class="form-control" @if($isEmployeeOnly) disabled title="Locked to Non-Manager" @endif>
                    <option value="" @if($defaultForManager === '') selected @endif>Semua (Manager/Non-Manager)</option>
                    <option value="1" @if($defaultForManager === '1') selected @endif>For Manager Only</option>
                    <option value="0" @if($defaultForManager === '0') selected @endif>Non-Manager Only</option>
                </select>
            </div>
            <div class="d-inline-block ml-2">
                @php
                    $user = Auth::user();
                    $userDivisionId = optional($user->employee)->division_id;
                    $isGlobalRole = $user && $user->hasAnyRole(['Ceo','Admin','Hrd']);
                @endphp
                @if($user && $user->hasAnyRole(['Hrd','Admin','Manager','Ceo']))
                    <select id="filter_division" class="form-control">
                        <option value="" @if($isGlobalRole) selected @endif>Semua Division</option>
                        @foreach($divisions as $d)
                            <option value="{{ $d->id }}" @if(!$isGlobalRole && $d->id == $userDivisionId) selected @endif>{{ $d->name }}</option>
                        @endforeach
                    </select>
                @else
                    {{-- Non-privileged users: lock to their division --}}
                    <select id="filter_division" class="form-control" disabled title="Division locked">
                        @if($userDivisionId)
                            @php $current = $divisions->firstWhere('id', $userDivisionId); @endphp
                            <option value="{{ $userDivisionId }}">{{ $current?->name ?? 'Division' }}</option>
                        @else
                            <option value="">- Tidak ada Division -</option>
                        @endif
                    </select>
                @endif
            </div>
            
        </div>
    </div>

    <table class="table table-striped" id="joblist-table" style="width:100%">
        <thead>
            <tr>
                <th>No</th>
                <th>Title</th>
                <th>Division</th>
                <th>Priority</th>
                <th>Status</th>
                <th>Due Date</th>
                <th>Creator</th>
                <th>Updated By</th>
                <th>Actions</th>
            </tr>
        </thead>
    </table>
</div>

<style>
/* blinking warning */
.blink { color: #dc3545; font-weight: 600; display:inline-block; animation: blinker 1s linear infinite; }
@keyframes blinker { 50% { opacity: 0; } }
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
                    <select name="status" id="status" class="form-control">
                        <option value="progress" selected>Progress</option>
                        <option value="done">Done</option>
                        <option value="canceled">Canceled</option>
                    </select>
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
        }
    } catch (e) { /* ignore if plugin missing */ }

    var table = $('#joblist-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{!! route("hrd.joblist.data") !!}',
            data: function(d) {
                d.status = $('#filter_status').val();
                d.division_id = $('#filter_division').val();
                // send for_manager filter: '' => all, '1' => only manager items, '0' => non-manager items
                d.for_manager = $('#filter_for_manager').length ? $('#filter_for_manager').val() : '';
            }
        },
        // Show all entries by default; include 'All' option in lengthMenu
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'All']],
        pageLength: -1,
        columns: [
            { data: 'id', name: 'id', render: function(data, type, row, meta) { return meta.row + meta.settings._iDisplayStart + 1; } },
            { data: 'title', name: 'title' },
            { data: 'division_name', name: 'division.name' },
            { data: 'priority_badge', name: 'priority', orderable: true, searchable: true },
            { data: 'status_control', name: 'status', orderable: true, searchable: true },
            { data: 'due_date_display', name: 'due_date', orderable: true, searchable: false },
            { data: 'creator_name', name: 'creator.name' },
            { data: 'updater_name', name: 'updater.name' },
            { data: 'actions', name: 'actions', orderable:false, searchable:false }
        ]
    });

    

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
        $('#jobModal').modal('show');
    });

    // reload table when filter changes
    $('#filter_status').on('change', function(){
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

    // Click badge to edit: swap badge -> select
    $(document).on('click', '.status-inline-badge', function(){
        var $badge = $(this);
        var $container = $badge.closest('div');
        var $select = $container.find('.job-status-select');
        // store original value
        $select.data('original', $select.val());
        $badge.hide();
        // show select and attempt to open dropdown programmatically so one click suffices
        $select.show();
        // focus then dispatch synthetic events; some browsers open native select on click
        try {
            $select[0].focus();
            // small delay to ensure element is visible
            setTimeout(function(){
                // trigger jQuery events
                $select.trigger('mousedown').trigger('mouseup').trigger('click');
                // dispatch native events as well
                var el = $select[0];
                el.dispatchEvent(new MouseEvent('mousedown', {bubbles:true}));
                el.dispatchEvent(new MouseEvent('mouseup', {bubbles:true}));
                el.dispatchEvent(new MouseEvent('click', {bubbles:true}));
            }, 10);
        } catch(e) {
            // fallback: just focus
            $select.focus();
        }
    });

    // Helper: map status to badge class and label
    function statusToBadge(status) {
        var cls = 'badge-info';
        var label = status.charAt(0).toUpperCase() + status.slice(1);
        if (status === 'done') cls = 'badge-success';
        if (status === 'canceled') cls = 'badge-danger';
        if (status === 'progress') cls = 'badge-info';
        return { cls: cls, label: label };
    }

    // On change -> send inline update and update badge in-place
    $(document).on('change', '.job-status-select', function(){
        var $select = $(this);
        var id = $select.data('id');
        var newStatus = $select.val();
        var orig = $select.data('original');
        $.ajax({
            url: '/hrd/joblist/' + id + '/inline-update',
            method: 'POST',
            data: { status: newStatus },
            success: function(res){
                if (res.success) {
                    var info = statusToBadge(newStatus);
                    var $container = $select.closest('div');
                    var $badge = $container.find('.status-inline-badge');
                    $badge.text(info.label).removeClass('badge-info badge-success badge-danger').addClass(info.cls);
                    $select.hide();
                    $badge.show();
                    Swal.fire({icon: 'success', title: 'Status diperbarui'});
                } else {
                    $select.val(orig);
                    $select.hide();
                    $select.closest('div').find('.status-inline-badge').show();
                    Swal.fire({icon: 'error', text: 'Gagal memperbarui status'});
                }
            },
            error: function(xhr){
                var msg = 'Terjadi kesalahan';
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    msg = Object.values(xhr.responseJSON.errors).map(function(v){ return v.join(', '); }).join('\n');
                }
                $select.val(orig);
                $select.hide();
                $select.closest('div').find('.status-inline-badge').show();
                Swal.fire({icon:'error', text: msg});
            }
        });
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
