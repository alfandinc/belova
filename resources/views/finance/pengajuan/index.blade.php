@extends('layouts.finance.app')
@section('title', 'Finance | Pengajuan Dana')
@section('navbar')
    @include('layouts.finance.navbar')
@endsection

@section('content')
    <link rel="stylesheet" href="{{ asset('dastone/vendor/datatable/FixedColumns-4.3.0/css/fixedColumns.bootstrap4.min.css') }}">
    <style>
        /* Solid backgrounds for pinned columns so scrolled cells don't show through (same palette as billing) */
        .pengajuan-dt-wrap { --pfc-bg: #2c3144; --pfc-bg-even: #333950; --pfc-bg-hover: #2a2e40; --pfc-head: #333950; }
        html.theme-light .pengajuan-dt-wrap { --pfc-bg: #fff; --pfc-bg-even: #f1f5fa; --pfc-bg-hover: #f8f8fc; --pfc-head: #f1f5fa; }
        .pengajuan-dt-wrap table.dataTable tbody tr > .dtfc-fixed-left,
        .pengajuan-dt-wrap table.dataTable tbody tr > .dtfc-fixed-right { background-color: var(--pfc-bg) !important; }
        .pengajuan-dt-wrap table.dataTable tbody tr:nth-of-type(even) > .dtfc-fixed-left,
        .pengajuan-dt-wrap table.dataTable tbody tr:nth-of-type(even) > .dtfc-fixed-right { background-color: var(--pfc-bg-even) !important; }
        .pengajuan-dt-wrap table.dataTable tbody tr:hover > .dtfc-fixed-left,
        .pengajuan-dt-wrap table.dataTable tbody tr:hover > .dtfc-fixed-right { background-color: var(--pfc-bg-hover) !important; }
        .pengajuan-dt-wrap table.dataTable thead tr > .dtfc-fixed-left,
        .pengajuan-dt-wrap table.dataTable thead tr > .dtfc-fixed-right { background-color: var(--pfc-head) !important; }

        /* Action buttons: icon and text on one line, buttons never wrap */
        #pengajuanTable td.actions-cell { white-space: nowrap; }
        #pengajuanTable td.actions-cell .btn-group { flex-wrap: nowrap; }
        #pengajuanTable td.actions-cell .btn {
            display: inline-flex; align-items: center; justify-content: center;
            white-space: nowrap;
            /* keep the theme's normal btn-sm size; only line-height was being squeezed */
            line-height: 1.5;
        }
        #pengajuanTable td.actions-cell .btn i { display: inline-block; margin-right: 4px; }

        /* Constrain items column without forcing table-layout: fixed which collapses other columns */
        #pengajuanTable td.items-list-cell {
            max-width: 360px; /* adjust as needed */
            white-space: normal !important;
            word-break: break-word;
            overflow: hidden;
            text-overflow: ellipsis;
            vertical-align: top;
        }
        /* approval + payment status cells */
        #pengajuanTable td.approvals-cell, #pengajuanTable td.payment-cell { text-align: left; vertical-align: top; }
        /* align every body cell to the top */
        #pengajuanTable tbody td { vertical-align: top !important; }
        #pengajuanTable .approval-status { cursor: pointer; border-radius: 4px; padding: 4px 6px; margin: -4px -6px; }
        #pengajuanTable .approval-status:hover { background: #f1f3f5; }
        #pengajuanTable .approval-label { font-weight: 600; font-size: 13px; white-space: nowrap; }
        #pengajuanTable .approval-label .fa { margin-right: 3px; }
        /* status label on the left, progress dots pushed to the right corner of the cell */
        #pengajuanTable .approval-head { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
        #pengajuanTable .approval-dots { line-height: 1; white-space: nowrap; flex: 0 0 auto; }
        #pengajuanTable .approval-dot { display: inline-block; width: 8px; height: 8px; border-radius: 50%; margin-right: 3px; }
        #pengajuanTable .approval-dot:last-child { margin-right: 0; }
        #pengajuanTable .approval-info { font-size: 11px; color: #6c757d; margin-top: 2px; line-height: 1.3; }
        #pengajuanTable .approval-info .decline-note { color: #dc3545; white-space: normal; }
        /* Small, fixed width for the 'No' column */
        #pengajuanTable td.col-no, #pengajuanTable th.col-no {
            text-align: center;
            white-space: nowrap;
            padding-left: 6px;
            padding-right: 6px;
            font-size: 13px;
            max-width: 36px;
            min-width: 28px;
            width: 32px;
        }
        /* Force grand total values to align to the right edge of the cell */
        #pengajuanTable td.grand-total-cell, #pengajuanTable th.grand-total-cell {
            text-align: right !important;
            padding-right: 12px;
            vertical-align: top;
            white-space: nowrap; /* money nominal must stay on one line (column grows instead) */
        }
        /* Ensure any inner elements also align right and span full width */
        #pengajuanTable td.grand-total-cell > * {
            display: block;
            width: 100%;
            text-align: right !important;
        }
        /* blinking badge for empty approvals */
        @keyframes blinkAnim { 0% { opacity: 1; } 50% { opacity: 0.2; } 100% { opacity: 1; } }
        /* apply animation directly to approvals-empty so it blinks */
        .approvals-empty { animation: blinkAnim 1.2s linear infinite; }
        /* blinking danger badge on the top-right corner of the Detail/Edit button when bukti is missing */
        #pengajuanTable .actions-cell { overflow: visible; }
        /* keep the flagged button (and its badge) above the neighbouring buttons in the group */
        #pengajuanTable .btn.has-warn-badge { position: relative; overflow: visible; z-index: 2; }
        #pengajuanTable .no-bukti-badge {
            position: absolute; top: -6px; right: -6px;
            display: flex; align-items: center; justify-content: center;
            width: 15px; height: 15px; border-radius: 50%;
            background: #dc3545; color: #fff;
            box-shadow: 0 0 0 2px #fff;           /* crisp white ring instead of a border */
            font-family: Arial, sans-serif; font-size: 10px; font-weight: 700; line-height: 1;
            pointer-events: none;
            animation: noBuktiPulse 1.4s ease-in-out infinite;
        }
        /* blink: fade the red dot but keep it readable */
        @keyframes noBuktiPulse { 0%, 100% { opacity: 1; } 50% { opacity: .45; } }
        .approvals-empty .fa { margin-right: 6px; }
        /* item names: single item as plain text, several items as bullet points */
        #pengajuanTable td.items-list-cell .items-summary-single { line-height: 1.4; font-weight: 700; }
        #pengajuanTable td.items-list-cell .items-summary { margin: 0; padding-left: 16px; line-height: 1.4; font-weight: 700; list-style: disc; }
        #pengajuanTable td.items-list-cell .items-summary li { margin-bottom: 1px; }
        /* item notes: small muted text under the item name */
        #pengajuanTable td.items-list-cell .item-notes { font-size: 11px; font-weight: 400; color: #6c757d; line-height: 1.3; white-space: normal; }
        /* main value of a cell (bold); secondary info below it stays small/muted */
        #pengajuanTable .cell-main { font-weight: 700; }
        /* detail pengajuan modal */
        #itemsDetailTable td, #itemsDetailTable th { vertical-align: middle; }
        #itemsDetailModal .detail-label { font-size: 11px; color: #6c757d; text-transform: uppercase; letter-spacing: .3px; }
        #itemsDetailModal .detail-info > div > div:last-child { font-weight: 500; }
        #itemsDetailModal .detail-section { font-weight: 600; margin-bottom: 8px; }
        #itemsDetailModal .detail-bukti { width: 120px; margin: 0 10px 10px 0; text-align: center; }
        #itemsDetailModal .detail-bukti img { width: 120px; height: 120px; object-fit: cover; border: 1px solid #dee2e6; border-radius: 4px; cursor: zoom-in; }
        /* Ensure form labels in the pengajuan modal use Title Case instead of all-caps */
        #pengajuanModal label { text-transform: capitalize !important; }
        /* show red asterisk for required fields */
        #pengajuanModal label.required:after { content: " *"; color: #e74c3c; margin-left: 4px; }
        /* simplified pengajuan form: numbered sections */
        #pengajuanModal .pj-section { border: 1px solid rgba(0,0,0,.08); border-radius: 6px; padding: 14px 16px 6px; margin-bottom: 14px; }
        #pengajuanModal .pj-section-title { font-weight: 600; font-size: 14px; margin-bottom: 12px; display: flex; align-items: center; }
        #pengajuanModal .pj-step {
            display: inline-flex; align-items: center; justify-content: center;
            width: 22px; height: 22px; border-radius: 50%; margin-right: 8px;
            background: #1761fd; color: #fff; font-size: 12px; font-weight: 700;
        }
        #pengajuanModal .form-group { margin-bottom: 12px; }
        #pengajuanModal label { font-weight: 500; margin-bottom: 4px; }
        #pengajuanModal .pj-inline-box { background: rgba(23,97,253,.05); border: 1px dashed rgba(23,97,253,.35); border-radius: 6px; padding: 10px 12px 2px; margin-bottom: 10px; }
        #pengajuanModal #itemsTable td { vertical-align: middle; padding: 4px; border-top: 1px solid rgba(0,0,0,.06); }
        #pengajuanModal #itemsTable th { font-weight: 600; font-size: 12px; padding: 6px 4px; border: 0; }
        #pengajuanModal #itemsTable .form-control { height: 34px; }
        #pengajuanModal #itemsTable tr.pj-faktur-row .form-control[readonly] { background: rgba(23,97,253,.06); }
        #pengajuanModal #itemsTable .item-total { border: 0; background: transparent; text-align: right; font-weight: 600; }
        #pengajuanModal #itemsTable .remove-item { padding: 4px 8px; }
        #pengajuanModal .pj-grand-total { display: flex; align-items: center; }
        #pengajuanModal .pj-grand-total-value { border: 0; background: transparent; font-size: 20px; font-weight: 700; color: #1761fd; text-align: right; width: 220px; padding: 0; }
        #pengajuanModal .pj-grand-total-value:focus { outline: none; }
        #pengajuanModal #bukti_preview img { width: 90px; height: 90px; object-fit: cover; border-radius: 4px; border: 1px solid #dee2e6; margin: 0 8px 8px 0; }
        /* select2 fields flagged invalid */
        #pengajuanModal .select2-invalid + .select2-container .select2-selection { border-color: #dc3545 !important; }
        #buktiModalPreview {
            display: block;
            width: 100%;
        }
        #buktiModalPreview img {
            display: block;
            width: 100%;
            height: auto;
            margin-bottom: 12px;
            object-fit: contain;
            cursor: zoom-in;
        }
        #buktiZoomModal .modal-content {
            background: #111;
            border: 0;
        }
        #buktiZoomModal .modal-header {
            border-bottom: 0;
            color: #fff;
        }
        #buktiZoomModal .close {
            color: #fff;
            opacity: 0.9;
        }
        #buktiZoomModal .modal-body {
            padding-top: 0;
        }
        #buktiZoomImage {
            display: block;
            width: 100%;
            height: auto;
            max-height: calc(100vh - 120px);
            object-fit: contain;
        }
    </style>
