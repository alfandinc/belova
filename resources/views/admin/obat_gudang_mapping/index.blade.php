@extends('layouts.admin.app')

@section('title', 'Obat & Gudang Mapping')

@section('navbar')
    @include('layouts.admin.navbar')
@endsection

@section('content')
<div class="container-fluid">
    <div class="card shadow-sm">
        <div class="card-body">
            <h3 class="card-title mb-3">Obat & Gudang Mapping</h3>

            <ul class="nav nav-tabs mb-3" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" data-toggle="tab" href="#tab-obat-mapping" role="tab">Obat Mapping</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#tab-gudang-mapping" role="tab">Gudang Mapping</a>
                </li>
            </ul>

            <div class="tab-content">
                <!-- Obat Mapping -->
                <div class="tab-pane fade show active" id="tab-obat-mapping" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <p class="text-muted mb-0">Visitation metode bayar → Obat metode bayar</p>
                        <button type="button" id="addObatMappingBtn" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus mr-1"></i> Tambah Mapping
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table id="obatMappingTable" class="table table-bordered table-striped" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Visitation Metode Bayar</th>
                                    <th>Obat Metode Bayar</th>
                                    <th>Status</th>
                                    <th>Dibuat</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>

                <!-- Gudang Mapping -->
                <div class="tab-pane fade" id="tab-gudang-mapping" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="alert alert-info mb-0 mr-3">
                            <i class="fas fa-info-circle mr-2"></i>
                            Setiap kombinasi tipe transaksi dan scope hanya boleh memiliki satu mapping aktif.
                            Mapping dapat dibuat global, per spesialisasi, atau khusus untuk Event Billing global.
                        </div>
                        <button type="button" id="addGudangMappingBtn" class="btn btn-primary btn-sm text-nowrap">
                            <i class="fas fa-plus mr-1"></i> Tambah Mapping
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table id="gudangMappingTable" class="table table-bordered table-striped" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Tipe Transaksi</th>
                                    <th>Entity</th>
                                    <th>Gudang</th>
                                    <th>Status</th>
                                    <th>Dibuat</th>
                                    <th>Aksi</th>
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

