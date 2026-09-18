@extends('layouts.erm.app')
@section('title', 'ERM | Data Pasien')
@section('navbar')
    @include('layouts.erm.navbar')
@endsection
@section('content')
<link rel="stylesheet" href="{{ asset('dastone/vendor/datatable/FixedColumns-4.3.0/css/fixedColumns.bootstrap4.min.css') }}">
<style>
:root.theme-light {
    --pasien-fixed-bg: #ffffff;
    --pasien-fixed-bg-alt: #f7f8fc;
    --pasien-fixed-header-bg: #2f6df6;
    --pasien-fixed-text: #212529;
    --pasien-fixed-border: rgba(0, 0, 0, 0.08);
    --pasien-header-text: #ffffff;
}

:root.theme-dark {
    --pasien-fixed-bg: #2a3042;
    --pasien-fixed-bg-alt: #3a4058;
    --pasien-fixed-header-bg: #2f6df6;
    --pasien-fixed-text: #dfe7ff;
    --pasien-fixed-border: rgba(255, 255, 255, 0.08);
    --pasien-header-text: #ffffff;
}

/* Status Pasien styling in DataTable */
.status-pasien-icon {
    display: inline-flex !important;
    align-items: center;
    justify-content: center;
}

.status-akses-icon {
    display: inline-flex !important;
    align-items: center;
    justify-content: center;
}

.edit-status-btn {
    font-size: 12px;
    padding: 2px;
}

.edit-status-btn:hover {
    background-color: transparent !important;
}

.status-text {
    font-weight: 500;
}

.pasien-notes-preview {
    font-size: 11px;
    line-height: 1.35;
    color: #8a94b8;
    margin-top: 0.2rem;
    white-space: normal;
}

:root.theme-light .pasien-notes-preview {
    color: #5f6b8a;
}

:root.theme-dark .pasien-notes-preview {
    color: #aeb8d8;
}

.pasien-filter-toolbar {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: nowrap;
    overflow-x: auto;
    padding-bottom: 0.25rem;
}

.pasien-filter-item {
    flex: 0 0 170px;
    min-width: 170px;
}

.pasien-filter-item.date-range-filter {
    flex-basis: 150px;
    min-width: 150px;
}

.pasien-filter-item.alamat-filter {
    flex-basis: 220px;
    min-width: 220px;
}

.pasien-filter-actions {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex: 0 0 auto;
}

