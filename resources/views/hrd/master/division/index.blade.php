@extends('layouts.hrd.app')
@section('navbar')
    @include('layouts.hrd.navbar')
@endsection

@section('title', 'Master Data Divisi & Jabatan')

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
                            <li class="breadcrumb-item active">Divisi & Jabatan</li>
                        </ol>
                    </div><!--end col-->
                </div><!--end row-->
            </div><!--end page-title-box-->
        </div><!--end col-->
    </div><!--end row-->
    <!-- end page title end breadcrumb -->

    @php
        $activeTab = request('tab', 'divisi');
    @endphp

    <div class="row">
        <div class="col-12">
            <ul class="nav nav-tabs" id="divisionTabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link {{ $activeTab === 'divisi' ? 'active' : '' }}" id="divisi-tab" data-toggle="tab" href="#tab-divisi" role="tab" aria-controls="tab-divisi" aria-selected="{{ $activeTab === 'divisi' ? 'true' : 'false' }}">Data Divisi</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $activeTab === 'jabatan' ? 'active' : '' }}" id="jabatan-tab" data-toggle="tab" href="#tab-jabatan" role="tab" aria-controls="tab-jabatan" aria-selected="{{ $activeTab === 'jabatan' ? 'true' : 'false' }}">Data Jabatan</a>
                </li>
            </ul>
            <div class="tab-content mt-3" id="divisionTabContent">
                <div class="tab-pane fade {{ $activeTab === 'divisi' ? 'show active' : '' }}" id="tab-divisi" role="tabpanel" aria-labelledby="divisi-tab">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Data Divisi</h4>
                            <button type="button" class="btn btn-primary btn-sm" id="btnAddDivision">
                                <i class="fa fa-plus"></i> Tambah Divisi
                            </button>
                        </div><!--end card-header-->
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="divisionTable" class="table table-striped table-bordered">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Nama Divisi</th>
                                            <th>Deskripsi</th>
                                            <th>Jumlah Karyawan</th>
                                            <th>Tgl Dibuat</th>
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
                </div>
                <div class="tab-pane fade {{ $activeTab === 'jabatan' ? 'show active' : '' }}" id="tab-jabatan" role="tabpanel" aria-labelledby="jabatan-tab">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Data Jabatan</h4>
                            <button type="button" class="btn btn-primary btn-sm" id="btnAddPosition">
                                <i class="fa fa-plus"></i> Tambah Jabatan
                            </button>
                        </div><!--end card-header-->
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="positionTable" class="table table-striped table-bordered" style="width: 100%;">
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
                </div>
            </div>
        </div> <!-- end col -->
    </div> <!-- end row -->
</div><!-- container -->

