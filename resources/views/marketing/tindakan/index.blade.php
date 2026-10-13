@extends('layouts.marketing.app')

@section('title', 'Paket Tindakan - Marketing')

@section('navbar')
    @include('layouts.marketing.navbar')
@endsection

@section('content')
@php $isAdmin = auth()->user()->hasRole('Admin'); @endphp
<style>
    .pt-toolbar { gap: 8px; }
    .pt-toolbar .pt-search { max-width: 320px; }
    .pt-status .btn { font-weight: 600; }
    .pt-status .badge { font-weight: 600; }
    #tindakan-table td { vertical-align: middle; }
    #tindakan-table .pt-nama { font-weight: 600; }
    #tindakan-table .pt-isi { font-size: .78rem; color: #6c757d; }
    #tindakan-table .badge { font-weight: 600; }
    #tindakan-table tr.pt-off td { color: #8a94a6; }
    #tindakan-table tr.pt-selected td { background: #eef4ff; }
    .pt-bulk { background: #eef4ff; border: 1px solid #c9dafc; border-radius: .4rem; padding: .35rem .6rem; }
</style>
<div class="container-fluid">
    <div class="d-flex flex-wrap align-items-center justify-content-between mt-3 mb-3" style="gap:8px">
        <div>
            <h4 class="page-title mb-0">Paket Tindakan</h4>
            <small class="text-muted">Yang dijual ke pasien. Isi paket = satu atau lebih <a href="{{ route('marketing.kode_tindakan.index') }}">tindakan</a> (obat/BHP). Harga berubah lewat <a href="{{ route('marketing.harga-tindakan.index') }}">pengajuan harga</a>.</small>
        </div>
        <div class="d-flex flex-wrap" style="gap:6px">
            @if($isAdmin)
            <button type="button" id="btnImportTindakan" class="btn btn-light btn-sm" title="Buat paket dari CSV"><i class="fas fa-file-import"></i> Import CSV</button>
            <button type="button" id="btnImportRelations" class="btn btn-light btn-sm" title="Isi paket dari CSV: nama paket, tindakan"><i class="fas fa-link"></i> Import Isi Paket</button>
            @endif
            <a href="{{ route('marketing.harga-tindakan.index') }}" class="btn btn-outline-primary btn-sm"><i class="fas fa-tags"></i> Harga & Pengajuan</a>
            <button type="button" class="btn btn-primary btn-sm add-tindakan"><i class="fas fa-plus"></i> Tambah Paket</button>
        </div>
    </div>

    @include('partials.action-cards', ['actionCounts' => $actionCounts])

    <div class="card">
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-center pt-toolbar mb-2">
                <div class="input-group input-group-sm pt-search">
                    <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-search"></i></span></div>
                    <input type="search" id="ptSearch" class="form-control" placeholder="Cari paket, spesialis, atau isi tindakan..." autocomplete="off">
                </div>
                <div class="btn-group btn-group-sm pt-status" role="group" id="ptStatus">
                    <button type="button" class="btn btn-outline-primary active" data-status="active">Aktif <span class="badge badge-light" data-count="active">{{ $statusCounts['active'] }}</span></button>
                    <button type="button" class="btn btn-outline-primary" data-status="inactive">Nonaktif <span class="badge badge-light" data-count="inactive">{{ $statusCounts['inactive'] }}</span></button>
                    <button type="button" class="btn btn-outline-primary" data-status="all">Semua <span class="badge badge-light" data-count="all">{{ $statusCounts['all'] }}</span></button>
                </div>
                <select id="filter_spesialis" class="custom-select custom-select-sm" style="width:auto">
                    <option value="">Semua spesialis</option>
                    @foreach($spesialisasi as $s)
                        <option value="{{ $s->id }}">{{ $s->nama }}</option>
                    @endforeach
                </select>
                <select id="filter_jenis" class="custom-select custom-select-sm" style="width:auto">
                    <option value="">Semua jenis</option>
                    <option value="diskon">Diskon aktif</option>
                    <option value="multivisit">Multi-visit</option>
                    <option value="slimming">Slimming</option>
                    <option value="vaksin">Vaksin</option>
                </select>
                <button type="button" class="btn btn-link btn-sm d-none" id="ptReset">Reset filter</button>
            </div>

            @if($isAdmin)
            <div class="pt-bulk d-none align-items-center mb-2" id="ptBulk" style="gap:8px">
                <strong id="ptBulkInfo"></strong>
                <button type="button" class="btn btn-sm btn-success" data-bulk="1"><i class="fas fa-eye"></i> Aktifkan</button>
                <button type="button" class="btn btn-sm btn-warning" data-bulk="0"><i class="fas fa-eye-slash"></i> Nonaktifkan</button>
                <button type="button" class="btn btn-sm btn-link" id="ptBulkClear">Batal pilih</button>
            </div>
            @endif

            <table id="tindakan-table" class="table table-sm table-hover w-100">
                <thead class="thead-light">
                    <tr>
                        <th style="width:24px">@if($isAdmin)<input type="checkbox" id="ptCheckAll" title="Pilih semua di halaman ini">@endif</th>
                        <th>Paket</th>
                        <th>Spesialis</th>
                        <th class="text-right">Harga</th>
                        <th class="text-center" style="width:80px">Aktif</th>
                        <th class="text-right" style="width:1%">Aksi</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<!-- Modal for Create/Edit Paket -->
<!-- Only the X or the footer button (with confirmation) close it, so a half-filled form is not lost -->
<div class="modal fade" id="tindakanModal" tabindex="-1" role="dialog" aria-labelledby="tindakanModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tindakanModalLabel">Tambah Paket Tindakan</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="tindakanForm">
                @csrf
                <div class="modal-body">
                    <input type="hidden" id="tindakan_id" name="id">
                    <div class="form-row">
                        <div class="form-group col-md-7">
                            <label for="nama">Nama Paket <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nama" name="nama" required>
                            <div class="invalid-feedback" id="nama-error"></div>
                        </div>
                        <div class="form-group col-md-5">
                            <label for="spesialis_id">Spesialis <span class="text-danger">*</span></label>
                            <select class="form-control select2" id="spesialis_id" name="spesialis_id" required>
                                <option value="">Pilih spesialis</option>
                                @foreach($spesialisasi as $s)
                                    <option value="{{ $s->id }}">{{ $s->nama }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" id="spesialis_id-error"></div>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap mb-3" style="gap:18px">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1">
                            <label class="custom-control-label" for="is_active">Aktif</label>
                        </div>
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="is_slimming" name="is_slimming" value="1">
                            <label class="custom-control-label" for="is_slimming">Slimming</label>
                        </div>
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="is_vaksin" name="is_vaksin" value="1">
                            <label class="custom-control-label" for="is_vaksin">Vaksin</label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="mb-0">Isi Paket (Tindakan) <span class="text-danger">*</span></label>
                        <small class="form-text text-muted mt-0 mb-1">Minimal satu tindakan. Paket berisi satu tindakan pun tetap dibuat sebagai paket, karena harga jual hanya ada di paket.</small>
                        <table class="table table-sm table-bordered mb-1" id="kodeTindakanTable">
                            <thead class="thead-light">
                                <tr>
                                    <th style="width:45%">Tindakan</th>
                                    <th>Obat/BHP</th>
                                    <th style="width:34px"></th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                        <div class="invalid-feedback d-block" id="kode_tindakan_ids-error"></div>
                        <button type="button" class="btn btn-sm btn-outline-success" id="addKodeTindakanRow"><i class="fas fa-plus"></i> Tambah Tindakan</button>
                    </div>

                    <hr>
                    <div class="alert alert-info py-2 small" id="hargaInfoBaru">
                        Harga paket baru akan <strong>diajukan</strong> dan baru berlaku setelah disetujui Admin / Finance. Sampai disetujui, paket tertagih Rp 0.
                    </div>
                    <div class="alert alert-secondary py-2 small d-none" id="hargaInfoLock">
                        Harga paket hanya bisa diubah lewat pengajuan harga.
                        <a href="#" id="hargaAjukanLink" target="_blank"><i class="fas fa-paper-plane"></i> Ajukan perubahan harga</a>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="harga">Harga normal <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="harga" name="harga" step="0.01" required>
                            <div class="invalid-feedback" id="harga-error"></div>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="harga_diskon">Harga diskon</label>
                            <input type="number" class="form-control" id="harga_diskon" name="harga_diskon" step="0.01">
                            <div class="invalid-feedback" id="harga_diskon-error"></div>
                            <div class="custom-control custom-checkbox mt-1">
                                <input type="checkbox" class="custom-control-input" id="diskon_active" name="diskon_active" value="1">
                                <label class="custom-control-label small" for="diskon_active">Diskon aktif</label>
                            </div>
                            <div class="invalid-feedback" id="diskon_active-error"></div>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="harga_paket_visit">Harga multi-visit</label>
                            <input type="number" class="form-control" id="harga_paket_visit" name="harga_paket_visit" step="0.01" placeholder="Harga untuk seluruh visit">
                            <div class="invalid-feedback" id="harga_paket_visit-error"></div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="multi_visit_total">Jumlah visit paket</label>
                            <input type="number" class="form-control" id="multi_visit_total" name="multi_visit_total" step="1" min="2" placeholder="Mis. 3 atau 4">
                            <div class="invalid-feedback" id="multi_visit_total-error"></div>
                            <small class="form-text text-muted">Kosongkan jika bukan paket multi-visit.</small>
                        </div>
                        <div class="form-group col-md-4 slimming-quota">
                            <label for="total_rf">Total RF</label>
                            <input type="number" class="form-control" id="total_rf" name="total_rf" step="1" min="0">
                            <div class="invalid-feedback" id="total_rf-error"></div>
                        </div>
                        <div class="form-group col-md-4 slimming-quota">
                            <label for="total_inject">Total Inject</label>
                            <input type="number" class="form-control" id="total_inject" name="total_inject" step="1" min="0">
                            <div class="invalid-feedback" id="total_inject-error"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="tindakanModalFooterClose">Tutup</button>
                    <button type="submit" class="btn btn-primary" id="saveBtn"><i class="fas fa-save"></i> Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

@if($isAdmin)
<!-- Import CSV Modal -->
<div class="modal fade" id="importTindakanModal" tabindex="-1" role="dialog" aria-labelledby="importTindakanModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="importTindakanForm" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="importTindakanModalLabel">Import Paket Tindakan dari CSV</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group mb-0">
                        <label for="csvFileTindakan">File CSV</label>
                        <input type="file" id="csvFileTindakan" name="csv" accept=".csv,text/csv" class="form-control" required />
                        <small class="form-text text-muted">Kolom: nama, harga normal, harga diskon, harga paket, multi_visit_total, spesialis (nama atau id), is_active, is_slimming, is_vaksin (1/0). Header opsional. Harga dari CSV menjadi <strong>pengajuan harga</strong>. Isi paket diisi lewat <strong>Import Isi Paket</strong>.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Import</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Import Relations Modal -->
<div class="modal fade" id="importRelationsModal" tabindex="-1" role="dialog" aria-labelledby="importRelationsModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="importRelationsForm" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="importRelationsModalLabel">Import Isi Paket dari CSV</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group mb-0">
                        <label for="csvRelationsFile">File CSV</label>
                        <input type="file" id="csvRelationsFile" name="csv" accept=".csv,text/csv" class="form-control" required />
                        <small class="form-text text-muted">Dua kolom: kiri = nama paket tindakan, kanan = tindakan (kode atau nama). Header opsional. Isi yang sudah ada tidak dihapus.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Import</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection

@section('scripts')
<script>
$(function () {
    var CSRF = '{{ csrf_token() }}';
    var IS_ADMIN = @json($isAdmin);
    var HARGA_URL = '{{ route('marketing.harga-tindakan.index') }}';
    var state = { status: 'active', needCounts: false, selected: {} };

    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': CSRF } });

    function esc(v) { return $('<div>').text(v == null ? '' : v).html(); }
    function rupiah(v) { return 'Rp ' + Number(v || 0).toLocaleString('id-ID', { maximumFractionDigits: 0 }); }
    function ajaxErrorMessage(xhr, fallback) {
        var json = xhr && xhr.responseJSON;
        if (json && json.errors) {
            var first = Object.values(json.errors)[0];
            if (first && first.length) return first[0];
        }
        return (json && json.message) || fallback || 'Terjadi kesalahan';
    }
    function showError(message) { Swal.fire({ icon: 'error', title: 'Gagal', text: message }); }

    // ================= Table =================
    var table = $('#tindakan-table').DataTable({
        processing: true,
        serverSide: true,
        deferRender: true,
        autoWidth: false,
        pageLength: 25,
        lengthMenu: [10, 25, 50, 100],
        order: [[1, 'asc']],
        dom: 'rt<"d-flex flex-wrap justify-content-between align-items-center mt-2"lip>',
        ajax: {
            url: '{{ route('marketing.tindakan.data') }}',
            data: function (d) {
                d.status = state.status;
                d.spesialis_id = $('#filter_spesialis').val();
                d.jenis = $('#filter_jenis').val();
                if (window.ActionCards && ActionCards.active) d.action = ActionCards.active;
                // Counts only on demand: they are already on the page and only change after an edit
                if (state.needCounts) { d.counts = 1; state.needCounts = false; }
            }
        },
        language: {
            processing: '<i class="fas fa-spinner fa-spin"></i> Memuat...',
            zeroRecords: 'Tidak ada paket yang cocok', emptyTable: 'Belum ada paket tindakan',
            info: '_START_–_END_ dari _TOTAL_ paket', infoEmpty: '0 paket', infoFiltered: '',
            lengthMenu: 'Tampil _MENU_', paginate: { previous: '‹', next: '›' }
        },
        columns: [
            {
                data: 'id', orderable: false, searchable: false,
                render: function (id) { return IS_ADMIN ? '<input type="checkbox" class="pt-check" value="' + id + '"' + (state.selected[id] ? ' checked' : '') + '>' : ''; }
            },
            {
                data: 'nama', name: 'nama', searchable: false,
                render: function (d, t, r) {
                    var badges = '';
                    if (r.is_slimming) badges += ' <span class="badge badge-info">Slimming</span>';
                    if (r.is_vaksin) badges += ' <span class="badge badge-primary">Vaksin</span>';
                    if (r.multi_visit_total > 1) badges += ' <span class="badge badge-secondary">' + r.multi_visit_total + 'x visit</span>';
                    var isi = !r.isi_count
                        ? '<span class="text-danger"><i class="fas fa-exclamation-triangle"></i> belum ada tindakan</span>'
                        : r.isi_count + ' tindakan: ' + (r.isi || []).map(esc).join(', ') + (r.isi_count > (r.isi || []).length ? ', …' : '');
                    if (r.isi_nonaktif > 0) isi += ' <span class="text-warning" title="Ganti atau keluarkan dari paket">· ' + r.isi_nonaktif + ' nonaktif</span>';
                    return '<div class="pt-nama">' + esc(d) + badges + '</div><div class="pt-isi">' + isi + '</div>';
                }
            },
            { data: 'spesialis_nama', name: 'spesialis_nama', searchable: false, render: function (d) { return d ? esc(d) : '<span class="text-muted">-</span>'; } },
            {
                data: 'harga', name: 'harga', searchable: false, className: 'text-right text-nowrap',
                render: function (d, t, r) {
                    var html;
                    if (!(Number(d) > 0)) html = '<span class="badge badge-dark">belum ada harga</span>';
                    else if (r.diskon_active && Number(r.harga_diskon) > 0) html = '<del class="small text-muted">' + rupiah(d) + '</del> <strong>' + rupiah(r.harga_diskon) + '</strong> <span class="badge badge-success">diskon</span>';
                    else html = '<strong>' + rupiah(d) + '</strong>';
                    if (Number(r.harga_paket_visit) > 0) html += '<div class="small text-muted">multi-visit' + (r.multi_visit_total ? ' ' + r.multi_visit_total + 'x' : '') + ': ' + rupiah(r.harga_paket_visit) + '</div>';
                    if (r.pengajuan_harga !== null && r.pengajuan_harga !== undefined) html += '<div><a href="' + HARGA_URL + '" class="badge badge-primary" title="Pengajuan harga menunggu approval">menunggu: ' + rupiah(r.pengajuan_harga) + '</a></div>';
                    return html;
                }
            },
            {
                data: 'is_active', name: 'is_active', searchable: false, className: 'text-center',
                render: function (d, t, r) {
                    return '<div class="custom-control custom-switch d-inline-block" title="' + (d ? 'Aktif: klik untuk nonaktifkan' : 'Nonaktif: klik untuk aktifkan') + '">'
                        + '<input type="checkbox" class="custom-control-input pt-toggle" id="pt_on_' + r.id + '" data-id="' + r.id + '"' + (d ? ' checked' : '') + '>'
                        + '<label class="custom-control-label" for="pt_on_' + r.id + '"></label></div>';
                }
            },
            {
                data: null, orderable: false, searchable: false, className: 'text-right text-nowrap',
                render: function (d, t, r) {
                    return '<button type="button" class="btn btn-sm btn-outline-primary edit-tindakan" data-id="' + r.id + '"><i class="fas fa-edit"></i> Edit</button>'
                        + (IS_ADMIN ? ' <button type="button" class="btn btn-sm btn-outline-danger delete-tindakan" data-id="' + r.id + '" data-nama="' + esc(r.nama) + '" title="Hapus (hanya paket yang belum pernah dipakai)"><i class="fas fa-trash"></i></button>' : '');
                }
            }
        ],
        createdRow: function (tr, r) {
            $(tr).toggleClass('pt-off', !r.is_active).toggleClass('pt-selected', !!state.selected[r.id]);
        },
        drawCallback: function () { $('#ptCheckAll').prop('checked', false); }
    });

    table.on('xhr', function (e, s, json) {
        if (!json) return;
        if (json.action_counts) ActionCards.update(json.action_counts);
        if (json.status_counts) $.each(json.status_counts, function (k, v) { $('#ptStatus [data-count="' + k + '"]').text(v); });
    });
    // After a change: reload this page of the table and the counts
    function reloadWithCounts() { state.needCounts = true; table.ajax.reload(null, false); }

    // ================= Filters =================
    function filtersActive() { return !!($('#ptSearch').val() || $('#filter_spesialis').val() || $('#filter_jenis').val() || state.status !== 'active'); }
    function refresh() { $('#ptReset').toggleClass('d-none', !filtersActive()); table.ajax.reload(); }
    function setStatus(s) {
        state.status = s;
        $('#ptStatus .btn').removeClass('active').filter('[data-status="' + s + '"]').addClass('active');
    }

    var searchTimer;
    $('#ptSearch').on('input', function () {
        var v = this.value;
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function () { $('#ptReset').toggleClass('d-none', !filtersActive()); table.search(v).draw(); }, 350);
    });
    $('#ptStatus').on('click', '.btn', function () { setStatus($(this).data('status')); refresh(); });
    $('#filter_spesialis, #filter_jenis').on('change', refresh);
    $('#ptReset').on('click', function () {
        $('#ptSearch').val('');
        $('#filter_spesialis, #filter_jenis').val('');
        setStatus('active');
        table.search('');
        refresh();
    });

    // "Data yang perlu action" cards: show every status so the card's rows are not hidden by the other filters
    ActionCards.init(function () { table.ajax.reload(); }, function () {
        $('#filter_spesialis, #filter_jenis').val('');
        setStatus('all');
        $('#ptReset').removeClass('d-none');
    });

    // ================= Aktif / nonaktif =================
    $('#tindakan-table').on('change', '.pt-toggle', function () {
        var $cb = $(this).prop('disabled', true);
        $.post('/marketing/tindakan/' + $cb.data('id') + '/toggle-active')
            .done(function () { reloadWithCounts(); })
            .fail(function (xhr) { $cb.prop('checked', !$cb.prop('checked')); showError(ajaxErrorMessage(xhr, 'Status gagal diubah')); })
            .always(function () { $cb.prop('disabled', false); });
    });

    // Bulk (Admin): tick rows, then Aktifkan / Nonaktifkan
    function refreshBulk() {
        var n = Object.keys(state.selected).length;
        $('#ptBulk').toggleClass('d-none', !n).toggleClass('d-flex', !!n);
        $('#ptBulkInfo').text(n + ' paket dipilih');
    }
    $('#tindakan-table').on('change', '.pt-check', function () {
        if (this.checked) state.selected[this.value] = true; else delete state.selected[this.value];
        $(this).closest('tr').toggleClass('pt-selected', this.checked);
        refreshBulk();
    });
    $('#ptCheckAll').on('change', function () {
        var on = this.checked;
        $('#tindakan-table .pt-check').each(function () { if (this.checked !== on) $(this).prop('checked', on).trigger('change'); });
    });
    $('#ptBulkClear').on('click', function () { state.selected = {}; refreshBulk(); table.draw(false); });
    $('#ptBulk').on('click', '[data-bulk]', function () {
        var ids = Object.keys(state.selected).map(Number);
        var on = $(this).data('bulk') === 1;
        Swal.fire({
            icon: 'question', title: (on ? 'Aktifkan ' : 'Nonaktifkan ') + ids.length + ' paket?',
            text: on ? 'Paket muncul lagi di ERM dan kasir.' : 'Paket tidak bisa dipilih lagi di ERM. Riwayat pasien tetap utuh.',
            showCancelButton: true, confirmButtonText: 'Ya', cancelButtonText: 'Batal', reverseButtons: true
        }).then(function (r) {
            if (!r.value) return;
            $.post('{{ url('marketing/tindakan/action/bulk-set-active') }}', { ids: ids, set_active: on ? 1 : 0 })
                .done(function (res) {
                    state.selected = {};
                    refreshBulk();
                    reloadWithCounts();
                    Swal.fire({ icon: 'success', title: 'Selesai', text: (res.updated || 0) + ' paket diperbarui.', timer: 1800, showConfirmButton: false });
                })
                .fail(function (xhr) { showError(ajaxErrorMessage(xhr)); });
        });
    });

    // ================= Form =================
    $('#spesialis_id').select2({ width: '100%', dropdownParent: $('#tindakanModal') });

    function obatListHtml(obats) {
        if (!obats || !obats.length) return '<span class="text-warning small"><i class="fas fa-exclamation-triangle"></i> belum ada obat/BHP</span>';
        return '<ul class="list-unstyled mb-0 small">' + obats.map(function (obat) {
            return '<li>' + esc(obat.nama) + ' <span class="badge badge-light border">' + esc(obat.qty || '') + ' ' + esc(obat.satuan_dosis || '') + '</span></li>';
        }).join('') + '</ul>';
    }

    // One row of the paket's isi. selected = {id, text, obats} for a prefilled row
    function addKodeRow(selected) {
        var $row = $('<tr>'
            + '<td><select class="form-control kode-tindakan-search" style="width:100%"></select>'
            + '<input type="hidden" class="kode-tindakan-id" name="kode_tindakan_ids[]" value=""></td>'
            + '<td class="kode-tindakan-obat-cell"><span class="text-muted small">Pilih tindakan</span></td>'
            + '<td class="text-center"><button type="button" class="btn btn-sm btn-link text-danger p-0 remove-kode-tindakan-row" title="Keluarkan dari paket"><i class="fas fa-times"></i></button></td>'
            + '</tr>');
        $('#kodeTindakanTable tbody').append($row);
        var $select = $row.find('.kode-tindakan-search');
        $select.select2({
            width: '100%',
            placeholder: 'Cari tindakan...',
            minimumInputLength: 2,
            ajax: {
                url: '/marketing/kodetindakan/search',
                dataType: 'json',
                delay: 250,
                data: function (params) { return { q: params.term }; },
                processResults: function (data) { return { results: (data.results || []).map(function (k) { return { id: k.id, text: k.text }; }) }; },
                cache: true
            },
            dropdownParent: $('#tindakanModal')
        }).on('select2:select', function (e) {
            var kodeId = e.params.data.id;
            var $cell = $row.find('.kode-tindakan-obat-cell');
            $row.find('.kode-tindakan-id').val(kodeId);
            $cell.html('<span class="text-muted small">Memuat...</span>');
            $.getJSON('/marketing/kodetindakan/' + kodeId + '/obats')
                .done(function (obats) { $cell.html(obatListHtml(obats)); })
                .fail(function () { $cell.html('<span class="text-danger small">Gagal memuat obat</span>'); });
        });
        if (selected) {
            $select.append(new Option(selected.text, selected.id, true, true)).trigger('change');
            $row.find('.kode-tindakan-id').val(selected.id);
            $row.find('.kode-tindakan-obat-cell').html(obatListHtml(selected.obats));
        }
        return $row;
    }

    $('#addKodeTindakanRow').on('click', function () { addKodeRow().find('.kode-tindakan-search').select2('open'); });
    $(document).on('click', '.remove-kode-tindakan-row', function () { $(this).closest('tr').remove(); });

    // Prices are only entered for a new paket (its first proposal); afterwards they change via Harga Paket Tindakan
    function setHargaMode(tindakanId) {
        var locked = !!tindakanId;
        $('#harga, #harga_diskon, #harga_paket_visit, #diskon_active').prop('disabled', locked);
        $('#hargaInfoBaru').toggleClass('d-none', locked);
        $('#hargaInfoLock').toggleClass('d-none', !locked);
        $('#hargaAjukanLink').attr('href', locked ? HARGA_URL + '?ajukan=' + tindakanId : '#');
    }

    function resetErrors() { $('#tindakanForm .is-invalid').removeClass('is-invalid'); $('#tindakanForm .invalid-feedback').text(''); }
    function resetForm() {
        $('#tindakanForm')[0].reset();
        resetErrors();
        $('#tindakan_id').val('');
        $('#spesialis_id').val('').trigger('change');
        $('#kodeTindakanTable tbody').empty();
        toggleSlimmingQuota();
    }
    // Total RF / Inject only apply to slimming paket
    function toggleSlimmingQuota() { $('.slimming-quota').toggle($('#is_slimming').is(':checked')); }
    $('#is_slimming').on('change', toggleSlimmingQuota);

    $('.add-tindakan').on('click', function () {
        resetForm();
        setHargaMode(null);
        $('#tindakanModalLabel').text('Tambah Paket Tindakan');
        $('#is_active').prop('checked', true);
        addKodeRow(); // a paket needs at least one tindakan
        $('#tindakanModal').modal('show');
    });

    $('#tindakan-table').on('click', '.edit-tindakan', function () {
        var id = $(this).data('id');
        $.getJSON('/marketing/tindakan/' + id).done(function (data) {
            resetForm();
            $('#tindakanModalLabel').text('Edit Paket: ' + data.nama);
            $('#tindakan_id').val(data.id);
            setHargaMode(data.id);
            $('#nama').val(data.nama);
            $('#harga').val(data.harga);
            $('#harga_diskon').val(data.harga_diskon || '');
            $('#harga_paket_visit').val(data.harga_paket_visit || '');
            $('#multi_visit_total').val(data.multi_visit_total || '');
            $('#diskon_active').prop('checked', !!Number(data.diskon_active));
            $('#is_active').prop('checked', !!Number(data.is_active));
            $('#is_slimming').prop('checked', !!Number(data.is_slimming));
            $('#is_vaksin').prop('checked', !!Number(data.is_vaksin));
            $('#total_rf').val(data.total_rf != null ? data.total_rf : '');
            $('#total_inject').val(data.total_inject != null ? data.total_inject : '');
            toggleSlimmingQuota();
            $('#spesialis_id').val(data.spesialis_id).trigger('change');
            (data.kode_tindakans || []).forEach(function (kode) { addKodeRow(kode); });
            if (!(data.kode_tindakans || []).length) addKodeRow();
            $('#tindakanModal').modal('show');
        }).fail(function (xhr) { showError(ajaxErrorMessage(xhr, 'Data paket gagal dimuat')); });
    });

    $('#tindakanModalFooterClose').on('click', function () {
        Swal.fire({
            title: 'Tutup formulir?', text: 'Perubahan yang belum disimpan akan hilang.', icon: 'warning',
            showCancelButton: true, confirmButtonText: 'Ya, tutup', cancelButtonText: 'Kembali', reverseButtons: true
        }).then(function (r) { if (r.value) $('#tindakanModal').modal('hide'); });
    });

    $('#tindakanForm').on('submit', function (e) {
        e.preventDefault();
        resetErrors();
        var $btn = $('#saveBtn').prop('disabled', true);
        $.post('{{ route('marketing.tindakan.store') }}', $(this).serializeArray())
            .done(function (response) {
                $('#tindakanModal').modal('hide');
                Swal.fire({ icon: 'success', title: 'Tersimpan', text: response.message });
                reloadWithCounts();
            })
            .fail(function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    $.each(xhr.responseJSON.errors, function (field, messages) {
                        // kode_tindakan_ids.0 etc. all belong to the isi table
                        if (field.indexOf('kode_tindakan_ids') === 0) field = 'kode_tindakan_ids';
                        $('#' + field).addClass('is-invalid');
                        $('#' + field + '-error').text(messages[0]);
                    });
                } else {
                    showError(ajaxErrorMessage(xhr));
                }
            })
            .always(function () { $btn.prop('disabled', false); });
    });

    // Delete (Admin): the server refuses a paket that was ever used
    $('#tindakan-table').on('click', '.delete-tindakan', function () {
        var id = $(this).data('id');
        Swal.fire({
            title: 'Hapus paket ' + $(this).data('nama') + '?',
            text: 'Paket yang sudah pernah dipakai pasien tidak bisa dihapus; nonaktifkan saja.',
            icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33',
            confirmButtonText: 'Ya, hapus', cancelButtonText: 'Batal', reverseButtons: true, focusCancel: true
        }).then(function (result) {
            if (!result.value) return;
            $.ajax({ url: '/marketing/tindakan/' + id, type: 'DELETE' })
                .done(function (response) { Swal.fire({ icon: 'success', title: 'Terhapus', text: response.message, timer: 1800, showConfirmButton: false }); reloadWithCounts(); })
                .fail(function (xhr) { showError(ajaxErrorMessage(xhr, 'Paket gagal dihapus')); });
        });
    });

    // ================= Import (Admin) =================
    function importCsv($form, $file, url) {
        if (!$file[0].files || !$file[0].files.length) { Swal.fire('Pilih file', 'Silakan pilih file CSV terlebih dahulu.', 'warning'); return; }
        var fd = new FormData();
        fd.append('csv', $file[0].files[0]);
        fd.append('_token', CSRF);
        Swal.fire({ title: 'Mengimpor...', allowOutsideClick: false, onOpen: function () { Swal.showLoading(); } });
        $.ajax({ url: url, type: 'POST', data: fd, processData: false, contentType: false })
            .done(function (res) {
                $form.closest('.modal').modal('hide');
                reloadWithCounts();
                var summary = 'Dibuat: ' + (res.created || 0) + ', dilewati: ' + (res.skipped || 0);
                if (res.errors && res.errors.length) {
                    Swal.fire({ title: 'Import selesai', icon: 'warning', width: 600,
                        html: '<div style="max-height:300px;overflow:auto;text-align:left"><p>' + summary + '</p><ul>' + res.errors.map(function (e) { return '<li>' + esc(e) + '</li>'; }).join('') + '</ul></div>' });
                } else {
                    Swal.fire('Selesai', summary, 'success');
                }
            })
            .fail(function (xhr) { Swal.fire('Gagal', ajaxErrorMessage(xhr, 'Import gagal'), 'error'); });
    }
    $('#btnImportTindakan').on('click', function () { $('#importTindakanForm')[0].reset(); $('#importTindakanModal').modal('show'); });
    $('#btnImportRelations').on('click', function () { $('#importRelationsForm')[0].reset(); $('#importRelationsModal').modal('show'); });
    $('#importTindakanForm').on('submit', function (e) { e.preventDefault(); importCsv($(this), $('#csvFileTindakan'), '/marketing/tindakan/import'); });
    $('#importRelationsForm').on('submit', function (e) { e.preventDefault(); importCsv($(this), $('#csvRelationsFile'), '/marketing/tindakan/import-relations'); });
});
</script>
@endsection
