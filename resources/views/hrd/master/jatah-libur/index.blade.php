@extends('layouts.hrd.app')
@section('navbar')
    @include('layouts.hrd.navbar')
@endsection

@section('title', 'Master Data Jatah Libur')

@section('content')
<div class="container-fluid">
    <!-- Page-Title -->
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="row">
                    <div class="col">
                        <h4 class="page-title">Master Data</h4>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:void(0);">HRD</a></li>
                            <li class="breadcrumb-item"><a href="javascript:void(0);">Master Data</a></li>
                            <li class="breadcrumb-item active">Jatah Libur</li>
                        </ol>
                    </div><!--end col-->
                </div><!--end row-->
            </div><!--end page-title-box-->
        </div><!--end col-->
    </div><!--end row-->
    <!-- end page title end breadcrumb -->

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Data Jatah Libur Karyawan</h4>
                    <button type="button" class="btn btn-primary btn-sm" id="btnAddJatahLibur">
                        <i class="fa fa-plus"></i> Tambah Jatah Libur
                    </button>
                </div><!--end card-header-->
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="jatahLiburTable" class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>ID Karyawan</th>
                                    <th>Nama Karyawan</th>
                                    <th>Divisi</th>
                                    <th>Cuti Tahunan</th>
                                    <th>Ganti Libur</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data will be loaded by DataTable -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div> <!-- end col -->
    </div> <!-- end row -->
</div><!-- container -->

