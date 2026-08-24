@extends('layouts.erm.app')
@section('title', 'ERM | Add Permintaan Pembelian')
@section('navbar')
    @include('layouts.erm.navbar')
@endsection  

@section('content')
<div class="container-fluid">
        <!-- Page-Title -->
    <!-- Title and Button Row -->
    <div class="row mt-3 align-items-center">
        <div class="col-md-6">
            <h2 class="mb-0">Buat Permintaan Pembelian</h2>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="row">
                    <div class="col">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:void(0);">ERM</a></li>
                            <li class="breadcrumb-item">Farmasi</li>
                            <li class="breadcrumb-item ">Permintaan Pembelian</li>
                            <li class="breadcrumb-item active">Create</li>
                        </ol>
                    </div><!--end col-->
                </div><!--end row-->                                                              
            </div><!--end page-title-box-->
        </div><!--end col-->
    </div><!--end row-->
    <!-- end page title end breadcrumb -->
    <form id="permintaanForm">
        @csrf
        @if(isset($permintaan))
            <input type="hidden" name="id" value="{{ $permintaan->id }}">
        @endif
        <div class="mb-3">
            <label>Tanggal Permintaan</label>
            <input type="date" name="request_date" id="request_date" class="form-control" required value="{{ isset($permintaan) ? $permintaan->request_date : '' }}">
        </div>
        <hr>
        <h5>Item Permintaan</h5>
        <table class="table table-bordered" id="items-table">
            <colgroup>
                    <col style="width: 16%;">
                    <col style="width: 18%;">
                    <col style="width: 18%;">
                    <col style="width: 7%;">
                    <col style="width: 7%;">
                    <col style="width: 8%;">
                    <col style="width: 8%;">
                    <col style="width: 6%;">
                    <col style="width: 4%;">
                    <col style="width: 4%;">
                </colgroup>
            <thead>
                <tr>
                    <th>Obat</th>
                    <th>Pemasok</th>
                    <th>Principal</th>
                    <th>Jumlah Box</th>
                    <th>Qty Total</th>
                    <th>Harga</th>
                    <th>Qty/Box</th>
                    <th>Diskon</th>
                    <th>Tipe Diskon</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    <button type="button" class="btn btn-success btn-sm mb-3" id="add-row">Tambah Item</button>
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-light d-flex flex-column flex-md-row align-items-md-center justify-content-between">
                <h5 class="mb-2 mb-md-0">Nilai Permintaan Pembelian</h5>
                <div class="d-flex align-items-center" style="gap: 8px;">
                    <label for="preview_period_months" class="mb-0">Rata-rata Keluar</label>
                    <select id="preview_period_months" class="form-control form-control-sm" style="width: 140px;">
                        <option value="1">1 Bulan</option>
                        <option value="3" selected>3 Bulan</option>
                        <option value="6">6 Bulan</option>
                        <option value="12">1 Tahun</option>
                    </select>
                </div>
            </div>
            <div class="card-body px-0 py-0">
                <div class="px-3 pt-2">
                    <small class="text-muted" id="previewPeriodInfo">Periode -</small>
                </div>
                <div class="table-responsive mb-0">
                    <table class="table table-bordered table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Obat Diminta</th>
                                <th class="text-right">Total Stok Saat Ini</th>
                                <th class="text-right">Rata-rata Keluar / Bulan</th>
                                <th class="text-right">Sudah Order Bulan Ini</th>
                                <th class="text-right">Qty</th>
                                <th class="text-right">Harga</th>
                                <th class="text-right">Diskon</th>
                                <th class="text-right">Setelah Diskon</th>
                                <th class="text-right">PPN</th>
                                <th class="text-right">Total Harga</th>
                            </tr>
                        </thead>
                        <tbody id="nilaiPermintaanPreviewBody">
                            <tr>
                                <td colspan="10" class="text-center text-muted">Pilih item untuk melihat estimasi nilai pembelian.</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="7" class="text-right font-weight-bold">Total Nilai Permintaan Pembelian</th>
                                <th class="text-right font-weight-bold" id="nilaiPermintaanSubtotalDiskon">Rp 0</th>
                                <th class="text-right font-weight-bold" id="nilaiPermintaanPpn">Rp 0</th>
                                <th class="text-right font-weight-bold" id="nilaiPermintaanGrandTotal">Rp 0</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
        <br>
        <div class="text-right">
            <button type="submit" class="btn btn-primary">Simpan Permintaan</button>
            <a href="{{ route('erm.permintaan.index') }}" class="btn btn-secondary">Kembali</a>
        </div>
    </form>
    <div id="formAlert" style="display:none;" class="alert mt-3"></div>

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
                            <div class="text-muted small mb-2" id="similarObatSharedZatAktif">-</div>
                            <div class="text-muted small" id="similarObatSelectedSummary">-</div>
                        </div>
                        <div id="similarObatMessage" class="alert alert-light border d-none mb-3"></div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>Nama Obat</th>
                                        <th>Total Stok</th>
                                        <th>Isi per Box</th>
                                        <th>Harga</th>
                                        <th>Diskon</th>
                                        <th>Setelah Diskon</th>
                                        <th>Zat Aktif Sama</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="similarObatTableBody">
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">Pilih obat untuk melihat alternatif serupa.</td>
                                    </tr>
                                </tbody>
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
@endsection

