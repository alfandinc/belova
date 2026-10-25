@extends('layouts.erm.app')

@section('title', 'Lakukan Stok Opname')
@section('navbar')
    @include('layouts.erm.navbar')
@endsection

@section('content')
<!-- Modal History Temuan -->
<div class="modal fade" id="temuanHistoryModal" tabindex="-1" role="dialog" aria-labelledby="temuanHistoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
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
                                                <input type="number" step="0.01" min="0.01" class="form-control form-control-sm" id="temuan_qty" placeholder="Qty" style="width:110px;">
                                        </div>
                                        <div class="form-group mr-2">
                                            <label class="sr-only" for="temuan_jenis">Jenis</label>
                                            {{-- plus = menjelaskan selisih plus (fisik lebih), minus = menjelaskan selisih minus (fisik kurang) --}}
                                            <select id="temuan_jenis" class="form-control form-control-sm" style="width:190px;" title="Pilih sesuai arah selisih yang dijelaskan temuan ini">
                                                <option value="plus">Fisik lebih (selisih +)</option>
                                                <option value="minus">Fisik kurang (selisih −)</option>
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
                                            <th>Jenis Selisih</th>
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
                        $totalItems = $totals['totalItems'];
                        $itemsWithSelisih = $totals['itemsWithSelisih'];
                        $totalStokSistem = $totals['totalStokSistem'];
                        $totalStokFisik = $totals['totalStokFisik'];
                        $fmtRpSigned = function ($v) {
                            $v = round((float) $v);
                            return ($v > 0 ? '+' : ($v < 0 ? '−' : '')) . 'Rp ' . number_format(abs($v), 0, ',', '.');
                        };
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
                                {{-- Same direction as the item selisih: + = fisik lebih, − = fisik kurang --}}
                                <div class="stat-label" title="Nilai fisik − nilai sistem (+ lebih, − kurang)">Selisih Nilai</div>
                                <div class="stat-value">
                                    <span id="selisihNilaiText">{{ $fmtRpSigned($totals['selisihNilai']) }}</span>
                                    <div class="small text-muted" style="font-weight:600;" title="Bagian selisih yang belum dijelaskan temuan">belum terjelaskan: <span id="sisaNilaiText">{{ $fmtRpSigned($totals['sisaNilai']) }}</span></div>
                                </div>
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
                                <div class="stat-label" title="Item yang masih punya selisih belum terjelaskan temuan">Items Belum Terjelaskan</div>
                                <div class="stat-value"><span id="itemsWithSelisihText">{{ $itemsWithSelisih }}</span></div>
                            </div>
                        </div>
                    </div>

                    @if($stokOpname->status !== 'selesai')
                    <div class="alert alert-warning mt-2 mb-0 py-2" style="font-size:13px;">
                        <i class="fa fa-exclamation-triangle"></i>
                        <strong>Stok sistem</strong> diambil saat <strong>Generate Items</strong>. Selama stok opname berjalan, jangan ada transaksi di gudang ini
                        (penjualan, resep, mutasi, penerimaan faktur, retur) sampai <strong>Submit Stok</strong>. Saat Submit, stok diset ke stok fisik.
                    </div>
                    @endif
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
                        {{-- Always rendered (hidden outside 'proses') so Generate Items can show it without a reload --}}
                        <button type="button" class="btn btn-secondary mr-2" id="addItemBtn"
                            @if($stokOpname->status !== 'proses') style="display:none;" @endif>
                            <i class="fa fa-plus"></i> Tambah Item
                        </button>
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
                                <label>Satuan Stok</label>
                                <select id="edit_obat_satuan" name="satuan_stok" class="form-control form-control-sm">
                                    <option value="">-- Pilih Satuan Stok --</option>
                                    @foreach(\App\Models\ERM\Obat::satuanStokOptions() as $s)
                                        <option value="{{ $s }}">{{ $s }}</option>
                                    @endforeach
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
                        <option value="with">Selisih belum terjelaskan</option>
                        <option value="without">Sesuai / sudah terjelaskan</option>
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
                            <th style="width:8%" title="Bagian selisih yang sudah dijelaskan oleh temuan">Terjelaskan Temuan</th>
                            <th style="width:10%" title="Stok fisik − stok sistem; di bawahnya sisa yang belum terjelaskan">Selisih</th>
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
    // +30 / −30 / 0 (selisih direction: + = fisik lebih, − = fisik kurang)
    function formatSigned(q) {
        var n = parseFloat(q) || 0;
        if (Math.abs(n) < 1e-9) return '0';
        return (n > 0 ? '+' : '−') + formatQty(Math.abs(n));
    }
    // Temuan explains part of the selisih: plus (legacy 'kurang') = selisih plus / fisik lebih,
    // minus (legacy 'lebih') = selisih minus / fisik kurang. Its net offsets the item's selisih.
    function temuanJenisLabel(jenis) {
        if (jenis === 'plus' || jenis === 'kurang') return 'Fisik lebih (selisih +)';
        if (jenis === 'minus' || jenis === 'lebih') return 'Fisik kurang (selisih −)';
        return '-';
    }
    // Escape text from the server before it goes into HTML (nama obat, catatan temuan, ...)
    function esc(v) {
        if (v === null || v === undefined) return '';
        return String(v).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
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
            var rp = function(v) { return Math.round(parseFloat(v) || 0).toLocaleString('id-ID'); };
            var rpSigned = function(v) {
                var n = Math.round(parseFloat(v) || 0);
                return (n > 0 ? '+' : (n < 0 ? '−' : '')) + 'Rp ' + Math.abs(n).toLocaleString('id-ID');
            };
            $('#totalStokSistemText').text(rp(res.totalStokSistem));
            $('#totalStokFisikText').text(rp(res.totalStokFisik));
            $('#selisihNilaiText').text(rpSigned(res.selisihNilai));
            $('#sisaNilaiText').text(rpSigned(res.sisaNilai));
            if (typeof res.itemsWithSelisih !== 'undefined') $('#itemsWithSelisihText').text(res.itemsWithSelisih);
            if (typeof res.totalItems !== 'undefined') $('#totalItemsText').text(res.totalItems);
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
        // Default order: Selisih column (index 6) ascending so biggest negative values appear first
        order: [[6, 'asc']],
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
                        badges += '<span class="badge mr-1 badge-kategori" style="background:' + c.css + '; color:' + readableTextColorFromLightness(c.l) + ';">'+esc(kategori)+'</span>';
                    }
                    if (metode) {
                        var cm = colorFromString(metode, {saturation:70, lightness:55});
                        badges += '<span class="badge mr-1 badge-metode" style="background:' + cm.css + '; color:' + readableTextColorFromLightness(cm.l) + ';">'+esc(metode)+'</span>';
                    }
                    if (jenis) {
                        var cj = colorFromString(jenis, {saturation:65, lightness:45});
                        badges += '<span class="badge mr-1 badge-jenis" style="background:' + cj.css + '; color:' + readableTextColorFromLightness(cj.l) + ';">'+esc(jenis)+'</span>';
                    }
                    return '<div class="nama-obat-cell"><strong>'+esc(name)+'</strong>' + '<div class="nama-meta" style="margin-top:6px;">'+badges+'</div></div>';
                } },
            { data: 'satuan', name: 'satuan', defaultContent: '-', className: 'satuan-cell' },
            { data: 'stok_sistem', name: 'stok_sistem', className: 'text-right', render: function(data, type, row) { return formatQty(data); } },
            { data: 'stok_fisik', name: 'stok_fisik', className: 'text-right', render: function(data, type, row) {
                    var disabledAttr = isCompleted ? 'disabled' : '';
                    return `<input type="number" step="0.01" min="0" class="form-control form-control-sm stok-fisik-input" data-id="${row.id}" value="${formatQty(data)}" style="width:110px; text-align:right;" ${disabledAttr}>`;
                }
            },
            // total_temuan is the net offset (−qty for "fisik lebih"); shown as the explained amount in the selisih direction
            { data: 'total_temuan', name: 'total_temuan', orderable: false, searchable: false, className: 'text-right', render: function(data, type, row) {
                    var explained = -(parseFloat(data) || 0);
                    return Math.abs(explained) < 1e-9 ? '<span class="text-muted">-</span>' : formatSigned(explained);
                } },
            // data = sisa belum terjelaskan (selisih + net temuan); the main number is the raw fisik − sistem
            { data: 'selisih', name: 'selisih', className: 'text-right', render: function(data, type, row) {
                    var raw = (parseFloat(row.stok_fisik) || 0) - (parseFloat(row.stok_sistem) || 0);
                    var sisa = parseFloat(data) || 0;
                    var hasTemuan = Math.abs((parseFloat(row.total_temuan) || 0)) > 1e-9;
                    var icon = Math.abs(sisa) > 1e-9
                        ? ' <i class="fa fa-exclamation-triangle text-warning blink-warning" title="Ada selisih belum terjelaskan"></i>'
                        : ' <i class="fa fa-check text-success" title="' + (Math.abs(raw) > 1e-9 ? 'Selisih sudah terjelaskan temuan' : 'Sesuai') + '"></i>';
                    var html = '<strong>' + formatSigned(raw) + '</strong>' + icon;
                    if (hasTemuan) {
                        html += '<div class="small text-muted">sisa ' + formatSigned(sisa) + '</div>';
                    }
                    return html;
                } },
            { data: 'nearest_exp', name: 'nearest_exp', className: 'text-center', render: function(data, type, row) { return data || '-'; } },
            { data: null, name: 'nilai_stok', orderable: false, searchable: false, className: 'text-right', render: function(data, type, row) { var hpp = parseFloat(row.hpp_obat) || 0; var stokFisik = parseFloat(row.stok_fisik) || 0; var nilai = hpp * stokFisik; return 'Rp ' + nilai.toLocaleString('id-ID'); } },
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
            // Whole-opname count from the server (rows on screen are only one page)
            var ajaxJson = table.ajax.json && table.ajax.json();
            if (ajaxJson && typeof ajaxJson.items_with_selisih !== 'undefined') {
                $('#itemsWithSelisihText').text(ajaxJson.items_with_selisih);
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
                        // Update the row data; re-rendering the row refreshes Selisih and Nilai Stok cells
                        var rowIdx = table.row(input.closest('tr')).index();
                        var rowData = table.row(rowIdx).data();
                        rowData.stok_fisik = parseFloat(res.stok_fisik);
                        rowData.selisih = parseFloat(res.selisih);
                        table.row(rowIdx).data(rowData);
                        var newInput = $(table.cell(rowIdx, 4).node()).find('.stok-fisik-input');
                        newInput.addClass('is-valid');
                        setTimeout(() => newInput.removeClass('is-valid'), 1000);

                        // Header totals cover the whole opname (incl. temuan): ask the server, not the current page
                        syncTotals();
                    },
                error: function(xhr) {
                    input.addClass('is-invalid');
                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.message) {
                        Swal.fire({ icon: 'error', title: 'Tidak bisa diubah', text: xhr.responseJSON.message });
                    }
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
                            <p><strong>Obat:</strong> ${esc(res.item.obat_nama)}<br>
                            <strong>Batch:</strong> ${esc(res.item.batch || '-')}<br>
                            <strong>Gudang:</strong> ${esc(res.item.gudang)}</p>
                        </div>
                        <hr>
                    `;

                    $('#temuanHistoryContent').html(content);

                    // Populate kartu stok-based history (if any) in the modal (append under header)
                    if (res.history && res.history.length > 0) {
                        var h = '<h6><strong>History (kartu stok)</strong></h6>' +
                            '<div class="table-responsive"><table class="table table-sm table-bordered"><thead class="bg-light"><tr><th>Tanggal & Waktu</th><th>Qty Temuan</th><th>Catatan</th><th>Stok Setelah</th></tr></thead><tbody>';
                        res.history.forEach(function(record) {
                            h += `<tr><td>${esc(record.tanggal)}</td><td class="text-center">${formatQty(record.qty)}</td><td>${esc(record.keterangan || '-')}</td><td class="text-center">${esc(record.stok_setelah)}</td></tr>`;
                        });
                        h += '</tbody></table></div>';
                        $('#temuanHistoryContent').append(h);
                    }

                    // Populate simple temuan records into the temuanRecordsTable
                    if (res.temuan_records && res.temuan_records.length > 0) {
                        res.temuan_records.forEach(function(r) {
                            var jenisLabel = temuanJenisLabel(r.jenis);
                            var actionBtn = '';
                            if (r.process_status && parseInt(r.process_status) === 1) {
                                actionBtn = `<button class="btn btn-sm btn-secondary" disabled>Diproses</button>`;
                            } else {
                                actionBtn = `<button class="btn btn-sm btn-danger delete-temuan-btn" data-id="${r.id}">Hapus</button>`;
                            }
                            var tr = `<tr data-id="${r.id}"><td>${esc(r.tanggal)}</td><td class="text-center">${formatQty(r.qty)}</td><td class="text-center">${jenisLabel}</td><td>${esc(r.keterangan || '-')}</td><td>${esc(r.created_by_name || '-')}</td><td class="text-center">${actionBtn}</td></tr>`;
                            $('#temuanRecordsTable tbody').append(tr);
                            var row = $('#temuanRecordsTable').find(`tr[data-id="${r.id}"]`);
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
                        opts += '<option value="' + esc(o.id) + '">' + esc(o.nama || o.name) + '</option>';
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
                            var expDisplay = esc(b.expiration_date || '-');
                            var expRaw = esc(b.expiration_date_raw || '');
                            var editBtn = '<button class="btn btn-sm btn-outline-secondary edit-exp-btn" data-id="'+esc(b.id)+'" data-exp="'+expRaw+'" title="Ubah Tanggal"><i class="fa fa-edit"></i></button>';
                            rows += '<tr data-batch-id="'+esc(b.id)+'"><td>' + esc(b.batch || '-') + '</td><td class="text-right">' + formatQty(parseFloat(b.stok) || 0) + '</td><td class="exp-cell text-center">' + expDisplay + ' ' + editBtn + '</td></tr>';
                        });
                    } else {
                        rows = '<tr><td colspan="3" class="text-center">Tidak ada batch.</td></tr>';
                    }
                    $('#batchListTable tbody').html(rows);
                    $('#batchListLoading').hide();
                    $('#batchListContent').show();
                } else {
                    $('#batchListLoading').html('<div class="text-danger">'+esc(res.message||'Gagal memuat data')+'</div>');
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
                var newDisplay = esc(res.data.expiration_date || '-');
                var newRaw = esc(res.data.expiration_date_raw || '');
                var editBtn = '<button class="btn btn-sm btn-outline-secondary edit-exp-btn" data-id="'+esc(id)+'" data-exp="'+newRaw+'" title="Ubah Tanggal"><i class="fa fa-edit"></i></button>';
                tr.find('.exp-cell').html(newDisplay + ' ' + editBtn);
                // Nearest exp is computed by the server (batches with stok only, same date format): reload the page of rows
                if (typeof table !== 'undefined') table.ajax.reload(null, false);
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
                    var jenisLabel = temuanJenisLabel(r.jenis);
                    var actionBtn = '';
                    if (r.process_status && parseInt(r.process_status) === 1) {
                        actionBtn = `<button class="btn btn-sm btn-secondary" disabled>Diproses</button>`;
                    } else {
                        actionBtn = `<button class="btn btn-sm btn-danger delete-temuan-btn" data-id="${r.id}">Hapus</button>`;
                    }
                    var tr = `<tr data-id="${r.id}"><td>${esc(r.tanggal)}</td><td class="text-center">${formatQty(r.qty)}</td><td class="text-center">${jenisLabel}</td><td>${esc(r.keterangan || '-')}</td><td>${esc(r.created_by_name || '-')}</td><td class="text-center">${actionBtn}</td></tr>`;
                    // if table previously had 'no data' row, remove it
                    var first = $('#temuanRecordsTable tbody tr:first');
                    if (first.length && first.find('td').length === 1) {
                        $('#temuanRecordsTable tbody').empty();
                    }
                    $('#temuanRecordsTable tbody').prepend(tr);
                    var newRow = $('#temuanRecordsTable').find(`tr[data-id="${r.id}"]`);
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
                        
                        // Reload table and refresh header totals
                        table.ajax.reload();
                        syncTotals();
                        // Hide generate button immediately so user doesn't need to refresh
                        btn.hide();
                        // Status is now 'proses': Submit and Tambah Item become available
                        $('#updateStockBtn').show().prop('disabled', false);
                        $('#addItemBtn').show();
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
        // Warn when stok changed since Generate (a transaction happened during the opname)
        var checkReq = $.get("{{ route('erm.stokopname.submitCheck', $stokOpname->id) }}");
        checkReq.always(function() {
        var berubah = (checkReq.responseJSON && checkReq.responseJSON.berubah) || [];
        var warningHtml = '';
        if (checkReq.status !== 200) {
            warningHtml = '<div class="alert alert-warning text-left">Pengecekan perubahan stok gagal dijalankan. Pastikan tidak ada transaksi di gudang ini sejak Generate Items.</div>';
        } else if (berubah.length) {
            var rows = berubah.slice(0, 15).map(function(b) {
                return '<tr><td>' + $('<div>').text(b.nama + (b.batch ? ' (' + b.batch + ')' : '')).html() + '</td>'
                    + '<td class="text-right">' + formatQty(b.stok_sistem) + '</td>'
                    + '<td class="text-right">' + formatQty(b.stok_sekarang) + '</td></tr>';
            }).join('');
            var more = berubah.length > 15 ? '<div><small>... dan ' + (berubah.length - 15) + ' item lainnya</small></div>' : '';
            warningHtml = '<div class="alert alert-danger text-left" style="font-size:13px;">'
                + '<strong>Peringatan:</strong> ' + berubah.length + ' item stoknya sudah berubah sejak Generate Items (ada transaksi selama stok opname). '
                + 'Jika tetap disubmit, stok item tersebut diset ke stok fisik dan transaksi tadi ikut tertimpa.'
                + '<table class="table table-sm table-bordered mt-2 mb-1" style="background:#fff;"><thead><tr><th>Obat</th><th>Stok Sistem (Generate)</th><th>Stok Sekarang</th></tr></thead><tbody>' + rows + '</tbody></table>'
                + more + '</div>';
        }
        Swal.fire({
            title: 'Update Stock dari Hasil Opname?',
            width: berubah.length ? 700 : undefined,
            html: warningHtml + `
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
                        $('#addItemBtn').hide();

                        // Opname is final now: the reloaded rows render stok fisik inputs disabled
                        isCompleted = true;
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
                    // 422 = already applied (e.g. the first request succeeded but its response was lost):
                    // reload so the page shows the real, finished state
                    if (xhr.status === 422) {
                        Swal.fire({
                            icon: 'info',
                            title: 'Sudah diterapkan',
                            text: xhr.responseJSON?.message || 'Opname ini sudah diterapkan ke stok.'
                        }).then(function() { location.reload(); });
                        return;
                    }
                    // No response at all (network drop / browser timeout): the server may still have finished
                    if (xhr.status === 0) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Koneksi terputus',
                            text: 'Hasil submit tidak diketahui. Halaman akan dimuat ulang untuk mengecek status; jika masih PROSES, silakan Submit lagi.'
                        }).then(function() { location.reload(); });
                        return;
                    }
                    // Server answered with an error: nothing was changed (the whole submit is one transaction), safe to retry
                    Swal.fire({
                        icon: 'error',
                        title: 'Submit gagal, stok tidak berubah',
                        text: (xhr.responseJSON?.message || ('Koneksi / server error (' + xhr.status + ')'))
                            + '. Semua perubahan dibatalkan, silakan coba Submit lagi. Jika terus gagal, muat ulang halaman dulu.'
                    });
                })
                .always(function() {
                    btn.prop('disabled', false);
                    unblockUI();
                });
            }
        });
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

        // Satuan stok: filled from the server response below (only valid satuan stok values are selectable)
        $('#edit_obat_satuan').val('');
        // Clear values left from the previously edited obat, else they get saved onto this one
        $('#edit_obat_kategori').val('');
        $('#edit_obat_metode').val('');

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
                // Satuan stok: leave empty when not set yet so the user has to pick one
                $('#edit_obat_satuan').val(res.satuan_stok || '');
                if (typeof res.kategori !== 'undefined' && res.kategori !== null) $('#edit_obat_kategori').val(res.kategori);
                if (typeof res.metode_bayar_id !== 'undefined' && res.metode_bayar_id !== null) {
                    var mv = res.metode_bayar_id;
                    var $met = $('#edit_obat_metode');
                    if ($met.find('option[value="'+mv+'"]').length) {
                        $met.val(mv);
                    } else if (res.metode_bayar && res.metode_bayar !== '') {
                        // append option with label
                        $met.append('<option value="'+esc(mv)+'">'+esc(res.metode_bayar)+'</option>');
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
            satuan_stok: $('#edit_obat_satuan').val(),
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
