@extends('layouts.erm.app')
@section('title', 'Farmasi | Harga Jual Obat')
@section('navbar')
    @include($navbar)
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
        #ajukanItems input { max-width: 140px; }
        .hj-pills .nav-link { font-weight: 600; }
    </style>

    <div class="d-flex flex-wrap align-items-center justify-content-between mt-3 mb-2">
        <div>
            <h4 class="page-title mb-0">Harga Jual Obat</h4>
            <small class="text-muted">
                Margin dihitung dari <strong>HPP</strong> (HNA + PPN {{ $ppn }}%), yaitu modal yang benar-benar dibayar.
                Target margin <strong>{{ rtrim(rtrim(number_format($target, 2, ',', '.'), '0'), ',') }}%</strong>.
                Perubahan harga jual diajukan Farmasi / Admin dan baru berlaku setelah disetujui <strong>Admin / Finance</strong>.
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
                <div class="col-6 col-md-3 col-xl mb-2"><div class="hj-card" data-filter="rugi"><div class="hj-lbl"><span class="badge badge-danger">Rugi</span></div><div class="hj-num text-danger" data-count="rugi">-</div><div class="hj-hint">harga jual di bawah HPP</div></div></div>
                <div class="col-6 col-md-3 col-xl mb-2"><div class="hj-card" data-filter="bawah_target"><div class="hj-lbl"><span class="badge badge-warning">Di bawah target</span></div><div class="hj-num text-warning" data-count="bawah_target">-</div><div class="hj-hint">margin 0 – {{ rtrim(rtrim(number_format($target, 2, ',', '.'), '0'), ',') }}%</div></div></div>
                <div class="col-6 col-md-3 col-xl mb-2"><div class="hj-card" data-filter="aman"><div class="hj-lbl"><span class="badge badge-success">Sesuai target</span></div><div class="hj-num text-success" data-count="aman">-</div><div class="hj-hint">margin ≥ target</div></div></div>
                <div class="col-6 col-md-3 col-xl mb-2"><div class="hj-card" data-filter="tanpa_harga"><div class="hj-lbl">Belum ada harga jual</div><div class="hj-num text-dark" data-count="tanpa_harga">-</div><div class="hj-hint">tidak bisa dijual dengan benar</div></div></div>
                <div class="col-6 col-md-3 col-xl mb-2"><div class="hj-card" data-filter="tanpa_hpp"><div class="hj-lbl">Belum ada HPP</div><div class="hj-num text-muted" data-count="tanpa_hpp">-</div><div class="hj-hint">margin belum bisa dihitung</div></div></div>
                <div class="col-6 col-md-3 col-xl mb-2"><div class="hj-card" data-filter="mencurigakan" title="HPP ≠ HNA + PPN, HNA beda dari faktur terakhir, atau margin ekstrem"><div class="hj-lbl"><span class="badge badge-warning"><i class="fas fa-exclamation-triangle"></i> HNA/HPP mencurigakan</span></div><div class="hj-num text-warning" data-count="mencurigakan">-</div><div class="hj-hint">cek & hitung ulang dari faktur</div></div></div>
                <div class="col-6 col-md-3 col-xl mb-2"><div class="hj-card" data-filter="menunggu"><div class="hj-lbl"><span class="badge badge-primary">Menunggu approval</span></div><div class="hj-num text-primary" data-count="menunggu">-</div><div class="hj-hint">obat dengan pengajuan</div></div></div>
                <div class="col-6 col-md-3 col-xl mb-2"><div class="hj-card" data-filter="semua"><div class="hj-lbl">Semua obat aktif</div><div class="hj-num text-muted" data-count="semua">-</div><div class="hj-hint">&nbsp;</div></div></div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-wrap align-items-center mb-2" style="gap:8px">
                        <input type="search" id="hjSearch" class="form-control form-control-sm" style="max-width:300px" placeholder="Cari nama atau kode obat...">
                        @if($canPropose)
                        <div class="ml-auto d-flex align-items-center" style="gap:6px">
                            <span class="small text-muted" id="hjSelectedInfo"></span>
                            <button type="button" class="btn btn-sm btn-outline-secondary d-none" id="hjClearSelected">Batal pilih</button>
                            @if($isAdmin)
                            <button type="button" class="btn btn-sm btn-warning" id="hjHitungUlang" disabled title="HNA = harga faktur approve terakhir + ongkir, HPP = HNA + PPN"><i class="fas fa-sync-alt"></i> Hitung ulang HNA/HPP dari faktur</button>
                            @endif
                            <button type="button" class="btn btn-sm btn-primary" id="hjAjukanTerpilih" disabled><i class="fas fa-paper-plane"></i> Ajukan harga untuk terpilih</button>
                        </div>
                        @endif
                    </div>
                    <table id="hargaTable" class="table table-sm table-hover w-100">
                        <thead class="thead-light">
                            <tr>
                                <th style="width:24px">@if($canPropose)<input type="checkbox" id="hjCheckAll" title="Pilih semua di halaman ini">@endif</th>
                                <th>Obat</th>
                                <th class="text-right" title="Harga beli + ongkir, tanpa PPN, dari faktur terakhir">HNA</th>
                                <th class="text-right" title="HNA + PPN {{ $ppn }}%: dasar perhitungan margin">HPP</th>
                                <th class="text-right">Harga Jual</th>
                                <th class="text-right" title="(Harga Jual − HPP) / HPP">Margin</th>
                                <th class="text-right" title="HPP × (1 + target margin), dibulatkan ke atas">Saran harga</th>
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
                        <input type="search" id="pjSearch" class="form-control form-control-sm" style="max-width:260px" placeholder="Cari obat...">
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
                                <th>Obat</th>
                                <th class="text-right" title="Harga beli + ongkir tanpa PPN saat ini, dari faktur terakhir">HNA</th>
                                <th class="text-right" title="HNA + PPN {{ $ppn }}% saat ini">HPP</th>
                                <th class="text-right">Harga lama → baru</th>
                                <th class="text-right" title="Terhadap HPP saat diajukan">Margin lama → baru</th>
                                <th>Alasan</th>
                                <th>Diajukan</th>
                                <th>Status</th>
                                <th class="text-right">Aksi</th>
                            </tr>
                        </thead>
                    </table>
                    <small class="text-muted d-block"><span class="badge badge-warning">estimasi</span> obat baru yang belum pernah dibeli: HNA / HPP diambil dari harga Master Pembelian, belum real dari faktur.</small>
                    @if($canApprove)
                    <small class="text-muted">Admin bisa langsung menyetujui pengajuannya sendiri. Harga baru langsung berlaku di e-resep dan kasir setelah disetujui.</small>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Propose new prices (one or many obat) --}}