@php
    $oldItems = [];
    if (isset($permintaan)) {
        $oldItems = $permintaan->items->map(function($item) {
            return [
                'obat_id' => $item->obat_id,
                'obat_nama' => optional($item->obat)->nama,
                'pemasok_id' => $item->pemasok_id,
                'pemasok_nama' => optional($item->pemasok)->nama,
                'principal_id' => $item->principal_id,
                'principal_nama' => optional($item->principal)->nama,
                'jumlah_box' => $item->jumlah_box,
                'qty_total' => $item->qty_total
            ];
        })->toArray();
    }
@endphp

@section('scripts')
{{-- <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script> --}}
<script>
let rowIdx = 0;
let oldItems = @json($oldItems);
const PPN_RATE = 11;
let forecastMetricsByObatId = {};
let forecastMetricRequests = {};
let activeSimilarObatRow = null;

function formatRupiah(value) {
    return 'Rp ' + Number(value || 0).toLocaleString('id-ID', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2
    });
}

function parseNumber(value) {
    if (value === null || value === undefined || value === '') {
        return 0;
    }

    const normalized = String(value).replace(/[^0-9,.-]/g, '').replace(',', '.');
    const parsed = parseFloat(normalized);

    return Number.isFinite(parsed) ? parsed : 0;
}

function isPercentDiscount(diskonType) {
    return ['persen', 'percent', '%', 'pct', 'pc', 'per'].includes(String(diskonType || '').trim().toLowerCase());
}

function getDiscountLabel(diskon, diskonType) {
    if (!diskon) {
        return '0';
    }

    return isPercentDiscount(diskonType) ? diskon + '%' : formatRupiah(diskon);
}

function getSelectedText($select) {
    return $select.find('option:selected').text().trim() || '-';
}

function escapeHtml(value) {
    return $('<div>').text(value || '').html();
}

function setSimilarObatLoading(isLoading) {
    $('#similarObatLoadingState').toggleClass('d-none', !isLoading);
    $('#similarObatContent').toggleClass('d-none', isLoading);
}

function updateSimilarObatButtonState($row) {
    const hasObat = Boolean($row.find('.obat-select').val());
    $row.find('.btn-similar-obat').prop('disabled', !hasObat);
}

function formatSimilarObatNumber(value) {
    const parsed = parseNumber(value);
    return Number(parsed).toLocaleString('id-ID', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2
    });
}

function renderSimilarObatBadges(items) {
    if (!items || !items.length) {
        return '-';
    }

    return items.map(function(item) {
        return '<span class="badge badge-info mr-1 mb-1">' + escapeHtml(item) + '</span>';
    }).join('');
}

function resetSimilarObatModal() {
    $('#similarObatName').text('-');
    $('#similarObatSharedZatAktif').text('-');
    $('#similarObatSelectedSummary').text('-');
    $('#similarObatMessage').addClass('d-none').removeClass('alert-warning alert-info').text('');
    $('#similarObatTableBody').html('<tr><td colspan="7" class="text-center text-muted">Pilih obat untuk melihat alternatif serupa.</td></tr>');
}

