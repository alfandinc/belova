@extends('layouts.erm.app')
@section('title', 'ERM | Paket Racikan')
@section('navbar')
    @include('layouts.erm.navbar-farmasi')
@endsection
@section('content')
<style>
    #paketTable td { vertical-align: middle; }
    .pr-obat { margin: 0; padding-left: 16px; }
    .pr-row-obat .select2-container { width: 100% !important; }
    .pr-row-obat .input-group-text { min-width: 64px; justify-content: center; }
    #paketModal .modal-body { max-height: 72vh; overflow-y: auto; }
</style>
<div class="container-fluid">
    <div class="d-flex flex-wrap align-items-center justify-content-between mb-3 mt-2">
        <div>
            <h3 class="mb-0">Paket Racikan</h3>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0 bg-transparent mt-1">
                    <li class="breadcrumb-item">ERM</li>
                    <li class="breadcrumb-item">Master Data</li>
                    <li class="breadcrumb-item active">Paket Racikan</li>
                </ol>
            </nav>
        </div>
        <button type="button" class="btn btn-primary mt-2 mt-md-0" id="btnTambahPaket"><i class="fas fa-plus mr-1"></i> Tambah Paket</button>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-center mb-3">
                <select id="filterStatus" class="form-control form-control-sm mr-2 mb-2" style="width:auto">
                    <option value="">Semua Status</option>
                    <option value="aktif">Aktif</option>
                    <option value="nonaktif">Nonaktif</option>
                </select>
                <input type="search" id="searchPaket" class="form-control form-control-sm mb-2" style="max-width:300px" placeholder="Cari nama paket atau obat...">
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-hover w-100" id="paketTable">
                    <thead class="thead-light">
                        <tr>
                            <th style="width:40px">No</th>
                            <th>Nama Paket</th>
                            <th>Obat &amp; Dosis</th>
                            <th>Wadah</th>
                            <th style="width:80px">Bungkus</th>
                            <th class="text-right" style="width:130px" title="Harga jual obat sesuai dosis, belum termasuk wadah">Estimasi Harga</th>
                            <th>Aturan Pakai</th>
                            <th style="width:90px">Status</th>
                            <th style="width:110px">Aksi</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Modal Tambah / Edit --}}
<div class="modal fade" id="paketModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <form class="modal-content" id="paketForm" autocomplete="off">
            <div class="modal-header">
                <h5 class="modal-title" id="paketModalTitle">Tambah Paket Racikan</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="paketId">
                <div class="form-row">
                    <div class="form-group col-md-8">
                        <label>Nama Paket <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="namaPaket" maxlength="255" required>
                    </div>
                    <div class="form-group col-md-4">
                        <label class="d-block">Status</label>
                        <div class="custom-control custom-switch mt-2">
                            <input type="checkbox" class="custom-control-input" id="isActive" checked>
                            <label class="custom-control-label" for="isActive">Aktif</label>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Deskripsi</label>
                    <textarea class="form-control" id="deskripsi" rows="2"></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-5">
                        <label>Wadah</label>
                        <select class="form-control" id="wadahId"></select>
                    </div>
                    <div class="form-group col-md-2">
                        <label>Bungkus <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="bungkusDefault" min="1" value="10" required>
                    </div>
                    <div class="form-group col-md-5">
                        <label>Aturan Pakai Default</label>
                        <input type="text" class="form-control aturan_pakai" id="aturanPakaiDefault" maxlength="255" placeholder="Ketik aturan pakai">
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-2 mb-2">
                    <h6 class="mb-0"><strong>Obat dalam Paket</strong> <small class="text-muted">(dosis per bungkus)</small></h6>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btnTambahObat"><i class="fas fa-plus mr-1"></i> Tambah Obat</button>
                </div>
                <div id="obatRows"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary" id="btnSimpanPaket">Simpan</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
