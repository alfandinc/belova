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

    .report-summary-clickable {
        cursor: pointer;
        transition: background-color 0.18s ease;
    }

    .report-summary-clickable:hover td {
        background-color: rgba(37, 99, 235, 0.06);
    }

    .report-cutoff-card {
        border: 1px solid #dbe7ff;
        background: linear-gradient(135deg, #f8fbff 0%, #eef5ff 100%);
    }

    .report-cutoff-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 1rem;
    }

    .report-cutoff-item {
        border: 1px solid #d7e3f8;
        border-radius: 0.75rem;
        background: #ffffff;
        padding: 1rem;
        box-shadow: 0 8px 18px rgba(37, 99, 235, 0.05);
    }

    .report-cutoff-item label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 700;
        color: #1e3a8a;
    }

    .report-cutoff-help {
        color: #64748b;
        font-size: 0.85rem;
    }

    .report-settings-trigger {
        min-width: 170px;
    }

    .report-cutoff-feedback {
        display: none;
    }
</style>
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12 d-flex flex-wrap justify-content-between align-items-center" style="gap:1rem;">
            <div>
                <h3 class="mb-0 font-weight-bold">Laporan Keuangan</h3>
                <div class="text-muted small">Pantau performa harian dan bulanan berdasarkan visit selesai serta pendapatan bersih transaksi.</div>
            </div>
            <button type="button" class="btn btn-outline-primary report-settings-trigger" data-toggle="modal" data-target="#modal-report-cutoff">
                Atur Cut Off Klinik
            </button>
            {{-- <div class="alert alert-light border mb-0 py-2 px-3">
                <div class="small text-muted mb-1">Rumus pendapatan bersih</div>
                <div class="font-weight-bold">Total transaksi masuk dikurangi transaksi keluar, termasuk kembalian.</div>
            </div> --}}
        </div>
    </div>

    <div id="report-page-feedback" class="alert border-0 shadow-sm report-cutoff-feedback" role="alert"></div>

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