function openSimilarObatModal($row) {
    const obatId = $row.find('.obat-select').val();

    if (!obatId) {
        Swal.fire({
            icon: 'warning',
            title: 'Pilih Obat Dulu',
            text: 'Silakan pilih obat pada baris ini sebelum melihat obat serupa.'
        });
        return;
    }

    activeSimilarObatRow = $row;
    resetSimilarObatModal();
    setSimilarObatLoading(true);
    $('#similarObatModal').modal('show');

    $.ajax({
        url: '/erm/obat/' + obatId + '/similar',
        type: 'GET',
        success: function(res) {
            const sharedZatAktif = (res.shared_zat_aktif || []).join(', ');
            $('#similarObatName').text(res.obat_nama || '-');
            $('#similarObatSharedZatAktif').text(sharedZatAktif ? 'Kandungan sama: ' + sharedZatAktif : 'Obat ini belum memiliki kandungan yang terhubung.');

            const summary = [
                'Stok: ' + formatSimilarObatNumber(res.total_stock),
                'Isi/Box: ' + (res.isi_per_box !== null && res.isi_per_box !== undefined ? formatSimilarObatNumber(res.isi_per_box) : '-'),
                'Harga: ' + (res.harga !== null && res.harga !== undefined ? formatRupiah(res.harga) : '-'),
                'Diskon: ' + getDiscountLabel(res.diskon, res.diskon_type),
                'Setelah Diskon: ' + (res.setelah_diskon !== null && res.setelah_diskon !== undefined ? formatRupiah(res.setelah_diskon) : '-')
            ];
            $('#similarObatSelectedSummary').text(summary.join(' | '));

            if (res.message) {
                $('#similarObatMessage')
                    .removeClass('d-none alert-info')
                    .addClass('alert-warning')
                    .text(res.message);
            }

            if (!res.rows || !res.rows.length) {
                $('#similarObatTableBody').html('<tr><td colspan="8" class="text-center text-muted">Tidak ada obat serupa yang ditemukan.</td></tr>');
                return;
            }

            const rowsHtml = res.rows.map(function(item) {
                return '<tr>' +
                    '<td>' + escapeHtml(item.obat_nama || '-') + '<div class="mt-1">' + (item.is_generik ? '<span class="badge badge-success">Generik</span>' : '<span class="badge badge-primary">Paten</span>') + '</div></td>' +
                    '<td class="text-right">' + formatSimilarObatNumber(item.total_stock) + '</td>' +
                    '<td class="text-right">' + (item.isi_per_box !== null && item.isi_per_box !== undefined ? formatSimilarObatNumber(item.isi_per_box) : '-') + '</td>' +
                    '<td class="text-right">' + (item.harga !== null && item.harga !== undefined ? formatRupiah(item.harga) : '-') + '</td>' +
                    '<td class="text-right">' + getDiscountLabel(item.diskon, item.diskon_type) + '</td>' +
                    '<td class="text-right">' + (item.setelah_diskon !== null && item.setelah_diskon !== undefined ? formatRupiah(item.setelah_diskon) : '-') + '</td>' +
                    '<td>' + renderSimilarObatBadges(item.matched_zat_aktif || []) + '</td>' +
                    '<td class="text-center"><button type="button" class="btn btn-sm btn-primary btn-replace-similar-obat" data-obat-id="' + escapeHtml(item.obat_id) + '" data-obat-name="' + escapeHtml(item.obat_nama || '') + '">Pilih</button></td>' +
                    '</tr>';
            }).join('');

            $('#similarObatTableBody').html(rowsHtml);
        },
        error: function() {
            $('#similarObatMessage')
                .removeClass('d-none alert-warning')
                .addClass('alert-info')
                .text('Gagal memuat obat serupa. Silakan coba lagi.');
            $('#similarObatTableBody').html('<tr><td colspan="7" class="text-center text-muted">Data obat serupa tidak tersedia.</td></tr>');
        },
        complete: function() {
            setSimilarObatLoading(false);
        }
    });
}

function formatPeriodInfo(startDate, endDate) {
    if (!startDate || !endDate) {
        return '';
    }

    return 'Periode ' + startDate + ' s/d ' + endDate;
}

function getForecastCacheKey(obatId) {
    return String(obatId) + '|' + String($('#preview_period_months').val() || '3');
}

function fetchForecastMetricForObat(obatId) {
    if (!obatId) {
        return;
    }

    const cacheKey = getForecastCacheKey(obatId);

    if (forecastMetricRequests[cacheKey]) {
        return;
    }

    forecastMetricRequests[cacheKey] = true;

    $.ajax({
        url: '/erm/obat/' + obatId + '/forecast',
        type: 'GET',
        data: {
            period_months: $('#preview_period_months').val()
        },
        success: function(res) {
            forecastMetricsByObatId[String(obatId)] = {
                obat_id: obatId,
                total_stock: res.total_stock,
                average_monthly_keluar: res.average_monthly_keluar,
                period_start: res.period_start,
                period_end: res.period_end,
            };

            if (res.period_start && res.period_end) {
                $('#previewPeriodInfo').text(formatPeriodInfo(res.period_start, res.period_end));
            }

            updatePurchasePreview();
        },
        complete: function() {
            delete forecastMetricRequests[cacheKey];
        }
    });
}