<div class="modal fade" id="ajukanModal" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-xl" role="document">
        <form class="modal-content" id="ajukanForm" autocomplete="off">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="ajukanTitle">Ajukan Harga Jual</h5>
                    <small class="text-muted">Isi harga baru <em>atau</em> margin yang diinginkan; yang lain dihitung otomatis. Berlaku setelah disetujui Admin / Finance.</small>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-2" id="ajukanItems">
                        <thead class="thead-light">
                            <tr>
                                <th>Obat</th>
                                <th class="text-right">HPP</th>
                                <th class="text-right">Harga sekarang</th>
                                <th>Harga baru (Rp)</th>
                                <th>Margin (%)</th>
                                <th class="text-right">Perubahan</th>
                                <th style="width:34px"></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                <button type="button" class="btn btn-sm btn-outline-success mb-3" id="ajukanPakaiSaran"><i class="fas fa-magic"></i> Semua pakai saran (target {{ rtrim(rtrim(number_format($target, 2, ',', '.'), '0'), ',') }}%)</button>
                <div class="form-group mb-0">
                    <label class="small font-weight-bold mb-1" for="ajukanAlasan">Alasan perubahan <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="ajukanAlasan" rows="2" maxlength="500" placeholder="Mis. HPP naik dari faktur terakhir, penyesuaian harga pasar, promo principal..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary" id="ajukanSubmit"><i class="fas fa-paper-plane"></i> Kirim Pengajuan</button>
            </div>
        </form>
    </div>
</div>