<div class="page-content">
    <div class="container-fluid">
        <!-- page header (same layout as Billing) -->
        <div class="row mb-2">
            <div class="col-12 d-flex flex-wrap justify-content-between align-items-center">
                <div>
                    <h3 class="mb-0 font-weight-bold">Daftar Pengajuan Dana</h3>
                    <div class="text-muted small">Ajukan dana, pantau persetujuan berjenjang, dan proses pembayaran.</div>
                </div>
                <div class="d-flex align-items-center">
                    <div class="btn-group btn-group-sm" role="group" aria-label="Header actions">
                        @hasrole('Admin')
                        <button type="button" class="btn btn-secondary" id="btnKelolaApprover" title="Kelola Approver">
                            <i class="fas fa-user-check mr-1"></i> Kelola Approver
                        </button>
                        @endhasrole
                        <button type="button" class="btn btn-dark" id="btnKelolaRekening" title="Kelola Rekening">
                            <i class="fas fa-university mr-1"></i> Kelola Rekening
                        </button>
                        <button type="button" class="btn btn-info" id="btnRiwayatPembayaran" title="Riwayat Pembayaran">
                            <i class="fas fa-history mr-1"></i> Riwayat Pembayaran
                        </button>
                        <button type="button" class="btn btn-primary" id="btnAddPengajuan" title="Buat Pengajuan">
                            <i class="fas fa-plus mr-1"></i> Buat Pengajuan
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm mb-4">

                            <!-- Riwayat Pembayaran Modal (Bootstrap 4) -->
                            <div class="modal fade" id="riwayatPembayaranModal" tabindex="-1" role="dialog" aria-labelledby="riwayatPembayaranModalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-lg" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="riwayatPembayaranModalLabel">Riwayat Pembayaran</h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-2">
                                                <input type="text" id="paidFilterTanggal" class="form-control form-control-sm" placeholder="Pilih rentang tanggal" readonly style="max-width:260px;" />
                                            </div>
                                            <div class="table-responsive">
                                                <table id="paidHistoryTable" class="table table-sm table-bordered" style="width:100%">
                                                    <thead>
                                                        <tr>
                                                            <th>No</th>
                                                            <th>No Pengajuan</th>
                                                            <th>Items</th>
                                                            <th>Rekening</th>
                                                            <th>Dibayar Pada</th>
                                                            <th>Nominal</th>
                                                            <th>Oleh</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody></tbody>
                                                </table>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fa fa-times mr-1"></i>Tutup</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Detail Pengajuan Modal (Bootstrap 4): info, items + harga, bukti foto -->
                            <div class="modal fade" id="itemsDetailModal" tabindex="-1" role="dialog" aria-labelledby="itemsDetailModalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-lg" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="itemsDetailModalLabel">Detail Pengajuan</h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row detail-info mb-3">
                                                <div class="col-md-4 mb-2"><div class="detail-label">No Pengajuan</div><div id="dt_kode">-</div></div>
                                                <div class="col-md-4 mb-2"><div class="detail-label">Tanggal Diajukan</div><div id="dt_tanggal">-</div></div>
                                                <div class="col-md-4 mb-2"><div class="detail-label">Diajukan oleh</div><div id="dt_pengaju">-</div></div>
                                                <div class="col-md-4 mb-2"><div class="detail-label">Jenis Pengajuan</div><div id="dt_jenis">-</div></div>
                                                <div class="col-md-4 mb-2"><div class="detail-label">Diajukan ke</div><div id="dt_diajukan">-</div></div>
                                                <div class="col-md-4 mb-2"><div class="detail-label">Rekening Tujuan</div><div id="dt_rekening">-</div></div>
                                            </div>
                                            <h6 class="detail-section">Items</h6>
                                            <div class="table-responsive">
                                                <table id="itemsDetailTable" class="table table-sm table-bordered mb-0">
                                                    <thead>
                                                        <tr>
                                                            <th style="width:5%">#</th>
                                                            <th>Nama Item</th>
                                                            <th>Notes</th>
                                                            <th class="text-center" style="width:8%">Qty</th>
                                                            <th class="text-right" style="width:17%">Harga</th>
                                                            <th class="text-right" style="width:17%">Total</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody></tbody>
                                                    <tfoot>
                                                        <tr>
                                                            <th colspan="5" class="text-right">Grand Total</th>
                                                            <th class="text-right" id="itemsDetailGrandTotal"></th>
                                                        </tr>
                                                    </tfoot>
                                                </table>
                                            </div>
                                            <h6 class="detail-section mt-3">Bukti Foto</h6>
                                            <div id="dt_bukti" class="d-flex flex-wrap"></div>
                                            <div id="dt_pembayaran_wrap" style="display:none;">
                                                <h6 class="detail-section mt-3">Pembayaran</h6>
                                                <div id="dt_pembayaran"></div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <a href="#" class="btn btn-outline-secondary mr-auto" id="dt_pdf" target="_blank"><i class="fa fa-file-pdf mr-1"></i>Cetak PDF</a>
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fa fa-times mr-1"></i>Tutup</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Kelola Rekening Modal (Bootstrap 4) -->
                            <div class="modal fade" id="kelolaRekeningModal" tabindex="-1" role="dialog" aria-labelledby="kelolaRekeningModalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-lg" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="kelolaRekeningModalLabel">Kelola Rekening</h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                        </div>
                                        <div class="modal-body">
                                            <form id="kelolaRekeningForm" class="mb-3" autocomplete="off">
                                                <input type="hidden" id="kr_id" value="">
                                                <div class="form-row align-items-end">
                                                    <div class="col-md-3 mb-2">
                                                        <label for="kr_bank" class="mb-1">Bank</label>
                                                        <input type="text" class="form-control form-control-sm" id="kr_bank" name="bank">
                                                        <div class="invalid-feedback"></div>
                                                    </div>
                                                    <div class="col-md-3 mb-2">
                                                        <label for="kr_no_rekening" class="mb-1">No. Rekening</label>
                                                        <input type="text" class="form-control form-control-sm" id="kr_no_rekening" name="no_rekening">
                                                        <div class="invalid-feedback"></div>
                                                    </div>
                                                    <div class="col-md-3 mb-2">
                                                        <label for="kr_atas_nama" class="mb-1">Atas Nama</label>
                                                        <input type="text" class="form-control form-control-sm" id="kr_atas_nama" name="atas_nama">
                                                        <div class="invalid-feedback"></div>
                                                    </div>
                                                    <div class="col-md-3 mb-2">
                                                        <button type="submit" class="btn btn-primary btn-sm" id="kr_save"><i class="fa fa-plus mr-1"></i>Tambah</button>
                                                        <button type="button" class="btn btn-outline-secondary btn-sm" id="kr_cancel" style="display:none;"><i class="fa fa-times mr-1"></i>Batal</button>
                                                    </div>
                                                </div>
                                            </form>
                                            <div class="table-responsive">
                                                <table id="kelolaRekeningTable" class="table table-sm table-bordered" style="width:100%">
                                                    <thead>
                                                        <tr>
                                                            <th>No</th>
                                                            <th>Bank</th>
                                                            <th>No. Rekening</th>
                                                            <th>Atas Nama</th>
                                                            <th style="width:130px">Action</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody></tbody>
                                                </table>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fa fa-times mr-1"></i>Tutup</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            @hasrole('Admin')
                            <!-- Kelola Approver Modal (Bootstrap 4, Admin only) -->
                            <div class="modal fade" id="kelolaApproverModal" tabindex="-1" role="dialog" aria-labelledby="kelolaApproverModalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-xl" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="kelolaApproverModalLabel">Kelola Approver Pengajuan</h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="alert alert-light border small mb-3">
                                                Approval berjalan dari <strong>tingkat tertinggi</strong> ke terendah; cukup satu approver per tingkat.
                                                Sumber dana kosong = berlaku untuk semua sumber dana.
                                            </div>
                                            <form id="kelolaApproverForm" class="mb-3" autocomplete="off">
                                                <input type="hidden" id="ka_id" value="">
                                                <div class="form-row align-items-end">
                                                    <div class="col-md-3 mb-2">
                                                        <label for="ka_user_id" class="mb-1">User <span class="text-danger">*</span></label>
                                                        <select id="ka_user_id" class="form-control form-control-sm" style="width:100%">
                                                            <option value="">-- Pilih User --</option>
                                                            @foreach($approverUsers as $u)
                                                                <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
                                                            @endforeach
                                                        </select>
                                                        <div class="invalid-feedback"></div>
                                                    </div>
                                                    <div class="col-md-2 mb-2">
                                                        <label for="ka_jabatan" class="mb-1">Jabatan</label>
                                                        <input type="text" class="form-control form-control-sm" id="ka_jabatan">
                                                        <div class="invalid-feedback"></div>
                                                    </div>
                                                    <div class="col-md-1 mb-2">
                                                        <label for="ka_tingkat" class="mb-1">Tingkat</label>
                                                        <input type="number" class="form-control form-control-sm" id="ka_tingkat" min="1" value="1">
                                                        <div class="invalid-feedback"></div>
                                                    </div>
                                                    <div class="col-md-2 mb-2">
                                                        <label for="ka_jenis" class="mb-1">Sumber Dana</label>
                                                        <select id="ka_jenis" class="form-control form-control-sm">
                                                            <option value="">Semua</option>
                                                            <option value="Kas Bank">Kas Bank</option>
                                                            <option value="Kas Kecil">Kas Kecil</option>
                                                        </select>
                                                        <div class="invalid-feedback"></div>
                                                    </div>
                                                    <div class="col-md-1 mb-2">
                                                        <div class="custom-control custom-checkbox mb-1">
                                                            <input type="checkbox" class="custom-control-input" id="ka_aktif" checked>
                                                            <label class="custom-control-label" for="ka_aktif">Aktif</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3 mb-2">
                                                        <button type="submit" class="btn btn-primary btn-sm" id="ka_save"><i class="fa fa-plus mr-1"></i>Tambah</button>
                                                        <button type="button" class="btn btn-outline-secondary btn-sm" id="ka_cancel" style="display:none;"><i class="fa fa-times mr-1"></i>Batal</button>
                                                    </div>
                                                </div>
                                            </form>
                                            <div class="table-responsive">
                                                <table id="kelolaApproverTable" class="table table-sm table-bordered" style="width:100%">
                                                    <thead>
                                                        <tr>
                                                            <th>No</th>
                                                            <th>User</th>
                                                            <th>Jabatan</th>
                                                            <th>Tingkat</th>
                                                            <th>Sumber Dana</th>
                                                            <th>Aktif</th>
                                                            <th style="width:130px">Action</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody></tbody>
                                                </table>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fa fa-times mr-1"></i>Tutup</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endhasrole

                            <!-- Bayar Modal: record one payment (full, partial, or closed with a difference) -->
                            <div class="modal fade" id="bayarModal" tabindex="-1" role="dialog" aria-labelledby="bayarModalLabel" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <form id="bayarForm" autocomplete="off">
                                            <input type="hidden" id="bayar_id" value="">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="bayarModalLabel">Bayar Pengajuan</h5>
                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                            </div>
                                            <div class="modal-body">
                                                <table class="table table-sm mb-3">
                                                    <tr><td class="text-muted">Total diajukan</td><td class="text-right" id="bayar_grand">-</td></tr>
                                                    <tr><td class="text-muted">Sudah dibayar</td><td class="text-right" id="bayar_sudah">-</td></tr>
                                                    <tr><th>Sisa yang bisa dibayar</th><th class="text-right" id="bayar_sisa">-</th></tr>
                                                </table>
                                                <div class="form-group">
                                                    <label for="bayar_nominal" class="mb-1">Nominal dibayar <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" id="bayar_nominal" inputmode="numeric">
                                                </div>
                                                <!-- shown only when nominal < sisa -->
                                                <div id="bayar_kurang_box" class="alert alert-warning py-2" style="display:none;">
                                                    <div class="mb-1"><strong>Nominal kurang <span id="bayar_selisih"></span> dari sisa.</strong> Sisanya:</div>
                                                    <div class="custom-control custom-radio">
                                                        <input type="radio" class="custom-control-input" id="bayar_mode_partial" name="bayar_mode" value="partial" checked>
                                                        <label class="custom-control-label" for="bayar_mode_partial">Dibayar menyusul (status: Dibayar Sebagian)</label>
                                                    </div>
                                                    <div class="custom-control custom-radio">
                                                        <input type="radio" class="custom-control-input" id="bayar_mode_close" name="bayar_mode" value="close">
                                                        <label class="custom-control-label" for="bayar_mode_close">Tidak dibayar, selesaikan pembayaran dengan selisih</label>
                                                    </div>
                                                </div>
                                                <div class="form-row">
                                                    <div class="form-group col-md-6">
                                                        <label for="bayar_tanggal" class="mb-1">Tanggal bayar</label>
                                                        <input type="datetime-local" class="form-control" id="bayar_tanggal">
                                                    </div>
                                                    <div class="form-group col-md-6">
                                                        <label for="bayar_bukti" class="mb-1">Bukti transfer</label>
                                                        <input type="file" class="form-control-file" id="bayar_bukti" accept="image/*,application/pdf">
                                                    </div>
                                                </div>
                                                <div class="form-group mb-0">
                                                    <label for="bayar_note" class="mb-1">Catatan <span class="text-danger" id="bayar_note_required" style="display:none;">* wajib jika kurang dari sisa</span></label>
                                                    <textarea class="form-control" id="bayar_note" rows="2" placeholder="Mis. dana kas belum cukup / item X dibatalkan"></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                                                <button type="submit" class="btn btn-success" id="bayar_submit"><i class="fa fa-wallet mr-1"></i>Simpan Pembayaran</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                    <div class="card-body">
                        <!-- status tabs left, filters right (same layout as Billing) -->
                        <div class="d-flex flex-wrap align-items-center justify-content-between" style="gap: .5rem;">
                            <ul class="nav nav-tabs mb-0" id="pengajuanTabs" role="tablist" style="flex:0 0 auto;">
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link active" href="#" data-status="menunggu" role="tab">
                                        Menunggu <span id="pj-tab-badge-menunggu" class="badge badge-danger ml-2" style="display:none;">0</span>
                                    </a>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link" href="#" data-status="approved" role="tab" title="Badge: sudah disetujui, belum dibayar">
                                        Disetujui <span id="pj-tab-badge-approved" class="badge badge-primary ml-2" style="display:none;">0</span>
                                    </a>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link" href="#" data-status="declined" role="tab">Ditolak</a>
                                </li>
                            </ul>
                            <!-- current tab; read by the DataTable request -->
                            <input type="hidden" id="filter_approval" value="menunggu">

                            <div class="d-flex flex-wrap align-items-center justify-content-end" style="gap: .5rem; flex:1 1 auto;">
                                <div class="d-flex align-items-center" style="flex:0 0 200px;">
                                    <select id="filter_jenis" class="form-control form-control-sm w-100">
                                        <option value="">Semua Jenis Pengajuan</option>
                                        <option value="Pembayaran Inkaso">Pembayaran Inkaso</option>
                                        <option value="Pembelian Barang">Pembelian Barang</option>
                                        <option value="Operasional">Operasional</option>
                                    </select>
                                </div>
                                <div class="d-flex align-items-center" style="flex:0 0 180px;">
                                    <select id="filter_sumber" class="form-control form-control-sm w-100">
                                        <option value="">Semua Sumber Dana</option>
                                        <option value="Kas Bank">Kas Bank</option>
                                        <option value="Kas Kecil">Kas Kecil</option>
                                    </select>
                                </div>
                                <div class="d-flex align-items-center" style="flex:0 0 260px;">
                                    <div class="input-group input-group-sm w-100">
                                        <input type="text" id="filter_tanggal" class="form-control form-control-sm" placeholder="Pilih Rentang Tanggal" readonly>
                                        <span class="input-group-text"><i class="ti-calendar"></i></span>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-light" id="clearFilterTanggal" title="Reset tanggal"><i class="fas fa-times mr-1"></i>Reset</button>
                                <span id="bulkSelectionSummary" class="badge badge-light border px-2 py-1" style="display:none; font-size:12px;">
                                    <span id="bulkSelectionCount">0</span> dipilih &middot; Total <strong id="bulkSelectionTotal">Rp 0</strong>
                                </span>
                                <button type="button" class="btn btn-sm btn-success" id="btnBulkApprove" title="Approve yang dipilih" style="display:none;">
                                    <i class="fas fa-check-double mr-1"></i>Approve Terpilih
                                </button>
                            </div>
                        </div>

                        <div class="pt-3">
                        <!-- horizontal scroll with No + No Pengajuan pinned left and Action pinned right (same as billing) -->
                        <div class="pengajuan-dt-wrap">
                            <table id="pengajuanTable" class="table table-bordered table-hover table-striped" style="width:100%">
                                <thead class="thead-light">
                                    <tr>
                                        <th class="col-no">No</th>
                                        <th>No Pengajuan</th>
                                        <th>Tanggal Diajukan</th>
                                        <th>Rincian Item</th>
                                        <th>Total</th>
                                        <th>Rekening Tujuan</th>
                                        <th>Diajukan ke</th>
                                        <th>Approval Status</th>
                                        <th>Payment Status</th>
                                        <th style="width:46px; text-align:center">
                                            <input type="checkbox" id="select_all_rows" title="Pilih semua (halaman ini)">
                                        </th>
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
    </div>
</div>

            <!-- Bukti Modal: show existing bukti and allow uploading additional files -->
            <div class="modal fade" id="buktiModal" tabindex="-1" aria-labelledby="buktiModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="buktiModalLabel">Upload Bukti Transaksi</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-2">
                                <strong>Gambar tersimpan:</strong>
                                <div id="buktiModalPreview" class="mt-2"></div>
                            </div>
                            <hr id="buktiModalUploadDivider" />
                            <div class="mb-2" id="buktiModalUploadSection">
                                <label class="form-label">Tambah file</label>
                                <div class="d-flex align-items-center">
                                    <input type="file" id="buktiModalInput" name="bukti_transaksi[]" accept="image/*" multiple style="display:block">
                                    <div class="ml-2"><small id="buktiModalFilesLabel" class="text-muted">Tidak ada file terpilih</small></div>
                                </div>
                                <div id="buktiModalNewPreview" class="mt-2 d-flex flex-wrap"></div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fa fa-times mr-1"></i>Batal</button>
                            <button type="button" id="buktiModalUpload" class="btn btn-primary"><i class="fa fa-upload mr-1"></i>Upload</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="buktiZoomModal" tabindex="-1" aria-labelledby="buktiZoomModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="buktiZoomModalLabel">Preview Bukti Transaksi</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        </div>
                        <div class="modal-body">
                            <img id="buktiZoomImage" src="" alt="Bukti transaksi">
                        </div>
                    </div>
                </div>
            </div>