function fetchForecastMetrics() {
    const obatIds = $('#items-table tbody .obat-select').map(function() {
        return $(this).val();
    }).get().filter(Boolean);

    forecastMetricRequests = {};

    if (!obatIds.length) {
        forecastMetricsByObatId = {};
        $('#previewPeriodInfo').text('Periode -');
        updatePurchasePreview();
        return;
    }

    $.ajax({
        url: '{{ route('erm.permintaan.forecast-preview') }}',
        type: 'GET',
        data: {
            period_months: $('#preview_period_months').val(),
            obat_ids: Array.from(new Set(obatIds))
        },
        success: function(res) {
            forecastMetricsByObatId = {};
            (res.rows || []).forEach(function(row) {
                forecastMetricsByObatId[String(row.obat_id)] = row;
            });
            $('#previewPeriodInfo').text(formatPeriodInfo(res.period_start, res.period_end) || 'Periode -');

            Array.from(new Set(obatIds)).forEach(function(obatId) {
                if (!forecastMetricsByObatId[String(obatId)]) {
                    fetchForecastMetricForObat(obatId);
                }
            });

            updatePurchasePreview();
        },
        error: function() {
            forecastMetricsByObatId = {};
            Array.from(new Set(obatIds)).forEach(function(obatId) {
                fetchForecastMetricForObat(obatId);
            });
            updatePurchasePreview();
        }
    });
}

function updatePurchasePreview() {
    let rows = '';
    let totalSetelahDiskon = 0;
    let totalPpn = 0;
    let grandTotal = 0;

    $('#items-table tbody tr').each(function() {
        const $row = $(this);
        const obatId = $row.find('.obat-select').val();
        const pemasokId = $row.find('.pemasok-select').val();

        if (!obatId || !pemasokId) {
            return;
        }

        const namaObat = getSelectedText($row.find('.obat-select'));
    const forecastMetrics = forecastMetricsByObatId[String(obatId)] || null;
        const qty = parseNumber($row.find('.qty-total').val());
        const jumlahBox = parseNumber($row.find('.jumlah-box').val());
        const harga = parseNumber($row.find('.harga-master').val());
        const diskon = parseNumber($row.find('.diskon-master').val());
        const diskonType = $row.find('.diskontype-master').val();
        const subtotal = harga * qty;
        const diskonValue = isPercentDiscount(diskonType)
            ? (subtotal * diskon / 100)
            : diskon;
        const setelahDiskon = Math.max(subtotal - diskonValue, 0);
        const ppn = setelahDiskon * PPN_RATE / 100;
        const totalHarga = setelahDiskon + ppn;

        totalSetelahDiskon += setelahDiskon;
        totalPpn += ppn;
        grandTotal += totalHarga;

        rows += '<tr>' +
            '<td>' + (namaObat || '-') + '</td>' +
            '<td class="text-right">' + (forecastMetrics ? formatForecastMetric(forecastMetrics.total_stock) : '-') + '</td>' +
            '<td class="text-right">' + (forecastMetrics ? formatForecastMetric(forecastMetrics.average_monthly_keluar) : '-') + '</td>' +
            '<td class="text-right">' + (forecastMetrics ? Number(forecastMetrics.ordered_this_month_qty || 0).toLocaleString('id-ID') + ' (' + Number(forecastMetrics.ordered_this_month_box || 0).toLocaleString('id-ID') + ' Box)' : '-') + '</td>' +
            '<td class="text-right">' + Number(qty || 0).toLocaleString('id-ID') + ' (' + Number(jumlahBox || 0).toLocaleString('id-ID') + ' Box)</td>' +
            '<td class="text-right">' + formatRupiah(harga) + '</td>' +
            '<td class="text-right">' + getDiscountLabel(diskon, diskonType) + '</td>' +
            '<td class="text-right">' + formatRupiah(setelahDiskon) + '</td>' +
            '<td class="text-right">' + formatRupiah(ppn) + '<br><small class="text-muted">' + PPN_RATE + '%</small></td>' +
            '<td class="text-right font-weight-bold">' + formatRupiah(totalHarga) + '</td>' +
            '</tr>';
    });

    if (!rows) {
        rows = '<tr><td colspan="10" class="text-center text-muted">Pilih item untuk melihat estimasi nilai pembelian.</td></tr>';
    }

    $('#nilaiPermintaanPreviewBody').html(rows);
    $('#nilaiPermintaanSubtotalDiskon').text(formatRupiah(totalSetelahDiskon));
    $('#nilaiPermintaanPpn').text(formatRupiah(totalPpn));
    $('#nilaiPermintaanGrandTotal').text(formatRupiah(grandTotal));
}

