@extends('layouts.erm.app')
@section('title', 'ERM | Forecast Stok Obat')
@section('navbar')
    @include('layouts.erm.navbar-farmasi')
@endsection

@section('content')
@php
    $forecastToday = \Carbon\Carbon::today();
    $forecastWeekEnd = $forecastToday->copy()->endOfWeek();
    $forecastMonthEnd = $forecastToday->copy()->endOfMonth();
    $forecastNextMonthEnd = $forecastToday->copy()->addMonthNoOverflow()->endOfMonth();
@endphp
<div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between mb-2 mt-2">
        <div>
            <h3 class="mb-0">Forecast Farmasi</h3>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0" style="background:transparent;padding:0;margin-top:6px;">
                    <li class="breadcrumb-item"><a href="javascript:void(0);">ERM</a></li>
                    <li class="breadcrumb-item">Farmasi</li>
                    <li class="breadcrumb-item active">Forecast Stok</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="{{ route('erm.obat.index') }}" class="btn btn-outline-primary">
                <i class="fas fa-boxes mr-1"></i> Master Obat
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-9 col-lg-8 mb-3">
            <div class="card h-100">
                <div class="card-body d-flex flex-column">
                    <div class="mb-3">
                        <h5 class="mb-1">Forecast Pembelian</h5>
                        <small class="text-muted">Bagian utama dengan proporsi 3 dari 4. Perhitungan kebutuhan stok berdasarkan histori obat keluar.</small>
                    </div>
                    <div class="row">
                            <div class="col-md-4">
                            <div class="form-group">
                                <label for="forecast_period_months">Obat Keluar</label>
                                <select class="form-control" id="forecast_period_months">
                                    <option value="1">1 Bulan</option>
                                    <option value="3" selected>3 Bulan</option>
                                    <option value="6">6 Bulan</option>
                                    <option value="12">1 Tahun</option>
                                </select>
                            </div>
                        </div>
                            <div class="col-md-4">
                            <div class="form-group">
                                <label for="forecast_pengadaan_frequency">Pengadaan</label>
                                <select class="form-control" id="forecast_pengadaan_frequency">
                                    <option value="monthly">1 Bulan Sekali</option>
                                    <option value="weekly">1 Minggu Sekali</option>
                                    <option value="twice_weekly" selected>1 Minggu 2x</option>
                                </select>
                            </div>
                        </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="forecast_kuota_divisor">Kuota</label>
                                    <select class="form-control" id="forecast_kuota_divisor">
                                        <option value="2">1/2 x Rata-rata Keluar / Bulan</option>
                                        <option value="4" selected>1/4 x Rata-rata Keluar / Bulan</option>
                                        <option value="8">1/8 x Rata-rata Keluar / Bulan</option>
                                    </select>
                                </div>
                            </div>
                    </div>
                    <div class="form-group mb-3">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="forecast_only_below_kuota">
                            <label class="custom-control-label" for="forecast_only_below_kuota">Tampilkan hanya obat dengan total stok kurang dari kuota</label>
                        </div>
                    </div>

                    <div id="forecastLoadingState" class="text-center py-4 d-none">
                        <div class="spinner-border text-primary" role="status"></div>
                        <div class="mt-2 text-muted">Menghitung forecast...</div>
                    </div>

                    <div id="forecastContent" class="d-flex flex-column flex-grow-1">
                        <div class="mb-3">
                            <h5 class="mb-1" id="forecastObatName">Semua Obat Aktif</h5>
                            <small class="text-muted" id="forecastPeriodInfo">-</small>
                        </div>
                        <div class="table-responsive flex-grow-1">
                            <table id="forecastTable" class="table table-bordered table-sm w-100">
                                <thead>
                                    <tr>
                                        <th>Nama Obat</th>
                                        <th>Total Stok</th>
                                        <th>Total Stok + Pending</th>
                                        <th>Obat Keluar</th>
                                        <th>Rata-rata Keluar / Bulan</th>
                                        <th>Kuota</th>
                                        <th>Limit Stok</th>
                                        <th>Pesan / Box</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                        <div class="alert alert-light border mb-0 mt-3" id="forecastFormulaInfo">Rumus: -</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-lg-4 mb-3">
            <div class="card h-100">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex align-items-start justify-content-between mb-3 flex-wrap gap-2 forecast-sidebar-header">
                        <div>
                            <h5 class="mb-1">Forecast Keluar</h5>
                            <small class="text-muted">Bagian samping dengan proporsi 1 dari 4 dari resep farmasi.</small>
                        </div>
                        <div class="w-100">
                            <label for="forecast_keluar_period" class="mb-1">Periode</label>
                            <select class="form-control" id="forecast_keluar_period">
                                <option value="today">Hari Ini ({{ $forecastToday->format('d/m/Y') }})</option>
                                <option value="week">Minggu Ini ({{ $forecastToday->format('d/m/Y') }} - {{ $forecastWeekEnd->format('d/m/Y') }})</option>
                                <option value="month">Bulan Ini ({{ $forecastToday->format('d/m/Y') }} - {{ $forecastMonthEnd->format('d/m/Y') }})</option>
                                <option value="next_month">Bulan Depan ({{ $forecastToday->format('d/m/Y') }} - {{ $forecastNextMonthEnd->format('d/m/Y') }})</option>
                            </select>
                        </div>
                    </div>

                    <div id="forecastKeluarLoadingState" class="text-center py-4 d-none">
                        <div class="spinner-border text-primary" role="status"></div>
                        <div class="mt-2 text-muted">Memuat forecast keluar...</div>
                    </div>

                    <div id="forecastKeluarContent" class="d-flex flex-column flex-grow-1">
                        <div class="mb-3">
                            <small class="text-muted d-block" id="forecastKeluarPeriodInfo">-</small>
                        </div>
                        <div class="table-responsive flex-grow-1">
                            <table id="forecastKeluarTable" class="table table-bordered table-sm w-100">
                                <thead>
                                    <tr>
                                        <th>Nama Obat</th>
                                        <th>Dibutuhkan</th>
                                        <th>Total Stok</th>
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

    <div class="modal fade" id="similarObatModal" tabindex="-1" role="dialog" aria-labelledby="similarObatModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="similarObatModalLabel">Obat Serupa</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="similarObatLoadingState" class="text-center py-4 d-none">
                        <div class="spinner-border text-primary" role="status"></div>
                        <div class="mt-2 text-muted">Memuat obat serupa...</div>
                    </div>
                    <div id="similarObatContent">
                        <div class="mb-3">
                            <h5 class="mb-1" id="similarObatName">-</h5>
                            <div id="similarObatSharedZatAktif" class="forecast-obat-meta"></div>
                            <div class="mt-2 text-muted" id="similarObatSelectedPrices">-</div>
                        </div>
                        <div id="similarObatMessage" class="alert alert-light border d-none mb-3"></div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm mb-0" id="similarObatTable">
                                <thead>
                                    <tr>
                                        <th>Nama Obat</th>
                                        <th>Total Stok</th>
                                        <th>Isi per Box</th>
                                        <th>Harga Beli</th>
                                        <th>Harga Jual</th>
                                        <th>Zat Aktif Sama</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="forecastKeluarDetailModal" tabindex="-1" role="dialog" aria-labelledby="forecastKeluarDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="forecastKeluarDetailModalLabel">Detail Forecast Keluar</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="forecastKeluarDetailLoadingState" class="text-center py-4 d-none">
                        <div class="spinner-border text-primary" role="status"></div>
                        <div class="mt-2 text-muted">Memuat detail visitation...</div>
                    </div>
                    <div id="forecastKeluarDetailContent">
                        <div class="mb-3">
                            <h5 class="mb-1" id="forecastKeluarDetailObatName">-</h5>
                            <small class="text-muted d-block" id="forecastKeluarDetailPeriodInfo">-</small>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm mb-0" id="forecastKeluarDetailTable">
                                <thead>
                                    <tr>
                                        <th>Visitation</th>
                                        <th>Tanggal Visit</th>
                                        <th>Pasien</th>
                                        <th>Jumlah</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<style>
    .gap-2 {
        gap: .5rem;
    }

    .badge-zat-aktif {
        background-color: #007bff !important;
        color: #fff !important;
        font-weight: bold;
        font-size: 0.95em;
        border-radius: 6px;
        padding: 4px 10px;
        margin: 2px 2px;
        display: inline-block;
    }

    .forecast-obat-name {
        font-weight: 500;
    }

    .forecast-search-keywords {
        display: none;
    }

    .forecast-obat-meta {
        margin-top: 6px;
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }

    .forecast-master-faktur-notes {
        margin-top: 6px;
        color: #6c757d;
        font-size: 12px;
        line-height: 1.4;
    }

    .forecast-box-summary {
        text-align: right;
        line-height: 1.35;
    }

    .forecast-box-summary small {
        color: #6c757d;
        font-size: 12px;
    }

    .forecast-stock-plus-permintaan {
        text-align: right;
        line-height: 1.35;
    }

    .forecast-stock-plus-permintaan small {
        color: #6c757d;
        font-size: 12px;
    }

    .btn-obat-favorite {
        border: 0;
        background: transparent;
        padding: 0;
        color: #adb5bd;
        font-size: 20px;
        line-height: 1;
    }

    .btn-obat-favorite.is-favorite {
        color: #f0ad4e;
    }

    .btn-obat-favorite:focus {
        outline: none;
        box-shadow: none;
    }

    .badge-principal-forecast {
        background-color: #17a2b8;
        color: #fff;
        font-weight: 400;
        display: inline-block;
        padding: .25em .4em;
        font-size: 75%;
        line-height: 1;
        text-align: center;
        white-space: nowrap;
        vertical-align: baseline;
        border-radius: .25rem;
    }

    .similar-price-value {
        display: inline-block;
        min-width: 90px;
    }

    .similar-price-compare {
        display: block;
        font-size: 12px;
        margin-top: 2px;
    }

    .similar-price-compare i {
        font-size: 12px;
    }

    .btn-forecast-similar {
        margin-top: 6px;
    }

    .forecast-keluar-row {
        cursor: pointer;
    }

    #similarObatTable td:nth-child(2),
    #similarObatTable td:nth-child(3),
    #similarObatTable td:nth-child(4),
    #similarObatTable td:nth-child(5),
    #similarObatTable th:nth-child(2),
    #similarObatTable th:nth-child(3),
    #similarObatTable th:nth-child(4),
    #similarObatTable th:nth-child(5) {
        text-align: right;
        white-space: nowrap;
        width: 120px;
    }

    #similarObatTable td:last-child,
    #similarObatTable th:last-child {
        width: 30%;
    }

    .forecast-sidebar-header {
        min-height: 96px;
    }

    #forecastKeluarTable td:nth-child(2),
    #forecastKeluarTable th:nth-child(2),
    #forecastTable td:nth-child(2),
    #forecastTable td:nth-child(3),
    #forecastTable td:nth-child(4),
    #forecastTable td:nth-child(5),
    #forecastTable td:nth-child(6),
    #forecastTable td:nth-child(7),
    #forecastTable th:nth-child(2),
    #forecastTable th:nth-child(3),
    #forecastTable th:nth-child(4),
    #forecastTable th:nth-child(5),
    #forecastTable th:nth-child(6),
    #forecastTable th:nth-child(7) {
        text-align: right;
        white-space: nowrap;
    }

    #forecastTable td:nth-child(9),
    #forecastTable th:nth-child(9) {
        text-align: center;
        white-space: nowrap;
        width: 70px;
    }

    #forecastKeluarTable td:last-child,
    #forecastTable td:last-child,
    #forecastTable th:last-child {
        text-align: center;
        white-space: nowrap;
        width: 90px;
    }

    #forecastKeluarDetailTable td:last-child,
    #forecastKeluarDetailTable th:last-child {
        text-align: right;
        white-space: nowrap;
        width: 100px;
    }

    @media (max-width: 991.98px) {
        .forecast-sidebar-header {
            min-height: 0;
        }

        .forecast-sidebar-header,
        .container-fluid > .d-flex {
            flex-direction: column;
            align-items: stretch !important;
        }

        .container-fluid > .d-flex > div:last-child,
        .container-fluid > .d-flex .btn {
            width: 100%;
        }

        .card-body {
            padding: 1rem;
        }

        #forecastTable,
        #forecastKeluarTable,
        #similarObatTable {
            font-size: 0.9rem;
        }

        .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_paginate {
            float: none;
            text-align: left;
            margin-top: .5rem;
        }

        .dataTables_wrapper .dataTables_filter input {
            width: 100%;
            margin-left: 0;
            margin-top: .35rem;
        }

        .btn-forecast-similar {
            width: 100%;
            margin-top: 0;
        }

        #similarObatTable td:nth-child(2),
        #similarObatTable td:nth-child(3),
        #similarObatTable td:nth-child(4),
        #similarObatTable td:nth-child(5),
        #similarObatTable th:nth-child(2),
        #similarObatTable th:nth-child(3),
        #similarObatTable th:nth-child(4),
        #similarObatTable th:nth-child(5) {
            width: auto;
        }
    }

    @media (max-width: 767.98px) {
        .breadcrumb {
            margin-bottom: .75rem !important;
        }

        .forecast-obat-meta {
            gap: 4px;
        }

        #forecastTable,
        #forecastKeluarTable,
        #similarObatTable {
            font-size: 0.85rem;
        }
    }
