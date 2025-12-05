@extends('layouts.erm.app')

@section('title', 'Lakukan Stok Opname')
@section('navbar')
    @include('layouts.erm.navbar')
@endsection

@section('content')
<!-- Modal Ubah Status -->
                    <div class="modal fade" id="changeStatusModal" tabindex="-1" role="dialog" aria-labelledby="changeStatusModalLabel" aria-hidden="true">
                      <div class="modal-dialog" role="document">
                        <div class="modal-content">
                          <form id="changeStatusForm" method="POST" action="{{ route('erm.stokopname.updateStatus', $stokOpname->id) }}">
                            @csrf
                            <div class="modal-header">
                              <h5 class="modal-title" id="changeStatusModalLabel">Ubah Status Stok Opname</h5>
                              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                              </button>
                            </div>
                            <div class="modal-body">
                              <div class="form-group">
                                <label for="status">Status Baru</label>
                                <select class="form-control" name="status" id="status" required>
                                  <option value="draft" {{ $stokOpname->status == 'draft' ? 'selected' : '' }}>Draft</option>
                                  <option value="proses" {{ $stokOpname->status == 'proses' ? 'selected' : '' }}>Proses</option>
                                  <option value="selesai" {{ $stokOpname->status == 'selesai' ? 'selected' : '' }}>Selesai</option>
                                </select>
                              </div>
                            </div>
                            <div class="modal-footer">
                              <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                              <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                            </div>
                          </form>
                        </div>
                      </div>
                    </div>

