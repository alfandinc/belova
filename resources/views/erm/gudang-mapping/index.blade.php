@extends('layouts.erm.app')
@section('title', 'ERM | Gudang Mapping Management')
@section('navbar')
    @include('layouts.erm.navbar-farmasi')
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-warehouse mr-2"></i>
                        Management Mapping Gudang
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-primary btn-sm" id="addMappingBtn">
                            <i class="fas fa-plus mr-1"></i> Tambah Mapping
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>
                        <strong>Informasi:</strong> Setiap tipe transaksi (Resep/Tindakan) hanya boleh memiliki satu mapping aktif. 
                        Mapping aktif akan digunakan sebagai default gudang untuk pengurangan stok.
                    </div>
                    
                    <div class="table-responsive">
                        <table id="gudangMappingTable" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Tipe Transaksi</th>
                                    <th>Gudang</th>
                                    <th>Status</th>
                                    <th>Dibuat</th>
                                    <th>Aksi</th>
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

<!-- Add/Edit Modal -->
<div class="modal fade" id="mappingModal" tabindex="-1" role="dialog" aria-labelledby="mappingModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="mappingModalLabel">Tambah Mapping Gudang</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="mappingForm">
                <div class="modal-body">
                    <input type="hidden" id="mappingId" name="mapping_id">
                    
                    <div class="form-group">
                        <label for="transactionType">Tipe Transaksi <span class="text-danger">*</span></label>
                        <select class="form-control" id="transactionType" name="transaction_type" required>
                            <option value="">Pilih Tipe Transaksi</option>
                            @foreach($transactionTypes as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="gudangId">Gudang <span class="text-danger">*</span></label>
                        <select class="form-control" id="gudangId" name="gudang_id" required>
                            <option value="">Pilih Gudang</option>
                            @foreach($gudangs as $gudang)
                                <option value="{{ $gudang->id }}">{{ $gudang->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="isActive" name="is_active" value="1">
                            <label class="custom-control-label" for="isActive">
                                Aktifkan mapping ini
                                <small class="form-text text-muted">
                                    Jika dicentang, mapping lain untuk tipe transaksi yang sama akan dinonaktifkan.
                                </small>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i> Simpan
                    </button>
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
    const table = $('#gudangMappingTable').DataTable({
        processing: true,
        serverSide: false, // We'll load all data since it's small
        ajax: {
            url: "{{ route('erm.gudang-mapping.index') }}",
            type: "GET"
        },
        columns: [
            { 
                data: 'transaction_type_label',
                name: 'transaction_type_label'
            },
            { 
                data: 'gudang_nama',
                name: 'gudang_nama'
            },
            { 
                data: 'status',
                name: 'status',
                orderable: false,
                searchable: false
            },
            { 
                data: 'created_at',
                name: 'created_at',
                render: function(data) {
                    return data ? new Date(data).toLocaleDateString('id-ID') : '-';
                }
            },
            { 
                data: 'aksi',
                name: 'aksi',
                orderable: false,
                searchable: false
            }
        ],
        language: {
            processing: "Sedang memproses...",
            lengthMenu: "Tampilkan _MENU_ entri",
            zeroRecords: "Tidak ada data yang ditemukan",
            info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ entri",
            infoEmpty: "Menampilkan 0 sampai 0 dari 0 entri",
            infoFiltered: "(difilter dari _MAX_ total entri)",
            search: "Cari:",
            paginate: {
                first: "Pertama",
                previous: "Sebelumnya",
                next: "Selanjutnya",
                last: "Terakhir"
            }
        }
    });

    // Add mapping button
    $('#addMappingBtn').on('click', function() {
        $('#mappingModal').modal('show');
        $('#mappingModalLabel').text('Tambah Mapping Gudang');
        $('#mappingForm')[0].reset();
        $('#mappingId').val('');
    });

    // Edit mapping function
    window.editMapping = function(id) {
        $.get("{{ url('/erm/gudang-mapping') }}/" + id, function(data) {
            $('#mappingModal').modal('show');
            $('#mappingModalLabel').text('Edit Mapping Gudang');
            $('#mappingId').val(data.id);
            $('#transactionType').val(data.transaction_type);
            $('#gudangId').val(data.gudang_id);
            $('#isActive').prop('checked', data.is_active);
        });
    };

    // Delete mapping function
    window.deleteMapping = function(id) {
        Swal.fire({
            title: 'Konfirmasi Hapus',
            text: 'Apakah Anda yakin ingin menghapus mapping ini?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.value) {
                $.ajax({
                    url: "{{ url('/erm/gudang-mapping') }}/" + id,
                    type: 'DELETE',
                    data: {
                        _token: "{{ csrf_token() }}"
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('Berhasil!', response.message, 'success');
                            table.ajax.reload();
                        }
                    },
                    error: function(xhr) {
                        Swal.fire('Error!', xhr.responseJSON?.message || 'Terjadi kesalahan', 'error');
                    }
                });
            }
        });
    };

    // Form submit handler
    $('#mappingForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = {
            transaction_type: $('#transactionType').val(),
            gudang_id: $('#gudangId').val(),
            is_active: $('#isActive').is(':checked'),
            _token: "{{ csrf_token() }}"
        };

        const mappingId = $('#mappingId').val();
        const url = mappingId ? 
            "{{ url('/erm/gudang-mapping') }}/" + mappingId : 
            "{{ route('erm.gudang-mapping.store') }}";
        const method = mappingId ? 'PUT' : 'POST';

        $.ajax({
            url: url,
            type: method,
            data: formData,
            success: function(response) {
                if (response.success) {
                    $('#mappingModal').modal('hide');
                    Swal.fire('Berhasil!', response.message, 'success');
                    table.ajax.reload();
                }
            },
            error: function(xhr) {
                let message = 'Terjadi kesalahan';
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    const errors = Object.values(xhr.responseJSON.errors).flat();
                    message = errors.join('<br>');
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                Swal.fire('Error!', message, 'error');
            }
        });
    });
});
</script>
@endsection
