@extends('layouts.finance.app')
@section('title', 'Finance | Target Revenue Klinik')
@section('navbar')
    @include('layouts.finance.navbar')
@endsection

@section('content')
<style>
    .revenue-target-row-saved {
        animation: revenueTargetSavedFlash 2.2s ease;
    }

    .revenue-target-row-saving {
        background-color: #eff6ff;
    }

    .revenue-target-row-dirty {
        background-color: #fffbea;
    }

    .revenue-target-row-status {
        min-height: 18px;
    }

    @keyframes revenueTargetSavedFlash {
        0% { background-color: #dcfce7; }
        100% { background-color: transparent; }
    }
</style>
<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-flex flex-wrap align-items-center justify-content-between" style="gap: 1rem;">
                    <div>
                        <h4 class="mb-1">Target Revenue Klinik</h4>
                        <div class="text-muted">Atur target revenue bulanan per klinik dan pantau total target aktif.</div>
                    </div>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="javascript: void(0);">Finance</a></li>
                            <li class="breadcrumb-item active">Target Revenue Klinik</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        @if (session('status'))
            <div class="alert alert-success border-0 shadow-sm">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger border-0 shadow-sm">
                {{ $errors->first() }}
            </div>
        @endif

        <div id="revenue-target-ajax-feedback" class="alert border-0 shadow-sm" style="display: none;"></div>

        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body">
                        <div class="text-muted small text-uppercase font-weight-bold mb-2">Periode Target</div>
                        <div class="h4 mb-0" id="period-target-label">{{ $period->translatedFormat('F Y') }}</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body">
                        <div class="text-muted small text-uppercase font-weight-bold mb-2">Total Target</div>
                        <div class="h4 mb-0" id="total-target-label">Rp {{ number_format($totalTarget, 0, ',', '.') }}</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body">
                        <div class="text-muted small text-uppercase font-weight-bold mb-2">Revenue Aktual</div>
                        <div class="h4 mb-0" id="total-actual-revenue-label">Rp {{ number_format($totalActualRevenue, 0, ',', '.') }}</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body">
                        <div class="text-muted small text-uppercase font-weight-bold mb-2">Klinik Diatur</div>
                        <div class="h4 mb-0" id="configured-clinic-label">{{ number_format($configuredClinicCount, 0, ',', '.') }} / {{ number_format($clinics->count(), 0, ',', '.') }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('finance.revenue-targets.index') }}" class="form-inline" id="revenue-target-filter-form" style="gap: 0.75rem;">
                    <div>
                        <label for="period" class="small text-muted d-block mb-1">Pilih Bulan</label>
                        <input type="month" name="period" id="period" class="form-control" value="{{ $periodValue }}">
                    </div>
                    <div class="align-self-end text-muted small" id="period-loading-indicator" style="display: none;">Memuat data...</div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white d-flex flex-wrap align-items-center justify-content-between" style="gap: 0.75rem;">
                <div>
                    <h5 class="mb-1">Daftar Target per Klinik</h5>
                    <div class="text-muted small">Perubahan pada nominal atau catatan akan otomatis tersimpan saat field berubah.</div>
                </div>
                <button type="submit" form="revenue-target-form" class="btn btn-primary" id="save-revenue-target-button">
                    Simpan Semua
                </button>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('finance.revenue-targets.store') }}" id="revenue-target-form">
                    @csrf
                    <input type="hidden" name="period" value="{{ $periodValue }}" id="target-period-hidden-input">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 60px;">No</th>
                                    <th>Klinik</th>
                                    <th style="width: 220px;">Target Revenue</th>
                                    <th style="width: 220px;">Actual Revenue</th>
                                    <th style="width: 140px;">Pencapaian</th>
                                    <th>Catatan</th>
                                    <th style="width: 200px;">Status</th>
                                </tr>
                            </thead>
                            <tbody id="revenue-target-table-body">
                                @forelse ($clinics as $index => $clinic)
                                    <tr
                                        data-clinic-id="{{ $clinic->id }}"
                                        data-saved-amount="{{ old('targets.' . $clinic->id . '.amount', $clinic->target_amount_input) }}"
                                        data-saved-notes="{{ old('targets.' . $clinic->id . '.notes', $clinic->target_notes) }}"
                                    >
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            <div class="font-weight-bold">{{ $clinic->nama }}</div>
                                            <input type="hidden" name="clinic_ids[]" value="{{ $clinic->id }}">
                                        </td>
                                        <td>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">Rp</span>
                                                </div>
                                                <input
                                                    type="number"
                                                    min="0"
                                                    step="0.01"
                                                    class="form-control"
                                                    name="targets[{{ $clinic->id }}][amount]"
                                                    value="{{ old('targets.' . $clinic->id . '.amount', $clinic->target_amount_input) }}"
                                                    placeholder="0.00"
                                                >
                                            </div>
                                        </td>
                                        <td>
                                            <div class="font-weight-bold text-dark">Rp {{ number_format($clinic->actual_revenue ?? 0, 0, ',', '.') }}</div>
                                        </td>
                                        <td>
                                            <span class="font-weight-bold {{ ($clinic->achievement_percentage ?? 0) >= 100 ? 'text-success' : 'text-dark' }}">
                                                {{ $clinic->achievement_percentage !== null ? number_format($clinic->achievement_percentage, 1, ',', '.') . '%' : '-' }}
                                            </span>
                                        </td>
                                        <td>
                                            <textarea
                                                name="targets[{{ $clinic->id }}][notes]"
                                                class="form-control"
                                                rows="2"
                                                placeholder="Opsional"
                                            >{{ old('targets.' . $clinic->id . '.notes', $clinic->target_notes) }}</textarea>
                                        </td>
                                        <td>
                                            <div class="text-muted revenue-target-row-status">
                                                {{ $clinic->target_updated_at ? 'Tersimpan ' . $clinic->target_updated_at->translatedFormat('d M Y H:i') : 'Belum ada target' }}
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">Belum ada data klinik.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(function () {
        var $periodInput = $('#period');
        var $filterForm = $('#revenue-target-filter-form');
        var $loadingIndicator = $('#period-loading-indicator');
        var $tableBody = $('#revenue-target-table-body');
        var $periodHiddenInput = $('#target-period-hidden-input');
        var $saveButton = $('#save-revenue-target-button');
        var $feedback = $('#revenue-target-ajax-feedback');
        var $targetForm = $('#revenue-target-form');
        var endpoint = $filterForm.attr('action');
        var storeEndpoint = $targetForm.attr('action');

        function formatCurrency(amount) {
            return 'Rp ' + new Intl.NumberFormat('id-ID', {
                maximumFractionDigits: 0,
            }).format(Number(amount || 0));
        }

        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function buildClinicRow(clinic, index) {
            return [
                '<tr data-clinic-id="' + clinic.id + '" data-saved-amount="' + escapeHtml(clinic.target_amount_input) + '" data-saved-notes="' + escapeHtml(clinic.target_notes) + '">',
                    '<td>' + (index + 1) + '</td>',
                    '<td>',
                        '<div class="font-weight-bold">' + escapeHtml(clinic.nama) + '</div>',
                        '<input type="hidden" name="clinic_ids[]" value="' + clinic.id + '">',
                    '</td>',
                    '<td>',
                        '<div class="input-group">',
                            '<div class="input-group-prepend">',
                                '<span class="input-group-text">Rp</span>',
                            '</div>',
                            '<input type="number" min="0" step="0.01" class="form-control" name="targets[' + clinic.id + '][amount]" value="' + escapeHtml(clinic.target_amount_input) + '" placeholder="0.00">',
                        '</div>',
                    '</td>',
                    '<td><div class="font-weight-bold text-dark">' + formatCurrency(clinic.actual_revenue) + '</div></td>',
                    '<td><span class="font-weight-bold ' + ((clinic.achievement_percentage !== null && clinic.achievement_percentage >= 100) ? 'text-success' : 'text-dark') + '">' + formatAchievement(clinic.achievement_percentage) + '</span></td>',
                    '<td>',
                        '<textarea name="targets[' + clinic.id + '][notes]" class="form-control" rows="2" placeholder="Opsional">' + escapeHtml(clinic.target_notes) + '</textarea>',
                    '</td>',
                    '<td><div class="text-muted revenue-target-row-status">' + escapeHtml(clinic.target_updated_at_label === '-' ? 'Belum ada target' : 'Tersimpan ' + clinic.target_updated_at_label) + '</div></td>',
                '</tr>'
            ].join('');
        }

        function formatAchievement(value) {
            if (value === null || typeof value === 'undefined') {
                return '-';
            }

            return Number(value).toLocaleString('id-ID', {
                minimumFractionDigits: 1,
                maximumFractionDigits: 1,
            }) + '%';
        }

        function normalizeAmountValue(value) {
            var normalized = String(value ?? '').trim();

            if (normalized === '') {
                return '';
            }

            var parsed = Number(normalized);

            if (Number.isNaN(parsed)) {
                return normalized;
            }

            return parsed.toFixed(2);
        }

        function normalizeNotesValue(value) {
            return String(value ?? '').trim();
        }

        function renderClinics(clinics) {
            if (!Array.isArray(clinics) || clinics.length === 0) {
                $tableBody.html('<tr><td colspan="7" class="text-center text-muted">Belum ada data klinik.</td></tr>');
                return;
            }

            $tableBody.html(clinics.map(buildClinicRow).join(''));
        }

        function showFeedback(type, message) {
            $feedback
                .removeClass('alert-success alert-danger alert-warning')
                .addClass('alert-' + type)
                .text(message)
                .show();
        }

        function hideFeedback() {
            $feedback.hide().text('').removeClass('alert-success alert-danger alert-warning');
        }

        function applyPayload(response) {
            $('#period-target-label').text(response.period_label || '-');
            $('#total-target-label').text(formatCurrency(response.total_target));
            $('#total-actual-revenue-label').text(formatCurrency(response.total_actual_revenue));
            $('#configured-clinic-label').text((response.configured_clinic_count || 0) + ' / ' + (response.clinic_count || 0));
            $periodHiddenInput.val(response.period_value || $periodInput.val());
            $periodInput.val(response.period_value || $periodInput.val());

            if (window.history && typeof window.history.replaceState === 'function') {
                window.history.replaceState({}, '', endpoint + '?period=' + encodeURIComponent(response.period_value || $periodInput.val()));
            }
        }

        function clinicMapFromResponse(response) {
            var map = {};

            (response.clinics || []).forEach(function (clinic) {
                map[String(clinic.id)] = clinic;
            });

            return map;
        }

        function replaceClinicRow(clinic, index) {
            var $existingRow = $tableBody.find('tr[data-clinic-id="' + clinic.id + '"]');
            var rowHtml = buildClinicRow(clinic, index);

            if ($existingRow.length) {
                $existingRow.replaceWith(rowHtml);
                return;
            }

            $tableBody.append(rowHtml);
        }

        function updateChangedRows(response, changedClinicIds) {
            var clinicsById = clinicMapFromResponse(response);
            var indexById = {};

            (response.clinics || []).forEach(function (clinic, index) {
                indexById[String(clinic.id)] = index;
            });

            (changedClinicIds || []).forEach(function (clinicId) {
                var key = String(clinicId);
                var clinic = clinicsById[key];

                if (!clinic) {
                    return;
                }

                replaceClinicRow(clinic, indexById[key] || 0);
                flashSavedRow(key);
            });
        }

        function flashSavedRow(clinicId) {
            var $row = $tableBody.find('tr[data-clinic-id="' + clinicId + '"]');

            if (!$row.length) {
                return;
            }

            $row.removeClass('revenue-target-row-saving revenue-target-row-dirty').addClass('revenue-target-row-saved');
            window.setTimeout(function () {
                $row.removeClass('revenue-target-row-saved');
            }, 2200);
        }

        function markRowDirty($row) {
            $row.addClass('revenue-target-row-dirty');
            $row.find('.revenue-target-row-status').text('Perubahan belum disimpan');
        }

        function clearRowDirtyIfSynced($row) {
            var amount = normalizeAmountValue($row.find('input[type="number"]').val());
            var notes = normalizeNotesValue($row.find('textarea').val());

            if (amount === ($row.attr('data-saved-amount') || '') && notes === ($row.attr('data-saved-notes') || '')) {
                $row.removeClass('revenue-target-row-dirty');
            }
        }

        function rowPayload($row) {
            var clinicId = $row.attr('data-clinic-id');

            return {
                period: $periodHiddenInput.val(),
                clinic_ids: [clinicId],
                targets: {
                    [clinicId]: {
                        amount: $row.find('input[type="number"]').val(),
                        notes: $row.find('textarea').val()
                    }
                },
                _token: $targetForm.find('input[name="_token"]').val()
            };
        }

        function saveRow($row) {
            var clinicId = $row.attr('data-clinic-id');
            var currentAmount = normalizeAmountValue($row.find('input[type="number"]').val());
            var currentNotes = normalizeNotesValue($row.find('textarea').val());

            if (currentAmount === ($row.attr('data-saved-amount') || '') && currentNotes === ($row.attr('data-saved-notes') || '')) {
                clearRowDirtyIfSynced($row);
                return;
            }

            hideFeedback();
            $row.addClass('revenue-target-row-saving');
            $row.find('.revenue-target-row-status').text('Menyimpan...');

            $.ajax({
                url: storeEndpoint,
                method: 'POST',
                data: rowPayload($row),
                headers: {
                    Accept: 'application/json'
                },
                success: function (response) {
                    applyPayload(response);
                    updateChangedRows(response, response.saved_clinic_ids || [clinicId]);
                    showFeedback('success', response.message || 'Target revenue berhasil disimpan.');
                },
                error: function (xhr) {
                    $row.removeClass('revenue-target-row-saving');
                    $row.find('.revenue-target-row-status').text('Gagal menyimpan');

                    if (xhr.status === 422 && xhr.responseJSON) {
                        var errors = xhr.responseJSON.errors || {};
                        var firstKey = Object.keys(errors)[0];
                        var firstMessage = firstKey && Array.isArray(errors[firstKey]) ? errors[firstKey][0] : 'Data tidak valid.';
                        showFeedback('danger', firstMessage);
                        return;
                    }

                    showFeedback('danger', 'Terjadi kesalahan saat menyimpan target revenue.');
                }
            });
        }

        function loadPeriod(periodValue) {
            hideFeedback();
            $loadingIndicator.show();
            $periodInput.prop('disabled', true);
            $saveButton.prop('disabled', true);

            $.ajax({
                url: endpoint,
                method: 'GET',
                data: { period: periodValue },
                headers: {
                    Accept: 'application/json'
                },
                success: function (response) {
                    applyPayload(response);
                    renderClinics(response.clinics || []);
                },
                error: function () {
                    window.location = endpoint + '?period=' + encodeURIComponent(periodValue);
                },
                complete: function () {
                    $loadingIndicator.hide();
                    $periodInput.prop('disabled', false);
                    $saveButton.prop('disabled', false);
                }
            });
        }

        $periodInput.on('change', function () {
            var periodValue = $(this).val();

            if (!periodValue) {
                return;
            }

            loadPeriod(periodValue);
        });

        $filterForm.on('submit', function (event) {
            event.preventDefault();

            if ($periodInput.val()) {
                loadPeriod($periodInput.val());
            }
        });

        $tableBody.on('input', 'input[type="number"], textarea', function () {
            markRowDirty($(this).closest('tr'));
        });

        $tableBody.on('change', 'input[type="number"], textarea', function () {
            saveRow($(this).closest('tr'));
        });

        $targetForm.on('submit', function (event) {
            event.preventDefault();

            hideFeedback();
            $saveButton.prop('disabled', true).text('Menyimpan...');
            $periodInput.prop('disabled', true);

            $.ajax({
                url: storeEndpoint,
                method: 'POST',
                data: $targetForm.serialize(),
                headers: {
                    Accept: 'application/json'
                },
                success: function (response) {
                    applyPayload(response);
                    updateChangedRows(response, response.saved_clinic_ids || []);
                    showFeedback('success', response.message || 'Target revenue berhasil disimpan.');
                },
                error: function (xhr) {
                    if (xhr.status === 422 && xhr.responseJSON) {
                        var errors = xhr.responseJSON.errors || {};
                        var firstKey = Object.keys(errors)[0];
                        var firstMessage = firstKey && Array.isArray(errors[firstKey]) ? errors[firstKey][0] : 'Data tidak valid.';
                        showFeedback('danger', firstMessage);
                        return;
                    }

                    showFeedback('danger', 'Terjadi kesalahan saat menyimpan target revenue.');
                },
                complete: function () {
                    $saveButton.prop('disabled', false).text('Simpan Semua');
                    $periodInput.prop('disabled', false);
                }
            });
        });
    });
</script>
@endsection