<!-- Add/Edit Division Modal -->
<div class="modal fade" id="divisionModal" tabindex="-1" role="dialog" aria-labelledby="divisionModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="divisionModalLabel">Tambah Divisi</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="divisionForm">
                @csrf
                <div class="modal-body">
                    <input type="hidden" id="division_id" name="division_id">
                    
                    <div class="form-group">
                        <label for="name">Nama Divisi <span class="text-danger">*</span></label>
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
                    <button type="submit" class="btn btn-primary" id="saveDivision">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

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
        // Initialize DataTable for Divisi
        var divisionTable = $('#divisionTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('hrd.master.division.data') }}",
                error: function(xhr, error, thrown) {
                    console.log('DataTables error:', error, thrown);
                    Swal.fire('Error', 'Terjadi kesalahan saat memuat data. Silakan coba lagi.', 'error');
                }
            },
            columns: [
                {data: 'id', name: 'id'},
                {data: 'name', name: 'name'},
                {data: 'description', name: 'description'},
                {data: 'employee_count', name: 'employee_count', defaultContent: '0'},
                {data: 'created_at', name: 'created_at'},
                {data: 'action', name: 'action', orderable: false, searchable: false}
            ]
        });

        // (Optional) select2 for division dropdown was removed here to avoid
        // incompatibility errors on pages where Select2 compat modules are not loaded.

        // Initialize DataTable for Jabatan
        var positionTable = $('#positionTable').DataTable({
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

        // Adjust DataTable columns when switching tabs so layout is correct
        $('a[data-toggle="tab"][href="#tab-jabatan"]').on('shown.bs.tab', function () {
            positionTable.columns.adjust();
        });

        $('a[data-toggle="tab"][href="#tab-divisi"]').on('shown.bs.tab', function () {
            divisionTable.columns.adjust();
        });

        // Open modal for adding new division
        $('#btnAddDivision').on('click', function() {
            $('#divisionModalLabel').text('Tambah Divisi');
            $('#divisionForm')[0].reset();
            $('#division_id').val('');
            $('#divisionForm .invalid-feedback').text('');
            $('#divisionModal').modal('show');
        });

        // Handle form submission (Divisi)
        $('#divisionForm').on('submit', function(e) {
            e.preventDefault();
            var id = $('#division_id').val();
            var url = id ? "{{ route('hrd.master.division.update', ':id') }}".replace(':id', id) : "{{ route('hrd.master.division.store') }}";
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
                    // Clear previous validation errors for division form only
                    $('#divisionForm .invalid-feedback').text('');
                    $('#divisionForm .is-invalid').removeClass('is-invalid');
                    $('#saveDivision').attr('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Menyimpan...');
                },
                success: function(response) {
                    Swal.fire({
                        title: 'Sukses!',
                        text: response.message,
                        icon: 'success',
                        confirmButtonText: 'OK'
                    });
                    $('#divisionModal').modal('hide');
                    divisionTable.ajax.reload();
                },
                error: function(xhr) {
                    $('#saveDivision').attr('disabled', false).html('Simpan');
                    if (xhr.status === 422) {
                        var errors = xhr.responseJSON.errors;
                        $.each(errors, function(key, value) {
                            var field = $('#divisionForm').find('#' + key);
                            field.addClass('is-invalid');
                            $('#divisionForm').find('#' + key + '-error').text(value[0]);
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
                    $('#saveDivision').attr('disabled', false).html('Simpan');
                }
            });
        });

        // Edit Division
        $(document).on('click', '.edit-division', function() {
            var id = $(this).data('id');
            $('#divisionForm .invalid-feedback').text('');
            $('#divisionForm .is-invalid').removeClass('is-invalid');
            
            $.ajax({
                url: "{{ route('hrd.master.division.show', ':id') }}".replace(':id', id),
                method: 'GET',
                success: function(response) {
                    $('#divisionModalLabel').text('Edit Divisi');
                    $('#division_id').val(response.id);
                    $('#divisionForm').find('#name').val(response.name);
                    $('#divisionForm').find('#description').val(response.description);
                    $('#divisionModal').modal('show');
                }
            });
        });

        // Delete Division
        $(document).on('click', '.delete-division', function() {
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
                        url: "{{ route('hrd.master.division.destroy', ':id') }}".replace(':id', id),
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
                            divisionTable.ajax.reload();
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

        // Open modal for adding new position
        $('#btnAddPosition').on('click', function() {
            var $modal = $('#positionModal');
            if ($modal.length === 0) {
                console.error('positionModal element not found in DOM');
                return;
            }

            $('#positionModalLabel').text('Tambah Jabatan');
            var formEl = $('#positionForm')[0];
            if (formEl) {
                formEl.reset();
            }
            $('#position_id').val('');
            $('#division_id').val('').trigger('change');
            $('#positionForm .invalid-feedback').text('');
            $modal.modal('show');
        });

        // Handle position form submission
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
                    // Clear previous validation errors for position form only
                    $('#positionForm .invalid-feedback').text('');
                    $('#positionForm .is-invalid').removeClass('is-invalid');
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
                    positionTable.ajax.reload();
                },
                error: function(xhr) {
                    $('#savePosition').attr('disabled', false).html('Simpan');
                    if (xhr.status === 422) {
                        var errors = xhr.responseJSON.errors;
                        $.each(errors, function(key, value) {
                            var field = $('#positionForm').find('#' + key);
                            field.addClass('is-invalid');
                            $('#positionForm').find('#' + key + '-error').text(value[0]);
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
            $('#positionForm .invalid-feedback').text('');
            $('#positionForm .is-invalid').removeClass('is-invalid');
            
            $.ajax({
                url: "{{ route('hrd.master.position.show', ':id') }}".replace(':id', id),
                method: 'GET',
                success: function(response) {
                    $('#positionModalLabel').text('Edit Jabatan');
                    $('#position_id').val(response.id);
                    $('#positionForm').find('#name').val(response.name);
                    $('#positionForm').find('#division_id').val(response.division_id).trigger('change');
                    $('#positionForm').find('#description').val(response.description);
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
                            positionTable.ajax.reload();
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