function formatForecastMetric(value) {
    return Number(value || 0).toLocaleString('id-ID', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2
    });
}

function initAjaxSelect2($el, url, selected = null) {
    $el.select2({
        placeholder: 'Pilih',
        minimumInputLength: 2,
        ajax: {
            url: url,
            dataType: 'json',
            delay: 250,
            data: function(params) { return { q: params.term }; },
            processResults: function(data) { return { results: data }; },
            cache: true
        },
        width: '100%',
        allowClear: true
    });
    $el.next('.select2-container').css('width', '100%');
    setTimeout(function() {
        $el.next('.select2-container').css('width', '100%');
    }, 0);
    if (selected) {
        let option = new Option(selected.text, selected.id, true, true);
        $el.append(option).trigger('change');
    }
}

function initStaticSelect2($el) {
    $el.select2({
        placeholder: 'Pilih',
        width: '100%',
        allowClear: true
    });
    $el.next('.select2-container').css('width', '100%');
}

function setStaticSelectOptions($el, options, selectedId = null, disabled = false) {
    if ($el.hasClass('select2-hidden-accessible')) {
        $el.select2('destroy');
    }

    $el.empty().append(new Option('', '', false, false));

    (options || []).forEach(function(option) {
        const isSelected = selectedId !== null && selectedId !== undefined && String(option.id) === String(selectedId);
        $el.append(new Option(option.text, option.id, isSelected, isSelected));
    });

    $el.prop('disabled', disabled);
    initStaticSelect2($el);

    if (selectedId !== null && selectedId !== undefined) {
        $el.val(String(selectedId)).trigger('change');
    } else {
        $el.val('').trigger('change');
    }
}

function updatePrincipalOptionsForRow($row, selectedPrincipalId = null) {
    const $principalSelect = $row.find('.principal-select');
    const pemasokId = String($row.find('.pemasok-select').val() || '');
    const principalOptionsByPemasok = $row.data('principalOptionsByPemasok') || {};
    const allPrincipalOptions = $row.data('allPrincipalOptions') || [];
    const options = principalOptionsByPemasok[pemasokId] || allPrincipalOptions;

    setStaticSelectOptions($principalSelect, options, selectedPrincipalId, !options.length);
}

function loadObatOptionsForRow($row, selectedValues = {}) {
    const obatId = $row.find('.obat-select').val();
    const $pemasokSelect = $row.find('.pemasok-select');
    const $principalSelect = $row.find('.principal-select');

    if (!obatId) {
        $row.removeData('principalOptionsByPemasok').removeData('allPrincipalOptions');
        setStaticSelectOptions($pemasokSelect, [], null, true);
        setStaticSelectOptions($principalSelect, [], null, true);
        applyMasterFakturToRow($row, { found: false });
        return;
    }

    $.ajax({
        url: '{{ route('erm.permintaan.obat-options') }}',
        type: 'GET',
        data: {
            obat_id: obatId
        },
        success: function(res) {
            const pemasoks = res.pemasoks || [];
            const allPrincipals = res.principals || [];
            const principalsByPemasok = res.principals_by_pemasok || {};
            const currentPemasokId = selectedValues.pemasok_id || $pemasokSelect.val() || '';
            const hasSelectedPemasok = pemasoks.some(function(option) {
                return String(option.id) === String(currentPemasokId);
            });
            const pemasokIdToUse = hasSelectedPemasok ? currentPemasokId : '';

            $row.data('principalOptionsByPemasok', principalsByPemasok);
            $row.data('allPrincipalOptions', allPrincipals);

            setStaticSelectOptions($pemasokSelect, pemasoks, pemasokIdToUse, !pemasoks.length);
            updatePrincipalOptionsForRow($row, selectedValues.principal_id || $principalSelect.val() || '');

            const activePemasokId = $pemasokSelect.val();
            if (obatId && activePemasokId) {
                $.get('/erm/permintaan/master-faktur', { obat_id: obatId, pemasok_id: activePemasokId }, function(data) {
                    applyMasterFakturToRow($row, data);
                });
            } else {
                applyMasterFakturToRow($row, { found: false });
            }
        },
        error: function() {
            $row.removeData('principalOptionsByPemasok').removeData('allPrincipalOptions');
            setStaticSelectOptions($pemasokSelect, [], null, true);
            setStaticSelectOptions($principalSelect, [], null, true);
            applyMasterFakturToRow($row, { found: false });
        }
    });
}