{{-- Price history of one obat --}}
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
                            <tr><th>Diajukan</th><th class="text-right">Harga lama → baru</th><th class="text-right">Margin</th><th>Alasan</th><th>Status</th></tr>
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
    var IS_ADMIN = @json($isAdmin);
    var TARGET = {{ (float) $target }};
    var STEP = {{ max((int) config('pembelian.pembulatan_harga_jual', 100), 1) }};
    var URLS = {
        data: '{{ route('erm.harga-jual.data') }}',
        pengajuan: '{{ route('erm.harga-jual.pengajuan') }}',
        store: '{{ route('erm.harga-jual.store') }}',
        approve: '{{ route('erm.harga-jual.approve') }}',
        hitungUlang: '{{ $isAdmin ? route('erm.harga-jual.hitung-ulang') : '' }}',
        base: '{{ url('/erm/harga-jual') }}'
    };
    var state = { filter: null, selected: {}, pjStatus: 'menunggu', pjSelected: {}, riwayatObat: null };

    function esc(v) { return $('<div>').text(v == null ? '' : v).html(); }
    function rupiah(v) { return v === null || v === undefined || v === '' ? '-' : 'Rp ' + Number(v).toLocaleString('id-ID', { maximumFractionDigits: 0 }); }
    function pct(v) { return v === null || v === undefined ? '-' : Number(v).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + '%'; }
    function marginOf(harga, modal) { return harga > 0 && modal > 0 ? (harga - modal) / modal * 100 : null; }
    function saranOf(modal) { return modal > 0 ? Math.ceil(Math.round(modal * (1 + TARGET / 100) * 10000) / 10000 / STEP) * STEP : null; }
    function marginClass(m) { return m === null ? 'text-muted' : (m < 0 ? 'text-danger' : (m < TARGET ? 'text-warning' : 'text-success')); }
    function marginHtml(m) { return '<span class="font-weight-bold ' + marginClass(m) + '">' + pct(m) + '</span>'; }
    function fail(xhr, fallback) {
        var json = (xhr && xhr.responseJSON) || {};
        var list = json.errors ? [].concat.apply([], Object.values(json.errors)) : [json.message || fallback];
        if (json.gagal && json.gagal.length) list = list.concat(json.gagal);
        if (json.dilewati && json.dilewati.length) list = list.concat(json.dilewati);
        Swal.fire({ icon: 'warning', title: 'Gagal', html: '<div class="text-left small">' + list.filter(function (v, i, a) { return a.indexOf(v) === i; }).map(esc).join('<br>') + '</div>' });
    }

    // ================= Daftar harga =================
    var hargaTable = $('#hargaTable').DataTable({
        processing: true,
        serverSide: true,
        autoWidth: false,
        pageLength: 25,
        dom: 'rt<"d-flex flex-wrap justify-content-between align-items-center mt-2"lip>',
        ajax: { url: URLS.data, data: function (d) { d.filter = state.filter || 'semua'; } },
        order: [[5, 'asc']],
        language: {
            processing: '<i class="fas fa-spinner fa-spin"></i> Memuat...',
            zeroRecords: 'Tidak ada obat di kelompok ini', emptyTable: 'Tidak ada obat',
            info: '_START_–_END_ dari _TOTAL_ obat', infoEmpty: '0 obat', infoFiltered: '',
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
                    var curiga = (r.curiga || []).map(function (a) { return '<div class="small text-warning"><i class="fas fa-exclamation-triangle"></i> ' + esc(a) + '</div>'; }).join('');
                    return '<div class="hj-name">' + esc(d) + '</div><div class="small text-muted">' + esc(r.kode_obat || '-') + (r.kategori ? ' · ' + esc(r.kategori) : '') + '</div>' + curiga;
                }
            },
            { data: 'hna', className: 'text-right text-nowrap', searchable: false, render: function (d) { return d > 0 ? rupiah(d) : '<span class="text-muted">-</span>'; } },
            { data: 'modal', className: 'text-right text-nowrap', searchable: false, render: function (d) { return d > 0 ? rupiah(d) : '<span class="text-muted">-</span>'; } },
            { data: 'harga_nonfornas', className: 'text-right text-nowrap', searchable: false, render: function (d) { return d > 0 ? '<strong>' + rupiah(d) + '</strong>' : '<span class="badge badge-dark">belum ada</span>'; } },
            { data: 'margin', className: 'text-right text-nowrap', searchable: false, render: function (d) { return marginHtml(d); } },
            {
                data: 'saran', className: 'text-right text-nowrap', orderable: false, searchable: false,
                render: function (d, t, r) {
                    if (!d) return '<span class="text-muted">-</span>';
                    var beda = r.harga_nonfornas > 0 ? d - r.harga_nonfornas : null;
                    return rupiah(d) + (beda ? '<div class="small ' + (beda > 0 ? 'text-danger' : 'text-muted') + '">' + (beda > 0 ? '+' : '') + rupiah(beda).replace('Rp -', '−Rp ') + '</div>' : '');
                }
            },
            {
                data: 'pengajuan_id', orderable: false, searchable: false,
                render: function (d, t, r) {
                    if (!d) return '';
                    return '<span class="badge badge-primary">Menunggu</span> <strong>' + rupiah(r.pengajuan_harga) + '</strong>'
                        + '<div class="small text-muted">margin ' + pct(r.pengajuan_margin) + (r.pengajuan_oleh ? ' · ' + esc(r.pengajuan_oleh) : '') + '</div>';
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
        createdRow: function (tr, r) { if (r.margin !== null && r.margin < 0) $(tr).addClass('table-danger'); },
        drawCallback: function (s) {
            var json = s.json || {};
            if (json.counts) {
                $.each(json.counts, function (k, v) { $('#hjCards [data-count="' + k + '"]').text(Number(v).toLocaleString('id-ID')); });
                // First load: open the group that needs attention
                if (state.filter === null) {
                    state.filter = json.counts.rugi > 0 ? 'rugi' : (json.counts.bawah_target > 0 ? 'bawah_target' : 'semua');
                    setFilter(state.filter);
                    return;
                }
            }
            $('#hjCheckAll').prop('checked', false);
            // Rows of the current page, kept for the propose modal
            this.api().rows().data().each(function (r) { rowCache[r.id] = r; });
        }
    });
    var rowCache = {};

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
        $('#hjSelectedInfo').text(n ? n + ' obat dipilih' : '');
        $('#hjClearSelected').toggleClass('d-none', !n);
        $('#hjAjukanTerpilih').prop('disabled', !n);
        $('#hjHitungUlang').prop('disabled', !n);
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

    // Admin: recalculate HNA / HPP of the selected obat from their latest approved faktur
    $('#hjHitungUlang').on('click', function () {
        var ids = Object.keys(state.selected);
        if (!ids.length || !IS_ADMIN) return;
        Swal.fire({
            icon: 'question',
            title: 'Hitung ulang ' + ids.length + ' obat?',
            html: '<div class="text-left small">HNA = harga faktur approve terakhir + bagian ongkir (tanpa PPN), HPP = HNA + PPN.<br>'
                + 'Obat tanpa faktur: hanya HPP disesuaikan menjadi HNA + PPN (bila HNA ada).<br>Harga jual tidak berubah.</div>',
            showCancelButton: true,
            confirmButtonText: 'Ya, hitung ulang',
            cancelButtonText: 'Batal',
            reverseButtons: true
        }).then(function (res) {
            if (!res.value) return;
            var $btn = $('#hjHitungUlang').prop('disabled', true);
            $.post(URLS.hitungUlang, { _token: CSRF, obat_ids: ids }).done(function (r) {
                state.selected = {};
                refreshSelectedInfo();
                hargaTable.ajax.reload(null, false);
                Swal.fire({
                    icon: r.dilewati && r.dilewati.length ? 'warning' : 'success',
                    title: 'Selesai',
                    html: esc(r.message) + (r.dilewati && r.dilewati.length ? '<div class="text-left small mt-2">' + r.dilewati.map(esc).join('<br>') + '</div>' : '')
                });
            }).fail(function (xhr) {
                fail(xhr, 'Gagal menghitung ulang HNA/HPP.');
                refreshSelectedInfo();
            });
        });
    });

    // ================= Propose modal =================
    function itemRow(o) {
        var modal = Number(o.modal || 0);
        var saran = o.saran || saranOf(modal);
        return '<tr data-id="' + o.id + '" data-modal="' + modal + '" data-harga="' + (o.harga_nonfornas || 0) + '" data-saran="' + (saran || '') + '">'
            + '<td><div class="hj-name">' + esc(o.nama) + '</div>'
            + (o.pengajuan_id ? '<div class="small text-primary">Pengajuan ' + rupiah(o.pengajuan_harga) + ' yang masih menunggu akan digantikan.</div>' : '')
            + (!modal ? '<div class="small text-warning">HPP belum ada, margin tidak bisa dihitung.</div>' : '') + '</td>'
            + '<td class="text-right text-nowrap">' + (modal ? rupiah(modal) : '-') + '</td>'
            + '<td class="text-right text-nowrap">' + (o.harga_nonfornas > 0 ? rupiah(o.harga_nonfornas) : '-') + '<div class="small">' + marginHtml(marginOf(o.harga_nonfornas, modal)) + '</div></td>'
            + '<td><input type="number" class="form-control form-control-sm aj-harga" min="1" step="1" placeholder="' + (saran ? 'saran ' + saran : '') + '">'
            + (saran ? '<a href="#" class="small aj-saran">pakai saran ' + rupiah(saran) + '</a>' : '') + '</td>'
            + '<td><input type="number" class="form-control form-control-sm aj-margin" step="0.1"' + (modal ? '' : ' disabled') + '></td>'
            + '<td class="text-right text-nowrap aj-preview small text-muted">-</td>'
            + '<td class="text-center"><button type="button" class="btn btn-sm btn-link text-danger p-0 aj-remove" title="Hapus dari daftar"><i class="fas fa-times"></i></button></td>'
            + '</tr>';
    }

    function updatePreview($tr) {
        var harga = parseFloat($tr.find('.aj-harga').val()) || 0;
        var lama = Number($tr.data('harga')) || 0;
        var modal = Number($tr.data('modal')) || 0;
        if (!harga) { $tr.find('.aj-preview').html('-'); return; }
        var m = marginOf(harga, modal);
        var html = '';
        if (lama > 0) {
            var d = (harga - lama) / lama * 100;
            html += '<span class="' + (d >= 0 ? 'text-danger' : 'text-success') + '">' + (d >= 0 ? '▲ ' : '▼ ') + pct(Math.abs(d)) + '</span><br>';
        }
        html += m === null ? '' : 'margin ' + marginHtml(m);
        if (m !== null && m < 0) html += '<br><span class="text-danger">di bawah HPP (rugi)</span>';
        $tr.find('.aj-preview').html(html);
    }

    function openAjukan(rows) {
        $('#ajukanItems tbody').html(rows.map(itemRow).join(''));
        $('#ajukanTitle').text(rows.length === 1 ? 'Ajukan Harga Jual: ' + rows[0].nama : 'Ajukan Harga Jual untuk ' + rows.length + ' obat');
        $('#ajukanAlasan').val('');
        $('#ajukanModal').modal('show');
    }

    $('#ajukanItems').on('input', '.aj-harga', function () {
        var $tr = $(this).closest('tr');
        var m = marginOf(parseFloat(this.value) || 0, Number($tr.data('modal')));
        $tr.find('.aj-margin').val(m === null ? '' : Math.round(m * 10) / 10);
        updatePreview($tr);
    });
    $('#ajukanItems').on('input', '.aj-margin', function () {
        var $tr = $(this).closest('tr');
        var modal = Number($tr.data('modal'));
        var m = parseFloat(this.value);
        $tr.find('.aj-harga').val(modal > 0 && !isNaN(m) ? Math.ceil(modal * (1 + m / 100)) : '');
        updatePreview($tr);
    });
    $('#ajukanItems').on('click', '.aj-saran', function (e) {
        e.preventDefault();
        var $tr = $(this).closest('tr');
        $tr.find('.aj-harga').val($tr.data('saran')).trigger('input');
    });
    $('#ajukanItems').on('click', '.aj-remove', function () {
        $(this).closest('tr').remove();
        if (!$('#ajukanItems tbody tr').length) $('#ajukanModal').modal('hide');
    });
    $('#ajukanPakaiSaran').on('click', function () {
        $('#ajukanItems tbody tr').each(function () {
            if ($(this).data('saran')) $(this).find('.aj-harga').val($(this).data('saran')).trigger('input');
        });
    });

    $('#ajukanForm').on('submit', function (e) {
        e.preventDefault();
        var items = [], problems = [];
        $('#ajukanItems tbody tr').each(function () {
            var harga = parseFloat($(this).find('.aj-harga').val());
            var nama = $(this).find('.hj-name').text();
            if (!(harga > 0)) { problems.push(nama + ': harga baru belum diisi.'); return; }
            if (harga === Number($(this).data('harga'))) { problems.push(nama + ': harga baru sama dengan harga sekarang.'); return; }
            items.push({ obat_id: $(this).data('id'), harga_baru: harga });
        });
        if (!$.trim($('#ajukanAlasan').val())) problems.push('Alasan perubahan wajib diisi.');
        if (problems.length) {
            Swal.fire({ icon: 'warning', title: 'Periksa lagi', html: '<div class="text-left small">' + problems.map(esc).join('<br>') + '</div>' });
            return;
        }
        var rugi = $('#ajukanItems tbody tr').filter(function () {
            var m = marginOf(parseFloat($(this).find('.aj-harga').val()) || 0, Number($(this).data('modal')));
            return m !== null && m < 0;
        }).length;
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
            icon: 'warning', title: rugi + ' obat di bawah HPP',
            text: 'Harga jual lebih rendah dari modal (rugi). Tetap kirim pengajuan?',
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
    function obatToRow(o) { return { id: o.id, nama: o.nama, modal: o.modal, harga_nonfornas: o.harga, saran: o.saran }; }
    function openRiwayat(id, thenAjukan) {
        $.get(URLS.base + '/obat/' + id + '/riwayat').done(function (res) {
            var o = res.obat;
            state.riwayatObat = obatToRow(o);
            var pending = (res.rows || []).filter(function (r) { return r.status === 'menunggu'; })[0];
            if (pending) { state.riwayatObat.pengajuan_id = 1; state.riwayatObat.pengajuan_harga = pending.harga_baru; }
            if (thenAjukan) { openAjukan([state.riwayatObat]); return; }
            $('#riwayatTitle').text('Riwayat Harga: ' + o.nama);
            $('#riwayatInfo').html('HNA ' + rupiah(o.hna) + ' · HPP ' + rupiah(o.modal) + ' · harga jual ' + rupiah(o.harga) + ' · margin ' + marginHtml(o.margin));
            var badge = { menunggu: 'badge-primary', disetujui: 'badge-success', ditolak: 'badge-danger', dibatalkan: 'badge-secondary' };
            $('#riwayatBody').html((res.rows || []).map(function (r) {
                return '<tr><td class="text-nowrap">' + esc(r.tanggal) + '<div class="small text-muted">' + esc(r.pengaju || '-') + '</div></td>'
                    + '<td class="text-right text-nowrap">' + rupiah(r.harga_lama) + '<span class="hj-arrow">→</span><strong>' + rupiah(r.harga_baru) + '</strong></td>'
                    + '<td class="text-right text-nowrap">' + pct(r.margin_lama) + '<span class="hj-arrow">→</span>' + marginHtml(r.margin_baru) + '</td>'
                    + '<td class="small">' + esc(r.alasan || '-') + '</td>'
                    + '<td><span class="badge ' + (badge[r.status] || 'badge-light') + '">' + esc(r.status_label) + '</span>'
                    + (r.keputusan_at ? '<div class="small text-muted">' + esc((r.keputusan_oleh ? r.keputusan_oleh + ' · ' : '') + r.keputusan_at) + '</div>' : '')
                    + (r.alasan_tolak ? '<div class="small text-danger">' + esc(r.alasan_tolak) + '</div>' : '') + '</td></tr>';
            }).join('') || '<tr><td colspan="5" class="text-center text-muted">Belum ada riwayat perubahan harga lewat pengajuan.</td></tr>');
            $('#riwayatModal').modal('show');
        }).fail(function (xhr) { fail(xhr, 'Riwayat tidak dapat dimuat.'); });
    }
    $(document).on('click', '.btn-riwayat', function () { openRiwayat($(this).data('id')); });
    $('#riwayatAjukan').on('click', function () {
        var row = state.riwayatObat;
        $('#riwayatModal').one('hidden.bs.modal', function () { openAjukan([row]); }).modal('hide');
    });

    // ================= Pengajuan =================
    var STATUS_BADGE = { menunggu: 'badge-primary', disetujui: 'badge-success', ditolak: 'badge-danger', dibatalkan: 'badge-secondary' };
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
            { data: 'obat', name: 'obat', render: function (d, t, r) { return '<div class="hj-name">' + esc(d) + '</div><div class="small text-muted">' + esc(r.kode_obat || '') + '</div>'; } },
            {
                data: 'hna_tampil', orderable: false, searchable: false, className: 'text-right text-nowrap',
                render: function (d, t, r) {
                    if (!(d > 0)) return '<span class="text-muted">-</span><div class="small text-muted">belum pernah dibeli</div>';
                    return rupiah(d) + (r.estimasi ? '<div><span class="badge badge-warning" title="Obat baru, belum pernah dibeli: dari harga Master Pembelian, belum real dari faktur">estimasi</span></div><div class="small text-muted">Master Pembelian</div>' : '');
                }
            },
            {
                data: 'hpp_tampil', orderable: false, searchable: false, className: 'text-right text-nowrap',
                render: function (d, t, r) {
                    if (!(d > 0)) return '<span class="text-muted">-</span>';
                    var html = rupiah(d) + (r.estimasi ? '<div><span class="badge badge-warning">estimasi</span></div>' : '');
                    // HPP (modal) at proposal time, when it differs from today's
                    if (!r.estimasi && r.hna > 0 && Math.abs(r.hna - d) >= 1) html += '<div class="small text-muted">saat diajukan ' + rupiah(r.hna) + '</div>';
                    return html;
                }
            },
            {
                data: 'harga_baru', orderable: false, searchable: false, className: 'text-right text-nowrap',
                render: function (d, t, r) {
                    var lama = Number(r.harga_lama || 0), baru = Number(d);
                    var ch = lama > 0 ? (baru - lama) / lama * 100 : null;
                    return rupiah(r.harga_lama) + '<span class="hj-arrow">→</span><strong>' + rupiah(d) + '</strong>'
                        + (ch !== null ? '<div class="small ' + (ch >= 0 ? 'text-danger' : 'text-success') + '">' + (ch >= 0 ? '▲ ' : '▼ ') + pct(Math.abs(ch)) + '</div>' : '')
                        + (r.harga_berubah ? '<div class="small text-warning" title="Harga jual sudah berubah setelah pengajuan dibuat"><i class="fas fa-exclamation-triangle"></i> harga sekarang ' + rupiah(r.harga_sekarang) + '</div>' : '');
                }
            },
            {
                data: 'margin_baru', orderable: false, searchable: false, className: 'text-right text-nowrap',
                render: function (d, t, r) {
                    var html = pct(r.margin_lama) + '<span class="hj-arrow">→</span>' + marginHtml(d);
                    // New obat: margin against the estimated HPP
                    if (d === null && r.margin_estimasi !== null) html = pct(r.margin_lama) + '<span class="hj-arrow">→</span>' + marginHtml(r.margin_estimasi) + '<div class="small text-warning">dari HPP estimasi</div>';
                    // HPP changed since the proposal (new faktur): show the margin on today's HPP
                    if (r.margin_sekarang_hna !== null && r.margin_baru !== null && Math.abs(r.margin_sekarang_hna - r.margin_baru) >= 0.5) {
                        html += '<div class="small text-warning" title="HPP berubah setelah pengajuan dibuat">dengan HPP sekarang: ' + pct(r.margin_sekarang_hna) + '</div>';
                    }
                    return html;
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
                            + '<button type="button" class="btn btn-sm btn-outline-danger btn-pj-tolak" data-id="' + r.id + '" data-nama="' + esc(r.obat) + '" title="Tolak"><i class="fas fa-times"></i> Tolak</button> ';
                    } else if (r.status === 'menunggu' && r.milik_sendiri && CAN_APPROVE) {
                        html += '<span class="small text-muted mr-1">menunggu approver lain</span>';
                    }
                    if (r.bisa_batal) html += '<button type="button" class="btn btn-sm btn-outline-secondary btn-pj-batal" data-id="' + r.id + '" title="Batalkan pengajuan"><i class="fas fa-ban"></i> Batalkan</button> ';
                    html += '<button type="button" class="btn btn-sm btn-outline-secondary btn-riwayat" data-id="' + r.obat_id + '" title="Riwayat harga obat ini"><i class="fas fa-history"></i></button>';
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
            text: 'Harga jual langsung berlaku di e-resep dan kasir.',
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
            icon: 'warning', title: 'Tolak pengajuan ' + $(this).data('nama') + '?', text: 'Harga jual tidak berubah.',
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

    // From Master Obat: ?ajukan=<obat id> opens the propose form for that obat
    var params = new URLSearchParams(window.location.search);
    if (params.get('ajukan') && CAN_PROPOSE) {
        openRiwayat(params.get('ajukan'), true);
        window.history.replaceState({}, '', window.location.pathname);
    }
});
</script>
@endsection