<!-- Modal History Temuan -->
<div class="modal fade" id="temuanHistoryModal" tabindex="-1" role="dialog" aria-labelledby="temuanHistoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="temuanHistoryModalLabel">History Temuan</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="temuanHistoryContent">
                    <div class="text-center">
                        <i class="fa fa-spinner fa-spin"></i> Loading...
                    </div>
                </div>

                <!-- Add Temuan Form (record-only) -->
                <div class="card mt-3 mb-2">
                        <div class="card-body">
                                <form id="temuanAddForm" class="form-inline">
                                        <input type="hidden" id="temuan_item_id" value="">
                                        <div class="form-group mr-2">
                                                <label class="sr-only" for="temuan_qty">Qty</label>
                                                <input type="number" step="0.0001" class="form-control form-control-sm" id="temuan_qty" placeholder="Qty" style="width:110px;">
                                        </div>
                                        <div class="form-group mr-2">
                                            <label class="sr-only" for="temuan_jenis">Jenis</label>
                                            <select id="temuan_jenis" class="form-control form-control-sm" style="width:120px;">
                                                <option value="kurang">Kurang</option>
                                                <option value="lebih">Lebih</option>
                                            </select>
                                        </div>
                                        <div class="form-group mr-2" style="flex:1;">
                                                <label class="sr-only" for="temuan_keterangan">Catatan</label>
                                                <input type="text" class="form-control form-control-sm" id="temuan_keterangan" placeholder="Catatan..." style="width:100%;">
                                        </div>
                                        <button type="button" id="temuanAddBtn" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i> Tambah</button>
                                </form>
                        </div>
                </div>

                <!-- Table list of temuan records in this opname item -->
                <div class="table-responsive">
                        <table class="table table-sm table-bordered" id="temuanRecordsTable">
                                <thead class="bg-light">
                                        <tr>
                                            <th>Tanggal</th>
                                            <th>Qty</th>
                                            <th>Jenis</th>
                                            <th>Catatan</th>
                                            <th>Oleh</th>
                                            <th>Action</th>
                                        </tr>
                                </thead>
                                <tbody>
                                        <!-- Filled dynamically -->
                                </tbody>
                        </table>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Lakukan Stok Opname</h4>
        <a href="{{ route('erm.stokopname.index') }}" class="btn btn-secondary mt-2"><i class="fa fa-arrow-left"></i> Kembali</a>
    </div>
    <div class="row">
        <div class="col-md-7">
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong>INFORMASI STOK OPNAME</strong>
                    <div class="d-inline-flex align-items-center" style="background: #17a2b8; padding: 0.12rem 0.7rem 0.12rem 0.7rem; border-radius: 4px;">
                        <span id="status-text" style="color: #fff; font-weight: 500; font-size: 0.92rem; letter-spacing: 0.2px; margin-right: 0.35rem;">{{ strtoupper($stokOpname->status) }}</span>
                        <button type="button" class="btn btn-link p-0 m-0" data-toggle="modal" data-target="#changeStatusModal" title="Ubah Status" style="color: #fff; font-size: 1rem; border-radius: 3px;">
                            <i class="fa fa-edit"></i>
                        </button>
                        
                    </div>
                    
                </div>
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col-5 font-weight-bold">Tanggal Opname</div>
                        <div class="col-7">
                            @php
                                $bulanIndo = [
                                    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
                                    7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                                ];
                                $tgl = \Carbon\Carbon::parse($stokOpname->tanggal_opname);
                                $tglText = $tgl->day . ' ' . ($bulanIndo[$tgl->month] ?? $tgl->month) . ' ' . $tgl->year;
                            @endphp
                            {{ $tglText }}
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-5 font-weight-bold">Gudang</div>
                        <div class="col-7">{{ $stokOpname->gudang->nama ?? '-' }}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-5 font-weight-bold">Periode</div>
                        <div class="col-7">
                            @php
                                $bulanIndo = [
                                    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
                                    7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                                ];
                                $periodeText = strtoupper(($bulanIndo[$stokOpname->periode_bulan] ?? $stokOpname->periode_bulan) . ' ' . $stokOpname->periode_tahun);
                            @endphp
                            <strong>{{ $periodeText }}</strong>
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-5 font-weight-bold">Catatan</div>
                        <div class="col-7">{{ $stokOpname->notes }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-5">
            <div class="card mb-3">
                <div class="card-header"><strong>AKSI</strong></div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2">
                        <button type="button" class="btn btn-info mr-2" id="generateItemsBtn" 
                            {{ $stokOpname->status === 'selesai' ? 'disabled' : '' }}>
                            <i class="fa fa-magic"></i> Generate Items (Per Gudang)
                        </button>
                        @php
                            $hasItems = $items->count() > 0;
                            $isCompleted = $stokOpname->status === 'selesai';
                            $updateStockEnabled = $hasItems && !$isCompleted;
                        @endphp
                        <button type="button" class="btn btn-success mr-2" id="updateStockBtn" 
                            {{ !$updateStockEnabled ? 'disabled' : '' }}>
                            <i class="fa fa-check"></i> Update Stock from Opname
                        </button>
                    </div>
                    {{-- <hr> --}}
                    {{-- <div class="d-flex align-items-center">
                        <a href="{{ route('erm.stokopname.downloadExcel', $stokOpname->id) }}" class="btn btn-warning mr-2"><i class="fa fa-download"></i> Download</a>
                        <form action="{{ route('erm.stokopname.uploadExcel', $stokOpname->id) }}" method="POST" enctype="multipart/form-data" role="form" class="d-flex align-items-center">
                            @csrf
                            <div class="input-group">
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" id="file" name="file" accept=".xlsx,.xls" required>
                                    <label class="custom-file-label" for="file">Pilih file...</label>
                                </div>
                                <div class="input-group-append">
                                    <button type="submit" class="btn btn-primary"><i class="fa fa-upload"></i> Upload Data</button>
                                    <button type="button" class="btn btn-success ml-2" id="saveStokFisikBtn" style="display: none;"><i class="fa fa-save"></i> Submit Stok (Legacy)</button>
                                </div>
                            </div>
                        </form>
                    </div> --}}
                    @if(session('success'))
                        <div class="alert alert-success mt-3">{{ session('success') }}</div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-danger mt-3">{{ session('error') }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
    <div class="mt-4">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="mb-0">Hasil Stok Opname</h5>
            <button id="syncTotalsBtn" class="btn btn-outline-primary btn-sm"><i class="fa fa-sync"></i> Sync Total Nilai Stok</button>
        </div>
        <table class="table table-bordered table-striped" id="stokOpnameItemsTable">
            <thead>
                <tr>
                    <th>Obat ID</th>
                    <th>Nama Obat</th>
                    <th>Batch</th>
                    <th>Satuan</th>
                    <th>Stok Sistem</th>
                    <th>Stok Fisik</th>
                    <th>Nilai Stok</th>
                    <th>Selisih</th>
                    <th>Total Temuan</th>
                    <th>Temuan</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
        <div class="row mt-3">
            <div class="col-md-6">
                <div class="alert alert-info" id="totalStokSistemBox">
                    <strong>Total Nilai Stok Sistem (HPP Jual x Stok Sistem):</strong><br>
                    Rp <span id="totalStokSistemText">{{ number_format($totalStokSistem, 0, ',', '.') }}</span>
                </div>
            </div>
            <div class="col-md-6">
                <div class="alert alert-success" id="totalStokFisikBox">
                    <strong>Total Nilai Stok Fisik (HPP Jual x Stok Fisik):</strong><br>
                    Rp <span id="totalStokFisikText">{{ number_format($totalStokFisik, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>
    </div>
@push('scripts')
<script>
$(function () {
    // Helper to format qty with 2 decimals
    function formatQty(q) {
        var n = parseFloat(q);
        if (isNaN(n)) return q;
        // If number is whole integer, show without decimals. Otherwise show up to 2 decimals.
        if (Math.abs(n - Math.round(n)) < 1e-9) {
            return String(Math.round(n));
        }
        return n.toFixed(2);
    }
    // Ensure nama obat column is constrained and text truncates
    $('#stokOpnameItemsTable').addClass('text-truncate');

    // Global flag: whether this stok opname is completed
    var isCompleted = '{{ $stokOpname->status }}' === 'selesai';

    // Check button states on page load
    checkButtonStates();
    
    function checkButtonStates() {
        var hasItems = {{ $items->count() > 0 ? 'true' : 'false' }};
        // Update button states based on current conditions
        if (isCompleted) {
            $('#generateItemsBtn').prop('disabled', true);
            $('#updateStockBtn').prop('disabled', true);
        } else if (hasItems) {
            $('#updateStockBtn').prop('disabled', false);
        }
    }
    $('#syncTotalsBtn').click(function() {
        var btn = $(this);
        btn.prop('disabled', true);
        btn.html('<i class="fa fa-sync fa-spin"></i> Syncing...');
        $.get("{{ route('erm.stokopname.syncTotals', $stokOpname->id) }}", function(res) {
            $('#totalStokSistemText').text(res.totalStokSistem.toLocaleString('id-ID'));
            $('#totalStokFisikText').text(res.totalStokFisik.toLocaleString('id-ID'));
        }).always(function() {
            btn.prop('disabled', false);
            btn.html('<i class="fa fa-sync"></i> Sync Total Nilai Stok');
        });
    });
    var table = $('#stokOpnameItemsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('erm.stokopname.itemsData', $stokOpname->id) }}",
        // Default order: Selisih column (index 7) ascending so biggest negative values appear first
        order: [[7, 'asc']],
        columnDefs: [
            { targets: 1, width: '220px', className: 'nama-obat-cell', render: function(data, type, row) { return '<div class="nama-obat-cell">'+data+'</div>'; } },
            { targets: 8, width: '140px' }, // Total Temuan column
            { targets: 9, width: '280px' }, // Temuan column - make it wider
        ],
        columns: [
            {data: 'obat_id', name: 'obat_id'},
            {data: 'nama_obat', name: 'nama_obat'},
            {data: 'batch_name', name: 'batch_name', defaultContent: '-'},
            {data: 'satuan', name: 'satuan', defaultContent: '-'},
            {data: 'stok_sistem', name: 'stok_sistem'},
                {
                    data: 'stok_fisik',
                    name: 'stok_fisik',
                    render: function(data, type, row) {
                        var disabledAttr = isCompleted ? 'disabled' : '';
                        return `<input type="number" class="form-control form-control-sm stok-fisik-input" data-id="${row.id}" value="${data}" style="width:90px;" ${disabledAttr}>`;
                    }
                },
                {
                    data: null,
                    name: 'nilai_stok',
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row) {
                        var hpp = parseFloat(row.hpp_jual) || 0;
                        var stokFisik = parseFloat(row.stok_fisik) || 0;
                        var nilai = hpp * stokFisik;
                        return 'Rp ' + nilai.toLocaleString('id-ID');
                    }
                },
            {
                data: 'selisih',
                name: 'selisih',
                render: function(data, type, row) {
                    var txt = formatQty(data);
                    if (parseFloat(data) != 0) {
                        return txt + ' <i class="fa fa-exclamation-triangle text-warning blink-warning" title="Ada selisih"></i>';
                    } else {
                        return txt + ' <i class="fa fa-check text-success" title="Sesuai"></i>';
                    }
                }
            },
            {
                data: 'total_temuan',
                name: 'total_temuan',
                orderable: false,
                searchable: false,
                render: function(data, type, row) {
                    if (data === null || data === undefined) return '0';
                    return formatQty(data);
                }
            },
            {
                data: null,
                name: 'temuan',
                orderable: false,
                searchable: false,
                render: function(data, type, row) {
                    return `
                        <button type="button" class="btn btn-info btn-sm lihat-temuan-btn" data-id="${row.id}" data-toggle="modal" data-target="#temuanHistoryModal" title="Kelola Temuan">
                            <i class="fa fa-list"></i> Kelola
                        </button>
                    `;
                }
            },
            // Removed duplicate List Temuan column; 'Kelola' button is shown in Temuan column
        ]
    });

        // Inline update stok fisik
        $('#stokOpnameItemsTable').on('change', '.stok-fisik-input', function() {
            var itemId = $(this).data('id');
            var stokFisik = $(this).val();
            var input = $(this);
            input.prop('disabled', true);
            $.ajax({
                url: '/erm/stokopname-item/' + itemId + '/update-stok-fisik',
                method: 'POST',
                data: {
                    stok_fisik: stokFisik,
                    _token: '{{ csrf_token() }}'
                },
                success: function(res) {
                    input.removeClass('is-invalid').addClass('is-valid');
                    setTimeout(() => input.removeClass('is-valid'), 1000);
                    // Get row index
                    var rowIdx = table.row(input.closest('tr')).index();
                    // Update selisih cell (column indexes shifted after adding Satuan)
                    // New Columns: 0 Obat ID,1 Nama,2 Batch,3 Satuan,4 Stok Sistem,5 Stok Fisik,6 Nilai Stok,7 Selisih,8 Total Temuan,9 Temuan
                    var selisihCell = $(table.cell(rowIdx, 7).node()); // Index 7 untuk kolom Selisih
                    var icon = res.selisih != 0 ? '<i class="fa fa-exclamation-triangle text-warning blink-warning" title="Ada selisih"></i>' : '<i class="fa fa-check text-success" title="Sesuai"></i>';
                    selisihCell.html(res.selisih + ' ' + icon);

                    // Update Nilai Stok cell using hpp_jual from row data
                    var rowData = table.row(rowIdx).data();
                    var hpp = parseFloat(rowData.hpp_jual) || 0;
                    var nilai = hpp * (parseFloat(res.stok_fisik) || 0);
                    var nilaiCell = $(table.cell(rowIdx, 6).node()); // Index 6 untuk Nilai Stok
                    nilaiCell.html('Rp ' + nilai.toLocaleString('id-ID'));
                    
                    // Also update table's internal data for stok_fisik and selisih so future redraws have correct values
                    rowData.stok_fisik = res.stok_fisik;
                    rowData.selisih = res.selisih;
                    table.row(rowIdx).data(rowData);
                },
                error: function() {
                    input.addClass('is-invalid');
                },
                complete: function() {
                    input.prop('disabled', false);
                }
            });
        });
    
    // Inline temuan inputs removed: temuan are managed via modal only
    
    // Handle lihat temuan history button
    $('#stokOpnameItemsTable').on('click', '.lihat-temuan-btn', function() {
        var itemId = $(this).data('id');
        // Reset modal content to loading state
        $('#temuanHistoryContent').html('<div class="text-center"><i class="fa fa-spinner fa-spin"></i> Loading...</div>');
        $('#temuan_item_id').val(itemId);
        $('#temuan_qty').val('');
        $('#temuan_keterangan').val('');
        $('#temuanRecordsTable tbody').empty();

        // Fetch temuan history + record-only temuan
        $.get('/erm/stokopname-item/' + itemId + '/temuan-history')
            .done(function(res) {
                if (res.success) {
                    var content = `
                        <div class="mb-3">
                            <h6><strong>Item Information</strong></h6>
                            <p><strong>Obat:</strong> ${res.item.obat_nama}<br>
                            <strong>Batch:</strong> ${res.item.batch || '-'}<br>
                            <strong>Gudang:</strong> ${res.item.gudang}</p>
                        </div>
                        <hr>
                    `;

                    $('#temuanHistoryContent').html(content);

                    // Populate kartu stok-based history (if any) in the modal (append under header)
                    if (res.history && res.history.length > 0) {
                        var h = '<h6><strong>History (kartu stok)</strong></h6>' +
                            '<div class="table-responsive"><table class="table table-sm table-bordered"><thead class="bg-light"><tr><th>Tanggal & Waktu</th><th>Qty Temuan</th><th>Catatan</th><th>Stok Setelah</th></tr></thead><tbody>';
                        res.history.forEach(function(record) {
                            h += `<tr><td>${record.tanggal}</td><td class="text-center">${formatQty(record.qty)}</td><td>${record.keterangan || '-'}</td><td class="text-center">${record.stok_setelah}</td></tr>`;
                        });
                        h += '</tbody></table></div>';
                        $('#temuanHistoryContent').append(h);
                    }

                    // Populate simple temuan records into the temuanRecordsTable
                    if (res.temuan_records && res.temuan_records.length > 0) {
                        res.temuan_records.forEach(function(r) {
                            var jenisLabel = r.jenis === 'lebih' ? 'Lebih' : (r.jenis === 'kurang' ? 'Kurang' : '-');
                            var actionBtn = '';
                            if (r.process_status && parseInt(r.process_status) === 1) {
                                // Already processed: show disabled 'Diproses' and do not allow deletion
                                actionBtn = `<button class="btn btn-sm btn-secondary" disabled>Diproses</button>`;
                            } else {
                                // Not processed: allow process and delete actions
                                actionBtn = `<button class="btn btn-sm btn-primary proses-temuan-btn mr-1" data-id="${r.id}">Proses Stok</button>` +
                                            `<button class="btn btn-sm btn-danger delete-temuan-btn" data-id="${r.id}">Hapus</button>`;
                            }
                            var tr = `<tr data-id="${r.id}"><td>${r.tanggal}</td><td class="text-center">${formatQty(r.qty)}</td><td class="text-center">${jenisLabel}</td><td>${r.keterangan || '-'}</td><td>${r.created_by_name || '-'}</td><td class="text-center">${actionBtn}</td></tr>`;
                            $('#temuanRecordsTable tbody').append(tr);
                            // bind handlers (if buttons exist)
                            var row = $('#temuanRecordsTable').find(`tr[data-id="${r.id}"]`);
                            row.find('.proses-temuan-btn').on('click', prosesTemuanHandler);
                            row.find('.delete-temuan-btn').on('click', deleteTemuanHandler);
                        });
                    } else {
                        $('#temuanRecordsTable tbody').html('<tr><td colspan="6" class="text-center">Belum ada temuan tercatat.</td></tr>');
                    }

                } else {
                    $('#temuanHistoryContent').html(`
                        <div class="alert alert-danger">
                            <i class="fa fa-exclamation-triangle"></i> ${res.message || 'Gagal memuat data history temuan'}
                        </div>
                    `);
                    $('#temuanRecordsTable tbody').html('<tr><td colspan="6" class="text-center">Gagal memuat data.</td></tr>');
                }
            })
            .fail(function() {
                $('#temuanHistoryContent').html(`
                    <div class="alert alert-danger">
                        <i class="fa fa-exclamation-triangle"></i> Gagal memuat data history temuan
                    </div>
                `);
                $('#temuanRecordsTable tbody').html('<tr><td colspan="6" class="text-center">Gagal memuat data.</td></tr>');
            });
    });

    // Handle adding a record-only temuan from modal
    $('#temuanAddBtn').on('click', function() {
        var itemId = $('#temuan_item_id').val();
        var qty = $('#temuan_qty').val();
        var keterangan = $('#temuan_keterangan').val();

        if (!qty || parseFloat(qty) <= 0) {
            Swal.fire({ icon: 'warning', title: 'Masukkan qty temuan yang valid', timer: 1500, showConfirmButton: false });
            return;
        }

        var btn = $(this);
        btn.prop('disabled', true);
        $.ajax({
            url: '/erm/stokopname-item/' + itemId + '/add-temuan-record',
            method: 'POST',
            data: {
                    qty: qty,
                    jenis: $('#temuan_jenis').val(),
                    keterangan: keterangan,
                    _token: '{{ csrf_token() }}'
                },
            success: function(res) {
                if (res.success) {
                    // append new record to table
                    var r = res.record;
                    var jenisLabel = r.jenis === 'lebih' ? 'Lebih' : (r.jenis === 'kurang' ? 'Kurang' : '-');
                    var actionBtn = '';
                    if (r.process_status && parseInt(r.process_status) === 1) {
                        actionBtn = `<button class="btn btn-sm btn-secondary" disabled>Diproses</button>`;
                    } else {
                        actionBtn = `<button class="btn btn-sm btn-primary proses-temuan-btn mr-1" data-id="${r.id}">Proses Stok</button>` +
                                    `<button class="btn btn-sm btn-danger delete-temuan-btn" data-id="${r.id}">Hapus</button>`;
                    }
                    var tr = `<tr data-id="${r.id}"><td>${r.tanggal}</td><td class="text-center">${formatQty(r.qty)}</td><td class="text-center">${jenisLabel}</td><td>${r.keterangan || '-'}</td><td>${r.created_by_name || '-'}</td><td class="text-center">${actionBtn}</td></tr>`;
                    // if table previously had 'no data' row, remove it
                    var first = $('#temuanRecordsTable tbody tr:first');
                    if (first.length && first.find('td').length === 1) {
                        $('#temuanRecordsTable tbody').empty();
                    }
                    $('#temuanRecordsTable tbody').prepend(tr);
                    // attach button handlers for the newly created row (if present)
                    var newRow = $('#temuanRecordsTable').find(`tr[data-id="${r.id}"]`);
                    newRow.find('.proses-temuan-btn').on('click', prosesTemuanHandler);
                    newRow.find('.delete-temuan-btn').on('click', deleteTemuanHandler);
                    // reload main DataTable so Total Temuan updates immediately
                    if (typeof table !== 'undefined') table.ajax.reload(null, false);
                    $('#temuan_qty').val('');
                    $('#temuan_keterangan').val('');
                    Swal.fire({ icon: 'success', title: res.message, timer: 1200, showConfirmButton: false });
                } else {
                    Swal.fire({ icon: 'error', title: res.message || 'Gagal menyimpan', timer: 1500, showConfirmButton: false });
                }
            },
            error: function(xhr) {
                var msg = 'Gagal menyimpan temuan';
                if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                Swal.fire({ icon: 'error', title: msg, timer: 1500, showConfirmButton: false });
            },
            complete: function() {
                btn.prop('disabled', false);
            }
        });
    });

    // Handler to process temuan into actual stok
    function prosesTemuanHandler(e) {
        var btn = $(this);
        var temuanId = btn.data('id');
        btn.prop('disabled', true).text('Processing...');
        $.ajax({
            url: '/erm/stokopname-temuan/' + temuanId + '/process',
            method: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function(res) {
                if (res.success) {
                    Swal.fire({ icon: 'success', title: res.message || 'Berhasil diproses', timer: 1200, showConfirmButton: false });
                    btn.removeClass('btn-primary').addClass('btn-secondary').text('Diproses').prop('disabled', true);
                    // refresh main table totals so Total Temuan updates
                    if (typeof table !== 'undefined') table.ajax.reload();
                } else {
                    Swal.fire({ icon: 'error', title: res.message || 'Gagal memproses', timer: 1500, showConfirmButton: false });
                    btn.prop('disabled', false).text('Proses Stok');
                }
            },
            error: function(xhr) {
                var msg = 'Gagal memproses temuan';
                if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                Swal.fire({ icon: 'error', title: msg, timer: 1500, showConfirmButton: false });
                btn.prop('disabled', false).text('Proses Stok');
            }
        });
    }

    function deleteTemuanHandler(e) {
        var btn = $(this);
        var temuanId = btn.data('id');
        Swal.fire({
            title: 'Hapus temuan?',
            text: 'Data temuan akan dihapus dan tidak bisa dikembalikan.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Hapus',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (!result.value) return;
            btn.prop('disabled', true).text('Menghapus...');
            $.ajax({
                url: '/erm/stokopname-temuan/' + temuanId + '/delete',
                method: 'POST',
                data: { _token: '{{ csrf_token() }}' },
                success: function(res) {
                    if (res.success) {
                        Swal.fire({ icon: 'success', title: res.message || 'Dihapus', timer: 1000, showConfirmButton: false });
                        // remove row from table
                        $('#temuanRecordsTable').find(`tr[data-id="${temuanId}"]`).remove();
                        if (typeof table !== 'undefined') table.ajax.reload();
                    } else {
                        Swal.fire({ icon: 'error', title: res.message || 'Gagal menghapus', timer: 1500, showConfirmButton: false });
                        btn.prop('disabled', false).text('Hapus');
                    }
                },
                error: function(xhr) {
                    var msg = 'Gagal menghapus temuan';
                    if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                    Swal.fire({ icon: 'error', title: msg, timer: 1500, showConfirmButton: false });
                    btn.prop('disabled', false).text('Hapus');
                }
            });
        });
    }

    // Show selected file name in upload
    $('#file').on('change', function() {
        var fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').html(fileName);
    });

    // Save stok fisik to stok obat
    $('#saveStokFisikBtn').click(function() {
        Swal.fire({
            title: 'Yakin ingin mengganti stok obat sesuai stok fisik hasil opname?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Simpan!',
            cancelButtonText: 'Batal',
        }).then((result) => {
            if (result.value) {
                var btn = $('#saveStokFisikBtn');
                btn.prop('disabled', true);
                Swal.fire({
                    title: 'Menyimpan stok fisik ke stok obat...',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });
                $.post("{{ route('erm.stokopname.saveStokFisik', $stokOpname->id) }}", {
                    _token: '{{ csrf_token() }}'
                })
                .done(function(res) {
                    Swal.fire({
                        icon: 'success',
                        title: res.message || 'Stok obat berhasil diperbarui!',
                        timer: 1800,
                        showConfirmButton: false
                    });
                    table.ajax.reload();
                })
                .fail(function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal menyimpan stok fisik ke stok obat!',
                        timer: 1800,
                        showConfirmButton: false
                    });
                })
                .always(function() {
                    btn.prop('disabled', false);
                });
            }
        });
    });

    // AJAX for change status
    $('#changeStatusForm').submit(function(e) {
        e.preventDefault();
        var form = $(this);
        var url = form.attr('action');
        var data = form.serialize();
        var btn = form.find('button[type="submit"]');
        btn.prop('disabled', true);
        Swal.fire({
            title: 'Menyimpan perubahan status...',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });
        $.post(url, data)
            .done(function(res) {
                $('#changeStatusModal').modal('hide');
                if(res.status) {
                    $('#status-text').text(res.status.toUpperCase());
                }
                Swal.fire({
                    icon: 'success',
                    title: 'Status berhasil diubah!',
                    timer: 1500,
                    showConfirmButton: false
                });
            })
            .fail(function(xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal mengubah status!',
                    timer: 1500,
                    showConfirmButton: false
                });
            })
            .always(function() {
                btn.prop('disabled', false);
            });
    });

    // ========== MULTI-GUDANG STOCK OPNAME HANDLERS ==========
    
    // Generate stock opname items from current stock in gudang
    $('#generateItemsBtn').click(function() {
        Swal.fire({
            title: 'Generate Items untuk Stok Opname?',
            text: 'Ini akan mengambil semua stok aktif dari gudang {{ $stokOpname->gudang->nama ?? "ini" }} dan membuat items untuk stok opname.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Generate!',
            cancelButtonText: 'Batal',
        }).then((result) => {
            if (result.value) {
                var btn = $('#generateItemsBtn');
                btn.prop('disabled', true);
                
                Swal.fire({
                    title: 'Generating items...',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });
                
                $.post("{{ route('erm.stokopname.generateItems', $stokOpname->id) }}", {
                    _token: '{{ csrf_token() }}'
                })
                .done(function(res) {
                    if (res.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: res.message,
                            timer: 2000,
                            showConfirmButton: false
                        });
                        
                        // Reload table and enable update stock button (only if not completed)
                        table.ajax.reload();
                        var isCompleted = '{{ $stokOpname->status }}' === 'selesai';
                        if (!isCompleted) {
                            $('#updateStockBtn').prop('disabled', false);
                        }
                        $('#syncTotalsBtn').click(); // Auto sync totals
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: res.message
                        });
                    }
                })
                .fail(function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'Failed to generate items: ' + (xhr.responseJSON?.message || 'Unknown error')
                    });
                })
                .always(function() {
                    btn.prop('disabled', false);
                });
            }
        });
    });
    
    // Update stock based on opname results using StokService
    $('#updateStockBtn').click(function() {
        Swal.fire({
            title: 'Update Stock dari Hasil Opname?',
            html: `
                <p>Ini akan mengupdate stok fisik di gudang berdasarkan hasil stok opname menggunakan StokService:</p>
                <ul style="text-align: left; margin: 10px 20px;">
                    <li><strong>Surplus</strong> (stok fisik > sistem): Akan menambah stok</li>
                    <li><strong>Shortage</strong> (stok fisik < sistem): Akan mengurangi stok</li>
                    <li>Status akan diubah menjadi <strong>"selesai"</strong></li>
                </ul>
                <p><strong>Pastikan semua data stok fisik sudah benar!</strong></p>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Update Stock!',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#28a745',
        }).then((result) => {
            if (result.value) {
                var btn = $('#updateStockBtn');
                btn.prop('disabled', true);
                
                Swal.fire({
                    title: 'Updating stock...',
                    text: 'Sedang mengupdate stok berdasarkan hasil opname...',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });
                
                $.post("{{ route('erm.stokopname.updateStockFromOpname', $stokOpname->id) }}", {
                    _token: '{{ csrf_token() }}'
                })
                .done(function(res) {
                    if (res.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Stock Updated Successfully!',
                            text: res.message,
                            timer: 3000,
                            showConfirmButton: false
                        });
                        
                        // Update status display and disable buttons
                        $('#status-text').text('SELESAI');
                        $('#updateStockBtn').prop('disabled', true);
                        $('#generateItemsBtn').prop('disabled', true);
                        
                        // Reload table
                        table.ajax.reload();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: res.message
                        });
                    }
                })
                .fail(function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'Failed to update stock: ' + (xhr.responseJSON?.message || 'Unknown error')
                    });
                })
                .always(function() {
                    btn.prop('disabled', false);
                });
            }
        });
    });
});
</script>
@endpush
<style>
@keyframes blink {
  0%, 100% { opacity: 1; }
  50% { opacity: 0; }
}
.blink-warning {
  animation: blink 1s linear infinite;
}
/* Truncate long nama obat in table cells */
/* Nama Obat: allow up to 2 lines then ellipsis */
.nama-obat-cell {
    display: -webkit-box;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2; /* show up to 2 lines */
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: normal;
    max-width: 220px; /* column width hint; DataTables columnDefs sets the column width */
}
</style>
</div>
@endsection
