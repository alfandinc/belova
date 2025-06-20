@extends('layouts.marketing.app')

@section('title', 'Manage Paket Tindakan - Marketing')

@section('navbar')
    @include('layouts.marketing.navbar')
@endsection

@section('styles')
<style>
    /* Improve Select2 styling */
    .select2-container--default .select2-selection--multiple {
        border: 1px solid #ced4da;
        min-height: 38px;
    }
    
    .select2-container--default.select2-container--focus .select2-selection--multiple {
        border-color: #80bdff;
        box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
    }
    
    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background-color: #007bff;
        border-color: #0069d9;
        color: #fff;
        padding: 2px 8px;
    }
    
    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
        color: #fff;
        margin-right: 5px;
    }
    
    .select2-result-tindakan__name {
        font-weight: bold;
    }
    
    .select2-result-tindakan__price {
        margin-left: 8px;
    }
    
    .is-invalid + .select2-container .select2-selection {
        border-color: #dc3545;
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <!-- Page-Title -->
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="row">
                    <div class="col">
                        <h4 class="page-title">Manage Paket Tindakan</h4>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('marketing.dashboard') }}">Marketing</a></li>
                            <li class="breadcrumb-item active">Paket Tindakan Management</li>
                        </ol>
                    </div>                    <div class="col-auto align-self-center">
                        <button type="button" class="btn btn-primary waves-effect waves-light" data-toggle="modal" data-target="#paketModal">
                            <i class="fas fa-plus mr-2"></i> Add Paket Tindakan
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
                    <h4 class="card-title">Paket Tindakan List</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="paket-table" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Price</th>
                                    <th>Included Tindakan</th>
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

<!-- Modal for Create/Edit Paket Tindakan -->
<div class="modal fade" id="paketModal" tabindex="-1" role="dialog" aria-labelledby="paketModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="paketModalLabel">Add New Paket Tindakan</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div><form id="paketForm">
                @csrf
                <div class="modal-body">
                    <input type="hidden" id="paket_id" name="id">
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
                        <label for="harga_paket">Package Price (Rp) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="harga_paket" name="harga_paket" step="0.01" required>
                        <div class="invalid-feedback" id="harga_paket-error"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="tindakan_ids">Included Tindakan <span class="text-danger">*</span></label>
                        <select class="form-control select2-multiple" id="tindakan_ids" name="tindakan_ids[]" multiple="multiple" required>
                            <!-- Tindakan options will be loaded via Ajax -->
                        </select>
                        <div class="invalid-feedback" id="tindakan_ids-error"></div>
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
<script>    $(document).ready(function() {
        
        // Set up CSRF token for all AJAX requests
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });        // Initialize Select2 for multiple selection
        $('.select2-multiple').select2({
            dropdownParent: $('#paketModal'),
            placeholder: 'Select Tindakan',
            allowClear: true,
            width: '100%',
            templateResult: formatTindakan,
            templateSelection: formatTindakanSelection,
            closeOnSelect: false
        });
        
        // Load tindakan on page load
        loadTindakan();

        
        // Initialize DataTable
        var table = $('#paket-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('marketing.tindakan.paket.data') }}",
            columns: [
                {data: 'id', name: 'id'},
                {data: 'nama', name: 'nama'},
                {data: 'deskripsi', name: 'deskripsi'},
                {
                    data: 'harga_paket', 
                    name: 'harga_paket',
                    render: function(data) {
                        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(data);
                    }
                },
                {data: 'tindakan_list', name: 'tindakan_list'},
                {data: 'action', name: 'action', orderable: false, searchable: false}
            ]
        });
          // Load tindakan function        function loadTindakan() {
            $.ajax({
                url: "{{ route('marketing.tindakan.list') }}",
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    $('#tindakan_ids').empty();
                    $.each(data, function(key, value) {
                        var harga = value.harga || 0;
                        var specialistName = value.spesialis ? value.spesialis.nama : '';
                        var formattedPrice = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(harga);
                        var displayText = value.nama + ' - ' + formattedPrice;
                        
                        $('#tindakan_ids').append(
                            '<option value="' + value.id + '" ' +
                            'data-price="' + harga + '" ' +
                            'data-specialist="' + specialistName + '">' +
                            displayText + 
                            '</option>'
                        );
                    });
                },
                error: function(xhr) {
                    showError("Failed to load tindakan list. Please refresh the page.");
                }
            });
        }
        
        // Format tindakan in dropdown        function formatTindakan(tindakan) {
            if (!tindakan.id) {
                return tindakan.text;
            }
            
            var price = $(tindakan.element).data('price') || 0;
            var specialist = $(tindakan.element).data('specialist');
            var formattedPrice = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(price);
            var name = tindakan.text.split(' - ')[0];
            
            var $tindakan = $(
                '<div class="select2-result-tindakan d-flex align-items-center p-1">' +
                    '<span class="select2-result-tindakan__name">' + name + '</span>' +
                    '<span class="select2-result-tindakan__price badge badge-info ml-2">' + formattedPrice + '</span>' +
                    (specialist ? '<span class="badge badge-secondary ml-2">' + specialist + '</span>' : '') +
                '</div>'
            );
            
            return $tindakan;
        }
        
        // Format selected tindakan
        function formatTindakanSelection(tindakan) {
            if (!tindakan.id) {
                return tindakan.text;
            }
            
            // Return name only (without price)
            return tindakan.text.split(' - ')[0];
        }        // Reset form when modal is about to be shown
        $('#paketModal').on('show.bs.modal', function (e) {
            resetForm();
            $('#paketModalLabel').text('Add New Paket Tindakan');
        });
          // Edit paket tindakan
        $(document).on('click', '.edit-paket', function() {
            resetForm();
            var id = $(this).data('id');
            $('#paketModalLabel').text('Edit Paket Tindakan');
            
            $.ajax({
                url: "/marketing/tindakan/paket/" + id,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    $('#paket_id').val(response.paket.id);
                    $('#nama').val(response.paket.nama);
                    $('#deskripsi').val(response.paket.deskripsi);
                    $('#harga_paket').val(response.paket.harga_paket);
                    $('#tindakan_ids').val(response.tindakan_ids).trigger('change');
                      // Show the modal
                    $('#paketModal').modal('show');
                },
                error: function(xhr) {
                    showError(xhr.responseJSON ? xhr.responseJSON.message : 'Error loading data');
                }
            });
        });
        
        // Delete paket tindakan
        $(document).on('click', '.delete-paket', function() {
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
                if (result.isConfirmed) {
                    $.ajax({
                        url: "/marketing/tindakan/paket/" + id,
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
        $('#paketForm').submit(function(e) {
            e.preventDefault();
            var formData = $(this).serialize();
            var url = "{{ route('marketing.tindakan.paket.store') }}";
            
            $.ajax({
                url: url,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        $('#paketModal').modal('hide');
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
        }        // Reset form
        function resetForm() {
            $('#paketForm')[0].reset();
            resetErrors();
            $('#paket_id').val('');
            $('#tindakan_ids').val(null).trigger('change');
        }
        
        // Show error alert
        function showError(message) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: message
            });
        }
          // No manual button handler needed
    });
</script>
@endsection
