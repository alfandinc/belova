@extends('layouts.hrd.app')
@section('navbar')
    @include('layouts.hrd.navbar')
@endsection

@section('title', 'Master Data Jabatan')

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
                            <li class="breadcrumb-item active">Jabatan</li>
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
                    <h4 class="card-title">Data Jabatan</h4>
                    <button type="button" class="btn btn-primary btn-sm" id="btnAddPosition">
                        <i class="fa fa-plus"></i> Tambah Jabatan
                    </button>
                </div><!--end card-header-->
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="positionTable" class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nama Jabatan</th>
                                    <th>Divisi</th>
                                    <th>Deskripsi</th>
                                    <th>Jumlah Karyawan</th>
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

<!-- Add/Edit Position Modal -->
<div class="modal fade" id="positionModal" tabindex="-1" role="dialog" aria-labelledby="positionModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="positionModalLabel">Tambah Jabatan</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="positionForm">
                @csrf
                <div class="modal-body">
                    <input type="hidden" id="position_id" name="position_id">
                    
                    <div class="form-group">
                        <label for="division_id">Divisi <span class="text-danger">*</span></label>
                        <select class="form-control" id="division_id" name="division_id" required>
                            <option value="">Pilih Divisi</option>
                            @foreach($divisions as $division)
                                <option value="{{ $division->id }}">{{ $division->name }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback" id="division_id-error"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="name">Nama Jabatan <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" required>
                        <div class="invalid-feedback" id="name-error"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Deskripsi</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        <div class="invalid-feedback" id="description-error"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-primary" id="savePosition">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // Initialize select2 for division dropdown
        $('#division_id').select2({
            dropdownParent: $('#positionModal'),
            placeholder: "Pilih Divisi",
            width: '100%'
        });
        
        // Initialize DataTable
        var table = $('#positionTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('hrd.master.position.data') }}",
                error: function(xhr, error, thrown) {
                    console.log('DataTables error:', error, thrown);
                    Swal.fire('Error', 'Terjadi kesalahan saat memuat data. Silakan coba lagi.', 'error');
                }
            },
            columns: [
                {data: 'id', name: 'id'},
                {data: 'name', name: 'name'},
                {data: 'division_name', name: 'division_name', defaultContent: '-'},
                {data: 'description', name: 'description'},
                {data: 'employee_count', name: 'employee_count', defaultContent: '0'},
                {data: 'action', name: 'action', orderable: false, searchable: false}
            ]
        });

        // Open modal for adding new position
        $('#btnAddPosition').on('click', function() {
            $('#positionModalLabel').text('Tambah Jabatan');
            var formEl = $('#positionForm')[0];
            if (formEl) {
                formEl.reset();
            }
            $('#position_id').val('');
            $('#division_id').val('').trigger('change');
            $('.invalid-feedback').text('');
            $('#positionModal').modal('show');
        });

        // Handle form submission
        $('#positionForm').on('submit', function(e) {
            e.preventDefault();
            var id = $('#position_id').val();
            var url = id ? "{{ route('hrd.master.position.update', ':id') }}".replace(':id', id) : "{{ route('hrd.master.position.store') }}";
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
                    $('#savePosition').attr('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Menyimpan...');
                },
                success: function(response) {
                    Swal.fire({
                        title: 'Sukses!',
                        text: response.message,
                        icon: 'success',
                        confirmButtonText: 'OK'
                    });
                    $('#positionModal').modal('hide');
                    table.ajax.reload();
                },
                error: function(xhr) {
                    $('#savePosition').attr('disabled', false).html('Simpan');
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
                    $('#savePosition').attr('disabled', false).html('Simpan');
                }
            });
        });

        // Edit Position
        $(document).on('click', '.edit-position', function() {
            var id = $(this).data('id');
            $('.invalid-feedback').text('');
            $('.is-invalid').removeClass('is-invalid');
            
            $.ajax({
                url: "{{ route('hrd.master.position.show', ':id') }}".replace(':id', id),
                method: 'GET',
                success: function(response) {
                    $('#positionModalLabel').text('Edit Jabatan');
                    $('#position_id').val(response.id);
                    $('#name').val(response.name);
                    $('#division_id').val(response.division_id).trigger('change');
                    $('#description').val(response.description);
                    $('#positionModal').modal('show');
                }
            });
        });

        // Delete Position
        $(document).on('click', '.delete-position', function() {
            var id = $(this).data('id');
            
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: "Data yang dihapus tidak dapat dikembalikan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.value) {
                    $.ajax({
                        url: "{{ route('hrd.master.position.destroy', ':id') }}".replace(':id', id),
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            Swal.fire(
                                'Terhapus!',
                                response.message,
                                'success'
                            );
                            table.ajax.reload();
                        },
                        error: function(xhr) {
                            if (xhr.status === 422) {
                                Swal.fire(
                                    'Gagal!',
                                    xhr.responseJSON.message,
                                    'error'
                                );
                            } else {
                                Swal.fire(
                                    'Error!',
                                    'Terjadi kesalahan pada server',
                                    'error'
                                );
                            }
                        }
                    });
                }
            });
        });
    });
</script>
@endsection
