@extends('layouts.admin.app')

@section('title', 'RND Produk')

@section('navbar')
    @include('layouts.rnd.navbar')
@endsection

@section('styles')
<style>
    .rnd-products-card .card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        background: linear-gradient(135deg, #0f766e, #14b8a6);
        color: #fff;
        border-bottom: 0;
    }

    .rnd-products-card .card-title {
        color: inherit;
        margin: 0;
        font-weight: 700;
    }

    .rnd-products-card .btn-light {
        color: #0f172a;
        font-weight: 600;
    }

    .produk-status-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.3rem 0.55rem;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 700;
        line-height: 1;
        background: rgba(37, 99, 235, 0.12);
        color: #1d4ed8;
        border: 1px solid rgba(29, 78, 216, 0.16);
    }

    .produk-status-badge-done {
        background: rgba(34, 197, 94, 0.14);
        color: #15803d;
        border-color: rgba(34, 197, 94, 0.24);
    }

    .produk-status-badge-warning {
        background: rgba(250, 204, 21, 0.22);
        color: #a16207;
        border-color: rgba(202, 138, 4, 0.28);
    }

    .produk-status-badge-danger {
        background: rgba(239, 68, 68, 0.14);
        color: #b91c1c;
        border-color: rgba(220, 38, 38, 0.24);
    }

    .produk-status-badge-empty {
        background: rgba(148, 163, 184, 0.12);
        color: #64748b;
        border-color: rgba(100, 116, 139, 0.2);
    }

    #produkTable th,
    #produkTable td {
        vertical-align: top;
    }

    #produkTable tbody tr:nth-child(even) {
        background: #f8fafc;
    }

    #produkTable tbody tr:nth-child(odd) {
        background: #ffffff;
    }

    #produkTable tbody tr:hover {
        background: #eef6ff;
    }

    #produkTable tbody td {
        border-bottom: 2px solid #dbe7f3;
    }

    .produk-status-trigger {
        border: 0;
        background: transparent;
        padding: 0;
        cursor: pointer;
        line-height: 1;
    }

    .produk-action-link {
        border: 0;
        background: rgba(15, 118, 110, 0.12);
        color: #0f766e;
        border-radius: 999px;
        padding: 0.35rem 0.7rem;
        font-size: 12px;
        font-weight: 700;
        line-height: 1;
        cursor: pointer;
        width: fit-content;
    }

    .produk-action-link:hover,
    .produk-action-link:focus {
        background: rgba(15, 118, 110, 0.18);
        outline: none;
    }

    .produk-action-group {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .produk-history-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
        text-align: left;
        margin-top: 8px;
    }

    .produk-history-item {
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 12px;
        background: #fff;
    }

    .produk-history-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 6px;
    }

    .produk-history-time {
        font-size: 12px;
        color: #64748b;
    }

    .produk-history-status {
        display: flex;
        justify-content: flex-end;
        margin-top: 8px;
    }

    .produk-history-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-top: 8px;
    }

    .produk-modal-layout {
        display: flex;
        gap: 24px;
        align-items: flex-start;
    }

    .produk-form-pane {
        flex: 1 1 auto;
        min-width: 0;
    }

    .produk-log-pane {
        flex: 0 0 340px;
        max-width: 340px;
        border-left: 1px solid #e2e8f0;
        padding-left: 24px;
    }

    .produk-log-pane.is-hidden {
        display: none;
    }

    .produk-log-card {
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        background: #f8fafc;
        padding: 16px;
    }

    .produk-log-title {
        margin: 0 0 12px;
        font-size: 14px;
        font-weight: 700;
        color: #0f172a;
    }

    .produk-log-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
        max-height: 420px;
        overflow-y: auto;
    }

    .produk-log-item {
        border: 1px solid #dbeafe;
        border-radius: 12px;
        background: #fff;
        padding: 12px;
    }

    .produk-log-item-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 6px;
    }

    .produk-log-item-status {
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.02em;
        text-transform: uppercase;
        color: #0f766e;
    }

    .produk-log-item-time {
        font-size: 11px;
        color: #64748b;
        white-space: nowrap;
    }

    .produk-log-item-notes {
        font-size: 12px;
        color: #334155;
        line-height: 1.5;
        white-space: pre-wrap;
    }

    .produk-log-empty {
        font-size: 12px;
        color: #64748b;
        text-align: center;
        padding: 16px 0;
    }

    @media (max-width: 1199.98px) {
        .produk-modal-layout {
            flex-direction: column;
        }

        .produk-log-pane {
            flex: 1 1 auto;
            max-width: none;
            width: 100%;
            border-left: 0;
            border-top: 1px solid #e2e8f0;
            padding-left: 0;
            padding-top: 24px;
        }
    }

    .swal2-radio {
        display: flex !important;
        flex-direction: column;
        gap: 10px;
        margin: 1rem 0 0;
    }

    .swal2-radio label {
        display: flex !important;
        align-items: center;
        gap: 10px;
        width: 100%;
        margin: 0;
        padding: 10px 12px;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        background: #fff;
        color: #0f172a;
        font-size: 14px;
        font-weight: 600;
        text-align: left;
    }

    .swal2-radio label:hover {
        background: #f8fafc;
        border-color: #cbd5e1;
    }

    .swal2-radio input {
        margin: 0;
    }

    .produk-meta {
        font-size: 12px;
        color: #64748b;
    }

    .produk-inline-meta {
        font-size: 12px;
        color: #64748b;
        white-space: nowrap;
    }

    .produk-inline-link {
        font-size: 12px;
        font-weight: 700;
        color: #0f766e;
        text-decoration: none;
        white-space: nowrap;
    }

    .produk-inline-link:hover,
    .produk-inline-link:focus {
        color: #0d9488;
        text-decoration: underline;
    }

    .produk-bahan-list {
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.02em;
        color: #64748b;
        line-height: 1.5;
        text-transform: uppercase;
    }

    .produk-name-stack {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .produk-name-trigger {
        display: inline-flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
        border: 0;
        background: transparent;
        padding: 0;
        text-align: left;
        cursor: pointer;
        color: inherit;
    }

    .produk-name-trigger:hover strong,
    .produk-name-trigger:focus strong {
        color: #0f766e;
        text-decoration: underline;
    }

    .produk-name-trigger:focus {
        outline: none;
    }

    .produk-kemasan-trigger {
        border: 0;
        background: transparent;
        padding: 0;
        text-align: left;
        color: inherit;
        cursor: pointer;
        font: inherit;
    }

    .produk-kemasan-trigger:hover,
    .produk-kemasan-trigger:focus {
        color: #0f766e;
        text-decoration: underline;
        outline: none;
    }

    .produk-kemasan-picker-search {
        margin-bottom: 16px;
    }

    .produk-kemasan-picker-list {
        display: flex;
        flex-direction: column;
        gap: 10px;
        max-height: 320px;
        overflow-y: auto;
    }

    .produk-kemasan-picker-item {
        width: 100%;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        background: #fff;
        color: #0f172a;
        padding: 12px 14px;
        text-align: left;
        font-weight: 600;
        transition: background-color 0.15s ease, border-color 0.15s ease;
    }

    .produk-kemasan-picker-item:hover,
    .produk-kemasan-picker-item:focus {
        background: #f8fafc;
        border-color: #cbd5e1;
        outline: none;
    }

    .produk-kemasan-picker-item.is-active {
        background: #ecfeff;
        border-color: #14b8a6;
        color: #0f766e;
    }

    .produk-kemasan-picker-item-meta {
        display: block;
        margin-top: 4px;
        font-size: 12px;
        font-weight: 500;
        color: #64748b;
    }

    .produk-kemasan-picker-empty {
        font-size: 13px;
        color: #64748b;
        text-align: center;
        padding: 16px 0;
    }

    .produk-form-create-only.is-hidden {
        display: none;
    }

    .produk-cell-stack {
        display: flex;
        flex-direction: column;
        gap: 6px;
        min-width: 180px;
    }

    .produk-cell-title {
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.02em;
        color: #64748b;
        text-transform: uppercase;
    }

    .produk-cell-value {
        color: #0f172a;
    }

    .produk-cell-value strong {
        font-weight: 700;
    }

    .produk-cell-divider {
        width: 100%;
        height: 1px;
        background: #e2e8f0;
    }

    .produk-kemasan-stack {
        display: flex;
        flex-direction: column;
        gap: 8px;
        min-width: 180px;
    }

    .produk-kemasan-name {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
        color: #0f172a;
        font-weight: 700;
    }

    .produk-kemasan-badges {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }

    .produk-kemasan-line {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
    }

    .produk-kemasan-design-text {
        font-size: 12px;
        color: #475569;
    }

    .produk-name-stack {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .produk-progress-stack {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .produk-progress-meta {
        flex: 0 0 auto;
        min-width: 82px;
        text-align: right;
        font-size: 16px;
        font-weight: 700;
        line-height: 1;
        color: #0f172a;
    }

    .produk-progress-track {
        flex: 1 1 auto;
        height: 8px;
        border-radius: 999px;
        background: #e2e8f0;
        overflow: hidden;
    }

    .produk-progress-fill {
        height: 100%;
        border-radius: 999px;
        background: linear-gradient(90deg, #0f766e 0%, #14b8a6 100%);
        transition: width 0.2s ease;
    }

    #produkTable_wrapper .btn-group .btn {
        min-width: 34px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="page-title-box">
        <div class="row align-items-center">
            <div class="col">
                <h4 class="page-title mb-1">Produk</h4>
                <p class="text-muted mb-0">Kelola data produk RND beserta referensi brand, kemasan, dan sediaan.</p>
            </div>
        </div>
    </div>

    <div class="card rnd-products-card shadow-sm">
        <div class="card-header">
            <h4 class="card-title">Daftar Produk</h4>
            <button type="button" class="btn btn-light btn-sm" id="createNewProduk">Tambah Produk</button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered dt-responsive nowrap w-100" id="produkTable">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama</th>
                            <th>Status Sample</th>
                            <th>Kemasan Primer</th>
                            <th>Kemasan Sekunder</th>
                            <th>Status Administrasi</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="produkModal" tabindex="-1" role="dialog" aria-labelledby="produkModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="produkModalLabel">Tambah Produk</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="produkForm">
                <div class="modal-body">
                    <input type="hidden" id="produk_id" value="">
                    <div class="produk-modal-layout">
                        <div class="produk-form-pane">
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="brand_id">Brand</label>
                                    <select class="form-control select2-produk" id="brand_id" name="brand_id" required>
                                        <option value="">Pilih Brand</option>
                                        @foreach($brands as $brand)
                                            <option value="{{ $brand->id }}">{{ $brand->nama_brand }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="nama_produk">Nama Produk</label>
                                    <input type="text" class="form-control" id="nama_produk" name="nama_produk" required>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group col-md-3">
                                    <label for="netto">Netto</label>
                                    <input type="text" class="form-control" id="netto" name="netto" placeholder="Contoh: 15 ml">
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="sediaan_id">Sediaan</label>
                                    <select class="form-control select2-produk" id="sediaan_id" name="sediaan_id" required>
                                        <option value="">Pilih Sediaan</option>
                                        @foreach($sediaans as $sediaan)
                                            <option value="{{ $sediaan->id }}">{{ $sediaan->nama_sediaan }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="produsen_vendor_id">Produsen Vendor</label>
                                    <select class="form-control select2-produk" id="produsen_vendor_id" name="produsen_vendor_id">
                                        <option value="">Pilih Produsen Vendor</option>
                                        @foreach($produsenVendors as $vendor)
                                            <option value="{{ $vendor->id }}">{{ $vendor->nama_vendor }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="bahan_aktif_ids">Bahan Aktif</label>
                                    <select class="form-control select2-produk" id="bahan_aktif_ids" name="bahan_aktif_ids[]" multiple>
                                        @foreach($bahanAktifs as $bahanAktif)
                                            <option value="{{ $bahanAktif->id }}">{{ $bahanAktif->nama_bahan_aktif }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="form-row produk-form-create-only">
                                <div class="form-group col-md-6">
                                    <label for="kemasan_premier_id">Kemasan Primer</label>
                                    <select class="form-control select2-produk" id="kemasan_premier_id" name="kemasan_premier_id" required>
                                        <option value="">Pilih Kemasan Primer</option>
                                        @foreach($kemasanPrimerOptions as $kemasan)
                                            <option value="{{ $kemasan->id }}">{{ $kemasan->nama_kemasan }}{{ $kemasan->ukuran ? ' (' . $kemasan->ukuran . ')' : '' }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="kemasan_sekunder_id">Kemasan Sekunder</label>
                                    <select class="form-control select2-produk" id="kemasan_sekunder_id" name="kemasan_sekunder_id">
                                        <option value="">Pilih Kemasan Sekunder</option>
                                        @foreach($kemasanSekunderOptions as $kemasan)
                                            <option value="{{ $kemasan->id }}">{{ $kemasan->nama_kemasan }}{{ $kemasan->ukuran ? ' (' . $kemasan->ukuran . ')' : '' }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <aside class="produk-log-pane is-hidden" id="produkLogPane">
                            <div class="produk-log-card">
                                <h6 class="produk-log-title">Riwayat Produk</h6>
                                <div class="produk-log-list" id="produkLogList">
                                    <div class="produk-log-empty">Pilih produk untuk melihat log.</div>
                                </div>
                            </div>
                        </aside>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger mr-auto d-none" id="deleteProdukBtn">Hapus</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="saveProdukBtn">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="kemasanPickerModal" tabindex="-1" role="dialog" aria-labelledby="kemasanPickerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="kemasanPickerModalLabel">Pilih Kemasan</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="text" class="form-control produk-kemasan-picker-search" id="kemasanPickerSearch" placeholder="Cari nama kemasan...">
                <div class="produk-kemasan-picker-list" id="kemasanPickerList"></div>
                <div class="form-row mt-3">
                    <div class="form-group col-md-6">
                        <label for="kemasanPickerVendorId">Vendor Kemasan</label>
                        <select class="form-control select2-kemasan-modal" id="kemasanPickerVendorId">
                            <option value="">Pilih Vendor Kemasan</option>
                            @foreach($kemasanVendors as $vendor)
                                <option value="{{ $vendor->id }}">{{ $vendor->nama_vendor }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-6 mb-0">
                        <label for="kemasanPickerDesainVendorId">Vendor Desain</label>
                        <select class="form-control select2-kemasan-modal" id="kemasanPickerDesainVendorId">
                            <option value="">Pilih Vendor Desain</option>
                            @foreach($desainVendors as $vendor)
                                <option value="{{ $vendor->id }}">{{ $vendor->nama_vendor }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="saveKemasanPickerBtn">Simpan</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(function () {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        var inlineStatusOptions = {
            status_kemasan_primer: @json($statusKemasanOptions),
            status_kemasan_sekunder: @json($statusKemasanOptions),
            status_desain_kemasan_primer: @json($statusDesainOptions),
            status_desain_kemasan_sekunder: @json($statusDesainOptions),
            status_administrasi_fpp: @json($statusAdministrasiFppOptions),
            status_administrasi_spk: @json($statusAdministrasiSpkOptions),
            status_administrasi_notif: @json($statusAdministrasiNotifOptions),
            status_sample: @json($statusSampleOptions)
        };

        var kemasanOptions = @json($kemasanPickerOptions);

        var activeKemasanPicker = {
            productId: null,
            field: null,
            currentValue: '',
            selectedValue: '',
            kemasanVendorValue: '',
            desainVendorValue: ''
        };

        function getKemasanRelationConfig(field) {
            if (field === 'kemasan_premier_id') {
                return {
                    title: 'Edit Kemasan Primer',
                    vendorField: 'kemasan_primer_vendor_id',
                    desainVendorField: 'desain_kemasan_primer_id'
                };
            }

            return {
                title: 'Edit Kemasan Sekunder',
                vendorField: 'kemasan_sekunder_vendor_id',
                desainVendorField: 'desain_kemasan_sekunder_id'
            };
        }

        function normalizeStatus(value) {
            return $.trim(String(value || '')).toLowerCase();
        }

        function statusBadge(value) {
            if (!value) {
                return '-';
            }

            var normalized = normalizeStatus(value);
            var badgeClass = '';

            if (normalized === 'done') {
                badgeClass = ' produk-status-badge-done';
            } else if (normalized === 'revisi') {
                badgeClass = ' produk-status-badge-danger';
            } else if (normalized === 'review' || normalized === 'in progress' || normalized === 'progress') {
                badgeClass = ' produk-status-badge-warning';
            }

            return '<span class="produk-status-badge' + badgeClass + '">' + $('<div>').text(value).html() + '</span>';
        }

        function escapeHtml(value) {
            return $('<div>').text(value || '-').html();
        }

        function renderStatusTrigger(productId, field, value) {
            var badgeHtml = value
                ? statusBadge(value)
                : '<span class="produk-status-badge produk-status-badge-empty">set status</span>';

            return '<button type="button" class="produk-status-trigger js-inline-status" data-id="' + productId + '" data-field="' + field + '" data-value="' + escapeHtml(value || '') + '">' + badgeHtml + '</button>';
        }

        function calculateProgress(row) {
            var fields = [
                row.status_administrasi_fpp,
                row.status_administrasi_spk,
                row.status_administrasi_notif,
                row.latest_sample_status,
                row.status_kemasan_primer,
                row.status_desain_kemasan_primer
            ];
            var hasKemasanSekunder = !!row.kemasan_sekunder_id;

            if (hasKemasanSekunder) {
                fields.push(row.status_kemasan_sekunder);
                fields.push(row.status_desain_kemasan_sekunder);
            }

            var completed = fields.filter(function (value) {
                return normalizeStatus(value) === 'done';
            }).length;
            var total = fields.length;
            var percent = total ? Math.round((completed / total) * 100) : 0;

            return {
                completed: completed,
                total: total,
                percent: percent
            };
        }

        function renderNamaColumn(row) {
            var brandName = $.trim(row.brand_name || '');
            var productName = $.trim(row.nama_produk || '');
            var netto = $.trim(row.netto || '');
            var sediaan = $.trim(row.sediaan_name || '');
            var fullName = $.trim((brandName + ' ' + productName + ' ' + netto + ' ' + sediaan).replace(/\s+/g, ' '));
            var bahanAktif = row.bahan_aktif_names === '-' ? '' : '<div class="produk-bahan-list">' + escapeHtml(row.bahan_aktif_names) + '</div>';
            var progress = calculateProgress(row);

            return '<div class="produk-name-stack">'
                + '<button type="button" class="produk-name-trigger js-edit-product" data-id="' + row.id + '"><strong>' + escapeHtml(fullName || '-') + '</strong>' + bahanAktif + '</button>'
                + '<div class="produk-progress-stack">'
                + '<div class="produk-progress-track"><div class="produk-progress-fill" style="width: ' + progress.percent + '%;"></div></div>'
                + '<div class="produk-progress-meta">' + progress.percent + '%</div>'
                + '</div>'
                + '</div>';
        }

        function renderSampleColumn(row) {
            if (!row.has_sample_log) {
                return '<div class="produk-cell-stack">'
                    + '<div class="produk-action-group">'
                    + '<button type="button" class="produk-action-link js-add-sample" data-id="' + row.id + '">Add Sample</button>'
                    + '</div>'
                    + '</div>';
            }

            var noProduksi = escapeHtml(row.latest_sample_no_produksi || '-');
            var produsenVendor = '<div class="produk-bahan-list">Produsen Vendor: ' + escapeHtml(row.produsen_vendor_name || '-') + '</div>';
            var statusHtml = renderStatusTrigger(row.id, 'status_sample', row.latest_sample_status);

            return '<div class="produk-cell-stack">'
                + '<div class="produk-kemasan-line"><div class="produk-cell-value"><strong>' + noProduksi + '</strong></div><div class="produk-kemasan-badges">' + statusHtml + '</div></div>'
                + produsenVendor
                + '<div class="produk-cell-divider"></div>'
                + '<div class="produk-action-group">'
                + '<button type="button" class="produk-action-link js-add-sample" data-id="' + row.id + '">Add Sample</button>'
                + '<button type="button" class="produk-action-link js-sample-history" data-id="' + row.id + '">History</button>'
                + '</div>'
                + '</div>';
        }

        function formatSampleDate(value) {
            if (!value) {
                return '-';
            }

            var date = new Date(value);

            if (isNaN(date.getTime())) {
                return escapeHtml(value);
            }

            return escapeHtml(date.toLocaleString('id-ID', {
                year: 'numeric',
                month: 'short',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            }));
        }

        function renderSampleHistory(logs) {
            if (!logs || !logs.length) {
                return '<div class="produk-meta text-center">Belum ada sample.</div>';
            }

            return '<div class="produk-history-list">' + logs.map(function (log) {
                var encodedNotes = encodeURIComponent(log.notes || '');

                return '<div class="produk-history-item">'
                    + '<div class="produk-history-head">'
                    + '<div class="produk-cell-value"><strong>' + escapeHtml(log.no_produksi || '-') + '</strong></div>'
                    + '<div class="produk-history-time">' + formatSampleDate(log.created_at) + '</div>'
                    + '</div>'
                    + (log.notes ? '<div class="produk-meta">' + escapeHtml(log.notes) + '</div>' : '<div class="produk-meta">-</div>')
                    + '<div class="produk-history-footer">'
                    + '<div class="produk-history-status">' + statusBadge(log.status_sample || '-') + '</div>'
                    + '<div class="produk-action-group">'
                    + '<button type="button" class="produk-action-link js-edit-sample-notes" data-product-id="' + log.produk_id + '" data-sample-id="' + log.id + '" data-notes="' + encodedNotes + '" data-reopen-history="1">Edit Notes</button>'
                    + '<button type="button" class="produk-action-link js-delete-sample" data-product-id="' + log.produk_id + '" data-sample-id="' + log.id + '" data-reopen-history="1">Delete</button>'
                    + '</div>'
                    + '</div>'
                    + '</div>';
            }).join('') + '</div>';
        }

        function openSampleHistory(productId) {
            $.get('{{ url('/rnd/produk') }}/' + productId, function (response) {
                var data = response.data || {};
                var sampleLogs = data.sample_logs || [];

                Swal.fire({
                    title: 'History Sample',
                    html: renderSampleHistory(sampleLogs),
                    width: 640,
                    showConfirmButton: false,
                    showCloseButton: true
                });
            }).fail(function (xhr) {
                var message = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Gagal mengambil history sample.';
                Swal.fire('Error', message, 'error');
            });
        }

        function updateSampleNotes(sampleId, notes, done) {
            $.ajax({
                url: '{{ url('/rnd/produk/sample-log') }}/' + sampleId,
                type: 'POST',
                data: {
                    _method: 'PUT',
                    notes: notes
                },
                success: function (response) {
                    table.ajax.reload(null, false);
                    if (typeof done === 'function') {
                        done();
                        return;
                    }
                    Swal.fire('Sukses', response.message || 'Catatan sample berhasil diperbarui.', 'success');
                },
                error: function (xhr) {
                    var message = 'Terjadi kesalahan saat memperbarui catatan sample.';

                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        message = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }

                    Swal.fire('Validasi gagal', message, 'warning');
                }
            });
        }

        function deleteSample(sampleId, done) {
            $.ajax({
                url: '{{ url('/rnd/produk/sample-log') }}/' + sampleId,
                type: 'POST',
                data: {
                    _method: 'DELETE'
                },
                success: function (response) {
                    table.ajax.reload(null, false);
                    if (typeof done === 'function') {
                        done();
                        return;
                    }
                    Swal.fire('Sukses', response.message || 'Sample berhasil dihapus.', 'success');
                },
                error: function (xhr) {
                    var message = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Gagal menghapus sample.';
                    Swal.fire('Error', message, 'error');
                }
            });
        }

        function stackedField(label, value, allowBadge) {
            var safeValue = value;

            if (!safeValue) {
                safeValue = '-';
            } else if (!allowBadge) {
                safeValue = escapeHtml(safeValue);
            }

            return '<div class="produk-cell-stack">'
                + '<div class="produk-cell-title">' + escapeHtml(label) + '</div>'
                + '<div class="produk-cell-value">' + safeValue + '</div>'
                + '</div>';
        }

        function mergedSection(sections) {
            return sections
                .map(function(section, index) {
                    return stackedField(section.label, section.value, section.allowBadge) + (index < sections.length - 1 ? '<div class="produk-cell-divider"></div>' : '');
                })
                .join('');
        }

        function renderKemasanColumn(productId, kemasanField, kemasanId, statusField, designField, name, status, desain, kemasanVendorId, desainVendorId, kemasanVendorName, desainVendorName) {
            var statusHtml = renderStatusTrigger(productId, statusField, status);
            var desainHtml = renderStatusTrigger(productId, designField, desain);
            var kemasanVendorField = kemasanField === 'kemasan_premier_id' ? 'kemasan_primer_vendor_id' : 'kemasan_sekunder_vendor_id';
            var desainVendorField = kemasanField === 'kemasan_premier_id' ? 'desain_kemasan_primer_id' : 'desain_kemasan_sekunder_id';
            var kemasanName = '<button type="button" class="produk-kemasan-trigger js-inline-kemasan" data-id="' + productId + '" data-field="' + kemasanField + '" data-value="' + escapeHtml(kemasanId || '') + '" data-kemasan-vendor-field="' + kemasanVendorField + '" data-kemasan-vendor-value="' + escapeHtml(kemasanVendorId || '') + '" data-desain-vendor-field="' + desainVendorField + '" data-desain-vendor-value="' + escapeHtml(desainVendorId || '') + '">' + escapeHtml(name || '-') + '</button>';
            var kemasanVendorText = kemasanVendorName
                ? '<div class="produk-bahan-list">Vendor: ' + escapeHtml(kemasanVendorName) + '</div>'
                : '';
            var desainVendorText = 'Design by ' + escapeHtml(desainVendorName || '-');

            return '<div class="produk-cell-stack">'
                + '<div class="produk-kemasan-line">'
                + '<div class="produk-kemasan-name">' + kemasanName + kemasanVendorText + '</div>'
                + '<div class="produk-kemasan-badges">' + statusHtml + '</div>'
                + '</div>'
                + '<div class="produk-cell-divider"></div>'
                + '<div class="produk-kemasan-line"><div class="produk-cell-title">' + desainVendorText + '</div><div class="produk-kemasan-badges">' + desainHtml + '</div></div>'
                + '</div>';
        }

        function renderAdministrasiColumn(row) {
            var parts = [];

            function formatInlineDate(value) {
                if (!value) {
                    return '';
                }

                var date = new Date(value + 'T00:00:00');

                if (isNaN(date.getTime())) {
                    return escapeHtml(value);
                }

                return escapeHtml(date.toLocaleDateString('id-ID', {
                    day: '2-digit',
                    month: 'short',
                    year: 'numeric'
                }));
            }

            function partHtml(label, field, value) {
                var extraMeta = '';

                if (field === 'status_administrasi_notif' && value === 'done' && row.latest_notif_tanggal_selesai) {
                    extraMeta = '<span class="produk-inline-meta">' + formatInlineDate(row.latest_notif_tanggal_selesai) + '</span>';

                    if (row.latest_notif_document_url) {
                        extraMeta += '<a class="produk-inline-link" href="' + encodeURI(row.latest_notif_document_url) + '" target="_blank" rel="noopener noreferrer">View Document</a>';
                    }
                }

                var badgeHtml = '<div class="produk-kemasan-badges">' + extraMeta + renderStatusTrigger(row.id, field, value) + '</div>';
                return '<div class="produk-kemasan-line">'
                    + '<div class="produk-cell-title">' + escapeHtml(label) + '</div>'
                    + badgeHtml
                    + '</div>';
            }

            parts.push(partHtml('FPP', 'status_administrasi_fpp', row.status_administrasi_fpp));
            parts.push('<div class="produk-cell-divider"></div>');
            parts.push(partHtml('SPK', 'status_administrasi_spk', row.status_administrasi_spk));
            parts.push('<div class="produk-cell-divider"></div>');
            parts.push(partHtml('NOTIF', 'status_administrasi_notif', row.status_administrasi_notif));

            return '<div class="produk-cell-stack">' + parts.join('') + '</div>';
        }

        function buildInlineStatusOptions(field) {
            var options = {};

            (inlineStatusOptions[field] || []).forEach(function (option) {
                options[option] = option;
            });

            options.__EMPTY__ = 'Kosongkan status';

            return options;
        }

        function filterKemasanOptions(field, keyword) {
            var tipe = field === 'kemasan_premier_id' ? 'primer' : 'sekunder';
            var search = $.trim(String(keyword || '')).toLowerCase();

            return kemasanOptions.filter(function (option) {
                if (option.tipe_kemasan !== tipe) {
                    return false;
                }

                if (!search) {
                    return true;
                }

                return option.label.toLowerCase().indexOf(search) !== -1;
            });
        }

        function renderKemasanPickerList() {
            var field = activeKemasanPicker.field;
            var productId = activeKemasanPicker.productId;
            var selectedValue = String(activeKemasanPicker.selectedValue || '');
            var keyword = $('#kemasanPickerSearch').val();
            var items = filterKemasanOptions(field, keyword);
            var html = [];

            if (field === 'kemasan_sekunder_id') {
                html.push('<button type="button" class="produk-kemasan-picker-item js-select-kemasan-option' + (selectedValue === '' ? ' is-active' : '') + '" data-id="' + productId + '" data-field="' + field + '" data-value="">Tanpa Kemasan Sekunder<span class="produk-kemasan-picker-item-meta">Kosongkan pilihan sekunder</span></button>');
            }

            items.forEach(function (option) {
                var isActive = String(option.id) === selectedValue;
                var currentMeta = isActive ? '<span class="produk-kemasan-picker-item-meta">Dipilih</span>' : '';
                html.push('<button type="button" class="produk-kemasan-picker-item js-select-kemasan-option' + (isActive ? ' is-active' : '') + '" data-id="' + productId + '" data-field="' + field + '" data-value="' + option.id + '">' + escapeHtml(option.label) + currentMeta + '</button>');
            });

            if (!html.length) {
                html.push('<div class="produk-kemasan-picker-empty">Tidak ada kemasan yang cocok.</div>');
            }

            $('#kemasanPickerList').html(html.join(''));
        }

        function openKemasanPicker(productId, field, value, kemasanVendorValue, desainVendorValue) {
            var config = getKemasanRelationConfig(field);

            activeKemasanPicker = {
                productId: productId,
                field: field,
                currentValue: value || '',
                selectedValue: value || '',
                kemasanVendorValue: kemasanVendorValue || '',
                desainVendorValue: desainVendorValue || ''
            };

            $('#kemasanPickerModalLabel').text(config.title);
            $('#kemasanPickerSearch').val('');
            $('#kemasanPickerVendorId').val(activeKemasanPicker.kemasanVendorValue || '');
            $('#kemasanPickerDesainVendorId').val(activeKemasanPicker.desainVendorValue || '');
            renderKemasanPickerList();
            $('#kemasanPickerModal').modal('show');
        }

        function formatProdukLogDate(value) {
            if (!value) {
                return '-';
            }

            var date = new Date(value);

            if (isNaN(date.getTime())) {
                return escapeHtml(value);
            }

            return escapeHtml(date.toLocaleString('id-ID', {
                year: 'numeric',
                month: 'short',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            }));
        }

        function renderProdukLogs(logs) {
            if (!logs || !logs.length) {
                return '<div class="produk-log-empty">Belum ada log untuk produk ini.</div>';
            }

            return logs.map(function (log) {
                return '<div class="produk-log-item">'
                    + '<div class="produk-log-item-head">'
                    + '<div class="produk-log-item-status">' + escapeHtml(log.status_activity || '-') + '</div>'
                    + '<div class="produk-log-item-time">' + formatProdukLogDate(log.log_date_time) + '</div>'
                    + '</div>'
                    + '<div class="produk-log-item-notes">' + escapeHtml(log.notes || '-') + '</div>'
                    + '</div>';
            }).join('');
        }

        function setProdukLogPanel(logs, isEdit) {
            $('#produkLogPane').toggleClass('is-hidden', !isEdit);
            $('#produkLogList').html(isEdit ? renderProdukLogs(logs || []) : '<div class="produk-log-empty">Pilih produk untuk melihat log.</div>');
        }

        function submitInlineStatus(id, field, value, extraData) {
            var isFormData = extraData instanceof FormData;
            var requestData;

            if (isFormData) {
                requestData = extraData;
                requestData.append('_method', 'PUT');
                requestData.append('inline_status_update', '1');
                requestData.append('field', field);
                requestData.append('value', value);
            } else {
                requestData = $.extend({
                    _method: 'PUT',
                    inline_status_update: 1,
                    field: field,
                    value: value
                }, extraData || {});
            }

            $.ajax({
                url: '{{ url('/rnd/produk') }}/' + id,
                type: 'POST',
                data: requestData,
                processData: !isFormData,
                contentType: isFormData ? false : 'application/x-www-form-urlencoded; charset=UTF-8',
                success: function (response) {
                    table.ajax.reload(null, false);
                    Swal.close();
                    Swal.fire({
                        title: 'Sukses',
                        text: response.message || 'Status berhasil diperbarui.',
                        icon: 'success',
                        timer: 1400,
                        showConfirmButton: false
                    });
                },
                error: function (xhr) {
                    var message = 'Terjadi kesalahan saat memperbarui status.';

                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        message = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }

                    Swal.fire('Validasi gagal', message, 'warning');
                }
            });
        }

        function submitInlineRelation(id, field, value, extraData) {
            $.ajax({
                url: '{{ url('/rnd/produk') }}/' + id,
                type: 'POST',
                data: $.extend({
                    _method: 'PUT',
                    inline_relation_update: 1,
                    field: field,
                    value: value
                }, extraData || {}),
                success: function (response) {
                    table.ajax.reload(null, false);
                    Swal.close();
                    Swal.fire({
                        title: 'Sukses',
                        text: response.message || 'Kemasan berhasil diperbarui.',
                        icon: 'success',
                        timer: 1400,
                        showConfirmButton: false
                    });
                },
                error: function (xhr) {
                    var message = 'Terjadi kesalahan saat memperbarui kemasan.';

                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        message = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }

                    Swal.fire('Validasi gagal', message, 'warning');
                }
            });
        }

        function initProdukSelect2() {
            $('.select2-produk').each(function () {
                var $select = $(this);
                if ($select.data('select2')) {
                    $select.select2('destroy');
                }

                $select.select2({
                    width: '100%',
                    dropdownParent: $('#produkModal')
                });
            });
        }

        function initKemasanModalSelect2() {
            $('.select2-kemasan-modal').each(function () {
                var $select = $(this);
                if ($select.data('select2')) {
                    $select.select2('destroy');
                }

                $select.select2({
                    width: '100%',
                    dropdownParent: $('#kemasanPickerModal'),
                    allowClear: true,
                    placeholder: $select.find('option:first').text()
                });
            });
        }

        function resetForm() {
            $('#produkForm')[0].reset();
            $('#produk_id').val('');
            $('.select2-produk').val(null).trigger('change');
            setProdukLogPanel([], false);
        }

        function toggleProdukFormMode(isEdit) {
            $('.produk-form-create-only').toggleClass('is-hidden', isEdit);
            $('#deleteProdukBtn').toggleClass('d-none', !isEdit).attr('data-id', '');
            $('#produkLogPane').toggleClass('is-hidden', !isEdit);
        }

        function confirmDeleteProduk(id) {
            Swal.fire({
                title: 'Hapus produk?',
                text: 'Data produk yang dihapus tidak bisa dikembalikan.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus',
                cancelButtonText: 'Batal'
            }).then(function (result) {
                if (!result.value) {
                    return;
                }

                $.ajax({
                    url: '{{ url('/rnd/produk') }}/' + id,
                    type: 'POST',
                    data: { _method: 'DELETE' },
                    success: function (response) {
                        $('#produkModal').modal('hide');
                        table.ajax.reload(null, false);
                        Swal.fire('Sukses', response.message || 'Produk berhasil dihapus.', 'success');
                    },
                    error: function (xhr) {
                        var message = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Gagal menghapus produk.';
                        Swal.fire('Error', message, 'error');
                    }
                });
            });
        }

        var table = $('#produkTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{{ route('rnd.products.data') }}',
            columns: [
                { data: null, orderable: false, searchable: false, render: function(data, type, row, meta) { return meta.row + meta.settings._iDisplayStart + 1; } },
                {
                    data: null,
                    name: 'nama_produk',
                    render: function(data, type, row) {
                        return renderNamaColumn(row);
                    }
                },
                {
                    data: null,
                    name: 'status_sample',
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row) {
                        return renderSampleColumn(row);
                    }
                },
                {
                    data: null,
                    name: 'kemasanPremier.nama_kemasan',
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row) {
                        return renderKemasanColumn(
                            row.id,
                            'kemasan_premier_id',
                            row.kemasan_premier_id,
                            'status_kemasan_primer',
                            'status_desain_kemasan_primer',
                            row.kemasan_premier_name,
                            row.status_kemasan_primer,
                            row.status_desain_kemasan_primer,
                            row.kemasan_primer_vendor_id,
                            row.desain_kemasan_primer_id,
                            row.kemasan_primer_vendor_name,
                            row.desain_kemasan_primer_vendor_name
                        );
                    }
                },
                {
                    data: null,
                    name: 'kemasanSekunder.nama_kemasan',
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row) {
                        return renderKemasanColumn(
                            row.id,
                            'kemasan_sekunder_id',
                            row.kemasan_sekunder_id,
                            'status_kemasan_sekunder',
                            'status_desain_kemasan_sekunder',
                            row.kemasan_sekunder_name,
                            row.status_kemasan_sekunder,
                            row.status_desain_kemasan_sekunder,
                            row.kemasan_sekunder_vendor_id,
                            row.desain_kemasan_sekunder_id,
                            row.kemasan_sekunder_vendor_name,
                            row.desain_kemasan_sekunder_vendor_name
                        );
                    }
                },
                {
                    data: null,
                    name: 'status_administrasi_fpp',
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row) {
                        return renderAdministrasiColumn(row);
                    }
                }
            ]
        });

        initProdukSelect2();
        initKemasanModalSelect2();

        $('#createNewProduk').on('click', function () {
            resetForm();
            toggleProdukFormMode(false);
            $('#produkModalLabel').text('Tambah Produk');
            $('#produkModal').modal('show');
        });

        $('body').on('click', '.js-edit-product', function () {
            var id = $(this).data('id');
            resetForm();
            toggleProdukFormMode(true);

            $.get('{{ url('/rnd/produk') }}/' + id, function (response) {
                var data = response.data || {};
                $('#produkModalLabel').text('Edit Produk');
                $('#produk_id').val(data.id || '');
                $('#nama_produk').val(data.nama_produk || '');
                $('#netto').val(data.netto || '');
                $('#brand_id').val(data.brand_id || '').trigger('change');
                $('#produsen_vendor_id').val(data.produsen_vendor_id || '').trigger('change');
                $('#bahan_aktif_ids').val(data.bahan_aktif_ids || []).trigger('change');
                $('#kemasan_premier_id').val(data.kemasan_premier_id || '').trigger('change');
                $('#kemasan_sekunder_id').val(data.kemasan_sekunder_id || '').trigger('change');
                $('#sediaan_id').val(data.sediaan_id || '').trigger('change');
                $('#status_administrasi_fpp').val(data.status_administrasi_fpp || '');
                $('#status_administrasi_spk').val(data.status_administrasi_spk || '');
                $('#status_administrasi_notif').val(data.status_administrasi_notif || '');
                $('#status_kemasan_primer').val(data.status_kemasan_primer || '');
                $('#status_kemasan_sekunder').val(data.status_kemasan_sekunder || '');
                $('#status_desain_kemasan_primer').val(data.status_desain_kemasan_primer || '');
                $('#status_desain_kemasan_sekunder').val(data.status_desain_kemasan_sekunder || '');
                $('#deleteProdukBtn').attr('data-id', data.id || '');
                setProdukLogPanel(data.product_logs || [], true);
                $('#produkModal').modal('show');
            });
        });

        $('#produkForm').on('submit', function (e) {
            e.preventDefault();

            var id = $('#produk_id').val();
            var formData = $(this).serializeArray();

            if (id) {
                formData.push({ name: '_method', value: 'PUT' });
            }

            $.ajax({
                url: id ? '{{ url('/rnd/produk') }}/' + id : '{{ route('rnd.products.store') }}',
                type: 'POST',
                data: $.param(formData),
                success: function (response) {
                    $('#produkModal').modal('hide');
                    table.ajax.reload(null, false);
                    Swal.fire('Sukses', response.message || 'Produk berhasil disimpan.', 'success');
                },
                error: function (xhr) {
                    var message = 'Terjadi kesalahan saat menyimpan data.';
                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        message = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }
                    Swal.fire('Validasi gagal', message, 'warning');
                }
            });
        });

        $('#deleteProdukBtn').on('click', function () {
            var id = $(this).data('id');

            if (!id) {
                return;
            }

            confirmDeleteProduk(id);
        });

        $('body').on('click', '.js-inline-status', function () {
            var id = $(this).data('id');
            var field = $(this).data('field');
            var value = $(this).data('value') || '';

            function openNotifDoneDialog() {
                Swal.fire({
                    title: 'Lengkapi Notif',
                    html: ''
                        + '<input type="date" id="swal-notif-tanggal-mulai" class="swal2-input" placeholder="Tanggal Mulai">'
                        + '<input type="date" id="swal-notif-tanggal-selesai" class="swal2-input" placeholder="Tanggal Selesai">'
                        + '<input type="file" id="swal-notif-doc" class="swal2-file" accept="application/pdf">'
                        + '<textarea id="swal-log-notes" class="swal2-textarea" placeholder="Catatan log (opsional)"></textarea>',
                    showCancelButton: true,
                    confirmButtonText: 'Simpan',
                    cancelButtonText: 'Batal',
                    focusConfirm: false,
                    preConfirm: function () {
                        var tanggalMulai = $('#swal-notif-tanggal-mulai').val();
                        var tanggalSelesai = $('#swal-notif-tanggal-selesai').val();
                        var logNotes = $('#swal-log-notes').val();
                        var fileInput = document.getElementById('swal-notif-doc');
                        var file = fileInput && fileInput.files ? fileInput.files[0] : null;

                        if (!tanggalMulai) {
                            Swal.showValidationMessage('Tanggal mulai wajib diisi.');
                            return false;
                        }

                        if (!tanggalSelesai) {
                            Swal.showValidationMessage('Tanggal selesai wajib diisi.');
                            return false;
                        }

                        if (tanggalSelesai < tanggalMulai) {
                            Swal.showValidationMessage('Tanggal selesai tidak boleh lebih kecil dari tanggal mulai.');
                            return false;
                        }

                        if (!file) {
                            Swal.showValidationMessage('Dokumen PDF wajib diupload.');
                            return false;
                        }

                        if (!/\.pdf$/i.test(file.name)) {
                            Swal.showValidationMessage('Dokumen harus berupa PDF.');
                            return false;
                        }

                        return {
                            tanggal_mulai: tanggalMulai,
                            tanggal_selesai: tanggalSelesai,
                            file: file,
                            log_notes: $.trim(logNotes || '')
                        };
                    }
                }).then(function (notifResult) {
                    if (!notifResult.value) {
                        return;
                    }

                    var formData = new FormData();
                    formData.append('tanggal_mulai', notifResult.value.tanggal_mulai);
                    formData.append('tanggal_selesai', notifResult.value.tanggal_selesai);
                    formData.append('notif_doc', notifResult.value.file);
                    formData.append('log_notes', notifResult.value.log_notes || '');

                    submitInlineStatus(id, field, 'done', formData);
                });
            }

            function openLogNotesDialog(nextValue) {
                Swal.fire({
                    title: 'Catatan Log',
                    input: 'textarea',
                    inputPlaceholder: 'Tambahkan catatan untuk perubahan status ini (opsional)',
                    inputAttributes: {
                        'aria-label': 'Catatan log'
                    },
                    showCancelButton: true,
                    confirmButtonText: 'Simpan',
                    cancelButtonText: 'Batal'
                }).then(function (noteResult) {
                    if (typeof noteResult.value === 'undefined') {
                        return;
                    }

                    submitInlineStatus(id, field, nextValue, {
                        log_notes: $.trim(noteResult.value || '')
                    });
                });
            }

            Swal.fire({
                title: 'Ubah status',
                input: 'radio',
                inputOptions: buildInlineStatusOptions(field),
                inputValue: value || '__EMPTY__',
                showConfirmButton: true,
                confirmButtonText: 'Simpan',
                showCancelButton: false,
                showCloseButton: true,
                allowOutsideClick: true,
                allowEscapeKey: true,
                inputValidator: function (selectedValue) {
                    if (typeof selectedValue === 'undefined' || selectedValue === null) {
                        return 'Pilih salah satu status.';
                    }
                }
            }).then(function (result) {
                if (!result.value) {
                    return;
                }

                var nextValue = result.value === '__EMPTY__' ? '' : result.value;

                if (field === 'status_administrasi_notif' && nextValue === 'done') {
                    openNotifDoneDialog();
                    return;
                }

                openLogNotesDialog(nextValue);
            });
        });

        $('#kemasanPickerSearch').on('input', function () {
            renderKemasanPickerList();
        });

        $('body').on('click', '.js-inline-kemasan', function () {
            openKemasanPicker(
                $(this).data('id'),
                $(this).data('field'),
                $(this).data('value') || '',
                $(this).data('kemasan-vendor-value') || '',
                $(this).data('desain-vendor-value') || ''
            );
        });

        $('body').on('click', '.js-select-kemasan-option', function () {
            activeKemasanPicker.selectedValue = String($(this).data('value') || '');
            renderKemasanPickerList();
        });

        $('#saveKemasanPickerBtn').on('click', function () {
            var field = activeKemasanPicker.field;
            var id = activeKemasanPicker.productId;
            var value = activeKemasanPicker.selectedValue;
            var config = getKemasanRelationConfig(field);

            if (!field || !id) {
                return;
            }

            if (field === 'kemasan_premier_id' && !String(value || '').length) {
                Swal.fire('Validasi gagal', 'Kemasan primer wajib dipilih.', 'warning');
                return;
            }

            $('#kemasanPickerModal').modal('hide');
            submitInlineRelation(id, field, value, {
                [config.vendorField]: $('#kemasanPickerVendorId').val() || '',
                [config.desainVendorField]: $('#kemasanPickerDesainVendorId').val() || ''
            });
        });

        $('body').on('click', '.js-add-sample', function () {
            var id = $(this).data('id');

            Swal.fire({
                title: 'Add Sample',
                html: ''
                    + '<input type="text" id="swal-sample-no-produksi" class="swal2-input" placeholder="No Produksi">'
                    + '<textarea id="swal-sample-notes" class="swal2-textarea" placeholder="Notes"></textarea>',
                showCancelButton: true,
                confirmButtonText: 'Simpan',
                cancelButtonText: 'Batal',
                focusConfirm: false,
                preConfirm: function () {
                    var noProduksi = $('#swal-sample-no-produksi').val();
                    var notes = $('#swal-sample-notes').val();

                    if (!$.trim(noProduksi || '')) {
                        Swal.showValidationMessage('No Produksi wajib diisi.');
                        return false;
                    }

                    return {
                        no_produksi: $.trim(noProduksi),
                        notes: $.trim(notes || '')
                    };
                }
            }).then(function (result) {
                if (!result.value || !result.value) {
                    return;
                }

                $.ajax({
                    url: '{{ url('/rnd/produk') }}/' + id,
                    type: 'POST',
                    data: {
                        _method: 'PUT',
                        add_sample_log: 1,
                        no_produksi: result.value.no_produksi,
                        notes: result.value.notes
                    },
                    success: function (response) {
                        table.ajax.reload(null, false);
                        Swal.fire('Sukses', response.message || 'Sample berhasil ditambahkan.', 'success');
                    },
                    error: function (xhr) {
                        var message = 'Terjadi kesalahan saat menambahkan sample.';

                        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                            message = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                        } else if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        }

                        Swal.fire('Validasi gagal', message, 'warning');
                    }
                });
            });
        });

        $('body').on('click', '.js-sample-history', function () {
            var id = $(this).data('id');

            openSampleHistory(id);
        });

        $('body').on('click', '.js-edit-sample-notes', function () {
            var productId = $(this).data('product-id');
            var sampleId = $(this).data('sample-id');
            var notes = decodeURIComponent($(this).data('notes') || '');
            var reopenHistory = String($(this).data('reopen-history') || '') === '1';

            Swal.fire({
                title: 'Edit Notes Sample',
                input: 'textarea',
                inputValue: notes,
                inputPlaceholder: 'Notes',
                showCancelButton: true,
                confirmButtonText: 'Simpan',
                cancelButtonText: 'Batal'
            }).then(function (result) {
                if (!result.isConfirmed) {
                    if (reopenHistory) {
                        openSampleHistory(productId);
                    }
                    return;
                }

                updateSampleNotes(sampleId, $.trim(result.value || ''), function () {
                    if (reopenHistory) {
                        openSampleHistory(productId);
                        return;
                    }

                    Swal.fire('Sukses', 'Catatan sample berhasil diperbarui.', 'success');
                });
            });
        });

        $('body').on('click', '.js-delete-sample', function () {
            var productId = $(this).data('product-id');
            var sampleId = $(this).data('sample-id');
            var reopenHistory = String($(this).data('reopen-history') || '') === '1';

            Swal.fire({
                title: 'Hapus sample?',
                text: 'Sample yang dihapus tidak bisa dikembalikan.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus',
                cancelButtonText: 'Batal'
            }).then(function (result) {
                if (!result.isConfirmed) {
                    if (reopenHistory) {
                        openSampleHistory(productId);
                    }
                    return;
                }

                deleteSample(sampleId, function () {
                    if (reopenHistory) {
                        openSampleHistory(productId);
                        return;
                    }

                    Swal.fire('Sukses', 'Sample berhasil dihapus.', 'success');
                });
            });
        });
    });
</script>
@endsection