<!-- Obat Mapping Modal -->
<div class="modal fade" id="obatMappingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Obat Mapping</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form id="obatMappingForm">
                <div class="modal-body">
                    <input type="hidden" id="obatMappingId">
                    <div class="form-group">
                        <label for="om_visitation_metode_bayar_id">Visitation Metode Bayar</label>
                        <select id="om_visitation_metode_bayar_id" class="form-control">
                            <option value="">-- Pilih --</option>
                            @foreach($metodeBayars as $mb)
                                <option value="{{ $mb->id }}">{{ $mb->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="om_obat_metode_bayar_id">Obat Metode Bayar</label>
                        <select id="om_obat_metode_bayar_id" class="form-control">
                            <option value="">-- Pilih --</option>
                            @foreach($metodeBayars as $mb)
                                <option value="{{ $mb->id }}">{{ $mb->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group form-check">
                        <input type="checkbox" id="om_is_active" class="form-check-input" checked>
                        <label class="form-check-label" for="om_is_active">Aktif</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Gudang Mapping Modal -->
<div class="modal fade" id="gudangMappingModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Gudang Mapping</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="gudangMappingForm">
                <div class="modal-body">
                    <input type="hidden" id="gudangMappingId">

                    <div class="form-group">
                        <label for="gm_transaction_type">Tipe Transaksi <span class="text-danger">*</span></label>
                        <select class="form-control" id="gm_transaction_type" required>
                            <option value="">Pilih Tipe Transaksi</option>
                            @foreach($transactionTypes as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="gm_entity_type">Entity (opsional)</label>
                        <select class="form-control" id="gm_entity_type">
                            <option value="">-- Default (tidak spesifik) --</option>
                            <option value="spesialisasi">Spesialisasi</option>
                        </select>
                    </div>

                    <div class="form-group" id="gm_entity_wrapper" style="display:none;">
                        <label for="gm_entity_id">Pilih Spesialisasi</label>
                        <select class="form-control" id="gm_entity_id">
                            <option value="">-- Pilih Spesialisasi --</option>
                            @foreach($spesialisasis as $s)
                                <option value="{{ $s->id }}">{{ $s->nama }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="gm_secondary_entity_type">Scope Tambahan (opsional)</label>
                        <select class="form-control" id="gm_secondary_entity_type">
                            <option value="">-- Tidak ada scope tambahan --</option>
                            <option value="billing_context">Konteks Billing</option>
                        </select>
                    </div>

                    <div class="form-group" id="gm_secondary_entity_wrapper" style="display:none;">
                        <label for="gm_secondary_entity_id">Pilih Konteks Billing</label>
                        <select class="form-control" id="gm_secondary_entity_id">
                            <option value="">-- Pilih Konteks Billing --</option>
                            @foreach($billingContexts as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="gm_gudang_id">Gudang <span class="text-danger">*</span></label>
                        <select class="form-control" id="gm_gudang_id" required>
                            <option value="">Pilih Gudang</option>
                            @foreach($gudangs as $gudang)
                                <option value="{{ $gudang->id }}">{{ $gudang->nama }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="gm_is_active" value="1">
                            <label class="custom-control-label" for="gm_is_active">
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
    const csrf = '{{ csrf_token() }}';
    const obatMappingUrl = '{{ url("/erm/obat-mapping") }}';
    const gudangMappingUrl = '{{ url("/erm/gudang-mapping") }}';
    const dtLanguage = {
        processing: 'Sedang memproses...',
        lengthMenu: 'Tampilkan _MENU_ entri',
        zeroRecords: 'Tidak ada data yang ditemukan',
        info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ entri',
        infoEmpty: 'Menampilkan 0 sampai 0 dari 0 entri',
        infoFiltered: '(difilter dari _MAX_ total entri)',
        search: 'Cari:',
        paginate: { first: 'Pertama', previous: 'Sebelumnya', next: 'Selanjutnya', last: 'Terakhir' }
    };

    function errorMessage(xhr, sep) {
        if (xhr.responseJSON && xhr.responseJSON.errors) {
            return Object.values(xhr.responseJSON.errors).flat().join(sep);
        }
        return (xhr.responseJSON && xhr.responseJSON.message) || 'Terjadi kesalahan';
    }

    // Tables in hidden tabs need their column widths recalculated once visible
    $('a[data-toggle="tab"]').on('shown.bs.tab', function() {
        $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
    });

    // ===== Obat Mapping =====
    const obatTable = $('#obatMappingTable').DataTable({
        ajax: { url: '{{ route("erm.obat-mapping.index") }}', dataSrc: 'data' },
        columns: [
            { data: 'visitation_metode_bayar_name' },
            { data: 'obat_metode_bayar_name' },
            { data: 'is_active' },
            { data: 'created_at' },
            { data: 'aksi', orderable: false, searchable: false }
        ],
        language: dtLanguage
    });

    $('#addObatMappingBtn').on('click', function() {
        $('#obatMappingForm')[0].reset();
        $('#obatMappingId').val('');
        $('#obatMappingModal .modal-title').text('Tambah Obat Mapping');
        $('#obatMappingModal').modal('show');
    });

    window.editObatMapping = function(id) {
        $.get(obatMappingUrl + '/' + id, function(data) {
            $('#obatMappingId').val(data.id);
            $('#om_visitation_metode_bayar_id').val(data.visitation_metode_bayar_id);
            $('#om_obat_metode_bayar_id').val(data.obat_metode_bayar_id);
            $('#om_is_active').prop('checked', data.is_active);
            $('#obatMappingModal .modal-title').text('Edit Obat Mapping');
            $('#obatMappingModal').modal('show');
        });
    };

    window.deleteObatMapping = function(id) {
        Swal.fire({
            title: 'Konfirmasi',
            text: 'Hapus mapping ini?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((res) => {
            if (!res.value) return;
            $.ajax({
                url: obatMappingUrl + '/' + id,
                type: 'DELETE',
                data: { _token: csrf },
                success: function() {
                    obatTable.ajax.reload();
                    Swal.fire('Dihapus', 'Mapping telah dihapus', 'success');
                },
                error: function(xhr) {
                    Swal.fire('Error', errorMessage(xhr, '\n'), 'error');
                }
            });
        });
    };

    $('#obatMappingForm').on('submit', function(e) {
        e.preventDefault();
        const id = $('#obatMappingId').val();
        $.ajax({
            url: id ? obatMappingUrl + '/' + id : '{{ route("erm.obat-mapping.store") }}',
            type: id ? 'PUT' : 'POST',
            data: {
                visitation_metode_bayar_id: $('#om_visitation_metode_bayar_id').val(),
                obat_metode_bayar_id: $('#om_obat_metode_bayar_id').val(),
                is_active: $('#om_is_active').is(':checked') ? 1 : 0,
                _token: csrf
            },
            success: function() {
                $('#obatMappingModal').modal('hide');
                obatTable.ajax.reload();
                Swal.fire('Sukses', 'Mapping tersimpan', 'success');
            },
            error: function(xhr) {
                Swal.fire('Error', errorMessage(xhr, '\n'), 'error');
            }
        });
    });

    // ===== Gudang Mapping =====
    const gudangTable = $('#gudangMappingTable').DataTable({
        processing: true,
        ajax: { url: '{{ route("erm.gudang-mapping.index") }}', type: 'GET' },
        columns: [
            { data: 'transaction_type_label', name: 'transaction_type_label' },
            { data: 'entity', name: 'entity' },
            { data: 'gudang_nama', name: 'gudang_nama' },
            { data: 'status', name: 'status', orderable: false, searchable: false },
            {
                data: 'created_at',
                name: 'created_at',
                render: function(data) {
                    return data ? new Date(data).toLocaleDateString('id-ID') : '-';
                }
            },
            { data: 'aksi', name: 'aksi', orderable: false, searchable: false }
        ],
        language: dtLanguage
    });

    function toggleGudangScopeFields() {
        $('#gm_entity_wrapper').toggle(!!$('#gm_entity_type').val());
        $('#gm_secondary_entity_wrapper').toggle(!!$('#gm_secondary_entity_type').val());
    }

    $('#gm_entity_type').on('change', function() {
        if (!$(this).val()) $('#gm_entity_id').val('');
        toggleGudangScopeFields();
    });

    $('#gm_secondary_entity_type').on('change', function() {
        if (!$(this).val()) $('#gm_secondary_entity_id').val('');
        toggleGudangScopeFields();
    });

    $('#addGudangMappingBtn').on('click', function() {
        $('#gudangMappingForm')[0].reset();
        $('#gudangMappingId').val('');
        toggleGudangScopeFields();
        $('#gudangMappingModal .modal-title').text('Tambah Gudang Mapping');
        $('#gudangMappingModal').modal('show');
    });

    window.editGudangMapping = function(id) {
        $.get(gudangMappingUrl + '/' + id, function(data) {
            $('#gudangMappingId').val(data.id);
            $('#gm_transaction_type').val(data.transaction_type);
            $('#gm_gudang_id').val(data.gudang_id);
            $('#gm_is_active').prop('checked', data.is_active);
            $('#gm_entity_type').val(data.entity_type || '');
            $('#gm_entity_id').val(data.entity_type === 'spesialisasi' ? data.entity_id : '');
            $('#gm_secondary_entity_type').val(data.secondary_entity_type || '');
            $('#gm_secondary_entity_id').val(data.secondary_entity_type ? data.secondary_entity_id : '');
            toggleGudangScopeFields();
            $('#gudangMappingModal .modal-title').text('Edit Gudang Mapping');
            $('#gudangMappingModal').modal('show');
        });
    };

    window.deleteGudangMapping = function(id) {
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
            if (!result.value) return;
            $.ajax({
                url: gudangMappingUrl + '/' + id,
                type: 'DELETE',
                data: { _token: csrf },
                success: function(response) {
                    if (response.success) {
                        Swal.fire('Berhasil!', response.message, 'success');
                        gudangTable.ajax.reload();
                    }
                },
                error: function(xhr) {
                    Swal.fire('Error!', errorMessage(xhr, '<br>'), 'error');
                }
            });
        });
    };

    $('#gudangMappingForm').on('submit', function(e) {
        e.preventDefault();
        const id = $('#gudangMappingId').val();
        $.ajax({
            url: id ? gudangMappingUrl + '/' + id : '{{ route("erm.gudang-mapping.store") }}',
            type: id ? 'PUT' : 'POST',
            data: {
                transaction_type: $('#gm_transaction_type').val(),
                gudang_id: $('#gm_gudang_id').val(),
                entity_type: $('#gm_entity_type').val() || null,
                entity_id: $('#gm_entity_id').val() || null,
                secondary_entity_type: $('#gm_secondary_entity_type').val() || null,
                secondary_entity_id: $('#gm_secondary_entity_id').val() || null,
                is_active: $('#gm_is_active').is(':checked'),
                _token: csrf
            },
            success: function(response) {
                if (response.success) {
                    $('#gudangMappingModal').modal('hide');
                    Swal.fire('Berhasil!', response.message, 'success');
                    gudangTable.ajax.reload();
                }
            },
            error: function(xhr) {
                Swal.fire('Error!', errorMessage(xhr, '<br>'), 'error');
            }
        });
    });
});
</script>
@endsection
