@extends('layouts.erm.app')
@section('title', 'Farmasi | Penyesuaian Stok')
@section('navbar')
    @include('layouts.erm.navbar-farmasi')
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">Penyesuaian Stok</h4>
                <small class="text-muted">Satu-satunya dokumen koreksi stok di luar pembelian, penjualan, transfer, dan retur. Setiap item wajib beralasan dan tercatat di kartu stok.
                    Dokumen bernilai &ge; Rp {{ number_format($approvalThreshold, 0, ',', '.') }} dan semua penyesuaian masuk menunggu approval Admin sebelum stok berubah.</small>
            </div>
        </div>
    </div>

    @if ($isApprover && $pendingCount > 0)
    <div class="alert alert-warning d-flex justify-content-between align-items-center">
        <span><i class="fas fa-user-check"></i> <strong>{{ $pendingCount }}</strong> dokumen penyesuaian menunggu approval.</span>
        <button type="button" class="btn btn-sm btn-warning" id="btn-filter-pending">Tampilkan</button>
    </div>
    @endif

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row mb-3 align-items-center">
                        <div class="col-md-3 mb-2 mb-md-0">
                            <select class="form-control" id="filter_gudang">
                                <option value="">Semua Gudang</option>
                                @foreach($gudangs as $gudang)
                                    <option value="{{ $gudang->id }}">{{ $gudang->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 mb-2 mb-md-0">
                            <select class="form-control" id="filter_jenis_mutasi">
                                <option value="">Semua Jenis</option>
                                <option value="masuk">Masuk</option>
                                <option value="keluar">Keluar</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-2 mb-md-0">
                            <select class="form-control" id="filter_status">
                                <option value="">Semua Status</option>
                                <option value="pending">Menunggu Approval</option>
                                <option value="done">Diposting</option>
                                <option value="rejected">Ditolak</option>
                                <option value="cancelled">Dibatalkan</option>
                            </select>
                        </div>
                        <div class="col-md-3 text-md-right">
                            <button type="button" class="btn btn-primary" id="btn-buat-mutasi">
                                <i class="fas fa-plus"></i> Buat Penyesuaian
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered dt-responsive w-100" id="mutasi-stok-table">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Tanggal Input</th>
                                    <th>Nomor Dokumen</th>
                                    <th>Gudang</th>
                                    <th>Jenis</th>
                                    <th>Item</th>
                                    <th>Nilai</th>
                                    <th>User</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalMutasiStok" tabindex="-1" role="dialog" aria-labelledby="modalMutasiStokLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-xl" role="document" style="max-width: 1300px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalMutasiStokLabel">Buat Penyesuaian Stok</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="form-mutasi-stok" action="{{ route('erm.mutasi-stok.store') }}" method="POST">
                @csrf
                <input type="hidden" name="edit_id" id="edit_id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="gudang_id">Gudang <span class="text-danger">*</span></label>
                                <select class="form-control" name="gudang_id" id="gudang_id" required>
                                    <option value="">Pilih Gudang</option>
                                    @foreach($gudangs as $gudang)
                                        <option value="{{ $gudang->id }}">{{ $gudang->nama }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="jenis_mutasi">Jenis <span class="text-danger">*</span></label>
                                <select class="form-control" name="jenis_mutasi" id="jenis_mutasi" required>
                                    <option value="">Pilih Jenis</option>
                                    <option value="keluar">Keluar (mengurangi stok)</option>
                                    @if ($canMasuk)
                                        <option value="masuk">Masuk (menambah stok)</option>
                                    @endif
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="tanggal_mutasi">Tanggal <span class="text-danger">*</span></label>
                                <input type="datetime-local" class="form-control" name="tanggal_mutasi" id="tanggal_mutasi" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="tanggal_input_preview">Tanggal Input</label>
                                <input type="text" class="form-control" id="tanggal_input_preview" readonly placeholder="Otomatis saat disimpan">
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info mb-3 small" id="mutasi-info-box"></div>

                    <div class="table-responsive">
                        <table class="table table-sm" id="mutasi-stok-items-table">
                            <thead>
                                <tr>
                                    <th style="width: 26%;">Obat</th>
                                    <th style="width: 20%;">Batch</th>
                                    <th style="width: 10%;">Stok</th>
                                    <th style="width: 12%;">Jumlah</th>
                                    <th style="width: 13%;">Alasan <span class="text-danger">*</span></th>
                                    <th style="width: 15%;">Keterangan <span class="text-danger">*</span></th>
                                    <th style="width: 4%;"></th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="add-item-row"><i class="fas fa-plus"></i> Tambah Item</button>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-primary" id="btn-simpan-mutasi"><i class="fas fa-save"></i> Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalDetailMutasiStok" tabindex="-1" role="dialog" aria-labelledby="modalDetailMutasiStokLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalDetailMutasiStokLabel">Detail Penyesuaian Stok</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="detail-mutasi-stok-body"></div>
            <div class="modal-footer">
                <div class="mr-auto" id="detail-mutasi-stok-actions"></div>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<style>.swal2-container { z-index: 2000 !important; }</style>
<script>
$(function () {
    var ALASAN = @json($alasan);
    var PREFILL = @json($prefill);
    var BATCH_BARU = '__baru__';
    var BATCH_NULL = '__null__'; // the row without a batch name ("" means FEFO / not chosen)
    var isEditMode = false;
    var THRESHOLD_LABEL = 'Rp {{ number_format($approvalThreshold, 0, ',', '.') }}';

    function escapeHtml(value) {
        if (value === null || value === undefined) return '';
        return String(value).replace(/[&<>"']/g, function (s) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[s];
        });
    }
    function fmtQty(n) {
        return Number(n || 0).toLocaleString('id-ID', { maximumFractionDigits: 2 });
    }
    function notifySuccess(message) {
        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: message, showConfirmButton: false, timer: 3000, timerProgressBar: true });
    }
    function notifyWarning(html) {
        Swal.fire({ icon: 'warning', title: 'Periksa kembali', html: html });
    }
    function notifyError(message) {
        Swal.fire({ icon: 'error', title: 'Gagal', text: message });
    }
    function xhrMessage(xhr, fallback) {
        var json = xhr && xhr.responseJSON;
        if (json && json.errors) {
            return Object.keys(json.errors).map(function (k) { return json.errors[k][0]; }).filter(function (v, i, a) { return a.indexOf(v) === i; }).join('\n');
        }
        return (json && json.message) || fallback;
    }

    function pad(n) { return String(n).padStart(2, '0'); }
    function getCurrentTimestampLabel() {
        var d = new Date();
        return pad(d.getDate()) + '/' + pad(d.getMonth() + 1) + '/' + d.getFullYear() + ' ' + pad(d.getHours()) + ':' + pad(d.getMinutes());
    }
    function getCurrentDateValue() {
        var d = new Date();
        return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()) + 'T' + pad(d.getHours()) + ':' + pad(d.getMinutes());
    }

    var table = $('#mutasi-stok-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('erm.mutasi-stok.data') }}",
            data: function (d) {
                d.gudang_id = $('#filter_gudang').val();
                d.jenis_mutasi = $('#filter_jenis_mutasi').val();
                d.status = $('#filter_status').val();
            }
        },
        pageLength: 25,
        order: [[0, 'desc']],
        columns: [
            { data: 'tanggal', name: 'tanggal_mutasi' },
            { data: 'tanggal_input', name: 'tanggal_input' },
            { data: 'nomor_mutasi', name: 'nomor_mutasi' },
            { data: 'gudang_nama', name: 'gudang.nama', orderable: false },
            { data: 'jenis_label', name: 'jenis_mutasi', searchable: false },
            { data: 'item_summary', name: 'items.obat.nama', orderable: false, searchable: false },
            { data: 'nilai', name: 'total_nilai', searchable: false, className: 'text-right' },
            { data: 'user_name', name: 'user.name', orderable: false },
            { data: 'status_label', name: 'status', searchable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ]
    });

    $('#filter_gudang, #filter_jenis_mutasi, #filter_status').on('change', function () {
        table.draw();
    });

    $('#gudang_id, #jenis_mutasi').select2({
        dropdownParent: $('#modalMutasiStok'),
        width: '100%'
    });

    function resetFormState() {
        isEditMode = false;
        $('#form-mutasi-stok')[0].reset();
        $('#edit_id').val('');
        $('#modalMutasiStokLabel').text('Buat Mutasi Stok');
        $('#tanggal_mutasi').val(getCurrentDateValue());
        $('#tanggal_input_preview').val(getCurrentTimestampLabel());
        $('#gudang_id, #jenis_mutasi').val(null).trigger('change.select2');
        $('#mutasi-stok-items-table tbody').empty();
        updateInfoBox();
    }

    function updateInfoBox() {
        var jenis = $('#jenis_mutasi').val();
        var message = 'Pilih gudang dan jenis penyesuaian terlebih dahulu.';
        if (jenis === 'keluar') {
            message = 'Keluar mengurangi stok. Pilih batch tertentu (mis. batch yang rusak), atau biarkan "Otomatis" untuk mengambil batch dengan ED terdekat. Alasan dan keterangan wajib diisi dan dicatat di kartu stok. Dokumen bernilai ' + THRESHOLD_LABEL + ' atau lebih menunggu approval Admin.';
        }
        if (jenis === 'masuk') {
            message = 'Masuk menambah stok tanpa dokumen pembelian, jadi hanya untuk koreksi. Pilih batch tujuan yang sudah ada, atau buat batch baru beserta tanggal kadaluarsanya. Penerimaan barang dari supplier tetap lewat Faktur Pembelian. Penyesuaian masuk selalu menunggu approval Admin.';
        }
        $('#mutasi-info-box').text(message);
    }

    function formatObat(obat) {
        if (obat.loading) return obat.text;
        var kode = obat.kode_obat ? '<div class="small text-muted">' + escapeHtml(obat.kode_obat) + '</div>' : '';
        return $('<div><div>' + escapeHtml(obat.nama) + '</div>' + kode + '<div class="small text-muted">Stok gudang: ' + escapeHtml(obat.stok_display) + '</div></div>');
    }
    function formatObatSelection(obat) {
        if (!obat || !obat.id) return obat && obat.text ? obat.text : 'Cari obat';
        return obat.nama || obat.text;
    }

    function alasanOptions(jenis, selected) {
        return '<option value="">Pilih alasan</option>' + (ALASAN[jenis] || []).map(function (a) {
            return '<option value="' + escapeHtml(a) + '"' + (a === selected ? ' selected' : '') + '>' + escapeHtml(a) + '</option>';
        }).join('');
    }

    // Batch picker of a row: keluar offers "Otomatis (FEFO)", masuk offers "Batch baru"
    function loadBatches($row, obatId, selectedBatch) {
        var $batch = $row.find('.item-batch');
        var jenis = $('#jenis_mutasi').val();
        $batch.html('<option value="">Memuat...</option>').prop('disabled', true);

        $.get("{{ route('erm.mutasi-stok.batches') }}", { gudang_id: $('#gudang_id').val(), obat_id: obatId })
            .done(function (res) {
                var batches = res.results || [];
                var html = jenis === 'keluar'
                    ? '<option value="">Otomatis (ED terdekat)</option>'
                    : '<option value="">Pilih batch tujuan</option>';
                batches.forEach(function (b) {
                    if (jenis === 'keluar' && b.stok <= 0) return;
                    var label = (b.batch === null ? '(tanpa batch)' : b.batch)
                        + ' · ' + fmtQty(b.stok)
                        + (b.migrasi ? ' · ED belum diketahui' : (b.expiration_date ? ' · ED ' + b.expiration_date : ''))
                        + (b.expired ? ' · EXPIRED' : '');
                    html += '<option value="' + escapeHtml(b.batch === null ? BATCH_NULL : b.batch) + '" data-stok="' + b.stok + '">' + escapeHtml(label) + '</option>';
                });
                if (jenis === 'masuk') {
                    html += '<option value="' + BATCH_BARU + '">+ Batch baru...</option>';
                }
                $batch.html(html).prop('disabled', false);
                if (selectedBatch !== undefined && selectedBatch !== null) {
                    $batch.val(selectedBatch);
                }
                $batch.trigger('change');
            })
            .fail(function () {
                $batch.html('<option value="">Gagal memuat batch</option>');
            });
    }

    function initItemSelect2($select) {
        $select.select2({
            dropdownParent: $('#modalMutasiStok'),
            width: '100%',
            placeholder: 'Cari obat...',
            minimumInputLength: 1,
            ajax: {
                url: "{{ route('erm.mutasi-stok.obat') }}",
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return { q: params.term, gudang_id: $('#gudang_id').val(), jenis_mutasi: $('#jenis_mutasi').val() };
                },
                processResults: function (data) { return { results: data.results }; }
            },
            templateResult: formatObat,
            templateSelection: formatObatSelection
        });

        $select.on('select2:select', function (e) {
            var obat = e.params.data;
            var $row = $(this).closest('tr');
            $row.data('total-stok', obat.stok || 0);
            $row.find('.item-satuan').text(obat.satuan || '');
            loadBatches($row, obat.id, $row.data('pending-batch'));
            $row.removeData('pending-batch');
        });
    }

    function addItemRow(itemData) {
        var jenis = $('#jenis_mutasi').val();
        if (!$('#gudang_id').val() || !jenis) {
            notifyWarning('Pilih gudang dan jenis mutasi terlebih dahulu.');
            return;
        }

        var $row = $(
            '<tr>' +
                '<td><select class="form-control item-obat"><option value="">Pilih Obat</option></select></td>' +
                '<td>' +
                    '<select class="form-control form-control-sm item-batch" disabled><option value="">Pilih obat dulu</option></select>' +
                    '<div class="item-batch-baru mt-1" style="display:none;">' +
                        '<input type="text" class="form-control form-control-sm mb-1 item-batch-baru-nama" placeholder="Nomor batch baru" maxlength="255">' +
                        '<input type="date" class="form-control form-control-sm item-batch-baru-ed" title="Tanggal kadaluarsa">' +
                    '</div>' +
                '</td>' +
                '<td><span class="item-stok text-muted">-</span></td>' +
                '<td><div class="input-group input-group-sm">' +
                    '<input type="number" step="0.01" min="0.01" class="form-control item-jumlah" value="1">' +
                    '<div class="input-group-append"><span class="input-group-text item-satuan"></span></div>' +
                '</div></td>' +
                '<td><select class="form-control form-control-sm item-alasan">' + alasanOptions(jenis, itemData && itemData.alasan) + '</select></td>' +
                '<td><input type="text" class="form-control form-control-sm item-keterangan" maxlength="200" placeholder="Contoh: botol pecah saat display"></td>' +
                '<td><button type="button" class="btn btn-sm btn-outline-danger btn-remove-item" title="Hapus baris"><i class="fas fa-times"></i></button></td>' +
            '</tr>'
        );
        $('#mutasi-stok-items-table tbody').append($row);
        var $select = $row.find('.item-obat');
        initItemSelect2($select);

        if (itemData) {
            var nama = itemData.nama || itemData.obat_nama;
            if (itemData.batch !== undefined && itemData.batch !== null) $row.data('pending-batch', itemData.batch);
            if (itemData.batch_baru) {
                // Pending document with a new batch: reopen the "Batch baru" fields with their values
                $row.data('pending-batch', BATCH_BARU);
                $row.find('.item-batch-baru-nama').val(itemData.batch_baru);
                $row.find('.item-batch-baru-ed').val(itemData.expiration_date || '');
            }
            $select.append(new Option(nama, itemData.obat_id, true, true)).trigger('change');
            $select.trigger({ type: 'select2:select', params: { data: { id: itemData.obat_id, nama: nama, text: nama, satuan: itemData.satuan || '', stok: itemData.stok || 0 } } });
            if (itemData.jumlah_raw || itemData.jumlah) $row.find('.item-jumlah').val(itemData.jumlah_raw || itemData.jumlah);
            $row.find('.item-keterangan').val(itemData.keterangan_saja !== undefined ? itemData.keterangan_saja : (itemData.keterangan || ''));
        }
    }

    // Stock shown follows the chosen batch; "Batch baru" reveals its fields
    $(document).on('change', '.item-batch', function () {
        var $row = $(this).closest('tr');
        var val = $(this).val();
        var $opt = $(this).find('option:selected');
        $row.find('.item-batch-baru').toggle(val === BATCH_BARU);
        var stok = val === '' ? $row.data('total-stok') : $opt.data('stok');
        $row.find('.item-stok').text(stok !== undefined && val !== BATCH_BARU ? fmtQty(stok) : (val === BATCH_BARU ? '0' : '-'));
    });

    $('#btn-buat-mutasi').on('click', function () {
        resetFormState();
        $('#modalMutasiStok').modal('show');
    });

    $('#modalMutasiStok').on('hidden.bs.modal', function () {
        resetFormState();
    });

    $('#add-item-row').on('click', function () { addItemRow(); });
    $(document).on('click', '.btn-remove-item', function () { $(this).closest('tr').remove(); });

    $('#gudang_id, #jenis_mutasi').on('change', function () {
        $('#mutasi-stok-items-table tbody').empty();
        updateInfoBox();
        if ($('#gudang_id').val() && $('#jenis_mutasi').val() && !isEditMode) {
            addItemRow();
        }
    });

    function collectItems() {
        var jenis = $('#jenis_mutasi').val();
        var items = [];
        var problems = [];

        $('#mutasi-stok-items-table tbody tr').each(function (index) {
            var $row = $(this);
            var no = 'Baris ' + (index + 1) + ': ';
            var obatId = $row.find('.item-obat').val();
            var jumlah = parseFloat($row.find('.item-jumlah').val() || '0');
            var batchVal = $row.find('.item-batch').val();
            var $opt = $row.find('.item-batch option:selected');
            var item = {
                obat_id: obatId,
                jumlah: jumlah,
                alasan: $row.find('.item-alasan').val(),
                keterangan: ($row.find('.item-keterangan').val() || '').trim()
            };

            if (!obatId) { problems.push(no + 'pilih obat.'); return; }
            if (!(jumlah > 0)) problems.push(no + 'jumlah harus lebih dari 0.');
            if (!item.alasan) problems.push(no + 'pilih alasan.');
            if (!item.keterangan) problems.push(no + 'isi keterangan.');

            if (batchVal === BATCH_BARU) {
                item.batch_baru = ($row.find('.item-batch-baru-nama').val() || '').trim();
                item.expiration_date = $row.find('.item-batch-baru-ed').val();
                if (!item.batch_baru || !item.expiration_date) problems.push(no + 'isi nomor batch baru dan tanggal kadaluarsanya.');
            } else if (batchVal !== '') {
                item.batch = batchVal;
                var stokBatch = parseFloat($opt.data('stok') || 0);
                if (jenis === 'keluar' && !isEditMode && jumlah > stokBatch) problems.push(no + 'jumlah melebihi stok batch (' + fmtQty(stokBatch) + ').');
            } else if (jenis === 'masuk') {
                problems.push(no + 'pilih batch tujuan atau buat batch baru.');
            } else if (!isEditMode && jumlah > parseFloat($row.data('total-stok') || 0)) {
                problems.push(no + 'jumlah melebihi stok yang bisa dikeluarkan (' + fmtQty($row.data('total-stok')) + ').');
            }

            item._label = $row.find('.item-obat option:selected').text() + ' · ' + fmtQty(jumlah) + ' · ' + item.alasan;
            items.push(item);
        });

        if (!items.length && !problems.length) problems.push('Tambahkan minimal satu item obat.');
        return { items: items, problems: problems };
    }

    $('#form-mutasi-stok').on('submit', function (e) {
        e.preventDefault();
        var jenis = $('#jenis_mutasi').val();
        if (!$('#gudang_id').val() || !jenis) {
            notifyWarning('Gudang dan jenis mutasi wajib diisi.');
            return;
        }

        var collected = collectItems();
        if (collected.problems.length) {
            notifyWarning(collected.problems.map(escapeHtml).join('<br>'));
            return;
        }

        var summary = collected.items.map(function (it) { return '<li>' + escapeHtml(it._label) + '</li>'; }).join('');
        Swal.fire({
            icon: 'warning',
            title: (isEditMode ? 'Simpan perubahan' : 'Simpan') + ' penyesuaian ' + jenis + '?',
            html: 'Gudang: <strong>' + escapeHtml($('#gudang_id option:selected').text()) + '</strong>'
                + '<ul class="text-left small mt-2 mb-0">' + summary + '</ul>'
                + '<div class="small text-muted mt-2">' + (jenis === 'masuk' ? 'Penyesuaian masuk menunggu approval Admin sebelum stok bertambah.' : 'Stok langsung berkurang, kecuali nilai dokumen ' + THRESHOLD_LABEL + ' atau lebih (menunggu approval).') + '</div>',
            showCancelButton: true,
            confirmButtonText: 'Ya, simpan',
            cancelButtonText: 'Batal',
            reverseButtons: true
        }).then(function (result) {
            if (!result.value) return;

            var items = collected.items.map(function (it) { var copy = $.extend({}, it); delete copy._label; return copy; });
            var $btn = $('#btn-simpan-mutasi').prop('disabled', true);
            $.ajax({
                url: $('#form-mutasi-stok').attr('action'),
                type: 'POST',
                data: {
                    _token: "{{ csrf_token() }}",
                    gudang_id: $('#gudang_id').val(),
                    jenis_mutasi: jenis,
                    tanggal_mutasi: $('#tanggal_mutasi').val(),
                    edit_id: $('#edit_id').val() || null,
                    items: items
                },
                success: function (response) {
                    $('#modalMutasiStok').modal('hide');
                    table.draw();
                    if (response.status === 'pending') {
                        // Not a quick toast: the user must know the stok did not change yet
                        Swal.fire({ icon: 'info', title: 'Menunggu approval', text: response.message });
                    } else {
                        notifySuccess(response.message || 'Penyesuaian stok berhasil disimpan.');
                    }
                },
                error: function (xhr) {
                    notifyError(xhrMessage(xhr, 'Terjadi kesalahan saat menyimpan mutasi stok.'));
                },
                complete: function () {
                    $btn.prop('disabled', false);
                }
            });
        });
    });

    $('#btn-filter-pending').on('click', function () {
        $('#filter_status').val('pending');
        table.draw();
    });

    function rupiah(n) {
        return 'Rp ' + Number(n || 0).toLocaleString('id-ID', { maximumFractionDigits: 0 });
    }

    function openDetail(id) {
        $.get("{{ url('erm/mutasi-stok') }}/" + id, function (response) {
            var jenisBadge = response.jenis_mutasi === 'masuk'
                ? '<span class="badge badge-success">Masuk</span>'
                : '<span class="badge badge-danger">Keluar</span>';
            var statusClass = { pending: 'warning', done: 'primary', rejected: 'danger', cancelled: 'dark' }[response.status] || 'light';

            var itemsHtml = response.items.map(function (item, index) {
                var satuan = item.satuan ? ' ' + escapeHtml(item.satuan) : '';
                var batch = item.batch_baru
                    ? escapeHtml(item.batch_baru) + ' <span class="badge badge-info">baru, ED ' + escapeHtml(item.expiration_date || '-') + '</span>'
                    : escapeHtml(item.batch_label || '-');
                return '<tr>'
                    + '<td>' + (index + 1) + '</td>'
                    + '<td>' + escapeHtml(item.obat_nama) + '</td>'
                    + '<td>' + batch + '</td>'
                    + '<td class="text-right">' + escapeHtml(item.jumlah) + satuan + '</td>'
                    + '<td>' + (item.alasan ? '<span class="badge badge-light border">' + escapeHtml(item.alasan) + '</span>' : '-') + '</td>'
                    + '<td>' + escapeHtml(item.keterangan_saja || '-') + '</td>'
                    + '<td class="text-right">' + (item.nilai > 0 ? rupiah(item.nilai) : '-') + '</td>'
                    + '</tr>';
            }).join('');

            var approval = '';
            if (response.status === 'pending') {
                approval = '<div class="alert alert-warning small mb-3"><i class="fas fa-hourglass-half"></i> Menunggu approval Admin. <strong>Stok belum berubah</strong> sampai dokumen di-approve.'
                    + (response.is_own_pending ? ' Dokumen ini tidak bisa Anda approve sendiri.' : '') + '</div>';
            } else if (response.approved_by) {
                approval = '<div class="small text-muted mb-3">Disetujui oleh <strong>' + escapeHtml(response.approved_by) + '</strong> pada ' + escapeHtml(response.approved_at) + '</div>';
            } else if (response.status === 'done') {
                approval = '<div class="small text-muted mb-3">Langsung diposting (di bawah ambang approval).</div>';
            }
            if (response.rejected_by) {
                approval += '<div class="alert alert-danger small mb-3">Ditolak oleh <strong>' + escapeHtml(response.rejected_by) + '</strong> pada ' + escapeHtml(response.rejected_at)
                    + '<br>Alasan: ' + escapeHtml(response.rejected_reason || '-') + '</div>';
            }
            if (response.cancelled_by) {
                approval += '<div class="small text-muted mb-3">Dibatalkan oleh <strong>' + escapeHtml(response.cancelled_by) + '</strong> pada ' + escapeHtml(response.cancelled_at) + '</div>';
            }

            var html = ''
                + '<div class="row mb-3">'
                    + '<div class="col-md-3"><strong>Nomor:</strong><br>' + escapeHtml(response.nomor_mutasi) + '</div>'
                    + '<div class="col-md-3"><strong>Tanggal:</strong><br>' + escapeHtml(response.tanggal_mutasi_display || '-') + '</div>'
                    + '<div class="col-md-3"><strong>Gudang:</strong><br>' + escapeHtml(response.gudang || '-') + '</div>'
                    + '<div class="col-md-3"><strong>Jenis / Status:</strong><br>' + jenisBadge + ' <span class="badge badge-' + statusClass + '">' + escapeHtml(response.status_label) + '</span></div>'
                + '</div>'
                + '<div class="row mb-3">'
                    + '<div class="col-md-3"><strong>Dibuat oleh:</strong><br>' + escapeHtml(response.user || '-') + '</div>'
                    + '<div class="col-md-3"><strong>Tanggal input:</strong><br>' + escapeHtml(response.tanggal_input || '-') + '</div>'
                    + '<div class="col-md-3"><strong>Nilai dokumen:</strong><br>' + rupiah(response.total_nilai) + '</div>'
                    + (response.revised_from ? '<div class="col-md-3"><strong>Revisi dari:</strong><br>' + escapeHtml(response.revised_from) + '</div>' : '')
                + '</div>'
                + approval
                + '<div class="table-responsive"><table class="table table-bordered table-sm mb-0">'
                    + '<thead><tr><th>#</th><th>Obat</th><th>Batch</th><th class="text-right">Jumlah</th><th>Alasan</th><th>Keterangan</th><th class="text-right">Nilai</th></tr></thead>'
                    + '<tbody>' + itemsHtml + '</tbody>'
                + '</table></div>';

            var actionHtml = '<a href="' + response.print_url + '" target="_blank" class="btn btn-secondary btn-sm mr-2"><i class="fas fa-print"></i> Cetak</a>';
            if (response.can_approve) {
                actionHtml += '<button type="button" class="btn btn-success btn-sm mr-2 btn-approve" data-id="' + response.id + '" data-nomor="' + escapeHtml(response.nomor_mutasi) + '" data-nilai="' + response.total_nilai + '"><i class="fas fa-check"></i> Approve</button>';
                actionHtml += '<button type="button" class="btn btn-outline-danger btn-sm mr-2 btn-reject" data-id="' + response.id + '" data-nomor="' + escapeHtml(response.nomor_mutasi) + '"><i class="fas fa-times"></i> Tolak</button>';
            }
            if (response.can_edit) {
                actionHtml += '<button type="button" class="btn btn-warning btn-sm mr-2 btn-edit" data-id="' + response.id + '"><i class="fas fa-edit"></i> Ubah</button>';
            }
            if (response.can_cancel) {
                actionHtml += response.status === 'done'
                    ? '<button type="button" class="btn btn-danger btn-sm btn-cancel" data-id="' + response.id + '" data-status="done"><i class="fas fa-undo"></i> Batalkan &amp; balik stok</button>'
                    : '<button type="button" class="btn btn-outline-secondary btn-sm btn-cancel" data-id="' + response.id + '" data-status="pending"><i class="fas fa-ban"></i> Tarik dokumen</button>';
            }

            $('#detail-mutasi-stok-body').html(html);
            $('#detail-mutasi-stok-actions').html(actionHtml);
            $('#modalDetailMutasiStok').modal('show');
        });
    }

    $(document).on('click', '.btn-detail', function () {
        openDetail($(this).data('id'));
    });

    $(document).on('click', '.btn-approve', function () {
        var $btn = $(this);
        Swal.fire({
            icon: 'question',
            title: 'Approve ' + escapeHtml($btn.data('nomor')) + '?',
            html: 'Stok langsung diperbarui sesuai dokumen ini dan tercatat di kartu stok.<br>Nilai dokumen: <strong>' + rupiah($btn.data('nilai')) + '</strong>',
            showCancelButton: true,
            confirmButtonText: 'Ya, approve',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#28a745',
            reverseButtons: true
        }).then(function (result) {
            if (!result.value) return;
            $btn.prop('disabled', true);
            $.post("{{ url('erm/mutasi-stok') }}/" + $btn.data('id') + "/approve", { _token: "{{ csrf_token() }}" })
                .done(function (response) {
                    $('#modalDetailMutasiStok').modal('hide');
                    table.draw(false);
                    notifySuccess(response.message);
                })
                .fail(function (xhr) {
                    notifyError(xhrMessage(xhr, 'Gagal meng-approve dokumen.'));
                    $btn.prop('disabled', false);
                });
        });
    });

    $(document).on('click', '.btn-reject', function () {
        var $btn = $(this);
        Swal.fire({
            icon: 'warning',
            title: 'Tolak ' + escapeHtml($btn.data('nomor')) + '?',
            text: 'Stok tidak berubah. Tulis alasan penolakan untuk pembuat dokumen.',
            input: 'textarea',
            inputPlaceholder: 'Alasan penolakan...',
            inputValidator: function (value) {
                if (!value || !value.trim()) return 'Alasan penolakan wajib diisi.';
            },
            showCancelButton: true,
            confirmButtonText: 'Tolak dokumen',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#d33',
            reverseButtons: true
        }).then(function (result) {
            if (!result.value) return;
            $.post("{{ url('erm/mutasi-stok') }}/" + $btn.data('id') + "/reject", { _token: "{{ csrf_token() }}", reason: result.value.trim() })
                .done(function (response) {
                    $('#modalDetailMutasiStok').modal('hide');
                    table.draw(false);
                    notifySuccess(response.message);
                })
                .fail(function (xhr) {
                    notifyError(xhrMessage(xhr, 'Gagal menolak dokumen.'));
                });
        });
    });

    $(document).on('click', '.btn-cancel', function () {
        var id = $(this).data('id');
        var posted = $(this).data('status') === 'done';
        Swal.fire({
            icon: 'warning',
            title: posted ? 'Batalkan dokumen yang sudah diposting?' : 'Tarik dokumen ini?',
            html: posted
                ? 'Stok dan kartu stok dokumen ini akan <strong>dibalik</strong>.<br><span class="text-danger">Pembatalan tidak dapat diurungkan.</span> Buat dokumen baru bila perlu koreksi.'
                : 'Dokumen dibatalkan sebelum di-approve. Stok tidak berubah.',
            showCancelButton: true,
            confirmButtonText: posted ? 'Ya, batalkan' : 'Ya, tarik',
            cancelButtonText: 'Tidak',
            confirmButtonColor: '#d33',
            reverseButtons: true,
            focusCancel: true
        }).then(function (result) {
            if (!result.value) return;
            $.post("{{ url('erm/mutasi-stok') }}/" + id + "/cancel", { _token: "{{ csrf_token() }}" })
                .done(function (response) {
                    $('#modalDetailMutasiStok').modal('hide');
                    table.draw(false);
                    notifySuccess(response.message);
                })
                .fail(function (xhr) {
                    notifyError(xhrMessage(xhr, 'Gagal membatalkan dokumen.'));
                });
        });
    });

    // Only pending documents are edited; posted ones are cancelled and re-entered
    $(document).on('click', '.btn-edit', function () {
        var id = $(this).data('id');

        $.get("{{ url('erm/mutasi-stok') }}/" + id, function (response) {
            $('#modalDetailMutasiStok').modal('hide');
            resetFormState();
            isEditMode = true;
            $('#modalMutasiStokLabel').text('Ubah Penyesuaian Stok (' + response.nomor_mutasi + ')');
            $('#edit_id').val(response.id);
            $('#tanggal_mutasi').val(response.tanggal_mutasi || getCurrentDateValue());
            $('#tanggal_input_preview').val(response.tanggal_input || getCurrentTimestampLabel());
            $('#gudang_id').val(response.gudang_id).trigger('change.select2');
            $('#jenis_mutasi').val(response.jenis_mutasi).trigger('change.select2');
            updateInfoBox();
            response.items.forEach(function (item) { addItemRow(item); });
            $('#modalMutasiStok').modal('show');
        });
    });

    resetFormState();

    // Opened from Manajemen Stok: form pre-filled with gudang, jenis, obat and batch
    if (PREFILL) {
        $('#gudang_id').val(PREFILL.gudang_id).trigger('change.select2');
        $('#jenis_mutasi').val(PREFILL.jenis).trigger('change.select2');
        updateInfoBox();
        addItemRow({
            obat_id: PREFILL.obat_id,
            nama: PREFILL.obat_nama,
            satuan: PREFILL.satuan,
            batch: PREFILL.batch,
            jumlah: PREFILL.jumlah,
            alasan: PREFILL.alasan
        });
        $('#modalMutasiStok').modal('show');
        // Keep the URL clean so a refresh does not reopen the form
        if (window.history && window.history.replaceState) {
            window.history.replaceState({}, '', "{{ route('erm.mutasi-stok.index') }}");
        }
    }
});
</script>
@endpush
