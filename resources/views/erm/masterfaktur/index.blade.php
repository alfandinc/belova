@extends('layouts.erm.app')
@section('title', 'ERM | Master Pembelian')
@section('navbar')
    @include('layouts.erm.navbar-farmasi')
@endsection
@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">
<div class="container-fluid mf-page">

    {{-- Header --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between mb-3 mt-2">
        <div>
            <h3 class="mb-0">Master Pembelian</h3>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0 bg-transparent mt-1">
                    <li class="breadcrumb-item">ERM</li>
                    <li class="breadcrumb-item">Farmasi</li>
                    <li class="breadcrumb-item active">Master Pembelian</li>
                </ol>
            </nav>
        </div>
        <div class="btn-toolbar mt-2 mt-md-0">
            <button type="button" class="btn btn-primary mr-2" id="btnInputPenawaran"><i class="fas fa-file-invoice-dollar mr-1"></i> Input Penawaran</button>
            <button type="button" class="btn btn-outline-primary mr-2" id="btnBandingkan"><i class="fas fa-balance-scale mr-1"></i> Bandingkan Obat</button>
            <button type="button" class="btn btn-outline-info mr-2" id="btnKelolaPemasok"><i class="fas fa-truck mr-1"></i> Kelola Pemasok</button>
            <button type="button" class="btn btn-outline-success" id="btnDownloadPrincipal"><i class="fas fa-file-excel mr-1"></i> Download by Principal</button>
        </div>
    </div>

    <div class="alert alert-light border small py-2 mb-3">
        <i class="fas fa-info-circle mr-1 text-info"></i>
        Harga pemasok per obat, dipakai otomatis saat membuat permintaan &amp; faktur pembelian.
        <strong>Harga disimpan per satuan stok, belum termasuk PPN.</strong>
        Di <em>Input Penawaran</em> harga boleh diisi per box dan/atau sudah termasuk PPN, sistem yang mengonversi.
        Principal diambil dari <strong>Master Obat</strong>.
    </div>

    <div class="card">
        <div class="card-body pb-2">
            {{-- Filters --}}
            <div class="form-row align-items-end">
                <div class="col-lg-3 col-md-6 mb-2">
                    <label for="filterSearch" class="small text-muted mb-1 font-weight-bold">Cari</label>
                    <div class="input-group">
                        <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-search"></i></span></div>
                        <input type="search" id="filterSearch" class="form-control" placeholder="Obat, kode, pemasok, principal, catatan" autocomplete="off">
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-2">
                    <label for="filterPemasok" class="small text-muted mb-1 font-weight-bold">Pemasok</label>
                    <select id="filterPemasok" class="form-control"></select>
                </div>
                <div class="col-lg-3 col-md-6 mb-2">
                    <label for="filterPrincipal" class="small text-muted mb-1 font-weight-bold">Principal</label>
                    <select id="filterPrincipal" class="form-control"></select>
                </div>
                <div class="col-lg col-md-6 mb-2">
                    <label for="filterObat" class="small text-muted mb-1 font-weight-bold">Obat</label>
                    <select id="filterObat" class="form-control"></select>
                </div>
                <div class="col-lg-auto mb-2 text-right">
                    <button type="button" class="btn btn-light border" id="btnResetFilter"><i class="fas fa-undo mr-1"></i> Reset</button>
                </div>
            </div>

            {{-- scrollX + FixedColumns (same as Rawat Jalan): Kode + Nama Obat pinned left, Aksi pinned right --}}
            <table class="table table-hover table-sm table-bordered w-100 nowrap" id="master-faktur-table">
                <thead class="thead-light">
                    <tr>
                        <th>Kode Obat</th>
                        <th>Nama Obat</th>
                        <th class="text-center">Generik/Paten</th>
                        <th>Pemasok</th>
                        <th title="Diambil dari Master Obat, ubah di sana">Principal</th>
                        <th class="text-right" title="Berapa satuan dalam 1 box">Isi per Box</th>
                        <th class="text-right" title="Harga per satuan × isi per box, belum termasuk PPN, sebelum diskon">Harga per Box</th>
                        <th class="text-right" title="Belum termasuk PPN, sebelum diskon. Ini yang menjadi HPP saat faktur di-approve">Harga per Satuan</th>
                        <th class="text-right">Diskon</th>
                        <th class="text-right" title="Harga per satuan dikurangi diskon persen. Hanya info: HPP tidak dihitung dari harga ini">Setelah Diskon</th>
                        <th class="text-right" title="Harga per satuan + PPN {{ \App\Models\ERM\Obat::PPN_PERCENT }}%">HNA (+PPN)</th>
                        <th class="text-right" title="HPP obat saat ini di Master Obat (dari faktur terakhir yang di-approve), untuk pembanding">HPP Saat Ini</th>
                        <th>Catatan</th>
                        <th>Terakhir Diperbarui</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
            <small class="text-muted d-block mt-1">
                Harga per box dan per satuan <strong>belum termasuk PPN</strong> dan sebelum diskon. HNA = harga per satuan + PPN.
                <strong>HPP Saat Ini</strong> adalah HPP obat sekarang di Master Obat, sebagai pembanding harga per satuan.
                Klik baris untuk mengubah.
            </small>
        </div>
    </div>
</div>

{{-- Modal: Input Penawaran / Edit Harga --}}
<div class="modal fade" id="penawaranModal" tabindex="-1" role="dialog" aria-labelledby="penawaranModalLabel" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="penawaranModalLabel"><i class="fas fa-file-invoice-dollar mr-1"></i> Input Penawaran</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                {{-- Step 1: pemasok & settings --}}
                <div class="form-row">
                    <div class="col-md-4 mb-2">
                        <label for="pnPemasok" class="small font-weight-bold mb-1">Pemasok / distributor <span class="text-danger">*</span></label>
                        <div class="input-group flex-nowrap">
                            <select id="pnPemasok" class="form-control"></select>
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary" type="button" id="btnPemasokBaru" title="Pemasok baru"><i class="fas fa-plus"></i></button>
                            </div>
                        </div>
                        <div class="input-group input-group-sm mt-1 d-none" id="pemasokBaruBox">
                            <input type="text" id="pemasokBaruNama" class="form-control" placeholder="Nama pemasok baru" maxlength="255">
                            <div class="input-group-append">
                                <button class="btn btn-success" type="button" id="btnPemasokBaruSimpan">Simpan</button>
                                <button class="btn btn-light border" type="button" id="btnPemasokBaruBatal">Batal</button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8 mb-2">
                        <label for="pnNotes" class="small font-weight-bold mb-1">Catatan <small class="text-muted">(opsional)</small></label>
                        <input type="text" id="pnNotes" class="form-control" placeholder="mis. Penawaran Okt 2026, berlaku s/d 31 Des" maxlength="1000">
                    </div>
                </div>

                <div class="d-flex flex-wrap align-items-center bg-light border rounded px-2 py-2 mb-2">
                    <span class="small font-weight-bold mr-2">Harga yang diisi:</span>
                    <div class="btn-group btn-group-toggle btn-group-sm mr-3" data-toggle="buttons" id="pnModeGroup">
                        <label class="btn btn-outline-primary active"><input type="radio" name="pnMode" value="box" checked> Per box</label>
                        <label class="btn btn-outline-primary"><input type="radio" name="pnMode" value="satuan"> Per satuan</label>
                    </div>
                    <div class="custom-control custom-checkbox mr-3">
                        <input type="checkbox" class="custom-control-input" id="pnPpn">
                        <label class="custom-control-label small" for="pnPpn">Harga sudah termasuk PPN {{ \App\Models\ERM\Obat::PPN_PERCENT }}%</label>
                    </div>
                    <span class="small text-muted ml-auto" id="pnModeHint"></span>
                </div>

                {{-- Paste dari Excel --}}
                <div id="pastePanel" class="border rounded p-2 mb-2 d-none">
                    <label for="pasteText" class="small font-weight-bold mb-1">Tempel daftar dari Excel / PDF penawaran</label>
                    <textarea id="pasteText" class="form-control form-control-sm text-monospace" rows="6" placeholder="PARACETAMOL 500 MG TAB&#9;10&#9;Rp 35.000&#9;5%&#10;AMOXICILLIN 500 MG&#9;100&#9;48.500&#10;..."></textarea>
                    <small class="form-text text-muted">
                        Satu obat per baris. Kolom dipisah tab (salin langsung dari Excel), titik koma, atau <code>|</code>.
                        Kolom teks pertama = nama obat (atau kode obat); angka terbesar = harga; angka kecil lainnya = isi per box; angka dengan <code>%</code> = diskon.
                        Harga dibaca sesuai pilihan <em>Per box / Per satuan</em> dan <em>PPN</em> di atas. Baris judul tanpa angka dilewati.
                    </small>
                    <div class="text-right mt-2">
                        <button type="button" class="btn btn-sm btn-light border" id="btnPasteBatal">Batal</button>
                        <button type="button" class="btn btn-sm btn-primary" id="btnPasteProses"><i class="fas fa-magic mr-1"></i> Cocokkan dengan master obat</button>
                    </div>
                </div>

                <div class="d-flex flex-wrap align-items-center mb-2">
                    <button type="button" class="btn btn-sm btn-outline-primary mr-2" id="btnTambahBaris"><i class="fas fa-plus mr-1"></i> Tambah obat</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary mr-2" id="btnPaste"><i class="fas fa-paste mr-1"></i> Paste dari Excel</button>
                    <span class="small ml-auto" id="pnSummary"></span>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-1" id="pnTable">
                        <thead class="thead-light">
                            <tr>
                                <th style="width:32px">#</th>
                                <th style="min-width:300px">Obat</th>
                                <th style="width:90px" class="text-right">Isi per box</th>
                                <th style="width:150px" class="text-right" id="pnHargaHead">Harga per box</th>
                                <th style="width:150px" class="text-right">Diskon</th>
                                <th style="width:200px" class="text-right" title="Yang disimpan: per satuan, belum termasuk PPN">Harga per satuan</th>
                                <th style="width:36px"></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                <small class="text-muted">
                    Tekan <kbd>Enter</kbd> untuk pindah kolom / menambah baris. Obat yang sudah punya harga dari pemasok ini akan diperbarui.
                </small>
            </div>
            <div class="modal-footer">
                <span class="small text-muted mr-auto" id="pnFooterInfo"></span>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="btnSimpanPenawaran"><i class="fas fa-save mr-1"></i> Simpan</button>
            </div>
        </div>
    </div>
</div>

{{-- Kelola Pemasok (principal is managed on Master Obat) --}}
@include('erm.partials.supplier-manager', [
    'key' => 'pemasok', 'label' => 'Pemasok', 'subtitle' => 'distributor', 'placeholder' => 'PT ANUGRAH ARGON MEDICA',
    'canDelete' => $canDelete, 'obatHint' => 'Klik jumlah obat untuk melihat harganya di tabel.',
])

{{-- Bandingkan Obat: two obat side by side (left / right) --}}
<div class="modal fade" id="bandingkanModal" tabindex="-1" role="dialog" aria-labelledby="bandingkanModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bandingkanModalLabel"><i class="fas fa-balance-scale mr-1"></i> Bandingkan Obat</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="form-row align-items-end mb-3">
                    <div class="col">
                        <label for="bdObatA" class="small text-muted mb-1 font-weight-bold">Obat kiri</label>
                        <select id="bdObatA" class="form-control"></select>
                    </div>
                    <div class="col-auto">
                        <button type="button" class="btn btn-light border" id="btnBdTukar" title="Tukar kiri dan kanan"><i class="fas fa-exchange-alt"></i></button>
                    </div>
                    <div class="col">
                        <label for="bdObatB" class="small text-muted mb-1 font-weight-bold">Obat kanan</label>
                        <select id="bdObatB" class="form-control"></select>
                    </div>
                </div>
                <div id="bdContent">
                    <div class="text-center text-muted py-5">Pilih dua obat untuk dibandingkan.</div>
                </div>
            </div>
            <div class="modal-footer">
                <small class="text-muted mr-auto">Harga beli per satuan stok, belum termasuk PPN. Hijau = lebih murah.</small>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
<link rel="stylesheet" href="{{ asset('dastone/vendor/datatable/FixedColumns-4.3.0/css/fixedColumns.bootstrap4.min.css') }}">
@endsection

@section('scripts')
<script src="{{ asset('dastone/vendor/datatable/FixedColumns-4.3.0/js/dataTables.fixedColumns.min.js') }}"></script>
<style>
    #master-faktur-table td, #master-faktur-table th { vertical-align: middle; white-space: nowrap; }
    #master-faktur-table tbody tr { cursor: pointer; }
    #master-faktur-table td.col-nama { white-space: normal; min-width: 220px; max-width: 320px; }
    #master-faktur-table td.col-notes { white-space: normal; min-width: 160px; max-width: 260px; font-size: .85rem; }
    #master-faktur-table tr.row-inactive td { background-color: #fdeeee; color: #6c757d; }
    #master-faktur-table th[title] { cursor: help; }
    /* Scroll + FixedColumns, same setup as Rawat Jalan (assets/css/erm/rawatjalans.css).
       The layout wrappers are flex items: without min-width 0 they grow to the table's width and the page scrolls instead of the table. */
    .page-wrapper, .page-content, .container-fluid, .card, .card-body { min-width: 0; overflow-x: visible; }
    #master-faktur-table { width: 100% !important; }
    #master-faktur-table_wrapper { width: 100%; overflow-x: visible; }
    #master-faktur-table_wrapper .dataTables_scrollBody { overflow-x: auto !important; }
    #master-faktur-table_wrapper .dataTables_scroll,
    #master-faktur-table_wrapper .dataTables_scrollHead,
    #master-faktur-table_wrapper .dataTables_scrollBody { width: 100% !important; }
    /* Pinned body cells are opaque, so give them the row's hover tint too */
    #master-faktur-table tbody tr:hover > .dtfc-fixed-left,
    #master-faktur-table tbody tr:hover > .dtfc-fixed-right { background-image: linear-gradient(rgba(0, 0, 0, .075), rgba(0, 0, 0, .075)); }
    .badge-missing { background: #fff3cd; color: #856404; border: 1px solid #ffe08a; }
    .mf-obat { font-weight: 600; }
    .mf-harga { border-bottom: 1px dotted #adb5bd; cursor: help; }
    .mf-meta { font-size: .8rem; color: #6c757d; }
    /* Bandingkan Obat */
    #bdTable { table-layout: fixed; }
    #bdTable th.bd-label { width: 22%; background: #f8f9fa; font-weight: 600; color: #495057; }
    #bdTable td { width: 39%; vertical-align: top; }
    #bdTable tr.bd-section th { background: #e9ecef; text-transform: uppercase; font-size: .75rem; letter-spacing: .03em; }
    #bdTable td.bd-better { background: #e8f5ee; }
    #bdTable .bd-head { font-size: 1rem; font-weight: 700; white-space: normal; }
    .bd-pemasok-table { font-size: .82rem; margin-bottom: 0; }
    .bd-pemasok-table td, .bd-pemasok-table th { white-space: nowrap; padding: .3rem .4rem; }
    #pnTable td { vertical-align: top; }
    #pnTable .form-control-sm { font-size: .85rem; }
    #pnTable .row-info { font-size: .78rem; color: #6c757d; margin-top: 3px; }
    #pnTable .row-result { font-size: .82rem; line-height: 1.35; }
    #pnTable tr.row-error td { background: #fff5f5; }
    #pnTable .candidate { margin: 2px 4px 0 0; }
    #pnTable .select2-container { width: 100% !important; }
    .badge-naik { background: #f8d7da; color: #842029; }
    .badge-turun { background: #d1e7dd; color: #0f5132; }
    .badge-sama { background: #e9ecef; color: #495057; }
    .badge-baru { background: #cfe2ff; color: #084298; }
</style>
@include('erm.partials.supplier-manager-js')
<script>
(function () {
    'use strict';

    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

    const PPN_FACTOR = 1 + @json(\App\Models\ERM\Obat::PPN_PERCENT) / 100;
    const URLS = {
        data: @json(route('erm.masterfaktur.data')),
        info: @json(route('erm.masterfaktur.penawaran.info')),
        bandingkan: @json(route('erm.masterfaktur.bandingkan')),
        match: @json(route('erm.masterfaktur.penawaran.match')),
        save: @json(route('erm.masterfaktur.penawaran.save')),
        masterfaktur: '/erm/masterfaktur',
        obat: '/erm/ajax/obat',
        pemasok: '/erm/ajax/pemasok',
        principal: '/erm/ajax/principal',
        pemasokCrud: '/erm/pemasok'
    };

    let table = null;
    let dirty = false;
    let editMode = false; // modal opened from a row (one existing record)
    let saving = false;
    const infoCache = {}; // obat_id => info for the selected pemasok

    // ---------- helpers ----------
    function escapeHtml(value) {
        if (value === null || value === undefined) return '';
        return String(value).replace(/[&<>"'`=\/]/g, function (s) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;', '/': '&#x2F;', '`': '&#x60;', '=': '&#x3D;' }[s];
        });
    }
    function notify(icon, title, html) { return Swal.fire({ icon: icon, title: title, html: html || '' }); }
    function confirmDialog(title, text, confirmText) {
        return Swal.fire({ title: title, text: text || '', icon: 'warning', showCancelButton: true, confirmButtonText: confirmText || 'Ya', cancelButtonText: 'Batal' })
            .then(function (r) { return !!(r.value || r.isConfirmed); });
    }
    function ajaxErrorHtml(xhr, fallback) {
        const json = xhr && xhr.responseJSON;
        if (json && json.errors) {
            const msgs = [];
            Object.keys(json.errors).forEach(function (k) { json.errors[k].forEach(function (m) { if (msgs.indexOf(m) < 0) msgs.push(m); }); });
            return msgs.map(escapeHtml).join('<br>');
        }
        return escapeHtml((json && json.message) || fallback || 'Terjadi kesalahan.');
    }
    function rupiah(n, decimals) {
        if (n === null || n === undefined || isNaN(n)) return '-';
        return 'Rp ' + Number(n).toLocaleString('id-ID', { maximumFractionDigits: decimals === undefined ? 2 : decimals });
    }
    function num(n) { return Number(n).toLocaleString('id-ID', { maximumFractionDigits: 2 }); }

    /**
     * Number typed or pasted the Indonesian way: "110.000", "Rp 9.909,91", "12,5", "5%".
     */
    function parseNum(value) {
        let s = String(value === undefined || value === null ? '' : value).replace(/rp\.?/gi, '').replace(/[\s%]/g, '');
        if (s === '') return NaN;
        const dot = s.lastIndexOf('.'), comma = s.lastIndexOf(',');
        if (dot > -1 && comma > -1) {
            s = comma > dot ? s.replace(/\./g, '').replace(',', '.') : s.replace(/,/g, '');
        } else if (comma > -1) {
            s = /^\d{1,3}(,\d{3})+$/.test(s) ? s.replace(/,/g, '') : s.replace(',', '.');
        } else if (dot > -1 && /^\d{1,3}(\.\d{3})+$/.test(s)) {
            s = s.replace(/\./g, '');
        }
        const n = parseFloat(s.replace(/[^\d.\-]/g, ''));
        return isNaN(n) ? NaN : n;
    }

    function select2Ajax(url, placeholder, parent, extra) {
        return $.extend({
            width: '100%',
            placeholder: placeholder,
            allowClear: true,
            minimumInputLength: 1,
            dropdownParent: parent || $(document.body),
            ajax: {
                url: url, dataType: 'json', delay: 250,
                data: function (params) { return { q: params.term }; },
                processResults: function (data) { return { results: data }; }
            }
        }, extra || {});
    }

    function pctDiff(a, b) {
        if (!b) return null;
        return Math.round(((a - b) / b) * 1000) / 10;
    }

    // ---------- main table ----------
    // One value per column, same style as the Master Obat table
    const MISSING = function (label) { return '<span class="badge badge-missing">' + label + '</span>'; };
    const DASH = '<span class="text-muted">-</span>';

    function textCell(d, t) {
        if (t !== 'display') return d;
        return d ? escapeHtml(d) : DASH;
    }

    function renderNamaObat(d, t, r) {
        if (t !== 'display') return d;
        return '<span class="mf-obat">' + escapeHtml(d) + '</span>' + (r.obat_aktif ? '' : ' <span class="badge badge-danger">Tidak Aktif</span>');
    }

    // Same badges as the Master Obat table
    function renderGenerik(d, t) {
        if (t !== 'display') return d ? 'Generik' : 'Paten';
        return d ? '<span class="badge badge-success">Generik</span>' : '<span class="badge badge-info">Paten</span>';
    }

    function renderDiskon(d, t, r) {
        if (t !== 'display') return d;
        if (!d) return DASH;
        return r.diskon_type === 'percent' ? num(d) + '%' : '<span title="Dipotong per baris faktur">' + rupiah(d, 0) + '</span>';
    }

    // "100 Tablet"; the satuan is what the price is per
    function renderIsi(d, t, r) {
        if (t !== 'display') return d;
        return num(d) + ' ' + (r.satuan ? escapeHtml(r.satuan) : MISSING('satuan belum diisi'));
    }

    function renderRupiah(d, t) {
        if (t !== 'display') return d;
        return d === null || d === undefined ? DASH : rupiah(d);
    }

    // Only a percent diskon can be shown per satuan; a nominal one is taken off the whole faktur line
    function renderNetto(d, t, r) {
        if (t !== 'display') return d;
        if (!r.diskon) return DASH;
        if (r.diskon_type !== 'percent') return '<span class="text-muted" title="Diskon nominal dipotong per baris faktur, bukan per satuan">-</span>';
        return rupiah(d);
    }

    // HPP is the faktur price before diskon (FakturBeliController::approveFaktur), so it is compared with harga, not netto.
    function renderHpp(d, t, r) {
        if (t !== 'display') return d;
        if (!d) return MISSING('Belum ada');
        const diff = pctDiff(r.harga, d);
        const same = Math.abs(diff) < 0.5;
        const label = same ? 'sama' : (diff > 0 ? 'harga per satuan lebih mahal ' : 'harga per satuan lebih murah ') + num(Math.abs(diff)) + '%';
        // Very large gaps usually mean the HPP or the satuan is wrong, not the price
        const warn = Math.abs(diff) >= 200 ? ' <i class="fas fa-exclamation-triangle text-warning" title="Jauh berbeda dari harga per satuan: cek HPP, isi per box atau satuan obat"></i>' : '';
        const badge = same ? '' : ' <span class="badge ' + (diff > 0 ? 'badge-naik' : 'badge-turun') + '">' + (diff > 0 ? '+' : '−') + num(Math.abs(diff)) + '%</span>';
        return '<span class="mf-harga" title="HPP saat ini di Master Obat (' + escapeHtml(label) + ')">' + rupiah(d) + '</span>' + badge + warn;
    }

    function renderUpdated(d, t) {
        if (t !== 'display') return d;
        if (!d) return DASH;
        const date = new Date(d + 'T00:00:00');
        const text = date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
        const old = (Date.now() - date.getTime()) / 86400000 > 180;
        return escapeHtml(text) + (old ? ' <span class="badge badge-warning" title="Harga lebih dari 6 bulan, minta penawaran terbaru">Lama</span>' : '');
    }

    function renderAksi(d, t, r) {
        return '<div class="btn-group btn-group-sm">' +
            '<button type="button" class="btn btn-outline-secondary btn-bandingkan" data-obat-id="' + r.obat_id + '" data-obat-nama="' + escapeHtml(r.obat) + '" title="Bandingkan dengan obat lain"><i class="fas fa-balance-scale"></i></button>' +
            '<button type="button" class="btn btn-outline-primary btn-edit" data-id="' + r.id + '" title="Edit"><i class="fas fa-edit"></i></button>' +
            '<button type="button" class="btn btn-outline-danger btn-delete" data-id="' + r.id + '" title="Hapus"><i class="fas fa-trash"></i></button></div>';
    }

    function initTable() {
        table = $('#master-faktur-table').DataTable({
            processing: true,
            serverSide: true,
            order: [[13, 'desc']],
            pageLength: 25,
            lengthMenu: [10, 25, 50, 100],
            scrollX: true,
            scrollCollapse: true,
            fixedColumns: { left: 2, right: 1 }, // Kode + Nama Obat, Aksi
            autoWidth: false,
            dom: "rt<'row align-items-center mt-2'<'col-md-4'l><'col-md-4 text-center'i><'col-md-4'p>>",
            createdRow: function (row, data) {
                if (!data.obat_aktif) $(row).addClass('row-inactive');
            },
            ajax: {
                url: URLS.data,
                data: function (d) {
                    d.obat_id = $('#filterObat').val() || '';
                    d.pemasok_id = $('#filterPemasok').val() || '';
                    d.principal_id = $('#filterPrincipal').val() || '';
                },
                error: function (xhr) { notify('error', 'Gagal memuat data', ajaxErrorHtml(xhr)); }
            },
            // name = sort key on the server (MasterFakturController::data)
            columns: [
                { data: 'kode_obat', name: 'kode_obat', render: function (d, t) { return t === 'display' ? (d ? '<span class="font-weight-bold">' + escapeHtml(d) + '</span>' : DASH) : d; } },
                { data: 'obat', name: 'obat', className: 'col-nama', render: renderNamaObat },
                { data: 'is_generik', name: 'is_generik', className: 'text-center', render: renderGenerik },
                { data: 'pemasok', name: 'pemasok', render: textCell },
                { data: 'principal', name: 'principal', render: function (d, t) { return t === 'display' ? (d ? escapeHtml(d) : MISSING('Belum diisi di Master Obat')) : d; } },
                { data: 'qty_per_box', name: 'qty_per_box', className: 'text-right', render: renderIsi },
                { data: 'harga_box', name: 'harga_box', className: 'text-right', render: function (d, t) { return t === 'display' ? rupiah(d, 0) : d; } },
                { data: 'harga', name: 'harga', className: 'text-right font-weight-bold', render: renderRupiah },
                { data: 'diskon', orderable: false, className: 'text-right', render: renderDiskon },
                { data: 'netto', orderable: false, className: 'text-right', render: renderNetto },
                { data: 'hna', name: 'harga', className: 'text-right', render: renderRupiah },
                { data: 'hpp', name: 'hpp', className: 'text-right', render: renderHpp },
                { data: 'notes', orderable: false, className: 'col-notes', render: textCell },
                { data: 'updated_at', name: 'updated_at', render: renderUpdated },
                { data: null, orderable: false, className: 'text-center', render: renderAksi }
            ],
            language: {
                processing: 'Memuat...',
                lengthMenu: 'Tampilkan _MENU_ baris',
                info: '_START_–_END_ dari _TOTAL_ harga',
                infoEmpty: 'Tidak ada data',
                infoFiltered: '(dari _MAX_)',
                zeroRecords: 'Tidak ada data yang cocok.',
                emptyTable: 'Belum ada harga pemasok. Klik "Input Penawaran" untuk mulai.',
                paginate: { previous: '&lsaquo;', next: '&rsaquo;' }
            }
        });
    }

    // ---------- Bandingkan Obat ----------
    let bdRequest = null;

    function bdMessage(text) {
        $('#bdContent').html('<div class="text-center text-muted py-5">' + text + '</div>');
    }

    // Opened from the header (empty) or from a row (that obat on the left)
    function openBandingkan(obatId, obatNama) {
        if (obatId) $('#bdObatA').empty().append(new Option(obatNama, obatId, true, true)).trigger('change.select2');
        const focus = $('#bdObatA').val() ? '#bdObatB' : '#bdObatA';
        $('#bandingkanModal').one('shown.bs.modal', function () { if (!$(focus).val()) $(focus).select2('open'); }).modal('show');
        loadBandingkan();
    }

    function loadBandingkan() {
        const a = $('#bdObatA').val(), b = $('#bdObatB').val();
        if (!a || !b) { bdMessage(a || b ? 'Pilih satu obat lagi untuk dibandingkan.' : 'Pilih dua obat untuk dibandingkan.'); return; }
        if (String(a) === String(b)) { bdMessage('Pilih dua obat yang berbeda.'); return; }

        if (bdRequest) bdRequest.abort();
        $('#bdContent').html('<div class="text-center text-muted py-5"><span class="spinner-border spinner-border-sm mr-2"></span>Memuat...</div>');
        const req = $.get(URLS.bandingkan, { obat_ids: [a, b] });
        bdRequest = req;
        req.done(function (res) {
            const byId = {};
            (res.data || []).forEach(function (o) { byId[o.id] = o; });
            if (!byId[a] || !byId[b]) { bdMessage('Obat tidak ditemukan.'); return; }
            renderBandingkan(byId[a], byId[b]);
        }).fail(function (xhr) {
            if (xhr.statusText === 'abort') return;
            $('#bdContent').html('<div class="alert alert-danger mb-0">' + ajaxErrorHtml(xhr, 'Gagal memuat perbandingan.') + '</div>');
        }).always(function () { if (bdRequest === req) bdRequest = null; });
    }

    /**
     * Price row: the cheaper side is green, the pricier side shows how much more it costs.
     * Prices are only comparable when both obat use the same satuan stok (checked in renderBandingkan).
     */
    function bdPriceCells(x, y, extraX, extraY) {
        const cls = ['', ''], badge = ['', ''];
        if (x && y && Math.abs(x - y) >= 0.005) {
            const cheapIdx = x < y ? 0 : 1;
            cls[cheapIdx] = 'bd-better';
            const pricey = cheapIdx === 0 ? y : x, cheap = cheapIdx === 0 ? x : y;
            badge[1 - cheapIdx] = ' <span class="badge badge-naik" title="Lebih mahal dari obat satunya">+' + num(pctDiff(pricey, cheap)) + '%</span>';
        }
        return '<td class="' + cls[0] + '">' + (x ? rupiah(x) : DASH) + badge[0] + (extraX || '') + '</td>' +
               '<td class="' + cls[1] + '">' + (y ? rupiah(y) : DASH) + badge[1] + (extraY || '') + '</td>';
    }

    function bdPemasokTable(o) {
        if (!o.pemasok.length) return '<span class="text-muted">Belum ada harga pemasok.</span>';
        let html = '<div class="table-responsive"><table class="table table-sm table-bordered bd-pemasok-table"><thead class="thead-light"><tr>' +
            '<th>Pemasok</th><th class="text-right">Harga/Satuan</th><th class="text-right">Diskon</th><th class="text-right">Setelah Diskon</th><th>Diperbarui</th></tr></thead><tbody>';
        o.pemasok.forEach(function (p, i) {
            const diskon = !p.diskon ? DASH : (p.diskon_type === 'percent' ? num(p.diskon) + '%' : rupiah(p.diskon, 0));
            const netto = p.diskon && p.diskon_type === 'percent' ? rupiah(p.netto) : DASH;
            html += '<tr' + (i === 0 && o.pemasok.length > 1 ? ' class="table-success"' : '') + '>' +
                '<td>' + escapeHtml(p.pemasok) + '</td>' +
                '<td class="text-right">' + rupiah(p.harga) + '<div class="mf-meta">' + num(p.qty_per_box) + '/box</div></td>' +
                '<td class="text-right">' + diskon + '</td>' +
                '<td class="text-right">' + netto + '</td>' +
                '<td>' + renderUpdated(p.updated_at, 'display') + '</td></tr>';
        });
        return html + '</tbody></table></div>';
    }

    function renderBandingkan(A, B) {
        const text = function (v) { return v === null || v === undefined || v === '' ? DASH : escapeHtml(v); };
        const row = function (label, a, b) { return '<tr><th class="bd-label">' + label + '</th><td>' + a + '</td><td>' + b + '</td></tr>'; };
        const section = function (label) { return '<tr class="bd-section"><th colspan="3">' + label + '</th></tr>'; };
        const dosis = function (o) { return o.dosis ? escapeHtml(o.dosis + ' ' + (o.satuan_dosis || '')) : DASH; };
        const stok = function (o) { return num(o.stok) + ' ' + escapeHtml(o.satuan_stok || ''); };
        const zat = function (o) { return o.zat_aktif.length ? o.zat_aktif.map(escapeHtml).join(', ') : DASH; };
        const termurah = function (o) { return o.pemasok.length ? o.pemasok[0].netto : null; };
        const termurahNote = function (o) { return o.pemasok.length ? '<div class="mf-meta">' + escapeHtml(o.pemasok[0].pemasok) + '</div>' : ''; };
        const margin = function (o) {
            if (!o.harga_jual || !o.hpp) return DASH;
            const m = o.harga_jual - o.hpp;
            return rupiah(m) + ' <span class="mf-meta">(' + num(pctDiff(o.harga_jual, o.hpp)) + '% dari HPP)</span>';
        };
        const head = function (o) {
            return '<div class="bd-head">' + escapeHtml(o.nama) + '</div>' +
                (o.aktif ? '' : '<span class="badge badge-danger">Tidak Aktif</span>');
        };

        let html = '';
        if ((A.satuan_stok || '') !== (B.satuan_stok || '')) {
            html += '<div class="alert alert-warning small py-2"><i class="fas fa-exclamation-triangle mr-1"></i>' +
                'Satuan stok berbeda (' + text(A.satuan_stok) + ' vs ' + text(B.satuan_stok) + '): harga per satuan tidak bisa dibandingkan langsung.</div>';
        }
        html += '<div class="table-responsive"><table class="table table-bordered table-sm mb-0" id="bdTable"><tbody>' +
            row('', head(A), head(B)) +
            section('Informasi Obat') +
            row('Kode Obat', text(A.kode_obat), text(B.kode_obat)) +
            row('Generik/Paten', renderGenerik(A.is_generik, 'display'), renderGenerik(B.is_generik, 'display')) +
            row('Principal', text(A.principal), text(B.principal)) +
            row('Kategori', text(A.kategori), text(B.kategori)) +
            row('Zat Aktif', zat(A), zat(B)) +
            row('Dosis', dosis(A), dosis(B)) +
            row('Satuan Stok', text(A.satuan_stok), text(B.satuan_stok)) +
            row('Metode Bayar', text(A.metode_bayar), text(B.metode_bayar)) +
            row('Stok Total', stok(A), stok(B)) +
            section('Harga per Satuan Stok') +
            '<tr><th class="bd-label">HPP Saat Ini</th>' + bdPriceCells(A.hpp, B.hpp) + '</tr>' +
            '<tr><th class="bd-label">HNA (+PPN)</th>' + bdPriceCells(A.hna, B.hna) + '</tr>' +
            '<tr><th class="bd-label">Harga Beli Termurah<div class="mf-meta font-weight-normal">setelah diskon, belum PPN</div></th>' +
                bdPriceCells(termurah(A), termurah(B), termurahNote(A), termurahNote(B)) + '</tr>' +
            row('Harga Jual', A.harga_jual ? rupiah(A.harga_jual) : DASH, B.harga_jual ? rupiah(B.harga_jual) : DASH) +
            row('Margin Jual', margin(A), margin(B)) +
            section('Harga per Pemasok') +
            row('<span class="font-weight-normal mf-meta">Termurah di atas</span>', bdPemasokTable(A), bdPemasokTable(B)) +
            '</tbody></table></div>';
        $('#bdContent').html(html);
    }

    function deleteRow(id) {
        const row = table.rows().data().toArray().find(function (r) { return r.id === id; });
        confirmDialog('Hapus harga ini?', row ? row.obat + ' dari ' + row.pemasok : '', 'Ya, hapus').then(function (ok) {
            if (!ok) return;
            $.ajax({ url: URLS.masterfaktur + '/' + id, type: 'DELETE' })
                .done(function () { table.ajax.reload(null, false); notify('success', 'Dihapus'); })
                .fail(function (xhr) { notify('error', 'Gagal menghapus', ajaxErrorHtml(xhr)); });
        });
    }

    // ---------- penawaran form ----------
    function mode() { return $('input[name="pnMode"]:checked').val(); }
    function withPpn() { return $('#pnPpn').is(':checked'); }

    function updateModeHint() {
        const perBox = mode() === 'box';
        $('#pnHargaHead').text(perBox ? 'Harga per box' : 'Harga per satuan');
        $('#pnModeHint').text('Disimpan sebagai harga per satuan' + (withPpn() ? ', PPN dikeluarkan' : ', tanpa PPN') +
            (perBox ? ' (harga box ÷ isi)' : '') + '.');
    }

    // Stored price (per satuan, without PPN) => what the user types with the current settings
    function toInput(hargaSatuan, isi) {
        let v = hargaSatuan;
        if (mode() === 'box') v = v * (isi || 1);
        if (withPpn()) v = v * PPN_FACTOR;
        return Math.round(v * 100) / 100;
    }

    let rowSeq = 0;
    function addRow(prefill) {
        prefill = prefill || {};
        rowSeq++;
        const $tr = $(
            '<tr data-row="' + rowSeq + '">' +
                '<td class="row-no text-muted small pt-2"></td>' +
                '<td><select class="form-control form-control-sm r-obat"></select><div class="row-info"></div></td>' +
                '<td><input type="text" inputmode="numeric" class="form-control form-control-sm text-right r-isi pn-input" placeholder="1"></td>' +
                '<td><input type="text" inputmode="decimal" class="form-control form-control-sm text-right r-harga pn-input" placeholder="0"></td>' +
                '<td><div class="input-group input-group-sm flex-nowrap">' +
                    '<input type="text" inputmode="decimal" class="form-control text-right r-diskon pn-input" placeholder="0">' +
                    '<div class="input-group-append"><select class="custom-select custom-select-sm r-dtype" style="max-width:62px"><option value="percent">%</option><option value="nominal">Rp</option></select></div>' +
                '</div></td>' +
                '<td class="row-result text-right"><span class="text-muted">—</span></td>' +
                '<td><button type="button" class="btn btn-sm btn-link text-danger p-1 r-del" title="Hapus baris"><i class="fas fa-times"></i></button></td>' +
            '</tr>');
        $tr.data('id', prefill.id || null);
        $('#pnTable tbody').append($tr);

        $tr.find('.r-obat').select2(select2Ajax(URLS.obat, 'Cari obat...', $('#penawaranModal'), { minimumInputLength: 2 }));

        if (prefill.obat) $tr.find('.r-obat').append(new Option(prefill.obat.text, prefill.obat.id, true, true)).trigger('change.select2');
        if (prefill.isi) $tr.find('.r-isi').val(prefill.isi);
        if (prefill.harga !== undefined && prefill.harga !== null && !isNaN(prefill.harga)) $tr.find('.r-harga').val(num(prefill.harga));
        if (prefill.diskon) $tr.find('.r-diskon').val(num(prefill.diskon));
        if (prefill.diskon_type) $tr.find('.r-dtype').val(prefill.diskon_type);
        if (prefill.sourceName) $tr.data('sourceName', prefill.sourceName);
        if (prefill.candidates) $tr.data('candidates', prefill.candidates);

        renumber();
        computeRow($tr);
        return $tr;
    }

    function removeRow($tr) {
        $tr.find('select').each(function () { if ($(this).data('select2')) $(this).select2('destroy'); });
        $tr.remove();
    }

    function renumber() {
        $('#pnTable tbody tr').each(function (i) { $(this).find('.row-no').text(i + 1); });
    }

    function rowValues($tr) {
        const isi = parseInt(parseNum($tr.find('.r-isi').val()), 10);
        const hargaInput = parseNum($tr.find('.r-harga').val());
        const diskon = parseNum($tr.find('.r-diskon').val());
        const dtype = $tr.find('.r-dtype').val();
        let harga = NaN;
        if (!isNaN(hargaInput)) {
            harga = hargaInput;
            if (mode() === 'box') harga = isi > 0 ? harga / isi : NaN;
            if (withPpn() && !isNaN(harga)) harga = harga / PPN_FACTOR;
            if (!isNaN(harga)) harga = Math.round(harga * 100) / 100;
        }
        return {
            id: $tr.data('id') || null,
            obat_id: $tr.find('.r-obat').val() || null,
            isi: isi > 0 ? isi : NaN,
            harga: harga,
            diskon: isNaN(diskon) ? 0 : diskon,
            diskon_type: dtype,
            netto: isNaN(harga) ? NaN : (dtype === 'percent' ? Math.round(harga * (1 - (isNaN(diskon) ? 0 : diskon) / 100) * 100) / 100 : harga)
        };
    }

    function isEmptyRow($tr) {
        return !$tr.find('.r-obat').val() && !$tr.find('.r-harga').val() && !$tr.data('sourceName');
    }

    // Obat info line under the select (satuan, HPP, unmatched pasted name with candidates)
    function renderInfo($tr) {
        const obatId = $tr.find('.r-obat').val();
        const info = obatId ? infoCache[obatId] : null;
        const $info = $tr.find('.row-info').empty();
        if (!obatId) {
            const src = $tr.data('sourceName');
            if (src) {
                let html = '<span class="text-danger"><i class="fas fa-question-circle mr-1"></i>Dari penawaran: <strong>' + escapeHtml(src) + '</strong> — pilih obatnya</span>';
                const cands = $tr.data('candidates') || [];
                if (cands.length) {
                    html += '<div>' + cands.map(function (c) {
                        return '<button type="button" class="btn btn-xs btn-outline-secondary btn-sm py-0 px-1 candidate" data-id="' + c.id + '" data-text="' + escapeHtml(c.text) + '">' + escapeHtml(c.text) + '</button>';
                    }).join('') + '</div>';
                } else {
                    html += '<div>Tidak ada yang mirip. Cari manual, atau tambahkan dulu di Master Obat.</div>';
                }
                $info.html(html);
            }
            return;
        }
        if (!info) { $info.text('Memuat...'); return; }
        // Only what is needed to fill the row: the satuan the isi per box is counted in
        $info.html('Satuan: ' + (info.satuan ? escapeHtml(info.satuan)
            : '<span class="badge badge-missing" title="Isi satuan stok di Master Obat supaya isi per box jelas">belum diisi</span>') +
            (info.aktif ? '' : ' <span class="badge badge-secondary">nonaktif</span>'));
        // Pasted rows: the vendor's own name, to double-check the automatic match
        const src = $tr.data('sourceName');
        if (src) $info.append(' <i class="far fa-file-alt text-muted" title="Di penawaran: ' + escapeHtml(src) + '"></i>');
    }

    function computeRow($tr) {
        renderInfo($tr);
        const v = rowValues($tr);
        const info = v.obat_id ? infoCache[v.obat_id] : null;
        const $res = $tr.find('.row-result');
        $tr.removeData('status');

        if (!v.obat_id && !$tr.find('.r-harga').val()) { $res.html('<span class="text-muted">—</span>'); updateSummary(); return; }
        if (isNaN(v.harga)) {
            $res.html('<span class="text-muted">' + (mode() === 'box' && isNaN(v.isi) ? 'isi isi per box' : 'isi harga') + '</span>');
            updateSummary();
            return;
        }

        // One price and one status; details (old price, HNA, HPP, other pemasok) live in the tooltip
        const details = [];
        let status = 'baru';
        let badge;
        if (info && info.current && v.id && String(info.current.id) !== String(v.id)) {
            // Editing one record but switching to an obat + pemasok that already has another record
            status = 'konflik';
            badge = '<span class="badge badge-danger" title="Obat ini sudah punya harga lain dari pemasok ini. Edit data tersebut saja.">Sudah ada</span>';
        } else if (info && info.current) {
            const diff = pctDiff(v.netto, info.current.netto);
            status = diff === null || Math.abs(diff) < 0.05 ? 'sama' : (diff > 0 ? 'naik' : 'turun');
            badge = '<span class="badge badge-' + status + '">' + { sama: 'Sama', naik: 'Naik ' + num(Math.abs(diff)) + '%', turun: 'Turun ' + num(Math.abs(diff)) + '%' }[status] + '</span>';
            details.push('Harga lama: ' + rupiah(info.current.harga) + (info.current.updated_at ? ' (' + info.current.updated_at + ')' : ''));
        } else {
            badge = v.obat_id ? '<span class="badge badge-baru">Baru</span>' : '';
        }
        details.push('HNA (+PPN): ' + rupiah(Math.round(v.harga * PPN_FACTOR * 100) / 100));
        if (v.diskon_type === 'percent' && v.diskon > 0) details.push('Setelah diskon: ' + rupiah(v.netto));
        if (info && info.hpp) details.push('HPP sekarang: ' + rupiah(info.hpp));
        let warn = '';
        if (info && info.best_other && info.best_other.netto < v.netto) {
            warn = ' <i class="fas fa-exclamation-triangle text-warning" title="Lebih murah di ' + escapeHtml(info.best_other.pemasok) + ': ' + rupiah(info.best_other.netto) + '"></i>';
        }

        $res.html('<span class="mf-harga" title="' + escapeHtml(details.join('\n')) + '"><strong>' + rupiah(v.harga) + '</strong></span>' + warn +
            '<div class="mt-1">' + badge + '</div>');
        $tr.data('status', v.obat_id ? status : null);
        updateSummary();
    }

    function computeAll() { $('#pnTable tbody tr').each(function () { computeRow($(this)); }); }

    function updateSummary() {
        const counts = { baru: 0, naik: 0, turun: 0, sama: 0 };
        let total = 0, unmatched = 0;
        $('#pnTable tbody tr').each(function () {
            const $tr = $(this);
            if (isEmptyRow($tr)) return;
            total++;
            if (!$tr.find('.r-obat').val()) unmatched++;
            const s = $tr.data('status');
            if (counts[s] !== undefined) counts[s]++;
        });
        const parts = [];
        if (counts.baru) parts.push('<span class="badge badge-baru">' + counts.baru + ' baru</span>');
        if (counts.naik) parts.push('<span class="badge badge-naik">' + counts.naik + ' naik</span>');
        if (counts.turun) parts.push('<span class="badge badge-turun">' + counts.turun + ' turun</span>');
        if (counts.sama) parts.push('<span class="badge badge-sama">' + counts.sama + ' sama</span>');
        if (unmatched) parts.push('<span class="badge badge-danger">' + unmatched + ' obat belum dipilih</span>');
        $('#pnSummary').html(parts.join(' '));
        $('#pnFooterInfo').text(total ? total + ' obat' : '');
    }

    // Fetch details for obat in the grid (satuan, HPP, current price from this pemasok, cheapest elsewhere)
    function loadInfo(obatIds, force) {
        const pemasokId = $('#pnPemasok').val() || '';
        const ids = (obatIds || []).filter(function (id) { return id && (force || !infoCache[id]); });
        if (!ids.length) { computeAll(); return $.Deferred().resolve().promise(); }
        return $.get(URLS.info, { obat_ids: ids, pemasok_id: pemasokId })
            .done(function (res) {
                Object.keys(res.data || {}).forEach(function (id) { infoCache[id] = res.data[id]; });
                computeAll();
            })
            .fail(function (xhr) { notify('error', 'Gagal memuat info obat', ajaxErrorHtml(xhr)); });
    }

    function allObatIds() {
        return $('#pnTable .r-obat').map(function () { return $(this).val(); }).get().filter(Boolean);
    }

    // When an obat is picked in a new row, copy isi/diskon from this pemasok's current price
    function onObatPicked($tr) {
        const obatId = $tr.find('.r-obat').val();
        if (!obatId) { computeRow($tr); return; }
        const dup = $('#pnTable .r-obat').filter(function () { return this !== $tr.find('.r-obat')[0] && $(this).val() === obatId; });
        if (dup.length) {
            notify('warning', 'Obat sudah ada di daftar', 'Obat ini sudah dimasukkan di baris ' + (dup.closest('tr').index() + 1) + '.');
        }
        loadInfo([obatId]).then(function () {
            const info = infoCache[obatId];
            if (!info) return;
            const cur = info.current;
            if (cur) {
                if (!$tr.find('.r-isi').val()) $tr.find('.r-isi').val(cur.qty_per_box);
                if (!$tr.find('.r-diskon').val() && cur.diskon) { $tr.find('.r-diskon').val(num(cur.diskon)); $tr.find('.r-dtype').val(cur.diskon_type); }
                $tr.find('.r-harga').attr('placeholder', 'lama ' + num(toInput(cur.harga, cur.qty_per_box)));
            }
            computeRow($tr);
            const $next = $tr.find('.r-isi').val() ? $tr.find('.r-harga') : $tr.find('.r-isi');
            setTimeout(function () { $next.trigger('focus'); }, 50);
        });
    }

    function resetPenawaran() {
        $('#pnTable tbody').empty();
        $('#pnPemasok').empty().trigger('change.select2');
        $('#pnPemasok').prop('disabled', false);
        $('#pnNotes').val('');
        $('#pasteText').val('');
        $('#pastePanel, #pemasokBaruBox').addClass('d-none');
        $('#pnSummary, #pnFooterInfo').empty();
        Object.keys(infoCache).forEach(function (k) { delete infoCache[k]; });
        editMode = false;
        dirty = false;
    }

    function openPenawaran() {
        resetPenawaran();
        $('#penawaranModalLabel').html('<i class="fas fa-file-invoice-dollar mr-1"></i> Input Penawaran');
        $('#btnSimpanPenawaran').html('<i class="fas fa-save mr-1"></i> Simpan');
        // Keep the pemasok picked in the filter, the usual case is entering that vendor's offer
        const fp = $('#filterPemasok').val();
        if (fp) $('#pnPemasok').append(new Option($('#filterPemasok').find('option:selected').text(), fp, true, true)).trigger('change.select2');
        addRow();
        $('#penawaranModal').modal('show');
    }

    function openEdit(id) {
        $.get(URLS.masterfaktur + '/' + id).done(function (d) {
            resetPenawaran();
            $('#penawaranModalLabel').html('<i class="fas fa-edit mr-1"></i> Edit Harga');
            $('#btnSimpanPenawaran').html('<i class="fas fa-save mr-1"></i> Simpan perubahan');
            // Edit shows the stored value as is: per satuan, without PPN
            $('input[name="pnMode"][value="satuan"]').prop('checked', true).closest('label').addClass('active').siblings().removeClass('active');
            $('#pnPpn').prop('checked', false);
            updateModeHint();
            $('#pnPemasok').append(new Option(d.pemasok_nama, d.pemasok_id, true, true)).trigger('change.select2');
            $('#pnNotes').val(d.notes || '');
            addRow({
                id: d.id,
                obat: { id: d.obat_id, text: d.obat_nama },
                isi: d.qty_per_box, harga: parseFloat(d.harga), diskon: parseFloat(d.diskon), diskon_type: d.diskon_type
            });
            editMode = true;
            loadInfo([d.obat_id], true);
            dirty = false;
            $('#penawaranModal').modal('show');
        }).fail(function (xhr) { notify('error', 'Gagal mengambil data', ajaxErrorHtml(xhr)); });
    }

    // ---------- paste dari Excel ----------
    function parsePastedLine(line) {
        let cells = line.split('\t');
        if (cells.length === 1) cells = line.split(/\s*[;|]\s*/);
        cells = cells.map(function (c) { return c.trim(); }).filter(function (c) { return c !== ''; });
        if (!cells.length) return null;

        let name = null;
        const numbers = [];
        let diskon = null;
        let isiExplicit = null, isiUnit = null;
        cells.forEach(function (c) {
            const looksNumeric = /^(rp\.?\s*)?[\d.,]+\s*%?$/i.test(c);
            if (!looksNumeric && name !== null) {
                // "isi 10" always means isi per box; "10 box" / "100 tab" too (but not "60 ml", "500 mg")
                const explicit = c.match(/^isi\s*:?\s*(\d+)/i);
                const unit = c.match(/^(\d+)\s*(box|bx|pcs|tab|tablet|kaps|kapsul|kaplet|strip|btl|botol|amp|ampul|vial|sachet|sach|pack|tube)\.?$/i);
                if (explicit) { isiExplicit = parseInt(explicit[1], 10); return; }
                if (unit) { isiUnit = parseInt(unit[1], 10); return; }
            }
            if (!looksNumeric) {
                if (name === null) name = c; // first text cell; later text (satuan, keterangan) is ignored
                return;
            }
            const n = parseNum(c);
            if (isNaN(n)) return;
            if (c.indexOf('%') > -1) diskon = n;
            else numbers.push(n);
        });
        // Single cell with a trailing price: "PARACETAMOL 500 MG 35.000"
        if (name === null || !numbers.length) {
            if (cells.length === 1 && name) {
                const m = name.match(/^(.*?)[\s]+(rp\.?\s*)?([\d.,]{3,})$/i);
                if (m) { name = m[1]; numbers.push(parseNum(m[3])); }
            }
        }
        if (!name || !numbers.length) return null; // header or empty line
        const harga = Math.max.apply(null, numbers);
        const rest = numbers.filter(function (n, i) { return i !== numbers.indexOf(harga); });
        const isi = isiExplicit || isiUnit || rest.find(function (n) { return Number.isInteger(n) && n > 0 && n < harga; });
        return { name: name, harga: harga, isi: isi || null, diskon: diskon };
    }

    function processPaste() {
        const lines = ($('#pasteText').val() || '').split(/\r?\n/);
        const items = lines.map(parsePastedLine).filter(Boolean);
        if (!items.length) { notify('warning', 'Tidak ada baris yang terbaca', 'Pastikan tiap baris berisi nama obat dan harga.'); return; }
        const $btn = $('#btnPasteProses').prop('disabled', true);
        $.post(URLS.match, { names: items.map(function (it) { return it.name; }) })
            .done(function (res) {
                // Drop the empty starter row
                $('#pnTable tbody tr').each(function () { if (isEmptyRow($(this))) removeRow($(this)); });
                (res.data || []).forEach(function (m, i) {
                    const it = items[i];
                    addRow({
                        obat: m.match, sourceName: it.name, candidates: m.candidates,
                        isi: it.isi, harga: it.harga, diskon: it.diskon, diskon_type: it.diskon !== null ? 'percent' : undefined
                    });
                });
                $('#pasteText').val('');
                $('#pastePanel').addClass('d-none');
                dirty = true;
                loadInfo(allObatIds());
                const unmatched = (res.data || []).filter(function (m) { return !m.match; }).length;
                notify(unmatched ? 'info' : 'success', items.length + ' baris dibaca',
                    unmatched ? unmatched + ' obat belum yakin cocok — pilih dari saran di bawah nama, atau cari manual.' : 'Semua obat cocok. Periksa isi per box dan harga sebelum simpan.');
                renumber();
            })
            .fail(function (xhr) { notify('error', 'Gagal mencocokkan obat', ajaxErrorHtml(xhr)); })
            .always(function () { $btn.prop('disabled', false); });
    }

    // ---------- simpan ----------
    function collectRows() {
        const rows = [];
        const errors = [];
        const seen = {};
        $('#pnTable tbody tr').removeClass('row-error').each(function (i) {
            const $tr = $(this);
            if (isEmptyRow($tr)) return;
            const v = rowValues($tr);
            const no = i + 1;
            let bad = false;
            if (!v.obat_id) { errors.push('Baris ' + no + ': obat belum dipilih.'); bad = true; }
            else if (seen[v.obat_id]) { errors.push('Baris ' + no + ': obat sama dengan baris ' + seen[v.obat_id] + '.'); bad = true; }
            if (isNaN(v.isi)) { errors.push('Baris ' + no + ': isi per box belum diisi.'); bad = true; }
            if (isNaN(v.harga) || v.harga <= 0) { errors.push('Baris ' + no + ': harga belum diisi.'); bad = true; }
            if (v.diskon_type === 'percent' && v.diskon > 100) { errors.push('Baris ' + no + ': diskon lebih dari 100%.'); bad = true; }
            if ($tr.data('status') === 'konflik') { errors.push('Baris ' + no + ': obat ini sudah punya harga lain dari pemasok ini.'); bad = true; }
            if (v.obat_id && infoCache[v.obat_id] && !infoCache[v.obat_id].satuan) {
                errors.push('Baris ' + no + ': satuan stok obat belum diisi. Isi dulu di Master Obat.'); bad = true;
            }
            if (v.obat_id) seen[v.obat_id] = no;
            if (bad) $tr.addClass('row-error');
            rows.push({ id: v.id, obat_id: v.obat_id, harga: v.harga, qty_per_box: v.isi, diskon: v.diskon, diskon_type: v.diskon_type });
        });
        return { rows: rows, errors: errors };
    }

    function savePenawaran() {
        if (!$('#pnPemasok').val()) { notify('warning', 'Pilih pemasok terlebih dahulu'); $('#pnPemasok').select2('open'); return; }
        const c = collectRows();
        if (!c.rows.length) { notify('warning', 'Belum ada obat yang diisi'); return; }
        if (c.errors.length) { notify('warning', 'Lengkapi dulu', c.errors.slice(0, 8).map(escapeHtml).join('<br>') + (c.errors.length > 8 ? '<br>…' : '')); return; }

        const naik = $('#pnTable tbody tr').filter(function () { return $(this).data('status') === 'naik'; }).length;
        const go = naik ? confirmDialog('Simpan?', naik + ' obat harganya naik dibanding harga sebelumnya dari pemasok ini.', 'Ya, simpan') : Promise.resolve(true);
        go.then(function (ok) {
            if (!ok) return;
            const $btn = $('#btnSimpanPenawaran').prop('disabled', true);
            saving = true;
            $.ajax({
                url: URLS.save, type: 'POST', contentType: 'application/json',
                data: JSON.stringify({
                    pemasok_id: $('#pnPemasok').val(),
                    notes: $('#pnNotes').val(), rows: c.rows
                })
            })
                .done(function (res) {
                    dirty = false;
                    $('#penawaranModal').modal('hide');
                    table.ajax.reload(null, false);
                    notify('success', 'Tersimpan', escapeHtml(res.message || ''));
                })
                .fail(function (xhr) { notify('error', 'Gagal menyimpan', ajaxErrorHtml(xhr)); })
                .always(function () { $btn.prop('disabled', false); saving = false; });
        });
    }

    // ---------- quick add pemasok ----------
    function quickAdd(kind) {
        const $box = $('#' + kind + 'BaruBox');
        const $input = $('#' + kind + 'BaruNama');
        const $select = $('#pnPemasok');
        const nama = ($input.val() || '').trim();
        if (!nama) { $input.trigger('focus'); return; }
        $.post(URLS.pemasokCrud, { nama: nama })
            .done(function (res) {
                $select.append(new Option(res.data.nama, res.data.id, true, true)).trigger('change');
                $input.val('');
                $box.addClass('d-none');
            })
            .fail(function (xhr) {
                const ex = xhr.responseJSON && xhr.responseJSON.existing;
                if (ex) {
                    // Already exists: just use it
                    $select.append(new Option(ex.nama, ex.id, true, true)).trigger('change');
                    $input.val('');
                    $box.addClass('d-none');
                    notify('info', 'Sudah ada', escapeHtml(ex.nama) + ' sudah terdaftar dan dipilih.');
                    return;
                }
                notify('error', 'Gagal menambahkan', ajaxErrorHtml(xhr));
            });
    }

    // ---------- wiring ----------
    $(function () {
        $('#filterPemasok').select2(select2Ajax(URLS.pemasok, 'Semua pemasok'));
        $('#filterPrincipal').select2(select2Ajax(URLS.principal, 'Semua principal'));
        $('#filterObat').select2(select2Ajax(URLS.obat, 'Semua obat', null, { minimumInputLength: 2 }));
        initTable();

        // Same layout sync as Rawat Jalan: re-measure columns and pinned columns after draws, resizes and sidebar toggles
        function syncTableLayout() {
            try {
                table.columns.adjust();
                if (typeof table.fixedColumns === 'function') {
                    const fc = table.fixedColumns();
                    if (fc && typeof fc.relayout === 'function') fc.relayout();
                }
            } catch (e) {
                console.warn('Failed to sync Master Pembelian table layout', e);
            }
        }
        function queueTableLayoutSync() {
            syncTableLayout();
            setTimeout(syncTableLayout, 150);
            setTimeout(syncTableLayout, 320);
        }
        table.on('draw.dt', syncTableLayout);
        $(window).on('resize.mfTableLayout', queueTableLayoutSync);
        $(document).on('click.mfTableLayout', '.button-menu-mobile', queueTableLayoutSync);
        queueTableLayoutSync();

        $('#filterPemasok, #filterPrincipal, #filterObat').on('change', function () { table.ajax.reload(); });
        let searchTimer = null;
        $('#filterSearch').on('input search', function () {
            clearTimeout(searchTimer);
            const value = $(this).val();
            searchTimer = setTimeout(function () { table.search(value.trim()).draw(); }, 400);
        });
        $('#btnResetFilter').on('click', function () {
            $('#filterSearch').val('');
            $('#filterPemasok, #filterPrincipal, #filterObat').val(null).trigger('change.select2');
            table.search('').ajax.reload();
        });
        $('#btnDownloadPrincipal').on('click', function () {
            const principalId = $('#filterPrincipal').val();
            if (!principalId) { notify('warning', 'Pilih principal', 'Pilih principal di filter terlebih dahulu.'); return; }
            window.location.href = URLS.data + '?export=excel&principal_id=' + encodeURIComponent(principalId);
        });

        const $tbl = $('#master-faktur-table');
        $tbl.on('click', '.btn-edit', function (e) { e.stopPropagation(); openEdit($(this).data('id')); });
        $tbl.on('click', '.btn-delete', function (e) { e.stopPropagation(); deleteRow($(this).data('id')); });
        $tbl.on('click', '.btn-bandingkan', function (e) { e.stopPropagation(); openBandingkan($(this).data('obat-id'), $(this).data('obat-nama')); });

        // Bandingkan Obat modal
        $('#bdObatA').select2(select2Ajax(URLS.obat, 'Pilih obat', $('#bandingkanModal')));
        $('#bdObatB').select2(select2Ajax(URLS.obat, 'Pilih obat', $('#bandingkanModal')));
        $('#bdObatA, #bdObatB').on('change', loadBandingkan);
        $('#btnBandingkan').on('click', function () { openBandingkan(); });

        // Kelola Pemasok: renames show in the table; "N obat" filters the table by that pemasok
        const pemasokManager = SupplierManager.create({
            key: 'pemasok', label: 'Pemasok', url: URLS.pemasokCrud, canDelete: @json($canDelete),
            onChanged: function () { table.ajax.reload(null, false); },
            showObatTitle: 'Lihat harganya di tabel',
            onShowObat: function (row) {
                $('#filterPemasok').empty().append(new Option(row.nama, row.id, true, true)).trigger('change');
            }
        });
        $('#btnKelolaPemasok').on('click', function () { pemasokManager.open(); });
        // Old menu links (/erm/pemasok) redirect here with ?kelola=pemasok
        const params = new URLSearchParams(window.location.search);
        if (params.get('kelola') === 'pemasok') {
            params.delete('kelola');
            const query = params.toString();
            try { window.history.replaceState(null, '', window.location.pathname + (query ? '?' + query : '') + window.location.hash); } catch (e) { /* ignore */ }
            pemasokManager.open();
        }
        $('#btnBdTukar').on('click', function () {
            const a = $('#bdObatA').select2('data')[0], b = $('#bdObatB').select2('data')[0];
            $('#bdObatA').empty();
            $('#bdObatB').empty();
            if (b && b.id) $('#bdObatA').append(new Option(b.text, b.id, true, true));
            if (a && a.id) $('#bdObatB').append(new Option(a.text, a.id, true, true));
            $('#bdObatA, #bdObatB').trigger('change.select2');
            loadBandingkan();
        });
        $tbl.on('click', 'tbody tr', function () { const r = table.row(this).data(); if (r) openEdit(r.id); });

        // Penawaran modal
        $('#pnPemasok').select2(select2Ajax(URLS.pemasok, 'Pilih pemasok', $('#penawaranModal')));
        $('#btnInputPenawaran').on('click', openPenawaran);
        $('#btnTambahBaris').on('click', function () { const $tr = addRow(); $tr.find('.r-obat').select2('open'); });
        $('#btnPaste').on('click', function () { $('#pastePanel').toggleClass('d-none'); $('#pasteText').trigger('focus'); });
        $('#btnPasteBatal').on('click', function () { $('#pastePanel').addClass('d-none'); });
        $('#btnPasteProses').on('click', processPaste);
        $('#btnSimpanPenawaran').on('click', savePenawaran);

        // Pemasok change: current prices are per pemasok, reload them
        $('#pnPemasok').on('change', function () {
            Object.keys(infoCache).forEach(function (k) { delete infoCache[k]; });
            loadInfo(allObatIds(), true);
        });
        $('input[name="pnMode"], #pnPpn').on('change', function () { updateModeHint(); computeAll(); });
        updateModeHint();

        ['pemasok'].forEach(function (kind) {
            const K = kind.charAt(0).toUpperCase() + kind.slice(1);
            $('#btn' + K + 'Baru').on('click', function () { $('#' + kind + 'BaruBox').toggleClass('d-none'); $('#' + kind + 'BaruNama').trigger('focus'); });
            $('#btn' + K + 'BaruBatal').on('click', function () { $('#' + kind + 'BaruBox').addClass('d-none'); });
            $('#btn' + K + 'BaruSimpan').on('click', function () { quickAdd(kind); });
            $('#' + kind + 'BaruNama').on('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); quickAdd(kind); } });
        });

        const $grid = $('#pnTable');
        $grid.on('change', '.r-obat', function () { dirty = true; onObatPicked($(this).closest('tr')); });
        $grid.on('input change', '.pn-input, .r-dtype', function () { dirty = true; computeRow($(this).closest('tr')); });
        $grid.on('click', '.r-del', function () {
            removeRow($(this).closest('tr'));
            renumber();
            updateSummary();
            if (!$('#pnTable tbody tr').length) addRow();
        });
        $grid.on('click', '.candidate', function () {
            const $tr = $(this).closest('tr');
            $tr.find('.r-obat').append(new Option($(this).data('text'), $(this).data('id'), true, true)).trigger('change');
        });
        // Enter moves along the row; on the last row's diskon it adds a row
        $grid.on('keydown', '.pn-input', function (e) {
            if (e.key !== 'Enter') return;
            e.preventDefault();
            const $tr = $(this).closest('tr');
            const $inputs = $tr.find('.pn-input');
            const idx = $inputs.index(this);
            if (idx < $inputs.length - 1) { $inputs.eq(idx + 1).trigger('focus').trigger('select'); return; }
            const $next = $tr.next('tr');
            if ($next.length) { $next.find('.r-obat').select2('open'); return; }
            addRow().find('.r-obat').select2('open');
        });

        // Ask before closing with unsaved input
        $('#penawaranModal').on('hide.bs.modal', function (e) {
            if (!dirty || saving) return;
            e.preventDefault();
            confirmDialog('Tutup tanpa menyimpan?', 'Data yang sudah diisi akan hilang.', 'Ya, tutup').then(function (ok) {
                if (!ok) return;
                dirty = false;
                $('#penawaranModal').modal('hide');
            });
        });
        $('#penawaranModal').on('hidden.bs.modal', function () {
            $('#pnTable tbody select').each(function () { if ($(this).data('select2')) $(this).select2('destroy'); });
            resetPenawaran();
            // Back to the defaults for a new penawaran
            $('input[name="pnMode"][value="box"]').prop('checked', true).closest('label').addClass('active').siblings().removeClass('active');
            updateModeHint();
        });
    });
})();
</script>
@endsection