<!-- Add/Edit Pengajuan Modal (skeleton) -->
<div class="modal fade" id="pengajuanModal" tabindex="-1" aria-labelledby="pengajuanModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" style="max-width:1100px;">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-0" id="pengajuanModalLabel">Buat Pengajuan Dana</h5>
                    <small class="text-muted" id="pengajuanKodeInfo">No. pengajuan dibuat otomatis saat disimpan</small>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="pengajuanForm" novalidate>
                @csrf
                <input type="hidden" id="pengajuan_id" name="pengajuan_id">
                <input type="hidden" id="division_id" name="division_id" value="">
                <input type="hidden" id="items_json" name="items_json">
                <!-- one-time token per opened form: prevents duplicate pengajuan from double clicks -->
                <input type="hidden" id="submit_token" name="submit_token">
                <div class="modal-body">

                    <!-- 1. Informasi pengajuan -->
                    <div class="pj-section">
                        <div class="pj-section-title"><span class="pj-step">1</span> Informasi Pengajuan</div>
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label for="employee_id" class="required">Nama Pengaju</label>
                                @if($canChoosePengaju)
                                    <select id="employee_id" name="employee_id" class="form-control select2" style="width:100%">
                                        <option value="">-- Pilih Pengaju --</option>
                                        @php $employees = \App\Models\HRD\Employee::active()->with('user')->orderBy('nama')->get(); @endphp
                                        @foreach($employees as $emp)
                                            <option value="{{ $emp->id }}" data-division-id="{{ $emp->division_id ?? '' }}">{{ $emp->user->name ?? $emp->nama }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    {{-- regular users always submit for themselves --}}
                                    <input type="text" class="form-control" value="{{ optional($currentEmployee)->user->name ?? optional($currentEmployee)->nama ?? auth()->user()->name }}" readonly>
                                    <input type="hidden" id="employee_id" name="employee_id" value="{{ optional($currentEmployee)->id }}" data-division-id="{{ optional($currentEmployee)->division_id }}">
                                @endif
                                <div class="invalid-feedback" id="employee_id-error"></div>
                            </div>
                            <div class="form-group col-md-4">
                                <label for="tanggal_pengajuan" class="required">Tanggal</label>
                                <input type="date" class="form-control" id="tanggal_pengajuan" name="tanggal_pengajuan">
                                <div class="invalid-feedback" id="tanggal_pengajuan-error"></div>
                            </div>
                            <div class="form-group col-md-4">
                                <label for="jenis_pengajuan" class="required">Jenis Pengajuan</label>
                                <select id="jenis_pengajuan" name="jenis_pengajuan" class="form-control">
                                    <option value="">-- Pilih Jenis --</option>
                                    <option value="Pembayaran Inkaso">Pembayaran Inkaso</option>
                                    <option value="Pembelian Barang">Pembelian Barang</option>
                                    <option value="Operasional">Operasional</option>
                                </select>
                                <div class="invalid-feedback" id="jenis_pengajuan-error"></div>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label for="sumber_dana" class="required">Sumber Dana</label>
                                <select id="sumber_dana" name="sumber_dana" class="form-control">
                                    <option value="">-- Pilih Sumber Dana --</option>
                                    <option value="Kas Bank">Kas Bank</option>
                                    <option value="Kas Kecil">Kas Kecil</option>
                                </select>
                                <div class="invalid-feedback" id="sumber_dana-error"></div>
                            </div>
                            <div class="form-group col-md-4">
                                <label for="perusahaan" class="required">Perusahaan</label>
                                <select id="perusahaan" name="perusahaan" class="form-control">
                                    <option value="">-- Pilih Perusahaan --</option>
                                    <option value="CV Belia Abadi">CV Belia Abadi</option>
                                    <option value="CV Belova Indonesia">CV Belova Indonesia</option>
                                    <option value="Belova Corp">Belova Corp</option>
                                    <option value="CV Grha Asri">CV Grha Asri</option>
                                    <option value="Belova Dental">Belova Dental</option>
                                </select>
                                <div class="invalid-feedback" id="perusahaan-error"></div>
                            </div>
                            <div class="form-group col-md-4">
                                <label for="rekening_id" class="required">Rekening Tujuan</label>
                                <div class="d-flex align-items-start">
                                    <div style="flex:1; min-width:0;">
                                        <select id="rekening_id" name="rekening_id" class="form-control select2" style="width:100%">
                                            <option value="">-- Pilih Rekening --</option>
                                            @php $reks = \App\Models\Finance\FinanceRekening::orderBy('bank')->get(); @endphp
                                            @foreach($reks as $r)
                                                <option value="{{ $r->id }}">{{ $r->bank }} / {{ $r->no_rekening }} / {{ $r->atas_nama }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <button type="button" class="btn btn-outline-primary ml-2 text-nowrap" id="btnToggleRekInline" title="Tambah rekening baru"><i class="fa fa-plus mr-1"></i>Baru</button>
                                </div>
                                <div class="invalid-feedback d-block" id="rekening_id-error"></div>
                            </div>
                        </div>

                        <!-- inline new rekening -->
                        <div id="rekeningInline" class="pj-inline-box" style="display:none;">
                            <div class="mb-2"><small class="text-muted">Rekening baru akan langsung dipilih setelah disimpan.</small></div>
                            <div class="form-row">
                                <div class="col-md-3 mb-2"><input type="text" id="rek_bank_inline" class="form-control" placeholder="Bank"></div>
                                <div class="col-md-3 mb-2"><input type="text" id="rek_no_inline" class="form-control" placeholder="No. Rekening"></div>
                                <div class="col-md-3 mb-2"><input type="text" id="rek_atas_inline" class="form-control" placeholder="Atas Nama"></div>
                                <div class="col-md-3 mb-2 d-flex">
                                    <button type="button" id="saveRekeningInline" class="btn btn-primary mr-2"><i class="fa fa-save mr-1"></i>Simpan</button>
                                    <button type="button" class="btn btn-light" id="btnCancelRekInline"><i class="fa fa-times mr-1"></i>Batal</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Rincian item -->
                    <div class="pj-section">
                        <div class="d-flex flex-wrap align-items-center justify-content-between mb-2">
                            <div class="pj-section-title mb-0"><span class="pj-step">2</span> Rincian Item</div>
                            <div class="pj-faktur-pick">
                                <select id="select_faktur_inline" class="form-control form-control-sm" style="width:300px;" data-placeholder="Ambil dari faktur pembelian..."></select>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm mb-0" id="itemsTable">
                                <thead class="thead-light">
                                    <tr>
                                        <th style="width:36px" class="text-center">#</th>
                                        <th>Nama Item <span class="text-danger">*</span></th>
                                        <th style="width:22%">Catatan</th>
                                        <th style="width:80px">Qty</th>
                                        <th style="width:16%">Harga</th>
                                        <th style="width:16%" class="text-right">Subtotal</th>
                                        <th style="width:84px"></th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                        <div class="invalid-feedback d-block" id="items_json-error"></div>
                        <div class="d-flex flex-wrap align-items-center justify-content-between mt-2">
                            <button type="button" id="addItemRow" class="btn btn-sm btn-outline-success"><i class="fa fa-plus mr-1"></i>Tambah Item</button>
                            <div class="pj-grand-total">
                                <span class="text-muted mr-2">Total Pengajuan</span>
                                <input type="text" id="grand_total_display" class="pj-grand-total-value" readonly tabindex="-1">
                            </div>
                        </div>
                    </div>

                    <!-- 3. Bukti transaksi -->
                    <div class="pj-section mb-0">
                        <div class="pj-section-title"><span class="pj-step">3</span> Bukti Transaksi / Invoice <small class="text-muted font-weight-normal">(opsional, bisa diupload nanti)</small></div>
                        <input type="file" class="d-none" id="bukti_transaksi" name="bukti_transaksi[]" accept="image/*" multiple>
                        <div class="d-flex flex-wrap align-items-center">
                            <button type="button" class="btn btn-outline-secondary mr-2" id="btnChooseBukti"><i class="fa fa-upload mr-1"></i>Pilih Gambar</button>
                            <input type="text" id="bukti_files_label" class="form-control-plaintext text-muted" style="width:auto; flex:1;" readonly placeholder="Belum ada file dipilih" tabindex="-1">
                        </div>
                        <small class="text-muted d-block mt-1" id="buktiHint">jpg / png / gif, maks 2MB per file, maks 10 file.</small>
                        <div class="invalid-feedback d-block" id="bukti_transaksi-error"></div>
                        <div id="bukti_preview" class="mt-2" style="display:none"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" id="btnCancelPengajuan"><i class="fa fa-times mr-1"></i>Batal</button>
                    <button type="submit" id="savePengajuan" class="btn btn-primary"><i class="fa fa-save mr-1"></i>Simpan Pengajuan</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script src="{{ asset('dastone/vendor/datatable/FixedColumns-4.3.0/js/dataTables.fixedColumns.min.js') }}"></script>
<script>
$(document).ready(function() {
    function setBuktiModalMode(mode) {
        var isViewOnly = mode === 'view';
        $('#buktiModal').data('mode', mode);
        $('#buktiModalLabel').text(isViewOnly ? 'Lihat Bukti Transaksi' : 'Upload Bukti Transaksi');
        $('#buktiModalUploadSection, #buktiModalUploadDivider').toggle(!isViewOnly);
        $('#buktiModalUpload').toggle(!isViewOnly);
        if (isViewOnly) {
            $('#buktiModalInput').val('');
            $('#buktiModalFilesLabel').text('Tidak ada file terpilih');
            $('#buktiModalNewPreview').empty();
        }
    }

    function openBuktiModal(id, mode) {
        if (!id) return;

        setBuktiModalMode(mode || 'upload');
        $('#buktiModal').data('id', id);
        $('#buktiModalPreview').empty();
        $('#buktiModalInput').val('');
        $('#buktiModalFilesLabel').text('Tidak ada file terpilih');
        $('#buktiModalNewPreview').empty();

        $.ajax({
            url: '/finance/pengajuan-dana/' + id,
            method: 'GET',
            success: function(res){
                if (!res) return;
                var preview = $('#buktiModalPreview');
                preview.empty();

                function appendBuktiItem(path, index) {
                    if (!path) return;

                    var normalizedPath = String(path).replace(/^\/+/, '');
                    var imageUrl = '/storage/' + normalizedPath;
                    var downloadUrl = '/finance/pengajuan-dana/' + id + '/download-bukti/' + index;
                    var card = $('<div class="d-inline-flex flex-column align-items-center mr-2 mb-2"></div>');
                    var image = $('<img>').attr('src', imageUrl);
                    var downloadButton = $('<a class="btn btn-sm btn-outline-primary mt-2" target="_blank"><i class="fa fa-download mr-1"></i>Download</a>')
                        .attr('href', downloadUrl);

                    card.append(image).append(downloadButton);
                    preview.append(card);
                }

                try {
                    var arr = null;
                    if (res.bukti_transaksi) {
                        arr = (typeof res.bukti_transaksi === 'string') ? JSON.parse(res.bukti_transaksi) : res.bukti_transaksi;
                    }
                    if (Array.isArray(arr)) {
                        arr.forEach(function(p, index){
                            appendBuktiItem(p, index);
                        });
                    } else if (res.bukti_transaksi) {
                        appendBuktiItem(res.bukti_transaksi, 0);
                    }
                } catch(e) {
                    if (res.bukti_transaksi) {
                        appendBuktiItem(res.bukti_transaksi, 0);
                    }
                }

                if (!preview.children().length) {
                    preview.append('<div class="text-muted">Belum ada bukti transaksi.</div>');
                }

                $('#buktiModal').modal('show');
            },
            error: function(){ Swal.fire('Error', 'Gagal memuat bukti', 'error'); }
        });
    }

    function openBuktiZoom(src) {
        if (!src) return;
        $('#buktiZoomImage').attr('src', src);
        $('#buktiZoomModal').modal('show');
    }

    // server-provided current employee id (logged-in user) to default main employee select
    var __currentEmployeeId = '{{ auth()->check() && optional(auth()->user()->employee)->id ? auth()->user()->employee->id : '' }}';
    if (typeof $.fn.select2 === 'function') {
        // #employee_id is a hidden input for regular users (fixed to themselves), so only enhance real selects
        $('select#employee_id, select#rekening_id').select2({ dropdownParent: $('#pengajuanModal'), width: '100%' });
    }

    // Make pengajuan modal only closable via the X button (no backdrop click, no ESC)
    // Keep show:false so we control when it is shown; subsequent .modal('show') calls will respect these options.
    if (typeof $('#pengajuanModal').modal === 'function') {
        $('#pengajuanModal').modal({ backdrop: 'static', keyboard: false, show: false });
    }

    // Date range filter (same setup as billing page). Empty by default = show all dates.
    var filterStartDate = '';
    var filterEndDate = '';
    if (typeof $.fn.daterangepicker === 'function' && typeof moment !== 'undefined') {
        $('#filter_tanggal').daterangepicker({
            autoUpdateInput: false,
            startDate: moment(),
            endDate: moment(),
            opens: 'left',
            locale: {
                format: 'DD MMMM YYYY',
                applyLabel: 'Pilih',
                cancelLabel: 'Batal',
                fromLabel: 'Dari',
                toLabel: 'Hingga',
                customRangeLabel: 'Custom Range',
                weekLabel: 'W',
                daysOfWeek: ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'],
                monthNames: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
                firstDay: 1
            },
            ranges: {
               'Hari Ini': [moment(), moment()],
               'Kemarin': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
               'Minggu Ini': [moment().startOf('week'), moment().endOf('week')],
               'Bulan Ini': [moment().startOf('month'), moment().endOf('month')],
               'Bulan Lalu': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
            }
        });
        // apply on every "Pilih" (also when re-picking the same range)
        $('#filter_tanggal').on('apply.daterangepicker', function(ev, picker) {
            filterStartDate = picker.startDate.format('YYYY-MM-DD');
            filterEndDate = picker.endDate.format('YYYY-MM-DD');
            $(this).val(picker.startDate.format('DD MMMM YYYY') + ' - ' + picker.endDate.format('DD MMMM YYYY'));
            table.ajax.reload();
        });
    }

    var table = $('#pengajuanTable').DataTable({
        processing: true,
        serverSide: true,
        // wait until the user stops typing instead of one request per keystroke
        searchDelay: 400,
        autoWidth: false,
        // Horizontal scroll with No + No Pengajuan pinned left and Action pinned right (same as billing)
        scrollX: true,
        scrollCollapse: true,
        fixedColumns: {
            left: 2,
            right: 1
        },
        columnDefs: [
            { targets: 0, width: '32px', className: 'col-no' },
            { targets: 1, width: '140px' },                                        // No Pengajuan
            { targets: 2, width: '200px' },                                        // Tanggal Diajukan (date nowrap)
            { targets: 3, width: '300px', className: 'items-list-cell' },         // Rincian Item
            { targets: 4, width: '150px', className: 'text-end grand-total-cell' }, // Total (nowrap)
            { targets: 5, width: '180px' },                                        // Rekening Tujuan
            { targets: 6, width: '160px' },                                        // Diajukan ke
            { targets: 7, width: '190px' },
            { targets: 8, width: '170px', className: 'payment-cell' },
            { targets: 9, width: '46px', className: 'text-center' },
            // Action width follows its (non-wrapping) buttons
            { targets: 10, className: 'actions-cell' }
        ],
        ajax: {
            url: '{!! route('finance.pengajuan.data') !!}',
            data: function(d) {
                // include date range filter parameters
                d.start_date = filterStartDate;
                d.end_date = filterEndDate;
                // include jenis, sumber_dana and approval status filters
                d.jenis = $('#filter_jenis').val() || '';
                d.sumber_dana = $('#filter_sumber').val() || '';
                var approval = $('#filter_approval').val();
                d.approval_status = approval || 'menunggu';
            }
        },
        columns: [
            // render a sequential row number instead of DB id
            { data: 'id', name: 'id', render: function(data, type, row, meta) {
                    return meta.settings._iDisplayStart + meta.row + 1;
                }
            },
        { data: 'kode_pengajuan', name: 'kode_pengajuan', orderable: true, orderData: [2], render: function(data, type) {
                return type === 'display' ? '<div class="cell-main">' + $('<div>').text(data || '').html() + '</div>' : data;
            }
        },
        // format tanggal_pengajuan as '1 Januari 2025' (Indonesian) with the pengaju name below it
        { data: 'tanggal_pengajuan', name: 'tanggal_pengajuan', render: function(data, type, row, meta) {
                    // keep raw data for ordering/searching; only format for display
                    if (type !== 'display') return data || '';
                    var tgl = '';
                    if (data) {
                        var d = new Date(data);
                        tgl = isNaN(d.getTime()) ? data : d.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
                    }
                    var html = '<div class="cell-main text-nowrap">' + $('<div>').text(tgl).html() + '</div>';
                    if (row.employee_name) {
                        html += '<div><small class="text-muted">oleh : ' + $('<div>').text(row.employee_name).html() + '</small></div>';
                    }
                    return html;
                }
            },
            { data: 'items_list', name: 'items_list', orderable: false, searchable: false },
            { data: 'grand_total', name: 'grand_total', render: function(data, type, row, meta) {
                    if (data === null || data === undefined) data = 0;
                    if (type === 'display' || type === 'filter') {
                        try {
                            var n = Number(data);
                            // Prefix Indonesian Rupiah symbol and format number
                            var html = '<span class="cell-main">Rp ' + n.toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 }) + '</span>';
                            // paid amount when it differs from the request (partial / closed with a difference)
                            var paid = Number(row.total_dibayar || 0);
                            if (type === 'display' && paid > 0 && Math.abs(paid - n) >= 0.01) {
                                html += '<div><small class="text-muted text-nowrap">Dibayar Rp ' + paid.toLocaleString('id-ID', { maximumFractionDigits: 0 }) + '</small></div>';
                            }
                            return html;
                        } catch (e) {
                            return data;
                        }
                    }
                    // raw data used for ordering/searching
                    return data;
                }, orderable: false, searchable: false },
            { data: 'rekening_display', name: 'rekening_display', defaultContent: '', orderable: false, searchable: false },
            { data: 'diajukan_ke', name: 'diajukan_ke', orderable: false, searchable: false, className: 'diajukan-cell' },
            // server returns rendered HTML list for approvals (approver name + date)
            { data: 'approvals_list', name: 'approvals_list', orderable: false, searchable: false, className: 'approvals-cell' },
            { data: 'payment_status_display', name: 'payment_status_display', orderable: false, searchable: false },
            // selectable checkbox — enabled only if current row is approvable (detect by presence of approve button in actions HTML)
            { data: null, orderable: false, searchable: false, render: function(data, type, row){
                    var id = row.id;
                    var canApprove = (row.actions && row.actions.indexOf('approve-pengajuan') !== -1);
                    var disabled = canApprove ? '' : 'disabled';
                    return '<input type="checkbox" class="row-select-checkbox" data-id="'+id+'" '+disabled+' />';
                }
            },
            { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'actions-cell' }
        ],
        createdRow: function(row, data, dataIndex) {
            try {
            // items_list is at column index 3 — mark it so CSS can constrain it
            $(row).find('td').eq(3).addClass('items-list-cell');
            // mark first cell as 'col-no' to apply narrow styling
            $(row).find('td').eq(0).addClass('col-no');

                var $kodeCell = $(row).find('td').eq(1);
                // (missing-bukti warning is rendered server-side as a badge on the Detail/Edit button)
                // show jenis_pengajuan as small text under the No Pengajuan cell
                var jenis = data.jenis_pengajuan || '';
                if (jenis && $kodeCell.find('.jenis-text').length === 0) {
                    var jenisColor = '#6c757d';
                    var k = jenis.toString().toLowerCase();
                    if (k.indexOf('operasional') !== -1) jenisColor = '#007bff';
                    else if (k.indexOf('pembelian') !== -1) jenisColor = '#28a745';
                    else if (k.indexOf('inkaso') !== -1 || k.indexOf('pembayaran') !== -1) jenisColor = '#d39e00'; // darker amber, readable as text
                    var $jenis = $('<div class="jenis-text"><small style="font-weight:600;"></small></div>');
                    $jenis.find('small').css('color', jenisColor).text(jenis);
                    $kodeCell.append($jenis);
                }
                // If approvals list empty, show blinking warning badge in approvals column
                var approvalsRaw = (data.approvals_list || '').toString().trim();
                var $approvalsCell = $(row).find('td.approvals-cell').first();
                if ($approvalsCell.length) {
                    if (!approvalsRaw) {
                        var warnHtml = '<div class="jenis-badge"><small><span class="badge badge-warning approvals-empty"><i class="fa fa-exclamation-triangle"></i> Menunggu Persetujuan</span></small></div>';
                        $approvalsCell.html(warnHtml);
                    }
                }

                // Edit vs Detail button is decided server-side (editable until approved, or after declined)
            } catch(e) {}
        },
        // tanggal_pengajuan is at column index 2 (0-based), so order by that
        order: [[2, 'desc']]
    });

    // Status tabs (same pattern as Billing): switch the approval filter and reload
    $('#pengajuanTabs').on('click', '.nav-link', function(e) {
        e.preventDefault();
        if ($(this).hasClass('active')) return;
        $('#pengajuanTabs .nav-link').removeClass('active');
        $(this).addClass('active');
        $('#filter_approval').val($(this).data('status')).trigger('change');
    });

    // Tab badges: pending approvals, and approved but not yet paid (counts come with each table response)
    table.on('xhr.dt', function(e, settings, json) {
        var counts = (json && json.tab_counts) || {};
        var setBadge = function($badge, n) {
            n = parseInt(n || 0, 10);
            $badge.text(n).toggle(n > 0);
        };
        setBadge($('#pj-tab-badge-menunggu'), counts.menunggu);
        setBadge($('#pj-tab-badge-approved'), counts.siap_bayar);
    });

    function formatNominal(n) {
        return 'Rp ' + Number(n || 0).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
    }

    // Checked rows with their table data (id, kode, nominal) so the approver sees what they approve
    function getSelectedPengajuan(){
        var rows = [];
        $('#pengajuanTable tbody .row-select-checkbox:checked').each(function(){
            var data = table.row($(this).closest('tr')).data() || {};
            rows.push({
                id: $(this).data('id'),
                kode: data.kode_pengajuan || '-',
                pengaju: data.employee_name || '',
                total: Number(data.grand_total || 0)
            });
        });
        return rows;
    }

    // Helper to toggle bulk approve button + selection summary (count & total nominal)
    function updateBulkApproveVisibility(){
        var selected = getSelectedPengajuan();
        var total = selected.reduce(function(sum, r){ return sum + r.total; }, 0);
        $('#bulkSelectionCount').text(selected.length);
        $('#bulkSelectionTotal').text(formatNominal(total));
        $('#btnBulkApprove, #bulkSelectionSummary').toggle(selected.length > 0);
    }

    // Reset select-all state on each draw and recalc visibility
    table.on('draw', function(){
        $('#select_all_rows').prop('checked', false);
        updateBulkApproveVisibility();
    });

    // ===================== Pengajuan form (create / edit) =====================
    var pengajuanSaving = false;   // blocks double submit (double click / Enter) while a request is running
    var pengajuanEditing = false;

    // local date for <input type="date"> (toISOString() is UTC and gives yesterday before 07:00 WIB)
    function localToday() {
        var d = new Date();
        return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    }

    // one-time token per opened form; the server rejects a second submit with the same token
    function newSubmitToken() {
        if (window.crypto && typeof window.crypto.randomUUID === 'function') return window.crypto.randomUUID();
        return Date.now() + '-' + Math.random().toString(36).slice(2) + Math.random().toString(36).slice(2);
    }

    function clearFormErrors() {
        $('#pengajuanForm .is-invalid').removeClass('is-invalid');
        $('#pengajuanForm .select2-invalid').removeClass('select2-invalid');
        $('#pengajuanForm .invalid-feedback').text('');
    }

    function showFieldError(field, message) {
        var $el = $('#' + field);
        $el.addClass($el.hasClass('select2-hidden-accessible') ? 'select2-invalid' : 'is-invalid');
        $('#' + field + '-error').text(message);
    }

    function setEmployee(value) {
        var $emp = $('#employee_id');
        if ($emp.is('select')) {
            $emp.val(value || '').trigger('change');
        } else if (value) {
            $emp.val(value); // regular users: fixed to themselves
        }
    }

    function resetPengajuanForm() {
        $('#pengajuanForm')[0].reset();
        clearFormErrors();
        pengajuanEditing = false;
        pengajuanSaving = false;
        $('#pengajuan_id').val('');
        $('#division_id').val('');
        $('#items_json').val('');
        $('#submit_token').val(newSubmitToken());
        setEmployee(__currentEmployeeId);
        $('#rekening_id').val('').trigger('change');
        $('#rekeningInline').hide();
        $('#tanggal_pengajuan').val(localToday());
        $('#bukti_transaksi').val('');
        $('#bukti_files_label').val('');
        $('#bukti_preview').empty().hide();
        $('#buktiHint').text('jpg / png / gif, maks 2MB per file, maks 10 file.');
        $('#itemsTable tbody').empty();
        addItemRow(null, true);
        $('#pengajuanModalLabel').text('Buat Pengajuan Dana');
        $('#pengajuanKodeInfo').text('No. pengajuan dibuat otomatis saat disimpan');
        $('#savePengajuan').prop('disabled', false).html('<i class="fa fa-save mr-1"></i>Simpan Pengajuan');
    }

    // Open modal for create
    $('#btnAddPengajuan').on('click', function() {
        resetPengajuanForm();
        $('#pengajuanModal').modal('show');
    });

    // clear everything when the modal closes so no stale data leaks into the next pengajuan
    $('#pengajuanModal').on('hidden.bs.modal', function () {
        resetPengajuanForm();
    });

    $(document).on('click', '#btnCancelPengajuan', function() {
        $('#pengajuanModal').modal('hide');
    });

    // ----- bukti (optional) -----
    function renderBuktiPreview(srcs) {
        var $preview = $('#bukti_preview').empty();
        srcs.forEach(function(src) { $preview.append($('<img>').attr('src', src)); });
        $preview.toggle(srcs.length > 0);
    }

    $(document).on('click', '#btnChooseBukti', function(){
        $('#bukti_transaksi').trigger('click');
    });

    $('#bukti_transaksi').on('change', function() {
        var files = Array.from(this.files || []);
        $('#bukti_transaksi-error').text('');
        if (!files.length) {
            $('#bukti_files_label').val('');
            renderBuktiPreview([]);
            return;
        }
        // early feedback; the server validates again
        var tooBig = files.filter(function(f) { return f.size > 2 * 1024 * 1024; }).map(function(f) { return f.name; });
        if (files.length > 10) {
            $('#bukti_transaksi-error').text('Maksimal 10 file bukti.');
        } else if (tooBig.length) {
            $('#bukti_transaksi-error').text('Lebih dari 2MB: ' + tooBig.join(', '));
        }
        $('#bukti_files_label').val(files.length === 1 ? files[0].name : files.length + ' file dipilih');
        var srcs = [];
        files.forEach(function(file) {
            if (!file.type || file.type.indexOf('image') === -1) return;
            var reader = new FileReader();
            reader.onload = function(evt) {
                srcs.push(evt.target.result);
                renderBuktiPreview(srcs);
            };
            reader.readAsDataURL(file);
        });
    });

    // ----- items -----
    function parseRupiah(value) {
        if (value === null || value === undefined) return 0;
        var normalized = value.toString().replace(/[^\d,.-]/g, '').replace(/\./g, '').replace(',', '.');
        var parsed = parseFloat(normalized);
        return isNaN(parsed) ? 0 : parsed;
    }

    function formatRupiah(value) {
        var amount = Number(value || 0);
        if (isNaN(amount)) amount = 0;
        return 'Rp ' + amount.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function setRupiahValue($input, value) {
        $input.val(formatRupiah(value));
    }

    function formatEditableRupiah(value) {
        var amount = Number(value || 0);
        if (isNaN(amount) || amount === 0) return '';
        var fixed = amount.toFixed(2);
        if (fixed.slice(-3) === '.00') return fixed.slice(0, -3);
        return fixed.replace('.', ',');
    }

    function sanitizeEditableRupiah(raw) {
        if (raw === null || raw === undefined) return '';
        // no minus sign: prices cannot be negative
        var cleaned = raw.toString().replace(/\s+/g, '').replace(/^rp/i, '').replace(/-/g, '');
        if (cleaned.indexOf(',') !== -1) {
            var parts = cleaned.split(',');
            var integerPart = (parts.shift() || '').replace(/[^\d]/g, '');
            var decimalPart = parts.join('').replace(/[^\d]/g, '').slice(0, 2);
            if (integerPart === '' && decimalPart === '') return '';
            return integerPart + (decimalPart !== '' ? ',' + decimalPart : ',');
        }
        return cleaned.replace(/[^\d]/g, '');
    }

    // qty left empty while a price is filled counts as 1
    function getEffectiveQty($tr) {
        var qty = parseInt(($tr.find('.item-qty').val() || '').toString().trim(), 10);
        if (qty > 0) return qty;
        return parseRupiah($tr.find('.item-price').val() || 0) > 0 ? 1 : 0;
    }

    function recalcItems() {
        var grand = 0;
        $('#itemsTable tbody tr').each(function(i, tr) {
            var $tr = $(tr);
            var total = getEffectiveQty($tr) * parseRupiah($tr.find('.item-price').val() || 0);
            setRupiahValue($tr.find('.item-total'), total);
            grand += total;
            $tr.find('.item-no').text(i + 1);
        });
        setRupiahValue($('#grand_total_display'), grand);
    }

    // faktur rows are read-only: their total comes from the faktur (the server re-reads it)
    function markFakturRow($tr, fakturId, desc) {
        $tr.addClass('pj-faktur-row').data('fakturbeli-id', fakturId);
        $tr.find('.item-desc').prop('readonly', true).attr('title', desc || '');
        $tr.find('.item-qty').val(1).prop('readonly', true);
        $tr.find('.item-price').prop('readonly', true);
    }

    function clearItemRow($tr) {
        $tr.removeClass('pj-faktur-row is-invalid').removeData('fakturbeli-id');
        $tr.find('.item-desc, .item-notes, .item-qty, .item-price').prop('readonly', false).val('').removeClass('is-invalid').removeAttr('title');
    }

    function addItemRow(data, skipFocus) {
        data = data || {};
        var $tr = $('<tr>');
        // values are set with .val() so quotes/HTML in item names cannot break the markup
        $tr.append('<td class="text-center text-muted item-no"></td>');
        $tr.append($('<td>').append($('<input type="text" class="form-control item-desc" placeholder="Nama item">').val(data.desc || '')));
        $tr.append($('<td>').append($('<input type="text" class="form-control item-notes" placeholder="Opsional">').val(data.notes || '')));
        $tr.append($('<td>').append($('<input type="number" min="1" step="1" class="form-control item-qty" placeholder="1">').val(data.qty || '')));
        $tr.append($('<td>').append($('<input type="text" inputmode="decimal" class="form-control item-price" placeholder="Rp 0">').val(data.price ? formatRupiah(data.price) : '')));
        $tr.append('<td><input type="text" readonly tabindex="-1" class="form-control item-total"></td>');
        $tr.append('<td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-item" title="Hapus item"><i class="fa fa-trash mr-1"></i>Hapus</button></td>');
        if (data.fakturbeli_id) markFakturRow($tr, data.fakturbeli_id, data.desc);
        $('#itemsTable tbody').append($tr);
        if (!skipFocus) $tr.find('.item-desc').focus();
        recalcItems();
        return $tr;
    }

    // initial one row
    addItemRow(null, true);

    $(document).on('click', '#addItemRow', function(){ addItemRow(); });

    // the last row is cleared instead of removed so there is always a row to type in
    $(document).on('click', '.remove-item', function(){
        var $tr = $(this).closest('tr');
        if ($('#itemsTable tbody tr').length <= 1) {
            clearItemRow($tr);
            recalcItems();
            return;
        }
        $tr.remove();
        recalcItems();
    });

    $(document).on('input', '.item-qty', function(){ recalcItems(); });
    $(document).on('input', '.item-desc', function(){ $(this).removeClass('is-invalid'); });

    $(document).on('input', '.item-price', function(){
        $(this).val(sanitizeEditableRupiah($(this).val()));
        recalcItems();
    });

    $(document).on('focus', '.item-price', function(){
        if ($(this).prop('readonly')) return;
        var value = parseRupiah($(this).val());
        $(this).val(value === 0 ? '' : formatEditableRupiah(value));
    });

    $(document).on('blur', '.item-price', function(){
        if ($(this).prop('readonly')) return;
        var value = parseRupiah($(this).val());
        $(this).val(value > 0 ? formatRupiah(value) : '');
        recalcItems();
    });

    // keyboard: Enter moves desc -> qty -> price, Enter on the last price adds a new row
    $(document).on('keydown', '#itemsTable .item-desc, #itemsTable .item-notes, #itemsTable .item-qty', function(e){
        if (e.key !== 'Enter') return;
        e.preventDefault();
        var $inputs = $(this).closest('tr').find('.item-desc, .item-notes, .item-qty, .item-price').filter(':not([readonly])');
        var idx = $inputs.index(this);
        if (idx >= 0 && idx < $inputs.length - 1) $inputs.eq(idx + 1).focus();
    });
    $(document).on('keydown', '#itemsTable .item-price', function(e){
        if (e.key !== 'Enter') return;
        e.preventDefault();
        var $next = $(this).closest('tr').next('tr');
        if ($next.length) $next.find('.item-desc').focus(); else addItemRow();
    });

    // ----- save -----
    $('#pengajuanForm').on('submit', function(e) {
        e.preventDefault();
        if (pengajuanSaving) return;
        clearFormErrors();

        // collect items; untouched blank rows are ignored
        var items = [];
        var itemErrors = [];
        $('#itemsTable tbody tr').each(function(i){
            var $tr = $(this);
            var desc = ($tr.find('.item-desc').val() || '').toString().trim();
            var notes = ($tr.find('.item-notes').val() || '').toString().trim();
            var qtyRaw = ($tr.find('.item-qty').val() || '').toString().trim();
            var price = parseRupiah($tr.find('.item-price').val() || 0);
            var fakturId = $tr.data('fakturbeli-id') || null;
            if (desc === '' && notes === '' && qtyRaw === '' && price === 0) return;
            var qty = fakturId ? 1 : getEffectiveQty($tr);
            if (desc === '') {
                $tr.find('.item-desc').addClass('is-invalid');
                itemErrors.push('baris ' + (i + 1) + ': nama item kosong');
                return;
            }
            if (qty <= 0) {
                $tr.find('.item-qty').addClass('is-invalid');
                itemErrors.push('baris ' + (i + 1) + ': qty belum diisi');
                return;
            }
            items.push({ desc: desc, qty: qty, price: price, notes: notes || null, fakturbeli_id: fakturId });
        });
        $('#items_json').val(JSON.stringify(items));

        var missing = [];
        function requireField(id, label) {
            if (!($('#' + id).val() || '').toString().trim()) {
                showFieldError(id, label + ' wajib diisi');
                missing.push(label);
            }
        }
        requireField('employee_id', 'Nama Pengaju');
        requireField('tanggal_pengajuan', 'Tanggal');
        requireField('jenis_pengajuan', 'Jenis Pengajuan');
        requireField('sumber_dana', 'Sumber Dana');
        requireField('perusahaan', 'Perusahaan');
        requireField('rekening_id', 'Rekening Tujuan');
        if (!items.length && !itemErrors.length) itemErrors.push('isi minimal 1 item');
        if (itemErrors.length) {
            $('#items_json-error').text('Rincian item: ' + itemErrors.join('; '));
            missing.push('Rincian Item');
        }
        if ($('#bukti_transaksi-error').text()) missing.push('Bukti');

        if (missing.length) {
            Swal.fire({ icon: 'warning', title: 'Data belum lengkap', html: 'Periksa: <b>' + missing.join(', ') + '</b>' });
            return;
        }

        var id = $('#pengajuan_id').val();
        var formData = new FormData(this);
        if (id) formData.append('_method', 'PUT');

        pengajuanSaving = true;
        $('#savePengajuan').prop('disabled', true).html('<i class="fa fa-spinner fa-spin mr-1"></i>Menyimpan...');
        $.ajax({
            url: id ? ('/finance/pengajuan-dana/' + id) : '/finance/pengajuan-dana',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function(res) {
                $('#pengajuanModal').modal('hide');
                table.ajax.reload(null, false);
                Swal.fire({ icon: 'success', title: 'Berhasil', text: res.message || 'Pengajuan tersimpan', timer: 2200, showConfirmButton: false });
            },
            error: function(xhr) {
                var res = xhr.responseJSON || {};
                if (xhr.status === 422 && res.errors) {
                    // show each server message under its field
                    Object.keys(res.errors).forEach(function(key) {
                        showFieldError(key.split('.')[0], res.errors[key][0]);
                    });
                    Swal.fire('Data belum valid', res.message || 'Periksa kembali isian yang ditandai merah.', 'warning');
                } else if (xhr.status === 409) {
                    // already submitted (double click / retried request): the first one was saved
                    $('#pengajuanModal').modal('hide');
                    table.ajax.reload(null, false);
                    Swal.fire('Sudah tersimpan', res.message || 'Pengajuan ini sudah terkirim.', 'info');
                } else if (xhr.status === 403) {
                    // e.g. someone approved it while it was being edited
                    $('#pengajuanModal').modal('hide');
                    table.ajax.reload(null, false);
                    Swal.fire('Tidak Diizinkan', res.message || 'Anda tidak diizinkan melakukan aksi ini.', 'error');
                } else {
                    Swal.fire('Error', res.message || 'Terjadi kesalahan pada server. Silakan coba lagi.', 'error');
                }
            },
            complete: function() {
                pengajuanSaving = false;
                $('#savePengajuan').prop('disabled', false).html('<i class="fa fa-save mr-1"></i>Simpan Pengajuan');
            }
        });
    });

    // ----- new rekening (inline) -----
    $(document).on('click', '#btnToggleRekInline', function() {
        $('#rekeningInline').slideToggle(120, function() {
            if ($(this).is(':visible')) $('#rek_bank_inline').focus();
        });
    });

    $(document).on('click', '#btnCancelRekInline', function() {
        $('#rekeningInline').slideUp(120);
    });

    $(document).on('click', '#saveRekeningInline', function(e) {
        e.preventDefault();
        var btn = $(this);
        var payload = {
            bank: $.trim($('#rek_bank_inline').val()),
            no_rekening: $.trim($('#rek_no_inline').val()),
            atas_nama: $.trim($('#rek_atas_inline').val())
        };
        if (!payload.bank || !payload.no_rekening || !payload.atas_nama) {
            Swal.fire('Data belum lengkap', 'Isi Bank, No. Rekening, dan Atas Nama.', 'warning');
            return;
        }
        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin mr-1"></i>Menyimpan...');
        $.ajax({
            url: '{{ route('finance.rekening.store') }}',
            method: 'POST',
            data: payload,
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function(res) {
                $('#rekeningInline').slideUp(120);
                $('#rek_bank_inline, #rek_no_inline, #rek_atas_inline').val('');
                // add to the dropdown and select it
                var opt = new Option(res.data.bank + ' / ' + (res.data.no_rekening || '') + ' / ' + (res.data.atas_nama || ''), res.data.id, true, true);
                $('#rekening_id').append(opt).trigger('change');
                $('#rekening_id').removeClass('select2-invalid');
                $('#rekening_id-error').text('');
            },
            error: function(xhr) {
                var errors = (xhr.responseJSON && xhr.responseJSON.errors) || {};
                var msgs = Object.keys(errors).map(function(k){ return errors[k][0]; }).join('\n');
                Swal.fire(xhr.status === 422 ? 'Validasi' : 'Error', msgs || 'Gagal menambahkan rekening', xhr.status === 422 ? 'warning' : 'error');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fa fa-save mr-1"></i>Simpan');
            }
        });
    });

    // ----- edit -----
    $('#pengajuanTable').on('click', '.edit-pengajuan', function() {
        var id = $(this).data('id');
        $.get('/finance/pengajuan-dana/' + id).done(function(res) {
            resetPengajuanForm();
            pengajuanEditing = true;
            var declined = (res.approvals || []).some(function(a) { return a.status === 'declined' || a.status === 'rejected'; });
            $('#pengajuanModalLabel').text('Edit Pengajuan Dana');
            $('#pengajuanKodeInfo').text(res.kode_pengajuan + (declined ? ' · pengajuan ditolak, menyimpan akan mengajukan ulang' : ''));
            $('#pengajuan_id').val(res.id);
            setEmployee(res.employee_id);
            $('#division_id').val(res.division_id || '');
            $('#tanggal_pengajuan').val((res.tanggal_pengajuan || '').toString().slice(0, 10));
            $('#jenis_pengajuan').val(res.jenis_pengajuan || '');
            $('#sumber_dana').val(res.sumber_dana || '');
            $('#perusahaan').val(res.perusahaan || '');
            $('#rekening_id').val(res.rekening_id || '').trigger('change');

            // existing bukti (server sends a normalized array of paths)
            var bukti = Array.isArray(res.bukti_transaksi) ? res.bukti_transaksi : [];
            renderBuktiPreview(bukti.map(function(p) { return '/storage/' + String(p).replace(/^\/+/, ''); }));
            if (bukti.length) {
                $('#bukti_files_label').val(bukti.length + ' bukti tersimpan');
                $('#buktiHint').text('Memilih gambar baru akan menggantikan bukti yang tersimpan. jpg / png / gif, maks 2MB per file.');
            }

            $('#itemsTable tbody').empty();
            (res.items || []).forEach(function(it) {
                addItemRow({ desc: it.nama_item, qty: it.jumlah, price: it.harga_satuan, notes: it.notes || '', fakturbeli_id: it.fakturbeli_id }, true);
            });
            if (!(res.items || []).length) addItemRow(null, true);
            recalcItems();

            $('#pengajuanModal').modal('show');
        }).fail(function(xhr) {
            Swal.fire('Error', (xhr.responseJSON && xhr.responseJSON.message) || 'Gagal memuat data', 'error');
        });
    });

    // Auto-fill division when the pengaju is chosen (admins/approvers only; the server also infers it)
    $(document).on('change', 'select#employee_id', function() {
        var divId = $(this).find('option:selected').data('division-id');
        $('#division_id').val(divId || '');
        $(this).removeClass('select2-invalid');
        $('#employee_id-error').text('');
    });

    // clear a field's error as soon as it is changed
    $(document).on('change input', '#pengajuanForm select, #pengajuanForm input[type=date]', function() {
        $(this).removeClass('is-invalid select2-invalid');
        $('#' + this.id + '-error').text('');
    });

    // Delete pengajuan
    $('#pengajuanTable').on('click', '.delete-pengajuan', function() {
        var id = $(this).data('id');
        Swal.fire({
            title: 'Apakah Anda yakin?',
            text: "Data yang dihapus tidak dapat dikembalikan!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Ya, hapus!'
        }).then((result) => {
            if (result.value) {
                $.ajax({
                    url: '/finance/pengajuan-dana/' + id,
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    success: function(res) {
                        Swal.fire('Terhapus!', res.message || 'Data telah dihapus', 'success');
                        table.ajax.reload(null, false);
                    },
                    error: function(xhr) {
                        // 403 = locked (already approved/paid) -> show reason and refresh buttons
                        var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Gagal menghapus data';
                        if (xhr.status === 403) table.ajax.reload(null, false);
                        Swal.fire(xhr.status === 403 ? 'Tidak Diizinkan' : 'Error', msg, 'error');
                    }
                });
            }
        });
    });

    // Approve pengajuan (only visible to approvers; button rendered server-side)
    $('#pengajuanTable').on('click', '.approve-pengajuan', function() {
        var id = $(this).data('id');
        Swal.fire({
            title: 'Setujui pengajuan? ',
            text: 'Anda akan menandai pengajuan ini sebagai disetujui',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, setujui',
            cancelButtonText: 'Batal'
        }).then(function(result){
            if (!result.value) return;
            $.ajax({
                url: '/finance/pengajuan-dana/' + id + '/approve',
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                beforeSend: function(){
                    // optional UI feedback
                },
                success: function(res){
                    Swal.fire('Disetujui', res.message || 'Pengajuan telah disetujui', 'success');
                    table.ajax.reload(null, false);
                },
                error: function(xhr){
                    var msg = xhr.responseJSON && xhr.responseJSON.message;
                    if (msg) {
                        // rule violations (not your turn, already processed, declined, ...) come back as 403
                        table.ajax.reload(null, false);
                        Swal.fire('Gagal', msg, 'warning');
                    } else {
                        Swal.fire('Error', 'Terjadi kesalahan pada server', 'error');
                    }
                }
            });
        });
    });

    // Pay pengajuan (visible when fully approved and unpaid)
    // first validation message of a 422, otherwise the server message
    function ajaxErrorMessage(xhr, fallback) {
        var r = xhr.responseJSON || {};
        if (r.errors) {
            var k = Object.keys(r.errors)[0];
            if (k && r.errors[k] && r.errors[k][0]) return r.errors[k][0];
        }
        return r.message || fallback;
    }

    function nowLocalDatetime() {
        var d = new Date();
        d.setMinutes(d.getMinutes() - d.getTimezoneOffset());
        return d.toISOString().slice(0, 16);
    }

    // ----- Bayar: record a payment (full, partial, or closed with a difference) -----
    var bayarSisa = 0;

    function updateBayarKurang() {
        var nominal = parseRupiah($('#bayar_nominal').val());
        var kurang = nominal > 0 && (bayarSisa - nominal) >= 0.01;
        $('#bayar_kurang_box').toggle(kurang);
        $('#bayar_note_required').toggle(kurang);
        if (kurang) $('#bayar_selisih').text(formatNominal(bayarSisa - nominal));
    }

    $('#pengajuanTable').on('click', '.pay-pengajuan', function() {
        var id = $(this).data('id');
        $('#bayarForm')[0].reset();
        $('#bayar_id').val(id);
        $('#bayar_grand, #bayar_sudah, #bayar_sisa').text('...');
        $('#bayar_tanggal').val(nowLocalDatetime());
        $('#bayar_mode_partial').prop('checked', true);
        $('#bayar_kurang_box, #bayar_note_required').hide();
        $.get('/finance/pengajuan-dana/' + id, function(res) {
            bayarSisa = Number(res.sisa_bayar || 0);
            $('#bayarModalLabel').text('Bayar Pengajuan - ' + (res.kode_pengajuan || ''));
            $('#bayar_grand').text(formatNominal(res.grand_total));
            $('#bayar_sudah').text(formatNominal(res.total_dibayar));
            $('#bayar_sisa').text(formatNominal(bayarSisa));
            $('#bayar_nominal').val(formatNominal(bayarSisa));
            $('#bayarModal').modal('show');
        }).fail(function() {
            Swal.fire('Error', 'Gagal memuat data pengajuan', 'error');
        });
    });

    $('#bayar_nominal').on('input', updateBayarKurang).on('blur', function() {
        var n = parseRupiah($(this).val());
        $(this).val(n > 0 ? formatNominal(n) : '');
        updateBayarKurang();
    });

    $('#bayarForm').on('submit', function(e) {
        e.preventDefault();
        var id = $('#bayar_id').val();
        var nominal = parseRupiah($('#bayar_nominal').val());
        if (!(nominal > 0)) {
            Swal.fire('Validasi', 'Isi nominal dibayar', 'warning');
            return;
        }
        if (nominal - bayarSisa > 0.009) {
            Swal.fire('Validasi', 'Nominal melebihi sisa yang disetujui (' + formatNominal(bayarSisa) + ')', 'warning');
            return;
        }
        var kurang = (bayarSisa - nominal) >= 0.01;
        if (kurang && !$.trim($('#bayar_note').val())) {
            Swal.fire('Validasi', 'Catatan wajib diisi jika nominal kurang dari sisa', 'warning');
            return;
        }
        var fd = new FormData();
        fd.append('nominal', nominal);
        fd.append('tanggal_bayar', $('#bayar_tanggal').val() || '');
        fd.append('note', $('#bayar_note').val() || '');
        if (kurang) fd.append('mode', $('input[name="bayar_mode"]:checked').val());
        var file = $('#bayar_bukti')[0].files[0];
        if (file) fd.append('bukti', file);
        var $btn = $('#bayar_submit').prop('disabled', true);
        $.ajax({
            url: '/finance/pengajuan-dana/' + id + '/pay',
            method: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function(res) {
                $('#bayarModal').modal('hide');
                Swal.fire('Sukses', res.message || 'Pembayaran dicatat', 'success');
                table.ajax.reload(null, false);
            },
            error: function(xhr) {
                Swal.fire('Error', ajaxErrorMessage(xhr, 'Gagal menyimpan pembayaran'), 'error');
            },
            complete: function() { $btn.prop('disabled', false); }
        });
    });

    // links to uploaded files (payment bukti) on the public disk
    function fileLinks(paths, label) {
        return (paths || []).map(function(p, i) {
            return '<a href="/storage/' + encodeURI(String(p).replace(/^\/+/, '')) + '" target="_blank" class="mr-2"><i class="fa fa-paperclip"></i> ' + label + ' ' + (i + 1) + '</a>';
        }).join('');
    }

    // Select all toggle (current page only)
    $(document).on('change', '#select_all_rows', function(){
        var checked = $(this).is(':checked');
        $('#pengajuanTable tbody .row-select-checkbox:enabled').prop('checked', checked);
        updateBulkApproveVisibility();
    });

    // Per-row selection change -> update visibility
    $(document).on('change', '#pengajuanTable tbody .row-select-checkbox', function(){
        // if any enabled checkbox is unchecked, also uncheck select-all
        if (!$(this).is(':checked')) {
            $('#select_all_rows').prop('checked', false);
        }
        updateBulkApproveVisibility();
    });

    // Bulk approve handler
    $('#btnBulkApprove').on('click', function(){
        var selected = getSelectedPengajuan().filter(function(r){ return r.id; });
        var ids = selected.map(function(r){ return r.id; });
        if (ids.length === 0) {
            Swal.fire('Info', 'Pilih minimal satu pengajuan yang bisa Anda approve.', 'info');
            return;
        }
        // list each selected pengajuan with its nominal + grand total, so the approver can double-check
        var esc = function(s){ return $('<div>').text(s).html(); };
        var total = selected.reduce(function(sum, r){ return sum + r.total; }, 0);
        var listHtml = '<div class="text-left" style="max-height:260px; overflow-y:auto;">'
            + '<table class="table table-sm table-bordered mb-2" style="font-size:13px;">'
            + '<thead class="thead-light"><tr><th style="width:36px">#</th><th>No Pengajuan</th><th class="text-right">Nominal</th></tr></thead><tbody>'
            + selected.map(function(r, i){
                return '<tr><td>' + (i + 1) + '</td><td>' + esc(r.kode)
                    + (r.pengaju ? '<br><small class="text-muted">' + esc(r.pengaju) + '</small>' : '')
                    + '</td><td class="text-right text-nowrap">' + formatNominal(r.total) + '</td></tr>';
            }).join('')
            + '</tbody><tfoot><tr><th colspan="2" class="text-right">Total</th><th class="text-right text-nowrap">' + formatNominal(total) + '</th></tr></tfoot></table></div>'
            + '<small class="text-muted">Hanya yang memenuhi hirarki approval yang akan disetujui.</small>';
        Swal.fire({
            title: 'Approve '+ids.length+' pengajuan?',
            html: listHtml,
            width: 600,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Approve',
            cancelButtonText: 'Batal'
        }).then(function(res){
            if (!res.value) return;
            $.ajax({
                url: '{{ route('finance.pengajuan.bulk_approve') }}',
                method: 'POST',
                data: { ids: ids },
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                beforeSend: function(){ Swal.fire({ title:'Memproses...', allowOutsideClick:false, didOpen:() => Swal.showLoading() }); },
                success: function(resp){
                    Swal.close();
                    var ok = resp.approved_count || 0;
                    var skipped = (resp.skipped || []).length;
                    var errs = (resp.errors || []).length;
                    var msg = 'Disetujui: '+ok+'<br>Dilewati: '+skipped+'<br>Error: '+errs;
                    // group skip/error reasons so the approver knows why an item was not approved
                    var reasons = {};
                    (resp.skipped || []).concat(resp.errors || []).forEach(function(s){
                        var r = s.reason || 'Tidak diketahui';
                        reasons[r] = (reasons[r] || 0) + 1;
                    });
                    var reasonKeys = Object.keys(reasons);
                    if (reasonKeys.length) {
                        msg += '<div class="text-left mt-2"><small>' + reasonKeys.map(function(r){
                            return '&bull; ' + $('<div>').text(r).html() + ' (' + reasons[r] + ')';
                        }).join('<br>') + '</small></div>';
                    }
                    Swal.fire(ok > 0 ? 'Selesai' : 'Tidak ada yang disetujui', msg, ok > 0 ? 'success' : 'warning');
                    table.ajax.reload(null, false);
                    updateBulkApproveVisibility();
                },
                error: function(xhr){
                    Swal.fire('Error', (xhr.responseJSON && xhr.responseJSON.message) || 'Gagal memproses bulk approve', 'error');
                }
            });
        });
    });

    // Decline pengajuan: reason (alasan penolakan) is required
    $('#pengajuanTable').on('click', '.decline-pengajuan', function() {
        var id = $(this).data('id');
        Swal.fire({
            title: 'Tolak pengajuan?',
            text: 'Tuliskan alasan penolakan. Pengaju dapat memperbaiki lalu mengajukan ulang.',
            icon: 'warning',
            input: 'textarea',
            inputPlaceholder: 'Alasan penolakan...',
            inputAttributes: { maxlength: 1000 },
            inputValidator: function(value) {
                if (!value || !value.trim()) return 'Alasan penolakan wajib diisi';
            },
            showCancelButton: true,
            confirmButtonText: 'Tolak',
            confirmButtonColor: '#dc3545',
            cancelButtonText: 'Batal'
        }).then(function(result){
            if (!result.value) return; // cancelled
            $.ajax({
                url: '/finance/pengajuan-dana/' + id + '/decline',
                method: 'POST',
                data: { note: result.value.trim() },
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function(res){
                    Swal.fire('Ditolak', res.message || 'Pengajuan telah ditolak', 'success');
                    table.ajax.reload(null, false);
                },
                error: function(xhr){
                    var msg = xhr.responseJSON && xhr.responseJSON.message;
                    if (msg) {
                        // rule violations (not your turn, already processed, declined, ...) come back as 403
                        table.ajax.reload(null, false);
                        Swal.fire('Gagal', msg, 'warning');
                    } else {
                        Swal.fire('Error', 'Terjadi kesalahan pada server', 'error');
                    }
                }
            });
        });
    });

    $(document).on('click', '#buktiModalPreview img', function() {
        openBuktiZoom($(this).attr('src'));
    });

    $('#buktiZoomModal').on('hidden.bs.modal', function() {
        $('#buktiZoomImage').attr('src', '');
        // keep the underlying modal (e.g. detail) scrollable after closing the stacked zoom modal
        if ($('.modal.show').length) $('body').addClass('modal-open');
    });

    // file input change inside bukti modal - update label and preview of selected files
    $(document).on('change', '#buktiModalInput', function(e){
        var files = this.files || [];
        var $label = $('#buktiModalFilesLabel');
        var $preview = $('#buktiModalNewPreview');
        $preview.empty();
        if (!files || files.length === 0) {
            $label.text('Tidak ada file terpilih');
            return;
        }
        if (files.length === 1) $label.text(files[0].name); else $label.text(files.length + ' file terpilih');
        Array.from(files).forEach(function(file){ if (!file.type || file.type.indexOf('image') === -1) return; var reader = new FileReader(); reader.onload = function(evt){ var $img = $('<img>').attr('src', evt.target.result).css({'max-width':'120px','max-height':'90px','margin-right':'6px','margin-bottom':'6px'}); $preview.append($img); }; reader.readAsDataURL(file); });
    });

    // Upload files from Bukti modal
    $(document).on('click', '#buktiModalUpload', function(){
        var id = $('#buktiModal').data('id');
        if (!id) return;
        var input = document.getElementById('buktiModalInput');
        var files = input.files || [];
        if (!files || files.length === 0) {
            Swal.fire('Info', 'Pilih file terlebih dahulu', 'info');
            return;
        }
        var fd = new FormData();
        Array.from(files).forEach(function(f){ fd.append('bukti_transaksi[]', f); });
        $.ajax({
            url: '/finance/pengajuan-dana/' + id + '/upload-bukti',
            method: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            beforeSend: function(){ Swal.fire({ title: 'Uploading...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } }); },
            success: function(res){
                Swal.fire('Sukses', res.message || 'Upload berhasil', 'success');
                $('#buktiModal').modal('hide');
                table.ajax.reload(null, false);
            },
            error: function(xhr){ var msg = 'Terjadi kesalahan pada server'; if (xhr && xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message; Swal.fire('Error', msg, 'error'); }
        });
    });

    // Clear date filter button
    $(document).on('click', '#clearFilterTanggal', function() {
        filterStartDate = '';
        filterEndDate = '';
        $('#filter_tanggal').val('');
        var picker = $('#filter_tanggal').data('daterangepicker');
        if (picker) { picker.setStartDate(moment()); picker.setEndDate(moment()); }
        table.ajax.reload();
    });

    // Approval status filter change -> reload table
    $(document).on('change', '#filter_approval', function() {
        table.ajax.reload();
    });
    // Jenis filter change -> reload table
    $(document).on('change', '#filter_jenis', function() {
        table.ajax.reload();
    });
    // Sumber Dana filter change -> reload table
    $(document).on('change', '#filter_sumber', function() {
        table.ajax.reload();
    });

    // Riwayat Pembayaran: open modal and initialize DataTable
    var paidHistoryTable = null;
    var _paidDaterangeInitialized = false;
    $(document).on('click', '#btnRiwayatPembayaran', function() {
        $('#riwayatPembayaranModal').modal('show');
        // initialize daterangepicker once and default to today
        if (!_paidDaterangeInitialized && typeof $.fn.daterangepicker === 'function' && typeof moment !== 'undefined') {
            var today = moment();
            $('#paidFilterTanggal').daterangepicker({
                showDropdowns: true,
                autoUpdateInput: true,
                locale: { format: 'DD MMMM YYYY' }
            }, function(start, end) {
                if (paidHistoryTable) paidHistoryTable.ajax.reload();
            });
            // default to today
            $('#paidFilterTanggal').data('daterangepicker').setStartDate(today);
            $('#paidFilterTanggal').data('daterangepicker').setEndDate(today);
            _paidDaterangeInitialized = true;
        }

        if (paidHistoryTable === null) {
            paidHistoryTable = $('#paidHistoryTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{!! route('finance.pengajuan.paid.data') !!}',
                    data: function(d){
                        var tanggal = $('#paidFilterTanggal').val();
                        var start = '', end = '';
                        if (tanggal && tanggal.indexOf(' - ') !== -1) {
                            var parts = tanggal.split(' - ');
                            start = parts[0];
                            end = parts[1] || parts[0];
                        } else if (tanggal) {
                            start = tanggal; end = tanggal;
                        }
                        d.start_date = start;
                        d.end_date = end;
                    }
                },
                columns: [
                    // one row per transfer (finance_pengajuan_dana_payment)
                    { data: 'id', render: function(data, type, row, meta){ return meta.settings._iDisplayStart + meta.row + 1; }, orderable:false, searchable:false },
                    { data: 'kode_pengajuan', name: 'kode_pengajuan', orderable: false, searchable: false },
                    { data: 'items_list', name: 'items_list', orderable: false, searchable: false },
                    { data: 'rekening', name: 'rekening', orderable: false, searchable: false },
                    { data: 'payment_date', name: 'tanggal_bayar', searchable: false },
                    { data: 'nominal_display', name: 'nominal', orderable: false, searchable: false },
                    { data: 'paid_by_name', name: 'paid_by_name', orderable: false, searchable: false }
                ],
                order: [[4, 'desc']]
            });
        } else {
            paidHistoryTable.ajax.reload();
        }
    });

    // Kelola Rekening: list / add / edit / delete rekening in a modal
    var kelolaRekeningTable = null;
    var kelolaRekeningChanged = false;
    var rekeningBaseUrl = '{{ url('finance/pengajuan-rekening') }}';

    function escapeHtmlRek(s) {
        return $('<div>').text(s == null ? '' : String(s)).html();
    }

    function resetKelolaRekeningForm() {
        $('#kr_id').val('');
        $('#kr_bank, #kr_no_rekening, #kr_atas_nama').val('').removeClass('is-invalid');
        $('#kelolaRekeningForm .invalid-feedback').text('');
        $('#kr_save').html('<i class="fa fa-plus mr-1"></i>Tambah');
        $('#kr_cancel').hide();
    }

    // keep the Rekening select in the pengajuan form in sync with changes made here
    function syncRekeningOption(rek, removed) {
        var $sel = $('#rekening_id');
        var $opt = $sel.find('option[value="' + rek.id + '"]');
        if (removed) {
            $opt.remove();
        } else {
            var label = (rek.bank || '') + ' / ' + (rek.no_rekening || '') + ' / ' + (rek.atas_nama || '');
            if ($opt.length) $opt.text(label);
            else $sel.append(new Option(label, rek.id, false, false));
        }
        $sel.trigger('change.select2');
    }

    $(document).on('click', '#btnKelolaRekening', function() {
        resetKelolaRekeningForm();
        kelolaRekeningChanged = false;
        $('#kelolaRekeningModal').modal('show');
        if (kelolaRekeningTable === null) {
            kelolaRekeningTable = $('#kelolaRekeningTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{!! route('finance.rekening.data') !!}',
                columns: [
                    { data: 'id', orderable: false, searchable: false, render: function(data, type, row, meta) {
                            return meta.settings._iDisplayStart + meta.row + 1;
                        }
                    },
                    { data: 'bank', name: 'bank', render: function(d) { return escapeHtmlRek(d); } },
                    { data: 'no_rekening', name: 'no_rekening', render: function(d) { return escapeHtmlRek(d); } },
                    { data: 'atas_nama', name: 'atas_nama', render: function(d) { return escapeHtmlRek(d); } },
                    { data: null, orderable: false, searchable: false, render: function(data, type, row) {
                            return '<button type="button" class="btn btn-sm btn-primary kr-edit" data-id="' + row.id + '" title="Edit"><i class="fa fa-edit mr-1"></i>Edit</button>' +
                                   ' <button type="button" class="btn btn-sm btn-danger kr-delete" data-id="' + row.id + '" title="Hapus"><i class="fa fa-trash mr-1"></i>Hapus</button>';
                        }
                    }
                ],
                order: [[1, 'asc']]
            });
        } else {
            kelolaRekeningTable.ajax.reload();
        }
    });

    // refresh the main table when rekening data changed (Rekening Tujuan column)
    $('#kelolaRekeningModal').on('hidden.bs.modal', function() {
        if (kelolaRekeningChanged) table.ajax.reload(null, false);
    });

    $(document).on('click', '#kr_cancel', resetKelolaRekeningForm);

    $(document).on('click', '.kr-edit', function() {
        var row = kelolaRekeningTable.row($(this).closest('tr')).data();
        if (!row) return;
        $('#kr_id').val(row.id);
        $('#kr_bank').val(row.bank || '');
        $('#kr_no_rekening').val(row.no_rekening || '');
        $('#kr_atas_nama').val(row.atas_nama || '');
        $('#kr_save').html('<i class="fa fa-save mr-1"></i>Simpan');
        $('#kr_cancel').show();
        $('#kr_bank').focus();
    });

    $('#kelolaRekeningForm').on('submit', function(e) {
        e.preventDefault();
        var id = $('#kr_id').val();
        var $btn = $('#kr_save');
        $('#kelolaRekeningForm .is-invalid').removeClass('is-invalid');
        $('#kelolaRekeningForm .invalid-feedback').text('');
        var payload = {
            bank: $('#kr_bank').val(),
            no_rekening: $('#kr_no_rekening').val(),
            atas_nama: $('#kr_atas_nama').val()
        };
        if (!$.trim(payload.bank) && !$.trim(payload.no_rekening) && !$.trim(payload.atas_nama)) {
            Swal.fire('Validasi', 'Isi minimal salah satu data rekening', 'warning');
            return;
        }
        if (id) payload._method = 'PUT';
        $btn.prop('disabled', true);
        $.ajax({
            url: id ? rekeningBaseUrl + '/' + id : '{{ route('finance.rekening.store') }}',
            method: 'POST',
            data: payload,
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function(res) {
                kelolaRekeningChanged = true;
                if (res && res.data) syncRekeningOption(res.data, false);
                resetKelolaRekeningForm();
                kelolaRekeningTable.ajax.reload(null, false);
                Swal.fire({ icon: 'success', title: 'Sukses', text: id ? 'Rekening berhasil diperbarui' : 'Rekening berhasil ditambahkan', timer: 1200, showConfirmButton: false });
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    var errors = (xhr.responseJSON && xhr.responseJSON.errors) || {};
                    var map = { bank: '#kr_bank', no_rekening: '#kr_no_rekening', atas_nama: '#kr_atas_nama' };
                    Object.keys(errors).forEach(function(k) {
                        if (map[k]) $(map[k]).addClass('is-invalid').siblings('.invalid-feedback').text(errors[k][0]);
                    });
                } else {
                    Swal.fire('Error', 'Gagal menyimpan rekening', 'error');
                }
            },
            complete: function() { $btn.prop('disabled', false); }
        });
    });

    $(document).on('click', '.kr-delete', function() {
        var id = $(this).data('id');
        Swal.fire({
            title: 'Hapus rekening?',
            text: 'Data rekening akan dihapus permanen.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Hapus',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#dc3545'
        }).then(function(result) {
            if (!(result.isConfirmed || result.value)) return;
            $.ajax({
                url: rekeningBaseUrl + '/' + id,
                method: 'POST',
                data: { _method: 'DELETE' },
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function() {
                    kelolaRekeningChanged = true;
                    syncRekeningOption({ id: id }, true);
                    if (String($('#kr_id').val()) === String(id)) resetKelolaRekeningForm();
                    kelolaRekeningTable.ajax.reload(null, false);
                    Swal.fire({ icon: 'success', title: 'Terhapus', timer: 1200, showConfirmButton: false });
                },
                error: function(xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Gagal menghapus rekening';
                    Swal.fire('Error', msg, 'error');
                }
            });
        });
    });

    // Kelola Approver (Admin only): list / add / edit / delete approvers in a modal
    var kelolaApproverTable = null;
    var kelolaApproverChanged = false;
    var approverBaseUrl = '{{ url('finance/pengajuan-dana-approvers') }}';

    function resetKelolaApproverForm() {
        $('#ka_id').val('');
        $('#ka_user_id').val('').trigger('change');
        $('#ka_jabatan').val('');
        $('#ka_tingkat').val(1);
        $('#ka_jenis').val('');
        $('#ka_aktif').prop('checked', true);
        $('#kelolaApproverForm .is-invalid').removeClass('is-invalid');
        $('#kelolaApproverForm .invalid-feedback').text('');
        $('#ka_save').html('<i class="fa fa-plus mr-1"></i>Tambah');
        $('#ka_cancel').hide();
    }

    $(document).on('click', '#btnKelolaApprover', function() {
        if (typeof $.fn.select2 === 'function' && !$('#ka_user_id').hasClass('select2-hidden-accessible')) {
            $('#ka_user_id').select2({ dropdownParent: $('#kelolaApproverModal'), width: '100%' });
        }
        resetKelolaApproverForm();
        kelolaApproverChanged = false;
        $('#kelolaApproverModal').modal('show');
        if (kelolaApproverTable === null) {
            kelolaApproverTable = $('#kelolaApproverTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{!! route('finance.pengajuan.approver.data') !!}',
                columns: [
                    { data: 'id', orderable: false, searchable: false, render: function(data, type, row, meta) {
                            return meta.settings._iDisplayStart + meta.row + 1;
                        }
                    },
                    { data: 'user.name', name: 'user.name', defaultContent: '', render: function(d) { return escapeHtmlRek(d); } },
                    { data: 'jabatan', name: 'jabatan', defaultContent: '', render: function(d) { return escapeHtmlRek(d); } },
                    { data: 'tingkat', name: 'tingkat' },
                    { data: 'jenis', name: 'jenis', render: function(d) {
                            return d ? escapeHtmlRek(d) : '<span class="text-muted">Semua</span>';
                        }
                    },
                    { data: 'aktif', name: 'aktif', searchable: false, render: function(d) {
                            return d == 1 ? '<span class="badge badge-success">Ya</span>' : '<span class="badge badge-secondary">Tidak</span>';
                        }
                    },
                    { data: null, orderable: false, searchable: false, render: function(data, type, row) {
                            return '<button type="button" class="btn btn-sm btn-primary ka-edit" data-id="' + row.id + '" title="Edit"><i class="fa fa-edit mr-1"></i>Edit</button>' +
                                   ' <button type="button" class="btn btn-sm btn-danger ka-delete" data-id="' + row.id + '" title="Hapus"><i class="fa fa-trash mr-1"></i>Hapus</button>';
                        }
                    }
                ],
                order: [[3, 'desc']]
            });
        } else {
            kelolaApproverTable.ajax.reload();
        }
    });

    // approver changes affect approval status + Approve buttons in the main table
    $('#kelolaApproverModal').on('hidden.bs.modal', function() {
        if (kelolaApproverChanged) table.ajax.reload(null, false);
    });

    $(document).on('click', '#ka_cancel', resetKelolaApproverForm);

    $(document).on('click', '.ka-edit', function() {
        var row = kelolaApproverTable.row($(this).closest('tr')).data();
        if (!row) return;
        resetKelolaApproverForm();
        $('#ka_id').val(row.id);
        $('#ka_user_id').val(row.user_id).trigger('change');
        $('#ka_jabatan').val(row.jabatan || '');
        $('#ka_tingkat').val(row.tingkat || 1);
        $('#ka_jenis').val(row.jenis || '');
        $('#ka_aktif').prop('checked', row.aktif == 1);
        $('#ka_save').html('<i class="fa fa-save mr-1"></i>Simpan');
        $('#ka_cancel').show();
    });

    $('#kelolaApproverForm').on('submit', function(e) {
        e.preventDefault();
        var id = $('#ka_id').val();
        var $btn = $('#ka_save');
        $('#kelolaApproverForm .is-invalid').removeClass('is-invalid');
        $('#kelolaApproverForm .invalid-feedback').text('');
        var payload = {
            user_id: $('#ka_user_id').val(),
            jabatan: $('#ka_jabatan').val(),
            tingkat: parseInt($('#ka_tingkat').val() || 1, 10),
            jenis: $('#ka_jenis').val() || '',
            aktif: $('#ka_aktif').is(':checked') ? 1 : 0
        };
        if (!payload.user_id) {
            Swal.fire('Validasi', 'Pilih user approver', 'warning');
            return;
        }
        if (id) payload._method = 'PUT';
        $btn.prop('disabled', true);
        $.ajax({
            url: id ? approverBaseUrl + '/' + id : '{{ route('finance.pengajuan.approver.store') }}',
            method: 'POST',
            data: payload,
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function() {
                kelolaApproverChanged = true;
                resetKelolaApproverForm();
                kelolaApproverTable.ajax.reload(null, false);
                Swal.fire({ icon: 'success', title: 'Sukses', text: id ? 'Approver berhasil diperbarui' : 'Approver berhasil ditambahkan', timer: 1200, showConfirmButton: false });
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    var errors = (xhr.responseJSON && xhr.responseJSON.errors) || {};
                    var map = { user_id: '#ka_user_id', jabatan: '#ka_jabatan', tingkat: '#ka_tingkat', jenis: '#ka_jenis' };
                    Object.keys(errors).forEach(function(k) {
                        if (map[k]) $(map[k]).addClass('is-invalid').siblings('.invalid-feedback').text(errors[k][0]);
                    });
                } else {
                    Swal.fire('Error', (xhr.responseJSON && xhr.responseJSON.message) || 'Gagal menyimpan approver', 'error');
                }
            },
            complete: function() { $btn.prop('disabled', false); }
        });
    });

    $(document).on('click', '.ka-delete', function() {
        var id = $(this).data('id');
        Swal.fire({
            title: 'Hapus approver?',
            text: 'Approver akan dihapus dari alur persetujuan.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Hapus',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#dc3545'
        }).then(function(result) {
            if (!(result.isConfirmed || result.value)) return;
            $.ajax({
                url: approverBaseUrl + '/' + id,
                method: 'POST',
                data: { _method: 'DELETE' },
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function() {
                    kelolaApproverChanged = true;
                    if (String($('#ka_id').val()) === String(id)) resetKelolaApproverForm();
                    kelolaApproverTable.ajax.reload(null, false);
                    Swal.fire({ icon: 'success', title: 'Terhapus', timer: 1200, showConfirmButton: false });
                },
                error: function(xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Gagal menghapus approver';
                    Swal.fire('Error', msg, 'error');
                }
            });
        });
    });

    // Detail Pengajuan: open modal with info, all items + harga and bukti photos
    $('#pengajuanTable').on('click', '.show-detail', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        if (!id) return;
        var $tbody = $('#itemsDetailTable tbody');
        var $bukti = $('#dt_bukti');
        $('#itemsDetailModalLabel').text('Detail Pengajuan');
        $('#dt_kode, #dt_tanggal, #dt_pengaju, #dt_jenis, #dt_diajukan, #dt_rekening').text('-');
        $tbody.html('<tr><td colspan="6" class="text-center text-muted">Memuat...</td></tr>');
        $('#itemsDetailGrandTotal').text('');
        $bukti.empty();
        $('#dt_pembayaran_wrap').hide();
        $('#dt_pembayaran').empty();
        $('#dt_pdf').attr('href', '/finance/pengajuan-dana/' + id + '/pdf');
        $('#itemsDetailModal').modal('show');
        $.get('/finance/pengajuan-dana/' + id, function(res) {
            if (!res || typeof res !== 'object') {
                $tbody.html('<tr><td colspan="6" class="text-center text-danger">Gagal memuat detail</td></tr>');
                return;
            }
            // info
            if (res.kode_pengajuan) {
                $('#itemsDetailModalLabel').text('Detail Pengajuan - ' + res.kode_pengajuan);
                $('#dt_kode').text(res.kode_pengajuan);
            }
            if (res.tanggal_pengajuan) {
                var tgl = new Date(res.tanggal_pengajuan);
                $('#dt_tanggal').text(isNaN(tgl.getTime()) ? res.tanggal_pengajuan : tgl.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }));
            }
            var emp = res.employee || null;
            var empName = emp ? ((emp.user && emp.user.name) || emp.nama || '') : '';
            if (empName) $('#dt_pengaju').text(empName);
            if (res.jenis_pengajuan) $('#dt_jenis').text(res.jenis_pengajuan);
            var diajukan = [res.sumber_dana, res.perusahaan].filter(function(v) { return v && String(v).trim() !== ''; }).join(' - ');
            if (diajukan) $('#dt_diajukan').text(diajukan);
            var rek = res.rekening || null;
            if (rek) {
                var rekMain = [rek.bank, rek.no_rekening].filter(function(v) { return v && String(v).trim() !== ''; }).join(' - ');
                $('#dt_rekening').html(escapeHtmlRek(rekMain || '-') + (rek.atas_nama ? '<div><small class="text-muted">' + escapeHtmlRek(rek.atas_nama) + '</small></div>' : ''));
            }

            // bukti photos (same storage layout as the bukti modal)
            var buktiArr = [];
            try {
                buktiArr = (typeof res.bukti_transaksi === 'string') ? JSON.parse(res.bukti_transaksi) : (res.bukti_transaksi || []);
            } catch (err) {
                buktiArr = res.bukti_transaksi ? [res.bukti_transaksi] : [];
            }
            if (!Array.isArray(buktiArr)) buktiArr = buktiArr ? [buktiArr] : [];
            buktiArr = buktiArr.filter(function(p) { return p && String(p).trim() !== ''; });
            if (!buktiArr.length) {
                $bukti.html('<div class="w-100"><div class="text-muted mb-2"><i class="fa fa-exclamation-triangle text-danger mr-1"></i>Belum ada bukti foto.</div>' +
                    '<button type="button" class="btn btn-sm btn-warning detail-upload-bukti" data-id="' + id + '"><i class="fa fa-upload mr-1"></i>Upload Bukti Transaksi / Invoice</button></div>');
            } else {
                buktiArr.forEach(function(p, index) {
                    var src = '/storage/' + String(p).replace(/^\/+/, '');
                    var $card = $('<div class="detail-bukti"></div>');
                    $card.append($('<img>').attr('src', src).attr('alt', 'Bukti ' + (index + 1)));
                    $card.append($('<a class="btn btn-sm btn-link p-0 mt-1" target="_blank"><i class="fa fa-download mr-1"></i>Download</a>')
                        .attr('href', '/finance/pengajuan-dana/' + id + '/download-bukti/' + index));
                    $bukti.append($card);
                });
            }

            renderDetailPembayaran(res);

            // items
            var items = res.items || [];
            if (!items.length) {
                $tbody.html('<tr><td colspan="6" class="text-center text-muted">Tidak ada item</td></tr>');
                $('#itemsDetailGrandTotal').text(formatRupiah(res.grand_total || 0));
                return;
            }
            var sum = 0;
            var rows = items.map(function(it, i) {
                var qty = Number(it.jumlah || 0);
                var harga = Number(it.harga_satuan || 0);
                var total = (it.harga_total_snapshot !== null && it.harga_total_snapshot !== undefined) ? Number(it.harga_total_snapshot) : qty * harga;
                sum += total;
                return '<tr>' +
                    '<td>' + (i + 1) + '</td>' +
                    '<td>' + escapeHtmlRek(it.nama_item) + '</td>' +
                    '<td><small class="text-muted">' + escapeHtmlRek(it.notes) + '</small></td>' +
                    '<td class="text-center">' + escapeHtmlRek(it.jumlah) + '</td>' +
                    '<td class="text-right text-nowrap">' + formatRupiah(harga) + '</td>' +
                    '<td class="text-right text-nowrap">' + formatRupiah(total) + '</td>' +
                '</tr>';
            });
            $tbody.html(rows.join(''));
            var grand = (res.grand_total !== null && res.grand_total !== undefined) ? res.grand_total : sum;
            $('#itemsDetailGrandTotal').text(formatRupiah(grand));
        }).fail(function() {
            $tbody.html('<tr><td colspan="6" class="text-center text-danger">Gagal memuat detail</td></tr>');
        });
    });

    function formatTanggalWaktu(v) {
        if (!v) return '-';
        var d = new Date(v);
        return isNaN(d.getTime()) ? v : d.toLocaleString('id-ID', { day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' });
    }

    // Detail modal: list of payments (transfers) with totals
    function renderDetailPembayaran(res) {
        var payments = res.payments || [];
        if (!payments.length) return;
        var rows = payments.map(function(p, i) {
            return '<tr>' +
                '<td>' + (i + 1) + '</td>' +
                '<td class="text-nowrap">' + escapeHtmlRek(formatTanggalWaktu(p.tanggal_bayar)) + '</td>' +
                '<td class="text-right text-nowrap">' + formatNominal(p.nominal) + '</td>' +
                '<td>' + escapeHtmlRek(p.note || '') + (p.bukti ? ' ' + fileLinks([p.bukti], 'Bukti') : '') + '</td>' +
                '<td>' + escapeHtmlRek((p.paid_by && p.paid_by.name) || '') + '</td>' +
            '</tr>';
        });
        var dibayar = Number(res.total_dibayar || 0);
        var grand = Number(res.grand_total || 0);
        var selisihLabel = res.payment_status === 'partial' ? 'Sisa belum dibayar' : 'Selisih (tidak dibayar)';
        var foot = '<tr><th colspan="2" class="text-right">Total dibayar</th><th class="text-right text-nowrap">' + formatNominal(dibayar) + '</th><th colspan="2"></th></tr>';
        if (grand - dibayar >= 0.01) {
            foot += '<tr><th colspan="2" class="text-right">' + selisihLabel + '</th><th class="text-right text-nowrap text-danger">' + formatNominal(grand - dibayar) + '</th><th colspan="2"></th></tr>';
        }
        $('#dt_pembayaran').html('<div class="table-responsive"><table class="table table-sm table-bordered mb-0">' +
            '<thead class="thead-light"><tr><th style="width:5%">#</th><th>Tanggal</th><th class="text-right">Nominal</th><th>Catatan</th><th>Oleh</th></tr></thead>' +
            '<tbody>' + rows.join('') + '</tbody><tfoot>' + foot + '</tfoot></table></div>');
        $('#dt_pembayaran_wrap').show();
    }

    // upload bukti from the detail modal: close detail, then open the existing upload modal
    $(document).on('click', '#itemsDetailModal .detail-upload-bukti', function() {
        var id = $(this).data('id');
        $('#itemsDetailModal').one('hidden.bs.modal', function() {
            openBuktiModal(id, 'upload');
        }).modal('hide');
    });

    // zoom bukti photo from the detail modal
    $(document).on('click', '#itemsDetailModal .detail-bukti img', function() {
        openBuktiZoom($(this).attr('src'));
    });

    // Initialize inline Select2 for faktur search (in footer)
    if (typeof $.fn.select2 === 'function') {
        $('#select_faktur_inline').select2({
            dropdownParent: $('#pengajuanModal'),
            width: 'resolve',
            placeholder: $('#select_faktur_inline').data('placeholder') || 'Cari faktur...',
            minimumInputLength: 2,
            ajax: {
                url: '{!! route('erm.fakturbeli.select2') !!}',
                dataType: 'json',
                delay: 250,
                data: function(params) { return { q: params.term }; },
                processResults: function(data) { return data; },
                cache: true
            }
        });

        // when a faktur is selected, fetch its JSON and insert a readonly row
        $('#select_faktur_inline').on('select2:select', function(e) {
            var id = e.params && e.params.data && e.params.data.id;
            if (!id) return;
            $.ajax({
                url: '/erm/fakturpembelian/' + id + '/json',
                method: 'GET',
                        success: function(res) {
                            // build description like: "Faktur: {no_faktur} (item1, item2, item3)"
                            var no = res.no_faktur || id || '';
                            var price = parseFloat(res.total || 0);
                            // try multiple common locations for item arrays (robust against varying payloads)
                            var itemArray = [];
                            var tryPaths = [
                                ['items'],
                                ['data','items'],
                                ['items_list'],
                                ['lines'],
                                ['detail'],
                                ['detail_items'],
                                ['items_pembelian'],
                                ['itemsList'],
                                ['itemsPembelian'],
                                ['items_array']
                            ];
                            tryPaths.some(function(path){
                                var o = res;
                                for (var i=0;i<path.length;i++) {
                                    if (o && Object.prototype.hasOwnProperty.call(o, path[i])) {
                                        o = o[path[i]];
                                    } else { o = null; break; }
                                }
                                if (Array.isArray(o) && o.length) { itemArray = o; return true; }
                                return false;
                            });

                            // if still empty, try to detect the first array-valued property on res
                            if (!itemArray.length) {
                                for (var k in res) {
                                    if (!res.hasOwnProperty(k)) continue;
                                    if (Array.isArray(res[k]) && res[k].length) { itemArray = res[k]; break; }
                                }
                            }

                            // if items is a JSON string, try parsing
                            if (!itemArray.length && typeof res.items === 'string') {
                                try { var parsed = JSON.parse(res.items); if (Array.isArray(parsed)) itemArray = parsed; } catch(e){}
                            }

                            var itemNames = [];
                            if (Array.isArray(itemArray) && itemArray.length) {
                                itemNames = itemArray.map(function(it){
                                    if (!it) return null;
                                    if (typeof it === 'string') return it;
                                    return it.nama_item || it.nama || it.nama_barang || it.barang_nama || it.obat_nama || it.name || it.item_name || it.description || it.keterangan || it.label || null;
                                }).filter(function(x){ return x && x.toString().trim() !== ''; });
                            }
                            // use full item list for the nama_item (it's stored as TEXT in DB)
                            var fullList = itemNames.join(', ');
                            var desc = 'Faktur: ' + no + (fullList ? ' (' + fullList + ')' : '');
                            var fakturId = res.id || id;
                            var $tbody = $('#itemsTable tbody');
                            $('#select_faktur_inline').val(null).trigger('change');

                            // the same faktur only once per pengajuan (the server also blocks it across pengajuan)
                            var already = $tbody.find('tr').filter(function() {
                                return String($(this).data('fakturbeli-id') || '') === String(fakturId);
                            });
                            if (already.length) {
                                Swal.fire('Sudah ada', 'Faktur ' + no + ' sudah ada di rincian item.', 'info');
                                return;
                            }

                            // reuse the first completely empty row, otherwise append one
                            var $row = $tbody.find('tr').filter(function() {
                                var $t = $(this);
                                return !$t.data('fakturbeli-id') && !($t.find('.item-desc').val() || '').trim()
                                    && !($t.find('.item-qty').val() || '').trim() && parseRupiah($t.find('.item-price').val()) === 0;
                            }).first();
                            if (!$row.length) $row = addItemRow(null, true);
                            $row.find('.item-desc').val(desc);
                            setRupiahValue($row.find('.item-price'), price);
                            markFakturRow($row, fakturId, desc);
                            recalcItems();
                        },
                error: function() {
                    Swal.fire('Error', 'Gagal mengambil data faktur', 'error');
                }
            });
        });
    }

    // Show approvals modal when badge/button clicked
    function _esc(s) { return $('<div>').text(s || '').html(); }
    $(document).on('click', '.show-approvals', function(e){
        e.preventDefault();
        var id = $(this).data('id');
        if (!id) return;
        var url = '{{ url('finance/pengajuan-dana') }}' + '/' + id + '/approvals';
        $.get(url, function(res, status, xhr){
            // if response is HTML (e.g., redirect to login), treat as failure
            var contentType = (xhr && xhr.getResponseHeader) ? xhr.getResponseHeader('Content-Type') : '';
            if (contentType && contentType.indexOf('application/json') === -1) {
                // not JSON — likely a redirect or error page
                Swal.fire('Error', 'Gagal memuat data persetujuan', 'error');
                return;
            }
            if (!res || !res.success) {
                Swal.fire('Error', 'Gagal memuat data persetujuan', 'error');
                return;
            }
            var list = res.data || [];
            var html = '';
            html += '<div class="modal fade" id="approvalsModal" tabindex="-1" aria-hidden="true">';
            html += '<div class="modal-dialog">';
            html += '<div class="modal-content">';
            html += '<div class="modal-header">';
            html += '<h5 class="modal-title">Daftar Persetujuan</h5>';
            html += '<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>';
            html += '</div>';
            html += '<div class="modal-body">';
            html += '<table class="table table-sm table-bordered">';
            html += '<thead><tr><th style="width:6%">#</th><th>Nama</th><th>Jabatan</th><th style="width:28%">Tanggal</th><th style="width:8%">Status</th></tr></thead>';
            html += '<tbody>';
            if (list.length) {
                // group by tingkat (level)
                var groups = {};
                list.forEach(function(it){
                    var lvl = (typeof it.tingkat !== 'undefined' && it.tingkat !== null && it.tingkat !== '') ? it.tingkat : '0';
                    if (!groups[lvl]) groups[lvl] = [];
                    groups[lvl].push(it);
                });
                // approval order: highest tingkat acts first
                var levels = Object.keys(groups).sort(function(a,b){ return Number(b) - Number(a); });
                var counter = 1;
                levels.forEach(function(lvl){
                    // group header row to indicate tingkat
                    html += '<tr class="table-secondary"><td colspan="4"><strong>Tingkat ' + _esc(lvl) + '</strong></td><td></td></tr>';
                    groups[lvl].forEach(function(it){
                        html += '<tr>';
                        html += '<td>' + (counter++) + '</td>';
                        html += '<td>' + _esc(it.name) + '</td>';
                        html += '<td>' + _esc(it.jabatan) + '</td>';
                        html += '<td>' + _esc(it.date) + '</td>';
                        var icon = '';
                        try {
                            if (it.status === 'approved') {
                                icon = '<i class="fa fa-check-circle text-success" title="Disetujui"></i>';
                            } else if (it.status === 'declined' || it.status === 'rejected') {
                                icon = '<i class="fa fa-times-circle text-danger" title="Ditolak"></i>';
                            } else {
                                icon = (it.status === 'skipped')
                                    ? '<span class="text-muted" title="Tidak perlu, sudah disetujui approver lain di tingkat ini">&ndash;</span>'
                                    : '<i class="fa fa-clock text-muted" title="Menunggu"></i>';
                            }
                        } catch(e) { icon = ''; }
                        html += '<td class="text-center">' + icon + '</td>';
                        html += '</tr>';
                        if (it.note) {
                            // alasan penolakan under the approver row
                            html += '<tr><td></td><td colspan="4"><small class="text-danger"><strong>Alasan:</strong> ' + _esc(it.note) + '</small></td></tr>';
                        }
                    });
                });
            } else {
                html += '<tr><td colspan="5" class="text-center">Belum ada persetujuan</td></tr>';
            }
            html += '</tbody></table>';
            // informational note: only one approval needed per tingkat (styled red)
            html += '<div class="mt-2"><small class="text-danger">Catatan: Persetujuan dimulai dari tingkat tertinggi. Hanya perlu 1 approval tiap tingkat.</small></div>';
            html += '</div>';
            html += '<div class="modal-footer">';
            html += '<button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fa fa-times mr-1"></i>Tutup</button>';
            html += '</div></div></div></div>';

            // ensure only one approvals modal exists
            $('#approvalsModal').remove();
            $('body').append(html);
            $('#approvalsModal').modal({ backdrop: 'static', keyboard: false });
            $('#approvalsModal').modal('show');
        }).fail(function(){
            Swal.fire('Error', 'Gagal memuat data persetujuan', 'error');
        });
    });

});
</script>
@endsection
