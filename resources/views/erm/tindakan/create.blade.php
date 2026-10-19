@extends('layouts.erm.app')

@section('title', 'ERM | Tindakan & Inform Consent')
@section('navbar')
    @include('layouts.erm.navbardetail')
@endsection

@section('content')
@include('erm.partials.modal-alergipasien')

@include('erm.partials.modal-tindakan-informconsent')
@include('erm.partials.modal-tindakan-fotohasil')
@include('erm.partials.modal-tindakan-spk')

<div class="container-fluid">
    <div class="d-flex align-items-center mb-0 mt-2">
        <h3 class="mb-0 mr-2">Tindakan &amp;</h3>
        <h3 class="mb-0"><strong>Inform Consent</strong></h3>
    </div>
    <!-- Page-Title -->
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="row">
                    <div class="col">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:void(0);">ERM</a></li>
                            <li class="breadcrumb-item">Rawat Jalan</li>
                            <li class="breadcrumb-item active">Tindakan &amp; Inform Consent</li>
                        </ol>
                    </div><!--end col-->
                </div><!--end row-->
            </div><!--end page-title-box-->
        </div><!--end col-->
    </div><!--end row-->
    <!-- end page title end breadcrumb -->
    @include('erm.partials.card-identitaspasien')

    @include('erm.partials.form-theme')
    {{-- PDF preview modal styles (inform consent) --}}
    <link rel="stylesheet" href="{{ asset('css/erm-penunjang.css') }}?v={{ filemtime(public_path('css/erm-penunjang.css')) }}">
    <style>
        .tindakan-page .erm-section { padding-bottom: 14px; }
        .tindakan-page .erm-section-head .btn { margin-left: auto; border-radius: 9px; font-weight: 600; }
        .tindakan-page .table { margin-bottom: 0; }
        /* Price + action rows of the paket list line up */
        .price-block { width: 100%; }
        .price-block .price-row { display: flex; justify-content: space-between; align-items: center; gap: 8px; padding: 4px 0; }
        .price-block .price-row + .price-row { border-top: 1px dashed var(--sb-line, #e2e8f0); }
        .price-block .price-label { font-size: .75rem; font-weight: 700; color: var(--sb-muted, #8a94ad); }
        .price-block .price-value { font-weight: 700; text-align: right; }
        .price-block .price-value s { color: var(--sb-muted, #8a94ad); font-weight: 500; margin-right: 6px; }
        .price-block .btn { min-width: 96px; border-radius: 8px; font-weight: 600; }
        .tindakan-name-link { color: var(--sb-text-strong, #1e2538); font-weight: 700; text-decoration: none; }
        .tindakan-name-link:hover { color: var(--ef-accent-text); text-decoration: underline; }
        .tindakan-badge { display: inline-block; margin-left: 6px; padding: 2px 8px; border-radius: 999px; font-size: .68rem; font-weight: 800; vertical-align: middle; }
        .tindakan-badge.is-current { background: rgba(var(--ef-accent-rgb), .14); color: var(--ef-accent-text); }
        .tindakan-badge.is-new { background: rgba(220, 38, 38, .12); color: #dc2626; }
        .tindakan-badge.is-count { background: var(--sb-tile, #f1f5f9); color: var(--sb-text, #4b5671); }
        .tindakan-badge.is-paket { background: rgba(13, 148, 136, .12); color: #0d9488; }
        .tindakan-date { font-size: .78rem; color: var(--sb-muted, #8a94ad); margin-top: 2px; }
        .tindakan-spec { font-size: .75rem; color: var(--sb-muted, #8a94ad); }
        /* Riwayat: name, then one meta line (date + badges) */
        .tindakan-name { font-weight: 600; color: var(--sb-text-strong, #1e2538); line-height: 1.35; }
        .tindakan-meta { display: flex; flex-wrap: wrap; align-items: center; gap: 4px 6px; margin-top: 3px; }
        .tindakan-meta .tindakan-date { margin: 0; }
        .tindakan-meta .tindakan-badge { margin-left: 0; }
        .tindakan-dokter { font-size: .85rem; line-height: 1.35; }
        /* Missing inform consent: the badge itself is the button to fill it */
        .tindakan-ic-missing {
            display: inline-flex; align-items: center; gap: 5px; margin-top: 6px; padding: 3px 10px; border-radius: 999px;
            border: 1px solid rgba(220, 38, 38, .45); background: rgba(220, 38, 38, .1); color: #dc2626;
            font-size: .74rem; font-weight: 700; cursor: pointer; white-space: nowrap;
        }
        .tindakan-ic-missing:hover { background: #dc2626; color: #fff; }
        html.theme-dark .tindakan-ic-missing { color: #fca5a5; }
        html.theme-dark .tindakan-ic-missing:hover { color: #fff; }
        /* Aksi: same-size icon buttons in one row */
        .tindakan-actions { display: flex; flex-wrap: nowrap; gap: 4px; }
        .tindakan-actions .btn.is-locked { opacity: .55; cursor: not-allowed; }
        /* Riwayat: kunjungan ini / semua */
        .tindakan-scope { display: inline-flex; gap: 4px; padding: 3px; margin: 0 0 10px; border-radius: 10px; background: var(--sb-tile, #f1f5f9); border: 1px solid var(--sb-line, #e2e8f0); }
        .tindakan-scope-btn { border: 0; background: transparent; border-radius: 8px; padding: 4px 12px; font-size: .8rem; font-weight: 700; color: var(--sb-text, #4b5671); cursor: pointer; }
        .tindakan-scope-btn.active { background: var(--ef-accent); color: #fff; }
        /* Riwayat badges: paket session, SPK, payment */
        .tindakan-badge.is-gratis { background: rgba(13, 148, 136, .12); color: #0d9488; }
        .tindakan-badge.is-spk-berjalan { background: rgba(245, 158, 11, .16); color: #b45309; cursor: pointer; }
        .tindakan-badge.is-spk-selesai { background: rgba(22, 163, 74, .14); color: #15803d; cursor: pointer; }
        .tindakan-badge.is-spk-dibuat { background: rgba(148, 163, 184, .2); color: var(--sb-text, #4b5671); cursor: pointer; }
        .tindakan-badge.is-paid { background: rgba(22, 163, 74, .14); color: #15803d; }
        .tindakan-badge.is-issued, .tindakan-badge.is-partial { background: rgba(14, 165, 233, .14); color: #0369a1; }
        .tindakan-badge.is-unbilled { background: rgba(148, 163, 184, .18); color: var(--sb-muted, #8a94ad); }
        html.theme-dark .tindakan-badge.is-spk-berjalan { color: #fcd34d; }
        html.theme-dark .tindakan-badge.is-spk-selesai, html.theme-dark .tindakan-badge.is-paid { color: #86efac; }
        html.theme-dark .tindakan-badge.is-issued, html.theme-dark .tindakan-badge.is-partial { color: #7dd3fc; }
        html.theme-dark .tindakan-badge.is-gratis { color: #5eead4; }
        .tindakan-actions .btn { width: 30px; height: 30px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; }
        /* DataTables controls keep their normal look inside the themed form */
        .tindakan-page .dataTables_wrapper label { text-transform: none; letter-spacing: normal; font-size: .82rem; font-weight: 500; color: var(--sb-muted, #8a94ad); }
        .tindakan-page .dataTables_length select.form-control { min-width: 72px; min-height: 32px; height: 32px; padding: 2px 24px 2px 10px; display: inline-block; width: auto; }
        .tindakan-page .dataTables_filter input.form-control { min-height: 32px; height: 32px; }
        .tindakan-page table.dataTable thead th { white-space: nowrap; }
        .ic-warning-banner { display: flex; align-items: center; gap: 12px; margin-bottom: 14px; padding: 12px 16px; border-radius: 12px; border: 1px solid rgba(220, 38, 38, .35); background: rgba(220, 38, 38, .08); color: #b91c1c; }
        html.theme-dark .ic-warning-banner { color: #fca5a5; }
        .ic-warning-banner i { font-size: 1.2rem; }
    </style>

    {{-- Filled by the Riwayat Tindakan table (pending_consent of this visit) --}}
    <div id="icWarningBanner" class="ic-warning-banner" style="display:none;">
        <i class="fas fa-exclamation-triangle"></i>
        <div><strong id="icWarningCount"></strong> di kunjungan ini belum diisi inform consent. Klik <strong>Isi Inform Consent</strong> di Riwayat Tindakan.</div>
    </div>

    <div class="row tindakan-page erm-form" style="{{ \App\Support\ClinicTheme::cssVars('ef') }}">
        <!-- Left: Daftar Paket Tindakan -->
        <div class="col-xl-6 mb-3">
            <section class="erm-section h-100">
                <div class="erm-section-head">
                    <span class="erm-section-num">1</span>
                    <h4 class="erm-section-title">Daftar Tindakan<span class="erm-section-sub">Klik nama untuk melihat isi paket</span></h4>
                </div>
                <div class="table-responsive">
                    <table id="tindakanTable" class="table w-100">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>Harga</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </section>
        </div>

        <!-- Right: Riwayat Tindakan Pasien -->
        <div class="col-xl-6 mb-3">
            <section class="erm-section h-100">
                <div class="erm-section-head">
                    <span class="erm-section-num">2</span>
                    <h4 class="erm-section-title">Riwayat Tindakan<span class="erm-section-sub" id="historyScopeSub">Semua kunjungan pasien</span></h4>
                    <a href="/erm/tindakan/history/{{ $visitation->id }}/print-detail" target="_blank" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-print mr-1"></i>Print Detail
                    </a>
                </div>
                <div class="tindakan-scope" role="group" aria-label="Tampilkan riwayat">
                    <button type="button" class="tindakan-scope-btn" data-scope="current">Kunjungan ini</button>
                    <button type="button" class="tindakan-scope-btn active" data-scope="all">Semua kunjungan</button>
                </div>
                <div class="table-responsive">
                    <table id="historyTindakanTable" class="table w-100">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Tindakan</th>
                                <th>Dokter</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </section>
        </div>
    </div>
</div>

<!-- Detail riwayat tindakan: obat / BHP yang dipakai -->
<div class="modal fade" id="modalRiwayatDetail" tabindex="-1" aria-labelledby="modalRiwayatDetailLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalRiwayatDetailLabel">Obat &amp; BHP Tindakan</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="riwayatDetailContent"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Isi paket tindakan modal -->
<div class="modal fade" id="modalSopDetail" tabindex="-1" aria-labelledby="modalSopDetailLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalSopDetailLabel">Isi Paket Tindakan</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <table class="table table-bordered" id="sopTable">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Kode</th>
                            <th>Nama</th>
                            <th>Obat (Bundled)</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Print Select Beautician Modal -->
<div class="modal fade" id="modalSelectBeautician" tabindex="-1" aria-labelledby="modalSelectBeauticianLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalSelectBeauticianLabel">Pilih Beautician untuk Print</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="selectBeautician">Beautician</label>
                    <select id="selectBeautician" class="form-control">
                        <option value="">-- Pilih Beautician --</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" id="confirmPrintWithBeautician" class="btn btn-primary">Print</button>
            </div>
        </div>
    </div>
</div>
<div class="erm-form" style="{{ \App\Support\ClinicTheme::cssVars('ef') }}">
    @include('erm.partials.modal-pdf-preview')
</div>
@endsection
@section('scripts')
<script src="{{ asset('js/erm-pdf-preview.js') }}?v={{ filemtime(public_path('js/erm-pdf-preview.js')) }}"></script>
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
{{-- Loaded up front so the QR of dokter / perawat is ready when the inform consent is saved --}}
<script src="https://cdn.jsdelivr.net/npm/qrious@4.0.2/dist/qrious.min.js"></script>

<script>
    // QR data URI in the browser (empty when the library failed to load: the server then makes it)
    function generateQrDataUriBrowser(text, size = 200) {
        try {
            if (!window.QRious) return null;
            return new QRious({ value: text || '', size: size, level: 'H' }).toDataURL('image/jpeg');
        } catch (e) {
            console.error('QR generation failed', e);
            return null;
        }
    }

    $(document).ready(function () {
        $.ajaxSetup({
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
        });
        const spesialisasiId = @json($spesialisasiId);
        const currentVisitationId = @json($visitation->id);
        let signaturePads = {};
        let savingInformConsent = false;

        function escapeHtml(value) {
            return $('<div>').text(value == null ? '' : String(value)).html();
        }

        function formatRupiah(value) {
            return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value);
        }

        // Today in the browser's own timezone (toISOString is UTC: before 07.00 WIB it gives yesterday)
        function todayLocal() {
            const d = new Date();
            const pad = function (n) { return String(n).padStart(2, '0'); };
            return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
        }

        function hasPaketVisit(row) {
            return typeof row.harga_paket_visit !== 'undefined' && row.harga_paket_visit !== null && String(row.harga_paket_visit).trim() !== '';
        }

        // Open multi visit package of this patient for the row ({used, total, sisa}) or null
        function openPaket(row) {
            if (row.multi_used === null || row.multi_total === null || typeof row.multi_used === 'undefined') return null;
            const used = parseInt(row.multi_used, 10), total = parseInt(row.multi_total, 10);
            return used < total ? { used: used, total: total, sisa: total - used } : null;
        }

        function errorMessage(xhr, fallback) {
            return (xhr && xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : fallback;
        }

        // Daftar paket tindakan
        $('#tindakanTable').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            pageLength: 10,
            ajax: { url: `/erm/tindakan/data/${spesialisasiId}`, data: { visitation_id: currentVisitationId } },
            columns: [
                { data: 'nama', name: 'nama', render: function (data, type, row) {
                        let badges = '';
                        const createdDate = row.created_at ? new Date(row.created_at) : null;
                        if (createdDate && !isNaN(createdDate.getTime()) && (Date.now() - createdDate) / 86400000 <= 30) {
                            badges += '<span class="tindakan-badge is-new">BARU</span>';
                        }
                        // Remaining sessions of this patient's open multi visit package
                        let usage = '';
                        if (row.multi_used !== null && row.multi_total !== null && typeof row.multi_used !== 'undefined') {
                            usage = `<div class="mt-1"><span class="tindakan-badge is-paket ml-0">Paket terpakai ${row.multi_used}/${row.multi_total}</span></div>`;
                        }
                        return `<a href="#" class="tindakan-name-link" data-id="${row.id}">${escapeHtml(data)}</a>${badges}${usage}`;
                    }
                },
                {
                    data: 'harga',
                    name: 'harga',
                    render: function (data, type, row) {
                        let normal = data ? formatRupiah(data) : '-';
                        if (row.harga_diskon && row.diskon_active) {
                            normal = `<s>${normal}</s>${formatRupiah(row.harga_diskon)}`;
                        }
                        let out = `<div class="price-block"><div class="price-row"><span class="price-label">Normal</span><span class="price-value">${normal}</span></div>`;
                        const paket = openPaket(row);
                        if (paket) {
                            out += `<div class="price-row"><span class="price-label">Sisa Paket</span><span class="price-value text-success">${paket.sisa} sesi &middot; Rp0</span></div>`;
                        } else if (hasPaketVisit(row)) {
                            out += `<div class="price-row"><span class="price-label">${row.multi_visit_total || 3}x Visit</span><span class="price-value">${formatRupiah(row.harga_paket_visit)}</span></div>`;
                        }
                        return out + '</div>';
                    }
                },
                {
                    data: null,
                    orderable: false,
                    searchable: false,
                    render: function (data, type, row) {
                        const nama = escapeHtml(row.nama);
                        const paket = openPaket(row);
                        let out = '<div class="price-block">';
                        if (paket) {
                            // Open package: the next session is the main choice, Normal still possible after a warning
                            const sesi = `${paket.used + 1}/${paket.total}`;
                            out += `<div class="price-row"><button class="btn btn-outline-secondary btn-sm buat-tindakan" title="Tambah Tindakan (Normal) - pasien masih punya sisa paket" data-id="${row.id}" data-nama="${nama}" data-harga-label="Normal" data-harga-type="normal" data-paket-used="${paket.used}" data-paket-total="${paket.total}" data-paket-sisa="${paket.sisa}"><i class="fas fa-plus mr-1"></i>Normal</button></div>`;
                            out += `<div class="price-row"><button class="btn btn-success btn-sm buat-tindakan" title="Pakai sesi paket ${sesi} (Rp0)" data-id="${row.id}" data-nama="${nama}" data-harga-label="Sesi Paket ${sesi} (Rp0)" data-harga-type="3x"><i class="fas fa-check mr-1"></i>Pakai Sesi ${sesi}</button></div>`;
                        } else {
                            out += `<div class="price-row"><button class="btn btn-success btn-sm buat-tindakan" title="Tambah Tindakan (Normal)" data-id="${row.id}" data-nama="${nama}" data-harga-label="Normal" data-harga-type="normal"><i class="fas fa-plus mr-1"></i>Normal</button></div>`;
                        }
                        if (!paket && hasPaketVisit(row)) {
                            const label = (row.multi_visit_total || 3) + 'x Visit';
                            out += `<div class="price-row"><button class="btn btn-primary btn-sm buat-tindakan" title="Tambah Tindakan (${label})" data-id="${row.id}" data-nama="${nama}" data-harga-label="${label}" data-harga-type="3x"><i class="fas fa-plus mr-1"></i>${label}</button></div>`;
                        }
                        return out + '</div>';
                    }
                },
            ],
        });

        // Riwayat tindakan pasien: one row per riwayat, newest first (order comes from the server)
        let historyScope = 'all';
        const SPK_LABEL = { dibuat: 'SPK dibuat', berjalan: 'SPK berjalan', selesai: 'SPK selesai' };
        $('#historyTindakanTable').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            ordering: false,
            pageLength: 5,
            ajax: {
                url: `/erm/tindakan/history/${currentVisitationId}`,
                data: function (d) { d.scope = historyScope; }
            },
            columns: [
                { data: 'tanggal', name: 'tanggal', visible: false },
                { data: 'tindakan', name: 'tindakan', render: function (data, type, row) {
                        let meta = row.tanggal ? `<span class="tindakan-date">${escapeHtml(row.tanggal)}</span>` : '';
                        if (row.current) {
                            meta += '<span class="tindakan-badge is-current">Kunjungan ini</span>';
                        }
                        // Multi visit package: session 1 carries the package price, the next ones use the quota
                        if (row.paket && row.paket.sesi) {
                            meta += row.paket.sesi > 1
                                ? `<span class="tindakan-badge is-gratis" title="Memakai kuota paket">Paket ${row.paket.sesi}/${row.paket.total} &middot; kuota</span>`
                                : `<span class="tindakan-badge is-paket" title="Sesi pertama, harga paket ditagih">Paket 1/${row.paket.total}</span>`;
                        }
                        if (row.spk) {
                            meta += `<span class="tindakan-badge is-spk-${row.spk} spk-btn" role="button" title="Lihat SPK" data-riwayat-id="${row.id}">${SPK_LABEL[row.spk]}</span>`;
                        }
                        const bayarClass = { paid: 'is-paid', partial: 'is-partial', issued: 'is-issued' }[row.bayar] || 'is-unbilled';
                        meta += `<span class="tindakan-badge ${bayarClass}">${escapeHtml(row.bayar_label)}</span>`;
                        // Missing inform consent: the warning is the button to fill it
                        const icMissing = (row.current && row.pending_consent_id)
                            ? `<div><span class="tindakan-ic-missing isi-inform-consent-btn" role="button" title="Klik untuk mengisi inform consent" data-riwayat-id="${row.pending_consent_id}" data-tindakan-id="${row.tindakan_id}"><i class="fas fa-exclamation-triangle"></i>Inform consent belum diisi &middot; Isi</span></div>`
                            : '';
                        return `<div class="tindakan-name">${escapeHtml(data)}</div><div class="tindakan-meta">${meta}</div>${icMissing}`;
                    }
                },
                { data: 'dokter', name: 'dokter', render: function (data, type, row) {
                        const spec = row.spesialisasi && row.spesialisasi !== '-' ? `<div class="tindakan-spec">${escapeHtml(row.spesialisasi)}</div>` : '';
                        return `<div class="tindakan-dokter">${escapeHtml(data)}</div>${spec}`;
                    }
                },
                {
                    data: null,
                    orderable: false,
                    searchable: false,
                    render: function (data, type, row) {
                        let group = '<div class="tindakan-actions">';
                        if (row.inform_consent) {
                            const ic = row.inform_consent;
                            const hasFoto = (ic.before_image_path || '').trim() !== '' && (ic.after_image_path || '').trim() !== '';
                            const icUrl = '/storage/' + String(ic.file_path || '').split('/').map(encodeURIComponent).join('/');
                            // PDF: preview in the page; anything else opens as it is
                            group += /\.pdf$/i.test(ic.file_path || '')
                                ? `<a href="${icUrl}" class="btn btn-info btn-sm btn-pdf-preview" data-title="Inform Consent &middot; ${escapeHtml(row.tindakan)}" title="Lihat Inform Consent"><i class="fas fa-file-alt"></i></a>`
                                : `<a href="${icUrl}" target="_blank" class="btn btn-info btn-sm" title="Lihat Inform Consent"><i class="fas fa-file-alt"></i></a>`;
                            if (row.can_refill) {
                                group += `<button class="btn btn-outline-warning btn-sm isi-ulang-ic-btn" title="Isi ulang inform consent" data-riwayat-id="${row.id}" data-tindakan-id="${row.tindakan_id}"><i class="fas fa-redo"></i></button>`;
                            }
                            group += `<button class="btn ${hasFoto ? 'btn-primary' : 'btn-success'} btn-sm foto-hasil-btn" title="${hasFoto ? 'Lihat Foto' : 'Upload Foto'}" data-id="${ic.id}" data-before="${escapeHtml(ic.before_image_path || '')}" data-after="${escapeHtml(ic.after_image_path || '')}"><i class="fas ${hasFoto ? 'fa-eye' : 'fa-upload'}"></i></button>`;
                        }
                        if (row.visitation_id) {
                            group += `<a href="/erm/tindakan/history/${row.visitation_id}/print-detail" target="_blank" class="btn btn-outline-primary btn-sm" title="Print Detail"><i class="fas fa-print"></i></a>`;
                        }
                        // Obat / BHP and Batalkan only for this visit's tindakan
                        if (row.current) {
                            group += `<button class="btn btn-outline-secondary btn-sm detail-riwayat-btn" title="Obat & BHP" data-id="${row.id}"><i class="fas fa-pills"></i></button>`;
                            // On an invoice / paid: cancelled at the kasir (the server refuses it too)
                            group += row.locked
                                ? `<button class="btn btn-outline-secondary btn-sm is-locked" title="Sudah masuk invoice (${escapeHtml(row.bayar_label)}). Pembatalan lewat kasir." disabled><i class="fas fa-lock"></i></button>`
                                : `<button class="btn btn-outline-danger btn-sm batalkan-tindakan-btn" title="Batalkan" data-id="${row.id}"><i class="fas fa-ban"></i></button>`;
                        }
                        return group + '</div>';
                    }
                }
            ]
        });

        $('.tindakan-scope-btn').on('click', function () {
            $('.tindakan-scope-btn').removeClass('active');
            $(this).addClass('active');
            historyScope = $(this).data('scope');
            $('#historyScopeSub').text(historyScope === 'current' ? 'Kunjungan ini' : 'Semua kunjungan pasien');
            $('#historyTindakanTable').DataTable().ajax.reload();
        });

        // Warning banner: tindakan of this visit still without inform consent
        $('#historyTindakanTable').on('xhr.dt', function (e, settings, json) {
            const pending = parseInt(json && json.pending_consent ? json.pending_consent : 0);
            $('#icWarningCount').text(pending + ' tindakan');
            $('#icWarningBanner').toggle(pending > 0);
        });

        // Foto hasil
        $(document).on('click', '.foto-hasil-btn', function () {
            const id = $(this).data('id');
            const beforePath = $(this).data('before');
            const afterPath = $(this).data('after');
            $('#informConsentId').val(id);
            $('#beforeImage').val('');
            $('#afterImage').val('');
            $('#beforePreview').hide();
            $('#afterPreview').hide();
            $('#allowPost').prop('checked', false);
            if (beforePath) {
                $('#beforePreview').attr('src', `/storage/${beforePath}`).show();
            }
            if (afterPath) {
                $('#afterPreview').attr('src', `/storage/${afterPath}`).show();
            }
            $.get(`/erm/inform-consent/${id}/get`, function (response) {
                if (response && typeof response.allow_post !== 'undefined') {
                    $('#allowPost').prop('checked', !!response.allow_post);
                }
            });
            $('#modalFotoHasil').modal('show');
        });

        // Print detail: choose the beautician, then the browser's print dialog opens right away (no preview, no new tab)
        let printTargetUrl = '';
        let beauticiansLoading = null;   // fetched once per page
        let lastBeautician = '';         // the next print starts from the last choice

        function loadBeauticians() {
            if (!beauticiansLoading) {
                beauticiansLoading = $.get('/erm/beauticians').then(function (res) {
                    const data = (res && res.data) ? res.data : res;
                    $('#selectBeautician').html('<option value="">-- Pilih Beautician --</option>'
                        + (Array.isArray(data) ? data : []).map(function (u) {
                            return `<option value="${escapeHtml(u.id)}">${escapeHtml(u.name)}</option>`;
                        }).join(''));
                }, function () {
                    beauticiansLoading = null; // try again next time
                });
            }
            return beauticiansLoading;
        }
        loadBeauticians();

        $(document).on('click', 'a[href*="/print-detail"]', function (e) {
            e.preventDefault();
            printTargetUrl = $(this).attr('href');
            $('#modalSelectBeautician').modal('show');
            loadBeauticians() && loadBeauticians().then(function () {
                $('#selectBeautician').val(lastBeautician);
            });
        });

        $(document).on('click', '#confirmPrintWithBeautician', function () {
            const beautId = $('#selectBeautician').val();
            if (!beautId) {
                Swal.fire('Pilih Beautician', 'Silakan pilih beautician terlebih dahulu.', 'warning');
                return;
            }
            if (!printTargetUrl) return;
            lastBeautician = beautId;
            const url = printTargetUrl + (printTargetUrl.indexOf('?') === -1 ? '?' : '&')
                + 'beautician_id=' + encodeURIComponent(beautId) + '&embed=1';
            // Print once the chooser is gone, so it is not behind the print dialog
            $('#modalSelectBeautician').one('hidden.bs.modal', function () {
                window.ErmPdfPreview.printHtml(url);
            }).modal('hide');
        });

        // Preview images before upload
        function previewImage(input, previewSelector) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    $(previewSelector).attr('src', e.target.result).show();
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
        $('#beforeImage').change(function () { previewImage(this, '#beforePreview'); });
        $('#afterImage').change(function () { previewImage(this, '#afterPreview'); });

        $('#saveFotoHasil').click(function () {
            const id = $('#informConsentId').val();
            const formData = new FormData($('#fotoHasilForm')[0]);
            Swal.fire({ title: 'Mengunggah...', text: 'Mohon tunggu, foto sedang diunggah.', icon: 'info', allowOutsideClick: false, allowEscapeKey: false, showConfirmButton: false });
            $.ajax({
                url: `/erm/tindakan/upload-foto/${id}`,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    if (response.success) {
                        Swal.fire('Berhasil', 'Foto hasil berhasil diunggah.', 'success');
                        $('#modalFotoHasil').modal('hide');
                        $('#historyTindakanTable').DataTable().ajax.reload();
                    } else {
                        Swal.fire('Gagal', 'Foto gagal diunggah.', 'error');
                    }
                },
                error: function (xhr) {
                    Swal.fire('Gagal', errorMessage(xhr, 'Foto gagal diunggah. Silakan coba lagi.'), 'error');
                }
            });
        });

        // Signature pads of the inform consent form (re-created for every newly loaded form)
        function initializeSignaturePads(step) {
            const patientCanvas = $('#signatureCanvas').get(0);
            const witnessCanvas = $('#witnessSignatureCanvas').get(0);
            if (!patientCanvas || !witnessCanvas) {
                return false;
            }
            if (!signaturePads[step]) {
                const scale = window.devicePixelRatio || 1;
                [patientCanvas, witnessCanvas].forEach(function (canvas) {
                    canvas.width = canvas.parentElement.clientWidth * scale;
                    canvas.height = canvas.parentElement.clientHeight * scale;
                    canvas.getContext('2d').scale(scale, scale);
                });
                // White background: the signatures are sent as JPEG (small, and fast for the PDF)
                const padOptions = { backgroundColor: 'rgb(255, 255, 255)' };
                signaturePads[step] = {
                    patient: new SignaturePad(patientCanvas, padOptions),
                    witness: new SignaturePad(witnessCanvas, padOptions)
                };
            }
            return true;
        }

        $(document).on('click', '#clearSignature', function () {
            if (signaturePads[1] && signaturePads[1].patient) signaturePads[1].patient.clear();
        });
        $(document).on('click', '#clearWitnessSignature', function () {
            if (signaturePads[1] && signaturePads[1].witness) signaturePads[1].witness.clear();
        });

        // "Perbesar": sign on the whole screen, then the drawing (cropped to what was drawn) goes into that pad
        let overlayPad = null;
        let overlayTarget = null;

        function openSignOverlay(which) {
            if (!signaturePads[1] || !signaturePads[1][which]) return;
            overlayTarget = which;
            $('#icSignOverlayTitle').text(which === 'patient' ? 'Tanda Tangan Pasien/Wali' : 'Tanda Tangan Saksi');
            $('#icSignOverlay').addClass('show');
            const canvas = document.getElementById('icSignOverlayCanvas');
            const ratio = window.devicePixelRatio || 1;
            canvas.width = canvas.offsetWidth * ratio;
            canvas.height = canvas.offsetHeight * ratio;
            canvas.getContext('2d').scale(ratio, ratio);
            overlayPad = overlayPad || new SignaturePad(canvas, { backgroundColor: 'rgb(255, 255, 255)', minWidth: 1.5, maxWidth: 4 });
            overlayPad.clear();
        }

        function closeSignOverlay() {
            $('#icSignOverlay').removeClass('show');
            overlayTarget = null;
        }

        $(document).on('click', '.ic-sign-expand', function () { openSignOverlay($(this).data('pad')); });
        $('#icSignOverlayClear').on('click', function () { if (overlayPad) overlayPad.clear(); });
        $('#icSignOverlayCancel').on('click', closeSignOverlay);
        $(document).on('keydown', function (e) { if (e.key === 'Escape' && overlayTarget) closeSignOverlay(); });

        $('#icSignOverlayUse').on('click', function () {
            const target = overlayTarget && signaturePads[1] ? signaturePads[1][overlayTarget] : null;
            if (!target || !overlayPad || overlayPad.isEmpty()) {
                closeSignOverlay();
                return;
            }
            // Bounding box of the strokes (CSS px), with a little margin
            let minX = Infinity, minY = Infinity, maxX = -Infinity, maxY = -Infinity;
            overlayPad.toData().forEach(function (stroke) {
                stroke.points.forEach(function (p) {
                    minX = Math.min(minX, p.x); minY = Math.min(minY, p.y);
                    maxX = Math.max(maxX, p.x); maxY = Math.max(maxY, p.y);
                });
            });
            const src = overlayPad.canvas;
            const ratio = window.devicePixelRatio || 1;
            const pad = 12;
            minX = Math.max(0, minX - pad); minY = Math.max(0, minY - pad);
            maxX = Math.min(src.offsetWidth, maxX + pad); maxY = Math.min(src.offsetHeight, maxY + pad);
            const cropW = Math.max(1, maxX - minX), cropH = Math.max(1, maxY - minY);
            const crop = document.createElement('canvas');
            crop.width = cropW * ratio;
            crop.height = cropH * ratio;
            crop.getContext('2d').drawImage(src, minX * ratio, minY * ratio, cropW * ratio, cropH * ratio, 0, 0, crop.width, crop.height);

            // Fit into the small pad, keeping the proportions, centred
            const tw = target.canvas.offsetWidth, th = target.canvas.offsetHeight;
            const scale = Math.min(tw / cropW, th / cropH);
            const w = cropW * scale, h = cropH * scale;
            target.clear();
            target.fromDataURL(crop.toDataURL('image/png'), { ratio: ratio, width: w, height: h, xOffset: (tw - w) / 2, yOffset: (th - h) / 2 });
            closeSignOverlay();
        });

        $(document).on('shown.bs.modal', '#modalInformConsent', function () {
            // Small delay so the form has its final size before the canvas is measured
            setTimeout(function () { initializeSignaturePads(1); }, 300);
        });

        // Inform consent form of an already added tindakan (riwayat): the save only adds its consent PDF
        let consentRiwayatId = null;
        let consentReplace = false;

        function loadInformConsentModal(tindakanId, riwayatId, replace) {
            consentRiwayatId = riwayatId;
            consentReplace = !!replace;
            signaturePads = {};
            $('#modalInformConsent .modal-title').text(consentReplace ? 'Isi Ulang Inform Consent' : 'Inform Consent Tindakan');
            $.get(`/erm/tindakan/inform-consent/${tindakanId}?visitation_id=${currentVisitationId}`)
                .done(function (html) {
                    $('#modalInformConsentBody').html(html);
                    $('#modalInformConsentBody').append('<div class="text-center mt-4"><button type="button" id="previewInformConsent" class="btn btn-outline-primary mr-2"><i class="fas fa-eye mr-1"></i>Preview</button><button id="saveInformConsent" class="btn btn-success">' + (consentReplace ? 'Simpan &amp; Ganti Inform Consent' : 'Simpan Inform Consent') + '</button></div>');
                    // A 'Perbesar' button next to each signature's Clear
                    $('#clearSignature').after(' <button type="button" class="btn btn-sm btn-outline-primary mt-2 ic-sign-expand" data-pad="patient"><i class="fas fa-expand mr-1"></i>Perbesar</button>');
                    $('#clearWitnessSignature').after(' <button type="button" class="btn btn-sm btn-outline-primary mt-2 ic-sign-expand" data-pad="witness"><i class="fas fa-expand mr-1"></i>Perbesar</button>');
                    $('#modalInformConsent').modal('show');
                })
                .fail(function () {
                    Swal.fire('Gagal', 'Form inform consent gagal dimuat.', 'error');
                });
        }

        function reloadTindakanTables() {
            $('#historyTindakanTable').DataTable().ajax.reload();
            $('#tindakanTable').DataTable().ajax.reload(null, false);
        }

        // Add the tindakan right away (riwayat + billing); its inform consent can be filled later
        function addTindakan(tindakanId, hargaType, reuseInformConsent) {
            const formData = new FormData();
            formData.append('tindakan_id', tindakanId);
            formData.append('visitation_id', currentVisitationId);
            formData.append('tanggal', todayLocal());
            formData.append('harga_type', hargaType || 'normal');
            if (reuseInformConsent) {
                formData.append('reuse_existing_inform_consent', '1');
            }

            Swal.fire({ title: 'Menyimpan...', text: 'Mohon tunggu, data sedang disimpan.', icon: 'info', allowOutsideClick: false, allowEscapeKey: false, showConfirmButton: false });
            $.ajax({
                url: '/erm/tindakan/inform-consent/save',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    if (!response.success) {
                        Swal.fire('Gagal', 'Tindakan gagal disimpan.', 'error');
                        return;
                    }
                    reloadTindakanTables();
                    const riwayatId = response.riwayatTindakan ? response.riwayatTindakan.id : null;
                    if (response.informConsent || !riwayatId) {
                        Swal.fire('Berhasil', 'Tindakan berhasil ditambahkan.', 'success');
                        return;
                    }
                    Swal.fire({
                        title: 'Tindakan ditambahkan',
                        text: 'Inform consent belum diisi. Isi sekarang?',
                        icon: 'success',
                        showCancelButton: true,
                        confirmButtonText: 'Isi Sekarang',
                        cancelButtonText: 'Nanti'
                    }).then(function (result) {
                        if (result.isConfirmed || result.value) {
                            loadInformConsentModal(tindakanId, riwayatId);
                        }
                    });
                },
                error: function (xhr) {
                    Swal.fire('Gagal', errorMessage(xhr, 'Tindakan gagal disimpan. Silakan coba lagi.'), 'error');
                }
            });
        }

        $(document).on('click', '.buat-tindakan', function () {
            const id = $(this).data('id');
            const nama = $(this).data('nama') || 'tindakan ini';
            const hargaType = $(this).data('harga-type') || 'normal';
            const hargaLabel = $(this).data('harga-label') || 'Normal';
            const paketSisa = parseInt($(this).data('paket-sisa'), 10);

            // Normal while the patient still has package sessions: confirm the full charge first
            if (hargaType === 'normal' && paketSisa > 0) {
                Swal.fire({
                    title: 'Pasien masih punya sisa paket',
                    html: `<b>${escapeHtml(nama)}</b><br>Sisa <b>${paketSisa} sesi</b> (terpakai ${parseInt($(this).data('paket-used'), 10)}/${parseInt($(this).data('paket-total'), 10)}).<br>Yakin tagih <b>harga normal</b>?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, tagih normal',
                    cancelButtonText: 'Batal',
                    confirmButtonColor: '#dc2626'
                }).then(function (result) {
                    if (result.isConfirmed || result.value) {
                        confirmAddTindakan(id, nama, hargaType, hargaLabel);
                    }
                });
                return;
            }
            confirmAddTindakan(id, nama, hargaType, hargaLabel);
        });

        function confirmAddTindakan(id, nama, hargaType, hargaLabel) {
            // The same tindakan again in this visit reuses its inform consent
            $.get(`/erm/tindakan/${id}/exists-in-visitation`, { visitation_id: currentVisitationId })
                .always(function (check) {
                    const exists = !!(check && check.success && check.exists);
                    Swal.fire({
                        title: exists ? 'Tindakan ini sudah ditambahkan' : 'Tambah Tindakan?',
                        html: `<b>${escapeHtml(nama)}</b> &middot; ${escapeHtml(hargaLabel)}`
                            + (exists ? '<br>Tambahkan lagi?' : '<br><small>Inform consent bisa diisi sekarang atau nanti.</small>'),
                        icon: exists ? 'warning' : 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, tambahkan',
                        cancelButtonText: 'Batal'
                    }).then(function (result) {
                        if (result.isConfirmed || result.value) {
                            addTindakan(id, hargaType, exists);
                        }
                    });
                });
        }

        $(document).on('click', '.isi-inform-consent-btn', function () {
            loadInformConsentModal($(this).data('tindakan-id'), $(this).data('riwayat-id'));
        });

        // Isi ulang: replaces the signed inform consent while the tindakan is not started nor billed
        $(document).on('click', '.isi-ulang-ic-btn', function () {
            const tindakanId = $(this).data('tindakan-id');
            const riwayatId = $(this).data('riwayat-id');
            Swal.fire({
                title: 'Isi ulang inform consent?',
                html: 'Inform consent yang lama diganti dengan yang baru.<br><small>Foto hasil tetap disimpan.</small>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, isi ulang',
                cancelButtonText: 'Batal'
            }).then(function (result) {
                if (result.isConfirmed || result.value) loadInformConsentModal(tindakanId, riwayatId, true);
            });
        });

        $(document).on('click', '#saveInformConsent', function () {
            saveInformConsent();
        });

        // Signatures (JPEG) and the dokter / perawat QR into the form's hidden fields (save and preview)
        function fillConsentPayload(form, pads) {
            $('#signatureData').val(pads.patient.isEmpty() ? '' : pads.patient.toDataURL('image/jpeg', 0.85));
            $('#witnessSignatureData').val(pads.witness.isEmpty() ? '' : pads.witness.toDataURL('image/jpeg', 0.85));
            const dokterNameForQr = form.find('input[name="dokter_name"]').val() || $('.dokter-nama').text() || '';
            const perawatNameForQr = @json(optional(auth()->user())->name ?? '');
            ['dokter_qr', 'perawat_qr'].forEach(function (name) {
                if (!form.find(`input[name="${name}"]`).length) {
                    form.append(`<input type="hidden" name="${name}" />`);
                }
            });
            form.find('input[name="dokter_qr"]').val(generateQrDataUriBrowser('Dokter: ' + dokterNameForQr) || '');
            form.find('input[name="perawat_qr"]').val(generateQrDataUriBrowser('Perawat: ' + perawatNameForQr) || '');
        }

        // Preview: the PDF as it would be saved (what is filled so far), nothing is stored
        let previewingConsent = false;
        $(document).on('click', '#previewInformConsent', function () {
            const form = $('#informConsentForm');
            const pads = signaturePads[1];
            if (previewingConsent || !consentRiwayatId || !form.length || !pads) return;
            fillConsentPayload(form, pads);
            previewingConsent = true;
            const btn = $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm mr-1"></span>Menyiapkan...');
            fetch(`/erm/tindakan/riwayat/${consentRiwayatId}/inform-consent/preview`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), Accept: 'application/pdf' },
                body: new FormData(form[0])
            }).then(function (res) {
                if (!res.ok || (res.headers.get('Content-Type') || '').indexOf('application/pdf') === -1) throw new Error('Preview gagal dibuat.');
                return res.blob();
            }).then(function (blob) {
                window.ErmPdfPreview.showBlob(blob, 'Preview Inform Consent (belum disimpan)', 'preview-inform-consent.pdf');
            }).catch(function (err) {
                Swal.fire('Gagal', err.message || 'Preview gagal dibuat.', 'error');
            }).finally(function () {
                previewingConsent = false;
                btn.prop('disabled', false).html('<i class="fas fa-eye mr-1"></i>Preview');
            });
        });

        function saveInformConsent() {
            // One save at a time: a double click would make the PDF twice
            if (savingInformConsent || !consentRiwayatId) return;
            const form = $('#informConsentForm');
            const pads = signaturePads[1];
            if (!form.length || !pads || !pads.patient || !pads.witness) {
                Swal.fire('Gagal', 'Form tanda tangan inform consent tidak ditemukan.', 'error');
                return;
            }
            if (pads.patient.isEmpty()) {
                Swal.fire('Tanda tangan kosong', 'Tanda tangan pasien wajib diisi.', 'error');
                return;
            }
            if (pads.witness.isEmpty()) {
                Swal.fire('Tanda tangan kosong', 'Tanda tangan saksi wajib diisi.', 'error');
                return;
            }
            const namaPasien = $.trim(form.find('[name="nama_pasien"]').val() || '');
            const namaSaksi = $.trim(form.find('[name="nama_saksi"]').val() || '');
            if (!namaPasien || !namaSaksi) {
                Swal.fire('Nama belum diisi', !namaPasien ? 'Nama pasien/wali wajib diisi.' : 'Nama saksi wajib diisi.', 'error');
                form.find(!namaPasien ? '[name="nama_pasien"]' : '[name="nama_saksi"]').addClass('is-invalid').trigger('focus');
                return;
            }
            form.find('[name="nama_pasien"], [name="nama_saksi"]').removeClass('is-invalid');
            fillConsentPayload(form, pads);

            savingInformConsent = true;
            Swal.fire({ title: 'Menyimpan...', text: 'Mohon tunggu, inform consent sedang disimpan.', icon: 'info', allowOutsideClick: false, allowEscapeKey: false, showConfirmButton: false });
            $.ajax({
                url: `/erm/tindakan/riwayat/${consentRiwayatId}/inform-consent`,
                method: 'POST',
                data: (function () { const fd = new FormData(form[0]); if (consentReplace) fd.append('replace', '1'); return fd; })(),
                processData: false,
                contentType: false,
                complete: function () {
                    savingInformConsent = false;
                },
                success: function (response) {
                    if (response.success) {
                        Swal.fire('Berhasil', response.message || 'Inform consent berhasil disimpan.', 'success').then(function () {
                            $('#modalInformConsent').modal('hide');
                            consentRiwayatId = null;
                            reloadTindakanTables();
                        });
                    } else {
                        Swal.fire('Gagal', response.message || 'Inform consent gagal disimpan.', 'error');
                    }
                },
                error: function (xhr) {
                    const errors = xhr.responseJSON && xhr.responseJSON.errors;
                    Swal.fire('Gagal', errors ? Object.values(errors).flat().join('<br>') : errorMessage(xhr, 'Inform consent gagal disimpan. Silakan coba lagi.'), 'error');
                }
            });
        }

        // SPK (read only)
        $(document).on('click', '.spk-btn', function () {
            const riwayatId = $(this).data('riwayat-id') || $(this).data('id');
            $('#modalSpkReadOnlyBody').html('<div class="text-center py-4">Memuat...</div>');
            $('#modalSpkReadOnly').modal('show');
            $.get(`/erm/tindakan/spk/by-riwayat/${riwayatId}`, function (response) {
                if (!response.success) {
                    $('#modalSpkReadOnlyBody').html('<div class="alert alert-danger">Data SPK tidak ditemukan.</div>');
                    return;
                }
                const data = response.data;
                let tanggalTindakan = data.spk?.tanggal_tindakan || '-';
                if (tanggalTindakan !== '-') {
                    const d = new Date(tanggalTindakan);
                    tanggalTindakan = !isNaN(d) ? d.toLocaleDateString('id-ID', { year: 'numeric', month: 'long', day: 'numeric' }) : tanggalTindakan;
                }
                $('#modalSpkReadOnlyBody').html(`<div class='row mb-2'>
                        <div class='col-md-4'><b>Nama Pasien:</b> ${escapeHtml(data.pasien_nama)}</div>
                        <div class='col-md-4'><b>No RM:</b> ${escapeHtml(data.pasien_id)}</div>
                        <div class='col-md-4'><b>Dokter PJ:</b> ${escapeHtml(data.dokter_nama)}</div>
                    </div>
                    <div class='row mb-2'>
                        <div class='col-md-4'><b>Tanggal Tindakan:</b> ${escapeHtml(tanggalTindakan)}</div>
                        <div class='col-md-4'><b>Nama Tindakan:</b> ${escapeHtml(data.tindakan_nama)}</div>
                        <div class='col-md-4'><b>Harga:</b> ${escapeHtml(data.harga)}</div>
                    </div>
                    <div class='row mb-2'>
                        <div class='col-md-4'><b>Jam Mulai:</b> ${data.spk?.waktu_mulai ? escapeHtml(data.spk.waktu_mulai.substring(0, 5)) : '-'}</div>
                        <div class='col-md-4'><b>Jam Selesai:</b> ${data.spk?.waktu_selesai ? escapeHtml(data.spk.waktu_selesai.substring(0, 5)) : '-'}</div>
                    </div>`);
            }).fail(function () {
                $('#modalSpkReadOnlyBody').html('<div class="alert alert-danger">Data SPK gagal dimuat.</div>');
            });
        });

        // Batalkan: a multi visit session is cancelled alone (the server refuses the paid first session while later ones exist)
        $(document).on('click', '.batalkan-tindakan-btn', function () {
            const id = $(this).data('id');
            Swal.fire({
                title: 'Batalkan Tindakan?',
                html: 'Tindakan beserta billing, inform consent, SPK, dan obat/BHP-nya akan dihapus.<br><small>Untuk paket multi visit, hanya sesi ini yang dibatalkan dan kuota paket dikembalikan.</small>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, batalkan',
                cancelButtonText: 'Tidak'
            }).then(function (result) {
                if (!(result.isConfirmed || result.value)) return;
                $.ajax({
                    url: `/erm/tindakan/riwayat/${id}`,
                    method: 'DELETE',
                    success: function (response) {
                        if (response.success) {
                            Swal.fire('Berhasil', response.message, 'success');
                            $('#historyTindakanTable').DataTable().ajax.reload();
                            $('#tindakanTable').DataTable().ajax.reload(null, false);
                        } else {
                            Swal.fire('Gagal', response.message || 'Tindakan tidak dapat dibatalkan.', 'error');
                        }
                    },
                    error: function (xhr) {
                        Swal.fire('Gagal', errorMessage(xhr, 'Terjadi kesalahan server.'), 'error');
                    }
                });
            });
        });

        // Isi paket (click on the tindakan name)
        $(document).on('click', '.tindakan-name-link', function (e) {
            e.preventDefault();
            const tindakanId = $(this).data('id');
            $('#modalSopDetailLabel').text('Isi Paket Tindakan');
            $('#sopTable tbody').html('<tr><td colspan="4" class="text-center">Memuat...</td></tr>');
            $('#modalSopDetail').modal('show');
            $.get(`/erm/tindakan/${tindakanId}/sop-list`, function (response) {
                if (!response.success) {
                    $('#sopTable tbody').html('<tr><td colspan="4" class="text-center">Gagal memuat data</td></tr>');
                    return;
                }
                $('#modalSopDetailLabel').text('Isi Paket Tindakan: ' + response.tindakan);
                if (!response.kode_tindakans || !response.kode_tindakans.length) {
                    $('#sopTable tbody').html('<tr><td colspan="4" class="text-center">Belum ada tindakan di paket ini</td></tr>');
                    return;
                }
                $('#sopTable tbody').html(response.kode_tindakans.map(function (item) {
                    let obatList = '-';
                    if (item.obats && item.obats.length) {
                        obatList = '<ul class="mb-0 pl-3">' + item.obats.map(function (o) {
                            const jumlah = o.jumlah !== null ? (' x ' + o.jumlah) : '';
                            const dosis = o.dosis ? (' | ' + o.dosis + (o.satuan_dosis ? (' ' + o.satuan_dosis) : '')) : '';
                            return '<li>' + escapeHtml(o.nama + jumlah + dosis) + '</li>';
                        }).join('') + '</ul>';
                    }
                    return `<tr><td>${escapeHtml(item.no)}</td><td>${escapeHtml(item.kode)}</td><td>${escapeHtml(item.nama)}</td><td>${obatList}</td></tr>`;
                }).join(''));
            }).fail(function () {
                $('#sopTable tbody').html('<tr><td colspan="4" class="text-center">Gagal memuat data</td></tr>');
            });
        });

        // Obat / BHP of a riwayat tindakan (change obat, qty, dosis)
        $(document).on('click', '.detail-riwayat-btn', function () {
            const riwayatId = $(this).data('id');
            $('#riwayatDetailContent').html('<div class="text-center py-4">Memuat...</div>');
            $('#modalRiwayatDetail').modal('show');
            $.get(`/erm/riwayat-tindakan/${riwayatId}/detail`, function (response) {
                $('#riwayatDetailContent').html(response.html);
            }).fail(function () {
                $('#riwayatDetailContent').html('<div class="alert alert-danger">Detail gagal dimuat.</div>');
            });
        });
    });
</script>

@endsection
