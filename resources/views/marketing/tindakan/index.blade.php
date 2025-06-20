@extends('layouts.marketing.app')

@section('title', 'Manage Tindakan - Marketing')

@section('navbar')
    @include('layouts.marketing.navbar')
@endsection

@section('content')
<div class="container-fluid">
    <!-- Page-Title -->
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="row">
                    <div class="col">
                        <h4 class="page-title">Manage Tindakan</h4>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('marketing.dashboard') }}">Marketing</a></li>
                            <li class="breadcrumb-item active">Tindakan Management</li>
                        </ol>
                    </div>
                    <div class="col-auto align-self-center">
                        <button type="button" class="btn btn-primary waves-effect waves-light add-tindakan">
                            <i class="fas fa-plus mr-2"></i> Add Tindakan
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Main content -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Tindakan List</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="tindakan-table" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Price</th>
                                    <th>Specialist</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal for Create/Edit Tindakan -->
<div class="modal fade" id="tindakanModal" tabindex="-1" role="dialog" aria-labelledby="tindakanModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tindakanModalLabel">Add New Tindakan</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>            <form id="tindakanForm">
                @csrf
                <div class="modal-body">
                    <input type="hidden" id="tindakan_id" name="id">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    
                    <div class="form-group">
                        <label for="nama">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nama" name="nama" required>
                        <div class="invalid-feedback" id="nama-error"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="deskripsi">Description</label>
                        <textarea class="form-control" id="deskripsi" name="deskripsi" rows="3"></textarea>
                        <div class="invalid-feedback" id="deskripsi-error"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="harga">Price (Rp) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="harga" name="harga" step="0.01" required>
                        <div class="invalid-feedback" id="harga-error"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="spesialis_id">Specialist <span class="text-danger">*</span></label>
                        <select class="form-control select2" id="spesialis_id" name="spesialis_id" required>
                            <option value="">Select Specialist</option>
                            <!-- Specialist options will be loaded via Ajax -->
                        </select>
                        <div class="invalid-feedback" id="spesialis_id-error"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="saveBtn">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // Set up CSRF token for all AJAX requests
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        
        // Initialize Select2
        $('.select2').select2({
            width: '100%',
            dropdownParent: $('#tindakanModal')
        });
        
        // Load specialists on page load
        loadSpesialisasi();
        
        // Initialize DataTable
        var table = $('#tindakan-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('marketing.tindakan.data') }}",
            columns: [
                {data: 'id', name: 'id'},
                {data: 'nama', name: 'nama'},
                {data: 'deskripsi', name: 'deskripsi'},
                {
                    data: 'harga', 
                    name: 'harga',
                    render: function(data) {
                        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(data);
                    }
                },
                {data: 'spesialis_nama', name: 'spesialis_nama'},
                {data: 'action', name: 'action', orderable: false, searchable: false}
            ]
        });
        
        // Load specialists function
        function loadSpesialisasi() {
            $.ajax({
                url: "{{ route('marketing.spesialisasi.list') }}",
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    $('#spesialis_id').empty();
                    $('#spesialis_id').append('<option value="">Select Specialist</option>');
                    $.each(data, function(key, value) {
                        $('#spesialis_id').append('<option value="' + value.id + '">' + value.nama + '</option>');
                    });
                }
            });
        }
        
        // Open modal to create new tindakan
        $('.add-tindakan').click(function() {
            resetForm();
            $('#tindakanModalLabel').text('Add New Tindakan');
            $('#tindakanModal').modal('show');
        });
        
        // Edit tindakan
        $(document).on('click', '.edit-tindakan', function() {
            resetForm();
            var id = $(this).data('id');
            $('#tindakanModalLabel').text('Edit Tindakan');
            
            $.ajax({
                url: "/marketing/tindakan/" + id,
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    $('#tindakan_id').val(data.id);
                    $('#nama').val(data.nama);
                    $('#deskripsi').val(data.deskripsi);
                    $('#harga').val(data.harga);
                    $('#spesialis_id').val(data.spesialis_id).trigger('change');
                    $('#tindakanModal').modal('show');
                },
                error: function(xhr) {
                    showError(xhr.responseJSON.message);
                }
            });
        });
        
        // Delete tindakan
        $(document).on('click', '.delete-tindakan', function() {
            var id = $(this).data('id');
            
            Swal.fire({
                title: 'Are you sure?',
                text: "This action cannot be undone!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.value) {                    $.ajax({
                        url: "/marketing/tindakan/" + id,
                        type: 'DELETE',
                        data: {
                            "_token": "{{ csrf_token() }}"
                        },
                        success: function(response) {
                            if (response.success) {
                                Swal.fire(
                                    'Deleted!',
                                    response.message,
                                    'success'
                                );
                                table.ajax.reload();
                            } else {
                                showError(response.message);
                            }
                        },
                        error: function(xhr) {
                            showError(xhr.responseJSON.message);
                        }
                    });
                }
            });
        });
        
        // Form submission
        $('#tindakanForm').submit(function(e) {
            e.preventDefault();
            var formData = $(this).serialize();
            var url = "{{ route('marketing.tindakan.store') }}";
            
            $.ajax({
                url: url,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        $('#tindakanModal').modal('hide');
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: response.message
                        });
                        table.ajax.reload();
                    }
                },
                error: function(xhr) {
                    if (xhr.status === 422) {
                        var errors = xhr.responseJSON.errors;
                        displayErrors(errors);
                    } else {
                        showError(xhr.responseJSON.message);
                    }
                }
            });
        });
        
        // Display validation errors
        function displayErrors(errors) {
            resetErrors();
            $.each(errors, function(field, messages) {
                var input = $('#' + field);
                input.addClass('is-invalid');
                $('#' + field + '-error').text(messages[0]);
            });
        }
        
        // Reset errors
        function resetErrors() {
            $('.is-invalid').removeClass('is-invalid');
            $('.invalid-feedback').text('');
        }
        
        // Reset form
        function resetForm() {
            $('#tindakanForm')[0].reset();
            resetErrors();
            $('#tindakan_id').val('');
            $('.select2').val('').trigger('change');
        }
        
        // Show error alert
        function showError(message) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: message
            });
        }
    });
</script>
@endsection
