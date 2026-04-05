@extends('layouts.finance.app')
@section('title', 'Finance | Laporan Keuangan')
@section('navbar')
    @include('layouts.finance.navbar')
@endsection

@section('content')
<style>
    .report-summary-table {
        margin-bottom: 0;
    }

    .report-summary-table td {
        vertical-align: middle;
        font-weight: 700;
    }

    .report-summary-clinic-row {
        font-weight: 800;
        text-align: center;
        border-width: 2px;
        color: #ffffff;
        text-shadow: 0 1px 1px rgba(15, 23, 42, 0.18);
    }

    .report-summary-clinic-blue {
        background: linear-gradient(135deg, #0f4c81 0%, #2563eb 55%, #60a5fa 100%);
        color: #ffffff;
        border-color: #1d4ed8;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.12);
    }

    .report-summary-clinic-purple {
        background: linear-gradient(135deg, #581c87 0%, #7c3aed 52%, #c084fc 100%);
        color: #ffffff;
        border-color: #7c3aed;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.12);
    }

    .report-summary-clinic-peach {
        background: linear-gradient(135deg, #c2410c 0%, #ea580c 48%, #fdba74 100%);
        color: #ffffff;
        border-color: #ea580c;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.12);
    }

    .report-summary-clinic-pink {
        background: linear-gradient(135deg, #9d174d 0%, #db2777 50%, #f9a8d4 100%);
        color: #ffffff;
        border-color: #db2777;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.12);
    }

    .report-summary-clinic-default {
        background: linear-gradient(135deg, #334155 0%, #475569 52%, #64748b 100%);
        color: #ffffff;
        border-color: #475569;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.1);
    }
</style>
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12 d-flex flex-wrap justify-content-between align-items-center" style="gap:1rem;">
            <div>
                <h3 class="mb-0 font-weight-bold">Laporan Keuangan</h3>
                <div class="text-muted small">Pantau performa harian dan bulanan berdasarkan visit selesai serta pendapatan bersih transaksi.</div>
            </div>
            {{-- <div class="alert alert-light border mb-0 py-2 px-3">
                <div class="small text-muted mb-1">Rumus pendapatan bersih</div>
                <div class="font-weight-bold">Total transaksi masuk dikurangi transaksi keluar, termasuk kembalian.</div>
            </div> --}}
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white border-bottom-0">
                    <div class="d-flex flex-wrap justify-content-between align-items-start" style="gap:1rem;">
                        <div>
                            <h5 class="mb-1 font-weight-bold">Laporan Harian</h5>
                            <div class="text-muted small">Ringkasan per tanggal: total visit status 2 dan pendapatan bersih transaksi.</div>
                        </div>
                        <div style="min-width:150px;">
                            <div class="text-muted small">Periode Laporan Harian</div>
                            <input type="date" id="filter-report-date" class="form-control form-control-sm" value="{{ now()->format('Y-m-d') }}">
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="table-laporan-harian" class="table table-bordered report-summary-table w-100 mb-0">
                            <tbody id="table-laporan-harian-body"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white border-bottom-0">
                    <div class="d-flex flex-wrap justify-content-between align-items-start" style="gap:1rem;">
                        <div>
                            <h5 class="mb-1 font-weight-bold">Laporan Bulanan</h5>
                            <div class="text-muted small">Ringkasan per bulan: total visit status 2 dan pendapatan bersih transaksi.</div>
                        </div>
                        <div style="min-width:170px;">
                            <div class="text-muted small">Periode Laporan Bulanan</div>
                            <input type="month" id="filter-report-month" class="form-control form-control-sm" value="{{ now()->format('Y-m') }}">
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="table-laporan-bulanan" class="table table-bordered report-summary-table w-100 mb-0">
                            <tbody id="table-laporan-bulanan-body"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(function() {
        var reportDate = $('#filter-report-date').val() || moment().format('YYYY-MM-DD');
        var reportMonth = $('#filter-report-month').val() || moment().format('YYYY-MM');

        function escapeHtml(value) {
            return $('<div>').text(value == null ? '' : String(value)).html();
        }

        function clinicThemeClass(clinicName) {
            var normalized = String(clinicName || '').trim().toLowerCase();

            if (normalized === 'klinik utama premiere belova') {
                return 'report-summary-clinic-blue';
            }

            if (normalized === 'klinik pratama belova skin & beauty center') {
                return 'report-summary-clinic-purple';
            }

            if (normalized === 'belova dental care' || normalized === 'klinik belova dental care') {
                return 'report-summary-clinic-peach';
            }

            if (normalized === 'belova center living') {
                return 'report-summary-clinic-pink';
            }

            return 'report-summary-clinic-default';
        }

        function renderSummaryRows($tbody, rows) {
            if (!Array.isArray(rows) || !rows.length) {
                $tbody.html('<tr><td colspan="2" class="text-center text-muted">Belum ada data laporan</td></tr>');
                return;
            }

            var html = rows.map(function(row) {
                if (row.row_type === 'clinic_header') {
                    var clinicClass = clinicThemeClass(row.clinic_name || '');

                    return '<tr class="bg-light">' +
                        '<td colspan="2" class="report-summary-clinic-row ' + clinicClass + '">' + escapeHtml(row.clinic_name || '-') + '</td>' +
                    '</tr>';
                }

                return '<tr>' +
                    '<td class="font-weight-bold">' + escapeHtml(row.metric_label || '-') + '</td>' +
                    '<td class="text-right font-weight-bold">' + escapeHtml(row.metric_value_display || '-') + '</td>' +
                '</tr>';
            }).join('');

            $tbody.html(html);
        }

        function loadDailySummary() {
            $('#table-laporan-harian-body').html('<tr><td colspan="2" class="text-center text-muted">Memuat data...</td></tr>');

            $.get('{{ route("finance.laporan-keuangan.daily-data") }}', {
                report_date: reportDate
            }).done(function(res) {
                renderSummaryRows($('#table-laporan-harian-body'), res && res.data ? res.data : []);
            }).fail(function() {
                $('#table-laporan-harian-body').html('<tr><td colspan="2" class="text-center text-danger">Gagal memuat data laporan</td></tr>');
            });
        }

        function loadMonthlySummary() {
            $('#table-laporan-bulanan-body').html('<tr><td colspan="2" class="text-center text-muted">Memuat data...</td></tr>');

            $.get('{{ route("finance.laporan-keuangan.monthly-data") }}', {
                report_month: reportMonth
            }).done(function(res) {
                renderSummaryRows($('#table-laporan-bulanan-body'), res && res.data ? res.data : []);
            }).fail(function() {
                $('#table-laporan-bulanan-body').html('<tr><td colspan="2" class="text-center text-danger">Gagal memuat data laporan</td></tr>');
            });
        }

        $('#filter-report-date').on('change', function() {
            reportDate = $('#filter-report-date').val() || moment().format('YYYY-MM-DD');
            loadDailySummary();
        });

        $('#filter-report-month').on('change', function() {
            reportMonth = $('#filter-report-month').val() || moment().format('YYYY-MM');
            loadMonthlySummary();
        });

        loadDailySummary();
        loadMonthlySummary();
    });
</script>
@endsection