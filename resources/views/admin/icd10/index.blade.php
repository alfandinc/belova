@extends('layouts.admin.app')

@section('title', 'Manage ICD 10')

@section('navbar')
    @include('layouts.admin.navbar')
@endsection

@section('content')
<div class="container">
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h3 class="card-title mb-0">Manage ICD 10</h3>
                <button type="button" class="btn btn-primary" id="btnAddIcd10">Add ICD 10</button>
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-hover table-bordered" id="icd10-table" style="width:100%">
                    <thead class="thead-light">
                        <tr>
                            <th style="width:14%">Code</th>
                            <th>Description</th>
                            <th style="width:18%">Category</th>
                            <th style="width:160px">Action</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="icd10Modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form id="icd10Form">
                <div class="modal-header">
                    <h5 class="modal-title" id="icd10ModalLabel">Add ICD 10</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="icd10Id" name="id">
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="icd10Code">Code</label>
                            <input type="text" class="form-control" id="icd10Code" name="code" required>
                        </div>
                        <div class="form-group col-md-8">
                            <label for="icd10Category">Category</label>
                            <input type="text" class="form-control" id="icd10Category" name="category">
                        </div>
                    </div>
                    <div class="form-group mb-0">
                        <label for="icd10Description">Description</label>
                        <textarea class="form-control" id="icd10Description" name="description" rows="4" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    var table = $('#icd10-table').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: '{{ route('admin.icd10.index') }}',
        columns: [
            { data: 'code', name: 'code' },
            { data: 'description', name: 'description' },
            { data: 'category', name: 'category', defaultContent: '-' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ]
    });

    $('#btnAddIcd10').on('click', function() {
        $('#icd10Form')[0].reset();
        $('#icd10Id').val('');
        $('#icd10ModalLabel').text('Add ICD 10');
        $('#icd10Modal').modal('show');
    });

    $('#icd10-table').on('click', '.btn-edit-icd10', function() {
        var id = $(this).data('id');
        $.get('/admin/icd10/' + id, function(data) {
            $('#icd10Id').val(data.id);
            $('#icd10Code').val(data.code);
            $('#icd10Description').val(data.description);
            $('#icd10Category').val(data.category);
            $('#icd10ModalLabel').text('Edit ICD 10');
            $('#icd10Modal').modal('show');
        });
    });

    $('#icd10Form').on('submit', function(e) {
        e.preventDefault();
        var id = $('#icd10Id').val();
        var url = id ? '/admin/icd10/' + id : '/admin/icd10';
        var method = id ? 'PUT' : 'POST';

        $.ajax({
            url: url,
            type: method,
            data: $(this).serialize(),
            success: function() {
                $('#icd10Modal').modal('hide');
                table.ajax.reload(null, false);
                Swal.fire('Success', 'ICD 10 saved successfully.', 'success');
            },
            error: function(xhr) {
                Swal.fire('Failed', xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'An error occurred.', 'error');
            }
        });
    });

    $('#icd10-table').on('click', '.btn-delete-icd10', function() {
        var id = $(this).data('id');
        var name = $(this).data('name') || 'this ICD 10';

        Swal.fire({
            title: 'Delete ICD 10?',
            text: 'You are about to delete ' + name + '.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Delete',
            cancelButtonText: 'Cancel'
        }).then(function(result) {
            if (!result.value) {
                return;
            }

            $.ajax({
                url: '/admin/icd10/' + id,
                type: 'DELETE',
                success: function() {
                    table.ajax.reload(null, false);
                    Swal.fire('Deleted', 'ICD 10 deleted successfully.', 'success');
                },
                error: function(xhr) {
                    Swal.fire('Failed', xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'An error occurred.', 'error');
                }
            });
        });
    });
});
</script>
@endsection
