@extends('layouts.erm.app')
@section('title', 'ERM | Master Radiologi')
@section('navbar')
    @include('layouts.erm.navbar-lab')
@endsection

@section('content')
<style>
    #mrTestTable td, #mrKategoriTable td { vertical-align: middle; }
    #mrTestTable .mr-nama { font-weight: 600; }
</style>
<div class="container-fluid">
    <div class="mt-3 mb-3">
        <h4 class="page-title mb-0">Master Radiologi</h4>
        <small class="text-muted">Pemeriksaan radiologi dan kategorinya. <strong>Harga normal</strong> dipakai bila dokter meminta lewat E-Radiologi; di paket biaya konsultasi (mis. + USG) berlaku harga paketnya.</small>
    </div>

    <div class="row">
        <div class="col-xl-9">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-wrap align-items-center mb-2" style="gap:8px">
                        <h6 class="mb-0 mr-auto">Pemeriksaan</h6>
                        <select id="mrFilterKategori" class="custom-select custom-select-sm" style="width:auto">
                            <option value="">Semua kategori</option>
                        </select>
                        <input type="search" id="mrSearch" class="form-control form-control-sm" style="max-width:240px" placeholder="Cari pemeriksaan...">
                        <button type="button" class="btn btn-primary btn-sm" id="btnAddTest"><i class="fas fa-plus"></i> Tambah Pemeriksaan</button>
                    </div>
                    <table id="mrTestTable" class="table table-sm table-hover w-100">
                        <thead class="thead-light">
                            <tr>
                                <th>Pemeriksaan</th>
                                <th>Kategori</th>
                                <th class="text-right">Harga Normal</th>
                                <th>Paket Biaya Konsultasi</th>
                                <th class="text-right">Dipakai</th>
                                <th class="text-right" style="width:1%">Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2">
                        <h6 class="mb-0 mr-auto">Kategori</h6>
                        <button type="button" class="btn btn-outline-primary btn-sm" id="btnAddKategori"><i class="fas fa-plus"></i> Kategori</button>
                    </div>
                    <table id="mrKategoriTable" class="table table-sm mb-0">
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="testModal" tabindex="-1" role="dialog" aria-labelledby="testModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="testForm">
                <input type="hidden" id="testId">
                <div class="modal-header">
                    <h5 class="modal-title" id="testModalLabel">Tambah Pemeriksaan</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="testNama">Nama pemeriksaan</label>
                        <input type="text" class="form-control" id="testNama" maxlength="255" required placeholder="Contoh: USG Cepat">
                    </div>
                    <div class="form-group">
                        <label for="testKategori">Kategori</label>
                        <select class="custom-select" id="testKategori" required></select>
                    </div>
                    <div class="form-group">
                        <label for="testHarga">Harga normal</label>
                        <div class="input-group">
                            <div class="input-group-prepend"><span class="input-group-text">Rp</span></div>
                            <input type="number" min="0" step="1" class="form-control" id="testHarga" required>
                        </div>
                        <small class="form-text text-muted">Harga bila diminta terpisah di E-Radiologi. Mengubahnya tidak mengubah permintaan / invoice yang sudah ada.</small>
                    </div>
                    <div class="form-group mb-0">
                        <label for="testDeskripsi">Deskripsi <small class="text-muted">(opsional)</small></label>
                        <textarea class="form-control" id="testDeskripsi" rows="2" maxlength="2000"></textarea>
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
@endsection