.pasien-filter-actions .btn {
    width: 40px;
    height: 40px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.pasien-filter-actions .btn i {
    margin-right: 0 !important;
}

.pasien-stats-board {
    display: flex;
    gap: 1rem;
    overflow-x: auto;
    overflow-y: hidden;
    padding-bottom: 0.35rem;
    margin-bottom: 1.25rem;
}

.pasien-stats-section {
    flex: 0 0 auto;
    min-width: 165px;
}

.pasien-stats-group-title {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    margin-bottom: 0.4rem;
    color: var(--pasien-fixed-text);
    opacity: 0.85;
}

.pasien-stats-grid {
    display: flex;
    flex-wrap: nowrap;
    gap: 0.75rem;
}

.pasien-stats-grid > * {
    flex: 0 0 132px;
}

.pasien-stat-card {
    border: 0;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 8px 18px rgba(15, 23, 42, 0.1);
    min-height: 100%;
}

.pasien-stat-card .card-body {
    padding: 0.6rem 0.65rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.pasien-stat-icon {
    width: 30px;
    height: 30px;
    border-radius: 9px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.2);
    color: #fff;
    font-size: 12px;
    flex-shrink: 0;
}

.pasien-stat-label {
    font-size: 9px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    opacity: 0.88;
    color: rgba(255, 255, 255, 0.92);
}

.pasien-stat-value {
    font-size: 1rem;
    line-height: 1.1;
    font-weight: 700;
    color: #fff;
}

.pasien-stat-theme-primary { background: linear-gradient(135deg, #2f6df6, #4f8bff); }
.pasien-stat-theme-success { background: linear-gradient(135deg, #18a957, #2fd27a); }
.pasien-stat-theme-warning { background: linear-gradient(135deg, #d99a00, #f0b429); }
.pasien-stat-theme-danger { background: linear-gradient(135deg, #cf3e5e, #ef5a7a); }
.pasien-stat-theme-info { background: linear-gradient(135deg, #1f88d6, #42a5f5); }
.pasien-stat-theme-dark { background: linear-gradient(135deg, #1f2432, #394055); }
.pasien-stat-theme-teal { background: linear-gradient(135deg, #0f8b8d, #28b7b3); }
.pasien-stat-theme-orange { background: linear-gradient(135deg, #d97706, #f59e0b); }
.pasien-stat-theme-purple { background: linear-gradient(135deg, #7c3aed, #9f67ff); }
.pasien-stat-theme-cyan { background: linear-gradient(135deg, #0891b2, #22c1dc); }
.pasien-stat-theme-rose { background: linear-gradient(135deg, #db2777, #f472b6); }
.pasien-stat-theme-slate { background: linear-gradient(135deg, #475569, #64748b); }

@media (max-width: 767.98px) {
    .pasien-filter-toolbar {
        gap: 0.5rem;
    }

    .pasien-filter-item {
        flex-basis: 150px;
        min-width: 150px;
    }

    .pasien-filter-item.alamat-filter {
        flex-basis: 200px;
        min-width: 200px;
    }

    .pasien-filter-item.date-range-filter {
        flex-basis: 205px;
        min-width: 205px;
    }

    .pasien-filter-actions {
        flex: 0 0 auto;
    }

    .pasien-stats-section {
        min-width: 165px;
    }

    .pasien-stats-grid > * {
        flex-basis: 120px;
    }
}

body {
    overflow-x: auto !important;
}

.page-wrapper,
.page-content,
.container-fluid,
.card,
.card-body {
    min-width: 0;
    overflow-x: visible;
}

#pasiens-table {
    width: 100% !important;
}

#pasiens-table th,
#pasiens-table td {
    white-space: nowrap;
}

#pasiens-table thead th,
#pasiens-table_wrapper table.dataTable thead th,
#pasiens-table_wrapper table.dataTable thead .dtfc-fixed-left,
#pasiens-table_wrapper table.dataTable thead .dtfc-fixed-right,
#pasiens-table_wrapper table.dataTable thead .dtfc-fixed-start,
#pasiens-table_wrapper table.dataTable thead .dtfc-fixed-end {
    background-color: var(--pasien-fixed-header-bg) !important;
    color: var(--pasien-header-text) !important;
    text-transform: uppercase;
    font-weight: 700 !important;
    letter-spacing: 0.04em;
    border-color: rgba(255, 255, 255, 0.12) !important;
    vertical-align: middle;
}

#pasiens-table_wrapper {
    width: 100%;
    overflow-x: visible;
}

#pasiens-table_wrapper .dataTables_scrollBody {
    overflow-x: auto !important;
}

#pasiens-table_wrapper .dataTables_scroll,
#pasiens-table_wrapper .dataTables_scrollHead,
#pasiens-table_wrapper .dataTables_scrollBody {
    width: 100% !important;
}

#pasiens-table_wrapper .dtfc-fixed-left,
#pasiens-table_wrapper .dtfc-fixed-right,
#pasiens-table_wrapper .dtfc-fixed-start,
#pasiens-table_wrapper .dtfc-fixed-end {
    color: var(--pasien-fixed-text) !important;
    box-shadow: none !important;
}

#pasiens-table_wrapper table.dataTable thead .dtfc-fixed-left,
#pasiens-table_wrapper table.dataTable thead .dtfc-fixed-right,
#pasiens-table_wrapper table.dataTable thead .dtfc-fixed-start,
#pasiens-table_wrapper table.dataTable thead .dtfc-fixed-end,
#pasiens-table_wrapper table.dataTable tfoot .dtfc-fixed-left,
#pasiens-table_wrapper table.dataTable tfoot .dtfc-fixed-right,
#pasiens-table_wrapper table.dataTable tfoot .dtfc-fixed-start,
#pasiens-table_wrapper table.dataTable tfoot .dtfc-fixed-end {
    background-color: var(--pasien-fixed-header-bg) !important;
    color: var(--pasien-header-text) !important;
    text-transform: uppercase;
    font-weight: 700 !important;
    letter-spacing: 0.04em;
    border-color: rgba(255, 255, 255, 0.12) !important;
}

#pasiens-table_wrapper table.dataTable tbody tr:nth-of-type(odd) > .dtfc-fixed-left,
#pasiens-table_wrapper table.dataTable tbody tr:nth-of-type(odd) > .dtfc-fixed-right,
#pasiens-table_wrapper table.dataTable tbody tr:nth-of-type(odd) > .dtfc-fixed-start,
#pasiens-table_wrapper table.dataTable tbody tr:nth-of-type(odd) > .dtfc-fixed-end {
    background-color: var(--pasien-fixed-bg) !important;
    color: var(--pasien-fixed-text) !important;
    border-color: var(--pasien-fixed-border) !important;
}

#pasiens-table_wrapper table.dataTable tbody tr:nth-of-type(even) > .dtfc-fixed-left,
#pasiens-table_wrapper table.dataTable tbody tr:nth-of-type(even) > .dtfc-fixed-right,
#pasiens-table_wrapper table.dataTable tbody tr:nth-of-type(even) > .dtfc-fixed-start,
#pasiens-table_wrapper table.dataTable tbody tr:nth-of-type(even) > .dtfc-fixed-end {
    background-color: var(--pasien-fixed-bg-alt) !important;
    color: var(--pasien-fixed-text) !important;
    border-color: var(--pasien-fixed-border) !important;
}

#pasiens-table_wrapper table.dataTable tbody tr:hover > .dtfc-fixed-left,
#pasiens-table_wrapper table.dataTable tbody tr:hover > .dtfc-fixed-right,
#pasiens-table_wrapper table.dataTable tbody tr:hover > .dtfc-fixed-start,
#pasiens-table_wrapper table.dataTable tbody tr:hover > .dtfc-fixed-end {
    filter: brightness(1.03);
}

#pasiens-table_wrapper td:last-child,
#pasiens-table_wrapper .dtfc-fixed-right td:last-child,
#pasiens-table_wrapper .dtfc-fixed-end td:last-child {
    position: relative;
}

#pasiens-table_wrapper td.action-dropdown-open,
#pasiens-table_wrapper .dtfc-fixed-right td.action-dropdown-open,
#pasiens-table_wrapper .dtfc-fixed-end td.action-dropdown-open {
    z-index: 1055 !important;
}

#pasiens-table_wrapper td:last-child .btn-group,
#pasiens-table_wrapper .dtfc-fixed-right td:last-child .btn-group,
#pasiens-table_wrapper .dtfc-fixed-end td:last-child .btn-group {
    position: static;
}

#pasiens-table_wrapper td:last-child .dropdown-menu,
#pasiens-table_wrapper .dtfc-fixed-right td:last-child .dropdown-menu,
#pasiens-table_wrapper .dtfc-fixed-end td:last-child .dropdown-menu {
    z-index: 1060 !important;
}
</style>
@include('erm.partials.modal-daftarkunjungan')
@include('erm.partials.modal-daftarkunjunganproduk')
@include('erm.partials.modal-daftarkunjunganlab')
@include('erm.rawatjalans.partials.modal-daftar-kunjungan')
@include('erm.partials.modal-info-pasien')
@include('erm.partials.modal-ic-pendaftaran')

<!-- Unified Manage Pasien Modal: Status Pasien, Status Akses, Status Review, Merchandise -->
<div class="modal fade" id="modalManagePasien" tabindex="-1" role="dialog" aria-labelledby="modalManagePasienLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalManagePasienLabel">Kelola Pasien</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="font-weight-bold" id="managePasienNama">-</div>
                            <div class="text-muted small">No. RM: <span id="managePasienId">-</span></div>
                        </div>
                    </div>
                </div>
                <hr/>
                <div class="row">
                    <div class="col-md-6">
                        <form id="manageStatusForm">
                            <div class="form-group">
                                <label for="manage_status_pasien">Status Pasien</label>
                                <select class="form-control" id="manage_status_pasien" name="status_pasien" required>
                                    <option value="Regular">Regular</option>
                                    <option value="VIP">VIP</option>
                                    <option value="Familia">Familia</option>
                                    <option value="Black Card">Black Card</option>
                                    <option value="Red Flag">Red Flag</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="manage_status_akses">Status Akses</label>
                                <select class="form-control" id="manage_status_akses" name="status_akses" required>
                                    <option value="normal">Normal</option>
                                    <option value="akses cepat">Akses Cepat</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="manage_status_review">Status Review</label>
                                <select class="form-control" id="manage_status_review" name="status_review" required>
                                    <option value="sudah">Sudah</option>
                                    <option value="belum">Belum</option>
                                </select>
                            </div>
                        </form>
                    </div>
                    <div class="col-md-6">
                        <label class="d-block">Merchandise</label>
                        <div id="unifiedMerchChecklistContainer"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-primary" id="saveManagePasien">Simpan</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Edit Status Akses -->
<div class="modal fade" id="modalEditStatusAkses" tabindex="-1" role="dialog" aria-labelledby="modalEditStatusAksesLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEditStatusAksesLabel">Edit Status Akses</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="editStatusAksesForm">
                    <div class="form-group">
                        <label for="edit_status_akses">Status Akses</label>
                        <select class="form-control" id="edit_status_akses" name="status_akses" required>
                            <option value="normal">Normal</option>
                            <option value="akses cepat">Akses Cepat</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="saveEditStatusAkses">Simpan</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Edit Status Review -->
<div class="modal fade" id="modalEditStatusReview" tabindex="-1" role="dialog" aria-labelledby="modalEditStatusReviewLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEditStatusReviewLabel">Edit Status Review</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="editStatusReviewForm">
                    <div class="form-group">
                        <label for="edit_status_review">Status Review</label>
                        <select class="form-control" id="edit_status_review" name="status_review" required>
                            <option value="sudah">Sudah</option>
                            <option value="belum">Belum</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="saveEditStatusReview">Simpan</button>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid">
                    <!-- Page-Title -->
                    <div class="row">
                        <div class="col-sm-12">
                            <div class="page-title-box">
                                <div class="row">
                                    <div class="col">
                                        <ol class="breadcrumb">
                                            <li class="breadcrumb-item"><a href="javascript:void(0);">ERM</a></li>
                                            <li class="breadcrumb-item active">Data Pasien</li>
                                        </ol>
                                    </div><!--end col-->
                                    <div class="col-auto align-self-center">
                                        <a href="{{ route('erm.pasiens.create') }}" class="btn btn-primary btn-lg">
                                            <i class="fas fa-plus-square mr-2"></i>Pasien Baru
                                        </a>
                                    </div><!--end col-->  
                                </div><!--end row-->                                                              
                            </div><!--end page-title-box-->
                        </div><!--end col-->
                    </div><!--end row-->
                    <!-- end page title end breadcrumb -->

    {{-- Table Pasien --}}
    <div class="card">
        {{-- <div class="card-header bg-primary">
            <h4 class="card-title text-white">Daftar Pasien</h4>
        </div> --}}
        <div class="card-body">
            <div class="pasien-stats-board">
                <div class="pasien-stats-section">
                    <div class="pasien-stats-group-title">Pasien Baru</div>
                    <div class="pasien-stats-grid" id="pasien-summary-stats">
                        <div class="card pasien-stat-card pasien-stat-theme-{{ $stats['total_new']['theme'] }}">
                            <div class="card-body">
                                <div class="pasien-stat-icon"><i class="{{ $stats['total_new']['icon'] }}"></i></div>
                                <div>
                                    <div class="pasien-stat-label">{{ $stats['total_new']['label'] }}</div>
                                    <div class="pasien-stat-value" data-stat-group="summary" data-stat-key="total_new">{{ number_format($stats['total_new']['count']) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="pasien-stats-section">
                    <div class="pasien-stats-group-title">Status Pasien</div>
                    <div class="pasien-stats-grid" id="pasien-status-stats">
                        @foreach($stats['statuses'] as $statusKey => $statusStat)
                            <div class="card pasien-stat-card pasien-stat-theme-{{ $statusStat['theme'] }}">
                                <div class="card-body">
                                    <div class="pasien-stat-icon"><i class="{{ $statusStat['icon'] }}"></i></div>
                                    <div>
                                        <div class="pasien-stat-label">{{ $statusStat['label'] }}</div>
                                        <div class="pasien-stat-value" data-stat-group="statuses" data-stat-key="{{ $statusKey }}">{{ number_format($statusStat['count']) }}</div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="pasien-stats-section">
                    <div class="pasien-stats-group-title">Referral</div>
                    <div class="pasien-stats-grid" id="pasien-referral-stats">
                        @foreach($stats['referrals'] as $referralKey => $referralStat)
                            <div class="card pasien-stat-card pasien-stat-theme-{{ $referralStat['theme'] }}">
                                <div class="card-body">
                                    <div class="pasien-stat-icon"><i class="{{ $referralStat['icon'] }}"></i></div>
                                    <div>
                                        <div class="pasien-stat-label">{{ $referralStat['label'] }}</div>
                                        <div class="pasien-stat-value" data-stat-group="referrals" data-stat-key="{{ $referralKey }}">{{ number_format($referralStat['count']) }}</div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="pasien-filter-toolbar mb-3">
                <div class="pasien-filter-item date-range-filter">
                    <input type="text" id="filter_date_range" class="form-control" placeholder="Tanggal Daftar" value="{{ $defaultStartDate }} - {{ $defaultEndDate }}">
                </div>
                <div class="pasien-filter-item">
                    <input type="text" id="filter_no_rm" class="form-control" placeholder="No RM">
                </div>
                <div class="pasien-filter-item">
                    <input type="text" id="filter_nama" class="form-control" placeholder="Nama">
                </div>
                <div class="pasien-filter-item">
                    <input type="text" id="filter_nik" class="form-control" placeholder="Identitas">
                </div>
                <div class="pasien-filter-item alamat-filter">
                    <input type="text" id="filter_alamat" class="form-control" placeholder="Alamat">
                </div>
                <div class="pasien-filter-item">
                    <select id="filter_status_pasien" class="form-control">
                        <option value="">Semua Status Pasien</option>
                        <option value="Regular">Regular</option>
                        <option value="VIP">VIP</option>
                        <option value="Familia">Familia</option>
                        <option value="Black Card">Black Card</option>
                        <option value="Red Flag">Red Flag</option>
                    </select>
                </div>
                <div class="pasien-filter-item">
                    <select id="filter_referral_type" class="form-control">
                        <option value="">Semua Referral</option>
                        <option value="walk_in">Walk-in</option>
                        <option value="pasien">Pasien</option>
                        <option value="dokter">Dokter</option>
                        <option value="employee">Karyawan</option>
                        <option value="social_media">Social Media</option>
                        <option value="marketplace">Marketplace</option>
                        <option value="event">Event</option>
                        <option value="website">Website</option>
                        <option value="partnership">Partnership</option>
                        <option value="google_maps">Google Maps</option>
                    </select>
                </div>
                <div class="pasien-filter-actions">
                    <button id="btn-filter" class="btn btn-primary" type="button" title="Cari" aria-label="Cari">
                        <i class="fas fa-search"></i>
                    </button>
                    <button id="btn-reset" class="btn btn-secondary" type="button" title="Reset" aria-label="Reset">
                        <i class="fas fa-undo"></i>
                    </button>
                </div>
            </div>
            <table class="table table-bordered table-striped" id="pasiens-table">
                <thead class="text-center font-weight-bold">
                    <tr>
                        <th>ID</th>
                        <th>Nama</th>
                        <th>No Identitas</th>
                        <th>Tanggal Lahir</th>
                        <th>Alamat</th>
                        <th>No HP</th>
                        <th>Referral</th>
                        <th>Last Visit</th>
                        <th>Tanggal Daftar</th>
                        <th>Action</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('dastone/vendor/datatable/FixedColumns-4.3.0/js/dataTables.fixedColumns.min.js') }}"></script>
<script>
window.ERM_STAY_ON_PASIEN_INDEX = true;
$(document).ready(function () {
    $('.select2').select2({ width: '100%' });

    const defaultStartDate = '{{ $defaultStartDate }}';
    const defaultEndDate = '{{ $defaultEndDate }}';

    function formatStatNumber(value) {
        return new Intl.NumberFormat('id-ID').format(parseInt(value || 0, 10));
    }

    function getDateRangePayload() {
        let value = ($('#filter_date_range').val() || '').trim();
        let parts = value.split(' - ');

        if (parts.length === 2 && parts[0] && parts[1]) {
            return {
                start_date: parts[0],
                end_date: parts[1]
            };
        }

        return {
            start_date: defaultStartDate,
            end_date: defaultEndDate
        };
    }

    function renderStatsCards(targetSelector, items, group) {
        let html = Object.keys(items || {}).map(function(key) {
            let item = items[key] || {};
            return '<div class="card pasien-stat-card pasien-stat-theme-' + (item.theme || 'primary') + '">' 
                + '<div class="card-body">'
                + '<div class="pasien-stat-icon"><i class="' + (item.icon || 'fas fa-chart-bar') + '"></i></div>'
                + '<div>'
                + '<div class="pasien-stat-label">' + $('<div>').text(item.label || '-').html() + '</div>'
                + '<div class="pasien-stat-value" data-stat-group="' + group + '" data-stat-key="' + $('<div>').text(key).html() + '">' + formatStatNumber(item.count || 0) + '</div>'
                + '</div>'
                + '</div>'
                + '</div>';
        }).join('');

        $(targetSelector).html(html);
    }

    function renderPasienStats(stats) {
        if (!stats) {
            return;
        }

        $('[data-stat-group="summary"][data-stat-key="total_new"]').text(formatStatNumber((stats.total_new || {}).count || 0));
        renderStatsCards('#pasien-status-stats', stats.statuses || {}, 'statuses');
        renderStatsCards('#pasien-referral-stats', stats.referrals || {}, 'referrals');
    }

    function updatePasienStats() {
        let payload = getDateRangePayload();
        payload.stats = 1;

        return $.ajax({
            url: "{{ route('erm.pasiens.index') }}",
            type: 'GET',
            data: payload
        }).done(function(resp) {
            renderPasienStats(resp);
        });
    }

    $('#filter_date_range').daterangepicker({
        autoUpdateInput: true,
        startDate: defaultStartDate,
        endDate: defaultEndDate,
        locale: {
            format: 'YYYY-MM-DD'
        }
    });

    function reloadPasienIndex() {
        table.ajax.reload();
        updatePasienStats();
    }

    let table = $('#pasiens-table').DataTable({
        processing: true,
        serverSide: true,
        searching: false,
        stripe: true,    // Enable row striping
        scrollX: true,
        scrollCollapse: true,
        autoWidth: false,
        fixedColumns: {
            right: 1
        },
        ajax: {
            url: "{{ route('erm.pasiens.index') }}",
            data: function (d) {
                d.no_rm = $('#filter_no_rm').val();
                d.nama = $('#filter_nama').val();
                d.nik = $('#filter_nik').val();
                d.alamat = $('#filter_alamat').val();
                d.status_pasien = $('#filter_status_pasien').val();
                d.referral_type = $('#filter_referral_type').val();
                d.start_date = getDateRangePayload().start_date;
                d.end_date = getDateRangePayload().end_date;
            }
        },
        columns: [
            { data: 'id', name: 'id' },
            { data: 'nama', name: 'nama' },
            { data: 'nik', name: 'identity_number' },
            { data: 'tanggal_lahir_display', name: 'tanggal_lahir' },
            { data: 'alamat', name: 'alamat' },
            { data: 'no_hp', name: 'no_hp' },
            { data: 'referral_display', name: 'referral_type', orderable: false, searchable: false },
            { data: 'last_visit_display', name: 'visitations_max_tanggal_visitation' },
            { data: 'tanggal_daftar_display', name: 'created_at' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        columnDefs: [
            { targets: 0, width: '50px' },
            {
                // Full alamat renderer (village, district, regency, province)
                targets: 4,
                render: function(data, type, row) {
                    var parts = [];
                    if (row.alamat) parts.push(row.alamat);
                    try {
                        if (row.village && row.village.name) parts.push(row.village.name);
                        if (row.village && row.village.district && row.village.district.name) parts.push(row.village.district.name);
                        if (row.village && row.village.district && row.village.district.regency && row.village.district.regency.name) parts.push(row.village.district.regency.name);
                        if (row.village && row.village.district && row.village.district.regency && row.village.district.regency.province && row.village.district.regency.province.name) parts.push(row.village.district.regency.province.name);
                    } catch (e) {
                        // ignore
                    }
                    return parts.filter(Boolean).join(', ');
                }
            },
            { targets: 7, width: '140px' },
            { targets: 8, width: '140px' },
            { targets: 9, width: '360px' },
            {
                targets: 1,
                render: function(data, type, row) {
                    function escapeHtml(unsafe){
                        if (!unsafe && unsafe !== 0) return '';
                        return String(unsafe).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
                    }
                    var statusIcon = row.status_pasien_icon || '';
                    var notes = (row.notes || '').toString().trim();
                    var link = '<a href="#" class="open-manage-modal d-inline-flex align-items-center font-weight-bold" data-id="'+ escapeHtml(row.id) +'">'+ escapeHtml(data) + statusIcon +'</a>';
                    var notesHtml = notes ? '<small class="pasien-notes-preview">' + escapeHtml(notes) + '</small>' : '';
                    return '<div class="d-flex flex-column">'+ link + notesHtml +'</div>';
                }
            },
            {
                targets: 2,
                render: function(data, type, row) {
                    if (row.identity_display && row.identity_display !== '-') {
                        return row.identity_display;
                    }

                    return data || '-';
                }
            },
            {
                targets: 3,
                render: function(data) {
                    return data || '-';
                }
            },
            {
                targets: 6,
                render: function(data) {
                    return data || 'Walk-in';
                }
            },
            {
                targets: 7,
                render: function(data) {
                    return data || '-';
                }
            },
            {
                targets: 8,
                render: function(data) {
                    return data || '-';
                }
            },
            {
                // Action column — server may supply edit/delete HTML
                targets: 9,
                render: function(data, type, row) {
                    return (data || '');
                }
            }
        ]
    });

    // Expose for modal scripts so they can refresh without full page reload
    window.pasiensTable = table;

    $('#btn-filter').click(function () {
        reloadPasienIndex();
    });

    // Replace "Isi IC" with "View IC" for rows that already have IC
    function refreshIcButtons() {
        var ids = [];
        table.rows({ page: 'current' }).every(function(){
            var r = this.data();
            if (r && r.id) ids.push(r.id.toString());
        });
        if (!ids.length) return;
        $.ajax({
            url: '{{ route('erm.ic_pendaftaran.check') }}',
            type: 'POST',
            data: { ids: ids, _token: $('meta[name="csrf-token"]').attr('content') }
        }).done(function(resp){
            var map = (resp && resp.mappings) ? resp.mappings : {};
            table.rows({ page: 'current' }).every(function(){
                var d = this.data();
                var has = map[(d.id || '').toString()];
                var $holder = $(this.node()).find('.ic-action');
                if (!$holder.length) return;
                if (has) {
                    var pdfUrl = '{{ route('erm.ic_pendaftaran.pdf', ['pasien' => 'PID']) }}'.replace('PID', (d.id || '').toString());
                    $holder.html('<a href="' + pdfUrl + '" target="_blank" class="btn btn-sm btn-outline-secondary" title="Lihat IC (PDF)"><i class="fas fa-file-pdf mr-1"></i>View IC</a>');
                } else {
                    $holder.html('<button type="button" class="btn btn-sm btn-outline-primary btn-open-ic"'
                        + ' title="Isi IC Pendaftaran"'
                        + ' data-id="' + (d.id || '') + '"'
                        + ' data-nama="' + $('<div>').text(d.nama || '').html() + '"'
                        + ' data-identity-label="' + $('<div>').text(d.identity_label || 'Identitas').html() + '"'
                        + ' data-identity-number="' + $('<div>').text(d.identity_number || d.nik || '').html() + '"'
                        + ' data-alamat="' + $('<div>').text(d.alamat || '').html() + '"'
                        + ' data-nohp="' + $('<div>').text(d.no_hp || '').html() + '"'
                        + ' data-tgllahir="' + $('<div>').text(d.tanggal_lahir || '').html() + '">'
                        + '<i class="fas fa-file-signature mr-1"></i>Isi IC</button>');
                }
            });
        });
    }

    table.on('draw', function(){ refreshIcButtons(); });
    refreshIcButtons();

    $('#pasiens-table').on('show.bs.dropdown', '.btn-group', function () {
        $(this).closest('td').addClass('action-dropdown-open');
    });

    $('#pasiens-table').on('hidden.bs.dropdown', '.btn-group', function () {
        $(this).closest('td').removeClass('action-dropdown-open');
    });

    // Reset button functionality
    $('#btn-reset').click(function () {
        // Clear all filter inputs
        $('#filter_no_rm').val('');
        $('#filter_nama').val('');
        $('#filter_nik').val('');
        $('#filter_alamat').val('');
        $('#filter_status_pasien').val('');
        $('#filter_referral_type').val('');
        $('#filter_date_range').data('daterangepicker').setStartDate(defaultStartDate);
        $('#filter_date_range').data('daterangepicker').setEndDate(defaultEndDate);
        $('#filter_date_range').val(defaultStartDate + ' - ' + defaultEndDate);
        
        // Reload table with cleared filters
        reloadPasienIndex();
    });

    // Add Enter key functionality to search fields
    $('#filter_no_rm, #filter_nama, #filter_nik, #filter_alamat').on('keypress', function(e) {
        if (e.which === 13) { // Enter key code
            reloadPasienIndex();
        }
    });

    // Add change event for select dropdowns
    $('#filter_status_pasien, #filter_referral_type').on('change', function() {
        reloadPasienIndex();
    });

    $('#filter_date_range').on('apply.daterangepicker', function(ev, picker) {
        $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
        reloadPasienIndex();
    });

    updatePasienStats();

    // Optional: Add input event for real-time search (search as you type)
    // Uncomment the lines below if you want search-as-you-type functionality
    /*
    $('#filter_no_rm, #filter_nama, #filter_nik, #filter_alamat').on('input', function() {
        clearTimeout(window.searchTimeout);
        window.searchTimeout = setTimeout(function() {
            table.ajax.reload();
        }, 500); // 500ms delay after user stops typing
    });
    */
let currentPasienId;
    $(document).on('click', '.btn-info-pasien', function () {
        let pasienId = $(this).data('id');
        currentPasienId = pasienId;

        $.ajax({
            url: "{{ route('erm.pasien.show', '') }}/" + pasienId, // Fetch patient info
            type: "GET",
            success: function (response) {
                const employeeName = response.employee && response.employee.nama ? response.employee.nama : '';
                const employeeNoInduk = response.employee && response.employee.no_induk ? response.employee.no_induk : '';
                const employeeLabel = employeeName
                    ? employeeName + (employeeNoInduk ? ' (' + employeeNoInduk + ')' : '')
                    : '-';

                // Populate table cells with response data
                $('#info-no-rm').text(response.id);
                $('#info-nama').text(response.nama);
                $('#info-identity-label').text(response.identity_label || 'Identitas');
                $('#info-identity-value').text(response.identity_number || response.nik || '-');
                // Build combined address: alamat, desa, kecamatan, kabupaten, provinsi
                const alamat = response.alamat || '';
                const villageName = response.village && response.village.name ? response.village.name : '';
                const districtName = response.village && response.village.district && response.village.district.name ? response.village.district.name : '';
                const regencyName = response.village && response.village.district && response.village.district.regency && response.village.district.regency.name ? response.village.district.regency.name : '';
                const provinceName = response.village && response.village.district && response.village.district.regency && response.village.district.regency.province && response.village.district.regency.province.name ? response.village.district.regency.province.name : '';

                // Collect non-empty parts and join with comma
                const parts = [];
                if (alamat) parts.push(alamat);
                if (villageName) parts.push(villageName);
                if (districtName) parts.push(districtName);
                if (regencyName) parts.push(regencyName);
                if (provinceName) parts.push(provinceName);

                const fullAddress = parts.join(', ');
                $('#info-alamat').text(fullAddress);
                $('#info-tanggal-lahir').text(response.tanggal_lahir);
                $('#info-jenis-kelamin').text(response.gender);
                $('#info-agama').text(response.agama || '-');
                $('#info-marital-status').text(response.marital_status || '-');
                $('#info-employee').text(employeeLabel);
                $('#info-pendidikan').text(response.pendidikan || '-');
                $('#info-pekerjaan').text(response.pekerjaan || '-');
                $('#info-golongan-darah').text(response.gol_darah || '-');
                $('#info-no-hp').text(response.no_hp || '-');
                $('#info-email').text(response.email || '-');
                $('#info-instagram').text(response.instagram || '-');
                // clear any leftover area spans if present
                $('#info-village').text('');
                $('#info-district').text('');
                $('#info-regency').text('');
                $('#info-province').text('');
                
                // Show the modal
                $('#modalInfoPasien').modal('show');
            },
            error: function () {
                alert("Terjadi kesalahan saat mengambil data pasien.");
            }
        });
    });    $(document).on('click', '#btn-edit-pasien', function() {
        if (currentPasienId) {
            window.location.href = "{{ route('erm.pasiens.create') }}?edit_id=" + currentPasienId;
        }
    });

    // Open IC modal from index actions
    $(document).on('click', '.btn-open-ic', function() {
        // Use attr() to preserve leading zeros
        const id = ($(this).attr('data-id') || '').toString();
        const fallback = {
            id: id,
            nama: ($(this).attr('data-nama') || ''),
            identity_label: ($(this).attr('data-identity-label') || 'Identitas'),
            identity_number: ($(this).attr('data-identity-number') || ''),
            alamat: ($(this).attr('data-alamat') || ''),
            no_hp: ($(this).attr('data-nohp') || ''),
            tanggal_lahir: ($(this).attr('data-tgllahir') || '')
        };

        $.ajax({
            url: "{{ route('erm.pasien.show', '') }}/" + id,
            type: 'GET'
        }).done(function(resp){
            const pasien = {
                id: (resp.id || fallback.id).toString(),
                nama: resp.nama || fallback.nama,
                identity_label: resp.identity_label || fallback.identity_label,
                identity_number: (resp.identity_number || resp.nik || fallback.identity_number).toString(),
                alamat: resp.alamat || fallback.alamat,
                no_hp: (resp.no_hp || fallback.no_hp).toString(),
                tanggal_lahir: resp.tanggal_lahir || fallback.tanggal_lahir
            };
            $('#icModal').data('pasien', pasien).modal('show');
        }).fail(function(){
            // Use fallback if detail endpoint is unavailable
            $('#icModal').data('pasien', fallback).modal('show');
        });
    });

    // Open unified manage modal helper
    let manageOriginal = { pasien: '', akses: '', review: '' };
    function openManageModal(pasienId){
        if (!pasienId) return;
        $('#modalManagePasien').data('pasien-id', pasienId);
        // Load patient raw values
        $.get("{{ route('erm.pasien.show', '') }}/" + pasienId, function(resp){
            manageOriginal.pasien = resp.status_pasien || 'Regular';
            manageOriginal.akses = resp.status_akses || 'normal';
            manageOriginal.review = resp.status_review || 'belum';
            $('#manage_status_pasien').val(manageOriginal.pasien);
            $('#manage_status_akses').val(manageOriginal.akses);
            $('#manage_status_review').val(manageOriginal.review);
            $('#managePasienNama').text(resp.nama || '-');
            $('#managePasienId').text(resp.id || pasienId);
        }).always(function(){
            // Load merchandise data in parallel
            let pid = $('#modalManagePasien').data('pasien-id');
            $('#unifiedMerchChecklistContainer').html('<p class="text-muted">Memuat...</p>');
            $.when(
                $.get('/marketing/master-merchandise/data').fail(()=>{}),
                $.get('/erm/pasiens/' + pid + '/merchandises').fail(()=>{})
            ).done(function(masterResp, pasienResp){
                let masterData = masterResp && masterResp[0] ? (masterResp[0].data || masterResp[0]) : [];
                let pasienData = pasienResp && pasienResp[0] ? (pasienResp[0].data || pasienResp[0]) : [];
                renderMerchChecklist(masterData, pasienData);
            }).fail(function(){
                $('#unifiedMerchChecklistContainer').html('<p class="text-danger">Gagal memuat data.</p>');
            });
            $('#modalManagePasien').modal('show');
        });
    }
    // Trigger by clicking patient name
    $(document).on('click', '.open-manage-modal', function(e){ e.preventDefault(); openManageModal($(this).data('id')); });
    // Also trigger when clicking existing merchandise "Lihat" buttons
    $(document).on('click', '.btn-merch-checklist', function(){ openManageModal($(this).data('id')); });

    // Save all statuses from unified modal
    $('#saveManagePasien').on('click', function(){
        let pasienId = $('#modalManagePasien').data('pasien-id');
        let p = $('#manage_status_pasien').val();
        let a = $('#manage_status_akses').val();
        let r = $('#manage_status_review').val();
        let reqs = [];
        reqs.push($.post('/erm/pasiens/' + pasienId + '/update-status', { _token: $('meta[name="csrf-token"]').attr('content'), status_pasien: p }));
        reqs.push($.post('/erm/pasiens/' + pasienId + '/update-status-akses', { _token: $('meta[name="csrf-token"]').attr('content'), status_akses: a }));
        reqs.push($.post('/erm/pasiens/' + pasienId + '/update-status-review', { _token: $('meta[name="csrf-token"]').attr('content'), status_review: r }));
        $.when.apply($, reqs).done(function(){
            Swal.fire({ icon: 'success', title: 'Tersimpan', text: 'Status pasien diperbarui.', timer: 1500, showConfirmButton: false });
            table.ajax.reload(null, false);
        }).fail(function(){
            Swal.fire({ icon: 'error', title: 'Gagal', text: 'Tidak dapat menyimpan status.' });
        });
    });

    // Handle edit status akses button click
    $(document).on('click', '.edit-status-akses-btn', function() {
        let pasienId = $(this).data('pasien-id');
        let currentStatus = $(this).data('current-status');
        
        $('#edit_status_akses').val(currentStatus);
        $('#modalEditStatusAkses').data('pasien-id', pasienId);
        $('#modalEditStatusAkses').modal('show');
    });
    
    // Handle save status akses
    $('#saveEditStatusAkses').on('click', function() {
        let pasienId = $('#modalEditStatusAkses').data('pasien-id');
        let newStatus = $('#edit_status_akses').val();
        
        $.ajax({
            url: '/erm/pasiens/' + pasienId + '/update-status-akses',
            type: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                status_akses: newStatus
            },
            success: function(response) {
                if(response.success) {
                    $('#modalEditStatusAkses').modal('hide');
                    table.ajax.reload(); // Reload the DataTable
                    
                    // Show success message
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: 'Status akses pasien berhasil diperbarui.',
                        timer: 2000,
                        showConfirmButton: false
                    });
                }
            },
            error: function(xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'Gagal memperbarui status akses pasien.',
                });
            }
        });

    });

    // Handle edit status review button click
    $(document).on('click', '.edit-status-review-btn', function() {
        let pasienId = $(this).data('pasien-id');
        let currentStatus = $(this).data('current-status');
        
        $('#edit_status_review').val(currentStatus);
        $('#modalEditStatusReview').data('pasien-id', pasienId);
        $('#modalEditStatusReview').modal('show');
    });
    
    // Handle save status review
    $('#saveEditStatusReview').on('click', function() {
        let pasienId = $('#modalEditStatusReview').data('pasien-id');
        let newStatus = $('#edit_status_review').val();
        
        $.ajax({
            url: '/erm/pasiens/' + pasienId + '/update-status-review',
            type: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                status_review: newStatus
            },
            success: function(response) {
                if(response.success) {
                    $('#modalEditStatusReview').modal('hide');
                    table.ajax.reload(); // Reload the DataTable
                    
                    // Show success message
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: 'Status review pasien berhasil diperbarui.',
                        timer: 2000,
                        showConfirmButton: false
                    });
                }
            },
            error: function(xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'Gagal memperbarui status review pasien.',
                });
            }
        });

    });

    // Merchandise checklist logic (used inside unified modal)
        function getMerchandiseErrorMessage(xhr, fallbackMessage) {
            if (xhr && xhr.responseJSON) {
                return xhr.responseJSON.message || xhr.responseJSON.error || fallbackMessage;
            }

            return fallbackMessage;
        }

        function getNullableInt(value) {
            if (value === null || value === undefined || value === '') {
                return null;
            }

            let parsed = parseInt(value, 10);
            return Number.isNaN(parsed) ? null : parsed;
        }

        function getMerchandiseMaxQty($input) {
            let remaining = getNullableInt($input.data('remaining'));
            let currentQty = parseInt($input.data('currentQty') || 0, 10);

            if (remaining === null) {
                return null;
            }

            return Math.max(0, remaining + currentQty);
        }

        function syncMerchandiseQtyControls($input) {
            let qty = parseInt($input.val() || 1, 10);
            let maxQty = getMerchandiseMaxQty($input);
            let merchId = $input.data('id');
            let $minus = $('.merch-qty-minus[data-id="' + merchId + '"]');
            let $plus = $('.merch-qty-plus[data-id="' + merchId + '"]');

            $minus.prop('disabled', qty <= 1);
            $plus.prop('disabled', maxQty !== null && qty >= maxQty);
        }

        function validateMerchandiseQty($input, qty) {
            let maxQty = getMerchandiseMaxQty($input);

            if (maxQty !== null && maxQty <= 0) {
                Swal.fire({ icon: 'warning', title: 'Limit bulanan habis', text: 'Merchandise ini sudah mencapai limit bulan berjalan.' });
                return { valid: false, qty: 0 };
            }

            if (maxQty !== null && qty > maxQty) {
                Swal.fire({ icon: 'warning', title: 'Melebihi limit bulanan', text: `Qty (${qty}) melebihi sisa limit yang tersedia (${maxQty}).` });
                return { valid: false, qty: maxQty };
            }

            return { valid: true, qty: qty };
        }

        function loadManagePasienMerchandise(pasienId) {
            $('#unifiedMerchChecklistContainer').html('<p class="text-muted">Memuat...</p>');

            $.when(
                $.get('/marketing/master-merchandise/data').fail(()=>{}),
                $.get('/erm/pasiens/' + pasienId + '/merchandises').fail(()=>{})
            ).done(function(masterResp, pasienResp){
                let masterData = masterResp && masterResp[0] ? (masterResp[0].data || masterResp[0]) : [];
                let pasienData = pasienResp && pasienResp[0] ? (pasienResp[0].data || pasienResp[0]) : [];
                renderMerchChecklist(masterData, pasienData);
            }).fail(function(){
                $('#unifiedMerchChecklistContainer').html('<p class="text-danger">Gagal memuat data.</p>');
            });
        }

        function renderMerchChecklist(masterList, pasienReceipts) {
            let receivedIds = (pasienReceipts || []).map(r => (r.merchandise_id || r.merchandise_id === 0) ? r.merchandise_id : null).filter(Boolean);
            let $container = $('#unifiedMerchChecklistContainer');
            $container.empty();

            if (!masterList.length) {
                $container.html('<p class="text-muted">No merchandise items available.</p>');
                return;
            }

            let $form = $('<div class="list-group"></div>');
            let qtyMap = {};
            let pmIdMap = {};
            (pasienReceipts || []).forEach(r => {
                if (r.merchandise_id) {
                    qtyMap[r.merchandise_id] = r.quantity || 1;
                    pmIdMap[r.merchandise_id] = r.id || '';
                }
            });

            masterList.forEach(item => {
                let received = receivedIds.includes(item.id);
                let qty = received ? (qtyMap[item.id] || 1) : 1;
                let monthlyLimit = getNullableInt(item.monthly_limit_stock);
                let remaining = getNullableInt(item.remaining_monthly_stock);
                let maxQty = monthlyLimit === null ? null : Math.max(0, (remaining || 0) + (received ? qty : 0));
                let exhausted = maxQty !== null && maxQty <= 0 && !received;
                let disabledAttr = exhausted ? 'disabled' : '';
                let limitBadge = monthlyLimit === null
                    ? '<small class="text-muted ml-2">Tanpa limit bulanan</small>'
                    : (exhausted
                        ? `<small class="text-danger ml-2">Limit: ${monthlyLimit} - habis bulan ini</small>`
                        : `<small class="text-muted ml-2">Limit: ${monthlyLimit}</small>`);
                let statusBadge = received ? '<span class="badge badge-success ml-2">Sudah diberikan</span>' : '';

                let $row = $(
                    `<div class="list-group-item merch-item-row" data-id="${item.id}">
                        <div class="d-flex align-items-start justify-content-between">
                            <div class="pr-3">
                                <div><strong>${item.name}</strong> ${limitBadge} ${statusBadge}</div>
                                <div class="small text-muted">${item.description || ''}</div>
                            </div>
                            <div class="text-right" style="min-width: 190px;">
                                <div class="input-group input-group-sm justify-content-end">
                                    <div class="input-group-prepend">
                                        <button type="button" class="btn btn-outline-secondary merch-qty-minus" data-id="${item.id}" ${disabledAttr}>-</button>
                                    </div>
                                    <input type="number" min="1" ${maxQty !== null ? `max="${maxQty}"` : ''} class="form-control form-control-sm merch-qty text-center" data-id="${item.id}" data-pm-id="${pmIdMap[item.id] || ''}" data-current-qty="${received ? qty : 0}" data-remaining="${remaining ?? ''}" value="${qty}" style="max-width:70px;" ${disabledAttr}>
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-outline-secondary merch-qty-plus" data-id="${item.id}" ${disabledAttr}>+</button>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <button type="button" class="btn btn-sm btn-primary merch-save" data-id="${item.id}" ${disabledAttr}>${received ? 'Ubah' : 'Beri'}</button>
                                    ${received ? `<button type="button" class="btn btn-sm btn-outline-danger merch-remove" data-id="${item.id}">Hapus</button>` : ''}
                                </div>
                            </div>
                        </div>
                    </div>`
                );
                $form.append($row);
            });

            $container.append($form);
            $('.merch-qty').each(function(){ syncMerchandiseQtyControls($(this)); });
        }

        // No dedicated open handler; merchandise loads inside unified modal open

        $(document).on('click', '.merch-qty-minus, .merch-qty-plus', function(){
            let $button = $(this);
            let merchId = $button.data('id');
            let $input = $('.merch-qty[data-id="' + merchId + '"]');
            let qty = parseInt($input.val() || 1, 10);
            qty = $button.hasClass('merch-qty-plus') ? qty + 1 : Math.max(1, qty - 1);
            let validation = validateMerchandiseQty($input, qty);
            $input.val(validation.valid ? validation.qty : Math.max(1, validation.qty || qty));
            syncMerchandiseQtyControls($input);
        });

        $(document).on('input change', '.merch-qty', function(){
            let $input = $(this);
            let qty = parseInt($input.val() || 1, 10);
            if (qty < 1) { qty = 1; $input.val(1); }
            let validation = validateMerchandiseQty($input, qty);
            if (!validation.valid) {
                if (validation.qty > 0) {
                    $input.val(validation.qty);
                }
                return;
            }
            qty = validation.qty;

            $input.val(qty);
            syncMerchandiseQtyControls($input);
        });

        $(document).on('click', '.merch-save', function(){
            let merchId = $(this).data('id');
            let pasienId = $('#modalManagePasien').data('pasien-id');
            if (!pasienId) return alert('Pasien ID missing');

            let $input = $('.merch-qty[data-id="' + merchId + '"]');
            let qty = parseInt($input.val() || 1, 10);
            let validation = validateMerchandiseQty($input, qty);
            if (!validation.valid) {
                $input.val(Math.max(1, validation.qty || qty));
                return;
            }
            qty = validation.qty;

            let pmId = $input.data('pm-id');
            if (pmId) {
                $.ajax({
                    url: '/erm/pasiens/' + pasienId + '/merchandises/' + pmId,
                    type: 'PUT',
                    data: { _token: $('meta[name="csrf-token"]').attr('content'), quantity: qty },
                    success: function(resp){
                        $input.data('currentQty', qty);
                        if (resp && Object.prototype.hasOwnProperty.call(resp, 'remaining_monthly_stock')) {
                            $input.data('remaining', resp.remaining_monthly_stock);
                        }
                        loadManagePasienMerchandise(pasienId);
                    },
                    error: function(xhr){
                        Swal.fire({ icon: 'error', title: 'Gagal', text: getMerchandiseErrorMessage(xhr, 'Failed to update quantity') });
                    }
                });
                return;
            }

            $.post('/erm/pasiens/' + pasienId + '/merchandises', {
                _token: $('meta[name="csrf-token"]').attr('content'),
                merchandise_id: merchId,
                quantity: qty
            }, function(resp){
                if (resp && resp.id) {
                    $input.data('pm-id', resp.id);
                }
                loadManagePasienMerchandise(pasienId);
            }).fail(function(xhr){
                Swal.fire({ icon: 'error', title: 'Gagal', text: getMerchandiseErrorMessage(xhr, 'Failed to add merchandise') });
            });
        });

        $(document).on('click', '.merch-remove', function(){
            let merchId = $(this).data('id');
            let pasienId = $('#modalManagePasien').data('pasien-id');
            if (!pasienId) return alert('Pasien ID missing');

            let $input = $('.merch-qty[data-id="' + merchId + '"]');
            let pmId = $input.data('pm-id');
            let handleDelete = function(targetPmId) {
                $.ajax({
                    url: '/erm/pasiens/' + pasienId + '/merchandises/' + targetPmId,
                    type: 'DELETE',
                    data: { _token: $('meta[name="csrf-token"]').attr('content') },
                    success: function(){
                        loadManagePasienMerchandise(pasienId);
                    },
                    error: function(xhr){
                        Swal.fire({ icon: 'error', title: 'Gagal', text: getMerchandiseErrorMessage(xhr, 'Failed to remove merchandise') });
                    }
                });
            };

            if (pmId) {
                handleDelete(pmId);
                return;
            }

            $.get('/erm/pasiens/' + pasienId + '/merchandises', function(resp){
                let rec = (resp.data || []).find(r => r.merchandise_id == merchId);
                if (!rec) return;
                handleDelete(rec.id);
            });
        });
    });
</script>
@endsection
