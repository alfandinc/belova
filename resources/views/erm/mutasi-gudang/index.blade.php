@extends('layouts.erm.app')
@section('title', 'Farmasi | Transfer Antar Gudang')
@section('navbar')
    @include('layouts.erm.navbar-farmasi')
@endsection
@section('content')
<div class="container-fluid">
    <div class="d-flex flex-wrap align-items-center justify-content-between mt-3 mb-3">
        <div>
            <h4 class="page-title mb-0">Transfer Antar Gudang</h4>
            <small class="text-muted">Pindahkan stok antar gudang. Stok baru berpindah setelah transfer disetujui.</small>
        </div>
        <button type="button" class="btn btn-primary mt-2 mt-md-0" id="btn-buat-transfer">
            <i class="fas fa-plus"></i> Buat Transfer
        </button>
    </div>

    <div class="card">
        <div class="card-body">
            {{-- Status tabs with counts (follow the gudang / period filters) --}}
            <ul class="nav nav-pills mg-tabs mb-3" id="status-tabs">
                <li class="nav-item"><a class="nav-link active" href="#" data-status="">Semua <span class="badge badge-light ml-1" data-count="all">0</span></a></li>
                <li class="nav-item"><a class="nav-link" href="#" data-status="pending">Menunggu approval <span class="badge badge-warning ml-1" data-count="pending">0</span></a></li>
                <li class="nav-item"><a class="nav-link" href="#" data-status="approved">Disetujui <span class="badge badge-light ml-1" data-count="approved">0</span></a></li>
                <li class="nav-item"><a class="nav-link" href="#" data-status="rejected">Ditolak <span class="badge badge-light ml-1" data-count="rejected">0</span></a></li>
            </ul>

            <div class="form-row align-items-end mb-2">
                <div class="col-md-3 mb-2">
                    <label class="small text-muted mb-1" for="filter_gudang">Gudang (asal atau tujuan)</label>
                    <select class="form-control form-control-sm" id="filter_gudang">
                        <option value="">Semua Gudang</option>
                        @foreach($gudangs as $gudang)
                            <option value="{{ $gudang->id }}">{{ $gudang->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2 mb-2">
                    <label class="small text-muted mb-1" for="filter_start">Dari tanggal</label>
                    <input type="date" class="form-control form-control-sm" id="filter_start">
                </div>
                <div class="col-6 col-md-2 mb-2">
                    <label class="small text-muted mb-1" for="filter_end">Sampai tanggal</label>
                    <input type="date" class="form-control form-control-sm" id="filter_end">
                </div>
                <div class="col-md-3 mb-2">
                    <label class="small text-muted mb-1" for="filter_search">Cari</label>
                    <input type="search" class="form-control form-control-sm" id="filter_search" placeholder="Nama obat atau nomor transfer">
                </div>
                <div class="col-md-2 mb-2">
                    <button type="button" class="btn btn-sm btn-light btn-block" id="btn-reset-filter"><i class="fas fa-undo"></i> Reset filter</button>
                </div>
            </div>

            <table class="table table-hover table-sm w-100" id="mutasi-table">
                <thead class="thead-light">
                    <tr>
                        <th>Nomor / Tanggal</th>
                        <th>Rute</th>
                        <th>Obat</th>
                        <th>Peminta / Keputusan</th>
                        <th>Status</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<!-- Modal Buat Transfer -->
<div class="modal fade" id="modalMutasi" tabindex="-1" role="dialog" aria-labelledby="modalMutasiLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalMutasiLabel">Buat Transfer Antar Gudang</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="form-mutasi" action="{{ route('erm.mutasi-gudang.store') }}" method="POST" autocomplete="off">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="gudang_asal_id">1. Gudang Asal <span class="text-danger">*</span></label>
                                <select name="gudang_asal_id" id="gudang_asal_id" class="form-control" required>
                                    <option value="">Pilih Gudang Asal</option>
                                    @foreach($gudangTransfer as $gudang)
                                        <option value="{{ $gudang->id }}">{{ $gudang->nama }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="gudang_tujuan_id">2. Gudang Tujuan <span class="text-danger">*</span></label>
                                <select name="gudang_tujuan_id" id="gudang_tujuan_id" class="form-control" required>
                                    <option value="">Pilih Gudang Tujuan</option>
                                    @foreach($gudangTransfer as $gudang)
                                        <option value="{{ $gudang->id }}">{{ $gudang->nama }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <label class="mb-1">3. Obat yang ditransfer</label>
                    <div class="table-responsive">
                        <table class="table table-sm mb-2" id="mutasi-items-table">
                            <thead>
                                <tr>
                                    <th style="width:42%">Obat</th>
                                    <th style="width:22%">Jumlah</th>
                                    <th style="width:28%">Keterangan</th>
                                    <th style="width:8%"></th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                    <div class="d-flex flex-wrap justify-content-between align-items-center">
                        <button type="button" id="add-item" class="btn btn-sm btn-outline-primary mb-2"><i class="fas fa-plus"></i> Tambah Obat</button>
                        <small class="text-muted mb-2" id="item-summary"></small>
                    </div>
                    <small class="text-muted d-block">Tips: tekan <kbd>Enter</kbd> di kolom jumlah untuk menambah baris obat berikutnya. Stok yang tampil adalah semua stok di gudang asal; obat dengan stok ED diberi tanda <span class="badge badge-danger">ED</span>. Batch layak pakai diambil dulu, batch ED hanya bila jumlahnya melebihi stok layak pakai.</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btn-simpan-mutasi"><i class="fas fa-paper-plane"></i> Ajukan Transfer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Detail -->
<div class="modal fade" id="modalDetail" tabindex="-1" role="dialog" aria-labelledby="modalDetailLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalDetailLabel">Detail Transfer</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="modalDetailContent"></div>
            <div class="modal-footer">
                <a href="#" target="_blank" class="btn btn-outline-secondary mr-auto" id="btn-print-detail"><i class="fas fa-print"></i> Cetak</a>
                <button type="button" class="btn btn-light" data-dismiss="modal">Tutup</button>
                <div id="approval-buttons" style="display: none;">
                    <button type="button" class="btn btn-outline-danger btn-reject"><i class="fas fa-times"></i> Tolak</button>
                    <button type="button" class="btn btn-success btn-approve"><i class="fas fa-check"></i> Setujui</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    var CSRF = "{{ csrf_token() }}";
    var BASE_URL = "{{ url('erm/mutasi-gudang') }}";
    var currentDetail = null;
    var currentStatus = '';

    function escapeHtml(value) {
        return String(value === undefined || value === null ? '' : value).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function fmt(n) {
        return Number(n || 0).toLocaleString('id-ID', { maximumFractionDigits: 2 });
    }

    function xhrMessage(xhr, fallback) {
        var json = xhr && xhr.responseJSON;
        if (json && json.errors) {
            var list = [];
            Object.keys(json.errors).forEach(function (k) { list = list.concat(json.errors[k]); });
            return list.filter(function (v, i, a) { return a.indexOf(v) === i; }).map(escapeHtml).join('<br>');
        }
        return escapeHtml((json && json.message) || fallback);
    }

    function notifySuccess(message) {
        Swal.fire({ icon: 'success', title: 'Berhasil', text: message, timer: 2200, showConfirmButton: false });
    }

    function notifyError(html, title) {
        Swal.fire({ icon: 'error', title: title || 'Gagal', html: html });
    }

    function notifyWarning(text) {
        Swal.fire({ icon: 'warning', title: 'Periksa lagi', text: text });
    }

    // ---------- Table ----------
    // Server values arrive HTML-escaped (Yajra); only the markup below is added here
    var STATUS = {
        pending: '<span class="badge badge-warning">Menunggu approval</span>',
        approved: '<span class="badge badge-success">Disetujui</span>',
        rejected: '<span class="badge badge-danger">Ditolak</span>'
    };

    var table = $('#mutasi-table').DataTable({
        processing: true,
        serverSide: true,
        deferRender: true,
        searchDelay: 400,
        dom: 'rt<"d-flex flex-wrap justify-content-between align-items-center mt-2"lip>',
        pageLength: 25,
        lengthMenu: [10, 25, 50, 100],
        autoWidth: false,
        ajax: {
            url: "{{ route('erm.mutasi-gudang.data') }}",
            data: function(d) {
                d.status = currentStatus;
                d.gudang_id = $('#filter_gudang').val();
                d.start = $('#filter_start').val();
                d.end = $('#filter_end').val();
            }
        },
        language: {
            processing: '<i class="fas fa-spinner fa-spin"></i> Memuat...',
            zeroRecords: 'Tidak ada transfer yang cocok',
            emptyTable: 'Belum ada transfer',
            info: '_START_–_END_ dari _TOTAL_ transfer',
            infoEmpty: '0 transfer',
            infoFiltered: '',
            lengthMenu: 'Tampil _MENU_',
            paginate: { previous: '‹', next: '›' }
        },
        createdRow: function (row, data) {
            $(row).addClass('mg-row').attr('data-id', data.id);
            if (data.status === 'pending') $(row).addClass('mg-pending');
        },
        drawCallback: function() {
            var json = this.api().ajax.json();
            var counts = (json && json.counts) || {};
            $('#status-tabs [data-count]').each(function () {
                $(this).text(counts[$(this).data('count')] || 0);
            });
        },
        columns: [
            {
                data: 'tanggal',
                name: 'tanggal',
                searchable: false,
                render: function(data, type, row) {
                    return '<div class="font-weight-bold text-nowrap">' + row.nomor_mutasi + '</div><small class="text-muted">' + data + '</small>';
                }
            },
            {
                data: null,
                orderable: false,
                searchable: false,
                render: function(data, type, row) {
                    return '<div class="text-nowrap"><i class="fas fa-arrow-circle-left text-danger mr-1"></i>' + (row.gudang_asal || '-') + '</div>'
                        + '<div class="text-nowrap"><i class="fas fa-arrow-circle-right text-success mr-1"></i>' + (row.gudang_tujuan || '-') + '</div>';
                }
            },
            {
                data: 'obat',
                name: 'obat',
                orderable: false,
                render: function(data) {
                    if (!data) return '-';
                    var html = data.nama.join(', ');
                    if (data.lainnya > 0) html += ' <span class="text-muted">+' + data.lainnya + ' lainnya</span>';
                    return '<div>' + html + '</div><small class="text-muted">' + data.jumlah_item + ' obat</small>';
                }
            },
            {
                data: 'peminta',
                orderable: false,
                searchable: false,
                render: function(data, type, row) {
                    var html = '<div><i class="fas fa-user text-primary mr-1"></i>' + (data || '-') + '</div>';
                    if (row.diputuskan && row.diputuskan.nama) {
                        var rejected = row.status === 'rejected';
                        html += '<small class="text-muted"><i class="fas ' + (rejected ? 'fa-user-times text-danger' : 'fa-user-check text-success') + ' mr-1"></i>'
                            + row.diputuskan.nama + (row.diputuskan.waktu ? ' · ' + row.diputuskan.waktu : '') + '</small>';
                    }
                    return html;
                }
            },
            { data: 'status', name: 'status', searchable: false, render: function (data) { return STATUS[data] || data; } },
            {
                data: null,
                orderable: false,
                searchable: false,
                className: 'text-right text-nowrap',
                render: function(data, type, row) {
                    var main = row.status === 'pending'
                        ? '<button type="button" class="btn btn-sm btn-warning btn-detail" data-id="' + row.id + '"><i class="fas fa-clipboard-check"></i> Tinjau</button>'
                        : '<button type="button" class="btn btn-sm btn-outline-info btn-detail" data-id="' + row.id + '"><i class="fas fa-eye"></i> Detail</button>';
                    return main + ' <a href="' + BASE_URL + '/' + row.id + '/print" class="btn btn-sm btn-outline-secondary" target="_blank" title="Cetak"><i class="fas fa-print"></i></a>';
                }
            },
            { data: 'nomor_mutasi', name: 'nomor_mutasi', visible: false }
        ],
        order: [[0, 'desc']]
    });

    // Status tabs
    $('#status-tabs').on('click', '.nav-link', function (e) {
        e.preventDefault();
        $('#status-tabs .nav-link').removeClass('active');
        $(this).addClass('active');
        currentStatus = $(this).data('status') || '';
        table.draw();
    });

    $('#filter_gudang, #filter_start, #filter_end').on('change', function() {
        table.draw();
    });

    var searchTimer;
    $('#filter_search').on('input', function () {
        var value = this.value;
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function () { table.search(value).draw(); }, 400);
    });

    $('#btn-reset-filter').on('click', function () {
        $('#filter_gudang').val('');
        $('#filter_start, #filter_end, #filter_search').val('');
        $('#status-tabs .nav-link').removeClass('active').first().addClass('active');
        currentStatus = '';
        table.search('').draw();
    });

    // Whole row opens the detail (links and buttons keep their own action)
    $('#mutasi-table tbody').on('click', 'tr.mg-row', function (e) {
        if ($(e.target).closest('a, button').length) return;
        openDetail($(this).data('id'));
    });

    // ---------- Form ----------
    $('#gudang_asal_id, #gudang_tujuan_id').select2({
        dropdownParent: $('#modalMutasi'),
        width: '100%'
    });

    // A gudang cannot be both source and destination
    function syncTujuanOptions() {
        var asal = $('#gudang_asal_id').val();
        $('#gudang_tujuan_id option').each(function () {
            $(this).prop('disabled', !!asal && this.value === asal);
        });
        if (asal && $('#gudang_tujuan_id').val() === asal) $('#gudang_tujuan_id').val('');
        $('#gudang_tujuan_id').trigger('change.select2');
    }

    function formatObat(obat) {
        if (obat.loading) return obat.text;
        var satuan = obat.satuan ? ' ' + escapeHtml(obat.satuan) : '';
        var stokEd = Number(obat.stok_ed || 0);
        var ed = stokEd > 0
            ? '<br><small class="text-danger font-weight-bold"><i class="fas fa-exclamation-triangle"></i> ' + fmt(stokEd) + satuan + ' sudah ED'
                + (obat.ed_terlama ? ' (ED ' + escapeHtml(obat.ed_terlama) + ')' : '') + '</small>'
            : '';
        return $('<div><div>' + escapeHtml(obat.nama) + (stokEd > 0 ? ' <span class="badge badge-danger">ED</span>' : '') + '</div>'
            + '<small class="text-muted">Stok: ' + fmt(obat.stok) + satuan + (stokEd > 0 ? ' (layak pakai ' + fmt(obat.stok - stokEd) + satuan + ')' : '') + '</small>' + ed + '</div>');
    }

    // Expired stok in this row that the jumlah would take (usable batches are used first)
    function rowEdTerpakai($row, jumlah) {
        var stok = Number($row.data('stok') || 0);
        var stokEd = Number($row.data('stok-ed') || 0);
        if (!stokEd || jumlah === null) return 0;
        return Math.max(0, Math.min(stokEd, jumlah - (stok - stokEd)));
    }

    function formatObatSelection(obat) {
        if (!obat) return '';
        return obat.nama || obat.text || '';
    }

    function normalizeDecimalInput(value) {
        if (value === undefined || value === null) return null;
        var normalized = String(value).trim().replace(',', '.');
        if (normalized === '') return null;
        var parsed = parseFloat(normalized);
        return isNaN(parsed) ? null : parsed;
    }

    function updateItemSummary() {
        var count = $('#mutasi-items-table .item-obat').filter(function () { return $(this).val(); }).length;
        var over = $('#mutasi-items-table .item-jumlah.is-invalid').length;
        $('#item-summary').html(count ? count + ' obat dipilih' + (over ? ' · <span class="text-danger">' + over + ' melebihi stok</span>' : '') : '');
    }

    // Stok hint and over-stok warning for one row
    function refreshRowStock($row) {
        var stok = $row.data('stok');
        var satuan = $row.data('satuan') || '';
        var $hint = $row.find('.item-stok');
        var $input = $row.find('.item-jumlah');
        if (stok === undefined || stok === null) {
            $hint.text('').removeClass('text-danger');
            $input.removeClass('is-invalid');
        } else {
            var jumlah = normalizeDecimalInput($input.val());
            var lebih = jumlah !== null && jumlah > stok;
            var edTerpakai = lebih ? 0 : rowEdTerpakai($row, jumlah);
            var stokEd = Number($row.data('stok-ed') || 0);
            var text = (lebih ? 'Melebihi stok! ' : '') + 'Tersedia ' + fmt(stok) + (satuan ? ' ' + satuan : '');
            if (edTerpakai > 0) text += ' · ' + fmt(edTerpakai) + (satuan ? ' ' + satuan : '') + ' diambil dari stok ED!';
            else if (stokEd > 0) text += ' (termasuk ' + fmt(stokEd) + ' ED)';
            $hint.text(text).toggleClass('text-danger', lebih || edTerpakai > 0).toggleClass('text-muted', !lebih && !edTerpakai);
            $input.toggleClass('is-invalid', lebih);
        }
        updateItemSummary();
    }

    function initItemSelect2($select) {
        $select.select2({
            dropdownParent: $('#modalMutasi'),
            width: '100%',
            ajax: {
                url: "{{ route('erm.mutasi-gudang.obat') }}",
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return { q: params.term, gudang_id: $('#gudang_asal_id').val() };
                },
                processResults: function(data) {
                    return { results: data.results };
                },
                cache: false
            },
            placeholder: 'Cari obat...',
            minimumInputLength: 1,
            language: { inputTooShort: function () { return 'Ketik nama obat'; }, noResults: function () { return $('#gudang_asal_id').val() ? 'Tidak ada obat dengan stok di gudang ini' : 'Pilih gudang asal dulu'; } },
            templateResult: formatObat,
            templateSelection: formatObatSelection
        });

        $select.on('select2:opening', function (e) {
            if (!$('#gudang_asal_id').val()) {
                e.preventDefault();
                notifyWarning('Pilih gudang asal terlebih dahulu.');
            }
        });

        $select.on('select2:select', function(e) {
            var obat = e.params && e.params.data ? e.params.data : null;
            var $row = $(this).closest('tr');

            // The same obat once per transfer
            var duplicate = false;
            $('#mutasi-items-table tbody tr').not($row).each(function () {
                if (String($(this).find('.item-obat').val()) === String(obat.id)) duplicate = true;
            });
            if (duplicate) {
                $(this).val(null).trigger('change');
                $row.removeData('stok').removeData('stok-ed').removeData('satuan');
                $row.find('.item-satuan').text('');
                refreshRowStock($row);
                notifyWarning(obat.nama + ' sudah ada di daftar. Ubah jumlah di baris tersebut.');
                return;
            }

            $row.data('stok', obat ? Number(obat.stok) : null).data('stok-ed', obat ? Number(obat.stok_ed || 0) : 0).data('satuan', obat && obat.satuan ? obat.satuan : '');
            $row.find('.item-satuan').text(obat && obat.satuan ? obat.satuan : '');
            refreshRowStock($row);
            var $jumlah = $row.find('.item-jumlah');
            if (obat && Number(obat.stok_ed) > 0) {
                var satuan = obat.satuan ? ' ' + obat.satuan : '';
                Swal.fire({
                    icon: 'warning',
                    title: 'Obat ini punya stok ED',
                    html: '<strong>' + escapeHtml(obat.nama) + '</strong>: ' + fmt(obat.stok_ed) + escapeHtml(satuan) + ' dari ' + fmt(obat.stok) + escapeHtml(satuan)
                        + ' sudah lewat ED' + (obat.ed_terlama ? ' (ED ' + escapeHtml(obat.ed_terlama) + ')' : '') + '.'
                        + '<div class="small text-muted mt-2">Batch layak pakai diambil dulu. Bila jumlah melebihi stok layak pakai ('
                        + fmt(obat.stok - obat.stok_ed) + escapeHtml(satuan) + '), batch ED ikut ditransfer beserta tanggal ED-nya.</div>'
                }).then(function () { $jumlah.trigger('focus').trigger('select'); });
            } else {
                $jumlah.trigger('focus').trigger('select');
            }
        });

        $select.on('select2:clear', function() {
            var $row = $(this).closest('tr');
            $row.removeData('stok').removeData('stok-ed').removeData('satuan');
            $row.find('.item-satuan').text('');
            refreshRowStock($row);
        });
    }

    function addItemRow() {
        var rowId = Date.now() + Math.floor(Math.random() * 1000);
        var $tr = $('<tr data-row-id="' + rowId + '"></tr>');
        $tr.append('<td><select class="form-control item-obat"><option value="">Pilih Obat</option></select></td>');
        $tr.append('<td><div class="input-group">'
            + '<input type="text" inputmode="decimal" class="form-control item-jumlah" value="1" placeholder="Contoh: 1,5">'
            + '<div class="input-group-append"><span class="input-group-text item-satuan" style="min-width:60px"></span></div>'
            + '</div><small class="item-stok text-muted"></small></td>');
        $tr.append('<td><input type="text" class="form-control item-keterangan" maxlength="255" placeholder="Opsional"></td>');
        $tr.append('<td class="text-right"><button type="button" class="btn btn-sm btn-outline-danger btn-remove-item" title="Hapus baris"><i class="fas fa-times"></i></button></td>');
        $('#mutasi-items-table tbody').append($tr);
        initItemSelect2($tr.find('.item-obat'));
        refreshRemoveButtons();
        return $tr;
    }

    function refreshRemoveButtons() {
        $('#mutasi-items-table .btn-remove-item').prop('disabled', $('#mutasi-items-table tbody tr').length <= 1);
        updateItemSummary();
    }

    function resetTransferForm() {
        $('#form-mutasi')[0].reset();
        $('#gudang_asal_id, #gudang_tujuan_id').val('').trigger('change.select2');
        syncTujuanOptions();
        lastGudangAsal = '';
        $('#mutasi-items-table tbody').empty();
        addItemRow();
    }

    $('#btn-buat-transfer').on('click', function () {
        resetTransferForm();
        $('#modalMutasi').modal('show');
    });

    $('#modalMutasi').on('shown.bs.modal', function () {
        if (!$('#gudang_asal_id').val()) $('#gudang_asal_id').select2('open');
    });

    $('#add-item').on('click', function() {
        addItemRow().find('.item-obat').select2('open');
    });

    $(document).on('click', '.btn-remove-item', function() {
        $(this).closest('tr').remove();
        refreshRemoveButtons();
    });

    $(document).on('input', '.item-jumlah', function () {
        refreshRowStock($(this).closest('tr'));
    });

    // Enter in a jumlah field: next row (reuse an empty one) instead of submitting
    $(document).on('keydown', '.item-jumlah', function (e) {
        if (e.key !== 'Enter') return;
        e.preventDefault();
        var $empty = $('#mutasi-items-table tbody tr').filter(function () { return !$(this).find('.item-obat').val(); }).first();
        ($empty.length ? $empty : addItemRow()).find('.item-obat').select2('open');
    });

    // Items picked for one gudang asal do not carry over to another
    var lastGudangAsal = '';
    $('#gudang_asal_id').on('change', function () {
        var value = $(this).val() || '';
        syncTujuanOptions();
        var hasItems = $('#mutasi-items-table .item-obat').filter(function () { return $(this).val(); }).length > 0;
        if (hasItems && value !== lastGudangAsal) {
            $('#mutasi-items-table tbody').empty();
            addItemRow();
            Swal.fire({ icon: 'info', title: 'Daftar obat dikosongkan', text: 'Gudang asal berubah, jadi obat perlu dipilih ulang dari gudang yang baru.', timer: 2500, showConfirmButton: false });
        }
        lastGudangAsal = value;
        if (value && !$('#gudang_tujuan_id').val()) {
            setTimeout(function () { $('#gudang_tujuan_id').select2('open'); }, 0);
        }
    });

    $('#gudang_tujuan_id').on('select2:select', function () {
        var $first = $('#mutasi-items-table tbody tr').filter(function () { return !$(this).find('.item-obat').val(); }).first();
        if ($first.length && $('#gudang_asal_id').val()) setTimeout(function () { $first.find('.item-obat').select2('open'); }, 0);
    });

    $('#form-mutasi').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        var gudangAsal = $('#gudang_asal_id').val();
        var gudangTujuan = $('#gudang_tujuan_id').val();

        if (!gudangAsal || !gudangTujuan) return notifyWarning('Pilih gudang asal dan gudang tujuan.');
        if (gudangAsal === gudangTujuan) return notifyWarning('Gudang tujuan tidak boleh sama dengan gudang asal.');

        var items = [];
        var edItems = [];
        var problem = null;
        $('#mutasi-items-table tbody tr').each(function() {
            var $row = $(this);
            var obatId = $row.find('.item-obat').val();
            var jumlah = normalizeDecimalInput($row.find('.item-jumlah').val());
            if (!obatId && (jumlah === null || jumlah === 1)) return; // untouched empty row
            if (!obatId) { problem = 'Ada baris yang belum memilih obat.'; return false; }
            if (jumlah === null || jumlah <= 0) { problem = 'Jumlah setiap obat harus lebih dari 0.'; return false; }
            var stok = $row.data('stok');
            if (stok !== undefined && stok !== null && jumlah > stok) {
                problem = $row.find('.item-obat').select2('data')[0].nama + ': jumlah melebihi stok tersedia (' + fmt(stok) + ').';
                return false;
            }
            var edTerpakai = rowEdTerpakai($row, jumlah);
            if (edTerpakai > 0) {
                edItems.push('<li>' + escapeHtml($row.find('.item-obat').select2('data')[0].nama) + ': <strong>' + fmt(edTerpakai) + ' ' + escapeHtml($row.data('satuan') || '') + '</strong> dari stok ED</li>');
            }
            items.push({ obat_id: obatId, jumlah: jumlah, keterangan: $row.find('.item-keterangan').val() });
        });

        if (problem) return notifyWarning(problem);
        if (!items.length) return notifyWarning('Tambahkan minimal satu obat.');

        if (edItems.length) {
            Swal.fire({
                icon: 'warning',
                title: 'Transfer stok yang sudah ED?',
                html: '<ul class="text-left small mb-2">' + edItems.join('') + '</ul>'
                    + '<div class="small text-muted">Batch ED ikut pindah ke gudang tujuan beserta tanggal ED-nya.</div>',
                showCancelButton: true,
                confirmButtonText: 'Ya, tetap ajukan',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#dc3545',
                reverseButtons: true
            }).then(function (result) {
                if (result.value) kirimTransfer(form, gudangAsal, gudangTujuan, items);
            });
            return;
        }
        kirimTransfer(form, gudangAsal, gudangTujuan, items);
    });

    function kirimTransfer(form, gudangAsal, gudangTujuan, items) {
        var $btn = $('#btn-simpan-mutasi').prop('disabled', true);
        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: { _token: CSRF, gudang_asal_id: gudangAsal, gudang_tujuan_id: gudangTujuan, items: items }
        }).done(function(response) {
            $('#modalMutasi').modal('hide');
            table.draw(false);
            // Approvers can review it right away
            Swal.fire({
                icon: 'success',
                title: 'Transfer diajukan',
                text: response.message,
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-clipboard-check"></i> Tinjau sekarang',
                cancelButtonText: 'Nanti',
                reverseButtons: true
            }).then(function (result) {
                if (result.value && response.id) openDetail(response.id);
            });
        }).fail(function(xhr) {
            notifyError(xhrMessage(xhr, 'Transfer gagal disimpan.'), 'Gagal menyimpan');
        }).always(function () {
            $btn.prop('disabled', false);
        });
    }

    // ---------- Detail & approval ----------
    function openDetail(id) {
        $('#modalDetailContent').html('<div class="text-center text-muted py-5"><i class="fas fa-spinner fa-spin fa-2x"></i></div>');
        $('#approval-buttons').hide();
        $('#btn-print-detail').attr('href', BASE_URL + '/' + id + '/print');
        $('#modalDetail').modal('show');
        $.get(BASE_URL + '/' + id).done(function(response) {
            currentDetail = $.extend({ id: id }, response);
            $('#modalDetailContent').html(response.html);
            var canApprove = !!(response.status === 'pending' && response.can_approve);
            $('#approval-buttons').toggle(canApprove);
            // Not enough stok: approving would fail, so only rejecting is offered
            $('.btn-approve').prop('disabled', canApprove && response.stok_kurang > 0)
                .attr('title', response.stok_kurang > 0 ? 'Stok di gudang asal belum cukup' : '');
        }).fail(function (xhr) {
            $('#modalDetail').modal('hide');
            notifyError(xhrMessage(xhr, 'Detail transfer tidak dapat dimuat.'));
        });
    }

    $(document).on('click', '.btn-detail', function() {
        openDetail($(this).data('id'));
    });

    $('.btn-approve').on('click', function() {
        if (!currentDetail) return;
        var d = currentDetail;
        var list = d.items.map(function (it) {
            return '<li>' + escapeHtml(it.nama) + ': <strong>' + escapeHtml(it.jumlah) + ' ' + escapeHtml(it.satuan) + '</strong>'
                + (it.jumlah_ed ? ' <span class="badge badge-danger">' + escapeHtml(it.jumlah_ed) + ' ' + escapeHtml(it.satuan) + ' sudah ED</span>' : '') + '</li>';
        }).join('');
        Swal.fire({
            icon: d.jumlah_obat_ed > 0 ? 'warning' : 'question',
            title: 'Setujui ' + escapeHtml(d.nomor_mutasi) + '?',
            html: '<div class="mb-2"><strong>' + escapeHtml(d.gudang_asal) + '</strong> &rarr; <strong>' + escapeHtml(d.gudang_tujuan) + '</strong></div>'
                + '<ul class="text-left small mb-2">' + list + '</ul>'
                + (d.jumlah_obat_ed > 0 ? '<div class="small text-danger mb-1"><i class="fas fa-exclamation-triangle"></i> ' + d.jumlah_obat_ed + ' obat ikut memindahkan batch yang sudah ED.</div>' : '')
                + '<div class="small text-muted">Stok langsung berpindah (batch layak pakai dengan ED terdekat dulu, batch ED paling akhir) dan tercatat di kartu stok kedua gudang.</div>',
            showCancelButton: true,
            confirmButtonText: 'Ya, setujui',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#28a745',
            reverseButtons: true
        }).then(function (result) {
            if (!result.value) return;
            var $btns = $('.btn-approve, .btn-reject').prop('disabled', true);
            $.post(BASE_URL + '/' + d.id + '/approve', { _token: CSRF })
                .done(function(response) {
                    $('#modalDetail').modal('hide');
                    table.draw(false);
                    notifySuccess(response.message);
                })
                .fail(function(xhr) {
                    notifyError(xhrMessage(xhr, 'Transfer gagal disetujui.'), 'Gagal menyetujui');
                })
                .always(function () { $btns.prop('disabled', false); });
        });
    });

    $('.btn-reject').on('click', function() {
        if (!currentDetail) return;
        var d = currentDetail;
        Swal.fire({
            icon: 'warning',
            title: 'Tolak ' + escapeHtml(d.nomor_mutasi) + '?',
            text: 'Stok tidak berubah. Tulis alasan penolakan untuk peminta.',
            input: 'textarea',
            inputPlaceholder: 'Alasan penolakan...',
            inputAttributes: { maxlength: 200 },
            inputValidator: function (value) {
                if (!value || !value.trim()) return 'Alasan penolakan wajib diisi.';
            },
            showCancelButton: true,
            confirmButtonText: 'Tolak transfer',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#d33',
            reverseButtons: true
        }).then(function (result) {
            if (!result.value) return;
            var $btns = $('.btn-approve, .btn-reject').prop('disabled', true);
            $.post(BASE_URL + '/' + d.id + '/reject', { _token: CSRF, reason: result.value.trim() })
                .done(function(response) {
                    $('#modalDetail').modal('hide');
                    table.draw(false);
                    notifySuccess(response.message);
                })
                .fail(function(xhr) {
                    notifyError(xhrMessage(xhr, 'Transfer gagal ditolak.'), 'Gagal menolak');
                })
                .always(function () { $btns.prop('disabled', false); });
        });
    });
});
</script>
@endpush

@push('styles')
<style>
.mg-tabs .nav-link { padding: .35rem .8rem; font-size: .875rem; }
#mutasi-table td { vertical-align: middle; white-space: normal; }
#mutasi-table tr.mg-row { cursor: pointer; }
#mutasi-table tr.mg-pending td:first-child { box-shadow: inset 3px 0 0 #ffc107; }
#mutasi-items-table td { vertical-align: top; }
#modalMutasi .select2-container { width: 100% !important; }
.select2-dropdown { z-index: 9999; }
</style>
@endpush