<div class="modal fade" id="modal-report-detail" tabindex="-1" role="dialog" aria-labelledby="modal-report-detail-label" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-report-detail-label">Detail Laporan</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped mb-0">
                        <thead id="report-detail-head"></thead>
                        <tbody id="report-detail-body"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modal-report-cutoff" tabindex="-1" role="dialog" aria-labelledby="modal-report-cutoff-label" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        <div class="modal-content report-cutoff-card">
            <form method="POST" action="{{ route('finance.laporan-keuangan.cutoffs.update') }}" id="report-cutoff-form">
                @csrf
                <div class="modal-header border-bottom-0">
                    <div>
                        <h5 class="modal-title font-weight-bold" id="modal-report-cutoff-label">Cut Off Jam Per Klinik</h5>
                        <div class="text-muted small">Transaksi sebelum jam cut off akan dihitung ke hari sebelumnya. Isi 02:00 jika praktik yang selesai sebelum jam 2 pagi masih milik hari sebelumnya.</div>
                    </div>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body pt-0">
                    <div id="report-cutoff-modal-feedback" class="alert report-cutoff-feedback" role="alert"></div>
                    <div class="report-cutoff-grid mb-3">
                        @foreach($clinics as $clinic)
                            <div class="report-cutoff-item">
                                <label for="cutoff_{{ $clinic->id }}">{{ $clinic->nama }}</label>
                                <input
                                    type="time"
                                    id="cutoff_{{ $clinic->id }}"
                                    name="cutoffs[{{ $clinic->id }}]"
                                    class="form-control"
                                    value="{{ old('cutoffs.' . $clinic->id, \Illuminate\Support\Str::of($clinic->report_cutoff_time ?? '00:00:00')->substr(0, 5)) }}"
                                >
                                <div class="report-cutoff-help mt-2">Default 00:00 berarti laporan tetap ganti hari tepat tengah malam.</div>
                            </div>
                        @endforeach
                    </div>
                    <div class="report-cutoff-help">Perubahan berlaku ke laporan harian, bulanan, dan detail pendapatan/piutang.</div>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-light" data-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-primary" id="report-cutoff-submit">Simpan Cut Off</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(function() {
        var reportDate = $('#filter-report-date').val() || moment().format('YYYY-MM-DD');
        var reportMonth = $('#filter-report-month').val() || moment().format('YYYY-MM');
        var $cutoffForm = $('#report-cutoff-form');
        var $cutoffSubmit = $('#report-cutoff-submit');
        var cutoffSubmitDefaultLabel = $cutoffSubmit.text();

        function showAlert($element, type, message) {
            $element
                .removeClass('alert-success alert-danger alert-info')
                .addClass('alert-' + type)
                .html(escapeHtml(message || ''))
                .stop(true, true)
                .fadeIn(150);
        }

        function hideAlert($element) {
            $element.hide().removeClass('alert-success alert-danger alert-info').html('');
        }

        function refreshSummaries() {
            loadDailySummary();
            loadMonthlySummary();
        }

        function applyReturnedCutoffs(cutoffs) {
            if (!cutoffs || typeof cutoffs !== 'object') {
                return;
            }

            Object.keys(cutoffs).forEach(function(clinicId) {
                $('#cutoff_' + clinicId).val(cutoffs[clinicId] || '00:00');
            });
        }

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

                var isClickable = row.metric_key === 'pendapatan' || row.metric_key === 'piutang';
                var rowClass = isClickable ? 'report-summary-clickable' : '';
                var dataAttributes = isClickable
                    ? ' data-metric-key="' + escapeHtml(row.metric_key || '') + '"' +
                      ' data-clinic-key="' + escapeHtml(row.clinic_key || '') + '"' +
                      ' data-clinic-name="' + escapeHtml(row.clinic_name || '') + '"'
                    : '';

                return '<tr class="' + rowClass + '"' + dataAttributes + '>' +
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

        function renderDetailModal(headers, rows) {
            var headHtml = '<tr>' + (headers || []).map(function(header) {
                return '<th>' + escapeHtml(header) + '</th>';
            }).join('') + '</tr>';

            $('#report-detail-head').html(headHtml);

            if (!Array.isArray(rows) || !rows.length) {
                $('#report-detail-body').html('<tr><td colspan="' + Math.max((headers || []).length, 1) + '" class="text-center text-muted">Tidak ada data detail</td></tr>');
                return;
            }

            var bodyHtml = rows.map(function(row) {
                return '<tr>' + row.map(function(cell) {
                    return '<td>' + escapeHtml(cell) + '</td>';
                }).join('') + '</tr>';
            }).join('');

            $('#report-detail-body').html(bodyHtml);
        }

        function openDetailModal(mode, clinicKey, clinicName, metricKey) {
            $('#modal-report-detail-label').text((metricKey === 'piutang' ? 'Detail Piutang' : 'Detail Pendapatan') + ' - ' + clinicName);
            $('#report-detail-head').html('');
            $('#report-detail-body').html('<tr><td class="text-center text-muted">Memuat detail...</td></tr>');
            $('#modal-report-detail').modal('show');

            $.get('{{ route("finance.laporan-keuangan.detail-data") }}', {
                mode: mode,
                metric: metricKey,
                clinic_key: clinicKey,
                clinic_name: clinicName,
                report_date: reportDate,
                report_month: reportMonth
            }).done(function(res) {
                $('#modal-report-detail-label').text((res && res.title) ? res.title : 'Detail Laporan');
                renderDetailModal(res && res.headers ? res.headers : [], res && res.rows ? res.rows : []);
            }).fail(function(xhr) {
                var message = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Gagal memuat detail laporan';
                $('#report-detail-head').html('');
                $('#report-detail-body').html('<tr><td class="text-center text-danger">' + escapeHtml(message) + '</td></tr>');
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

        $('#table-laporan-harian-body').on('click', 'tr.report-summary-clickable', function() {
            openDetailModal('daily', $(this).data('clinic-key'), $(this).data('clinic-name'), $(this).data('metric-key'));
        });

        $('#table-laporan-bulanan-body').on('click', 'tr.report-summary-clickable', function() {
            openDetailModal('monthly', $(this).data('clinic-key'), $(this).data('clinic-name'), $(this).data('metric-key'));
        });

        $cutoffForm.on('submit', function(event) {
            event.preventDefault();

            hideAlert($('#report-cutoff-modal-feedback'));
            hideAlert($('#report-page-feedback'));

            $cutoffSubmit.prop('disabled', true).text('Menyimpan...');

            $.ajax({
                url: $cutoffForm.attr('action'),
                type: 'POST',
                data: $cutoffForm.serialize(),
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }).done(function(res) {
                applyReturnedCutoffs(res && res.cutoffs ? res.cutoffs : null);
                showAlert($('#report-page-feedback'), 'success', (res && res.message) ? res.message : 'Cut off jam laporan berhasil diperbarui.');
                $('#modal-report-cutoff').modal('hide');
                refreshSummaries();
            }).fail(function(xhr) {
                var message = 'Gagal menyimpan cut off jam laporan.';

                if (xhr.responseJSON) {
                    if (xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }

                    if (xhr.responseJSON.errors) {
                        var firstKey = Object.keys(xhr.responseJSON.errors)[0];
                        if (firstKey && xhr.responseJSON.errors[firstKey] && xhr.responseJSON.errors[firstKey][0]) {
                            message = xhr.responseJSON.errors[firstKey][0];
                        }
                    }
                }

                showAlert($('#report-cutoff-modal-feedback'), 'danger', message);
            }).always(function() {
                $cutoffSubmit.prop('disabled', false).text(cutoffSubmitDefaultLabel);
            });
        });

        $('#modal-report-cutoff').on('hidden.bs.modal', function() {
            hideAlert($('#report-cutoff-modal-feedback'));
        });

        loadDailySummary();
        loadMonthlySummary();
    });
</script>
@endsection