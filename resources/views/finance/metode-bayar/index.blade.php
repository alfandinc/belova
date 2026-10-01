@extends('layouts.finance.app')
@section('title', 'Finance | Master Metode Bayar')
@section('navbar')
    @include('layouts.finance.navbar')
@endsection
@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12 d-flex flex-wrap justify-content-between align-items-center" style="gap:1rem;">
            <div>
                <h3 class="mb-0 font-weight-bold">Master Metode Bayar</h3>
                <div class="text-muted small">Kelola metode bayar dan kelompoknya (Umum / Asuransi). Kelompok ini dipakai saat daftar kunjungan dan untuk tab Umum / Asuransi di Billing.</div>
            </div>
            <div class="d-flex flex-wrap justify-content-end" style="gap:.75rem;">
                <div class="card shadow-sm border-0 mb-0" style="min-width:130px;">
                    <div class="card-body py-2 px-3">
                        <div class="text-muted small">Total</div>
                        <div class="h5 mb-0 font-weight-bold" id="summary-total">{{ $summary['total'] }}</div>
                    </div>
                </div>
                <div class="card shadow-sm border-0 mb-0" style="min-width:130px;">
                    <div class="card-body py-2 px-3">
                        <div class="text-muted small">Umum (aktif)</div>
                        <div class="h5 mb-0 font-weight-bold text-success" id="summary-umum">{{ $summary['umum'] }}</div>
                    </div>
                </div>
                <div class="card shadow-sm border-0 mb-0" style="min-width:130px;">
                    <div class="card-body py-2 px-3">
                        <div class="text-muted small">Asuransi (aktif)</div>
                        <div class="h5 mb-0 font-weight-bold text-primary" id="summary-asuransi">{{ $summary['asuransi'] }}</div>
                    </div>
                </div>
                <div class="card shadow-sm border-0 mb-0" style="min-width:130px;">
                    <div class="card-body py-2 px-3">
                        <div class="text-muted small">Nonaktif</div>
                        <div class="h5 mb-0 font-weight-bold text-muted" id="summary-inactive">{{ $summary['inactive'] }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-end justify-content-between mb-3" style="gap:.75rem;">
                <div class="d-flex flex-wrap" style="gap:.75rem;">
                    <div>
                        <label class="small text-muted mb-1" for="filter-group">Kelompok</label>
                        <select id="filter-group" class="form-control form-control-sm" style="min-width:160px;">
                            <option value="">Semua Kelompok</option>
                            <option value="umum">Umum</option>
                            <option value="asuransi">Asuransi</option>
                        </select>
                    </div>
                    <div>
                        <label class="small text-muted mb-1" for="filter-status">Status</label>
                        <select id="filter-status" class="form-control form-control-sm" style="min-width:160px;">
                            <option value="">Semua Status</option>
                            <option value="active">Aktif</option>
                            <option value="inactive">Nonaktif</option>
                        </select>
                    </div>
                </div>
                <button type="button" class="btn btn-primary btn-sm" id="btn-add-metode-bayar">
                    <i class="fas fa-plus mr-1"></i> Tambah Metode Bayar
                </button>
            </div>

            <div class="table-responsive">
                <table id="table-metode-bayar" class="table table-bordered table-hover mb-0" style="width:100%;">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th style="width:130px;">Kelompok</th>
                            <th style="width:110px;">Status</th>
                            <th style="width:130px;">Dipakai Kunjungan</th>
                            <th style="width:140px;">Aksi</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalMetodeBayar" tabindex="-1" role="dialog" aria-labelledby="modalMetodeBayarTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <form id="form-metode-bayar" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalMetodeBayarTitle">Tambah Metode Bayar</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="metode-bayar-id">
                <div class="form-group">
                    <label for="metode-bayar-nama">Nama</label>
                    <input type="text" class="form-control" id="metode-bayar-nama" name="nama" maxlength="255" placeholder="Contoh: Umum, InHealth, BPJS" required>
                </div>
                <div class="form-group">
                    <label class="d-block">Kelompok</label>
                    <div class="custom-control custom-radio custom-control-inline">
                        <input type="radio" id="metode-bayar-group-umum" name="is_asuransi" value="0" class="custom-control-input" checked>
                        <label class="custom-control-label" for="metode-bayar-group-umum">Umum</label>
                    </div>
                    <div class="custom-control custom-radio custom-control-inline">
                        <input type="radio" id="metode-bayar-group-asuransi" name="is_asuransi" value="1" class="custom-control-input">
                        <label class="custom-control-label" for="metode-bayar-group-asuransi">Asuransi</label>
                    </div>
                </div>
                <div class="form-group mb-0">
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="metode-bayar-active" name="is_active" value="1" checked>
                        <label class="custom-control-label" for="metode-bayar-active">Aktif (muncul di pilihan daftar kunjungan)</label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary" id="btn-save-metode-bayar">Simpan</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(function () {
    const baseUrl = "{{ url('finance/metode-bayar') }}";
    const csrfToken = $('meta[name="csrf-token"]').attr('content');

    function escapeHtml(value) {
        return $('<div>').text(value == null ? '' : value).html();
    }

    const table = $('#table-metode-bayar').DataTable({
        processing: true,
        serverSide: true,
        order: [[1, 'asc'], [0, 'asc']],
        ajax: {
            url: "{{ route('finance.metode-bayar.data') }}",
            data: function (d) {
                d.group = $('#filter-group').val();
                d.status = $('#filter-status').val();
            }
        },
        columns: [
            { data: 'nama', name: 'nama', render: function (data, type) {
                return type === 'display' ? escapeHtml(data) : data;
            } },
            { data: 'is_asuransi', name: 'is_asuransi', searchable: false, render: function (data, type) {
                if (type !== 'display') return data ? 1 : 0;
                return data
                    ? '<span class="badge badge-primary"><i class="fas fa-credit-card mr-1"></i>Asuransi</span>'
                    : '<span class="badge badge-success"><i class="fas fa-money-bill-wave mr-1"></i>Umum</span>';
            } },
            { data: 'is_active', name: 'is_active', searchable: false, render: function (data, type) {
                if (type !== 'display') return data ? 1 : 0;
                return data
                    ? '<span class="badge badge-soft-success">Aktif</span>'
                    : '<span class="badge badge-soft-secondary">Nonaktif</span>';
            } },
            { data: 'visitations_count', name: 'visitations_count', searchable: false, className: 'text-right', render: function (data, type) {
                return type === 'display' ? Number(data || 0).toLocaleString('id-ID') : data;
            } },
            { data: null, orderable: false, searchable: false, render: function (row) {
                return '<button type="button" class="btn btn-sm btn-outline-primary btn-edit-metode-bayar mr-1" data-id="' + row.id + '"><i class="fas fa-edit"></i> Edit</button>'
                    + '<button type="button" class="btn btn-sm btn-outline-danger btn-delete-metode-bayar" data-id="' + row.id + '" data-nama="' + escapeHtml(row.nama) + '"><i class="fas fa-trash"></i></button>';
            } }
        ]
    });

    function refreshSummary() {
        // Summary cards are server-rendered; a light reload keeps them right after edits.
        $.get(window.location.href).done(function (html) {
            const $html = $(html);
            ['total', 'umum', 'asuransi', 'inactive'].forEach(function (key) {
                $('#summary-' + key).text($html.find('#summary-' + key).text());
            });
        });
    }

    $('#filter-group, #filter-status').on('change', function () {
        table.ajax.reload();
    });

    function openForm(data) {
        data = data || {};
        $('#modalMetodeBayarTitle').text(data.id ? 'Edit Metode Bayar' : 'Tambah Metode Bayar');
        $('#metode-bayar-id').val(data.id || '');
        $('#metode-bayar-nama').val(data.nama || '');
        $('#metode-bayar-group-' + (data.is_asuransi ? 'asuransi' : 'umum')).prop('checked', true);
        $('#metode-bayar-active').prop('checked', data.id ? !!data.is_active : true);
        $('#modalMetodeBayar').modal('show');
    }

    $('#btn-add-metode-bayar').on('click', function () {
        openForm();
    });

    $(document).on('click', '.btn-edit-metode-bayar', function () {
        $.get(baseUrl + '/' + $(this).data('id')).done(openForm).fail(function () {
            Swal.fire('Gagal', 'Data metode bayar tidak ditemukan.', 'error');
        });
    });

    $('#form-metode-bayar').on('submit', function (e) {
        e.preventDefault();
        const id = $('#metode-bayar-id').val();
        const $btn = $('#btn-save-metode-bayar').prop('disabled', true);

        $.ajax({
            url: id ? baseUrl + '/' + id : baseUrl,
            method: id ? 'PUT' : 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            data: {
                nama: $('#metode-bayar-nama').val(),
                is_asuransi: $('input[name="is_asuransi"]:checked').val(),
                is_active: $('#metode-bayar-active').is(':checked') ? 1 : 0
            }
        }).done(function (res) {
            $('#modalMetodeBayar').modal('hide');
            table.ajax.reload(null, false);
            refreshSummary();
            Swal.fire({ icon: 'success', title: 'Berhasil', text: res.message, timer: 1500, showConfirmButton: false });
        }).fail(function (xhr) {
            const json = xhr.responseJSON || {};
            const firstError = json.errors ? Object.values(json.errors)[0][0] : null;
            Swal.fire('Gagal', firstError || json.message || 'Gagal menyimpan metode bayar.', 'error');
        }).always(function () {
            $btn.prop('disabled', false);
        });
    });

    $(document).on('click', '.btn-delete-metode-bayar', function () {
        const id = $(this).data('id');
        const nama = $(this).data('nama');

        Swal.fire({
            icon: 'warning',
            title: 'Hapus metode bayar?',
            text: nama,
            showCancelButton: true,
            confirmButtonText: 'Hapus',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#d33'
        }).then(function (result) {
            if (!result.value) return;

            $.ajax({
                url: baseUrl + '/' + id,
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrfToken }
            }).done(function (res) {
                table.ajax.reload(null, false);
                refreshSummary();
                Swal.fire({ icon: 'success', title: 'Dihapus', text: res.message, timer: 1500, showConfirmButton: false });
            }).fail(function (xhr) {
                Swal.fire('Tidak bisa dihapus', (xhr.responseJSON && xhr.responseJSON.message) || 'Gagal menghapus metode bayar.', 'warning');
            });
        });
    });
});
</script>
@endsection
