@extends('layouts.erm.app')
@section('title', 'Farmasi | Manajemen Stok')
@section('navbar')
    @include('layouts.erm.navbar-farmasi')
@endsection
@section('content')
<div class="container-fluid">
    <!-- Page-Title -->
    <style>
        /* Main table: long names wrap; the Responsive extension folds HPP / Nilai Stok on narrow screens */
        #stok-table { width: 100% !important; }
        #stok-table td { vertical-align: middle; }
        #stok-table td.col-nama { white-space: normal; word-break: break-word; }
        #stok-table td.col-nama a { font-weight: 700; }
        #stok-table td.col-stok { text-align: right; white-space: nowrap; }
        #stok-table td.col-stok .badge { white-space: normal; }
        #stok-table td.col-angka { text-align: right; white-space: nowrap; }
        #stok-table td.col-aksi { text-align: center; white-space: nowrap; }
        #stok-table tbody tr.row-selected > td { background-color: rgba(0, 123, 255, .08) !important; }
        /* Kartu stok stays in view while scrolling the table (desktop) */
        @media (min-width: 768px) {
            .kartu-stok-sticky { position: sticky; top: 80px; }
            .kartu-stok-sticky .card-body { max-height: calc(100vh - 160px); overflow-y: auto; }
        }
        /* Header buttons wrap instead of overflowing on small screens */
        .page-actions { display: flex; flex-wrap: wrap; gap: 8px; justify-content: flex-end; }
        .page-actions .btn { margin: 0 !important; }
        /* Compact filter styles */
        .compact-filters .form-group { margin-bottom: 6px; }
        .compact-filters label { font-size: 12px; font-weight: 600; margin-bottom: 4px; }
        .compact-filters .form-control-sm { height: calc(1.6rem + 2px); padding: .25rem .5rem; font-size: .875rem; }
        .compact-filters .btn-sm { padding: .35rem .55rem; font-size: .85rem; }
        .compact-filters .form-check-label { font-size: 13px; font-weight:700; }
        .compact-filters .form-text { font-size: 11px; margin-top: 2px; }
        /* Notification badge on top-right of button */
        #btn-low-stock, #btn-audit { position: relative; }
        .notification-badge {
            position: absolute;
            top: -6px;
            right: -8px;
            min-width: 20px;
            height: 20px;
            padding: 0 5px;
            border-radius: 10px;
            background: #dc3545; /* red */
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            line-height: 1;
            box-shadow: 0 0 0 2px rgba(255,255,255,0.2);
        }
        /* Make modal extra-large wider than bootstrap default */
        .modal-dialog.modal-xl {
            max-width: 1400px;
            width: 95%;
        }
        #stok-table .action-buttons {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            white-space: nowrap;
        }
        #stok-table .action-buttons .btn-group { flex-wrap: nowrap; }
        #stok-table .action-buttons .btn {
            min-width: 32px;
        }
        .summary-card[data-status-filter], .summary-card[data-open] { cursor: pointer; transition: box-shadow .15s; }
        .summary-card[data-status-filter]:hover, .summary-card[data-open]:hover { box-shadow: 0 0 0 2px rgba(0,123,255,.35); }
        #lowstock-table td { vertical-align: middle; }
    </style>

    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="row">
                    <div class="col">
                        <h4 class="page-title">Manajemen Stok</h4>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:void(0);">Farmasi</a></li>
                            <li class="breadcrumb-item active">Manajemen Stok</li>
                        </ol>
                    </div>
                    <div class="col-12 col-md-auto ml-auto page-actions align-items-center mt-2 mt-md-0">
                        <a href="{{ route('erm.obat.forecast.index') }}" class="btn btn-outline-primary mr-2" title="Forecast pembelian: titik pesan, stok maks, dan saran qty pesan per box">
                            <i class="fas fa-chart-line"></i>&nbsp;Forecast
                        </a>
                        <button type="button" class="btn btn-primary mr-2" id="btn-low-stock" title="Obat yang stok + pending-nya sudah di bawah titik pesan (menurut forecast)">
                            <i class="fas fa-shopping-cart"></i>&nbsp;Perlu Dipesan
                            <span id="low-stock-count" class="notification-badge" style="display:none;">0</span>
                        </button>
                        <button type="button" class="btn btn-outline-secondary" id="btn-audit" title="Batch dengan data mencurigakan: ED default/kosong, batch tidak resmi, tidak cocok dengan kartu stok, dll">
                            <i class="fas fa-search"></i>&nbsp;Data Perlu Dicek
                            <span id="audit-count" class="notification-badge" style="display:none;">0</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Ringkasan -->
    <div class="row stok-summary">
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card h-100 mb-0 summary-card" data-status-filter="perlu_dipesan" title="Klik untuk menampilkan obat yang perlu dipesan">
                <div class="card-body d-flex align-items-center">
                    <div class="mr-3"><i class="fas fa-shopping-cart fa-2x text-warning"></i></div>
                    <div>
                        <div class="text-muted small">Perlu Dipesan</div>
                        <div class="h4 mb-0 font-weight-bold" id="summary-perlu-pesan">-</div>
                        <div class="small"><span class="text-danger" id="summary-kritis">-</span> kritis · <span id="summary-menunggu">-</span> menunggu barang</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card h-100 mb-0 summary-card" data-open="expiring" title="Klik untuk melihat batch yang akan / sudah expired">
                <div class="card-body d-flex align-items-center">
                    <div class="mr-3"><i class="fas fa-hourglass-half fa-2x text-danger"></i></div>
                    <div>
                        <div class="text-muted small">Expired & Akan Expired (gudang aktif)</div>
                        <div class="h4 mb-0 font-weight-bold" id="summary-sudah-expired">-</div>
                        <div class="small"><span id="summary-expired-30">-</span> dalam 30 hari · <span id="summary-akan-expired">-</span> obat dalam 6 bulan</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card h-100 mb-0 summary-card" data-status-filter="tidak_bergerak" title="Klik untuk menampilkan stok yang tidak bergerak">
                <div class="card-body d-flex align-items-center">
                    <div class="mr-3"><i class="fas fa-pause-circle fa-2x text-secondary"></i></div>
                    <div>
                        <div class="text-muted small">Stok Tidak Bergerak (3 bln)</div>
                        <div class="h4 mb-0 font-weight-bold" id="summary-nilai-tidak-bergerak">-</div>
                        <div class="small"><span id="summary-tidak-bergerak">-</span> obat · <span id="summary-overstock">-</span> overstock</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card h-100 mb-0">
                <div class="card-body d-flex align-items-center">
                    <div class="mr-3"><i class="fas fa-coins fa-2x text-success"></i></div>
                    <div>
                        <div class="text-muted small">Nilai Stok Gudang Terpilih</div>
                        <div class="h4 mb-0 font-weight-bold" id="nilai-stok-gudang">Rp 0</div>
                        <div class="small">Keseluruhan: <span id="nilai-stok-keseluruhan">Rp 0</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-7">
            <div class="card">
                <div class="card-header">
                    <div class="row align-items-center mb-2">
                        <div class="col">
                            <h4 class="card-title">Data Stok Obat per Gudang</h4>
                        </div>
                    </div>
                    <!-- Filters: what to show on row 1, how to narrow it on row 2 -->
                    <div class="compact-filters">
                        <div class="form-row">
                            <div class="col-sm-5 form-group">
                                <label for="filter_gudang">Gudang</label>
                                <select class="form-control form-control-sm" id="filter_gudang">
                                    @foreach($gudangs as $gudang)
                                        <option value="{{ $gudang->id }}" {{ $defaultGudang && $gudang->id === $defaultGudang->id ? 'selected' : '' }}>
                                            {{ $gudang->nama }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-sm-7 form-group">
                                <label for="search_obat">Cari Obat</label>
                                <div class="input-group input-group-sm">
                                    <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-search"></i></span></div>
                                    <input type="search" class="form-control" id="search_obat" placeholder="Nama obat atau kode..." autocomplete="off">
                                </div>
                            </div>
                        </div>
                        <div class="form-row align-items-end">
                            <div class="col-6 col-sm-4 form-group">
                                <label for="filter_status_stok">Status Stok</label>
                                <select class="form-control form-control-sm" id="filter_status_stok">
                                    <option value="">Semua</option>
                                    <option value="perlu_dipesan">Perlu Dipesan</option>
                                    <option value="kritis">Kritis</option>
                                    <option value="menunggu">Menunggu Barang</option>
                                    <option value="aman">Aman</option>
                                    <option value="overstock">Overstock</option>
                                    <option value="tidak_bergerak">Tidak Bergerak</option>
                                    <option value="stop_pengadaan">Stop Pengadaan</option>
                                </select>
                            </div>
                            <div class="col-6 col-sm-4 form-group">
                                <label for="filter_kategori">Kategori</label>
                                <select class="form-control form-control-sm" id="filter_kategori">
                                    <option value="">Semua Kategori</option>
                                    @foreach($kategoris as $kategori)
                                        <option value="{{ $kategori }}">{{ $kategori }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-sm-4 form-group d-flex justify-content-sm-end">
                                <button type="button" class="btn btn-outline-secondary btn-sm mr-2" id="btn-reset-filter" title="Kosongkan pencarian dan filter">
                                    <i class="fas fa-undo"></i> Reset
                                </button>
                                <button type="button" class="btn btn-success btn-sm" id="btn-download-stok" title="Download data stok ke Excel">
                                    <i class="fas fa-file-excel"></i> Export
                                </button>
                            </div>
                        </div>
                        <div class="custom-control custom-switch">
                            <!-- Checked = obat nonaktif disembunyikan (default) -->
                            <input type="checkbox" class="custom-control-input" id="hide_inactive_obat" checked>
                            <label class="custom-control-label small" for="hide_inactive_obat">Sembunyikan obat nonaktif</label>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-striped table-hover" style="width: 100%;" id="stok-table">
                        <thead>
                            <tr>
                                <th>Nama Obat</th>
                                <th class="text-right" title="Stok di gudang terpilih. Badge = status forecast untuk total stok semua gudang">Stok</th>
                                <th class="text-right">HPP</th>
                                <th class="text-right">Nilai Stok</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-5">
            <div class="card kartu-stok-sticky" id="kartu-stok-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">Detail Kartu Stok</h4>
                    <button type="button" class="btn btn-sm btn-link text-muted p-0" id="btn-close-kartu-stok" style="display:none;" title="Tutup kartu stok"><i class="fas fa-times"></i></button>
                </div>
                <div class="card-body" id="kartu-stok-panel"></div>
            </div>
        </div>
    </div>

                    <!-- Modal Download Data Stok -->
                    <div class="modal fade" id="downloadStokModal" tabindex="-1" role="dialog" aria-labelledby="downloadStokLabel" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="downloadStokLabel">Download Data Stok</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <form id="form-download-stok" class="form-horizontal">
                                        <div class="form-group">
                                            <label>Pilih Gudang</label>
                                            <select class="form-control" id="download_gudang" name="gudang_id">
                                                <option value="">Semua Gudang</option>
                                                @foreach($gudangs as $g)
                                                    <option value="{{ $g->id }}">{{ $g->nama }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>Tipe Data</label>
                                            <select class="form-control" id="download_type" name="type">
                                                <option value="1">Live Data (Hari Ini)</option>
                                                <option value="2">Stok Opname (Stok Fisik)</option>
                                                <option value="3">Data Tanggal Tertentu</option>
                                            </select>
                                        </div>
                                        <div class="form-row date-range">
                                            <div class="form-group col-md-6">
                                                <label>Tanggal Mulai</label>
                                                <input type="date" class="form-control" id="download_date_start" name="date_start" />
                                            </div>
                                            <div class="form-group col-md-6">
                                                <label>Tanggal Selesai</label>
                                                <input type="date" class="form-control" id="download_date_end" name="date_end" />
                                            </div>
                                        </div>

                                        <div class="form-group opname-group" style="display:none;">
                                            <label>Pilih Stok Opname</label>
                                            <select class="form-control" id="download_opname" name="stok_opname_id">
                                                <option value="">-- Pilih Stok Opname --</option>
                                                @foreach($stokOpnames as $op)
                                                    @php $gName = $op->gudang ? $op->gudang->nama : 'Gudang ID '.$op->gudang_id; @endphp
                                                    <option value="{{ $op->id }}" data-gudang="{{ $op->gudang_id }}">{{ $op->tanggal_opname }} — {{ $gName }} @if($op->notes) ({{ Str::limit($op->notes,30) }}) @endif</option>
                                                @endforeach
                                            </select>
                                            <small class="form-text text-muted">Pilih stok opname untuk mendownload stok fisik yang tercatat pada opname tersebut.</small>
                                        </div>
                                        <!-- Specific date group: used when type == 3 -->
                                        <div class="form-group specific-date-group" style="display:none;">
                                            <label>Tanggal Acuan</label>
                                            <input type="date" class="form-control" id="download_pivot_date" name="pivot_date" />
                                            <small class="form-text text-muted">Hitung stok per tanggal ini: menggunakan stok live saat ini lalu dikoreksi dengan kartu stok (masuk/keluar) setelah tanggal acuan.</small>
                                        </div>
                                    </form>
                                    <div class="text-muted small">Tipe <strong>Stok Opname</strong> akan men-download stok fisik yang tercatat pada stok opname dalam rentang tanggal yang dipilih. <strong>Live Data</strong> menggunakan stok saat ini (default hari ini). <strong>Data Tanggal Tertentu</strong> menghitung stok pada tanggal acuan dengan menyesuaikan transaksi setelah tanggal tersebut.</div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-primary" id="confirm-download-stok"><i class="fas fa-download"></i> Unduh</button>
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                                </div>
                            </div>
                        </div>
                    </div>
</div>

            <!-- Modal Low Stock (Stok Habis) -->
            <div class="modal fade" id="lowStockModal" tabindex="-1" role="dialog" aria-labelledby="lowStockLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="lowStockLabel">Obat Perlu Dipesan <small class="text-muted">(menurut forecast, stok + pending ≤ titik pesan)</small></h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" id="lowstock-table" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>Nama Obat</th>
                                            <th>Status</th>
                                            <th class="text-right" title="Stok semua gudang kecuali gudang expired">Stok</th>
                                            <th class="text-right" title="Pesanan yang belum datang">Pending</th>
                                            <th class="text-right">Titik Pesan</th>
                                            <th class="text-right">Saran Pesan</th>
                                            <th>Detail Stok per Gudang</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <a href="{{ route('erm.obat.forecast.index') }}" class="btn btn-outline-primary mr-auto"><i class="fas fa-chart-line"></i> Buka Forecast Lengkap</a>
                            <a href="{{ route('erm.permintaan.index', ['baru' => 1]) }}" class="btn btn-success"><i class="fas fa-file-invoice"></i> Buat Permintaan Pembelian</a>
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Expiring (Stok Expired / Kadaluarsa dalam 6 bulan) -->
            <div class="modal fade" id="expiringModal" tabindex="-1" role="dialog" aria-labelledby="expiringLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="expiringLabel">Batch Kadaluarsa dalam 6 Bulan / Sudah Lewat ED <small class="text-muted">(gudang aktif)</small></h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-light border small mb-3">
                                <i class="fas fa-info-circle"></i>
                                Batch yang sudah lewat ED tidak bisa dijual atau dimutasi. Pindahkan ke gudang expired per batch atau sekaligus lewat tombol di bawah.
                                Batch migrasi (<strong>MIGRATE-*</strong>) tidak tampil di sini karena ED-nya belum diketahui; lengkapi dulu lewat <strong>Data Perlu Dicek</strong>.
                                Dari gudang expired, stok hanya keluar lewat <strong>Retur ke Supplier</strong> atau <strong>Pemusnahan</strong> (dengan Berita Acara).
                            </div>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" id="expiring-table" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>Nama Obat</th>
                                            <th>Batch</th>
                                            <th>Stok Batch</th>
                                            <th>Gudang</th>
                                            <th>Tanggal Expired</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary mr-auto" id="btn-riwayat-tindak-lanjut"><i class="fas fa-history"></i> Riwayat Retur / Pemusnahan</button>
                            @hasanyrole('Admin|Farmasi')
                            <button type="button" class="btn btn-danger" id="btn-move-all-expired" title="Pindahkan semua batch yang sudah lewat ED dari semua gudang aktif ke gudang expired"><i class="fas fa-dolly"></i> Pindahkan Semua yang Sudah Expired</button>
                            @endhasanyrole
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                        </div>
                    </div>
                </div>
            </div>

<!-- Modal Riwayat Tindak Lanjut Expired -->
<div class="modal fade" id="riwayatTindakLanjutModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Riwayat Retur / Pemusnahan Stok Expired</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-sm" id="riwayat-tindak-lanjut-table" style="width:100%">
                        <thead>
                            <tr>
                                <th>Nomor Dokumen</th>
                                <th>Jenis</th>
                                <th>Tanggal</th>
                                <th>Obat</th>
                                <th class="text-right">Batch</th>
                                <th class="text-right">Jumlah</th>
                                <th>Status</th>
                                <th>Petugas</th>
                                <th>Dokumen</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal for Batch Details -->
<div class="modal fade" id="batchDetailsModal" tabindex="-1" role="dialog" aria-labelledby="batchDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="batchDetailsTitle">Detail Batch</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="batchDetailsContent">
                    <!-- Content will be loaded dynamically -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="expiredFollowUpModal" tabindex="-1" role="dialog" aria-labelledby="expiredFollowUpModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="expiredFollowUpModalLabel">Tindak Lanjut Stok Expired</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="expired-follow-up-form">
                    <input type="hidden" id="expired_follow_up_obat_id" name="obat_id">
                    <input type="hidden" id="expired_follow_up_gudang_id" name="gudang_id">
                    <div class="mb-3">
                        <div class="small text-muted">Obat</div>
                        <div class="font-weight-bold" id="expired_follow_up_obat_nama">-</div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-7">
                            <label for="expired_follow_up_action">Tindak Lanjut <span class="text-danger">*</span></label>
                            <select class="form-control" id="expired_follow_up_action" name="tindak_lanjut" required>
                                <option value="">-- Pilih Tindak Lanjut --</option>
                                <option value="diretur">Retur ke Supplier</option>
                                <option value="dimusnahkan">Pemusnahan</option>
                            </select>
                        </div>
                        <div class="form-group col-md-5">
                            <label for="expired_follow_up_tanggal">Tanggal <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="expired_follow_up_tanggal" name="tanggal" required>
                        </div>
                    </div>
                    <div class="alert alert-info small py-2 follow-up-help" data-action="diretur" style="display:none;">
                        Sistem membuat <strong>Retur ke Supplier</strong> (status diajukan) untuk faktur asal setiap batch.
                        Stok baru keluar dari gudang expired setelah retur di-approve di menu Retur ke Supplier. Batch yang tidak ditemukan fakturnya tidak bisa diretur, jadi pilih Pemusnahan untuk batch tersebut.
                    </div>
                    <div class="follow-up-musnah" style="display:none;">
                        <div class="form-group">
                            <label for="expired_follow_up_metode">Metode Pemusnahan <span class="text-danger">*</span></label>
                            <select class="form-control" id="expired_follow_up_metode" name="metode">
                                <option value="">-- Pilih Metode --</option>
                                @foreach ($metodePemusnahan as $metode)
                                    <option value="{{ $metode }}">{{ $metode }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="expired_follow_up_saksi_1">Saksi 1 <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="expired_follow_up_saksi_1" name="saksi_1" maxlength="150" placeholder="Nama saksi">
                            </div>
                            <div class="form-group col-md-6">
                                <label for="expired_follow_up_saksi_2">Saksi 2 <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="expired_follow_up_saksi_2" name="saksi_2" maxlength="150" placeholder="Nama saksi">
                            </div>
                        </div>
                        <div class="alert alert-info small py-2">
                            Semua batch obat ini di gudang expired dikeluarkan dan dicatat di <strong>Berita Acara Pemusnahan</strong> yang bisa dicetak dan ditandatangani.
                        </div>
                    </div>
                    <div class="form-group mb-0">
                        <label for="expired_follow_up_notes">Keterangan</label>
                        <textarea class="form-control" id="expired_follow_up_notes" name="notes" rows="3" maxlength="2000" placeholder="Opsional"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="save-expired-follow-up-btn"><i class="fas fa-save"></i> Simpan</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: preview dokumen referensi kartu stok -->
<div class="modal fade" id="refPreviewModal" tabindex="-1" role="dialog" aria-labelledby="refPreviewTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="refPreviewTitle">Dokumen</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-0" id="refPreviewContent" style="min-height:300px;"></div>
            <div class="modal-footer">
                <a href="#" target="_blank" class="btn btn-outline-secondary mr-auto" id="refPreviewOpen"><i class="fas fa-external-link-alt"></i> Buka di tab baru</a>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Data Perlu Dicek (batch dengan data mencurigakan) -->
<div class="modal fade" id="auditModal" tabindex="-1" role="dialog" aria-labelledby="auditModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="auditModalLabel"><i class="fas fa-search"></i> Data Stok Perlu Dicek</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p class="small text-muted mb-2">
                    Batch yang datanya terlihat tidak wajar, misalnya ED default/kosong, nomor batch buatan sistem, batch yang tidak ada di dokumen masuk, atau stok yang tidak cocok dengan kartu stok.
                    Perbaiki <strong>nomor batch dan ED</strong> langsung di tabel (sesuai fisik barang). Untuk <strong>jumlah</strong>, gunakan Penyesuaian Stok atau Stok Opname.
                </p>
                <div class="d-flex flex-wrap align-items-center mb-2" id="audit-chips"></div>
                <div class="form-row align-items-end mb-2">
                    <div class="col-md-4 mb-2">
                        <select class="form-control form-control-sm" id="audit_gudang">
                            <option value="">Semua Gudang</option>
                            @foreach($gudangs as $gudang)
                                <option value="{{ $gudang->id }}">{{ $gudang->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-5 mb-2">
                        <input type="search" class="form-control form-control-sm" id="audit_search" placeholder="Cari nama obat atau batch...">
                    </div>
                    <div class="col-md-3 mb-2 text-md-right">
                        <span class="small text-muted" id="audit-info"></span>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered table-hover mb-0" id="auditTable">
                        <thead class="thead-light">
                            <tr>
                                <th>Obat</th>
                                <th>Gudang</th>
                                <th style="width:190px">Batch</th>
                                <th style="width:150px">ED</th>
                                <th style="width:110px" class="text-right">Stok</th>
                                <th>Temuan</th>
                                <th style="width:150px"></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary mr-auto" id="btn-audit-refresh"><i class="fas fa-sync-alt"></i> Muat ulang</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<style>
    /* Above stacked Bootstrap modals (their z-index is raised per level) */
    .swal2-container { z-index: 2000 !important; }
</style>
<script type="text/javascript">
// SweetAlert2 (v9) helpers shared by every script on this page
window.StokUi = {
    success: function(message, title) {
        return Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: title || message,
            text: title ? message : undefined,
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true
        });
    },
    error: function(message, title) {
        return Swal.fire({ icon: 'error', title: title || 'Gagal', text: message || 'Terjadi kesalahan.' });
    },
    warning: function(message, title) {
        return Swal.fire({ icon: 'warning', title: title || 'Periksa kembali', html: message });
    },
    // Resolves to true only when the user confirmed
    confirm: function(options) {
        return Swal.fire({
            icon: options.icon || 'warning',
            title: options.title || 'Lanjutkan?',
            html: options.html,
            text: options.text,
            showCancelButton: true,
            confirmButtonText: options.confirmText || 'Ya, lanjutkan',
            cancelButtonText: 'Batal',
            confirmButtonColor: options.danger ? '#d33' : '#3085d6',
            cancelButtonColor: '#6c757d',
            reverseButtons: true,
            focusCancel: !!options.danger
        }).then(function(result) { return !!result.value; });
    },
    // Message from a failed $.ajax call (validation errors, JSON message or a fallback)
    xhrMessage: function(xhr, fallback) {
        var json = xhr && xhr.responseJSON;
        if (json && json.errors) {
            return Object.keys(json.errors).map(function(k) { return json.errors[k][0]; }).join('\n');
        }
        return (json && json.message) || fallback || 'Terjadi kesalahan.';
    }
};

$(document).ready(function() {

    function escapeHtml(value) {
        if (value === null || value === undefined) return '';
        return String(value).replace(/[&<>"']/g, function(s) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[s];
        });
    }

    var GUDANG_STORAGE_KEY = 'stokGudang.gudangId';

    // Remember the last gudang per browser (per-viewer convenience only)
    try {
        var savedGudang = window.localStorage.getItem(GUDANG_STORAGE_KEY);
        if (savedGudang && $('#filter_gudang option[value="' + savedGudang + '"]').length) {
            $('#filter_gudang').val(savedGudang);
        }
    } catch (e) {}

    var KARTU_STOK_EMPTY = '<div class="text-center text-muted py-5">'
        + '<i class="fas fa-book-open fa-2x mb-2"></i>'
        + '<div>Klik tombol <i class="fas fa-book-open"></i> pada daftar obat untuk melihat kartu stok di sini.</div>'
        + '</div>';
    function resetKartuStokPanel() {
        $('#kartu-stok-panel').html(KARTU_STOK_EMPTY);
        $('#btn-close-kartu-stok').hide();
        $('#stok-table tbody tr').removeClass('row-selected');
    }
    resetKartuStokPanel();
    $('#btn-close-kartu-stok').on('click', resetKartuStokPanel);

    // Initialize DataTable
    var table = $('#stok-table').DataTable({
        processing: true,
        serverSide: true,
        searching: false, // filters above the table are sent to the server
        autoWidth: false,
        ajax: {
            url: '{{ route("erm.stok-gudang.data") }}',
            data: function(d) {
                d.gudang_id = $('#filter_gudang').val();
                d.search_obat = $('#search_obat').val();
                d.kategori = $('#filter_kategori').val();
                d.status_stok = $('#filter_status_stok').val();
                d.hide_inactive = $('#hide_inactive_obat').is(':checked') ? 1 : 0;
            }
        },
        columns: [
            { data: 'nama_obat', name: 'nama_obat', searchable: false, className: 'col-nama', responsivePriority: 1 },
            { data: 'total_stok', name: 'total_stok', searchable: false, className: 'col-stok', responsivePriority: 2, width: '22%' },
            { data: 'hpp', name: 'hpp', searchable: false, className: 'col-angka', responsivePriority: 5, width: '12%' },
            { data: 'nilai_stok', name: 'nilai_stok', searchable: false, className: 'col-angka', responsivePriority: 4, width: '14%' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'col-aksi', responsivePriority: 3, width: '110px' }
        ],
        order: [[0, 'asc']],
        pageLength: 25,
        lengthMenu: [10, 25, 50, 100],
        responsive: true,
        language: {
            processing: '<i class="fas fa-spinner fa-spin"></i> Memuat...',
            lengthMenu: 'Tampilkan _MENU_ obat',
            zeroRecords: 'Tidak ada obat yang cocok dengan filter.',
            emptyTable: 'Belum ada stok di gudang ini.',
            info: 'Menampilkan _START_–_END_ dari _TOTAL_ obat',
            infoEmpty: 'Tidak ada data',
            paginate: { previous: '‹', next: '›' }
        },
        drawCallback: function() {
            // Tooltips only inside the table; dispose first so they do not pile up on every redraw
            var $tips = $('#stok-table [data-toggle="tooltip"]');
            try { $tips.tooltip('dispose'); } catch (e) {}
            $tips.tooltip();
        }
    });

    // One request refreshes every number in the header (cards + button badges).
    // Several actions call these helpers right after each other, so the call is debounced.
    var summaryTimer = null;
    function refreshSummary() {
        clearTimeout(summaryTimer);
        summaryTimer = setTimeout(loadSummary, 150);
    }
    var updateNilaiStok = refreshSummary;
    var updateLowStockCount = refreshSummary;
    var updateExpiringCount = refreshSummary;
    var updateSummary = refreshSummary;

    function formatRupiah(value) {
        return 'Rp ' + Number(value || 0).toLocaleString('id-ID', { maximumFractionDigits: 0 });
    }

    function setBadge($badge, count) {
        count = parseInt(count || 0, 10);
        if (count > 0) {
            $badge.text(count > 99 ? '99+' : String(count)).show();
        } else {
            $badge.hide();
        }
    }

    function loadSummary() {
        $.get('{{ route("erm.stok-gudang.summary") }}', {
            gudang_id: $('#filter_gudang').val(),
            hide_inactive: $('#hide_inactive_obat').is(':checked') ? 1 : 0,
            months: 6
        }).done(function(res) {
            $('#summary-perlu-pesan').text(Number(res.perlu_pesan || 0).toLocaleString('id-ID') + ' obat');
            $('#summary-kritis').text(Number(res.kritis || 0).toLocaleString('id-ID'));
            $('#summary-menunggu').text(Number(res.menunggu || 0).toLocaleString('id-ID'));
            $('#summary-sudah-expired').text(Number(res.sudah_expired || 0).toLocaleString('id-ID') + ' batch');
            $('#summary-expired-30').text(Number(res.expired_30_hari || 0).toLocaleString('id-ID'));
            $('#summary-nilai-tidak-bergerak').text(formatRupiah(res.nilai_tidak_bergerak));
            $('#summary-tidak-bergerak').text(Number(res.tidak_bergerak || 0).toLocaleString('id-ID'));
            $('#summary-overstock').text(Number(res.overstock || 0).toLocaleString('id-ID'));
            $('#nilai-stok-gudang').text(formatRupiah(res.nilai_gudang));
            $('#nilai-stok-keseluruhan').text(formatRupiah(res.nilai_keseluruhan));

            setBadge($('#low-stock-count'), res.perlu_pesan);
            $('#btn-low-stock').toggleClass('btn-primary', res.perlu_pesan > 0).toggleClass('btn-outline-secondary', !(res.perlu_pesan > 0));
            $('#summary-akan-expired').text(Number(res.akan_expired || 0).toLocaleString('id-ID'));
            setBadge($('#audit-count'), res.data_perlu_dicek);
            $('#btn-audit').toggleClass('btn-outline-danger', res.data_perlu_dicek > 0).toggleClass('btn-outline-secondary', !(res.data_perlu_dicek > 0));
        });
    }
    loadSummary();

    $('#filter_gudang').on('change', function() {
        try { window.localStorage.setItem(GUDANG_STORAGE_KEY, $(this).val()); } catch (e) {}
        // The kartu stok on screen belongs to the previous gudang
        resetKartuStokPanel();
        table.ajax.reload();
        refreshSummary();
    });

    // Search as you type (debounced); 'input' also catches paste and the clear (x) button
    var searchTimeout;
    $('#search_obat').on('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            table.ajax.reload();
        }, 350);
    });

    // Kategori / status filter: reload table when changed (server-side filter applied)
    $('#filter_kategori, #filter_status_stok').on('change', function() {
        table.ajax.reload();
    });

    $('#hide_inactive_obat').on('change', function() {
        table.ajax.reload();
        refreshSummary();
    });

    // Summary cards act as shortcuts
    $('.summary-card[data-status-filter]').on('click', function() {
        $('#filter_status_stok').val($(this).data('status-filter')).trigger('change');
        $('html, body').animate({ scrollTop: $('#stok-table').offset().top - 120 }, 200);
    });
    $('.summary-card[data-open="expiring"]').on('click', function() {
        openExpiringModal();
    });

    $('#btn-reset-filter').on('click', function() {
        $('#search_obat').val('');
        $('#filter_kategori').val('');
        $('#filter_status_stok').val('');
        $('#hide_inactive_obat').prop('checked', true);
        table.ajax.reload();
        refreshSummary();
    });

    // Bootstrap 4 drops body.modal-open when a stacked modal (batch details over Perlu Dipesan / Akan Expired)
    // closes, which breaks scrolling of the modal still open underneath
    $(document).on('hidden.bs.modal', '.modal', function() {
        if ($('.modal.show').length) {
            $('body').addClass('modal-open');
        }
    });
    $(document).on('show.bs.modal', '.modal', function() {
        var openCount = $('.modal.show').length;
        if (openCount) {
            $(this).css('z-index', 1050 + openCount * 20);
            setTimeout(function() {
                $('.modal-backdrop').not('.modal-stack').last().css('z-index', 1049 + openCount * 20).addClass('modal-stack');
            }, 0);
        }
    });

        function renderBatchDetailsTable(response, showAll) {
            var allItems = Array.isArray(response.data) ? response.data : [];
            var positiveItems = allItems.filter(function(item) {
                return parseFloat(item.stok || 0) > 0;
            });
            var itemsToShow = showAll ? allItems : positiveItems.slice(0, 5);
            var hiddenCount = allItems.length - itemsToShow.length;

            var showGudang = !!response.all_gudang;
            var colCount = showGudang ? 5 : 4;

            var tableHtml = '<div class="table-responsive">';
            tableHtml += '<table class="table table-bordered" id="batch-table">';
            tableHtml += '<thead>';
            tableHtml += '<tr>';
            if (showGudang) tableHtml += '<th>Gudang</th>';
            tableHtml += '<th>Batch</th>';
            tableHtml += '<th>Stok</th>';
            tableHtml += '<th>Tanggal Expired</th>';
            tableHtml += '<th>Status</th>';
            tableHtml += '</tr>';
            tableHtml += '</thead>';
            tableHtml += '<tbody>';

            if (itemsToShow.length === 0) {
                tableHtml += '<tr><td colspan="' + colCount + '" class="text-center text-muted py-4">Tidak ada batch dengan stok lebih dari 0 pada tampilan ringkas.</td></tr>';
            } else {
                itemsToShow.forEach(function(item) {
                    tableHtml += '<tr data-id="' + item.id + '">';
                    if (showGudang) tableHtml += '<td>' + escapeHtml(item.gudang) + '</td>';
                    tableHtml += '<td>' + (item.batch === null ? '<span class="text-muted">-</span>' : escapeHtml(item.batch)) + '</td>';
                    tableHtml += '<td><span class="stok-display">' + item.stok_display + '</span></td>';
                    tableHtml += '<td>' + item.expiration_date + '</td>';
                    tableHtml += '<td>' + item.status + '</td>';
                    tableHtml += '</tr>';
                });
            }

            tableHtml += '</tbody></table></div>';

            if (!showAll && hiddenCount > 0) {
                tableHtml += '<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">';
                tableHtml += '<small class="text-muted">Menampilkan ' + itemsToShow.length + ' batch dengan stok > 0 dari total ' + allItems.length + ' batch.</small>';
                tableHtml += '<button type="button" class="btn btn-outline-primary btn-sm mt-2 mt-md-0" id="btn-show-all-batches">Tampilkan Semua Batch</button>';
                tableHtml += '</div>';
            } else if (showAll && allItems.length > 5) {
                tableHtml += '<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">';
                tableHtml += '<small class="text-muted">Menampilkan semua ' + allItems.length + ' batch.</small>';
                tableHtml += '<button type="button" class="btn btn-outline-secondary btn-sm mt-2 mt-md-0" id="btn-show-active-batches">Tampilkan 5 Batch Aktif</button>';
                tableHtml += '</div>';
            }

            tableHtml += '<div class="small text-muted"><i class="fas fa-info-circle"></i> Tampilan ini hanya untuk melihat. Perubahan jumlah stok dilakukan di menu <strong>Penyesuaian Stok</strong> atau <strong>Stok Opname</strong>; nomor batch / ED yang salah diperbaiki lewat <strong>Data Perlu Dicek</strong>.</div>';

            $('#batchDetailsContent').html(tableHtml);
            $('#batchDetailsModal').data('show-all', !!showAll);
        }

    // Kartu stok references open their document (invoice, faktur, transfer, penyesuaian, retur) in a preview
    $(document).on('click', '#kartu-stok-panel .btn-ref-preview', function(e) {
        e.preventDefault();
        var url = $(this).data('preview-url');
        var mode = $(this).data('preview-mode');
        if (!url) return;
        var title = $.trim($(this).find('strong').first().text()) || 'Dokumen';
        var $content = $('#refPreviewContent');
        $('#refPreviewTitle').text(title);
        $('#refPreviewOpen').attr('href', url).toggle(mode !== 'html');
        $content.html('<div class="text-center text-muted py-5"><i class="fas fa-spinner fa-spin fa-2x"></i><div class="mt-2">Memuat dokumen...</div></div>');
        $('#refPreviewModal').modal('show');
        if (mode === 'html') {
            $.get(url).done(function(html) {
                $content.html('<div class="p-3">' + html + '</div>');
            }).fail(function(xhr) {
                $content.html('<div class="text-danger text-center py-5">' + escapeHtml(StokUi.xhrMessage(xhr, 'Dokumen tidak dapat dimuat.')) + '</div>');
            });
            return;
        }
        var iframe = document.createElement('iframe');
        iframe.src = url;
        iframe.style.width = '100%';
        iframe.style.height = '75vh';
        iframe.style.border = '0';
        $content.empty().append(iframe);
    });
    $('#refPreviewModal').on('hidden.bs.modal', function() {
        $('#refPreviewContent').empty(); // stop the iframe
    });

    // Handle batch details button click
    $(document).on('click', '.show-batch-details', function() {
        var obatId = $(this).data('obat-id');
        var gudangId = $(this).data('gudang-id');
        
        $.ajax({
            url: '{{ route("erm.stok-gudang.batch-details") }}',
            type: 'GET',
            data: {
                obat_id: obatId,
                gudang_id: gudangId
            },
            success: function(response) {
                response.obat_id = obatId;
                response.gudang_id = gudangId;
                $('#batchDetailsTitle').text('Detail Batch - ' + response.obat + ' (' + response.gudang + ')');
                $('#batchDetailsModal').data('batch-response', response);
                renderBatchDetailsTable(response, false);
                $('#batchDetailsModal').modal('show');
            },
            error: function(xhr) {
                StokUi.error(StokUi.xhrMessage(xhr, 'Terjadi kesalahan saat mengambil data batch'));
            }
        });
    });

    $(document).on('click', '#btn-show-all-batches', function() {
        var response = $('#batchDetailsModal').data('batch-response');
        if (response) {
            renderBatchDetailsTable(response, true);
        }
    });

    $(document).on('click', '#btn-show-active-batches', function() {
        var response = $('#batchDetailsModal').data('batch-response');
        if (response) {
            renderBatchDetailsTable(response, false);
        }
    });

    // Handle kartu stok button click - load kartu stok detail into right panel
    $(document).on('click', '.btn-kartu-stok', function(e) {
        e.preventDefault();
        var obatId = $(this).data('obat-id');
        var gudangId = $(this).data('gudang-id');

        // Mark the row whose kartu stok is shown
        $('#stok-table tbody tr').removeClass('row-selected');
        $(this).closest('tr').addClass('row-selected');
        $('#btn-close-kartu-stok').show();

        $('#kartu-stok-panel').html('<div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-2x mb-2"></i><div>Memuat kartu stok...</div></div>');
        // On phones the panel sits below the table: bring it into view
        if (window.innerWidth < 768) {
            $('html, body').animate({ scrollTop: $('#kartu-stok-card').offset().top - 70 }, 200);
        }

        $.ajax({
            url: '{{ route("erm.kartustok.detail") }}',
            type: 'GET',
            data: {
                obat_id: obatId,
                gudang_id: gudangId
                // Keep date range empty (controller will use defaults). If you want to pass start/end, add inputs and send here.
            },
            success: function(response) {
                $('#kartu-stok-panel').html(response);
                // Re-initialize tooltips / icons inside the loaded HTML only
                $('#kartu-stok-panel [data-toggle="tooltip"]').tooltip();
                if (window.feather) feather.replace();
            },
            error: function() {
                $('#kartu-stok-panel').html('<div class="text-danger">Gagal memuat kartu stok.</div>');
            }
        });
    });

    function todayIso() {
        var d = new Date();
        return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    }

    function toggleFollowUpFields() {
        var action = $('#expired_follow_up_action').val();
        $('.follow-up-musnah').toggle(action === 'dimusnahkan');
        $('.follow-up-help[data-action="diretur"]').toggle(action === 'diretur');
    }
    $('#expired_follow_up_action').on('change', toggleFollowUpFields);

    $(document).on('click', '.btn-expired-follow-up', function() {
        var button = $(this);
        $('#expired-follow-up-form')[0].reset();
        $('#expired_follow_up_obat_id').val(button.data('obat-id'));
        $('#expired_follow_up_gudang_id').val(button.data('gudang-id'));
        $('#expired_follow_up_obat_nama').text(button.data('obat-nama') || '-');
        $('#expired_follow_up_tanggal').val(todayIso());
        $('#save-expired-follow-up-btn').prop('disabled', false).show();
        toggleFollowUpFields();
        $('#expiredFollowUpModal').modal('show');
    });

    $('#save-expired-follow-up-btn').on('click', function() {
        var btn = $(this);
        var action = $('#expired_follow_up_action').val();
        var data = {
            _token: '{{ csrf_token() }}',
            obat_id: $('#expired_follow_up_obat_id').val(),
            gudang_id: $('#expired_follow_up_gudang_id').val(),
            tindak_lanjut: action,
            tanggal: $('#expired_follow_up_tanggal').val(),
            notes: ($('#expired_follow_up_notes').val() || '').trim()
        };

        var errors = [];
        if (!action) errors.push('Pilih tindak lanjut.');
        if (!data.tanggal) errors.push('Tanggal wajib diisi.');
        if (action === 'dimusnahkan') {
            data.metode = $('#expired_follow_up_metode').val();
            data.saksi_1 = ($('#expired_follow_up_saksi_1').val() || '').trim();
            data.saksi_2 = ($('#expired_follow_up_saksi_2').val() || '').trim();
            if (!data.metode) errors.push('Pilih metode pemusnahan.');
            if (!data.saksi_1 || !data.saksi_2) errors.push('Isi nama kedua saksi.');
            if (data.saksi_1 && data.saksi_1.toLowerCase() === data.saksi_2.toLowerCase()) errors.push('Saksi 1 dan Saksi 2 harus orang yang berbeda.');
        }
        if (errors.length) {
            StokUi.warning(errors.map(escapeHtml).join('<br>'));
            return;
        }

        var obatNama = escapeHtml($('#expired_follow_up_obat_nama').text());
        var confirmOptions = action === 'dimusnahkan'
            ? {
                title: 'Musnahkan stok expired?',
                html: 'Semua batch <strong>' + obatNama + '</strong> di gudang expired dikeluarkan dan dicatat di <strong>Berita Acara Pemusnahan</strong>.'
                    + '<div class="text-left small mt-3">Tanggal: ' + escapeHtml(data.tanggal)
                    + '<br>Metode: ' + escapeHtml(data.metode)
                    + '<br>Saksi: ' + escapeHtml(data.saksi_1) + ' &amp; ' + escapeHtml(data.saksi_2) + '</div>'
                    + '<div class="text-danger small mt-2">Tindakan ini tidak dapat dibatalkan.</div>',
                confirmText: 'Ya, musnahkan',
                danger: true
            }
            : {
                title: 'Ajukan retur ke supplier?',
                html: 'Retur ke Supplier diajukan untuk semua batch <strong>' + obatNama + '</strong> di gudang expired.'
                    + '<div class="small mt-2">Stok baru keluar setelah retur di-approve di menu Retur ke Supplier.</div>',
                confirmText: 'Ya, ajukan retur'
            };

        StokUi.confirm(confirmOptions).then(function(ok) {
            if (!ok) return;
            submitFollowUp(btn, data);
        });
    });

    function submitFollowUp(btn, data) {
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...');

        $.ajax({
            url: '{{ route("erm.stok-gudang.expired-follow-up") }}',
            type: 'POST',
            data: data,
            success: function(response) {
                $('#expiredFollowUpModal').modal('hide');
                table.ajax.reload(null, false);
                refreshSummary();

                // The print / retur link is opened from a click, so pop-up blockers allow it
                var link = response.print_url
                    ? { url: response.print_url, text: '<i class="fas fa-print"></i> Cetak Berita Acara', newTab: true }
                    : (response.retur_url ? { url: response.retur_url, text: 'Buka Retur ke Supplier', newTab: false } : null);
                Swal.fire({
                    icon: 'success',
                    title: 'Tersimpan',
                    text: response.message || 'Tindak lanjut berhasil disimpan.',
                    showCancelButton: !!link,
                    confirmButtonText: link ? link.text : 'OK',
                    cancelButtonText: 'Tutup'
                }).then(function(result) {
                    if (link && result.value) {
                        if (link.newTab) window.open(link.url, '_blank');
                        else window.location.href = link.url;
                    }
                });
            },
            error: function(xhr) {
                StokUi.error(StokUi.xhrMessage(xhr, 'Gagal menyimpan tindak lanjut expired.'));
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fas fa-save"></i> Simpan');
            }
        });
    }

    // Karantina: move every expired batch out of the active gudang at once
    $('#btn-move-all-expired').on('click', function() {
        var btn = $(this);
        StokUi.confirm({
            title: 'Pindahkan semua batch expired?',
            html: 'Semua batch yang <strong>sudah lewat ED</strong> di gudang aktif dipindahkan ke gudang expired dan tidak bisa dijual lagi.'
                + '<div class="small mt-2">Batch migrasi (MIGRATE-*) tidak ikut dipindah.</div>',
            confirmText: 'Ya, pindahkan semua',
            danger: true
        }).then(function(ok) {
            if (!ok) return;

            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Memindahkan...');
            $.post('{{ route("erm.stok-gudang.expiring.move-all") }}', { _token: '{{ csrf_token() }}' })
                .done(function(res) {
                    if (res.success) {
                        Swal.fire({ icon: 'success', title: 'Selesai', text: res.message });
                    } else {
                        Swal.fire({ icon: 'warning', title: 'Sebagian gagal', text: res.message });
                    }
                })
                .fail(function(xhr) {
                    StokUi.error(StokUi.xhrMessage(xhr, 'Gagal memindahkan batch expired.'));
                })
                .always(function() {
                    btn.prop('disabled', false).html('<i class="fas fa-dolly"></i> Pindahkan Semua yang Sudah Expired');
                    if ($.fn.DataTable.isDataTable('#expiring-table')) {
                        $('#expiring-table').DataTable().ajax.reload(null, false);
                    }
                    table.ajax.reload(null, false);
                    refreshSummary();
                });
        });
    });

    // Riwayat retur / pemusnahan
    var riwayatTable = null;
    $('#btn-riwayat-tindak-lanjut').on('click', function() {
        $('#riwayatTindakLanjutModal').modal('show');
        if (riwayatTable) {
            riwayatTable.ajax.reload();
            return;
        }
        riwayatTable = $('#riwayat-tindak-lanjut-table').DataTable({
            ajax: { url: '{{ route("erm.stok-gudang.expired-follow-up.riwayat") }}', dataSrc: 'data' },
            order: [],
            pageLength: 25,
            columns: [
                { data: 'nomor_dokumen', render: function(d) { return d ? escapeHtml(d) : '<span class="text-muted">(data lama)</span>'; } },
                { data: 'jenis', render: function(d) { return '<span class="badge ' + (d === 'Pemusnahan' ? 'badge-danger' : 'badge-warning') + '">' + escapeHtml(d) + '</span>'; } },
                { data: 'tanggal' },
                { data: 'obat', render: function(d) { return escapeHtml(d); } },
                { data: 'jumlah_batch', className: 'text-right' },
                { data: 'jumlah', className: 'text-right', render: function(d) { return Number(d || 0).toLocaleString('id-ID', { maximumFractionDigits: 2 }); } },
                {
                    data: 'status_label',
                    render: function(d, type, row) {
                        var cls = row.status === 'selesai' ? 'badge-success' : (row.status === 'menunggu_retur' ? 'badge-warning' : 'badge-secondary');
                        return '<span class="badge ' + cls + '">' + escapeHtml(d) + '</span>';
                    }
                },
                { data: 'petugas', render: function(d) { return escapeHtml(d || '-'); } },
                {
                    data: 'print_url', orderable: false,
                    render: function(d, type, row) {
                        if (d) return '<a href="' + d + '" target="_blank" class="btn btn-sm btn-outline-success"><i class="fas fa-print"></i> Berita Acara</a>';
                        if (row.jenis === 'Retur') return '<a href="{{ route("erm.fakturretur.index") }}" class="btn btn-sm btn-outline-primary">Retur ke Supplier</a>';
                        return '-';
                    }
                }
            ],
            language: { emptyTable: 'Belum ada riwayat tindak lanjut.', search: 'Cari:' }
        });
    });

    // Download stok modal
    $('#btn-download-stok').on('click', function() {
        $('#downloadStokModal').modal('show');
    });
    function toggleDownloadFields() {
        var type = $('#download_type').val();
        if (type == '2') {
            $('.date-range').hide();
            $('.opname-group').show();
            $('.specific-date-group').hide();
        } else if (type == '3') {
            $('.date-range').hide();
            $('.opname-group').hide();
            $('.specific-date-group').show();
        } else {
            $('.date-range').show();
            $('.opname-group').hide();
            $('.specific-date-group').hide();
        }
    }

    // Initialize modal fields
    toggleDownloadFields();

    $('#download_type').on('change', function() {
        toggleDownloadFields();
    });

    // Filter opname options when gudang changes
    $('#download_gudang').on('change', function() {
        var gudang = $(this).val();
        $('#download_opname option').each(function() {
            var optGudang = $(this).data('gudang') + '';
            if (!optGudang) return; // first placeholder
            if (!gudang || optGudang === gudang) $(this).show(); else $(this).hide();
        });
        // Reset opname selection
        $('#download_opname').val('');
    });

    $('#confirm-download-stok').on('click', function() {
        var gudang = $('#download_gudang').val();
        var type = $('#download_type').val();
        var params = [];
        if (gudang) params.push('gudang_id=' + encodeURIComponent(gudang));
        params.push('type=' + encodeURIComponent(type));

        if (type == '2') {
            var opnameId = $('#download_opname').val();
            if (opnameId) params.push('stok_opname_id=' + encodeURIComponent(opnameId));
        } else if (type == '3') {
            var pivotDate = $('#download_pivot_date').val();
            if (pivotDate) params.push('pivot_date=' + encodeURIComponent(pivotDate));
        } else {
            var dateStart = $('#download_date_start').val();
            var dateEnd = $('#download_date_end').val();
            if (dateStart) params.push('date_start=' + encodeURIComponent(dateStart));
            if (dateEnd) params.push('date_end=' + encodeURIComponent(dateEnd));
        }

        var url = '{{ route("erm.stok-gudang.export") }}';
        if (params.length > 0) url += '?' + params.join('&');
        // Trigger browser download
        window.location = url;
        $('#downloadStokModal').modal('hide');
    });

    // "Perlu Dipesan" modal: obat at or below the forecast reorder point (client-side table, list is short)
    function fmtQty(value) {
        var n = Number(value || 0);
        return n.toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
    }

    var lowTable = null;
    $('#btn-low-stock').on('click', function() {
        $('#lowStockModal').modal('show');

        if (lowTable) {
            lowTable.ajax.reload();
            return;
        }

        lowTable = $('#lowstock-table').DataTable({
            ajax: {
                url: '{{ route("erm.stok-gudang.low-stock") }}',
                dataSrc: 'data'
            },
            columns: [
                {
                    data: 'obat_nama',
                    render: function(data, type, row) {
                        if (type !== 'display') return (row.obat_nama || '') + ' ' + (row.obat_kode || '');
                        var html = '<a href="#" class="show-batch-details font-weight-bold" data-obat-id="' + row.obat_id + '" data-gudang-id="">' + escapeHtml(row.obat_nama) + '</a>'
                            + '<div class="text-muted small">' + escapeHtml(row.obat_kode || '-') + '</div>';
                        if (row.wajib_stok) html += '<span class="badge badge-danger"><i class="fas fa-shield-alt"></i> Wajib Ada</span>';
                        return html;
                    }
                },
                {
                    data: 'status_rank',
                    render: function(data, type, row) {
                        if (type !== 'display') return data;
                        var cover = row.days_of_cover !== null && row.days_of_cover !== undefined
                            ? '<div class="small text-muted">cukup ' + fmtQty(Math.floor(row.days_of_cover)) + ' hari</div>' : '';
                        return '<span class="badge ' + row.status_badge + '">' + escapeHtml(row.status_label) + '</span>' + cover;
                    }
                },
                { data: 'total_stock', className: 'text-right', render: function(d, type, row) { return type === 'display' ? fmtQty(d) + ' ' + escapeHtml((row.satuan || '').toLowerCase()) : d; } },
                { data: 'approved_outstanding_permintaan', className: 'text-right', render: function(d, type) { return type === 'display' ? fmtQty(d) : d; } },
                {
                    data: 'reorder_point', className: 'text-right',
                    render: function(d, type, row) {
                        if (type !== 'display') return d;
                        return fmtQty(d) + '<div class="small text-muted">maks ' + fmtQty(row.max_stock) + '</div>';
                    }
                },
                {
                    data: 'qty_pesan', className: 'text-right',
                    render: function(d, type, row) {
                        if (type !== 'display') return d;
                        if (!Number(d)) return '<span class="text-muted">-</span>';
                        var box = row.jumlah_pesan_box ? '<div class="small">' + fmtQty(row.jumlah_pesan_box) + ' box × ' + fmtQty(row.isi_per_box) + '</div>' : '<div class="small text-warning">isi box ?</div>';
                        return '<strong>' + fmtQty(d) + '</strong>' + box;
                    }
                },
                {
                    data: 'detail_gudang', orderable: false,
                    render: function(d, type) {
                        if (type !== 'display') return '';
                        if (!d || !d.length) return '<span class="text-muted">-</span>';
                        return '<div class="small">' + d.map(function(g) {
                            return '<div><strong>' + escapeHtml(g.gudang) + ':</strong> ' + fmtQty(g.stok) + '</div>';
                        }).join('') + '</div>';
                    }
                }
            ],
            order: [],
            pageLength: 25,
            language: {
                emptyTable: 'Tidak ada obat yang perlu dipesan saat ini.',
                search: 'Cari:'
            },
            drawCallback: function() {
                $('#lowstock-table [data-toggle="tooltip"]').tooltip();
            }
        });
        // update count after low table loads
        lowTable.on('xhr', function() {
            updateLowStockCount();
        });
    });

    // Expiring modal & DataTable (expiration within 6 months)
    var expTable = null;
    function openExpiringModal() {
        $('#expiringModal').modal('show');

        if ($.fn.DataTable.isDataTable('#expiring-table')) {
            $('#expiring-table').DataTable().ajax.reload();
            return;
        }

        expTable = $('#expiring-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ route("erm.stok-gudang.expiring") }}',
                data: function(d) {
                    d.hide_inactive = $('#hide_inactive_obat').is(':checked') ? 1 : 0;
                    d.months = 6;
                }
            },
            columns: [
                { data: 'nama_obat', name: 'nama_obat', orderable: false },
                { data: 'exp_batches', name: 'exp_batches', orderable: false },
                { data: 'exp_stoks', name: 'exp_stoks', orderable: false },
                { data: 'exp_gudangs', name: 'exp_gudangs', orderable: false },
                { data: 'exp_dates', name: 'exp_dates', orderable: false },
                { data: 'exp_actions', name: 'exp_actions', orderable: false, searchable: false }
            ],
            order: [[0, 'asc']],
            pageLength: 25,
            responsive: true,
            drawCallback: function() {
                $('#expiring-table [data-toggle="tooltip"]').tooltip();
            }
        });
        expTable.on('draw', function() {
            updateExpiringCount();
        });
    }

    // If filters change, refresh low-stock table if visible
    $('#filter_gudang, #hide_inactive_obat').on('change', function() {
        if ($.fn.DataTable.isDataTable('#lowstock-table')) {
            $('#lowstock-table').DataTable().ajax.reload();
        }
        if ($.fn.DataTable.isDataTable('#expiring-table')) {
            $('#expiring-table').DataTable().ajax.reload();
        }
    });

    $(document).on('click', '.btn-move-expired', function() {
        var button = $(this);
        var stokId = button.data('stok-id');
        var targetGudangName = button.data('target-gudang-name') || 'Gudang Expired';

        if (!stokId) {
            StokUi.error('ID stok batch tidak ditemukan.');
            return;
        }

        StokUi.confirm({
            title: 'Pindahkan ke ' + escapeHtml(targetGudangName) + '?',
            html: '<strong>' + escapeHtml(button.data('obat') || '') + '</strong><br>'
                + 'Batch <strong>' + escapeHtml(button.data('batch') || '-') + '</strong> dari ' + escapeHtml(button.data('gudang') || '-')
                + '<div class="small mt-2">Seluruh stok batch ini dipindahkan dan tidak bisa dijual atau dimutasi lagi.</div>',
            confirmText: 'Ya, pindahkan',
            danger: true
        }).then(function(ok) {
            if (!ok) return;
            moveBatchToExpired(button, stokId);
        });
    });

    function moveBatchToExpired(button, stokId) {
        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Pindah...');

        $.ajax({
            url: '{{ route("erm.stok-gudang.expiring.move") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                id: stokId
            },
            success: function(response) {
                if (response.success) {
                    StokUi.success(response.message || 'Batch berhasil dipindahkan ke gudang expired.');
                    if ($.fn.DataTable.isDataTable('#expiring-table')) {
                        $('#expiring-table').DataTable().ajax.reload(null, false);
                    }
                    table.ajax.reload(null, false);
                    refreshSummary();
                } else {
                    StokUi.error(response.message || 'Gagal memindahkan batch.');
                }
            },
            error: function(xhr) {
                StokUi.error(StokUi.xhrMessage(xhr, 'Gagal memindahkan batch ke gudang expired.'));
            },
            complete: function() {
                button.prop('disabled', false).html('<i class="fas fa-exchange-alt"></i> Pindah');
            }
        });
    }
});
</script>