function addPermintaanRow(item = null) {
    rowIdx++;
    let row = `<tr>
        <td>
            <div class="d-flex align-items-start" style="gap: 8px; min-width: 320px; max-width: 100%;">
                <select name="items[${rowIdx}][obat_id]" class="form-control obat-select" required style="width:100%"></select>
                <button type="button" class="btn btn-outline-info btn-sm btn-similar-obat flex-shrink-0" disabled title="Obat Serupa" aria-label="Obat Serupa">
                    <i class="fa fa-random"></i>
                </button>
            </div>
        </td>
        <td><select name="items[${rowIdx}][pemasok_id]" class="form-control pemasok-select" required style="min-width:400px; width:100%"></select></td>
        <td><select name="items[${rowIdx}][principal_id]" class="form-control principal-select" style="min-width:300px; width:100%"></select></td>
    <td><input type="number" name="items[${rowIdx}][jumlah_box]" class="form-control jumlah-box" min="0" required value="${item ? item.jumlah_box : ''}"></td>
        <td><input type="number" name="items[${rowIdx}][qty_total]" class="form-control qty-total" min="1" required value="${item ? item.qty_total : ''}"></td>
        <td><input type="text" class="form-control harga-master" readonly></td>
        <td><input type="text" class="form-control qtybox-master" readonly></td>
        <td><input type="text" class="form-control diskon-master" readonly></td>
        <td><input type="text" class="form-control diskontype-master" readonly></td>
        <td><button type="button" class="btn btn-danger btn-sm remove-row">Hapus</button></td>
    </tr>`;
    $('#items-table tbody').append(row);
    let $row = $('#items-table tbody tr:last');
    // Init select2 and set value if editing
    initAjaxSelect2($row.find('.obat-select'), '/erm/ajax/obat', item ? {id: item.obat_id, text: item.obat_nama} : null);
    setStaticSelectOptions($row.find('.pemasok-select'), [], null, true);
    setStaticSelectOptions($row.find('.principal-select'), [], null, true);

    if (item && item.obat_id) {
        loadObatOptionsForRow($row, {
            pemasok_id: item.pemasok_id,
            principal_id: item.principal_id
        });
    }

    updateSimilarObatButtonState($row);
    updatePurchasePreview();
    fetchForecastMetrics();
}

