@extends('layouts.marketing.app')
@section('title', 'Harga Paket Tindakan')
@section('navbar')
    @include('layouts.marketing.navbar')
@endsection

@section('content')
<div class="container-fluid">
    <style>
        .hj-card { border: 1px solid #e3e8f0; border-radius: .5rem; padding: .55rem .8rem; background: #fff; cursor: pointer; height: 100%; transition: box-shadow .15s, border-color .15s; }
        .hj-card:hover { box-shadow: 0 2px 8px rgba(0,0,0,.06); }
        .hj-card.active { border-color: #4e73df; box-shadow: 0 0 0 2px rgba(78,115,223,.25); }
        .hj-card .hj-num { font-size: 1.4rem; font-weight: 700; line-height: 1.1; }
        .hj-card .hj-lbl { font-size: .78rem; color: #6c7a92; }
        .hj-card .hj-hint { font-size: .72rem; color: #8a94a6; }
        #hargaTable td, #pengajuanTable td, #ajukanItems td { vertical-align: middle; }
        .hj-name { font-weight: 600; }
        .hj-arrow { color: #8a94a6; margin: 0 4px; }
        #ajukanItems input[type=number] { min-width: 110px; }
        .hj-pills .nav-link { font-weight: 600; }
        .hj-isi { max-width: 340px; }
    </style>

    <div class="d-flex flex-wrap align-items-center justify-content-between mt-3 mb-2">
        <div>
            <h4 class="page-title mb-0">Harga Paket Tindakan</h4>
            <small class="text-muted">
                Harga jual hanya ada di <strong>Paket Tindakan</strong>; tindakan di dalamnya hanya membawa obat/BHP.
                <strong>Biaya bahan</strong> = jumlah obat/BHP semua tindakan di paket × HPP obat (HNA + PPN).
                Perubahan harga (normal, diskon, on/off diskon, multi-visit) diajukan Marketing / Admin dan baru berlaku setelah disetujui <strong>Admin / Finance</strong>.
            </small>
        </div>
    </div>

    <ul class="nav nav-pills hj-pills mb-3" role="tablist">
        <li class="nav-item"><a class="nav-link {{ $canPropose ? 'active' : '' }}" data-toggle="pill" href="#paneHarga" role="tab" id="tabHarga"><i class="fas fa-tags"></i> Daftar Harga</a></li>
        <li class="nav-item"><a class="nav-link {{ $canPropose ? '' : 'active' }}" data-toggle="pill" href="#panePengajuan" role="tab" id="tabPengajuan"><i class="fas fa-clipboard-check"></i> Pengajuan Harga <span class="badge badge-warning ml-1" id="badgeMenunggu">{{ $menunggu ?: '' }}</span></a></li>
    </ul>

    <div class="tab-content">
        {{-- ================= Daftar harga ================= --}}
        <div class="tab-pane fade {{ $canPropose ? 'show active' : '' }}" id="paneHarga" role="tabpanel">
            <div class="row mb-2" id="hjCards">
                <div class="col-6 col-md-4 col-xl mb-2"><div class="hj-card" data-filter="rugi"><div class="hj-lbl"><span class="badge badge-danger">Rugi</span></div><div class="hj-num text-danger" data-count="rugi">-</div><div class="hj-hint">harga di bawah biaya bahan</div></div></div>
                <div class="col-6 col-md-4 col-xl mb-2"><div class="hj-card" data-filter="tanpa_harga"><div class="hj-lbl">Belum ada harga</div><div class="hj-num text-dark" data-count="tanpa_harga">-</div><div class="hj-hint">tertagih Rp 0</div></div></div>
                <div class="col-6 col-md-4 col-xl mb-2"><div class="hj-card" data-filter="tanpa_isi"><div class="hj-lbl"><span class="badge badge-warning">Belum ada tindakan</span></div><div class="hj-num text-warning" data-count="tanpa_isi">-</div><div class="hj-hint">biaya bahan tidak bisa dihitung</div></div></div>
                <div class="col-6 col-md-4 col-xl mb-2"><div class="hj-card" data-filter="menunggu"><div class="hj-lbl"><span class="badge badge-primary">Menunggu approval</span></div><div class="hj-num text-primary" data-count="menunggu">-</div><div class="hj-hint">paket dengan pengajuan</div></div></div>
                <div class="col-6 col-md-4 col-xl mb-2"><div class="hj-card" data-filter="semua"><div class="hj-lbl">Semua paket aktif</div><div class="hj-num text-muted" data-count="semua">-</div><div class="hj-hint">&nbsp;</div></div></div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-wrap align-items-center mb-2" style="gap:8px">
                        <input type="search" id="hjSearch" class="form-control form-control-sm" style="max-width:300px" placeholder="Cari nama paket...">
                        @if($canPropose)
                        <div class="ml-auto d-flex align-items-center" style="gap:6px">
                            <span class="small text-muted" id="hjSelectedInfo"></span>
                            <button type="button" class="btn btn-sm btn-outline-secondary d-none" id="hjClearSelected">Batal pilih</button>
                            <button type="button" class="btn btn-sm btn-primary" id="hjAjukanTerpilih" disabled><i class="fas fa-paper-plane"></i> Ajukan harga untuk terpilih</button>
                        </div>
                        @endif
                    </div>
                    <table id="hargaTable" class="table table-sm table-hover w-100">
                        <thead class="thead-light">
                            <tr>
                                <th style="width:24px">@if($canPropose)<input type="checkbox" id="hjCheckAll" title="Pilih semua di halaman ini">@endif</th>
                                <th>Paket Tindakan</th>
                                <th class="text-right" title="Obat/BHP semua tindakan di paket × HPP obat (HNA + PPN)">Biaya bahan</th>
                                <th class="text-right">Harga normal</th>
                                <th class="text-right">Harga diskon</th>
                                <th class="text-right">Harga multi-visit</th>
                                <th class="text-right" title="Biaya bahan / harga yang dibayar pasien (harga diskon jika diskon aktif)">Bahan</th>
                                <th>Pengajuan</th>
                                <th class="text-right">Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>

        {{-- ================= Pengajuan ================= --}}
        <div class="tab-pane fade {{ $canPropose ? '' : 'show active' }}" id="panePengajuan" role="tabpanel">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-wrap align-items-center mb-2" style="gap:8px">
                        <div class="btn-group btn-group-sm" role="group" id="pjStatus">
                            <button type="button" class="btn btn-outline-primary active" data-status="menunggu">Menunggu</button>
                            <button type="button" class="btn btn-outline-primary" data-status="disetujui">Disetujui</button>
                            <button type="button" class="btn btn-outline-primary" data-status="ditolak">Ditolak</button>
                            <button type="button" class="btn btn-outline-primary" data-status="dibatalkan">Dibatalkan</button>
                            <button type="button" class="btn btn-outline-primary" data-status="semua">Semua</button>
                        </div>
                        <input type="search" id="pjSearch" class="form-control form-control-sm" style="max-width:260px" placeholder="Cari paket...">
                        @if($canApprove)
                        <div class="ml-auto d-flex align-items-center" style="gap:6px">
                            <span class="small text-muted" id="pjSelectedInfo"></span>
                            <button type="button" class="btn btn-sm btn-success" id="pjSetujuiTerpilih" disabled><i class="fas fa-check-double"></i> Setujui terpilih</button>
                        </div>
                        @endif
                    </div>
                    <table id="pengajuanTable" class="table table-sm table-hover w-100">
                        <thead class="thead-light">
                            <tr>
                                <th style="width:24px">@if($canApprove)<input type="checkbox" id="pjCheckAll" title="Pilih semua yang bisa disetujui di halaman ini">@endif</th>
                                <th>Paket Tindakan</th>
                                <th class="text-right" title="Obat/BHP semua tindakan di paket × HNA obat (tanpa PPN), saat ini">HNA bahan</th>
                                <th class="text-right" title="Obat/BHP semua tindakan di paket × HPP obat (HNA + PPN), saat ini">HPP bahan</th>
                                <th>Perubahan</th>
                                <th class="text-right" title="Saat diajukan">Biaya bahan</th>
                                <th>Alasan</th>
                                <th>Diajukan</th>
                                <th>Status</th>
                                <th class="text-right">Aksi</th>
                            </tr>
                        </thead>
                    </table>
                    <small class="text-muted d-block"><span class="badge badge-warning">estimasi</span> paket berisi obat baru yang belum pernah dibeli: biaya obat itu diambil dari harga Master Pembelian, belum real dari faktur.</small>
                    @if($canApprove)
                    <small class="text-muted">Admin bisa langsung menyetujui pengajuannya sendiri. Harga baru langsung berlaku di ERM dan kasir setelah disetujui.</small>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Propose new prices (one or many paket) --}}
<div class="modal fade" id="ajukanModal" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-xl" role="document">
        <form class="modal-content" id="ajukanForm" autocomplete="off">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="ajukanTitle">Ajukan Harga Paket Tindakan</h5>
                    <small class="text-muted">Isian sudah berisi harga sekarang; ubah yang perlu. Berlaku setelah disetujui Admin / Finance.</small>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-2" id="ajukanItems">
                        <thead class="thead-light">
                            <tr>
                                <th>Paket</th>
                                <th class="text-right">Biaya bahan</th>
                                <th>Harga normal (Rp) <span class="text-danger">*</span></th>
                                <th>Harga diskon (Rp)</th>
                                <th class="text-center">Diskon aktif</th>
                                <th>Harga multi-visit (Rp)</th>
                                <th class="text-right">Perubahan</th>
                                <th style="width:34px"></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                <div class="form-group mb-0">
                    <label class="small font-weight-bold mb-1" for="ajukanAlasan">Alasan perubahan <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="ajukanAlasan" rows="2" maxlength="500" placeholder="Mis. harga bahan naik, penyesuaian harga pasar, promo bulan ini..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary" id="ajukanSubmit"><i class="fas fa-paper-plane"></i> Kirim Pengajuan</button>
            </div>
        </form>
    </div>
</div>

{{-- Price history of one paket --}}
<div class="modal fade" id="riwayatModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="riwayatTitle">Riwayat Harga</h5>
                    <small class="text-muted" id="riwayatInfo"></small>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="thead-light">
                            <tr><th>Diajukan</th><th>Perubahan</th><th class="text-right">Biaya bahan</th><th>Alasan</th><th>Status</th></tr>
                        </thead>
                        <tbody id="riwayatBody"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                @if($canPropose)
                <button type="button" class="btn btn-primary btn-sm mr-auto" id="riwayatAjukan"><i class="fas fa-paper-plane"></i> Ajukan harga baru</button>
                @endif
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(function () {
    var CSRF = '{{ csrf_token() }}';
    var CAN_PROPOSE = @json($canPropose);
    var CAN_APPROVE = @json($canApprove);
    var URLS = {
        data: '{{ route('marketing.harga-tindakan.data') }}',
        pengajuan: '{{ route('marketing.harga-tindakan.pengajuan') }}',
        store: '{{ route('marketing.harga-tindakan.store') }}',
        approve: '{{ route('marketing.harga-tindakan.approve') }}',
        base: '{{ url('/marketing/harga-tindakan') }}'
    };
    var FIELDS = [
        { key: 'harga', label: 'Normal' },
        { key: 'harga_diskon', label: 'Diskon' },
        { key: 'diskon_active', label: 'Diskon aktif', bool: true },
        { key: 'harga_paket_visit', label: 'Multi-visit' }
    ];
    var state = { filter: null, selected: {}, pjStatus: 'menunggu', pjSelected: {}, riwayatRow: null };

    function esc(v) { return $('<div>').text(v == null ? '' : v).html(); }
    function num(v) { return v === null || v === undefined || v === '' ? null : Number(v); }
    function rupiah(v) { return v === null || v === undefined || v === '' ? '-' : 'Rp ' + Number(v).toLocaleString('id-ID', { maximumFractionDigits: 0 }); }
    function pct(v) { return v === null || v === undefined ? '-' : Number(v).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + '%'; }
    function hargaEfektif(r) { return r.diskon_active && num(r.harga_diskon) > 0 ? num(r.harga_diskon) : num(r.harga); }
    function bahanPct(bahan, harga) { return bahan > 0 && harga > 0 ? bahan / harga * 100 : null; }
    function bahanHtml(bahan, harga) {
        var p = bahanPct(bahan, harga);
        if (p === null) return '<span class="text-muted">-</span>';
        return '<span class="font-weight-bold ' + (p >= 100 ? 'text-danger' : 'text-muted') + '">' + pct(p) + '</span>';
    }
    function fieldText(f, v) { return f.bool ? (v ? 'aktif' : 'nonaktif') : rupiah(v); }
    function same(f, a, b) { return f.bool ? !!a === !!b : (num(a) || 0) === (num(b) || 0); }
    // Lines "Normal: Rp a → Rp b" for the fields that changed
    function changesHtml(r) {
        var lines = FIELDS.filter(function (f) { return !same(f, r[f.key + '_lama'], r[f.key + '_baru']); }).map(function (f) {
            return '<div class="text-nowrap"><span class="small text-muted">' + f.label + ':</span> ' + fieldText(f, r[f.key + '_lama']) + '<span class="hj-arrow">→</span><strong>' + fieldText(f, r[f.key + '_baru']) + '</strong></div>';
        });
        return lines.join('') || '<span class="text-muted small">tidak ada perubahan</span>';
    }
    function fail(xhr, fallback) {
        var json = (xhr && xhr.responseJSON) || {};
        var list = json.errors ? [].concat.apply([], Object.values(json.errors)) : [json.message || fallback];
        if (json.gagal && json.gagal.length) list = list.concat(json.gagal);
        if (json.dilewati && json.dilewati.length) list = list.concat(json.dilewati);
        Swal.fire({ icon: 'warning', title: 'Gagal', html: '<div class="text-left small">' + list.filter(function (v, i, a) { return a.indexOf(v) === i; }).map(esc).join('<br>') + '</div>' });
    }

    // ================= Daftar harga =================
    var rowCache = {};
    var hargaTable = $('#hargaTable').DataTable({
        processing: true,
        serverSide: true,
        autoWidth: false,
        pageLength: 25,
        dom: 'rt<"d-flex flex-wrap justify-content-between align-items-center mt-2"lip>',
        ajax: { url: URLS.data, data: function (d) { d.filter = state.filter || 'semua'; } },
        order: [[1, 'asc']],
        language: {
            processing: '<i class="fas fa-spinner fa-spin"></i> Memuat...',
            zeroRecords: 'Tidak ada paket di kelompok ini', emptyTable: 'Tidak ada paket',
            info: '_START_–_END_ dari _TOTAL_ paket', infoEmpty: '0 paket', infoFiltered: '',
            lengthMenu: 'Tampil _MENU_', paginate: { previous: '‹', next: '›' }
        },
        columns: [
            {
                data: 'id', orderable: false, searchable: false,
                render: function (id) { return CAN_PROPOSE ? '<input type="checkbox" class="hj-check" value="' + id + '"' + (state.selected[id] ? ' checked' : '') + '>' : ''; }
            },
            {
                data: 'nama',
                render: function (d, t, r) {
                    return '<div class="hj-name">' + esc(d) + (r.is_active ? '' : ' <span class="badge badge-secondary">nonaktif</span>') + '</div>'
                        + '<div class="small text-muted">' + esc(r.spesialis || '-') + '</div>'
                        + (r.jumlah_isi ? '<div class="small hj-isi text-truncate" title="' + esc(r.isi) + '">' + r.jumlah_isi + ' tindakan: ' + esc(r.isi) + '</div>'
                            : '<div class="small text-warning"><i class="fas fa-exclamation-triangle"></i> belum ada tindakan</div>');
                }
            },
            { data: 'bahan', className: 'text-right text-nowrap', searchable: false, render: function (d) { return d > 0 ? rupiah(d) : '<span class="text-muted">-</span>'; } },
            { data: 'harga', className: 'text-right text-nowrap', searchable: false, render: function (d) { return d > 0 ? '<strong>' + rupiah(d) + '</strong>' : '<span class="badge badge-dark">belum ada</span>'; } },
            {
                data: 'harga_diskon', className: 'text-right text-nowrap', orderable: false, searchable: false,
                render: function (d, t, r) { return d > 0 ? rupiah(d) + '<div class="small">' + (r.diskon_active ? '<span class="badge badge-success">aktif</span>' : '<span class="badge badge-light">nonaktif</span>') + '</div>' : '<span class="text-muted">-</span>'; }
            },
            {
                data: 'harga_paket_visit', className: 'text-right text-nowrap', orderable: false, searchable: false,
                render: function (d, t, r) { return d > 0 ? rupiah(d) + '<div class="small text-muted">' + (r.multi_visit_total || 3) + '× visit</div>' : '<span class="text-muted">-</span>'; }
            },
            { data: 'harga_efektif', className: 'text-right text-nowrap', orderable: false, searchable: false, render: function (d, t, r) { return bahanHtml(r.bahan, hargaEfektif(r)); } },
            {
                data: 'pengajuan_id', orderable: false, searchable: false,
                render: function (d, t, r) {
                    if (!d) return '';
                    return '<span class="badge badge-primary">Menunggu</span> <strong>' + rupiah(r.pengajuan_harga) + '</strong>'
                        + (r.pengajuan_harga_diskon ? '<div class="small">diskon ' + rupiah(r.pengajuan_harga_diskon) + (r.pengajuan_diskon_active ? ' (aktif)' : '') + '</div>' : '')
                        + '<div class="small text-muted">' + esc(r.pengajuan_oleh || '') + '</div>';
                }
            },
            {
                data: null, orderable: false, searchable: false, className: 'text-right text-nowrap',
                render: function (d, t, r) {
                    return (CAN_PROPOSE ? '<button type="button" class="btn btn-sm btn-outline-primary btn-ajukan" data-id="' + r.id + '" title="Ajukan harga baru"><i class="fas fa-paper-plane"></i> Ajukan</button> ' : '')
                        + '<button type="button" class="btn btn-sm btn-outline-secondary btn-riwayat" data-id="' + r.id + '" title="Riwayat harga"><i class="fas fa-history"></i></button>';
                }
            }
        ],
        createdRow: function (tr, r) { var p = bahanPct(r.bahan, hargaEfektif(r)); if (p !== null && p >= 100) $(tr).addClass('table-danger'); },
        drawCallback: function (s) {
            var json = s.json || {};
            if (json.counts) {
                $.each(json.counts, function (k, v) { $('#hjCards [data-count="' + k + '"]').text(Number(v).toLocaleString('id-ID')); });
                // First load: open the group that needs attention
                if (state.filter === null) {
                    state.filter = json.counts.rugi > 0 ? 'rugi' : 'semua';
                    setFilter(state.filter);
                    return;
                }
            }
            $('#hjCheckAll').prop('checked', false);
            // Rows of the current page, kept for the propose modal
            this.api().rows().data().each(function (r) { rowCache[r.id] = r; });
        }
    });

    function setFilter(f) {
        state.filter = f;
        $('#hjCards .hj-card').removeClass('active').filter('[data-filter="' + f + '"]').addClass('active');
        hargaTable.ajax.reload();
    }
    $('#hjCards').on('click', '.hj-card', function () { setFilter($(this).data('filter')); });
    var searchTimer;
    $('#hjSearch').on('input', function () {
        var v = this.value;
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function () { hargaTable.search(v).draw(); }, 300);
    });

    function refreshSelectedInfo() {
        var n = Object.keys(state.selected).length;
        $('#hjSelectedInfo').text(n ? n + ' paket dipilih' : '');
        $('#hjClearSelected').toggleClass('d-none', !n);
        $('#hjAjukanTerpilih').prop('disabled', !n);
    }
    $('#hargaTable').on('change', '.hj-check', function () {
        var id = this.value;
        if (this.checked) state.selected[id] = rowCache[id]; else delete state.selected[id];
        refreshSelectedInfo();
    });
    $('#hjCheckAll').on('change', function () {
        var on = this.checked;
        $('#hargaTable .hj-check').each(function () { if (this.checked !== on) $(this).prop('checked', on).trigger('change'); });
    });
    $('#hjClearSelected').on('click', function () { state.selected = {}; $('#hargaTable .hj-check').prop('checked', false); refreshSelectedInfo(); });

    // ================= Propose modal =================
    function numInput(cls, v) { return '<input type="number" class="form-control form-control-sm ' + cls + '" min="1" step="1" value="' + (num(v) > 0 ? num(v) : '') + '">'; }
    function itemRow(o) {
        var bahan = num(o.bahan) || 0;
        return '<tr data-id="' + o.id + '" data-bahan="' + bahan + '"'
            + ' data-harga="' + (num(o.harga) || '') + '" data-harga_diskon="' + (num(o.harga_diskon) || '') + '" data-diskon_active="' + (o.diskon_active ? 1 : 0) + '" data-harga_paket_visit="' + (num(o.harga_paket_visit) || '') + '">'
            + '<td><div class="hj-name">' + esc(o.nama) + '</div>'
            + (o.pengajuan_id ? '<div class="small text-primary">Pengajuan yang masih menunggu akan digantikan.</div>' : '')
            + (!o.jumlah_isi ? '<div class="small text-warning">Belum ada tindakan di paket ini.</div>' : '') + '</td>'
            + '<td class="text-right text-nowrap">' + (bahan ? rupiah(bahan) : '-') + '</td>'
            + '<td>' + numInput('aj-harga', o.harga) + '</td>'
            + '<td>' + numInput('aj-harga_diskon', o.harga_diskon) + '</td>'
            + '<td class="text-center"><input type="checkbox" class="aj-diskon_active"' + (o.diskon_active ? ' checked' : '') + '></td>'
            + '<td>' + numInput('aj-harga_paket_visit', o.harga_paket_visit) + '<div class="small text-muted">' + (o.multi_visit_total || 3) + '× visit</div></td>'
            + '<td class="text-right text-nowrap aj-preview small text-muted">-</td>'
            + '<td class="text-center"><button type="button" class="btn btn-sm btn-link text-danger p-0 aj-remove" title="Hapus dari daftar"><i class="fas fa-times"></i></button></td>'
            + '</tr>';
    }
    function readItem($tr) {
        return {
            tindakan_id: $tr.data('id'),
            harga: num($tr.find('.aj-harga').val()),
            harga_diskon: num($tr.find('.aj-harga_diskon').val()),
            diskon_active: $tr.find('.aj-diskon_active').is(':checked'),
            harga_paket_visit: num($tr.find('.aj-harga_paket_visit').val())
        };
    }
    function changed($tr, it) {
        return FIELDS.some(function (f) {
            var lama = $tr.data(f.key);
            return !same(f, f.bool ? Number(lama) === 1 : lama, it[f.key]);
        });
    }
    function updatePreview($tr) {
        var it = readItem($tr);
        if (!it.harga) { $tr.find('.aj-preview').html('-'); return; }
        var html = '';
        var lama = Number($tr.data('harga')) || 0;
        if (lama > 0 && it.harga !== lama) {
            var d = (it.harga - lama) / lama * 100;
            html += '<span class="' + (d >= 0 ? 'text-danger' : 'text-success') + '">normal ' + (d >= 0 ? '▲ ' : '▼ ') + pct(Math.abs(d)) + '</span><br>';
        }
        var bahan = Number($tr.data('bahan'));
        var bayar = it.diskon_active && it.harga_diskon > 0 ? it.harga_diskon : it.harga;
        var p = bahanPct(bahan, bayar);
        if (p !== null) html += 'bahan ' + bahanHtml(bahan, bayar) + (p >= 100 ? '<br><span class="text-danger">di bawah biaya bahan</span>' : '');
        if (!changed($tr, it)) html = '<span class="text-muted">belum diubah</span>';
        $tr.find('.aj-preview').html(html || '-');
    }

    function openAjukan(rows) {
        $('#ajukanItems tbody').html(rows.map(itemRow).join(''));
        $('#ajukanItems tbody tr').each(function () { updatePreview($(this)); });
        $('#ajukanTitle').text(rows.length === 1 ? 'Ajukan Harga: ' + rows[0].nama : 'Ajukan Harga untuk ' + rows.length + ' paket');
        $('#ajukanAlasan').val('');
        $('#ajukanModal').modal('show');
    }

    $('#ajukanItems').on('input change', 'input', function () { updatePreview($(this).closest('tr')); });
    $('#ajukanItems').on('click', '.aj-remove', function () {
        $(this).closest('tr').remove();
        if (!$('#ajukanItems tbody tr').length) $('#ajukanModal').modal('hide');
    });

    $('#ajukanForm').on('submit', function (e) {
        e.preventDefault();
        var items = [], problems = [], rugi = 0;
        $('#ajukanItems tbody tr').each(function () {
            var $tr = $(this), it = readItem($tr), nama = $tr.find('.hj-name').text();
            if (!(it.harga > 0)) { problems.push(nama + ': harga normal belum diisi.'); return; }
            if (it.diskon_active && !(it.harga_diskon > 0)) { problems.push(nama + ': harga diskon wajib diisi jika diskon aktif.'); return; }
            if (it.harga_diskon && it.harga_diskon >= it.harga) { problems.push(nama + ': harga diskon harus lebih kecil dari harga normal.'); return; }
            if (!changed($tr, it)) { problems.push(nama + ': harga belum diubah.'); return; }
            var p = bahanPct(Number($tr.data('bahan')), it.diskon_active && it.harga_diskon > 0 ? it.harga_diskon : it.harga);
            if (p !== null && p >= 100) rugi++;
            items.push(it);
        });
        if (!$.trim($('#ajukanAlasan').val())) problems.push('Alasan perubahan wajib diisi.');
        if (problems.length) {
            Swal.fire({ icon: 'warning', title: 'Periksa lagi', html: '<div class="text-left small">' + problems.map(esc).join('<br>') + '</div>' });
            return;
        }
        var kirim = function () {
            var $btn = $('#ajukanSubmit').prop('disabled', true);
            $.ajax({ url: URLS.store, type: 'POST', contentType: 'application/json', headers: { 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' }, data: JSON.stringify({ items: items, alasan: $.trim($('#ajukanAlasan').val()) }) })
                .done(function (res) {
                    $('#ajukanModal').modal('hide');
                    state.selected = {};
                    refreshSelectedInfo();
                    hargaTable.ajax.reload(null, false);
                    pengajuanTable.ajax.reload(null, false);
                    Swal.fire({ icon: 'success', title: 'Terkirim', html: esc(res.message) + ((res.dilewati || []).length ? '<div class="small text-muted mt-2">Dilewati: ' + res.dilewati.map(esc).join(', ') + '</div>' : ''), timer: (res.dilewati || []).length ? undefined : 2200, showConfirmButton: !!(res.dilewati || []).length });
                })
                .fail(function (xhr) { fail(xhr, 'Pengajuan gagal dikirim.'); })
                .always(function () { $btn.prop('disabled', false); });
        };
        if (!rugi) { kirim(); return; }
        Swal.fire({
            icon: 'warning', title: rugi + ' paket di bawah biaya bahan',
            text: 'Harga yang dibayar pasien lebih rendah dari biaya obat/BHP-nya (rugi). Tetap kirim pengajuan?',
            showCancelButton: true, confirmButtonText: 'Tetap kirim', cancelButtonText: 'Periksa lagi', reverseButtons: true
        }).then(function (r) { if (r.value) kirim(); });
    });

    $('#hargaTable').on('click', '.btn-ajukan', function () {
        var r = rowCache[$(this).data('id')];
        if (r) openAjukan([r]);
    });
    $('#hjAjukanTerpilih').on('click', function () {
        var rows = Object.keys(state.selected).map(function (k) { return state.selected[k]; }).filter(Boolean);
        if (rows.length) openAjukan(rows);
    });

    // ================= Riwayat =================
    var STATUS_BADGE = { menunggu: 'badge-primary', disetujui: 'badge-success', ditolak: 'badge-danger', dibatalkan: 'badge-secondary' };
    function openRiwayat(id, thenAjukan) {
        $.get(URLS.base + '/tindakan/' + id + '/riwayat').done(function (res) {
            var t = res.tindakan;
            var pending = (res.rows || []).filter(function (r) { return r.status === 'menunggu'; })[0];
            state.riwayatRow = $.extend({}, t, { pengajuan_id: pending ? 1 : null });
            if (thenAjukan) { openAjukan([state.riwayatRow]); return; }
            $('#riwayatTitle').text('Riwayat Harga: ' + t.nama);
            $('#riwayatInfo').html('Biaya bahan ' + rupiah(t.bahan) + ' · normal ' + rupiah(t.harga)
                + (t.harga_diskon ? ' · diskon ' + rupiah(t.harga_diskon) + (t.diskon_active ? ' (aktif)' : ' (nonaktif)') : '')
                + (t.harga_paket_visit ? ' · multi-visit ' + rupiah(t.harga_paket_visit) : '')
                + '<br>' + (t.jumlah_isi ? t.jumlah_isi + ' tindakan: ' + esc(t.isi) : '<span class="text-warning">belum ada tindakan</span>'));
            $('#riwayatBody').html((res.rows || []).map(function (r) {
                return '<tr><td class="text-nowrap">' + esc(r.tanggal) + '<div class="small text-muted">' + esc(r.pengaju || '-') + '</div></td>'
                    + '<td>' + changesHtml(r) + '</td>'
                    + '<td class="text-right text-nowrap">' + rupiah(r.biaya_bahan) + '</td>'
                    + '<td class="small">' + esc(r.alasan || '-') + '</td>'
                    + '<td><span class="badge ' + (STATUS_BADGE[r.status] || 'badge-light') + '">' + esc(r.status_label) + '</span>'
                    + (r.keputusan_at ? '<div class="small text-muted">' + esc((r.keputusan_oleh ? r.keputusan_oleh + ' · ' : '') + r.keputusan_at) + '</div>' : '')
                    + (r.alasan_tolak ? '<div class="small text-danger">' + esc(r.alasan_tolak) + '</div>' : '') + '</td></tr>';
            }).join('') || '<tr><td colspan="5" class="text-center text-muted">Belum ada riwayat perubahan harga lewat pengajuan.</td></tr>');
            $('#riwayatModal').modal('show');
        }).fail(function (xhr) { fail(xhr, 'Riwayat tidak dapat dimuat.'); });
    }
    $(document).on('click', '.btn-riwayat', function () { openRiwayat($(this).data('id')); });
    $('#riwayatAjukan').on('click', function () {
        var row = state.riwayatRow;
        $('#riwayatModal').one('hidden.bs.modal', function () { openAjukan([row]); }).modal('hide');
    });

    // ================= Pengajuan =================
    var pengajuanTable = $('#pengajuanTable').DataTable({
        processing: true,
        serverSide: true,
        autoWidth: false,
        pageLength: 25,
        dom: 'rt<"d-flex flex-wrap justify-content-between align-items-center mt-2"lip>',
        ajax: { url: URLS.pengajuan, data: function (d) { d.status = state.pjStatus; } },
        order: [[7, 'desc']],
        language: {
            processing: '<i class="fas fa-spinner fa-spin"></i> Memuat...',
            zeroRecords: 'Tidak ada pengajuan', emptyTable: 'Tidak ada pengajuan',
            info: '_START_–_END_ dari _TOTAL_ pengajuan', infoEmpty: '0 pengajuan', infoFiltered: '',
            lengthMenu: 'Tampil _MENU_', paginate: { previous: '‹', next: '›' }
        },
        columns: [
            {
                data: 'id', orderable: false, searchable: false,
                render: function (id, t, r) { return r.bisa_setujui ? '<input type="checkbox" class="pj-check" value="' + id + '"' + (state.pjSelected[id] ? ' checked' : '') + '>' : ''; }
            },
            { data: 'tindakan', name: 'tindakan', render: function (d) { return '<div class="hj-name">' + esc(d) + '</div>'; } },
            {
                data: 'bahan_hna', orderable: false, searchable: false, className: 'text-right text-nowrap',
                render: function (d, t, r) {
                    var html = d > 0 ? rupiah(d) : '<span class="text-muted">-</span>';
                    // Obat in the paket that were never bought: their cost is the Master Pembelian price
                    if (r.bahan_estimasi > 0) html += '<div><span class="badge badge-warning" title="Obat baru, belum pernah dibeli: dari harga Master Pembelian, belum real dari faktur">estimasi</span></div><div class="small text-muted">' + r.bahan_estimasi + ' obat dari Master Pembelian</div>';
                    if (r.bahan_tanpa_hpp > 0) html += '<div class="small text-danger">' + r.bahan_tanpa_hpp + ' obat belum ada HPP</div>';
                    return html;
                }
            },
            {
                data: 'bahan_hpp', orderable: false, searchable: false, className: 'text-right text-nowrap',
                render: function (d, t, r) { return (d > 0 ? rupiah(d) : '<span class="text-muted">-</span>') + (r.bahan_estimasi > 0 ? '<div><span class="badge badge-warning">estimasi</span></div>' : ''); }
            },
            {
                data: 'harga_baru', orderable: false, searchable: false,
                render: function (d, t, r) {
                    return changesHtml(r)
                        + (r.harga_berubah ? '<div class="small text-warning" title="Harga paket sudah berubah setelah pengajuan dibuat"><i class="fas fa-exclamation-triangle"></i> harga sekarang sudah berbeda: normal ' + rupiah(r.harga_sekarang) + '</div>' : '');
                }
            },
            {
                data: 'biaya_bahan', orderable: false, searchable: false, className: 'text-right text-nowrap',
                render: function (d, t, r) {
                    var bayar = r.diskon_active_baru && num(r.harga_diskon_baru) > 0 ? num(r.harga_diskon_baru) : num(r.harga_baru);
                    return rupiah(d) + (d > 0 ? '<div class="small">bahan ' + bahanHtml(num(d), bayar) + '</div>' : '');
                }
            },
            { data: 'alasan', orderable: false, searchable: false, render: function (d) { return '<span class="small">' + esc(d || '-') + '</span>'; } },
            { data: 'tanggal', name: 'tanggal', searchable: false, render: function (d, t, r) { return '<span class="text-nowrap">' + esc(d) + '</span><div class="small text-muted">' + esc(r.pengaju || '-') + (r.milik_sendiri ? ' (Anda)' : '') + '</div>'; } },
            {
                data: 'status', orderable: false, searchable: false,
                render: function (d, t, r) {
                    return '<span class="badge ' + (STATUS_BADGE[d] || 'badge-light') + '">' + esc(r.status_label) + '</span>'
                        + (r.keputusan ? '<div class="small text-muted">' + esc(r.keputusan) + '</div>' : '')
                        + (r.alasan_tolak ? '<div class="small text-danger">' + esc(r.alasan_tolak) + '</div>' : '');
                }
            },
            {
                data: null, orderable: false, searchable: false, className: 'text-right text-nowrap',
                render: function (d, t, r) {
                    var html = '';
                    if (r.bisa_setujui) {
                        html += '<button type="button" class="btn btn-sm btn-success btn-pj-setujui" data-id="' + r.id + '" title="Setujui, harga langsung berlaku"><i class="fas fa-check"></i> Setujui</button> '
                            + '<button type="button" class="btn btn-sm btn-outline-danger btn-pj-tolak" data-id="' + r.id + '" data-nama="' + esc(r.tindakan) + '" title="Tolak"><i class="fas fa-times"></i> Tolak</button> ';
                    } else if (r.status === 'menunggu' && r.milik_sendiri && CAN_APPROVE) {
                        html += '<span class="small text-muted mr-1">menunggu approver lain</span>';
                    }
                    if (r.bisa_batal) html += '<button type="button" class="btn btn-sm btn-outline-secondary btn-pj-batal" data-id="' + r.id + '" title="Batalkan pengajuan"><i class="fas fa-ban"></i> Batalkan</button> ';
                    html += '<button type="button" class="btn btn-sm btn-outline-secondary btn-riwayat" data-id="' + r.tindakan_id + '" title="Riwayat harga paket ini"><i class="fas fa-history"></i></button>';
                    return html;
                }
            }
        ],
        drawCallback: function (s) {
            var json = s.json || {};
            if (json.menunggu !== undefined) $('#badgeMenunggu').text(json.menunggu || '');
            $('#pjCheckAll').prop('checked', false);
        }
    });

    $('#pjStatus').on('click', 'button', function () {
        $('#pjStatus button').removeClass('active');
        $(this).addClass('active');
        state.pjStatus = $(this).data('status');
        state.pjSelected = {};
        refreshPjSelected();
        pengajuanTable.ajax.reload();
    });
    var pjTimer;
    $('#pjSearch').on('input', function () {
        var v = this.value;
        clearTimeout(pjTimer);
        pjTimer = setTimeout(function () { pengajuanTable.search(v).draw(); }, 300);
    });
    $('#tabPengajuan, #tabHarga').on('shown.bs.tab', function () { hargaTable.columns.adjust(); pengajuanTable.columns.adjust(); });

    function refreshPjSelected() {
        var n = Object.keys(state.pjSelected).length;
        $('#pjSelectedInfo').text(n ? n + ' dipilih' : '');
        $('#pjSetujuiTerpilih').prop('disabled', !n);
    }
    $('#pengajuanTable').on('change', '.pj-check', function () {
        if (this.checked) state.pjSelected[this.value] = true; else delete state.pjSelected[this.value];
        refreshPjSelected();
    });
    $('#pjCheckAll').on('change', function () {
        var on = this.checked;
        $('#pengajuanTable .pj-check').each(function () { if (this.checked !== on) $(this).prop('checked', on).trigger('change'); });
    });

    function afterDecision(res) {
        pengajuanTable.ajax.reload(null, false);
        hargaTable.ajax.reload(null, false);
        var gagal = res.gagal || [];
        Swal.fire({ icon: gagal.length ? 'info' : 'success', title: 'Selesai', html: esc(res.message) + (gagal.length ? '<div class="small text-left mt-2">' + gagal.map(esc).join('<br>') + '</div>' : ''), timer: gagal.length ? undefined : 2000, showConfirmButton: !!gagal.length });
    }

    function approve(ids) {
        Swal.fire({
            icon: 'question',
            title: ids.length === 1 ? 'Setujui harga baru?' : 'Setujui ' + ids.length + ' pengajuan?',
            text: 'Harga paket langsung berlaku di ERM dan kasir.',
            showCancelButton: true, confirmButtonText: 'Ya, setujui', cancelButtonText: 'Batal', confirmButtonColor: '#28a745', reverseButtons: true
        }).then(function (r) {
            if (!r.value) return;
            $.ajax({ url: URLS.approve, type: 'POST', contentType: 'application/json', headers: { 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' }, data: JSON.stringify({ ids: ids }) })
                .done(function (res) { state.pjSelected = {}; refreshPjSelected(); afterDecision(res); })
                .fail(function (xhr) { fail(xhr, 'Pengajuan gagal disetujui.'); pengajuanTable.ajax.reload(null, false); });
        });
    }
    $('#pengajuanTable').on('click', '.btn-pj-setujui', function () { approve([$(this).data('id')]); });
    $('#pjSetujuiTerpilih').on('click', function () { approve(Object.keys(state.pjSelected).map(Number)); });

    $('#pengajuanTable').on('click', '.btn-pj-tolak', function () {
        var id = $(this).data('id');
        Swal.fire({
            icon: 'warning', title: 'Tolak pengajuan ' + $(this).data('nama') + '?', text: 'Harga paket tidak berubah.',
            input: 'textarea', inputPlaceholder: 'Alasan penolakan...', inputAttributes: { maxlength: 500 },
            inputValidator: function (v) { if (!v || !v.trim()) return 'Alasan penolakan wajib diisi.'; },
            showCancelButton: true, confirmButtonText: 'Tolak', cancelButtonText: 'Batal', confirmButtonColor: '#d33', reverseButtons: true
        }).then(function (r) {
            if (!r.value) return;
            $.ajax({ url: URLS.base + '/pengajuan/' + id + '/reject', type: 'POST', headers: { Accept: 'application/json' }, data: { _token: CSRF, alasan: r.value.trim() } })
                .done(afterDecision)
                .fail(function (xhr) { fail(xhr, 'Pengajuan gagal ditolak.'); });
        });
    });

    $('#pengajuanTable').on('click', '.btn-pj-batal', function () {
        var id = $(this).data('id');
        Swal.fire({ icon: 'question', title: 'Batalkan pengajuan ini?', showCancelButton: true, confirmButtonText: 'Ya, batalkan', cancelButtonText: 'Tidak', reverseButtons: true })
            .then(function (r) {
                if (!r.value) return;
                $.ajax({ url: URLS.base + '/pengajuan/' + id + '/batal', type: 'POST', headers: { Accept: 'application/json' }, data: { _token: CSRF } })
                    .done(afterDecision)
                    .fail(function (xhr) { fail(xhr, 'Pengajuan gagal dibatalkan.'); });
            });
    });

    // From the Paket Tindakan form: ?ajukan=<tindakan id> opens the propose form for that paket
    var params = new URLSearchParams(window.location.search);
    if (params.get('ajukan') && CAN_PROPOSE) {
        openRiwayat(params.get('ajukan'), true);
        window.history.replaceState({}, '', window.location.pathname);
    }
});
</script>
@endsection
