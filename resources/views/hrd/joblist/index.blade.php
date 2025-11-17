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
                <select id="filter_division" class="form-control">
                    <option value="">Semua Division</option>
                    @php $userDivisionId = optional(Auth::user()->employee)->division_id; @endphp
                    @foreach($divisions as $d)
                        <option value="{{ $d->id }}" @if($d->id == $userDivisionId) selected @endif>{{ $d->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <table class="table table-striped" id="joblist-table" style="width:100%">
        <thead>
            <tr>
                <th>Number</th>
                <th>Title</th>
                <th>Division</th>
                <th>Priority</th>
                <th>Status</th>
                <th>Due Date</th>
                <th>Creator</th>
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
                        <option value="low" selected>Low</option>
                        <option value="normal">Normal</option>
                        <option value="important">Important</option>
                        <option value="very_important">Very Important</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Division</label>
                    <select name="division_id" id="division_id" class="form-control">
                        <option value="">-- Pilih Division --</option>
                        @php $userDivisionId = optional(Auth::user()->employee)->division_id; @endphp
                        @foreach($divisions as $d)
                            <option value="{{ $d->id }}" @if($d->id == $userDivisionId) selected @endif>{{ $d->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-6">
                    <label>Due Date</label>
                    <input type="date" name="due_date" id="due_date" class="form-control" />
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
    var table = $('#joblist-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{!! route("hrd.joblist.data") !!}',
            data: function(d) {
                d.status = $('#filter_status').val();
                d.division_id = $('#filter_division').val();
            }
        },
        columns: [
            { data: 'id', name: 'id', render: function(data, type, row, meta) { return meta.row + meta.settings._iDisplayStart + 1; } },
            { data: 'title', name: 'title' },
            { data: 'division_name', name: 'division.name' },
            { data: 'priority_badge', name: 'priority', orderable: true, searchable: true },
            { data: 'status_badge', name: 'status', orderable: true, searchable: true },
            { data: 'due_date_display', name: 'due_date', orderable: true, searchable: false },
            { data: 'creator_name', name: 'creator.name' },
            { data: 'actions', name: 'actions', orderable:false, searchable:false }
        ]
    });

    $('#btnAddJob').on('click', function(){
        $('#jobForm')[0].reset();
        $('#job_id').val('');
        $('#jobModalLabel').text('Tambah Job');
        $('#jobModal').modal('show');
    });

    // reload table when filter changes
    $('#filter_status').on('change', function(){
        table.ajax.reload();
    });
    $('#filter_division').on('change', function(){
        table.ajax.reload();
    });

    $('#saveJobBtn').on('click', function(){
        var id = $('#job_id').val();
        var url = id ? '/hrd/joblist/' + id : '/hrd/joblist';
        var method = id ? 'POST' : 'POST';
        var data = $('#jobForm').serialize();
        $.ajax({
            url: url,
            method: method,
            data: data,
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
            $('#division_id').val(data.division_id);
            $('#due_date').val(data.due_date);
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
            if (result.isConfirmed) {
                $.ajax({ url: '/hrd/joblist/' + id, method: 'DELETE', success: function(){
                    table.ajax.reload(null, false);
                    Swal.fire({icon:'success', title:'Terhapus'});
                }});
            }
        });
    });
});
</script>
@endsection
