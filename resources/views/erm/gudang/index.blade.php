@extends('layouts.erm.app')
@section('title', 'Finance | Billing')
@section('navbar')
    @include('layouts.erm.navbar')
@endsection
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">Manajemen Gudang</h4>
                    <button type="button" class="btn btn-primary" id="btn-create">
                        <i class="fas fa-plus"></i> Tambah Gudang
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="gudang-table">
                            <thead>
                                <tr>
                                    <th>Nama Gudang</th>
                                    <th>Lokasi</th>
                                    <th>Dibuat</th>
                                    <th width="100px">Aksi</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Create/Edit -->
<div class="modal fade" id="gudangModal" tabindex="-1" role="dialog" aria-labelledby="gudangModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="gudangModalLabel">Tambah Gudang</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="gudangForm">
                <div class="modal-body">
                    <input type="hidden" id="gudang_id" name="gudang_id">
                    <div class="form-group">
                        <label for="nama">Nama Gudang <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nama" name="nama" required maxlength="255">
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="form-group">
                        <label for="lokasi">Lokasi</label>
                        <textarea class="form-control" id="lokasi" name="lokasi" rows="3" maxlength="255"></textarea>
                        <div class="invalid-feedback"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btn-save">
                        <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('vendor/datatables/datatables.min.css') }}">
<style>
.card {
    box-shadow: 0 2px 4px rgba(0,0,0,.1);
    border: none;
}
.table th {
    background-color: #f8f9fa;
    border-top: none;
    font-weight: 600;
}
.badge {
    font-size: 0.75em;
}
.btn-group .btn {
    margin-right: 2px;
}
.btn-group .btn:last-child {
    margin-right: 0;
}
</style>
@endpush

@push('scripts')
<script src="{{ asset('vendor/datatables/datatables.min.js') }}"></script>
<script>
$(document).ready(function() {
    // Initialize DataTable
    const table = $('#gudang-table').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: {
            url: "{{ route('erm.gudang.data') }}",
            type: "GET"
        },
        columns: [
            { data: 'nama', name: 'nama' },
            { data: 'lokasi', name: 'lokasi', defaultContent: '-' },
            { data: 'created_at', name: 'created_at' },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ],
        order: [[0, 'asc']],
        language: {
            processing: "Memuat data...",
            lengthMenu: "Tampilkan _MENU_ data",
            zeroRecords: "Tidak ada data yang ditemukan",
            info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
            infoEmpty: "Menampilkan 0 sampai 0 dari 0 data",
            infoFiltered: "(difilter dari _MAX_ total data)",
            search: "Cari:",
            paginate: {
                first: "Pertama",
                last: "Terakhir",
                next: "Selanjutnya",
                previous: "Sebelumnya"
            }
        }
    });

    // Create button
    $('#btn-create').click(function() {
        resetForm();
        $('#gudangModalLabel').text('Tambah Gudang');
        $('#gudangModal').modal('show');
    });

    // Edit button
    $(document).on('click', '.edit-btn', function() {
        const id = $(this).data('id');
        resetForm();
        $('#gudangModalLabel').text('Edit Gudang');
        
        // Load data
        $.get("{{ route('erm.gudang.index') }}/" + id)
            .done(function(response) {
                if (response.success) {
                    const data = response.data;
                    $('#gudang_id').val(data.id);
                    $('#nama').val(data.nama);
                    $('#lokasi').val(data.lokasi);
                    $('#gudangModal').modal('show');
                } else {
                    showAlert('error', 'Gagal memuat data gudang');
                }
            })
            .fail(function() {
                showAlert('error', 'Terjadi kesalahan saat memuat data');
            });
    });

    // Delete button
    $(document).on('click', '.delete-btn', function() {
        const id = $(this).data('id');
        
        Swal.fire({
            title: 'Konfirmasi Hapus',
            text: 'Apakah Anda yakin ingin menghapus gudang ini?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ route('erm.gudang.index') }}/" + id,
                    type: 'DELETE',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success) {
                            showAlert('success', response.message);
                            table.ajax.reload();
                        } else {
                            showAlert('error', response.message);
                        }
                    },
                    error: function(xhr) {
                        const response = xhr.responseJSON;
                        showAlert('error', response ? response.message : 'Terjadi kesalahan saat menghapus data');
                    }
                });
            }
        });
    });

    // Form submission
    $('#gudangForm').submit(function(e) {
        e.preventDefault();
        
        const id = $('#gudang_id').val();
        const isEdit = id !== '';
        const url = isEdit ? "{{ route('erm.gudang.index') }}/" + id : "{{ route('erm.gudang.store') }}";
        const method = isEdit ? 'PUT' : 'POST';
        
        // Show loading
        const $btnSave = $('#btn-save');
        const $spinner = $btnSave.find('.spinner-border');
        $btnSave.prop('disabled', true);
        $spinner.removeClass('d-none');
        
        // Clear previous errors
        $('.form-control').removeClass('is-invalid');
        $('.invalid-feedback').text('');
        
        const formData = {
            nama: $('#nama').val(),
            lokasi: $('#lokasi').val(),
            _token: $('meta[name="csrf-token"]').attr('content')
        };
        
        if (isEdit) {
            formData._method = 'PUT';
        }
        
        $.ajax({
            url: url,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    $('#gudangModal').modal('hide');
                    showAlert('success', response.message);
                    table.ajax.reload();
                } else {
                    showAlert('error', response.message);
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    // Validation errors
                    const errors = xhr.responseJSON.errors;
                    for (let field in errors) {
                        $(`#${field}`).addClass('is-invalid');
                        $(`#${field}`).siblings('.invalid-feedback').text(errors[field][0]);
                    }
                } else {
                    const response = xhr.responseJSON;
                    showAlert('error', response ? response.message : 'Terjadi kesalahan saat menyimpan data');
                }
            },
            complete: function() {
                $btnSave.prop('disabled', false);
                $spinner.addClass('d-none');
            }
        });
    });

    function resetForm() {
        $('#gudangForm')[0].reset();
        $('#gudang_id').val('');
        $('.form-control').removeClass('is-invalid');
        $('.invalid-feedback').text('');
    }

    function showAlert(type, message) {
        const bgColor = type === 'success' ? 'success' : 'danger';
        const icon = type === 'success' ? 'check-circle' : 'exclamation-triangle';
        
        const alert = `
            <div class="alert alert-${bgColor} alert-dismissible fade show" role="alert">
                <i class="fas fa-${icon} me-2"></i>
                ${message}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        `;
        
        $('.container-fluid').prepend(alert);
        
        // Auto remove after 5 seconds
        setTimeout(function() {
            $('.alert').fadeOut('slow', function() {
                $(this).remove();
            });
        }, 5000);
    }
});
</script>
@endpush
