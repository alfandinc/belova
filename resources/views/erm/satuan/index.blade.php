@extends('layouts.erm.app')
@section('title', 'ERM | Master Satuan')
@section('navbar')
    @include('layouts.erm.navbar-farmasi')
@endsection
@section('content')
<style>
    #satuanTable td { vertical-align: middle; }
    .satuan-flag { font-size: 11px; }
</style>
<div class="container-fluid">
    <div class="d-flex flex-wrap align-items-center justify-content-between mb-3 mt-2">
        <div>
            <h3 class="mb-0">Master Satuan</h3>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0 bg-transparent mt-1">
                    <li class="breadcrumb-item">Admin Panel</li>
                    <li class="breadcrumb-item">Master Data</li>
                    <li class="breadcrumb-item active">Satuan</li>
                </ol>
            </nav>
        </div>
        <button type="button" class="btn btn-primary mt-2 mt-md-0" id="btnTambahSatuan"><i class="fas fa-plus mr-1"></i> Tambah Satuan</button>
    </div>

    <div class="alert alert-light border small mb-3">
        Pilihan satuan untuk <strong>Satuan Stok</strong> dan <strong>Satuan Dosis</strong> obat serta <strong>Sediaan</strong> produk R&amp;D.
        Data menyimpan nama satuan sebagai teks: mengganti nama di sini ikut mengganti semua data yang memakainya.
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table table-bordered table-sm w-100" id="satuanTable">
                <thead>
                    <tr>
                        <th style="width:60px">#</th>
                        <th>Nama Satuan</th>
                        <th style="width:120px">Satuan Stok</th>
                        <th style="width:120px">Satuan Dosis</th>
                        <th style="width:120px">Dipakai</th>
                        <th style="width:170px">Aksi</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="satuanModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" id="satuanForm">
            <div class="modal-header">
                <h5 class="modal-title" id="satuanModalTitle">Tambah Satuan</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="satuan_id">
                <div class="form-group">
                    <label for="satuan_nama">Nama Satuan</label>
                    <input type="text" class="form-control" id="satuan_nama" maxlength="50" required placeholder="mis. Tablet, mL, Sachet">
                    <small class="form-text text-muted d-none" id="satuanRenameNote"></small>
                </div>
                <div class="custom-control custom-checkbox mb-1">
                    <input type="checkbox" class="custom-control-input" id="satuan_untuk_stok">
                    <label class="custom-control-label" for="satuan_untuk_stok">Satuan Stok <small class="text-muted">(unit stok, HPP &amp; harga jual dihitung)</small></label>
                </div>
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="satuan_untuk_dosis">
                    <label class="custom-control-label" for="satuan_untuk_dosis">Satuan Dosis <small class="text-muted">(ukuran kekuatan, mis. mg, mL)</small></label>
                </div>
                <small class="text-muted d-block mt-2">Semua satuan juga tersedia sebagai pilihan Sediaan produk R&amp;D.</small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary" id="btnSimpanSatuan">Simpan</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(function () {
    const urls = {
        list: @json(route('erm.satuan.index')),
        store: @json(route('erm.satuan.store')),
        item: @json(route('erm.satuan.update', ['satuan' => '__ID__'])),
    };
    let rows = {};

    function esc(value) {
        return $('<div>').text(value == null ? '' : String(value)).html();
    }
    function flag(on) {
        return on ? '<span class="badge badge-success satuan-flag">Ya</span>' : '<span class="text-muted">-</span>';
    }

    const table = $('#satuanTable').DataTable({
        ajax: { url: urls.list, dataSrc: function (json) {
            rows = {};
            json.data.forEach(function (row) { rows[row.id] = row; });
            return json.data;
        } },
        order: [],
        pageLength: 50,
        columns: [
            { data: null, orderable: false, render: function (d, t, r, meta) { return meta.row + 1; } },
            { data: 'nama', render: function (d) { return '<strong>' + esc(d) + '</strong>'; } },
            { data: 'untuk_stok', render: flag },
            { data: 'untuk_dosis', render: flag },
            { data: 'dipakai', render: function (d) { return d > 0 ? d + ' data' : '<span class="text-muted">0</span>'; } },
            { data: null, orderable: false, render: function (d, t, row) {
                return '<button type="button" class="btn btn-sm btn-outline-primary js-edit mr-1" data-id="' + row.id + '"><i class="fas fa-edit mr-1"></i>Edit</button>'
                    + '<button type="button" class="btn btn-sm btn-outline-danger js-delete" data-id="' + row.id + '"' + (row.dipakai > 0 ? ' disabled title="Masih dipakai"' : '') + '><i class="fas fa-trash mr-1"></i>Hapus</button>';
            } },
        ],
    });

    function openModal(row) {
        $('#satuan_id').val(row ? row.id : '');
        $('#satuan_nama').val(row ? row.nama : '');
        $('#satuan_untuk_stok').prop('checked', row ? !!row.untuk_stok : true);
        $('#satuan_untuk_dosis').prop('checked', row ? !!row.untuk_dosis : false);
        $('#satuanModalTitle').text(row ? 'Edit Satuan' : 'Tambah Satuan');
        const note = $('#satuanRenameNote');
        if (row && row.dipakai > 0) {
            note.text('Dipakai di ' + row.dipakai + ' data. Mengganti nama akan ikut mengganti nama satuan pada data tersebut.').removeClass('d-none');
        } else {
            note.addClass('d-none');
        }
        $('#satuanModal').modal('show');
        setTimeout(function () { $('#satuan_nama').trigger('focus'); }, 300);
    }

    function showError(xhr) {
        const res = xhr.responseJSON || {};
        const first = res.errors ? Object.values(res.errors)[0][0] : null;
        Swal.fire('Gagal', first || res.message || 'Terjadi kesalahan.', 'error');
    }

    $('#btnTambahSatuan').on('click', function () { openModal(null); });
    $('#satuanTable').on('click', '.js-edit', function () { openModal(rows[$(this).data('id')]); });

    $('#satuanForm').on('submit', function (e) {
        e.preventDefault();
        const id = $('#satuan_id').val();
        const btn = $('#btnSimpanSatuan').prop('disabled', true);
        $.ajax({
            url: id ? urls.item.replace('__ID__', id) : urls.store,
            method: id ? 'PUT' : 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                nama: $('#satuan_nama').val(),
                untuk_stok: $('#satuan_untuk_stok').is(':checked') ? 1 : 0,
                untuk_dosis: $('#satuan_untuk_dosis').is(':checked') ? 1 : 0,
            },
        }).done(function (res) {
            $('#satuanModal').modal('hide');
            table.ajax.reload(null, false);
            Swal.fire({ icon: 'success', title: res.message, timer: 1800, showConfirmButton: false });
        }).fail(showError).always(function () { btn.prop('disabled', false); });
    });

    $('#satuanTable').on('click', '.js-delete', function () {
        const row = rows[$(this).data('id')];
        Swal.fire({
            title: 'Hapus satuan "' + row.nama + '"?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, hapus',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#d33',
        }).then(function (result) {
            if (!result.isConfirmed && !result.value) return;
            $.ajax({
                url: urls.item.replace('__ID__', row.id),
                method: 'DELETE',
                data: { _token: $('meta[name="csrf-token"]').attr('content') },
            }).done(function (res) {
                table.ajax.reload(null, false);
                Swal.fire({ icon: 'success', title: res.message, timer: 1800, showConfirmButton: false });
            }).fail(showError);
        });
    });
});
</script>
@endsection