<script>
// Data Perlu Dicek: batches with suspicious data; batch number and ED are fixed inline, quantities via Penyesuaian Stok
$(function() {
    var AUDIT_URL = '{{ route('erm.stok-gudang.audit') }}';
    var PENYESUAIAN_URL = '{{ route("erm.mutasi-stok.index") }}';
    var BATCH_NULL = @json(\App\Http\Controllers\ERM\MutasiStokController::BATCH_NULL);
    var CAN_EDIT = @json($canMutasi);
    var CAN_MASUK = @json($canMasuk);
    var ED_PLACEHOLDER = @json(config('stok.audit.ed_placeholder', []));
    var TOKEN = '{{ csrf_token() }}';
    var SEVERITY_CLASS = { tinggi: 'badge-danger', sedang: 'badge-warning', rendah: 'badge-secondary' };

    var state = { rows: [], issues: {}, ringkasan: {}, issue: '' };

    function esc(value) {
        if (value === null || value === undefined) return '';
        return String(value).replace(/[&<>"']/g, function(s) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[s];
        });
    }

    function num(n) {
        return Number(n || 0).toLocaleString('id-ID', { maximumFractionDigits: 2 });
    }

    function renderChips() {
        var html = '<button type="button" class="btn btn-sm mr-1 mb-1 audit-chip ' + (state.issue === '' ? 'btn-dark' : 'btn-outline-dark') + '" data-issue="">Semua <span class="badge badge-light">' + (state.ringkasan.total || 0) + '</span></button>';
        Object.keys(state.issues).forEach(function(code) {
            var count = state.ringkasan[code] || 0;
            if (!count) return;
            var meta = state.issues[code];
            var active = state.issue === code;
            html += '<button type="button" class="btn btn-sm mr-1 mb-1 audit-chip ' + (active ? 'btn-dark' : 'btn-outline-secondary') + '" data-issue="' + code + '" title="' + esc(meta.info) + '">'
                + esc(meta.label) + ' <span class="badge ' + (SEVERITY_CLASS[meta.severity] || 'badge-light') + '">' + count + '</span></button>';
        });
        $('#audit-chips').html(html);
    }

    // Quantity fixes go to a Penyesuaian Stok document pre-filled with this batch
    function koreksiLink(r) {
        if (!CAN_EDIT) return '';
        if (r.stok < 0 && !CAN_MASUK) return '';
        var params = {
            gudang_id: r.gudang_id,
            obat_id: r.obat_id,
            batch: r.batch === null || r.batch === '' ? BATCH_NULL : r.batch,
            jenis: r.stok < 0 ? 'masuk' : 'keluar',
            alasan: 'Koreksi hitung'
        };
        return '<a class="btn btn-sm btn-outline-secondary" href="' + PENYESUAIAN_URL + '?' + $.param(params) + '" title="Koreksi jumlah: buat dokumen Penyesuaian Stok untuk batch ini"><i class="fas fa-balance-scale"></i></a>';
    }

    function renderRows() {
        var $tbody = $('#auditTable tbody');
        var q = $.trim($('#audit_search').val()).toLowerCase();
        var rows = state.rows.filter(function(r) {
            if (!q) return true;
            return (r.nama_obat || '').toLowerCase().indexOf(q) !== -1
                || String(r.batch || '').toLowerCase().indexOf(q) !== -1
                || String(r.kode_obat || '').toLowerCase().indexOf(q) !== -1;
        });
        $('#audit-info').text(rows.length + ' batch ditampilkan');

        if (!rows.length) {
            $tbody.html('<tr><td colspan="7" class="text-center text-muted py-4">' + (state.rows.length
                ? 'Tidak ada yang cocok dengan pencarian.'
                : '<i class="fas fa-check-circle text-success fa-2x d-block mb-2"></i>Tidak ada data stok yang perlu dicek.') + '</td></tr>');
            return;
        }

        var html = '';
        rows.forEach(function(r) {
            var badges = r.issues.map(function(code) {
                var meta = state.issues[code] || { label: code, severity: 'sedang', info: '' };
                return '<span class="badge ' + (SEVERITY_CLASS[meta.severity] || 'badge-light') + ' mr-1 mb-1" title="' + esc(meta.info) + '" data-toggle="tooltip">' + esc(meta.label) + '</span>';
            }).join('');
            if (r.issues.indexOf('tidak_cocok_kartu') !== -1) {
                badges += '<div class="small text-muted">Saldo kartu stok: ' + (r.saldo_kartu === null ? 'tidak ada riwayat' : num(r.saldo_kartu)) + '</div>';
            }
            html += '<tr data-id="' + r.id + '" data-batch="' + esc(r.batch || '') + '" data-ed="' + esc(r.expiration_date || '') + '">'
                + '<td>' + esc(r.nama_obat) + '<br><small class="text-muted">' + esc(r.kode_obat || '-') + ' · ' + esc(r.satuan || '-') + '</small></td>'
                + '<td>' + esc(r.nama_gudang || '-') + '</td>'
                + '<td>' + (CAN_EDIT ? '<input type="text" class="form-control form-control-sm audit-batch" maxlength="255" value="' + esc(r.batch || '') + '" placeholder="Nomor batch">' : esc(r.batch || '-')) + '</td>'
                + '<td>' + (CAN_EDIT ? '<input type="date" class="form-control form-control-sm audit-ed" value="' + esc(r.expiration_date || '') + '">' : esc(r.expiration_date || '-')) + '</td>'
                + '<td class="text-right' + (r.stok < 0 ? ' text-danger font-weight-bold' : '') + '">' + num(r.stok) + '</td>'
                + '<td>' + badges + '</td>'
                + '<td class="text-nowrap text-right">'
                    + (CAN_EDIT ? '<button type="button" class="btn btn-sm btn-primary btn-audit-save mr-1" title="Simpan nomor batch & ED"><i class="fas fa-save"></i> Simpan</button>' : '')
                    + koreksiLink(r)
                + '</td>'
                + '</tr>';
        });
        $tbody.html(html);
        $tbody.find('[data-toggle="tooltip"]').tooltip();
    }

    function loadAudit() {
        $('#auditTable tbody').html('<tr><td colspan="7" class="text-center text-muted py-4"><i class="fas fa-spinner fa-spin"></i> Memeriksa data stok...</td></tr>');
        $.get(AUDIT_URL, { gudang_id: $('#audit_gudang').val(), issue: state.issue }).done(function(res) {
            state.rows = res.data || [];
            state.issues = res.issues || {};
            state.ringkasan = res.ringkasan || {};
            renderChips();
            renderRows();
            // Header badge counts every gudang
            if (!$('#audit_gudang').val()) {
                var total = state.ringkasan.total || 0;
                $('#audit-count').text(total > 99 ? '99+' : String(total)).toggle(total > 0);
                $('#btn-audit').toggleClass('btn-outline-danger', total > 0).toggleClass('btn-outline-secondary', total === 0);
            }
        }).fail(function(xhr) {
            $('#auditTable tbody').html('<tr><td colspan="7" class="text-center text-danger py-4">Gagal memuat data.</td></tr>');
            StokUi.error(StokUi.xhrMessage(xhr, 'Gagal memeriksa data stok.'));
        });
    }

    $('#btn-audit').on('click', function() {
        $('#auditModal').modal('show');
        loadAudit();
    });
    $('#btn-audit-refresh').on('click', loadAudit);
    $('#audit_gudang').on('change', loadAudit);
    $(document).on('click', '.audit-chip', function() {
        state.issue = $(this).data('issue') || '';
        loadAudit();
    });
    var auditSearchTimer;
    $('#audit_search').on('input', function() {
        clearTimeout(auditSearchTimer);
        auditSearchTimer = setTimeout(renderRows, 200);
    });

    // Batch master data only (number + ED); the stok quantity is not touched
    $(document).on('click', '.btn-audit-save', function() {
        var $btn = $(this);
        var $tr = $btn.closest('tr');
        var id = $tr.data('id');
        var oldBatch = String($tr.attr('data-batch') || '');
        var newBatch = $.trim($tr.find('.audit-batch').val());
        var oldEd = String($tr.attr('data-ed') || '');
        var newEd = $tr.find('.audit-ed').val();

        if (!newBatch) {
            StokUi.warning('Nomor batch wajib diisi sesuai yang tertera di kemasan.');
            return;
        }
        var batchChanged = newBatch !== oldBatch;
        var edChanged = newEd !== oldEd;
        if (!batchChanged && !edChanged) {
            StokUi.warning('Ubah nomor batch atau ED terlebih dahulu.', 'Tidak ada perubahan');
            return;
        }
        if (!newEd || ED_PLACEHOLDER.indexOf(newEd) !== -1) {
            StokUi.warning('Isi ED asli sesuai kemasan. ED kosong atau tanggal default membuat batch ini tetap ditandai.');
            return;
        }

        var changes = [];
        if (batchChanged) changes.push('Batch: ' + esc(oldBatch || '(kosong)') + ' &rarr; <strong>' + esc(newBatch) + '</strong>');
        if (edChanged) changes.push('ED: ' + esc(oldEd || '(kosong)') + ' &rarr; <strong>' + esc(newEd) + '</strong>');

        StokUi.confirm({
            title: 'Simpan data batch?',
            html: '<div class="text-left">' + changes.join('<br>') + '</div>'
                + '<div class="small text-muted mt-2">Jumlah stok tidak berubah.' + (batchChanged ? ' Riwayat kartu stok batch lama ikut memakai nomor batch baru.' : '') + '</div>',
            confirmText: 'Ya, simpan'
        }).then(function(ok) {
            if (!ok) return;
            var requests = [];
            // Rename first: if the new batch number is rejected (duplicate), the ED is not saved either
            if (batchChanged) {
                requests.push(function() {
                    return $.post('{{ route('erm.stok-gudang.update-batch-name') }}', { _token: TOKEN, id: id, batch: newBatch });
                });
            }
            if (edChanged) {
                requests.push(function() {
                    return $.post('{{ route('erm.stok-gudang.update-batch-exp') }}', { _token: TOKEN, id: id, expiration_date: newEd });
                });
            }
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
            requests.reduce(function(chain, next) {
                return chain.then(next);
            }, $.Deferred().resolve().promise()).done(function() {
                StokUi.success('Data batch disimpan.');
                loadAudit();
                if ($.fn.DataTable.isDataTable('#stok-table')) {
                    $('#stok-table').DataTable().ajax.reload(null, false);
                }
            }).fail(function(xhr) {
                StokUi.error(StokUi.xhrMessage(xhr, 'Gagal menyimpan data batch.'));
                loadAudit();
            }).always(function() {
                $btn.prop('disabled', false).html('<i class="fas fa-save"></i> Simpan');
            });
        });
    });
});
</script>
@endsection