@include('erm.partials.aturan-pakai-builder')
<script>
$(function () {
    const csrf = '{{ csrf_token() }}';
    const urls = {
        index: @json(route('erm.paket-racikan.index')),
        store: @json(route('erm.paket-racikan.master.store')),
        item: @json(route('erm.paket-racikan.master.show', ['id' => '__ID__'])),
        toggle: @json(route('erm.paket-racikan.master.toggle', ['id' => '__ID__'])),
        obat: @json(route('obat.search')),
        wadah: @json(route('wadah.search')),
    };
    const urlFor = (tpl, id) => tpl.replace('__ID__', encodeURIComponent(id));
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const dash = d => d ? esc(d) : '<span class="text-muted">-</span>';
    const rupiah = n => 'Rp ' + Math.round(parseFloat(n || 0)).toLocaleString('id-ID');

    // ---------- Table ----------
    const table = $('#paketTable').DataTable({
        processing: true,
        serverSide: true,
        dom: 'rt<"d-flex flex-wrap justify-content-between align-items-center mt-2"ip>',
        pageLength: 25,
        ajax: { url: urls.index, data: d => { d.status = $('#filterStatus').val(); } },
        order: [[1, 'asc']],
        columns: [
            { data: null, orderable: false, searchable: false, render: (d, t, r, m) => m.row + m.settings._iDisplayStart + 1 },
            { data: 'nama_paket', name: 'nama_paket', render: d => '<strong>' + esc(d) + '</strong>' },
            { data: 'obats', orderable: false, searchable: false, render: list => !list || !list.length ? dash() :
                '<ul class="pr-obat">' + list.map(o => '<li>' + esc(o.nama) + ' &mdash; <strong>' + esc(o.dosis) + '</strong>' +
                    (o.aktif ? '' : ' <span class="badge badge-warning">obat nonaktif</span>') + '</li>').join('') + '</ul>' },
            { data: 'wadah_nama', orderable: false, searchable: false, render: dash },
            { data: 'bungkus_default', name: 'bungkus_default', searchable: false, className: 'text-center' },
            { data: 'harga_per_bungkus', orderable: false, searchable: false, className: 'text-right text-nowrap', render: (d, t, r) =>
                '<strong>' + rupiah(d) + '</strong><div class="small text-muted">/ bungkus</div>' +
                '<div class="small text-muted">' + esc(r.bungkus_default) + ' bks: ' + rupiah(d * r.bungkus_default) + '</div>' },
            { data: 'aturan_pakai_default', name: 'aturan_pakai_default', searchable: false, render: dash },
            { data: 'is_active', name: 'is_active', searchable: false, render: (d, t, r) =>
                '<div class="custom-control custom-switch">' +
                '<input type="checkbox" class="custom-control-input btn-toggle" id="tg' + r.id + '" data-id="' + r.id + '"' + (d ? ' checked' : '') + '>' +
                '<label class="custom-control-label" for="tg' + r.id + '">' + (d ? 'Aktif' : 'Nonaktif') + '</label></div>' },
            { data: null, orderable: false, searchable: false, render: (d, t, r) =>
                '<button class="btn btn-sm btn-outline-primary btn-edit mr-1" data-id="' + r.id + '" title="Edit"><i class="fas fa-pen"></i></button>' +
                '<button class="btn btn-sm btn-outline-secondary btn-duplicate mr-1" data-id="' + r.id + '" title="Duplikat sebagai dasar paket baru (obat/dosis harus diubah)"><i class="fas fa-copy"></i></button>' +
                '<button class="btn btn-sm btn-outline-danger btn-delete" data-id="' + r.id + '" data-nama="' + esc(r.nama_paket) + '" title="Hapus"><i class="fas fa-trash"></i></button>' },
        ],
        language: {
            processing: 'Memuat data...', zeroRecords: 'Tidak ada paket yang cocok', emptyTable: 'Belum ada paket racikan',
            info: 'Menampilkan _START_–_END_ dari _TOTAL_ paket', infoEmpty: '', infoFiltered: '(dari _MAX_)',
            paginate: { previous: '‹', next: '›' }
        }
    });

    $('#filterStatus').on('change', () => table.ajax.reload());
    let searchTimer;
    $('#searchPaket').on('input', function () {
        clearTimeout(searchTimer);
        const v = this.value;
        searchTimer = setTimeout(() => table.search(v).draw(), 300);
    });

    // ---------- Form ----------
    const aturanPakai = window.AturanPakai.mount('#aturanPakaiDefault', { racikan: true });

    $('#wadahId').select2({
        placeholder: 'Pilih Wadah', allowClear: true, width: '100%', dropdownParent: $('#paketModal'),
        ajax: { url: urls.wadah, dataType: 'json', delay: 250, data: p => ({ q: p.term }), processResults: data => ({ results: data }), cache: true }
    });

    // One obat row: obat picker + dosis number in the obat's own satuan (Obat::satuan)
    function addObatRow(obat) {
        const $row = $(
            '<div class="form-row pr-row-obat mb-2">' +
                '<div class="col-md-7"><select class="form-control obat-select"></select></div>' +
                '<div class="col-md-4"><div class="input-group">' +
                    '<input type="number" class="form-control obat-dosis" placeholder="Dosis" step="any" min="0">' +
                    '<div class="input-group-append"><span class="input-group-text obat-satuan">-</span></div>' +
                '</div></div>' +
                '<div class="col-md-1 text-right"><button type="button" class="btn btn-outline-danger btn-remove-obat" title="Hapus"><i class="fas fa-times"></i></button></div>' +
            '</div>'
        ).appendTo('#obatRows');

        const $select = $row.find('.obat-select').select2({
            placeholder: 'Cari obat...', allowClear: true, width: '100%', minimumInputLength: 2, dropdownParent: $('#paketModal'),
            ajax: { url: urls.obat, dataType: 'json', delay: 250, data: p => ({ q: p.term }),
                processResults: data => ({ results: Array.isArray(data.results) ? data.results : data }), cache: true }
        });
        $select.on('select2:select', e => {
            const o = e.params.data, $d = $row.find('.obat-dosis');
            $row.find('.obat-satuan').text(o.satuan || '-');
            if (!$d.val() && o.dosis) $d.val(parseFloat(String(o.dosis).replace(',', '.')) || '');
            $d.trigger('focus').trigger('select');
        });
        $select.on('select2:clear', () => $row.find('.obat-satuan').text('-'));

        if (obat) {
            $select.append(new Option(obat.obat_text, obat.obat_id, true, true)).trigger('change');
            $row.find('.obat-dosis').val(obat.dosis);
            $row.find('.obat-satuan').text(obat.satuan || '-');
        }
        refreshRemoveButtons();
        return $row;
    }

    function refreshRemoveButtons() {
        $('#obatRows .btn-remove-obat').prop('disabled', $('#obatRows .pr-row-obat').length <= 1);
    }

    $('#btnTambahObat').on('click', () => addObatRow().find('.obat-select').select2('open'));
    $('#obatRows').on('click', '.btn-remove-obat', function () {
        $(this).closest('.pr-row-obat').remove();
        refreshRemoveButtons();
    });

    function resetForm() {
        $('#paketForm')[0].reset();
        $('#paketId').val('');
        $('#wadahId').val(null).trigger('change').empty();
        aturanPakai.reset();
        $('#isActive').prop('checked', true);
        $('#obatRows').empty();
    }

    function fillForm(p, asCopy) {
        resetForm();
        $('#paketId').val(asCopy ? '' : p.id);
        $('#namaPaket').val(asCopy ? p.nama_paket + ' (Salinan)' : p.nama_paket);
        $('#deskripsi').val(p.deskripsi || '');
        $('#bungkusDefault').val(p.bungkus_default || 10);
        aturanPakai.setValue(p.aturan_pakai_default || '');
        $('#isActive').prop('checked', asCopy ? true : !!p.is_active);
        if (p.wadah) $('#wadahId').append(new Option(p.wadah.text, p.wadah.id, true, true)).trigger('change');
        (p.details.length ? p.details : [null]).forEach(addObatRow);
    }

    $('#btnTambahPaket').on('click', function () {
        resetForm();
        addObatRow();
        $('#paketModalTitle').text('Tambah Paket Racikan');
        $('#paketModal').modal('show');
    });

    $('#paketTable').on('click', '.btn-edit, .btn-duplicate', function () {
        const asCopy = $(this).hasClass('btn-duplicate');
        $.get(urlFor(urls.item, $(this).data('id')))
            .done(p => {
                fillForm(p, asCopy);
                $('#paketModalTitle').text(asCopy ? 'Duplikat Paket Racikan' : 'Edit Paket Racikan');
                $('#paketModal').modal('show');
            })
            .fail(() => Swal.fire('Gagal', 'Data paket tidak dapat dimuat.', 'error'));
    });

    $('#paketModal').on('shown.bs.modal', () => $('#namaPaket').trigger('focus'));

    $('#paketForm').on('submit', function (e) {
        e.preventDefault();
        const obats = [];
        let incomplete = false;
        $('#obatRows .pr-row-obat').each(function () {
            const obatId = $(this).find('.obat-select').val();
            const dosis = $.trim($(this).find('.obat-dosis').val());
            if (!obatId && !dosis) return;
            if (!obatId || !(parseFloat(dosis) > 0)) incomplete = true;
            obats.push({ obat_id: obatId, dosis: dosis });
        });
        if (incomplete) return Swal.fire('Data belum lengkap', 'Setiap baris obat harus memilih obat dan mengisi dosis lebih dari 0.', 'warning');
        if (!obats.length) return Swal.fire('Data belum lengkap', 'Minimal harus ada satu obat dalam paket.', 'warning');

        const id = $('#paketId').val();
        const $btn = $('#btnSimpanPaket').prop('disabled', true);
        $.ajax({
            url: id ? urlFor(urls.item, id) : urls.store,
            method: id ? 'PUT' : 'POST',
            data: {
                _token: csrf,
                nama_paket: $.trim($('#namaPaket').val()),
                deskripsi: $('#deskripsi').val(),
                wadah_id: $('#wadahId').val() || '',
                bungkus_default: $('#bungkusDefault').val(),
                aturan_pakai_default: $.trim($('#aturanPakaiDefault').val()),
                is_active: $('#isActive').is(':checked') ? 1 : 0,
                obats: obats
            }
        }).done(res => {
            $('#paketModal').modal('hide');
            table.ajax.reload(null, false);
            Swal.fire({ icon: 'success', title: res.message, timer: 1500, showConfirmButton: false });
        }).fail(xhr => {
            const errors = xhr.responseJSON && xhr.responseJSON.errors;
            const msg = errors ? [...new Set(Object.values(errors).flat())].join('<br>') : (xhr.responseJSON?.message || 'Terjadi kesalahan.');
            Swal.fire({ icon: 'error', title: 'Gagal menyimpan', html: msg });
        }).always(() => $btn.prop('disabled', false));
    });

    // ---------- Toggle / delete ----------
    $('#paketTable').on('change', '.btn-toggle', function () {
        const $cb = $(this);
        $.post(urlFor(urls.toggle, $cb.data('id')), { _token: csrf })
            .done(() => table.ajax.reload(null, false))
            .fail(() => {
                $cb.prop('checked', !$cb.is(':checked'));
                Swal.fire('Gagal', 'Status paket tidak dapat diubah.', 'error');
            });
    });

    $('#paketTable').on('click', '.btn-delete', function () {
        const id = $(this).data('id');
        Swal.fire({
            icon: 'warning', title: 'Hapus paket racikan?',
            html: '<strong>' + esc($(this).data('nama')) + '</strong> akan dihapus permanen.<br><small class="text-muted">Resep yang sudah dibuat tidak terpengaruh. Untuk menyembunyikan sementara, nonaktifkan saja.</small>',
            showCancelButton: true, confirmButtonText: 'Hapus', cancelButtonText: 'Batal', confirmButtonColor: '#d33'
        }).then(r => {
            if (!(r.isConfirmed || r.value)) return;
            $.ajax({ url: urlFor(urls.item, id), method: 'DELETE', data: { _token: csrf } })
                .done(res => {
                    table.ajax.reload(null, false);
                    Swal.fire({ icon: 'success', title: res.message, timer: 1500, showConfirmButton: false });
                })
                .fail(xhr => Swal.fire('Gagal', xhr.responseJSON?.message || 'Paket tidak dapat dihapus.', 'error'));
        });
    });
});
</script>
@endsection