<!-- Add/Edit Jatah Libur Modal -->
<div class="modal fade" id="jatahLiburModal" tabindex="-1" role="dialog" aria-labelledby="jatahLiburModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="jatahLiburModalLabel">Tambah Jatah Libur</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="jatahLiburForm">
                @csrf
                <div class="modal-body">
                    <input type="hidden" id="jatah_libur_id" name="jatah_libur_id">
                    
                    <div class="form-group" id="employee_selection_group">
                        <label for="employee_id">Karyawan <span class="text-danger">*</span></label>
                        <select class="form-control" id="employee_id" name="employee_id" required>
                            <option value="">Pilih Karyawan</option>
                            <!-- Options will be loaded via AJAX -->
                        </select>
                        <div class="invalid-feedback" id="employee_id-error"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="jatah_cuti_tahunan">Jatah Cuti Tahunan <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="jatah_cuti_tahunan" name="jatah_cuti_tahunan" min="0" value="0" required>
                        <div class="invalid-feedback" id="jatah_cuti_tahunan-error"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="jatah_ganti_libur">Jatah Ganti Libur <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="jatah_ganti_libur" name="jatah_ganti_libur" min="0" value="0" required>
                        <div class="invalid-feedback" id="jatah_ganti_libur-error"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-primary" id="saveJatahLibur">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // Initialize DataTable
        var table = $('#jatahLiburTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('hrd.master.jatah-libur.data') }}",
            columns: [
                {data: 'id', name: 'id'},
                {data: 'employee_number', name: 'employee_number'},
                {data: 'employee_name', name: 'employee_name'},
                {data: 'division', name: 'division'},
                {data: 'jatah_cuti_tahunan', name: 'jatah_cuti_tahunan'},
                {data: 'jatah_ganti_libur', name: 'jatah_ganti_libur'},
                {data: 'action', name: 'action', orderable: false, searchable: false}
            ]
        });

        // Initialize select2 for employee dropdown
        $('#employee_id').select2({
            dropdownParent: $('#jatahLiburModal'),
            placeholder: "Pilih Karyawan",
            width: '100%'
        });

        // Open modal for adding new jatah libur
        $('#btnAddJatahLibur').on('click', function() {
            $('#jatahLiburModalLabel').text('Tambah Jatah Libur');
            $('#jatahLiburForm')[0].reset();
            $('#jatah_libur_id').val('');
            
            // Show employee selection and ensure required attribute is set
            $('#employee_selection_group').show();
            $('#employee_id').attr('required', 'required');
            
            // Remove any hidden employee_id field if it exists
            $('#hidden_employee_id').remove();
            
            // Load employees without jatah libur
            loadEmployeesWithoutJatahLibur();
            
            $('.invalid-feedback').text('');
            $('#jatahLiburModal').modal('show');
        });

        function loadEmployeesWithoutJatahLibur() {
            console.log('Loading employees without jatah libur...');
            $.ajax({
                url: "{{ route('hrd.master.jatah-libur.employees-without-jatah-libur') }}",
                method: 'GET',
                dataType: 'json',
                beforeSend: function() {
                    $('#employee_id').empty().append('<option value="">Loading...</option>');
                },
                error: function(xhr, status, error) {
                    console.error('Error loading employees:', error);
                    console.error('Status:', status);
                    console.error('Response:', xhr.responseText);
                    $('#employee_id').empty().append('<option value="">Error loading data</option>');
                    
                    // Show error to user with more details
                    var errorMsg = 'Failed to load employee data: ' + error;
                    if (xhr.responseText) {
                        try {
                            var jsonResponse = JSON.parse(xhr.responseText);
                            if (jsonResponse.error) {
                                errorMsg += '<br>Details: ' + jsonResponse.error;
                            }
                        } catch (e) {
                            errorMsg += '<br>Response: ' + xhr.responseText.substring(0, 100);
                        }
                    }
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        html: errorMsg
                    });
                },
                success: function(response) {
                    console.log('Employees received:', response);
                    $('#employee_id').empty().append('<option value="">Pilih Karyawan</option>');
                    
                    if (response.error) {
                        console.error('Server returned an error:', response.error);
                        $('#employee_id').append('<option value="">Error: ' + response.error + '</option>');
                        return;
                    }
                    
                    if (!Array.isArray(response)) {
                        console.error('Expected array but got:', typeof response);
                        $('#employee_id').append('<option value="">Invalid response format</option>');
                        return;
                    }
                    
                    $.each(response, function(index, employee) {
                        // Make sure we have values before displaying them
                        var employeeNumber = employee.employee_number || 'No ID';
                        var employeeName = employee.name || 'Unnamed';
                        $('#employee_id').append('<option value="' + employee.id + '">' + employeeNumber + ' - ' + employeeName + '</option>');
                    });
                    
                    if (response.length === 0) {
                        $('#employee_id').append('<option value="">Semua karyawan sudah memiliki jatah libur</option>');
                    }
                }
            });
        }

        // Handle form submission
        $('#jatahLiburForm').on('submit', function(e) {
            e.preventDefault();
            var id = $('#jatah_libur_id').val();
            var url = id ? "{{ route('hrd.master.jatah-libur.update', ':id') }}".replace(':id', id) : "{{ route('hrd.master.jatah-libur.store') }}";
            var method = id ? 'PUT' : 'POST';

            var formData = $(this).serialize();
            formData += '&_token=' + $('meta[name="csrf-token"]').attr('content');
            
            $.ajax({
                url: url,
                method: method,
                data: formData,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                beforeSend: function() {
                    // Clear previous validation errors
                    $('.invalid-feedback').text('');
                    $('.is-invalid').removeClass('is-invalid');
                    $('#saveJatahLibur').attr('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Menyimpan...');
                },
                success: function(response) {
                    Swal.fire({
                        title: 'Sukses!',
                        text: response.message,
                        icon: 'success',
                        confirmButtonText: 'OK'
                    });
                    $('#jatahLiburModal').modal('hide');
                    table.ajax.reload();
                },
                error: function(xhr) {
                    $('#saveJatahLibur').attr('disabled', false).html('Simpan');
                    if (xhr.status === 422) {
                        var errors = xhr.responseJSON.errors;
                        $.each(errors, function(key, value) {
                            $('#' + key).addClass('is-invalid');
                            $('#' + key + '-error').text(value[0]);
                        });
                    } else if (xhr.status === 500) {
                        Swal.fire({
                            title: 'Error!',
                            text: 'Terjadi kesalahan pada server',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                },
                complete: function() {
                    $('#saveJatahLibur').attr('disabled', false).html('Simpan');
                }
            });
        });

        // Edit Jatah Libur
        $(document).on('click', '.edit-jatah-libur', function() {
            var id = $(this).data('id');
            $('.invalid-feedback').text('');
            $('.is-invalid').removeClass('is-invalid');
            
            $.ajax({
                url: "{{ route('hrd.master.jatah-libur.show', ':id') }}".replace(':id', id),
                method: 'GET',
                success: function(response) {
                    $('#jatahLiburModalLabel').text('Edit Jatah Libur');
                    $('#jatah_libur_id').val(response.id);
                    $('#jatah_cuti_tahunan').val(response.jatah_cuti_tahunan);
                    $('#jatah_ganti_libur').val(response.jatah_ganti_libur);
                    
                    // Hide employee selection when editing and remove required attribute
                    $('#employee_selection_group').hide();
                    $('#employee_id').removeAttr('required');
                    
                    // Add hidden input for employee_id to ensure it's submitted with the form
                    if (!$('#hidden_employee_id').length) {
                        $('<input>').attr({
                            type: 'hidden',
                            id: 'hidden_employee_id',
                            name: 'employee_id',
                            value: response.employee_id
                        }).appendTo('#jatahLiburForm');
                    } else {
                        $('#hidden_employee_id').val(response.employee_id);
                    }
                    
                    $('#jatahLiburModal').modal('show');
                }
            });
        });
    });
</script>
@endsection