$(document).ready(function() {
    // Set default date to today if not editing
    if (!$("input[name='id']").length) {
        let today = new Date();
        let yyyy = today.getFullYear();
        let mm = String(today.getMonth() + 1).padStart(2, '0');
        let dd = String(today.getDate()).padStart(2, '0');
        let formatted = yyyy + '-' + mm + '-' + dd;
        $('#request_date').val(formatted);
    }
    if (oldItems.length > 0) {
        oldItems.forEach(function(item) { addPermintaanRow(item); });
    } else {
        addPermintaanRow();
    }
    $('#add-row').on('click', function() { addPermintaanRow(); });
    $('#items-table').on('click', '.remove-row', function() {
        $(this).closest('tr').remove();
        fetchForecastMetrics();
        updatePurchasePreview();
    });

    $('#preview_period_months').on('change', function() {
        fetchForecastMetrics();
    });

    // Autofill master faktur fields when obat or pemasok change
    $('#items-table').on('change', '.obat-select', function() {
        let $row = $(this).closest('tr');
        updateSimilarObatButtonState($row);
        loadObatOptionsForRow($row);
        fetchForecastMetrics();
        updatePurchasePreview();
    });

    $('#items-table').on('change', '.pemasok-select', function() {
        let $row = $(this).closest('tr');
        let obatId = $row.find('.obat-select').val();
        let pemasokId = $row.find('.pemasok-select').val();

        updatePrincipalOptionsForRow($row);

        if (obatId && pemasokId) {
            $.get('/erm/permintaan/master-faktur', { obat_id: obatId, pemasok_id: pemasokId }, function(data) {
                applyMasterFakturToRow($row, data);
            });
        } else {
            applyMasterFakturToRow($row, { found: false });
        }

        updatePurchasePreview();
    });

    // helper: apply master faktur data to row
    function applyMasterFakturToRow($row, data) {
        if (!data || !data.found) {
            $row.find('.harga-master').val('');
            $row.find('.qtybox-master').val('');
            $row.find('.diskon-master').val('');
            $row.find('.diskontype-master').val('');
            updatePurchasePreview();
            return;
        }
        $row.find('.harga-master').val(data.harga ?? '');
        $row.find('.qtybox-master').val(data.qty_per_box ?? '');
        $row.find('.diskon-master').val(data.diskon ?? '');
        $row.find('.diskontype-master').val(data.diskon_type ?? '');
        if (data.principal_id) {
            let $pselect = $row.find('.principal-select');
            if ($pselect.find('option[value="'+data.principal_id+'"]').length === 0) {
                let option = new Option(data.principal_nama || data.principal_id, data.principal_id, true, true);
                $pselect.append(option).trigger('change');
            } else {
                $pselect.val(data.principal_id).trigger('change');
            }
        }
        // if jumlah_box present, autofill qty_total using qtybox-master
        let jumlahBox = parseInt($row.find('.jumlah-box').val());
        let qtyBox = parseInt($row.find('.qtybox-master').val());
        if (qtyBox > 0 && jumlahBox > 0) {
            $row.find('.qty-total').val(qtyBox * jumlahBox);
        }

        updatePurchasePreview();
    }

    // Qty total autofill
    $('#items-table').on('input', '.jumlah-box', function() {
        let $row = $(this).closest('tr');
        let qtyBox = parseInt($row.find('.qtybox-master').val());
        let jumlahBox = parseInt($(this).val());
        let $qtyTotal = $row.find('.qty-total');
        if (qtyBox > 0 && jumlahBox > 0) {
            $qtyTotal.val(qtyBox * jumlahBox);
        } else {
            $qtyTotal.val('');
        }

        updatePurchasePreview();
    });

    $('#items-table').on('input', '.qty-total', function() {
        updatePurchasePreview();
    });

    $('#items-table').on('click', '.btn-similar-obat', function() {
        openSimilarObatModal($(this).closest('tr'));
    });

    $('#similarObatTableBody').on('click', '.btn-replace-similar-obat', function() {
        if (!activeSimilarObatRow) {
            return;
        }

        const obatId = $(this).data('obat-id');
        const obatName = $(this).data('obat-name');
        const $obatSelect = activeSimilarObatRow.find('.obat-select');

        if ($obatSelect.find('option[value="' + obatId + '"]').length === 0) {
            $obatSelect.append(new Option(obatName, obatId, true, true));
        }

        $obatSelect.val(obatId).trigger('change');
        $('#similarObatModal').modal('hide');

        Swal.fire({
            icon: 'success',
            title: 'Obat Diganti',
            text: 'Obat pada baris ini berhasil diganti ke obat serupa.',
            timer: 1200,
            showConfirmButton: false
        });
    });

    $('#similarObatModal').on('hidden.bs.modal', function() {
        activeSimilarObatRow = null;
        resetSimilarObatModal();
    });

    // AJAX form submit
    $('#permintaanForm').on('submit', function(e) {
        e.preventDefault();
        let form = $(this);
        let data = form.serialize();
        let isEdit = $('input[name="id"]').length > 0;
        let url = isEdit ? '/erm/permintaan/' + $('input[name="id"]').val() : '{{ route('erm.permintaan.store') }}';
        let method = isEdit ? 'PUT' : 'POST';
        $.ajax({
            url: url,
            type: method,
            data: data,
            success: function(res) {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: 'Permintaan berhasil disimpan!',
                    timer: 1200,
                    showConfirmButton: false
                });
                setTimeout(function(){ window.location = '{{ route('erm.permintaan.index') }}'; }, 1200);
            },
            error: function(xhr) {
                let msg = 'Gagal menyimpan permintaan!';
                if(xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: msg,
                    timer: 2000,
                    showConfirmButton: false
                });
            }
        });
    });
});
</script>
@endsection
