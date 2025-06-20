@extends('layouts.marketing.app')

@section('title', 'Manage Paket Tindakan - Marketing')

@section('navbar')
    @include('layouts.marketing.navbar')
@endsection

@section('styles')
<!-- Add these styles to ensure proper rendering of Select2 in modal -->
<style>
    .select2-container {
        width: 100% !important;
    }
    
    .select2-container--open {
        z-index: 9999; /* Ensure dropdown appears above modal */
    }
    
    .select2-dropdown {
        z-index: 10000;
    }
    
    .select2-selection--multiple {
        min-height: 38px;
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
                    </div>
                    <div class="col-auto align-self-center">
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
            </div>
            <form id="paketForm">
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
                        <label for="spesialisasi_id">Specialization <span class="text-danger">*</span></label>
                        <select class="form-control" id="spesialisasi_id" name="spesialisasi_id" required>
                            <option value="">Select Specialization</option>
                            <!-- Options will be loaded via AJAX -->
                        </select>
                        <div class="invalid-feedback" id="spesialisasi_id-error"></div>
                    </div>

                    <div class="form-group">
                        <label for="tindakan_ids">Tindakan <span class="text-danger">*</span></label>
                        <select class="form-control" id="tindakan_ids" name="tindakan_ids[]" multiple="multiple" required>
                            <!-- Options loaded via AJAX with filter -->
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
<script>
$(document).ready(function() {
    // Set up CSRF token for all AJAX requests
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

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

    // Make sure Select2 is properly initialized when modal opens
    $('#paketModal').on('shown.bs.modal', function() {
        initSelects();
    });

    // Initialize both select boxes
    function initSelects() {
        // Initialize specialization dropdown
        $('#spesialisasi_id').select2({
            dropdownParent: $('#paketModal'),
            placeholder: 'Select Specialization',
            allowClear: true,
            width: '100%',
            ajax: {
                url: "{{ route('marketing.spesialisasi.list') }}",
                dataType: 'json',
                delay: 250,
                processResults: function(data) {
                    return {
                        results: $.map(data, function(item) {
                            return {
                                text: item.nama,
                                id: item.id
                            }
                        })
                    };
                },
                cache: true
            }
        });

        // Initialize tindakan dropdown
        $('#tindakan_ids').select2({
            dropdownParent: $('#paketModal'),
            placeholder: 'Type at least 2 characters to search tindakan',
            allowClear: true,
            width: '100%',
            multiple: true,
            minimumInputLength: 2,
            ajax: {
                url: "{{ route('marketing.tindakan.search') }}",
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        q: params.term,
                        spesialisasi_id: $('#spesialisasi_id').val()
                    };
                },
                processResults: function(data) {
                    return {
                        results: data.results || []
                    };
                },
                cache: true
            }
        });
    }

    // When specialization changes, reset tindakan dropdown
    $(document).on('change', '#spesialisasi_id', function() {
        $('#tindakan_ids').val(null).trigger('change');
    });

    // Initialize specialization select2 for edit mode
    function initSpesialisasiSelect(selectedId = '') {
        // Clear any existing options
        $('#spesialisasi_id').empty();
        
        $('#spesialisasi_id').select2({
            dropdownParent: $('#paketModal'),
            placeholder: 'Select Specialization',
            allowClear: true,
            width: '100%',
            ajax: {
                url: "{{ route('marketing.spesialisasi.list') }}",
                dataType: 'json',
                delay: 250,
                processResults: function(data) {
                    return {
                        results: $.map(data, function(item) {
                            return {
                                text: item.nama,
                                id: item.id
                            }
                        })
                    };
                },
                cache: true
            }
        }).on('change', function() {
            // When specialization changes, reset the tindakan dropdown
            $('#tindakan_ids').val(null).trigger('change');
        });

        // Set pre-selected value if available
        if (selectedId) {
            $.ajax({
                type: 'GET',
                url: "{{ route('marketing.spesialisasi.list') }}",
                success: function(data) {
                    var spesialisasi = data.find(item => item.id == selectedId);
                    if (spesialisasi) {
                        var option = new Option(spesialisasi.nama, spesialisasi.id, true, true);
                        $('#spesialisasi_id').append(option).trigger('change');
                    }
                }
            });
        }
    }

    // Initialize tindakan select2 with filtering for edit mode
    function initTindakanSelect(selected = []) {
        // Clear any existing options
        $('#tindakan_ids').empty();
        
        $('#tindakan_ids').select2({
            dropdownParent: $('#paketModal'),
            placeholder: 'Type at least 2 characters to search tindakan',
            allowClear: true,
            width: '100%',
            multiple: true,
            minimumInputLength: 2,
            ajax: {
                url: "{{ route('marketing.tindakan.search') }}",
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        q: params.term,
                        spesialisasi_id: $('#spesialisasi_id').val()
                    };
                },
                processResults: function(data) {
                    return {
                        results: data.results || []
                    };
                },
                cache: true
            }
        });

        // If there are selected values, load them
        if (selected && selected.length > 0) {
            $.ajax({
                type: 'GET',
                url: "{{ route('marketing.tindakan.search') }}",
                data: { 
                    q: '',
                    spesialisasi_id: $('#spesialisasi_id').val()
                },
                success: function(data) {
                    if (data && data.results) {
                        selected.forEach(function(id) {
                            var tindakan = data.results.find(item => item.id == id);
                            if (tindakan) {
                                var option = new Option(tindakan.text, tindakan.id, true, true);
                                $('#tindakan_ids').append(option);
                            }
                        });
                        $('#tindakan_ids').trigger('change');
                    }
                }
            });
        }
    }

    // When opening modal for add
    $('[data-target="#paketModal"]').on('click', function() {
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
                
                // Get the specialization of the first tindakan
                if (response.tindakan_ids.length > 0) {
                    $.ajax({
                        url: "/marketing/tindakan/" + response.tindakan_ids[0],
                        type: 'GET',
                        success: function(tindakanData) {
                            // Initialize specialization select with the specialization of first tindakan
                            initSpesialisasiSelect(tindakanData.spesialis_id);
                            
                            // Then initialize tindakan select with selected IDs
                            setTimeout(function() {
                                initTindakanSelect(response.tindakan_ids);
                            }, 500);
                        }
                    });
                } else {
                    initSpesialisasiSelect();
                    initTindakanSelect([]);
                }
                
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
            if (result.value) {
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
    }

    // Reset form
    function resetForm() {
        $('#paketForm')[0].reset();
        resetErrors();
        $('#paket_id').val('');
        
        // Reset all select2 fields
        if ($('#spesialisasi_id').data('select2')) {
            $('#spesialisasi_id').val(null).trigger('change');
        }
        
        if ($('#tindakan_ids').data('select2')) {
            $('#tindakan_ids').val(null).trigger('change');
        }
        
        $('#spesialisasi_id').empty();
        $('#tindakan_ids').empty();
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