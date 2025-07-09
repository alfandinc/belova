@extends('layouts.inventory.app')
@section('title', 'Ruangan Management')
@section('navbar')
    @include('layouts.inventory.navbar')
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="row">
                    <div class="col">
                        <h4 class="page-title">Ruangan Management</h4>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:void(0);">Inventory</a></li>
                            <li class="breadcrumb-item active">Ruangan</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Data Ruangan</h4>
                    <button type="button" class="btn btn-primary" id="createNewRuangan">Add New Ruangan</button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered dt-responsive nowrap data-table" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                            <thead>
                                <tr>
                                    <th width="5%">No</th>
                                    <th>Name</th>
                                    <th>Gedung</th>
                                    <th>Description</th>
                                    <th width="15%">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="ajaxModel" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modelHeading"></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="ruanganForm" name="ruanganForm" class="form-horizontal">
                    <input type="hidden" name="ruangan_id" id="ruangan_id">
                    <div class="form-group mb-3">
                        <label for="gedung_id" class="form-label">Gedung</label>
                        <select class="form-control select2" id="gedung_id" name="gedung_id" required>
                            <option value="">Select Gedung</option>
                            @foreach($gedungs as $gedung)
                                <option value="{{ $gedung->id }}">{{ $gedung->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input type="text" class="form-control" id="name" name="name" placeholder="Enter ruangan name" required>
                    </div>
                    <div class="form-group mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3" placeholder="Enter description"></textarea>
                    </div>
                    <div class="form-group text-right">
                        <button type="submit" class="btn btn-primary" id="saveBtn">Save</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script type="text/javascript">
    $(function() {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        var table = $('.data-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('ruangan.index') }}",
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                {data: 'name', name: 'name'},
                {data: 'gedung', name: 'gedung'},
                {data: 'description', name: 'description'},
                {data: 'action', name: 'action', orderable: false, searchable: false},
            ]
        });

        // Initialize select2
        $('.select2').select2({
            dropdownParent: $('#ajaxModel'),
            width: '100%'
        });

        $('#createNewRuangan').click(function() {
            $('#saveBtn').text('Save');
            $('#ruangan_id').val('');
            $('#ruanganForm').trigger("reset");
            $('#modelHeading').html("Create New Ruangan");
            $('#ajaxModel').modal('show');
            // Reset select2
            $('#gedung_id').val('').trigger('change');
        });

        $('body').on('click', '.edit', function() {
            var ruangan_id = $(this).data('id');
            $.get("{{ route('ruangan.index') }}" + '/' + ruangan_id + '/edit', function(data) {
                $('#modelHeading').html("Edit Ruangan");
                $('#saveBtn').text('Update');
                $('#ajaxModel').modal('show');
                $('#ruangan_id').val(data.id);
                $('#name').val(data.name);
                $('#description').val(data.description);
                $('#gedung_id').val(data.gedung_id).trigger('change');
            });
        });

        $('#saveBtn').click(function(e) {
            e.preventDefault();
            $(this).html('Saving..');

            $.ajax({
                data: $('#ruanganForm').serialize(),
                url: "{{ route('ruangan.store') }}",
                type: "POST",
                dataType: 'json',
                success: function(data) {
                    $('#ruanganForm').trigger("reset");
                    $('#ajaxModel').modal('hide');
                    table.draw();
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: data.message,
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message,
                        });
                    }
                },
                error: function(data) {
                    console.log('Error:', data);
                    $('#saveBtn').html('Save');
                    
                    // Show validation errors
                    var errors = data.responseJSON.errors;
                    if (errors) {
                        var errorMessage = '';
                        $.each(errors, function(key, value) {
                            errorMessage += value + '<br>';
                        });
                        
                        Swal.fire({
                            icon: 'error',
                            title: 'Validation Error',
                            html: errorMessage,
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'An error occurred while saving the data.',
                        });
                    }
                }
            });
        });

        $('body').on('click', '.delete', function() {
            var ruangan_id = $(this).data("id");
            
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        type: "DELETE",
                        url: "{{ route('ruangan.store') }}" + '/' + ruangan_id,
                        success: function(data) {
                            table.draw();
                            if (data.success) {
                                Swal.fire(
                                    'Deleted!',
                                    data.message,
                                    'success'
                                );
                            } else {
                                Swal.fire(
                                    'Error!',
                                    data.message,
                                    'error'
                                );
                            }
                        },
                        error: function(data) {
                            console.log('Error:', data);
                            Swal.fire(
                                'Error!',
                                'Failed to delete the ruangan.',
                                'error'
                            );
                        }
                    });
                }
            });
        });
    });
</script>
@endsection