@section('scripts')
<script>
$(function () {
    var urls = {
        data: '{{ route('erm.radiologi-master.data') }}',
        test: '{{ url('/erm/master-radiologi/test') }}',
        kategori: '{{ url('/erm/master-radiologi/kategori') }}'
    };
    var kategoris = [];

    function esc(v) { return $('<div>').text(v == null ? '' : String(v)).html(); }
    function rupiah(n) { return 'Rp' + Number(n || 0).toLocaleString('id-ID', { maximumFractionDigits: 2 }); }
    function fail(xhr) { Swal.fire({ icon: 'error', title: 'Gagal', html: belovaAjaxError(xhr) }); }
    function ok(res) { Swal.fire({ icon: 'success', title: 'Berhasil', text: res.message, timer: 1500, showConfirmButton: false }); load(); }
    function send(method, url, data) {
        return $.ajax({ url: url, type: 'POST', data: $.extend({ _token: '{{ csrf_token() }}', _method: method }, data || {}) });
    }

    var table = $('#mrTestTable').DataTable({
        dom: 'rt<"d-flex flex-wrap justify-content-between align-items-center mt-2"lip>',
        data: [], order: [[1, 'asc'], [0, 'asc']], pageLength: 50,
        language: {
            zeroRecords: 'Tidak ada pemeriksaan', emptyTable: 'Belum ada pemeriksaan',
            info: '_START_–_END_ dari _TOTAL_', infoEmpty: '0 data', infoFiltered: '(dari _MAX_)',
            lengthMenu: 'Tampil _MENU_', paginate: { previous: '‹', next: '›' }
        },
        columns: [
            { data: 'nama', render: function (d, t, r) { return t === 'display' ? '<span class="mr-nama">' + esc(d) + '</span>' + (r.deskripsi ? '<div class="small text-muted">' + esc(r.deskripsi) + '</div>' : '') : d; } },
            { data: 'kategori', render: function (d) { return esc(d || '-'); } },
            { data: 'harga', className: 'text-right', render: function (d, t) { return t === 'display' ? rupiah(d) : d; } },
            {
                data: 'paket', orderable: false, render: function (d, t) {
                    if (t !== 'display') return (d || []).map(function (p) { return p.nama; }).join(' ');
                    return (d || []).length
                        ? d.map(function (p) { return '<div class="small">' + esc(p.nama) + ' <span class="text-muted">(' + rupiah(p.harga) + ')</span></div>'; }).join('')
                        : '<span class="text-muted">-</span>';
                }
            },
            { data: 'dipakai', className: 'text-right', render: function (d, t) { return t === 'display' ? (d ? d + '×' : '<span class="text-muted">0</span>') : d; } },
            {
                data: 'id', orderable: false, className: 'text-right text-nowrap', render: function (d, t, r) {
                    var used = r.dipakai > 0 || (r.paket || []).length > 0;
                    return '<button type="button" class="btn btn-sm btn-warning btn-edit-test" data-id="' + d + '">Edit</button> '
                        + (used
                            ? '<span class="d-inline-block" tabindex="0" title="Sudah dipakai di permintaan / paket: tidak bisa dihapus"><button type="button" class="btn btn-sm btn-danger" disabled style="pointer-events:none">Hapus</button></span>'
                            : '<button type="button" class="btn btn-sm btn-danger btn-delete-test" data-id="' + d + '">Hapus</button>');
                }
            }
        ]
    });
    var tests = {};

    function renderKategori() {
        var html = kategoris.map(function (k) {
            return '<tr><td>' + esc(k.nama) + ' <span class="badge badge-light">' + k.jumlah + '</span></td>'
                + '<td class="text-right text-nowrap">'
                + '<button type="button" class="btn btn-sm btn-link p-0 mr-2 btn-edit-kategori" data-id="' + k.id + '">Ubah</button>'
                + (k.jumlah ? '' : '<button type="button" class="btn btn-sm btn-link text-danger p-0 btn-delete-kategori" data-id="' + k.id + '">Hapus</button>')
                + '</td></tr>';
        }).join('');
        $('#mrKategoriTable tbody').html(html || '<tr><td class="text-muted">Belum ada kategori</td></tr>');

        var options = kategoris.map(function (k) { return '<option value="' + k.id + '">' + esc(k.nama) + '</option>'; }).join('');
        var filter = $('#mrFilterKategori').val();
        $('#mrFilterKategori').html('<option value="">Semua kategori</option>' + options).val(filter);
        $('#testKategori').html('<option value="">Pilih kategori...</option>' + options);
    }

    function load() {
        $.get(urls.data).done(function (res) {
            kategoris = res.kategoris;
            tests = {};
            res.tests.forEach(function (t) { tests[t.id] = t; });
            renderKategori();
            table.clear().rows.add(res.tests).draw(false);
        }).fail(fail);
    }

    $('#mrSearch').on('input', function () { table.search(this.value).draw(); });
    $('#mrFilterKategori').on('change', function () {
        var nama = $(this).find('option:selected').text();
        table.column(1).search(this.value ? '^' + $.fn.dataTable.util.escapeRegex(nama) + '$' : '', true, false).draw();
    });

    // ---- pemeriksaan
    function openTest(t) {
        $('#testId').val(t ? t.id : '');
        $('#testNama').val(t ? t.nama : '');
        $('#testKategori').val(t ? t.kategori_id : ($('#mrFilterKategori').val() || ''));
        $('#testHarga').val(t ? t.harga : '');
        $('#testDeskripsi').val(t ? t.deskripsi : '');
        $('#testModalLabel').text(t ? 'Edit Pemeriksaan' : 'Tambah Pemeriksaan');
        $('#testModal').modal('show');
    }
    $('#btnAddTest').on('click', function () { openTest(null); });
    $(document).on('click', '.btn-edit-test', function () { openTest(tests[$(this).data('id')]); });

    $('#testForm').on('submit', function (e) {
        e.preventDefault();
        var id = $('#testId').val();
        var data = { nama: $('#testNama').val(), radiologi_kategori_id: $('#testKategori').val(), harga: $('#testHarga').val(), deskripsi: $('#testDeskripsi').val() };
        var $btn = $(this).find('[type=submit]').prop('disabled', true);
        send(id ? 'PUT' : 'POST', id ? urls.test + '/' + id : urls.test, data)
            .done(function (res) { $('#testModal').modal('hide'); ok(res); })
            .fail(fail)
            .always(function () { $btn.prop('disabled', false); });
    });

    $(document).on('click', '.btn-delete-test', function () {
        var t = tests[$(this).data('id')];
        Swal.fire({ icon: 'warning', title: 'Hapus pemeriksaan?', text: t.nama + ' akan dihapus.', showCancelButton: true, confirmButtonText: 'Ya, hapus', cancelButtonText: 'Batal' })
            .then(function (r) { if (r.isConfirmed) send('DELETE', urls.test + '/' + t.id).done(ok).fail(fail); });
    });

    // ---- kategori
    function promptKategori(k) {
        Swal.fire({
            title: k ? 'Ubah kategori' : 'Tambah kategori', input: 'text', inputValue: k ? k.nama : '',
            inputPlaceholder: 'Nama kategori', showCancelButton: true, confirmButtonText: 'Simpan', cancelButtonText: 'Batal',
            inputValidator: function (v) { return v && v.trim() ? null : 'Nama kategori wajib diisi'; }
        }).then(function (r) {
            if (!r.isConfirmed) return;
            send(k ? 'PUT' : 'POST', k ? urls.kategori + '/' + k.id : urls.kategori, { nama: r.value.trim() }).done(ok).fail(fail);
        });
    }
    function kategoriById(id) { return kategoris.filter(function (k) { return String(k.id) === String(id); })[0]; }
    $('#btnAddKategori').on('click', function () { promptKategori(null); });
    $(document).on('click', '.btn-edit-kategori', function () { promptKategori(kategoriById($(this).data('id'))); });
    $(document).on('click', '.btn-delete-kategori', function () {
        var k = kategoriById($(this).data('id'));
        Swal.fire({ icon: 'warning', title: 'Hapus kategori?', text: k.nama + ' akan dihapus.', showCancelButton: true, confirmButtonText: 'Ya, hapus', cancelButtonText: 'Batal' })
            .then(function (r) { if (r.isConfirmed) send('DELETE', urls.kategori + '/' + k.id).done(ok).fail(fail); });
    });

    load();
});
</script>
@endsection
