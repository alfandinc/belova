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

            <!-- Modal Tambah Item -->
            <div class="modal fade" id="addItemModal" tabindex="-1" role="dialog" aria-labelledby="addItemModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <form id="addItemForm">
                            <div class="modal-header">
                                <h5 class="modal-title" id="addItemModalLabel">Tambah Item ke Stok Opname</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                            <div class="form-group">
                                <label for="add_obat_id">Pilih Obat</label>
                                <select id="add_obat_id" name="obat_id" class="form-control">
                                    <option value="">-- Muat daftar obat... --</option>
                                </select>
                                <small class="form-text text-muted">Menambahkan item akan membuat baris teragregasi per obat menggunakan stok sistem saat ini untuk gudang ini.</small>
                            </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-primary" id="saveAddItemBtn">Tambah Item</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

<div class="container-fluid px-2">
    <!-- Full-page overlay to block UI during long-running ops -->
    <div id="pageOverlay" style="display:none;">
        <div class="overlay-spinner"><i class="fa fa-spinner fa-spin fa-3x"></i></div>
    </div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        
    </div>
    <div class="row">
        <div class="col-md-9">
            <div class="card mb-1">
                @php
                    $bulanIndo = [
                        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
                        7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                    ];
                    $periodeText = strtoupper(($bulanIndo[$stokOpname->periode_bulan] ?? $stokOpname->periode_bulan) . ' ' . $stokOpname->periode_tahun);
                    $gudangName = strtoupper($stokOpname->gudang->nama ?? '-');
                    $status = $stokOpname->status ?? '';
                    $headerClass = 'bg-primary';
                    $textClass = 'text-white';
                    if ($status === 'selesai') {
                        $headerClass = 'bg-success';
                        $textClass = 'text-white';
                    } elseif ($status === 'proses') {
                        $headerClass = 'bg-warning';
                        $textClass = 'text-dark';
                    } elseif ($status === 'draft') {
                        $headerClass = 'bg-primary';
                        $textClass = 'text-white';
                    }
                @endphp
                <div id="stokOpnameHeader" class="card-header d-flex justify-content-between align-items-center {{ $headerClass }} {{ $textClass }}">
                    <strong>{{ $gudangName }} - {{ $periodeText }}</strong>
                    <span id="status-text" class="font-weight-bold" style="opacity:0.95">{{ strtoupper($status) }}</span>
                </div>
                <div class="card-body">
                    
                    @php
                        $totalItems = $items->count();
                        $itemsWithSelisih = $items->filter(function($it){ return (float)($it->selisih ?? 0) != 0; })->count();
                    @endphp

                    <div class="row mt-1 stat-row">
                        <div class="col stat-col">
                            <div class="stat-box stat-primary" id="totalStokSistemBox">
                                <div class="stat-label">Nilai Stok Sistem</div>
                                <div class="stat-value">Rp <span id="totalStokSistemText">{{ number_format($totalStokSistem, 0, ',', '.') }}</span></div>
                            </div>
                        </div>
                        <div class="col stat-col">
                            <div class="stat-box stat-success" id="totalStokFisikBox">
                                <div class="stat-label">Nilai Stok Fisik</div>
                                <div class="stat-value">Rp <span id="totalStokFisikText">{{ number_format($totalStokFisik, 0, ',', '.') }}</span></div>
                            </div>
                        </div>
                        <div class="col stat-col">
                            <div class="stat-box stat-warning" id="selisihNilaiBox">
                                <div class="stat-label">Selisih Nilai</div>
                                @php $selisihVal = ($totalStokSistem - $totalStokFisik); @endphp
                                <div class="stat-value">Rp <span id="selisihNilaiText">{{ number_format($selisihVal, 0, ',', '.') }}</span></div>
                            </div>
                        </div>
                        <div class="col stat-col small-stat">
                            <div class="stat-box stat-neutral" id="totalItemsBox">
                                <div class="stat-label">Items</div>
                                <div class="stat-value"><span id="totalItemsText">{{ $totalItems }}</span></div>
                            </div>
                        </div>
                        <div class="col stat-col small-stat-wide">
                            <div class="stat-box stat-danger" id="itemsWithSelisihBox">
                                <div class="stat-label">Items Selisih</div>
                                <div class="stat-value"><span id="itemsWithSelisihText">{{ $itemsWithSelisih }}</span></div>
                            </div>
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card mb-1">
                <div class="card-header"><strong>AKSI</strong></div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2">
                        @if($items->count() == 0)
                        <button type="button" class="btn btn-info mr-2" id="generateItemsBtn" 
                            {{ $stokOpname->status === 'selesai' ? 'disabled' : '' }}>
                            <i class="fa fa-magic"></i> Generate Items
                        </button>
                        @endif
                        @if($stokOpname->status === 'proses')
                        <button type="button" class="btn btn-secondary mr-2" id="addItemBtn">
                            <i class="fa fa-plus"></i> Tambah Item
                        </button>
                        @endif
                        @php
                            $hasItems = $items->count() > 0;
                            $isCompleted = $stokOpname->status === 'selesai';
                            $updateStockEnabled = $hasItems && !$isCompleted;
                        @endphp
                        @if($stokOpname->status !== 'selesai')
                        <button type="button" class="btn btn-success mr-2" id="updateStockBtn" 
                            {{ !$updateStockEnabled ? 'disabled' : '' }}
                            @if($stokOpname->status === 'draft') style="display:none;" @endif>
                            <i class="fa fa-check"></i> Submit Stok
                        </button>
                        @endif
                        <a href="{{ route('erm.stokopname.index') }}" class="btn btn-danger ml-auto"><i class="fa fa-arrow-left"></i> Kembali</a>
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
        <!-- Edit Obat Modal -->
        <div class="modal fade" id="editObatModal" tabindex="-1" role="dialog" aria-labelledby="editObatModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editObatModalLabel">Edit Obat</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="editObatForm">
                            <input type="hidden" id="edit_obat_id" name="id" value="">
                            <div class="form-group">
                                <label>Nama Obat</label>
                                <input type="text" class="form-control form-control-sm" id="edit_obat_nama" name="nama" required>
                            </div>
                            <div class="form-group">
                                <label>Satuan</label>
                                <select id="edit_obat_satuan" name="satuan" class="form-control form-control-sm">
                                    <option value="">-- Pilih Satuan --</option>
                                    <option value="pcs">pcs</option>
                                    <option value="strip">strip</option>
                                    <option value="tablet">tablet</option>
                                    <option value="kapsul">kapsul</option>
                                    <option value="mL">mL</option>
                                    <option value="g">g</option>
                                    <option value="mg">mg</option>
                                    <option value="botol">botol</option>
                                    <option value="tube">tube</option>
                                    <option value="sachet">sachet</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Kategori</label>
                                <select id="edit_obat_kategori" name="kategori" class="form-control form-control-sm">
                                    <option value="">-- Pilih Kategori --</option>
                                    @if(!empty($kategoriList))
                                        @foreach($kategoriList as $kat)
                                            <option value="{{ e($kat) }}">{{ e($kat) }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Metode Bayar</label>
                                <select id="edit_obat_metode" name="metode_bayar_id" class="form-control form-control-sm">
                                    <option value="">-- Pilih Metode --</option>
                                    @if(!empty($metodeList))
                                        @foreach($metodeList as $m)
                                            <option value="{{ $m->id }}">{{ $m->nama }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="button" id="saveEditObatBtn" class="btn btn-primary">Simpan</button>
                    </div>
                </div>
            </div>
        </div>
</div>
    <div class="container-fluid px-2">
        <div class="card mt-1">
        <div class="card-header"><strong>Hasil Stok Opname</strong></div>
        <div class="card-body">
            <div class="d-flex justify-content-end mb-2">
                <div class="form-inline">
                    <label class="mr-2" for="filterSelisih" style="font-size:0.85rem; color:#6c757d; text-transform:none;">Filter:</label>
                    <select id="filterSelisih" class="form-control form-control-sm mr-2">
                        <option value="">All</option>
                        <option value="with">With Selisih</option>
                        <option value="without">No Selisih</option>
                    </select>

                    <label class="mr-2 ml-2" for="filterKategori" style="font-size:0.85rem; color:#6c757d; text-transform:none;">Kategori:</label>
                    <select id="filterKategori" class="form-control form-control-sm mr-2">
                        <option value="">All</option>
                        @if(!empty($kategoriList))
                            @foreach($kategoriList as $kat)
                                <option value="{{ e($kat) }}">{{ e($kat) }}</option>
                            @endforeach
                        @endif
                    </select>

                    <label class="mr-2 ml-2" for="filterMetode" style="font-size:0.85rem; color:#6c757d; text-transform:none;">Metode:</label>
                    <select id="filterMetode" class="form-control form-control-sm mr-2">
                        <option value="">All</option>
                        @if(!empty($metodeList))
                            @foreach($metodeList as $m)
                                <option value="{{ $m->id }}">{{ $m->nama }}</option>
                            @endforeach
                        @endif
                    </select>

                    <label class="mr-2 ml-2" for="filterExpYear" style="font-size:0.85rem; color:#6c757d; text-transform:none;">Exp Year:</label>
                    <select id="filterExpYear" class="form-control form-control-sm mr-2">
                        <option value="">All</option>
                        @if(!empty($expYears))
                            @foreach($expYears as $y)
                                <option value="{{ $y }}">{{ $y }}</option>
                            @endforeach
                        @endif
                    </select>

                    <label class="mr-2 ml-2" for="filterStokFisik" style="font-size:0.85rem; color:#6c757d; text-transform:none;">Stok Fisik:</label>
                    <select id="filterStokFisik" class="form-control form-control-sm mr-2">
                        <option value="">All</option>
                        <option value="zero">Not Opnamed (0)</option>
                        <option value="nonzero">Opnamed (&gt;0)</option>
                    </select>

                    {{-- Sync button commented out --}}
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="stokOpnameItemsTable">
            <thead>
                        <tr>
                            <th style="width:6%">Obat ID</th>
                            <th style="width:28%">Nama Obat</th>
                            <th style="width:6%">Satuan</th>
                            <th style="width:10%">Stok Sistem</th>
                            <th style="width:10%">Stok Fisik</th>
                            <th style="width:8%">Total Temuan</th>
                            <th style="width:10%">Selisih</th>
                            <th style="width:6%">Exp Date</th>
                            <th style="width:7%">Nilai Stok</th>
                            <th style="width:9%">Aksi</th>
                        </tr>
            </thead>
            <tbody></tbody>
                </table>
            </div>
        <!-- Batch List Modal -->
        <div class="modal fade" id="batchListModal" tabindex="-1" role="dialog" aria-labelledby="batchListModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="batchListModalLabel">Batch List</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div id="batchListLoading" class="text-center my-3"><i class="fa fa-spinner fa-spin"></i> Loading...</div>
                        <div id="batchListContent" style="display:none;">
                            <p><strong>Obat:</strong> <span id="batchObatName"></span></p>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered" id="batchListTable">
                                    <thead class="bg-light"><tr><th>Batch</th><th>Stok</th><th>Expiration Date</th></tr></thead>
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
    // Convert string to deterministic HSL color
    function hashCode(str) {
        var hash = 0;
        if (!str) return hash;
        for (var i = 0; i < str.length; i++) {
            hash = str.charCodeAt(i) + ((hash << 5) - hash);
            hash = hash & hash;
        }
        return hash;
    }
    function colorFromString(str, opts) {
        opts = opts || {};
        var h = Math.abs(hashCode(String(str))) % 360;
        var s = opts.saturation || 60;
        var l = (typeof opts.lightness !== 'undefined') ? opts.lightness : 55;
        return { css: 'hsl(' + h + ',' + s + '%,' + l + '%)', h:h, s:s, l:l };
    }
    function readableTextColorFromLightness(l) {
        // lightness in percent
        return l > 60 ? '#212529' : '#ffffff';
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
    function syncTotals() {
        var btn = $("#syncTotalsBtn");
        if (btn.length) {
            btn.prop('disabled', true);
            btn.html('<i class="fa fa-sync fa-spin"></i> Syncing...');
        }
        $.get("{{ route('erm.stokopname.syncTotals', $stokOpname->id) }}", function(res) {
            $('#totalStokSistemText').text(res.totalStokSistem.toLocaleString('id-ID'));
            $('#totalStokFisikText').text(res.totalStokFisik.toLocaleString('id-ID'));
            var sel = (parseFloat(res.totalStokSistem) || 0) - (parseFloat(res.totalStokFisik) || 0);
            $('#selisihNilaiText').text(sel.toLocaleString('id-ID'));
        }).always(function() {
            if (btn.length) {
                btn.prop('disabled', false);
                btn.html('<i class="fa fa-sync"></i> Sync Total Nilai Stok');
            }
        });
    }
    var table = $('#stokOpnameItemsTable').DataTable({
        processing: true,
        serverSide: true,
        autoWidth: false,
        responsive: false,
            ajax: {
            url: "{{ route('erm.stokopname.itemsData', $stokOpname->id) }}",
            data: function(d) {
                d.filter_selisih = $('#filterSelisih').val();
                d.filter_kategori = $('#filterKategori').val();
                d.filter_metode = $('#filterMetode').val();
                d.filter_exp_year = $('#filterExpYear').val();
                d.filter_stok_fisik = $('#filterStokFisik').val();
            }
        },
        // Default order: Selisih column (now at index 7) ascending so biggest negative values appear first
        order: [[7, 'asc']],
        columns: [
            { data: 'obat_id', name: 'obat_id', className: 'text-center' },
            { data: 'nama_obat', name: 'nama_obat', className: 'nama-obat-cell', render: function(data, type, row) {
                    var name = data || '';
                    var kategori = row.kategori || row.obat_kategori || '';
                    var jenis = row.jenis || row.obat_jenis || '';
                    var metode = row.metode_bayar || row.obat_metode_bayar || '';
                    var badges = '';
                    if (kategori) {
                        var c = colorFromString(kategori, {saturation:60, lightness:50});
                        badges += '<span class="badge mr-1 badge-kategori" style="background:' + c.css + '; color:' + readableTextColorFromLightness(c.l) + ';">'+kategori+'</span>';
                    }
                    if (metode) {
                        var cm = colorFromString(metode, {saturation:70, lightness:55});
                        badges += '<span class="badge mr-1 badge-metode" style="background:' + cm.css + '; color:' + readableTextColorFromLightness(cm.l) + ';">'+metode+'</span>';
                    }
                    if (jenis) {
                        var cj = colorFromString(jenis, {saturation:65, lightness:45});
                        badges += '<span class="badge mr-1 badge-jenis" style="background:' + cj.css + '; color:' + readableTextColorFromLightness(cj.l) + ';">'+jenis+'</span>';
                    }
                    return '<div class="nama-obat-cell"><strong>'+name+'</strong>' + '<div class="nama-meta" style="margin-top:6px;">'+badges+'</div></div>';
                } },
            { data: 'satuan', name: 'satuan', defaultContent: '-', className: 'satuan-cell' },
            { data: 'stok_sistem', name: 'stok_sistem', className: 'text-right', render: function(data, type, row) { return formatQty(data); } },
            { data: 'stok_fisik', name: 'stok_fisik', className: 'text-right', render: function(data, type, row) {
                    var disabledAttr = isCompleted ? 'disabled' : '';
                    return `<input type="number" step="0.01" class="form-control form-control-sm stok-fisik-input" data-id="${row.id}" value="${formatQty(data)}" style="width:110px; text-align:right;" ${disabledAttr}>`;
                }
            },
            { data: 'total_temuan', name: 'total_temuan', orderable: false, searchable: false, className: 'text-right', render: function(data, type, row) { if (data === null || data === undefined) data = 0; return formatQty(data); } },
            { data: 'selisih', name: 'selisih', className: 'text-right', render: function(data, type, row) { var txt = formatQty(data); if (parseFloat(data) != 0) { return txt + ' <i class="fa fa-exclamation-triangle text-warning blink-warning" title="Ada selisih"></i>'; } else { return txt + ' <i class="fa fa-check text-success" title="Sesuai"></i>'; } } },
            { data: 'nearest_exp', name: 'nearest_exp', className: 'text-center', render: function(data, type, row) { return data || '-'; } },
            { data: null, name: 'nilai_stok', orderable: false, searchable: false, className: 'text-right', render: function(data, type, row) { var hpp = parseFloat(row.hpp_jual) || 0; var stokFisik = parseFloat(row.stok_fisik) || 0; var nilai = hpp * stokFisik; return 'Rp ' + nilai.toLocaleString('id-ID'); } },
                { data: null, orderable: false, searchable: false, className: 'text-center', render: function(data, type, row) {
                        var id = row.id || '';
                        var obatId = row.obat_id || row.obatId || '';
                        var editBtn = '<button class="btn btn-outline-info btn-edit-obat" title="Edit Obat" data-obat-id="'+obatId+'"><i class="fas fa-pencil-alt"></i></button>';
                        var batchBtn = '<button class="btn btn-outline-primary btn-batch-list" title="Batch" data-id="'+id+'"><i class="fa fa-th-list"></i></button>';
                        var temuanBtn = '<button class="btn btn-outline-secondary btn-temuan" title="Temuan" data-id="'+id+'"><i class="fa fa-exclamation-circle"></i></button>';
                        return '<div class="btn-group btn-group-sm" role="group" aria-label="Aksi">' + editBtn + batchBtn + temuanBtn + '</div>';
                    } }
        ]
    });

    // Reload table when filter changes
    $('#filterSelisih, #filterKategori, #filterMetode, #filterExpYear, #filterStokFisik').on('change', function() {
        table.ajax.reload();
    });

    // Update summary counts (total items & items with selisih)
    function updateCounts() {
        try {
            var info = table.page.info();
            if (info && typeof info.recordsTotal !== 'undefined') {
                $('#totalItemsText').text(info.recordsTotal);
            }
            var ajaxJson = table.ajax.json && table.ajax.json();
            if (ajaxJson && typeof ajaxJson.items_with_selisih !== 'undefined') {
                $('#itemsWithSelisihText').text(ajaxJson.items_with_selisih);
            } else {
                // Fallback: count selisih in currently loaded rows
                var cnt = 0;
                var d = table.rows().data();
                for (var i = 0; i < d.length; i++) {
                    var s = parseFloat(d[i].selisih) || 0;
                    if (s !== 0) cnt++;
                }
                $('#itemsWithSelisihText').text(cnt);
            }
        } catch (e) {
            console.error('updateCounts error', e);
        }
    }

    // Hook counts update on table draw/xhr
    table.on('xhr.dt draw', function() { updateCounts(); });

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
                        // Update selisih cell (column indexes after moving Nilai Stok to the rightmost)
                        // Columns: 0 Obat ID,1 Nama,2 Satuan,3 Stok Sistem,4 Stok Fisik,5 Total Temuan,6 Selisih,7 Exp Date,8 Nilai Stok
                        var selisihCell = $(table.cell(rowIdx, 6).node()); // Index 6 untuk kolom Selisih
                        var icon = parseFloat(res.selisih) != 0 ? '<i class="fa fa-exclamation-triangle text-warning blink-warning" title="Ada selisih"></i>' : '<i class="fa fa-check text-success" title="Sesuai"></i>';
                        selisihCell.html(formatQty(res.selisih) + ' ' + icon);
                        // Update Nilai Stok cell using hpp_jual from row data (now at index 8)
                        var rowData = table.row(rowIdx).data();
                        var hpp = parseFloat(rowData.hpp_jual) || 0;
                        var nilai = hpp * (parseFloat(res.stok_fisik) || 0);
                        var nilaiCell = $(table.cell(rowIdx, 8).node()); // Index 8 untuk Nilai Stok (rightmost)
                        nilaiCell.html('Rp ' + nilai.toLocaleString('id-ID'));
                        
                        // Also update table's internal data for stok_fisik and selisih so future redraws have correct values
                        rowData.stok_fisik = parseFloat(res.stok_fisik);
                        rowData.selisih = parseFloat(res.selisih);
                            table.row(rowIdx).data(rowData);

                            // Recalculate and update total nilai stok (sistem & fisik) immediately
                            var totalSistem = 0;
                            var totalFisik = 0;
                            table.rows().every(function() {
                                var d = this.data();
                                var h = parseFloat(d.hpp_jual) || 0;
                                var s = parseFloat(d.stok_sistem) || 0;
                                var f = parseFloat(d.stok_fisik) || 0;
                                totalSistem += h * s;
                                totalFisik += h * f;
                            });
                            $('#totalStokSistemText').text(totalSistem.toLocaleString('id-ID'));
                            $('#totalStokFisikText').text(totalFisik.toLocaleString('id-ID'));
                            var selDiff = totalSistem - totalFisik;
                            $('#selisihNilaiText').text(selDiff.toLocaleString('id-ID'));
                                // Refresh item counts (total items & items with selisih)
                                if (typeof updateCounts === 'function') updateCounts();
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
    
    // Function to load and show temuan modal for itemId
    function showTemuanModal(itemId) {
        if (!itemId) return;
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

                    // show modal (ensure it's visible)
                    $('#temuanHistoryModal').modal('show');

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
    }

    // bind click handlers: existing button (if any) and new total link
    $('#stokOpnameItemsTable').on('click', '.lihat-temuan-btn', function() {
        var itemId = $(this).data('id');
        showTemuanModal(itemId);
    });
    
    // Add Item modal open - load options via AJAX
    $('#addItemBtn').on('click', function() {
        var sel = $('#add_obat_id');
        sel.prop('disabled', true);
        sel.html('<option value="">Memuat...</option>');
        $.get("{{ route('erm.stokopname.availableObats', $stokOpname->id) }}")
            .done(function(res) {
                if (res.success && res.data && res.data.length > 0) {
                    var opts = '<option value="">-- Pilih Obat --</option>';
                    res.data.forEach(function(o) {
                        opts += '<option value="' + o.id + '">' + (o.nama || o.name || o.nama) + '</option>';
                    });
                    sel.html(opts);
                    sel.prop('disabled', false);
                    $('#addItemModal').modal('show');
                } else {
                    sel.html('<option value="">-- Tidak ada obat tersedia --</option>');
                    sel.prop('disabled', true);
                    Swal.fire({ icon: 'info', title: 'Tidak ada item', text: 'Tidak ada obat tersisa di gudang ini yang belum dimasukkan ke stok opname.' });
                }
            }).fail(function() {
                sel.html('<option value="">-- Gagal memuat --</option>');
                sel.prop('disabled', true);
                Swal.fire({ icon: 'error', title: 'Error', text: 'Gagal memuat daftar obat. Coba lagi.' });
            });
    });

    // Submit add item form
    $('#addItemForm').on('submit', function(e) {
        e.preventDefault();
        var obatId = $('#add_obat_id').val();
        if (!obatId) {
            alert('Pilih obat terlebih dahulu');
            return;
        }
        var btn = $('#saveAddItemBtn');
        btn.prop('disabled', true).text('Menambahkan...');
        $.post("{{ route('erm.stokopname.addItem', $stokOpname->id) }}", { obat_id: obatId, _token: '{{ csrf_token() }}' })
            .done(function(res) {
                if (res.success) {
                    $('#addItemModal').modal('hide');
                    // reload datatable and sync totals
                    try { table.ajax.reload(null, false); } catch (e) { location.reload(); }
                    if (typeof syncTotals === 'function') syncTotals();
                    // update buttons
                    checkButtonStates();
                    Swal.fire({ icon: 'success', title: 'Berhasil', text: res.message || 'Item ditambahkan' });
                } else {
                    Swal.fire({ icon: 'error', title: 'Gagal', text: res.message || 'Gagal menambahkan item' });
                }
            }).fail(function(xhr) {
                var msg = 'Gagal menambahkan item';
                if (xhr && xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                Swal.fire({ icon: 'error', title: 'Gagal', text: msg });
            }).always(function() {
                btn.prop('disabled', false).text('Tambah Item');
            });
    });
    // legacy links removed — use Aksi buttons instead

    // New action buttons handlers
    $('#stokOpnameItemsTable').on('click', '.btn-batch-list', function(e) {
        e.preventDefault();
        var itemId = $(this).data('id');
        if (!itemId) return;
        // reuse existing nama-obat-link handler logic
        $('#batchListContent').hide();
        $('#batchListLoading').show();
        $('#batchObatName').text('');
        $('#batchListTable tbody').empty();
        $('#batchListModal').data('item-id', itemId).modal('show');
        $.get('/erm/stokopname-item/' + itemId + '/batches')
            .done(function(res) {
                if (res.success) {
                    $('#batchObatName').text(res.obat || '-');
                    var rows = '';
                    if (res.batches && res.batches.length > 0) {
                        res.batches.forEach(function(b) {
                            var expDisplay = b.expiration_date || '-';
                            var expRaw = b.expiration_date_raw || '';
                            var editBtn = '<button class="btn btn-sm btn-outline-secondary edit-exp-btn" data-id="'+b.id+'" data-exp="'+expRaw+'" title="Ubah Tanggal"><i class="fa fa-edit"></i></button>';
                            rows += '<tr data-batch-id="'+b.id+'"><td>' + (b.batch || '-') + '</td><td class="text-right">' + (parseFloat(b.stok) || 0) + '</td><td class="exp-cell text-center">' + expDisplay + ' ' + editBtn + '</td></tr>';
                        });
                    } else {
                        rows = '<tr><td colspan="3" class="text-center">Tidak ada batch.</td></tr>';
                    }
                    $('#batchListTable tbody').html(rows);
                    $('#batchListLoading').hide();
                    $('#batchListContent').show();
                } else {
                    $('#batchListLoading').html('<div class="text-danger">'+(res.message||'Gagal memuat data')+'</div>');
                }
            }).fail(function() {
                $('#batchListLoading').html('<div class="text-danger">Gagal memuat data batch</div>');
            });
    });

    // Handle edit expiration button click inside batch modal
    $('#batchListModal').on('click', '.edit-exp-btn', function(e) {
        e.preventDefault();
        var btn = $(this);
        var tr = btn.closest('tr');
        var id = btn.data('id');
        var currentRaw = btn.data('exp') || '';

        // Replace cell with input + save/cancel
        var inputHtml = '<div class="d-flex justify-content-center align-items-center">' +
            '<input type="date" class="form-control form-control-sm exp-input" value="'+(currentRaw)+'" style="width:140px;">' +
            '<button class="btn btn-sm btn-primary ml-2 save-exp-btn" data-id="'+id+'">Save</button>' +
            '<button class="btn btn-sm btn-secondary ml-1 cancel-exp-btn">Cancel</button>' +
            '</div>';
        tr.find('.exp-cell').data('orig', tr.find('.exp-cell').html()).html(inputHtml);
    });

    // Cancel edit
    $('#batchListModal').on('click', '.cancel-exp-btn', function(e) {
        var tr = $(this).closest('tr');
        var orig = tr.find('.exp-cell').data('orig') || '-';
        tr.find('.exp-cell').html(orig);
    });

    // Save edited expiration date
    $('#batchListModal').on('click', '.save-exp-btn', function(e) {
        e.preventDefault();
        var btn = $(this);
        var id = btn.data('id');
        var tr = btn.closest('tr');
        var input = tr.find('.exp-input');
        var val = input.val();
        btn.prop('disabled', true).text('Saving...');

        $.post("{{ route('erm.stok-gudang.update-batch-exp') }}", {
            id: id,
            expiration_date: val,
            _token: '{{ csrf_token() }}'
        }).done(function(res) {
            if (res.success) {
                var newDisplay = res.data.expiration_date || '-';
                var newRaw = res.data.expiration_date_raw || '';
                var editBtn = '<button class="btn btn-sm btn-outline-secondary edit-exp-btn" data-id="'+id+'" data-exp="'+newRaw+'" title="Ubah Tanggal"><i class="fa fa-edit"></i></button>';
                tr.find('.exp-cell').html(newDisplay + ' ' + editBtn);
                // Recompute nearest expiration for this item from modal rows and update main DataTable
                try {
                    var itemIdModal = $('#batchListModal').data('item-id');
                    var minDate = null;
                    $('#batchListModal .edit-exp-btn').each(function() {
                        var d = $(this).data('exp');
                        if (d && d !== '') {
                            var dt = new Date(d + 'T00:00:00');
                            if (!minDate || dt < minDate) minDate = dt;
                        }
                    });
                    var formatted = '-';
                    if (minDate) formatted = minDate.toLocaleDateString('id-ID');

                    if (typeof table !== 'undefined') {
                        table.rows().every(function() {
                            var rowData = this.data();
                            if (rowData && parseInt(rowData.id) === parseInt(itemIdModal)) {
                                // Update the displayed nearest_exp cell (column index 7)
                                rowData.nearest_exp = formatted;
                                this.data(rowData);
                            }
                        });
                        // Redraw without resetting pagination
                        table.draw(false);
                    }
                } catch (e) {
                    console.error('Failed to update nearest exp in main table', e);
                }
            } else {
                alert(res.message || 'Gagal menyimpan');
                tr.find('.exp-cell').html(tr.find('.exp-cell').data('orig') || '-');
            }
        }).fail(function(xhr) {
            var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Gagal menyimpan';
            alert(msg);
            tr.find('.exp-cell').html(tr.find('.exp-cell').data('orig') || '-');
        }).always(function() {
            btn.prop('disabled', false).text('Save');
        });
    });

    $('#stokOpnameItemsTable').on('click', '.btn-temuan', function(e) {
        e.preventDefault();
        var itemId = $(this).data('id');
        showTemuanModal(itemId);
    });

    // batch list and temuan are opened via action buttons in the Aksi column

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
                    // also sync totals (includes temuan in nilai stok fisik)
                    try { syncTotals(); } catch(e) {}
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
                    try { syncTotals(); } catch(e) {}
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
                        try { syncTotals(); } catch(e) {}
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
    function blockUI() {
        try { $('#pageOverlay').show(); } catch(e) {}
    }
    function unblockUI() {
        try { $('#pageOverlay').hide(); } catch(e) {}
    }
    
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
                blockUI();

                Swal.fire({
                    title: 'Generating items...',
                    allowOutsideClick: false,
                    showConfirmButton: false,
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
                        // Hide generate button immediately so user doesn't need to refresh
                        btn.hide();
                        // Show submit button (updateStockBtn)
                        $('#updateStockBtn').show().prop('disabled', false);
                        // Update header to 'proses' (yellow) and update status text
                        $('#stokOpnameHeader').removeClass('bg-primary bg-success bg-warning text-white text-dark').addClass('bg-warning text-dark');
                        var newStatus = (res.status || 'proses').toUpperCase();
                        $('#status-text').text(newStatus);
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
                    unblockUI();
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
                blockUI();

                Swal.fire({
                    title: 'Updating stock...',
                    text: 'Sedang mengupdate stok berdasarkan hasil opname...',
                    allowOutsideClick: false,
                    showConfirmButton: false,
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
                        
                        // Update status display and hide/disable buttons
                        $('#status-text').text('SELESAI');
                        // change header to green
                        $('#stokOpnameHeader').removeClass('bg-primary bg-success bg-warning text-white text-dark').addClass('bg-success text-white');
                        $('#updateStockBtn').hide();
                        $('#generateItemsBtn').hide();

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
                    unblockUI();
                });
            }
        });
    });

    // ========== EDIT OBAT (Modal) ==========
    // Open edit obat modal when clicking edit button in Aksi column
    $('#stokOpnameItemsTable').on('click', '.btn-edit-obat', function(e) {
        e.preventDefault();
        var obatId = $(this).data('obat-id');
        if (!obatId) return;
        // Try to prefill from DataTable row for instant UX
        var tr = $(this).closest('tr');
        var rowData = null;
        try {
            rowData = table.row(tr).data();
        } catch (err) {
            rowData = null;
        }
        // reset and prefill form with current displayed values
        var initialId = (rowData && (rowData.obat_id || rowData.obatId || rowData.id)) ? (rowData.obat_id || rowData.obatId || rowData.id) : obatId;
        $('#edit_obat_id').val(initialId);
        $('#edit_obat_nama').val(rowData && rowData.nama_obat ? rowData.nama_obat : '');

        // Satuan: try to select matching option by value or by text; if not present leave placeholder
        var preSatuan = rowData && rowData.satuan ? rowData.satuan : '';
        if (preSatuan) {
            var $s = $('#edit_obat_satuan');
            if ($s.find('option[value="'+preSatuan+'"]').length) {
                $s.val(preSatuan);
            } else {
                // try match by option text (case-insensitive)
                var matched = false;
                $s.find('option').each(function(){ if ($(this).text().toLowerCase() === String(preSatuan).toLowerCase()) { $s.val($(this).val()); matched = true; return false; } });
                if (!matched) {
                    // append unknown satuan as option and select it
                    $s.append('<option value="'+preSatuan+'">'+preSatuan+'</option>');
                    $s.val(preSatuan);
                }
            }
        } else {
            $('#edit_obat_satuan').val('');
        }

        // Kategori: straightforward (text values)
        if (rowData && (rowData.kategori || rowData.obat_kategori)) $('#edit_obat_kategori').val(rowData.kategori || rowData.obat_kategori);

        // Metode bayar: prefer ID if present, otherwise try to find option by label
        var preMetodeId = rowData && (rowData.metode_bayar_id || rowData.obat_metode_bayar_id) ? (rowData.metode_bayar_id || rowData.obat_metode_bayar_id) : null;
        var preMetodeLabel = rowData && (rowData.metode_bayar || rowData.obat_metode_bayar) ? (rowData.metode_bayar || rowData.obat_metode_bayar) : null;
        var $met = $('#edit_obat_metode');
        if (preMetodeId) {
            $met.val(preMetodeId);
        } else if (preMetodeLabel) {
            var matched = false;
            $met.find('option').each(function(){ if ($(this).text().trim().toLowerCase() === String(preMetodeLabel).trim().toLowerCase()) { $met.val($(this).val()); matched = true; return false; } });
            if (!matched) {
                // leave as placeholder (server will fill authoritative value)
            }
        }

        $('#editObatModal').modal('show');

        // fetch authoritative obat data via ajax route (ajax/obat/{id}) and refresh fields
        $.get('/ajax/obat/' + obatId)
            .done(function(res) {
                $('#edit_obat_id').val(res.id || obatId);
                $('#edit_obat_nama').val(res.nama || $('#edit_obat_nama').val() || '');
                // Satuan: ensure option exists and select it
                if (typeof res.satuan !== 'undefined' && res.satuan !== null && String(res.satuan) !== '') {
                    var sVal = String(res.satuan);
                    var $s = $('#edit_obat_satuan');
                    if ($s.find('option[value="'+sVal+'"]').length === 0) {
                        $s.append('<option value="'+sVal+'">'+sVal+'</option>');
                    }
                    $s.val(sVal);
                }
                if (typeof res.kategori !== 'undefined' && res.kategori !== null) $('#edit_obat_kategori').val(res.kategori);
                if (typeof res.metode_bayar_id !== 'undefined' && res.metode_bayar_id !== null) {
                    var mv = res.metode_bayar_id;
                    var $met = $('#edit_obat_metode');
                    if ($met.find('option[value="'+mv+'"]').length) {
                        $met.val(mv);
                    } else if (res.metode_bayar && res.metode_bayar !== '') {
                        // append option with label
                        $met.append('<option value="'+mv+'">'+res.metode_bayar+'</option>');
                        $met.val(mv);
                    }
                }
            })
            .fail(function() {
                Swal.fire({ icon: 'error', title: 'Gagal memuat data obat', timer: 1500, showConfirmButton: false });
                $('#editObatModal').modal('hide');
            });
    });

    // Save edited obat via AJAX PUT
    $('#saveEditObatBtn').on('click', function() {
        var id = $('#edit_obat_id').val();
        if (!id) { Swal.fire({ icon: 'error', title: 'Invalid obat id', timer: 1200, showConfirmButton: false }); return; }
        var payload = {
            nama: $('#edit_obat_nama').val(),
            satuan: $('#edit_obat_satuan').val(),
            kategori: $('#edit_obat_kategori').val(),
            metode_bayar_id: $('#edit_obat_metode').val(),
            _token: '{{ csrf_token() }}'
        };
        var btn = $(this);
        btn.prop('disabled', true).text('Menyimpan...');
        $.ajax({
            url: '/erm/obat/' + id,
            method: 'PUT',
            data: payload
        }).done(function(res) {
            if (res.success) {
                Swal.fire({ icon: 'success', title: res.message || 'Berhasil disimpan', timer: 1200, showConfirmButton: false });
                $('#editObatModal').modal('hide');
                // reload table to reflect badge/metadata changes
                if (typeof table !== 'undefined') table.ajax.reload(null, false);
            } else {
                Swal.fire({ icon: 'error', title: res.message || 'Gagal menyimpan', timer: 1500, showConfirmButton: false });
            }
        }).fail(function(xhr) {
            var msg = 'Gagal menyimpan obat';
            if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
            Swal.fire({ icon: 'error', title: msg, timer: 1800, showConfirmButton: false });
        }).always(function() {
            btn.prop('disabled', false).text('Simpan');
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
.blink-warning { animation: blink 1s linear infinite; }

/* Nama Obat: single-line truncation to keep responsive proportions */
.nama-obat-cell { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; display: block; max-width: 420px; }
.nama-obat-cell a { cursor: pointer; color: inherit; text-decoration: underline; display: block; }

/* Make action buttons in the Aksi column a bit wider for easier click targets */
#stokOpnameItemsTable td:last-child .btn { min-width: 38px; }
/* Remove extra margin for buttons inside a btn-group */
#stokOpnameItemsTable td:last-child .btn-group .btn { margin-right: 0; }
/* Nama meta badges (kategori, jenis) under the nama obat */
.nama-meta .badge { font-size: 0.68rem; padding: 0.25em 0.4em; vertical-align: middle; }
.badge-kategori { background-color: #6c757d; }
.badge-jenis { background-color: #17a2b8; }
.badge-metode { background-color: #ffc107; color: #212529; }

/* Ensure stok fisik input is right-aligned */
.stok-fisik-input { text-align: right !important; padding-right: 8px; }

/* Style for satuan cell: lowercase and left-aligned */
#stokOpnameItemsTable td.satuan-cell { text-align: left !important; text-transform: lowercase; }

/* Hide any DataTables responsive/detail control column if present */
#stokOpnameItemsTable th.dtr-control, #stokOpnameItemsTable td.dtr-control { display: none !important; padding: 0 !important; border: 0 !important; width: 0 !important; }
/* Hide empty header cells that might be injected */
#stokOpnameItemsTable thead th:empty { display: none !important; }

.stat-box { display:flex; align-items:center; justify-content:space-between; padding:0.35rem 0.5rem; border-radius:4px; box-shadow:none; margin-bottom:0.35rem; border:1px solid rgba(0,0,0,0.04); }
.stat-label { font-size:0.72rem; color:#6c757d; text-transform:uppercase; font-weight:700; }
.stat-value { font-size:0.88rem; font-weight:700; color:#212529; }
.stat-box .stat-value small { font-weight:600; }
.stat-primary { background: linear-gradient(90deg, rgba(13,110,253,0.06), rgba(13,110,253,0.02)); border-color: rgba(13,110,253,0.12); }
.stat-success { background: linear-gradient(90deg, rgba(40,167,69,0.06), rgba(40,167,69,0.02)); border-color: rgba(40,167,69,0.12); }
.stat-warning { background: linear-gradient(90deg, rgba(255,193,7,0.06), rgba(255,193,7,0.02)); border-color: rgba(255,193,7,0.12); }
.stat-danger { background: linear-gradient(90deg, rgba(220,53,69,0.04), rgba(220,53,69,0.02)); border-color: rgba(220,53,69,0.12); }
.stat-neutral { background: #f8f9fa; border-color: rgba(0,0,0,0.04); }

/* Reduce spacing of the stat rows for compactness */
.card-body .row.mt-1 { margin-top:0.25rem !important; }
.card-body .stat-box { padding:0.3rem 0.45rem; }

/* Uppercase key UI labels for consistency */
.card-header, .card-header strong, .stat-label, #stokOpnameItemsTable thead th { text-transform: uppercase; }
.stat-box .stat-value { letter-spacing: 0.2px; }

/* Subtle card shadow for this page */
.card { box-shadow: 0 6px 18px rgba(16,24,40,0.06); border-radius: 6px; }
.card .card-header { border-top-left-radius:6px; border-top-right-radius:6px; }
/* Force stat boxes into a single horizontal row on wide screens */
.stat-row { display:flex; flex-wrap:nowrap; gap:0.35rem; align-items:stretch; }
.stat-row .stat-col { flex: 1 1 0; min-width: 120px; }
.stat-row .stat-col.small-stat { min-width: 70px; flex: 0 1 90px; }
.stat-row .stat-col.small-stat-wide { min-width: 140px; flex: 0 1 140px; }
.stat-row .stat-col:last-child { margin-right: 0; }
@media (max-width: 1100px) {
    .stat-row { flex-wrap:wrap; }
    .stat-row .stat-col { min-width: 120px; flex: 1 1 45%; }
}

/* Full page overlay to prevent user interaction during async ops */
#pageOverlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(255,255,255,0.65); z-index: 15000; display: none; }
#pageOverlay .overlay-spinner { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: #333; }
</style>
</div>
@endsection