</style>
<script>
    let forecastKeluarTable = null;
    let forecastTable = null;
    let similarObatRows = [];
    let latestForecastFormulaLabel = '-';

    function formatForecastNumber(value) {
        var numeric = Number(value || 0);
        return numeric.toLocaleString('id-ID', {
            minimumFractionDigits: Number.isInteger(numeric) ? 0 : 2,
            maximumFractionDigits: 2
        });
    }

    function formatRupiah(value) {
        if (value === null || value === undefined || value === '') {
            return '-';
        }

        return 'Rp ' + formatForecastNumber(value);
    }

    function getForecastKuotaDivisor() {
        return Number($('#forecast_kuota_divisor').val() || 4);
    }

    function getForecastKuotaLabel() {
        return $('#forecast_kuota_divisor option:selected').text() || '1/4 x Rata-rata Keluar / Bulan';
    }

    function calculateForecastKuota(row) {
        var averageMonthlyKeluar = Number(row && row.average_monthly_keluar || 0);
        var divisor = getForecastKuotaDivisor();

        if (!divisor) {
            return 0;
        }

        return Math.ceil(averageMonthlyKeluar / divisor);
    }

    function updateForecastFormulaInfo() {
        $('#forecastFormulaInfo').text('Rumus Limit Stok: ' + latestForecastFormulaLabel + '. Rumus Kuota: ' + getForecastKuotaLabel() + '. QTY Pesan = Limit Stok x 3.');
    }

    function isForecastBelowKuotaFilterActive() {
        return $('#forecast_only_below_kuota').is(':checked');
    }

    function shouldShowForecastRow(row) {
        if (!isForecastBelowKuotaFilterActive()) {
            return true;
        }

        return Number(row && row.total_stock || 0) < calculateForecastKuota(row);
    }

    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        if (!settings || !settings.nTable || settings.nTable.id !== 'forecastTable') {
            return true;
        }

        var tableApi = new $.fn.dataTable.Api(settings);
        var rowData = tableApi.row(dataIndex).data();

        if (!rowData) {
            return true;
        }

        return shouldShowForecastRow(rowData);
    });

    function comparePriceClass(value, baseline) {
        if (value === null || value === undefined || value === '' || baseline === null || baseline === undefined || baseline === '') {
            return '';
        }

        var numericValue = Number(value);
        var numericBaseline = Number(baseline);

        if (numericValue < numericBaseline) {
            return 'text-success';
        }

        if (numericValue > numericBaseline) {
            return 'text-danger';
        }

        return 'text-muted';
    }

    function comparePriceIcon(value, baseline) {
        if (value === null || value === undefined || value === '' || baseline === null || baseline === undefined || baseline === '') {
            return '';
        }

        var numericValue = Number(value);
        var numericBaseline = Number(baseline);

        if (numericValue < numericBaseline) {
            return '<i class="fas fa-arrow-down"></i>';
        }

        if (numericValue > numericBaseline) {
            return '<i class="fas fa-arrow-up"></i>';
        }

        return '<i class="fas fa-minus"></i>';
    }

    function renderComparedPrice(value, baseline) {
        var priceText = formatRupiah(value);
        if (priceText === '-') {
            return '-';
        }

        var compareClass = comparePriceClass(value, baseline);
        var compareIcon = comparePriceIcon(value, baseline);
        var compareHtml = compareIcon ? '<span class="similar-price-compare ' + compareClass + '">' + compareIcon + '</span>' : '';

        return '<span class="similar-price-value ' + compareClass + '">' + priceText + '</span>' + compareHtml;
    }

    function setForecastLoading(isLoading) {
        $('#forecastLoadingState').toggleClass('d-none', !isLoading);
        $('#forecastContent').toggleClass('d-none', isLoading);
    }

    function setForecastKeluarLoading(isLoading) {
        $('#forecastKeluarLoadingState').toggleClass('d-none', !isLoading);
        $('#forecastKeluarContent').toggleClass('d-none', isLoading);
    }

    function setForecastKeluarDetailLoading(isLoading) {
        $('#forecastKeluarDetailLoadingState').toggleClass('d-none', !isLoading);
        $('#forecastKeluarDetailContent').toggleClass('d-none', isLoading);
    }

    function formatForecastDate(value) {
        if (!value) {
            return '-';
        }

        return new Date(value).toLocaleDateString('id-ID', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });
    }

    function escapeHtml(value) {
        return $('<div>').text(value || '').html();
    }

    function renderForecastObatName(row) {
        var nameHtml = '<div class="forecast-obat-name">' + escapeHtml(row.obat_nama || '-') + '</div>';
        var badges = [];
        var isGenerik = row.is_generik === 1 || row.is_generik === true || String(row.is_generik) === '1';
        var zatAktifSearchHtml = row && row.zat_aktif_names && row.zat_aktif_names.length
            ? '<div class="forecast-search-keywords">' + escapeHtml(row.zat_aktif_names.join(' ')) + '</div>'
            : '';

        badges.push('<span class="badge ' + (isGenerik ? 'badge-success' : 'badge-info') + '">' + (isGenerik ? 'Obat Generik' : 'Obat Paten') + '</span>');

        (row.principal_names || []).forEach(function(principalName) {
            badges.push('<span class="badge-principal-forecast">' + escapeHtml(principalName) + '</span>');
        });

        if (!badges.length) {
            badges.push('');
        }

        var notesHtml = row.master_faktur_notes
            ? '<div class="forecast-master-faktur-notes">Catatan: ' + escapeHtml(row.master_faktur_notes) + '</div>'
            : '';

        return nameHtml + zatAktifSearchHtml + '<div class="forecast-obat-meta">' + badges.join('') + '</div>' + notesHtml;
    }

    function renderForecastObatFilterText(row) {
        return [
            row && row.obat_nama ? row.obat_nama : '',
            row && row.zat_aktif_names ? row.zat_aktif_names.join(' ') : ''
        ].join(' ').trim();
    }

    function renderForecastBoxSummary(row) {
        var qtyPesan = row && row.qty_pesan !== null && row.qty_pesan !== undefined ? formatForecastNumber(row.qty_pesan) : '-';
        var isiPerBox = row && row.isi_per_box !== null && row.isi_per_box !== undefined ? formatForecastNumber(row.isi_per_box) : '-';
        var jumlahPesanBox = row && row.jumlah_pesan_box !== null && row.jumlah_pesan_box !== undefined ? formatForecastNumber(row.jumlah_pesan_box) : '-';

        return '<div class="forecast-box-summary">'
            + '<div>' + qtyPesan + '</div>'
            + '<small>Isi/box: ' + isiPerBox + '</small>'
            + '<small>Pesan box: ' + jumlahPesanBox + '</small>'
            + '</div>';
    }

    function renderForecastStockWithApprovedPermintaan(row) {
        var totalCombined = row && row.total_stock_with_approved_permintaan !== null && row.total_stock_with_approved_permintaan !== undefined
            ? formatForecastNumber(row.total_stock_with_approved_permintaan)
            : '-';
        var approvedOutstanding = row && row.approved_outstanding_permintaan !== null && row.approved_outstanding_permintaan !== undefined
            ? formatForecastNumber(row.approved_outstanding_permintaan)
            : '-';

        return '<div class="forecast-stock-plus-permintaan">'
            + '<div>' + totalCombined + '</div>'
            + '<small>Pending: ' + approvedOutstanding + '</small>'
            + '</div>';
    }

    function renderFavoriteButton(row) {
        if (!row || !row.obat_id) {
            return '<span class="text-muted">-</span>';
        }

        var isFavorite = row.is_favorite === true || row.is_favorite === 1 || String(row.is_favorite) === '1';
        var className = 'btn-obat-favorite' + (isFavorite ? ' is-favorite' : '');
        var iconClass = isFavorite ? 'fas fa-star' : 'far fa-star';
        var title = isFavorite ? 'Hapus favorite' : 'Jadikan favorite';

        return '<button type="button" class="' + className + '" data-id="' + row.obat_id + '" title="' + title + '">'
            + '<i class="' + iconClass + '"></i>'
            + '</button>';
    }

    function renderSimilarActionButton(row) {
        if (!row || !row.obat_id) {
            return '-';
        }

        return '<div class="d-flex align-items-center justify-content-center flex-wrap" style="gap:6px;">'
            + renderFavoriteButton(row)
            + '<button type="button" class="btn btn-sm btn-outline-primary btn-forecast-similar" data-id="' + escapeHtml(row.obat_id) + '" data-name="' + escapeHtml(row.obat_nama || '') + '">Obat Serupa</button>'
            + '</div>';
    }

    function setSimilarObatLoading(isLoading) {
        $('#similarObatLoadingState').toggleClass('d-none', !isLoading);
        $('#similarObatContent').toggleClass('d-none', isLoading);
    }

    function renderBadgeList(items, badgeClass) {
        return (items || []).map(function(item) {
            return '<span class="' + badgeClass + '">' + escapeHtml(item) + '</span>';
        }).join(' ');
    }

    function renderSimilarObatRows(rows, selectedHargaBeli, selectedHargaJual) {
        var html = '';

        if (!rows || !rows.length) {
            html = '<tr><td colspan="6" class="text-center text-muted">Tidak ada obat lain dengan zat aktif yang sama.</td></tr>';
            $('#similarObatTable tbody').html(html);
            return;
        }

        rows.forEach(function(row) {
            var generikBadge = '<span class="badge ' + ((row.is_generik === 1 || row.is_generik === true || String(row.is_generik) === '1') ? 'badge-success' : 'badge-info') + '">' + ((row.is_generik === 1 || row.is_generik === true || String(row.is_generik) === '1') ? 'Obat Generik' : 'Obat Paten') + '</span>';
            var principalBadges = renderBadgeList(row.principal_names || [], 'badge-principal-forecast');
            var zatAktifBadges = renderBadgeList(row.matched_zat_aktif || [], 'badge badge-zat-aktif');
            var metaHtml = '<div class="forecast-obat-meta">' + generikBadge + (principalBadges ? ' ' + principalBadges : '') + '</div>';

            html += '<tr>' +
                '<td><div class="forecast-obat-name">' + escapeHtml(row.obat_nama || '-') + '</div>' + metaHtml + '</td>' +
                '<td>' + formatForecastNumber(row.total_stock) + '</td>' +
                '<td>' + (row.isi_per_box === null || row.isi_per_box === undefined ? '-' : formatForecastNumber(row.isi_per_box)) + '</td>' +
                '<td>' + renderComparedPrice(row.harga_beli, selectedHargaBeli) + '</td>' +
                '<td>' + renderComparedPrice(row.harga_jual, selectedHargaJual) + '</td>' +
                '<td>' + (zatAktifBadges || '-') + '</td>' +
                '</tr>';
        });

        $('#similarObatTable tbody').html(html);
    }

    function loadSimilarObats(obatId) {
        setSimilarObatLoading(true);
        $('#similarObatMessage').addClass('d-none').text('');
        $('#similarObatTable tbody').html('');

        $.ajax({
            url: '/erm/obat/' + obatId + '/similar',
            type: 'GET',
            success: function(response) {
                $('#similarObatName').text(response.obat_nama || 'Obat Serupa');
                $('#similarObatSharedZatAktif').html(renderBadgeList(response.shared_zat_aktif || [], 'badge badge-zat-aktif'));
                $('#similarObatSelectedPrices').html(
                    'Total Stok: <strong>' + formatForecastNumber(response.total_stock) + '</strong>'
                    + ' | Isi/Box: <strong>' + (response.isi_per_box === null || response.isi_per_box === undefined ? '-' : formatForecastNumber(response.isi_per_box)) + '</strong>'
                    + ' | Harga Beli: <strong>' + formatRupiah(response.harga_beli) + '</strong>'
                    + ' | Harga Jual: <strong>' + formatRupiah(response.harga_jual) + '</strong>'
                );

                if (response.message) {
                    $('#similarObatMessage').removeClass('d-none').text(response.message);
                }

                renderSimilarObatRows(response.rows || [], response.harga_beli, response.harga_jual);
                $('#similarObatModal').modal('show');
            },
            error: function(xhr) {
                var message = xhr.responseJSON?.message || 'Gagal memuat obat serupa.';
                alert(message);
            },
            complete: function() {
                setSimilarObatLoading(false);
            }
        });
    }

    function renderForecastKeluarDetailRows(rows) {
        var html = '';

        if (!rows || !rows.length) {
            html = '<tr><td colspan="4" class="text-center text-muted">Tidak ada visitation pada periode ini.</td></tr>';
            $('#forecastKeluarDetailTable tbody').html(html);
            return;
        }

        rows.forEach(function(row) {
            html += '<tr>' +
                '<td>' + escapeHtml(row.visitation_id || '-') + '</td>' +
                '<td>' + escapeHtml(formatForecastDate(row.tanggal_visitation)) + '</td>' +
                '<td>' + escapeHtml(row.pasien_nama || '-') + '</td>' +
                '<td>' + formatForecastNumber(row.jumlah) + '</td>' +
                '</tr>';
        });

        $('#forecastKeluarDetailTable tbody').html(html);
    }

    function loadForecastKeluarDetail(obatId) {
        setForecastKeluarDetailLoading(true);
        $('#forecastKeluarDetailTable tbody').html('');

        $.ajax({
            url: '/erm/obat/forecast-keluar/' + obatId + '/detail',
            type: 'GET',
            data: {
                period: $('#forecast_keluar_period').val()
            },
            success: function(response) {
                $('#forecastKeluarDetailObatName').text(response.obat_nama || '-');
                $('#forecastKeluarDetailPeriodInfo').text('Periode ' + response.period_start + ' s/d ' + response.period_end + ' (' + response.period_label + ')');
                renderForecastKeluarDetailRows(response.rows || []);
                $('#forecastKeluarDetailModal').modal('show');
            },
            error: function(xhr) {
                var message = xhr.responseJSON?.message || 'Gagal memuat detail forecast keluar.';
                alert(message);
            },
            complete: function() {
                setForecastKeluarDetailLoading(false);
            }
        });
    }

    function renderForecastKeluarRows(rows) {
        var sortedRows = (rows || []).slice().sort(function(a, b) {
            if (Number(a.dibutuhkan || 0) !== Number(b.dibutuhkan || 0)) {
                return Number(b.dibutuhkan || 0) - Number(a.dibutuhkan || 0);
            }

            return String(a.obat_nama || '').localeCompare(String(b.obat_nama || ''), 'id');
        });

        if (forecastKeluarTable) {
            forecastKeluarTable.clear();
            forecastKeluarTable.rows.add(sortedRows);
            forecastKeluarTable.search('');
            forecastKeluarTable.draw();
            return;
        }

        forecastKeluarTable = $('#forecastKeluarTable').DataTable({
            data: sortedRows,
            pageLength: 10,
            lengthChange: false,
            searching: true,
            ordering: true,
            info: true,
            autoWidth: false,
            order: [],
            createdRow: function(row) {
                $(row).addClass('forecast-keluar-row');
            },
            language: {
                search: 'Cari Obat:',
                emptyTable: 'Tidak ada data obat keluar pada periode ini.',
                zeroRecords: 'Obat tidak ditemukan.',
                info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ obat',
                infoEmpty: 'Tidak ada data obat',
                paginate: {
                    previous: 'Sebelumnya',
                    next: 'Berikutnya'
                }
            },
            columns: [
                {
                    data: null,
                    name: 'obat_nama',
                    render: function(data, type, row) {
                        if (type === 'filter') {
                            return renderForecastObatFilterText(row);
                        }

                        if (type === 'sort') {
                            return row.obat_nama || '';
                        }

                        return renderForecastObatName(row);
                    }
                },
                {
                    data: 'dibutuhkan',
                    name: 'dibutuhkan',
                    render: function(data) {
                        return formatForecastNumber(data);
                    }
                },
                {
                    data: 'total_stock',
                    name: 'total_stock',
                    render: function(data) {
                        return formatForecastNumber(data);
                    }
                }
            ]
        });
    }

    function loadForecastKeluarData() {
        setForecastKeluarLoading(true);

        $.ajax({
            url: '{{ route('erm.obat.forecast-keluar') }}',
            type: 'GET',
            data: {
                period: $('#forecast_keluar_period').val()
            },
            success: function(response) {
                $('#forecastKeluarPeriodInfo').text('Periode ' + response.period_start + ' s/d ' + response.period_end + ' (' + response.period_label + ')');
                renderForecastKeluarRows(response.rows || []);
            },
            error: function(xhr) {
                var message = xhr.responseJSON?.message || 'Gagal memuat forecast keluar obat.';
                alert(message);
            },
            complete: function() {
                setForecastKeluarLoading(false);
            }
        });
    }

    function prioritizeForecastRows(rows) {
        return (rows || []).slice().sort(function(a, b) {
            var aHasKeluar = Number(a.obat_keluar || 0) > 0 ? 1 : 0;
            var bHasKeluar = Number(b.obat_keluar || 0) > 0 ? 1 : 0;
            var aBelowLimit = Number(a.total_stock || 0) < Number(a.limit_stok || 0) ? 1 : 0;
            var bBelowLimit = Number(b.total_stock || 0) < Number(b.limit_stok || 0) ? 1 : 0;
            var aDeficit = Number(a.limit_stok || 0) - Number(a.total_stock || 0);
            var bDeficit = Number(b.limit_stok || 0) - Number(b.total_stock || 0);

            if (aBelowLimit !== bBelowLimit) {
                return bBelowLimit - aBelowLimit;
            }

            if (aBelowLimit && bBelowLimit && aDeficit !== bDeficit) {
                return bDeficit - aDeficit;
            }

            if (aHasKeluar !== bHasKeluar) {
                return bHasKeluar - aHasKeluar;
            }

            if (aHasKeluar && bHasKeluar && Number(a.obat_keluar || 0) !== Number(b.obat_keluar || 0)) {
                return Number(b.obat_keluar || 0) - Number(a.obat_keluar || 0);
            }

            return String(a.obat_nama || '').localeCompare(String(b.obat_nama || ''), 'id');
        });
    }

    function renderForecastRows(rows) {
        var sortedRows = prioritizeForecastRows(rows);

        if (forecastTable) {
            forecastTable.clear();
            forecastTable.rows.add(sortedRows);
            forecastTable.draw(false);
            return;
        }

        forecastTable = $('#forecastTable').DataTable({
            data: sortedRows,
            pageLength: 10,
            lengthChange: false,
            searching: true,
            ordering: true,
            info: true,
            autoWidth: false,
            order: [],
            language: {
                search: 'Cari Obat:',
                emptyTable: 'Tidak ada obat aktif untuk diforecast.',
                zeroRecords: 'Obat tidak ditemukan.',
                info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ obat',
                infoEmpty: 'Tidak ada data obat',
                paginate: {
                    previous: 'Sebelumnya',
                    next: 'Berikutnya'
                }
            },
            columns: [
                {
                    data: null,
                    name: 'obat_nama',
                    render: function(data, type, row) {
                        return renderForecastObatName(row);
                    }
                },
                {
                    data: 'total_stock',
                    name: 'total_stock',
                    render: function(data) {
                        return formatForecastNumber(data);
                    }
                },
                {
                    data: null,
                    name: 'total_stock_with_approved_permintaan',
                    render: function(data, type, row) {
                        if (type === 'sort' || type === 'type') {
                            return Number(row.total_stock_with_approved_permintaan || 0);
                        }

                        return renderForecastStockWithApprovedPermintaan(row);
                    }
                },
                {
                    data: 'obat_keluar',
                    name: 'obat_keluar',
                    render: function(data) {
                        return formatForecastNumber(data);
                    }
                },
                {
                    data: 'average_monthly_keluar',
                    name: 'average_monthly_keluar',
                    render: function(data) {
                        return formatForecastNumber(data);
                    }
                },
                {
                    data: null,
                    name: 'kuota',
                    render: function(data, type, row) {
                        var kuota = calculateForecastKuota(row);

                        if (type === 'sort' || type === 'type') {
                            return kuota;
                        }

                        return formatForecastNumber(kuota);
                    }
                },
                {
                    data: 'limit_stok',
                    name: 'limit_stok',
                    render: function(data) {
                        return formatForecastNumber(data);
                    }
                },
                {
                    data: null,
                    name: 'qty_pesan',
                    render: function(data, type, row) {
                        return renderForecastBoxSummary(row);
                    }
                },
                {
                    data: null,
                    name: 'action',
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row) {
                        return renderSimilarActionButton(row);
                    }
                },
                {
                    data: 'search_keywords',
                    name: 'search_keywords',
                    visible: false,
                    searchable: true,
                    orderable: false
                }
            ]
        });
    }

    function updateForecastFavoriteState(obatId, isFavorite) {
        if (!forecastTable || !obatId) {
            return;
        }

        forecastTable.rows().every(function() {
            var rowData = this.data();

            if (!rowData || String(rowData.obat_id) !== String(obatId)) {
                return;
            }

            rowData.is_favorite = isFavorite;
            this.data(rowData);
        });

        forecastTable.draw(false);
    }

    function loadForecastData() {
        setForecastLoading(true);

        $.ajax({
            url: '{{ route('erm.obat.forecast-all') }}',
            type: 'GET',
            data: {
                period_months: $('#forecast_period_months').val(),
                pengadaan_frequency: $('#forecast_pengadaan_frequency').val()
            },
            success: function(response) {
                $('#forecastObatName').text('Semua Obat Aktif');
                $('#forecastPeriodInfo').text('Periode ' + response.period_start + ' s/d ' + response.period_end);
                latestForecastFormulaLabel = response.formula_label || '-';
                renderForecastRows(response.rows || []);
                updateForecastFormulaInfo();
            },
            error: function(xhr) {
                var message = xhr.responseJSON?.message || 'Gagal memuat forecast stok obat.';
                alert(message);
            },
            complete: function() {
                setForecastLoading(false);
            }
        });
    }

    $(document).ready(function() {
        $(document).on('click', '.btn-forecast-similar', function() {
            var obatId = $(this).data('id');
            loadSimilarObats(obatId);
        });

        $(document).on('click', '.btn-obat-favorite', function() {
            var obatId = $(this).data('id');
            var $button = $(this);

            if (!obatId) {
                return;
            }

            if ($button.prop('disabled')) {
                return;
            }

            $button.prop('disabled', true);

            $.ajax({
                url: '/erm/obat/' + obatId + '/toggle-favorite',
                type: 'PATCH',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    updateForecastFavoriteState(obatId, !!response.is_favorite);
                },
                error: function(xhr) {
                    var message = xhr.responseJSON?.message || 'Gagal mengubah favorite obat.';
                    alert(message);
                },
                complete: function() {
                    $button.prop('disabled', false);
                }
            });
        });

        $('#forecastKeluarTable tbody').on('click', 'tr', function() {
            if (!forecastKeluarTable) {
                return;
            }

            var rowData = forecastKeluarTable.row(this).data();
            if (!rowData || !rowData.obat_id) {
                return;
            }

            loadForecastKeluarDetail(rowData.obat_id);
        });

        $('#forecast_keluar_period').on('change', function() {
            loadForecastKeluarData();
        });

        loadForecastKeluarData();

        $('#forecast_period_months, #forecast_pengadaan_frequency').on('change', function() {
            loadForecastData();
        });

        $('#forecast_kuota_divisor').on('change', function() {
            updateForecastFormulaInfo();

            if (forecastTable) {
                forecastTable.rows().invalidate('data').draw(false);
            }
        });

        $('#forecast_only_below_kuota').on('change', function() {
            if (forecastTable) {
                forecastTable.draw(false);
            }
        });

        loadForecastData();
    });
</script>
@endsection