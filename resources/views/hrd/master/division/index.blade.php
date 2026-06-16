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
        <div class="col-md-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">Data Divisi</h4>
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

        <div class="col-md-8">
                <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">Data Posisi</h4>
                    <button type="button" class="btn btn-primary btn-sm" id="btnAddPosition">
                        <i class="fa fa-plus"></i> Tambah Posisi
                    </button>
                </div><!--end card-header-->
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="positionTable" class="table table-striped table-bordered" style="width: 100%;">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nama Posisi</th>
                                    <th>Divisi</th>
                                    <th>Posisi Induk</th>
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
                	<h5 class="modal-title" id="positionModalLabel">Tambah Posisi</h5>
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
                        <label for="name">Nama Posisi <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" required>
                        <div class="invalid-feedback" id="name-error"></div>
                    </div>

                    <div class="form-group">
                        <label for="parent_id">Posisi Induk</label>
                        <select class="form-control" id="parent_id" name="parent_id">
                            <option value="">-- Tidak Ada --</option>
                            @foreach($positions as $pos)
                                <option value="{{ $pos->id }}">{{ $pos->name }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback" id="parent_id-error"></div>
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
<style>
/* Icon-only action buttons styling */
.table .btn-icon-only { padding: .25rem .4rem; }
.table .btn-icon-only i { margin: 0; }
.table small.text-muted { display: block; margin-top: 2px; }
</style>
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
                {data: 'name', name: 'name', render: function(data, type, row) {
                    var desc = row.description ? '<br><small class="text-muted">' + row.description + '</small>' : '';
                    return data + desc;
                }},
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
                {data: 'name', name: 'name', render: function(data, type, row) {
                    var desc = row.description ? '<br><small class="text-muted">' + row.description + '</small>' : '';
                    return data + desc;
                }},
                {data: 'division_name', name: 'division_name', defaultContent: '-'},
                {data: 'parent_name', name: 'parent_name', defaultContent: '-'},
                {data: 'action', name: 'action', orderable: false, searchable: false}
            ]
        });

        // Convert action buttons to icon-only by keeping their first <i> element
        function makeActionButtonsIconOnly(tableSelector) {
            $(tableSelector + ' tbody').find('tr').each(function() {
                $(this).find('td').last().find('.btn').each(function() {
                    var $btn = $(this);
                    var $icon = $btn.find('i').first();
                    if ($icon.length) {
                        $btn.addClass('btn-icon-only');
                        $btn.html($icon);
                    } else {
                        $btn.html('<i class="fa fa-ellipsis-h"></i>');
                        $btn.addClass('btn-icon-only');
                    }
                });
            });
        }

        // Adjust DataTable columns after initialization and on resize, and fix buttons
        function adjustAndFix() {
            divisionTable.columns.adjust();
            positionTable.columns.adjust();
            makeActionButtonsIconOnly('#divisionTable');
            makeActionButtonsIconOnly('#positionTable');
        }

        setTimeout(adjustAndFix, 50);

        $(window).on('resize', function() {
            adjustAndFix();
        });

        // Initialize Select2 for parent position select inside the modal
        if (typeof $.fn.select2 === 'function') {
            $('#parent_id').select2({
                dropdownParent: $('#positionModal'),
                width: '100%',
                placeholder: '-- Tidak Ada --',
                allowClear: true
            });
        }

        // Ensure buttons are icon-only after each draw
        divisionTable.on('draw', function() { makeActionButtonsIconOnly('#divisionTable'); });
        positionTable.on('draw', function() { makeActionButtonsIconOnly('#positionTable'); });

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

                    $('#positionModalLabel').text('Tambah Posisi');
            var formEl = $('#positionForm')[0];
            if (formEl) {
                formEl.reset();
            }
            $('#position_id').val('');
            $('#division_id').val('').trigger('change');
            $('#parent_id').val('').trigger('change');
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
                    $('#positionModalLabel').text('Edit Posisi');
                    $('#position_id').val(response.id);
                    $('#positionForm').find('#name').val(response.name);
                    $('#positionForm').find('#division_id').val(response.division_id).trigger('change');
                    $('#positionForm').find('#description').val(response.description);
                    $('#positionForm').find('#parent_id').val(response.parent_id).trigger('change');
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
