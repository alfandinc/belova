@extends('layouts.erm.app')
@section('title', 'ERM | Master Obat & BHP')
@section('navbar')
    @include('layouts.erm.navbar-farmasi')
@endsection

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">
<div class="container-fluid obat-page">

    {{-- Header --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between mb-3 mt-2">
        <div>
            <h3 class="mb-0">Master Obat &amp; BHP</h3>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0 bg-transparent mt-1">
                    <li class="breadcrumb-item">ERM</li>
                    <li class="breadcrumb-item">Farmasi</li>
                    <li class="breadcrumb-item active">Master Obat &amp; BHP</li>
                </ol>
            </nav>
        </div>
        <div class="btn-toolbar mt-2 mt-md-0">
            <button type="button" class="btn btn-primary mr-2 btn-tambah-obat"><i class="fas fa-plus mr-1"></i> Tambah Obat</button>
            <button type="button" class="btn btn-outline-info mr-2 btn-zat-aktif"><i class="fas fa-pills mr-1"></i> Kelola Zat Aktif</button>
            <button type="button" class="btn btn-outline-info mr-2 btn-supplier"><i class="fas fa-industry mr-1"></i> Kelola Principal</button>
            <div class="btn-group">
                <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-file-excel mr-1"></i> Export / Import
                </button>
                <div class="dropdown-menu dropdown-menu-right">
                    <a class="dropdown-item" href="#" id="btnExportExcel"><i class="fas fa-file-download mr-2"></i> Export Excel (sesuai filter)</a>
                    <a class="dropdown-item" href="#" data-toggle="modal" data-target="#importCsvModal"><i class="fas fa-file-upload mr-2"></i> Import CSV (update massal)</a>
                </div>
            </div>
        </div>
    </div>

    {{-- Data quality shortcuts --}}
    <div id="incompleteBox" class="alert alert-warning py-2 px-3 mb-3 d-none">
        <i class="fas fa-clipboard-check mr-1"></i> <strong>Data obat aktif yang perlu dilengkapi:</strong>
        <span id="incompleteChips"></span>
        <small class="d-block text-muted mt-1">Klik untuk menampilkan obatnya, lalu klik baris obat untuk mengedit.</small>
    </div>

    <div class="card">
        <div class="card-body pb-2">
            {{-- Filters --}}
            <div class="form-row align-items-end filter-bar">
                <div class="col-lg-3 col-md-6 mb-2">
                    <label for="filter_search" class="small text-muted mb-1">Cari</label>
                    <div class="input-group">
                        <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-search"></i></span></div>
                        <input type="search" id="filter_search" class="form-control" placeholder="Nama, kode, zat aktif, principal, distributor" autocomplete="off">
                    </div>
                </div>
                <div class="col-lg col-md-3 col-6 mb-2">
                    <label for="filter_kategori" class="small text-muted mb-1">Kategori</label>
                    <select id="filter_kategori" class="form-control filter-select">
                        <option value="">Semua</option>
                        @foreach($kategoris as $kategori)
                            <option value="{{ $kategori }}">{{ $kategori }}</option>
                        @endforeach
                        <option value="{{ \App\Http\Controllers\ERM\ObatController::FILTER_KOSONG }}">(Belum diisi)</option>
                    </select>
                </div>
                <div class="col-lg col-md-3 col-6 mb-2">
                    <label for="filter_metode_bayar" class="small text-muted mb-1">Metode Bayar</label>
                    <select id="filter_metode_bayar" class="form-control filter-select">
                        <option value="">Semua</option>
                        @foreach($metodeBayars as $metodeBayar)
                            <option value="{{ $metodeBayar->id }}">{{ $metodeBayar->nama }}</option>
                        @endforeach
                        <option value="{{ \App\Http\Controllers\ERM\ObatController::FILTER_KOSONG }}">(Belum diisi)</option>
                    </select>
                </div>
                <div class="col-lg col-md-3 col-6 mb-2">
                    <label for="filter_satuan_stok" class="small text-muted mb-1">Satuan Stok</label>
                    <select id="filter_satuan_stok" class="form-control filter-select">
                        <option value="">Semua</option>
                        @foreach($satuanStokList as $s)
                            <option value="{{ $s }}">{{ $s }}</option>
                        @endforeach
                        <option value="{{ \App\Http\Controllers\ERM\ObatController::FILTER_KOSONG }}">(Belum diisi)</option>
                    </select>
                </div>
                <div class="col-lg col-md-3 col-6 mb-2">
                    <label for="filter_paten" class="small text-muted mb-1">Jenis</label>
                    <select id="filter_paten" class="form-control filter-select">
                        <option value="">Semua</option>
                        <option value="1">Generik</option>
                        <option value="0">Paten</option>
                    </select>
                </div>
                <div class="col-lg col-md-3 col-6 mb-2">
                    <label for="filter_status" class="small text-muted mb-1">Status</label>
                    <select id="filter_status" class="form-control filter-select">
                        <option value="">Semua</option>
                        <option value="1" selected>Aktif</option>
                        <option value="0">Tidak Aktif</option>
                    </select>
                </div>
            </div>
            <div class="form-row align-items-end filter-bar">
                <div class="col-lg-3 col-md-6 mb-2">
                    <label for="filter_kandungan" class="small text-muted mb-1">Zat Aktif / Kandungan</label>
                    <select id="filter_kandungan" class="form-control"></select>
                </div>
                <div class="col-lg-3 col-md-6 mb-2">
                    <label for="filter_kelengkapan" class="small text-muted mb-1">Kelengkapan Data</label>
                    <select id="filter_kelengkapan" class="form-control filter-select">
                        <option value="">Semua</option>
                        <option value="harga_jual">Harga jual belum diisi</option>
                        <option value="hpp">Belum ada HPP (belum pernah dibeli)</option>
                        <option value="zat_aktif">Zat aktif belum diisi</option>
                    </select>
                </div>
                <div class="col-lg-auto col-md-12 mb-2 ml-auto text-right">
                    <span id="activeFilterInfo" class="small text-muted mr-2"></span>
                    <button type="button" class="btn btn-light border" id="btnResetFilter"><i class="fas fa-undo mr-1"></i> Reset filter</button>
                </div>
            </div>

            {{-- Set from the Pemasok & Principal modal ("N obat"); not saved with the other filters --}}
            <div id="supplierFilterInfo" class="alert alert-info py-1 px-2 mb-2 small d-none">
                <i class="fas fa-truck mr-1"></i> Menampilkan obat dari <span id="supplierFilterLabel"></span> <strong id="supplierFilterNama"></strong>
                <a href="#" id="btnClearSupplierFilter" class="ml-2">&times; hapus filter</a>
            </div>

            {{-- scrollX: DataTables adds its own horizontal scroll wrapper --}}
            <table id="obat-table" class="table table-hover table-sm table-bordered w-100 nowrap">
                <thead class="thead-light">
                    <tr>
                        <th>Kode Obat</th>
                        <th>Nama Obat</th>
                        <th>Kategori</th>
                        <th>Jenis Obat</th>
                        <th>Metode Bayar</th>
                        <th>Satuan Stok/Jual</th>
                        <th>Dosis</th>
                        <th>Zat Aktif</th>
                        <th>Principal</th>
                        <th>Distributor</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

{{-- Modal Tambah/Edit Obat --}}
<div class="modal fade" id="obatModal" tabindex="-1" role="dialog" aria-labelledby="obatModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form id="formObat" autocomplete="off" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title" id="obatModalLabel">Tambah Obat</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="obat_id" name="obat_id">
                    <input type="hidden" name="sync_zataktif" value="1">
                    <div id="formErrors" class="alert alert-danger d-none"></div>

                    <h6 class="form-section">Identitas</h6>
                    <div class="form-row">
                        <div class="form-group col-md-8">
                            <label for="nama">Nama Obat <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nama" name="nama" maxlength="191" required>
                            <div id="namaCheck" class="mt-2"></div>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="kode_obat">Kode Obat</label>
                            <input type="text" class="form-control bg-light" id="kode_obat" readonly tabindex="-1" placeholder="Otomatis setelah kategori diisi">
                            <small class="form-text text-muted">Dibuat otomatis dari kategori.</small>
                        </div>
                    </div>

                    <h6 class="form-section">Klasifikasi</h6>
                    <div class="form-row">
                        <div class="form-group col-md-3">
                            <label for="kategori">Kategori <span class="text-danger">*</span></label>
                            <select class="form-control modal-select" id="kategori" name="kategori" required>
                                <option value="">Pilih</option>
                                @foreach($kategoris as $kategori)
                                    <option value="{{ $kategori }}">{{ $kategori }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-3">
                            <label for="metode_bayar_id">Metode Bayar <span class="text-danger">*</span></label>
                            <select class="form-control modal-select" id="metode_bayar_id" name="metode_bayar_id" required>
                                <option value="">Pilih</option>
                                @foreach($metodeBayars as $metodeBayar)
                                    <option value="{{ $metodeBayar->id }}">{{ $metodeBayar->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-3">
                            <label for="is_generik">Jenis</label>
                            <select class="form-control" id="is_generik" name="is_generik">
                                <option value="0">Paten</option>
                                <option value="1">Generik</option>
                            </select>
                        </div>
                        <div class="form-group col-md-3">
                            <label for="status_aktif">Status</label>
                            <select class="form-control" id="status_aktif" name="status_aktif">
                                <option value="1">Aktif</option>
                                <option value="0">Tidak Aktif</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="principal_id">Principal</label>
                            <div class="input-group flex-nowrap">
                                <select class="form-control" id="principal_id" name="principal_id"></select>
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary" type="button" id="btnPrincipalBaru" title="Principal baru"><i class="fas fa-plus"></i></button>
                                </div>
                            </div>
                            <div class="input-group input-group-sm mt-1 d-none" id="principalBaruBox">
                                <input type="text" id="principalBaruNama" class="form-control" placeholder="Nama principal baru, mis. PT KALBE FARMA" maxlength="255">
                                <div class="input-group-append">
                                    <button class="btn btn-success" type="button" id="btnPrincipalBaruSimpan">Simpan</button>
                                    <button class="btn btn-light border" type="button" id="btnPrincipalBaruBatal">Batal</button>
                                </div>
                            </div>
                            <small class="form-text text-muted">Pabrik / pemilik merek. Dipakai di Master Pembelian, permintaan &amp; faktur beli. Belum ada? Klik <i class="fas fa-plus"></i>.</small>
                        </div>
                    </div>

                    <h6 class="form-section">Satuan &amp; Dosis</h6>
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="satuan_stok">Satuan Stok/Jual <span class="text-danger">*</span></label>
                            <select class="form-control modal-select" id="satuan_stok" name="satuan_stok" required>
                                <option value="">Pilih</option>
                                @foreach($satuanStokList as $s)
                                    <option value="{{ $s }}">{{ $s }}</option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted" id="satuan_stok_hint">Satuan untuk stok, HPP &amp; harga jual.</small>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="dosis">Dosis</label>
                            <input type="text" class="form-control" id="dosis" name="dosis" maxlength="191" placeholder="mis. 500">
                            <small class="form-text text-muted">Isi per 1 satuan stok, mis. 500 (mg) per Tablet.</small>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="satuan">Satuan Dosis</label>
                            <select class="form-control modal-select" id="satuan" name="satuan">
                                <option value="">Pilih</option>
                                <optgroup label="Ukuran">
                                    @foreach($satuanDosisList as $s)
                                        <option value="{{ $s }}">{{ $s }}</option>
                                    @endforeach
                                </optgroup>
                                <optgroup label="Per unit">
                                    @foreach($satuanStokList as $s)
                                        <option value="{{ $s }}">{{ $s }}</option>
                                    @endforeach
                                </optgroup>
                            </select>
                            <small class="form-text text-muted" id="kekuatan_preview">&nbsp;</small>
                        </div>
                    </div>

                    <h6 class="form-section">Harga</h6>
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="hpp_display">HPP <small class="text-muted">(tanpa PPN)</small></label>
                            <input type="text" class="form-control bg-light" id="hpp_display" readonly tabindex="-1">
                            <small class="form-text text-muted">Otomatis dari faktur beli terakhir.</small>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="hna">HNA <small class="text-muted">(termasuk PPN)</small></label>
                            {{-- No name: HNA is always HPP + PPN and is not sent with the form --}}
                            <input type="text" class="form-control bg-light" id="hna" readonly tabindex="-1">
                            <small class="form-text text-muted">Otomatis = HPP + PPN {{ \App\Models\ERM\Obat::PPN_PERCENT }}%.</small>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="harga_nonfornas">Harga Jual</label>
                            <input type="text" class="form-control numeric-input" id="harga_nonfornas" name="harga_nonfornas" inputmode="decimal">
                            <small class="form-text" id="harga_jual_hint">&nbsp;</small>
                        </div>
                    </div>

                    <h6 class="form-section">Zat Aktif</h6>
                    <div class="form-group mb-0">
                        <select class="form-control" id="zat_aktif_id" name="zataktif_id[]" multiple></select>
                        <small class="form-text text-muted">Ketik minimal 2 huruf untuk mencari. Belum ada? Tambahkan lewat tombol <em>Kelola Zat Aktif</em>.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btnSimpanObat"><i class="fas fa-save mr-1"></i> Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal: Pilih Kolom Export --}}
<div class="modal fade" id="exportColumnsModal" tabindex="-1" role="dialog" aria-labelledby="exportColumnsModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exportColumnsModalLabel">Export Excel</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
            </div>
            <form id="exportColumnsForm">
                <div class="modal-body">
                    <p class="small text-muted">Data yang diexport sama dengan isi tabel (filter &amp; pencarian yang sedang aktif).</p>
                    <div class="custom-control custom-checkbox mb-2">
                        <input type="checkbox" class="custom-control-input" id="selectAllColumns" checked>
                        <label class="custom-control-label font-weight-bold" for="selectAllColumns">Pilih semua kolom</label>
                    </div>
                    <div class="row">
                        @foreach([
                            'id' => 'ID', 'kode_obat' => 'Kode Obat', 'kode_obat_lama' => 'Kode Lama', 'nama' => 'Nama', 'hpp' => 'HPP',
                            'hna' => 'HNA', 'harga_nonfornas' => 'Harga Jual', 'metode_bayar' => 'Metode Bayar', 'kategori' => 'Kategori',
                            'zat_aktif' => 'Zat Aktif', 'dosis' => 'Dosis', 'satuan' => 'Satuan Dosis', 'satuan_stok' => 'Satuan Stok',
                            'is_generik' => 'Generik', 'status_aktif' => 'Status',
                        ] as $colKey => $colLabel)
                        <div class="col-6">
                            <div class="custom-control custom-checkbox">
                                <input class="custom-control-input column-choice" type="checkbox" id="col-{{ $colKey }}" value="{{ $colKey }}" checked>
                                <label class="custom-control-label" for="col-{{ $colKey }}">{{ $colLabel }}</label>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-file-excel mr-1"></i> Export</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal: Kelola Zat Aktif --}}
<div class="modal fade" id="zatAktifModal" tabindex="-1" role="dialog" aria-labelledby="zatAktifModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="zatAktifModalLabel"><i class="fas fa-pills mr-1"></i> Kelola Zat Aktif</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                {{-- Tambah --}}
                <form id="formAddZat" class="mb-3" autocomplete="off">
                    <label for="newZatNama" class="small text-muted mb-1">Tambah zat aktif baru</label>
                    <div class="input-group">
                        <input type="text" id="newZatNama" class="form-control" placeholder="mis. PARACETAMOL" maxlength="191">
                        <div class="input-group-append">
                            <button class="btn btn-primary" id="btnAddZat" type="submit"><i class="fas fa-plus mr-1"></i> Tambah</button>
                        </div>
                    </div>
                    <small id="newZatFeedback" class="form-text"></small>
                </form>

                {{-- Gabungkan (shown when merging) --}}
                <div id="zatMergePanel" class="alert alert-info d-none">
                    <div class="mb-2"><i class="fas fa-code-branch mr-1"></i> Gabungkan <strong id="zatMergeSource"></strong> ke:</div>
                    <select id="zatMergeTarget" class="form-control"></select>
                    <small class="d-block mt-2">Semua obat dan data alergi pasien yang memakai <strong id="zatMergeSource2"></strong> akan dipindah ke zat aktif tujuan, lalu nama ganda ini dihapus.</small>
                    <div class="mt-2 text-right">
                        <button type="button" class="btn btn-sm btn-light border" id="btnZatMergeCancel">Batal</button>
                        <button type="button" class="btn btn-sm btn-primary" id="btnZatMergeSave"><i class="fas fa-check mr-1"></i> Gabungkan</button>
                    </div>
                </div>

                {{-- Cari & filter --}}
                <div class="form-row">
                    <div class="col-md-7 mb-2">
                        <div class="input-group input-group-sm">
                            <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-search"></i></span></div>
                            <input type="search" id="zatSearch" class="form-control" placeholder="Cari nama zat aktif" autocomplete="off">
                        </div>
                    </div>
                    <div class="col-md-5 mb-2">
                        <select id="zatFilter" class="form-control form-control-sm">
                            <option value="">Semua zat aktif</option>
                            <option value="dipakai">Dipakai obat/alergi</option>
                            <option value="tidak_dipakai">Tidak dipakai</option>
                            <option value="ganda">Nama ganda</option>
                        </select>
                    </div>
                </div>
                <div id="zatDuplicateHint" class="small text-warning mb-2 d-none"></div>

                <table id="zataktif-table" class="table table-sm table-hover w-100">
                    <thead class="thead-light"><tr><th style="width:50px">No</th><th>Nama Zat Aktif</th><th>Dipakai di</th><th class="text-right" style="width:120px">Aksi</th></tr></thead>
                    <tbody></tbody>
                </table>
                <small class="text-muted d-block">
                    Klik jumlah obat untuk melihat obatnya di tabel.
                    @if($canDelete) Hapus hanya untuk zat aktif yang tidak dipakai; nama ganda gunakan <em>Gabungkan</em>. @endif
                </small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal: Kelola Principal (pemasok is managed on Master Pembelian) --}}
@include('erm.partials.supplier-manager', [
    'key' => 'principal', 'label' => 'Principal', 'subtitle' => 'pabrik / pemilik merek', 'placeholder' => 'PT KALBE FARMA',
    'canDelete' => $canDelete, 'obatHint' => 'Klik jumlah obat untuk melihat obatnya di tabel.',
])

{{-- Modal: Import CSV --}}
<div class="modal fade" id="importCsvModal" tabindex="-1" role="dialog" aria-labelledby="importCsvModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importCsvModalLabel">Import CSV — Update Obat berdasarkan ID</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
            </div>
            <form id="importCsvForm" enctype="multipart/form-data" onsubmit="return false;">
                <div class="modal-body">
                    <ol class="small text-muted pl-3">
                        <li>Export Excel, ubah datanya, lalu simpan sebagai <strong>CSV</strong> (pemisah <strong>,</strong> atau <strong>;</strong>). Kolom <strong>ID</strong> wajib ada.</li>
                        <li>Kolom yang bisa diubah: Nama, Dosis, Satuan Dosis, Satuan Stok, Generik (1/0, Ya/Tidak), Kategori, Metode Bayar (nama), HNA, Harga Jual. Sel kosong = tidak diubah.</li>
                        <li>Kolom lain (HPP, Kode Obat, Zat Aktif, Status) diabaikan. Klik <strong>Preview</strong>, cek perubahannya, lalu <strong>Import</strong>.</li>
                    </ol>
                    <div class="form-group">
                        <input type="file" name="csv_file" id="csv_file" accept=".csv,text/csv" class="form-control-file" required>
                    </div>
                    <div id="importPreviewArea"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="button" id="btnPreviewCsv" class="btn btn-info"><i class="fas fa-eye mr-1"></i> Preview</button>
                    <button type="button" id="btnConfirmImport" class="btn btn-primary" disabled><i class="fas fa-file-import mr-1"></i> Import</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<style>
    .obat-page .filter-bar label { font-weight: 600; }
    /* .page-wrapper is a flex item (flex: 1), so by default it grows to fit a wide table (e.g. 100 rows with
       long lists) and pushes the whole page past the screen. min-width: 0 keeps it at the screen width,
       so only the DataTables scroll body scrolls sideways. */
    .page-wrapper { min-width: 0; }
    #obat-table tbody tr { cursor: pointer; }
    #obat-table td, #obat-table th { vertical-align: middle; white-space: nowrap; }
    #obat-table td.col-nama { white-space: normal; min-width: 240px; max-width: 360px; }
    #obat-table .cell-list { margin: 0; padding-left: 16px; }
    #obat-table .cell-list li { line-height: 1.35; }
    #obat-table .obat-nama { font-weight: 600; }
    #obat-table .obat-meta { font-size: 0.8rem; color: #6c757d; }
    #obat-table .badge { font-weight: 500; }
    #obat-table tr.row-inactive td { background-color: #fdeeee; color: #6c757d; }
    .badge-zat { background: #e7f1ff; color: #0b5ed7; font-weight: 500; margin: 1px; }
    .badge-kat-obat { background: #0d6efd; color: #fff; }
    .badge-kat-produk { background: #d63384; color: #fff; }
    .badge-kat-racikan { background: #ffc107; color: #212529; }
    .badge-kat-bhp { background: #20c997; color: #fff; }
    .badge-kat-bhp-alat { background: #198754; color: #fff; }
    .badge-kat-lainnya { background: #6c757d; color: #fff; }
    .badge-missing { background: #fff3cd; color: #856404; border: 1px solid #ffe08a; }
    .chip-incomplete { margin: 2px 4px 2px 0; }
    .form-section { font-size: .78rem; text-transform: uppercase; letter-spacing: .04em; color: #6c757d; border-bottom: 1px solid #e9ecef; padding-bottom: 4px; margin: 14px 0 10px; }
    .form-section:first-of-type { margin-top: 0; }
    #importPreviewArea { display: none; margin-top: 12px; }
    #importPreviewArea .csv-preview-scroll { max-height: 55vh; overflow: auto; }
    #importPreviewArea table { min-width: 1400px; }
    #importPreviewArea thead th { position: sticky; top: 0; z-index: 2; background: #f8f9fa; }
    #importCsvModal .table td, #importCsvModal .table th { white-space: normal; }
    /* Selected zat aktif chips: the theme makes chip text white, so set a readable dark-on-light pair */
    #obatModal .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background: #e7f1ff !important;
        border: 1px solid #b6d4fe !important;
        color: #084298 !important;
        font-weight: 500;
        padding: 2px 8px;
    }
    #obatModal .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
        color: #084298 !important;
        margin-right: 4px;
    }
    #obatModal .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover { color: #dc3545 !important; }
    /* Principal select + "+" button side by side */
    #obatModal .input-group > .select2-container { flex: 1 1 auto; width: 1% !important; }
</style>
@include('erm.partials.supplier-manager-js')
<script>
(function () {
    'use strict';

    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

    const CAN_DELETE = @json($canDelete);
    const FILTER_KOSONG = @json(\App\Http\Controllers\ERM\ObatController::FILTER_KOSONG);
    const PPN_PERCENT = @json(\App\Models\ERM\Obat::PPN_PERCENT);
    const URLS = {
        index: @json(route('erm.obat.index')),
        exportExcel: @json(route('erm.obat.export-excel')),
        importPreview: @json(route('erm.obat.import_csv_preview')),
        importCsv: @json(route('erm.obat.import_csv')),
        checkNama: @json(route('erm.obat.check-nama')),
        zatAktif: '/erm/ajax/zataktif',
        principalOptions: '/erm/ajax/principal', // [{id, text}] for the form select
        principal: '/erm/principal'
    };
    const FILTER_STORAGE_KEY = 'erm.obat.index.filters';
    const PRICE_FIELDS = ['harga_nonfornas'];
    const KATEGORI_CLASS = { 'obat': 'badge-kat-obat', 'produk': 'badge-kat-produk', 'racikan': 'badge-kat-racikan', 'bhp': 'badge-kat-bhp', 'bhp alat': 'badge-kat-bhp-alat', 'lainnya': 'badge-kat-lainnya' };

    let table = null;
    let editRequest = null;
    let supplierFilter = null; // { param: 'principal_id', id, nama, label }

    // ---------- helpers ----------
    function escapeHtml(value) {
        if (value === null || value === undefined) return '';
        return String(value).replace(/[&<>"'`=\/]/g, function (s) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;', '/': '&#x2F;', '`': '&#x60;', '=': '&#x3D;' }[s];
        });
    }

    function notify(icon, title, html) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({ icon: icon, title: title, html: html || '', timer: icon === 'success' ? 1800 : undefined, showConfirmButton: icon !== 'success' });
        } else {
            alert(title + (html ? '\n' + $('<div>').html(html).text() : ''));
        }
    }

    function confirmDialog(title, text) {
        if (typeof Swal !== 'undefined') {
            return Swal.fire({ icon: 'warning', title: title, text: text, showCancelButton: true, confirmButtonText: 'Ya', cancelButtonText: 'Batal', confirmButtonColor: '#dc3545' })
                .then(function (r) { return !!(r.value || r.isConfirmed); });
        }
        return Promise.resolve(confirm(title + (text ? '\n' + text : '')));
    }

    function ajaxErrorHtml(xhr, fallback) {
        const json = xhr.responseJSON;
        if (json && json.errors) {
            return Object.keys(json.errors).map(function (k) { return escapeHtml(json.errors[k].join(', ')); }).join('<br>');
        }
        if (json && json.message) return escapeHtml(json.message);
        return fallback;
    }

    function formatRupiah(value) {
        if (value === null || value === undefined || value === '') return '-';
        const num = parseFloat(value);
        if (isNaN(num)) return '-';
        const decimals = Math.round(num * 100) % 100 === 0 ? 0 : 2;
        return 'Rp ' + num.toLocaleString('id-ID', { minimumFractionDigits: decimals, maximumFractionDigits: decimals });
    }

    function formatNumber(value) {
        const num = parseFloat(value) || 0;
        return num.toLocaleString('id-ID', { maximumFractionDigits: 2 });
    }

    // "Rp 1.500.000" / "1.234,56" / "1234.56" -> "1500000" / "1234.56"
    function normalizeNumericInput(value) {
        const raw = (value || '').toString().replace(/[^0-9.,-]/g, '').trim();
        if (!raw) return '';
        const lastComma = raw.lastIndexOf(','), lastDot = raw.lastIndexOf('.');
        const decimalIndex = Math.max(lastComma, lastDot);
        if (decimalIndex === -1) return raw.replace(/[.,]/g, '');
        // A single separator followed by 3+ digits is a thousands separator (1.500 / 1,500)
        if ((lastComma === -1 || lastDot === -1) && raw.length - decimalIndex - 1 > 2) return raw.replace(/[.,]/g, '');
        let integerPart = raw.slice(0, decimalIndex).replace(/[.,]/g, '');
        const decimalPart = raw.slice(decimalIndex + 1).replace(/[.,]/g, '');
        if (!integerPart && decimalPart) integerPart = '0';
        return decimalPart ? integerPart + '.' + decimalPart : integerPart;
    }

    function storageGet(key) { try { return JSON.parse(window.localStorage.getItem(key) || 'null'); } catch (e) { return null; } }
    function storageSet(key, value) { try { window.localStorage.setItem(key, JSON.stringify(value)); } catch (e) { /* private mode */ } }

    // Disable a button while its request runs (no overlay), restore the label afterwards
    function busy($btn, label) {
        $btn.data('label', $btn.data('label') || $btn.html()).prop('disabled', true).text(label || 'Memproses...');
    }
    function idle($btn) {
        $btn.prop('disabled', false).html($btn.data('label'));
    }

    // ---------- select2 ----------
    function zatAktifAjax() {
        return {
            url: URLS.zatAktif,
            dataType: 'json',
            delay: 250,
            data: function (params) {
                const page = params.page || 1;
                return { 'search[value]': params.term || '', start: (page - 1) * 20, length: 20, 'order[0][column]': 1, 'order[0][dir]': 'asc' };
            },
            processResults: function (res, params) {
                const page = params.page || 1;
                return {
                    results: (res.data || []).map(function (z) { return { id: z.id, text: z.nama }; }),
                    pagination: { more: page * 20 < (res.recordsFiltered || 0) }
                };
            }
        };
    }

    function initSelects() {
        $('.filter-select').select2({ width: '100%', minimumResultsForSearch: 8 });
        $('#filter_kandungan').select2({ width: '100%', allowClear: true, placeholder: 'Semua', minimumInputLength: 2, ajax: zatAktifAjax() });

        // dropdownParent keeps the search box usable inside the Bootstrap modal
        $('.modal-select').select2({ width: '100%', dropdownParent: $('#obatModal'), minimumResultsForSearch: 8 });
        $('#zat_aktif_id').select2({
            width: '100%', dropdownParent: $('#obatModal'), placeholder: 'Cari zat aktif...', minimumInputLength: 2, ajax: zatAktifAjax()
        });
        $('#principal_id').select2({
            width: '100%', dropdownParent: $('#obatModal'), placeholder: 'Cari principal...', allowClear: true, minimumInputLength: 1,
            ajax: {
                url: URLS.principalOptions, dataType: 'json', delay: 250,
                data: function (params) { return { q: params.term }; },
                processResults: function (data) { return { results: data }; }
            }
        });
    }

    // ---------- filters ----------
    const FILTER_FIELDS = {
        kategori: '#filter_kategori', metode_bayar_id: '#filter_metode_bayar', satuan_stok: '#filter_satuan_stok',
        is_generik: '#filter_paten', status_aktif: '#filter_status', kelengkapan: '#filter_kelengkapan'
    };

    function currentFilters() {
        const f = {};
        Object.keys(FILTER_FIELDS).forEach(function (k) { f[k] = $(FILTER_FIELDS[k]).val() || ''; });
        f.zataktif_id = $('#filter_kandungan').val() || '';
        if (supplierFilter) f[supplierFilter.param] = supplierFilter.id;
        return f;
    }

    function saveFilters() {
        const f = currentFilters();
        const kandunganText = $('#filter_kandungan').find('option:selected').text();
        storageSet(FILTER_STORAGE_KEY, { filters: f, kandunganText: kandunganText, search: $('#filter_search').val() || '' });
    }

    function setFilters(values, kandungan) {
        Object.keys(FILTER_FIELDS).forEach(function (k) {
            $(FILTER_FIELDS[k]).val(values[k] !== undefined ? values[k] : '').trigger('change.select2');
        });
        const $k = $('#filter_kandungan').empty();
        if (kandungan && kandungan.id) {
            $k.append(new Option(kandungan.text, kandungan.id, true, true));
        }
        $k.trigger('change.select2');
    }

    function restoreFilters() {
        const saved = storageGet(FILTER_STORAGE_KEY);
        if (!saved || !saved.filters) return;
        setFilters(saved.filters, saved.filters.zataktif_id ? { id: saved.filters.zataktif_id, text: saved.kandunganText } : null);
        $('#filter_search').val(saved.search || '');
    }

    function updateActiveFilterInfo() {
        const f = currentFilters();
        const active = Object.keys(f).filter(function (k) { return f[k] !== '' && !(k === 'status_aktif' && f[k] === '1'); }).length
            + ($('#filter_search').val() ? 1 : 0);
        $('#activeFilterInfo').text(active ? active + ' filter aktif' : '');
        $('#btnResetFilter').toggleClass('btn-warning', active > 0).toggleClass('btn-light', active === 0);
    }

    function reloadTable(resetPaging) {
        saveFilters();
        updateActiveFilterInfo();
        table.ajax.reload(null, resetPaging !== false);
    }

    // ---------- data quality summary ----------
    const INCOMPLETE_CHIPS = [
        { key: 'kategori', label: 'Kategori kosong', apply: { kategori: FILTER_KOSONG } },
        { key: 'metode_bayar', label: 'Metode bayar kosong', apply: { metode_bayar_id: FILTER_KOSONG } },
        { key: 'satuan_stok', label: 'Satuan stok kosong', apply: { satuan_stok: FILTER_KOSONG } },
        { key: 'harga_jual', label: 'Harga jual kosong', apply: { kelengkapan: 'harga_jual' } },
        { key: 'hpp', label: 'Belum ada HPP', apply: { kelengkapan: 'hpp' } }
    ];

    function loadSummary() {
        $.get(URLS.index, { summary: 1 }).done(function (res) {
            const chips = INCOMPLETE_CHIPS.filter(function (c) { return res[c.key] > 0; }).map(function (c) {
                return '<button type="button" class="btn btn-sm btn-outline-dark chip-incomplete" data-chip="' + c.key + '">' +
                    escapeHtml(c.label) + ' <span class="badge badge-dark">' + formatNumber(res[c.key]) + '</span></button>';
            });
            $('#incompleteChips').html(chips.join(''));
            $('#incompleteBox').toggleClass('d-none', chips.length === 0);
        });
    }

    $(document).on('click', '.chip-incomplete', function () {
        const key = $(this).data('chip');
        const chip = INCOMPLETE_CHIPS.find(function (c) { return c.key === key; });
        if (!chip) return;
        $('#filter_search').val('');
        table.search('');
        setFilters($.extend({ status_aktif: '1' }, chip.apply), null);
        reloadTable();
    });

    // ---------- table ----------
    const MISSING = function (label) { return '<span class="badge badge-missing">' + label + '</span>'; };

    // kode_obat is generated from kategori; without kategori there is no code yet
    function renderKodeCell(data, type, row) {
        if (type !== 'display') return data;
        if (row.kode_obat) return '<span class="font-weight-bold">' + escapeHtml(row.kode_obat) + '</span>';
        return '<span class="text-muted" title="Kode dibuat otomatis setelah kategori diisi">-</span>';
    }

    function renderNamaCell(data, type, row) {
        if (type !== 'display') return data;
        const inactive = String(row.status_aktif) === '0' ? '<div class="mt-1"><span class="badge badge-danger">Tidak Aktif</span></div>' : '';
        return '<div class="obat-nama">' + escapeHtml(row.nama) + '</div>' + inactive;
    }

    function renderKategoriCell(data, type) {
        if (type !== 'display') return data;
        if (!data) return MISSING('Belum diisi');
        return '<span class="badge ' + (KATEGORI_CLASS[String(data).toLowerCase()] || 'badge-secondary') + '">' + escapeHtml(data) + '</span>';
    }

    function renderJenisCell(data, type, row) {
        const generik = row.is_generik === true || String(row.is_generik) === '1';
        if (type !== 'display') return generik ? 'Generik' : 'Paten';
        return generik ? '<span class="badge badge-success">Generik</span>' : '<span class="badge badge-info">Paten</span>';
    }

    function renderMetodeCell(data, type) {
        if (type !== 'display') return data;
        if (!data) return MISSING('Belum diisi');
        return '<span class="badge ' + (String(data).toLowerCase() === 'umum' ? 'badge-success' : 'badge-primary') + '">' + escapeHtml(data) + '</span>';
    }

    function renderSatuanStokCell(data, type, row) {
        if (type !== 'display') return data;
        return data ? escapeHtml(data) : MISSING('Belum diisi');
    }

    function renderDosisCell(data, type, row) {
        if (type !== 'display') return data;
        return row.dosis ? escapeHtml(row.dosis + (row.satuan ? ' ' + row.satuan : '')) : '<span class="text-muted">-</span>';
    }

    // One value: plain text. More than one: a list, one item per line.
    function renderList(items) {
        if (!Array.isArray(items)) items = items && typeof items === 'object' ? Object.values(items) : [];
        if (!items.length) return '<span class="text-muted">-</span>';
        if (items.length === 1) return escapeHtml(items[0]);
        return '<ul class="cell-list">' + items.map(function (n) { return '<li>' + escapeHtml(n) + '</li>'; }).join('') + '</ul>';
    }

    function listColumn(data, type) {
        if (type !== 'display') return Array.isArray(data) ? data.join(', ') : data;
        return renderList(data);
    }

    // Delete is only rendered for Admin; the server also refuses it for everyone else (403)
    function renderActions(data, type, row) {
        let html = '<div class="btn-group btn-group-sm" role="group">' +
            '<button type="button" class="btn btn-outline-primary btn-edit-obat" data-id="' + row.id + '" title="Edit"><i class="fas fa-edit"></i></button>';
        if (CAN_DELETE) {
            html += '<button type="button" class="btn btn-outline-danger btn-delete-obat" data-id="' + row.id + '" title="Hapus"><i class="fas fa-trash"></i></button>';
        }
        return html + '</div>';
    }

    function initTable() {
        table = $('#obat-table').DataTable({
            processing: true,
            serverSide: true,
            search: { search: ($('#filter_search').val() || '').trim() },
            pageLength: 25,
            lengthMenu: [10, 25, 50, 100],
            order: [[1, 'asc']],
            scrollX: true,
            autoWidth: false,
            dom: "rt<'row align-items-center mt-2'<'col-md-4'l><'col-md-4 text-center'i><'col-md-4'p>>",
            language: {
                lengthMenu: 'Tampilkan _MENU_ baris',
                info: '_START_–_END_ dari _TOTAL_ obat',
                infoEmpty: 'Tidak ada data',
                infoFiltered: '',
                zeroRecords: 'Tidak ada obat yang cocok dengan filter/pencarian.',
                emptyTable: 'Belum ada data obat.',
                paginate: { previous: '&lsaquo;', next: '&rsaquo;' }
            },
            ajax: {
                url: URLS.index,
                data: function (d) { $.extend(d, currentFilters()); },
                error: function (xhr) { notify('error', 'Gagal memuat data obat', ajaxErrorHtml(xhr, 'Silakan muat ulang halaman.')); }
            },
            createdRow: function (row, data) {
                if (String(data.status_aktif) === '0') $(row).addClass('row-inactive');
                $(row).attr('data-id', data.id);
            },
            columns: [
                { data: 'kode_obat', name: 'kode_obat', render: renderKodeCell },
                { data: 'nama', name: 'nama', className: 'col-nama', render: renderNamaCell },
                { data: 'kategori', name: 'kategori', searchable: false, render: renderKategoriCell },
                { data: 'is_generik', name: 'is_generik', searchable: false, render: renderJenisCell },
                { data: 'metode_bayar', name: 'metode_bayar_id', orderable: false, searchable: false, render: renderMetodeCell },
                { data: 'satuan_stok', name: 'satuan_stok', searchable: false, render: renderSatuanStokCell },
                { data: 'dosis', name: 'dosis', orderable: false, searchable: false, render: renderDosisCell },
                { data: 'zat_aktif', name: 'zat_aktif', orderable: false, searchable: false, render: listColumn },
                { data: 'principal', name: 'principal', orderable: false, searchable: false, render: listColumn },
                { data: 'distributor', name: 'distributor', orderable: false, searchable: false, render: listColumn },
                { data: null, orderable: false, searchable: false, className: 'text-center', render: renderActions }
            ]
        });
    }

    // ---------- form ----------
    const SATUAN_STOK_GUESS = [
        [/\bKAPLET\b/, 'Kaplet'], [/\bTAB(LET)?\b/, 'Tablet'], [/\b(KAP(SUL)?|CAPS?)\b/, 'Kapsul'],
        [/\b(SIRUP|SYRUP|SYR|DROPS?|SUSP(ENSI)?|ELIXIR)\b/, 'Botol'], [/\b(CREAM|KRIM|GEL|SALEP|OINTMENT|OINT|ZALF)\b/, 'Tube'],
        [/\b(AMP(UL)?|AMPOULE)\b/, 'Ampul'], [/\bVIAL\b/, 'Vial'], [/\b(SACHET|SACH|SACHE)\b/, 'Sachet'], [/\bSTRIP\b/, 'Strip'],
        [/\bPEN\b/, 'Pen'], [/\bSOFTBAG\b/, 'Softbag'], [/\bBOX\b/, 'Box']
    ];

    function guessSatuanStok(nama) {
        const upper = String(nama || '').toUpperCase();
        const hit = SATUAN_STOK_GUESS.find(function (g) { return g[0].test(upper); });
        return hit ? hit[1] : null;
    }

    function setSatuanStokHint(isSuggestion) {
        $('#satuan_stok_hint')
            .toggleClass('text-warning font-weight-bold', isSuggestion)
            .toggleClass('text-muted', !isSuggestion)
            .html(isSuggestion ? 'Saran dari nama obat — cek sebelum simpan.' : 'Satuan untuk stok, HPP &amp; harga jual.');
    }

    function updateKekuatanPreview() {
        const dosis = ($('#dosis').val() || '').trim();
        const satuan = $('#satuan').val() || '';
        const stok = $('#satuan_stok').val() || '';
        $('#kekuatan_preview').text(dosis && satuan && stok ? dosis + ' ' + satuan + ' per 1 ' + stok : ' ');
    }

    function updateHargaJualHint() {
        const hpp = parseFloat($('#hpp_display').data('raw')) || 0;
        const jual = parseFloat(normalizeNumericInput($('#harga_nonfornas').val())) || 0;
        const $hint = $('#harga_jual_hint').removeClass('text-danger text-success text-muted');
        if (!hpp || !jual) { $hint.addClass('text-muted').html('&nbsp;'); return; }
        const markup = ((jual - hpp) / hpp) * 100;
        $hint.addClass(markup < 0 ? 'text-danger' : 'text-success')
            .text((markup < 0 ? 'Di bawah HPP: ' : 'Markup dari HPP: ') + markup.toLocaleString('id-ID', { maximumFractionDigits: 1 }) + '%');
    }

    function resetForm() {
        const $form = $('#formObat');
        $form[0].reset();
        $('#formErrors').addClass('d-none').empty();
        clearTimeout(namaTimer);
        namaCheck = { key: '', result: null, request: null };
        $('#namaCheck').empty();
        $form.find('.is-invalid').removeClass('is-invalid');
        $('#obat_id').val('');
        $('#kode_obat').val('');
        $('#hpp_display').val('-').data('raw', 0);
        $('.modal-select').val('').trigger('change.select2');
        $('#zat_aktif_id').empty().trigger('change');
        $('#principal_id').empty().trigger('change.select2');
        $('#principalBaruBox').addClass('d-none');
        $('#principalBaruNama').val('');
        $('#is_generik').val('0');
        $('#status_aktif').val('1');
        setSatuanStokHint(false);
        updateKekuatanPreview();
        updateHargaJualHint();
    }

    function openTambahObat() {
        resetForm();
        $('#obatModalLabel').text('Tambah Obat');
        $('#obatModal').modal('show');
    }

    function openEditObat(id) {
        if (editRequest) return; // ignore double clicks while loading
        editRequest = $.get('/erm/obat/' + id + '/edit')
            .done(function (data) {
                resetForm();
                $('#obatModalLabel').text('Edit Obat');
                $('#obat_id').val(data.id);
                $('#kode_obat').val(data.kode_obat || '');
                $('#nama').val(data.nama);
                $('#hpp_display').val(formatRupiah(data.hpp)).data('raw', data.hpp || 0);
                $('#hna').val(data.hna !== null ? formatRupiah(data.hna) : '');
                $('#harga_nonfornas').val(data.harga_nonfornas !== null ? formatRupiah(data.harga_nonfornas) : '');
                $('#kategori').val(data.kategori || '').trigger('change.select2');
                $('#metode_bayar_id').val(data.metode_bayar_id || '').trigger('change.select2');
                $('#satuan').val(data.satuan || '').trigger('change.select2');
                const suggested = data.satuan_stok ? null : guessSatuanStok(data.nama);
                $('#satuan_stok').val(data.satuan_stok || suggested || '').trigger('change.select2');
                setSatuanStokHint(!!suggested);
                $('#dosis').val(data.dosis || '');
                $('#is_generik').val(data.is_generik ? '1' : '0');
                if (data.principal_id) $('#principal_id').append(new Option(data.principal_nama, data.principal_id, true, true)).trigger('change.select2');
                $('#status_aktif').val(String(data.status_aktif === null || data.status_aktif === undefined ? 1 : data.status_aktif));
                const $zat = $('#zat_aktif_id');
                (data.zataktif || []).forEach(function (z) { $zat.append(new Option(z.nama, z.id, true, true)); });
                $zat.trigger('change');
                updateKekuatanPreview();
                updateHargaJualHint();
                $('#obatModal').modal('show');
            })
            .fail(function (xhr) { notify('error', 'Gagal mengambil data obat', ajaxErrorHtml(xhr, '')); })
            .always(function () { editRequest = null; });
    }

    // "+" next to Principal: add a principal without leaving the form; an existing name is just selected
    function addPrincipalInline() {
        const $input = $('#principalBaruNama');
        const nama = ($input.val() || '').trim();
        if (!nama) { $input.trigger('focus'); return; }
        const select = function (p) {
            $('#principal_id').empty().append(new Option(p.nama, p.id, true, true)).trigger('change.select2');
            $input.val('');
            $('#principalBaruBox').addClass('d-none');
        };
        const $btn = $('#btnPrincipalBaruSimpan');
        busy($btn, '...');
        $.post(URLS.principal, { nama: nama })
            .done(function (res) { select(res.data); })
            .fail(function (xhr) {
                const ex = xhr.responseJSON && xhr.responseJSON.existing;
                if (ex) {
                    select(ex);
                    notify('info', 'Sudah ada', escapeHtml(ex.nama) + ' sudah terdaftar dan dipilih.');
                    return;
                }
                notify('error', 'Gagal menambahkan principal', ajaxErrorHtml(xhr, ''));
            })
            .always(function () { idle($btn); });
    }

    function showFormErrors(xhr) {
        const json = xhr.responseJSON || {};
        $('#formObat .is-invalid').removeClass('is-invalid');
        if (json.errors) {
            Object.keys(json.errors).forEach(function (field) {
                const name = field.replace(/\.\d+$/, '');
                const $el = $('#formObat [name="' + name + '"], #formObat [name="' + name + '[]"]');
                $el.addClass('is-invalid').next('.select2').find('.select2-selection').addClass('is-invalid');
            });
        }
        $('#formErrors').html(ajaxErrorHtml(xhr, 'Gagal menyimpan data.')).removeClass('d-none');
        $('#obatModal .modal-body').scrollTop(0);
    }

    // ---------- duplicate / similar name warning ----------
    let namaCheck = { key: '', result: null, request: null };
    let namaTimer = null;

    function namaKey() {
        return ($('#obat_id').val() || '') + '|' + ($('#nama').val() || '').trim().toUpperCase();
    }

    // Resolves with { exact, matches } for the current name; reuses the last answer for the same name
    function checkNama() {
        const key = namaKey();
        const nama = ($('#nama').val() || '').trim();
        if (nama.replace(/[^A-Za-z0-9]/g, '').length < 3) {
            namaCheck = { key: key, result: { exact: false, matches: [] }, request: null };
            renderNamaCheck(namaCheck.result);
            return $.Deferred().resolve(namaCheck.result).promise();
        }
        if (namaCheck.key === key && namaCheck.result) return $.Deferred().resolve(namaCheck.result).promise();
        if (namaCheck.key === key && namaCheck.request) return namaCheck.request;

        const request = $.get(URLS.checkNama, { nama: nama, exclude_id: $('#obat_id').val() || '' })
            .then(function (res) {
                if (namaCheck.key === key) { // ignore answers for a name the user already changed
                    namaCheck.result = res;
                    renderNamaCheck(res);
                }
                return res;
            }, function () { return { exact: false, matches: [] }; });
        namaCheck = { key: key, result: null, request: request };
        return request;
    }

    function renderNamaCheck(res) {
        const $box = $('#namaCheck');
        if (!res || !res.matches || !res.matches.length) { $box.empty(); return; }
        const items = res.matches.map(function (m) {
            const info = [m.kategori, String(m.status_aktif) === '0' ? 'Tidak Aktif' : ''].filter(Boolean).join(', ');
            return '<li>' + (m.kode_obat ? '<strong>' + escapeHtml(m.kode_obat) + '</strong> · ' : '') + escapeHtml(m.nama) +
                (info ? ' <span class="text-muted">(' + escapeHtml(info) + ')</span>' : '') +
                (m.exact ? ' <span class="badge badge-danger">sama</span>' : '') + '</li>';
        }).join('');
        const title = res.exact
            ? '<i class="fas fa-exclamation-circle mr-1"></i> Nama obat ini sudah ada:'
            : '<i class="fas fa-exclamation-triangle mr-1"></i> Ada nama obat yang mirip, pastikan bukan obat yang sama:';
        $box.html('<div class="alert ' + (res.exact ? 'alert-danger' : 'alert-warning') + ' py-2 px-3 mb-0 small">' + title +
            '<ul class="mb-0 pl-3 mt-1">' + items + '</ul></div>');
    }

    function submitForm(e) {
        e.preventDefault();
        const form = this;
        const $btn = $('#btnSimpanObat').prop('disabled', true);

        // Same name already exists: warn and let the user decide (not blocked).
        // Promise.resolve: chain on a native promise so the confirm dialog is awaited on any jQuery version.
        Promise.resolve(checkNama()).then(function (res) {
            if (!res || !res.exact) return true;
            return confirmDialog('Nama obat sudah ada', 'Obat dengan nama yang sama sudah terdaftar. Tetap simpan?');
        }).then(function (ok) {
            if (!ok) { $btn.prop('disabled', false); return; }
            saveObat(form, $btn);
        });
    }

    function saveObat(form, $btn) {
        const id = $('#obat_id').val();
        const formData = $(form).serializeArray().map(function (item) {
            if (PRICE_FIELDS.indexOf(item.name) !== -1) item.value = normalizeNumericInput(item.value);
            return item;
        });
        // A cleared principal select sends nothing; send it empty so the principal is removed
        if (!formData.some(function (item) { return item.name === 'principal_id'; })) formData.push({ name: 'principal_id', value: '' });
        $.ajax({ url: id ? '/erm/obat/' + id : '/erm/obat', type: id ? 'PUT' : 'POST', data: $.param(formData) })
            .done(function (res) {
                $('#obatModal').modal('hide');
                table.ajax.reload(null, false);
                loadSummary();
                notify('success', res.message || 'Data obat tersimpan');
            })
            .fail(showFormErrors)
            .always(function () { $btn.prop('disabled', false); });
    }

    // ---------- delete ----------

    function deleteObat(id, nama) {
        confirmDialog('Hapus obat ini?', nama || '').then(function (ok) {
            if (!ok) return;
            $.ajax({ url: '/erm/obat/' + id, type: 'DELETE' })
                .done(function (res) {
                    notify('success', res.message || 'Obat dihapus');
                    table.ajax.reload(null, false);
                    loadSummary();
                })
                .fail(function (xhr) { notify('error', 'Tidak bisa menghapus', ajaxErrorHtml(xhr, 'Terjadi kesalahan.')); });
        });
    }

    // ---------- export ----------
    function submitExport(e) {
        e.preventDefault();
        const selected = $('.column-choice:checked').map(function () { return $(this).val(); }).get();
        if (!selected.length) { notify('warning', 'Pilih minimal satu kolom.'); return; }
        const params = new URLSearchParams();
        const filters = currentFilters();
        Object.keys(filters).forEach(function (k) { if (filters[k] !== '') params.append(k, filters[k]); });
        const q = ($('#filter_search').val() || '').trim();
        if (q) params.append('q', q);
        selected.forEach(function (c) { params.append('columns[]', c); });
        window.open(URLS.exportExcel + '?' + params.toString(), '_blank');
        $('#exportColumnsModal').modal('hide');
    }

    // ---------- import ----------
    function csvFormData() {
        const file = document.getElementById('csv_file').files[0];
        if (!file) return null;
        const fd = new FormData();
        fd.append('csv_file', file);
        return fd;
    }

    function renderImportPreview(res) {
        const rows = res.rows || [], columns = res.columns || [], metodeNames = res.metode_bayar || {};
        const $area = $('#importPreviewArea');
        if (!rows.length) {
            $area.html('<div class="alert alert-warning">Tidak ada baris data dalam file.</div>').show();
            $('#btnConfirmImport').prop('disabled', true);
            return;
        }
        function display(col, value) {
            if (value === null || value === undefined || value === '') return '';
            if (col.type === 'boolean') return String(value) === '1' ? 'Ya' : 'Tidak';
            if (col.type === 'metode_bayar') return escapeHtml(metodeNames[value] || value);
            if (col.type === 'decimal') return escapeHtml(formatRupiah(value));
            return escapeHtml(value);
        }
        const changedCount = rows.filter(function (r) { return r.changes && !(r.errors || []).length; }).length;
        const errorCount = rows.filter(function (r) { return (r.errors || []).length; }).length;

        let html = '<div class="mb-2"><strong>' + rows.length + ' baris</strong> — <span class="text-success">' + changedCount + ' akan diperbarui</span>' +
            (errorCount ? ', <span class="text-danger">' + errorCount + ' bermasalah (dilewati)</span>' : '') + '</div>';
        if (res.ignored_columns && res.ignored_columns.length) {
            html += '<div class="small text-muted mb-2">Kolom diabaikan: ' + res.ignored_columns.map(escapeHtml).join(', ') + '</div>';
        }
        html += '<div class="table-responsive csv-preview-scroll"><table class="table table-sm table-bordered"><thead><tr><th>Baris</th><th>ID</th><th>Masalah</th>';
        columns.forEach(function (c) { html += '<th>' + escapeHtml(c.label) + ' (lama)</th><th>' + escapeHtml(c.label) + ' (baru)</th>'; });
        html += '</tr></thead><tbody>';
        rows.forEach(function (r) {
            const errors = r.errors || [];
            html += '<tr class="' + (errors.length ? 'table-danger' : (r.changes ? '' : 'table-secondary')) + '"><td>' + r.line + '</td><td>' + escapeHtml(r.id) + '</td>' +
                '<td class="text-danger small">' + errors.map(escapeHtml).join('<br>') + '</td>';
            columns.forEach(function (c) {
                const changed = r.changed && Object.prototype.hasOwnProperty.call(r.changed, c.key);
                const newHtml = display(c, r.new ? r.new[c.key] : null);
                html += '<td>' + display(c, r.existing ? r.existing[c.key] : null) + '</td><td>' + (changed ? '<span class="badge badge-warning">' + newHtml + '</span>' : newHtml) + '</td>';
            });
            html += '</tr>';
        });
        $area.html(html + '</tbody></table></div>').show();
        $('#btnConfirmImport').prop('disabled', changedCount === 0);
    }

    function previewImport() {
        const fd = csvFormData();
        if (!fd) { notify('warning', 'Pilih file CSV terlebih dahulu'); return; }
        const $btn = $('#btnPreviewCsv');
        busy($btn, 'Menganalisis...');
        $.ajax({ url: URLS.importPreview, method: 'POST', data: fd, processData: false, contentType: false })
            .done(renderImportPreview)
            .fail(function (xhr) { notify('error', 'Gagal menganalisis file', ajaxErrorHtml(xhr, xhr.statusText)); })
            .always(function () { idle($btn); });
    }

    function confirmImport() {
        const fd = csvFormData();
        if (!fd) { notify('warning', 'File tidak ditemukan'); return; }
        confirmDialog('Terapkan perubahan?', 'Perubahan yang terlihat di preview akan disimpan.').then(function (ok) {
            if (!ok) return;
            const $btn = $('#btnConfirmImport');
            busy($btn, 'Mengimpor...');
            $.ajax({ url: URLS.importCsv, method: 'POST', data: fd, processData: false, contentType: false })
                .done(function (res) {
                    let html = escapeHtml(res.message);
                    if (res.skipped && res.skipped.length) {
                        html += '<div class="text-left small mt-2" style="max-height:200px;overflow:auto;">' + res.skipped.map(escapeHtml).join('<br>') + '</div>';
                    }
                    $('#importCsvModal').modal('hide');
                    notify(res.skipped && res.skipped.length ? 'warning' : 'success', 'Import selesai', html);
                    table.ajax.reload(null, false);
                    loadSummary();
                })
                .fail(function (xhr) { notify('error', 'Import gagal', ajaxErrorHtml(xhr, xhr.statusText)); })
                .always(function () { idle($btn); });
        });
    }

    // ---------- kelola zat aktif ----------
    let zatTable = null;
    let zatMergeSourceId = null;

    function renderZatUsage(data, type, row) {
        if (type !== 'display') return row.obat_count;
        const parts = [];
        if (row.obat_count > 0) {
            parts.push('<a href="#" class="zat-show-obat" data-id="' + row.id + '" title="Lihat obatnya di tabel">' + formatNumber(row.obat_count) + ' obat</a>');
        }
        if (row.alergi_count > 0) {
            parts.push('<span class="badge badge-warning" title="Dipakai di data alergi pasien">' + formatNumber(row.alergi_count) + ' alergi pasien</span>');
        }
        return parts.length ? parts.join(' · ') : '<span class="text-muted">Tidak dipakai</span>';
    }

    function renderZatActions(data, type, row) {
        let html = '<div class="btn-group btn-group-sm">' +
            '<button type="button" class="btn btn-outline-primary zat-rename" title="Ubah nama"><i class="fas fa-pen"></i></button>';
        if (CAN_DELETE) {
            html += '<button type="button" class="btn btn-outline-secondary zat-merge" title="Gabungkan ke zat aktif lain (nama ganda)"><i class="fas fa-code-branch"></i></button>';
            const used = row.obat_count > 0 || row.alergi_count > 0;
            html += '<button type="button" class="btn btn-outline-danger zat-delete" ' +
                (used ? 'disabled title="Masih dipakai, tidak bisa dihapus"' : 'title="Hapus"') + '><i class="fas fa-trash"></i></button>';
        }
        return html + '</div>';
    }

    function initZatTable() {
        zatTable = $('#zataktif-table').DataTable({
            processing: true,
            serverSide: true,
            pageLength: 10,
            lengthChange: false,
            order: [[1, 'asc']],
            dom: "rt<'row align-items-center mt-2'<'col-sm-6 small'i><'col-sm-6'p>>",
            ajax: {
                url: URLS.zatAktif,
                data: function (d) { d.pemakaian = $('#zatFilter').val() || ''; d.with_summary = 1; },
                dataSrc: function (res) {
                    const dup = res.duplicate_count || 0;
                    $('#zatDuplicateHint').toggleClass('d-none', dup === 0)
                        .html('<i class="fas fa-exclamation-triangle mr-1"></i>' + dup + ' zat aktif punya nama ganda atau kosong. ' +
                            '<a href="#" id="zatShowDuplicates">Tampilkan</a>');
                    return res.data || [];
                },
                error: function (xhr) { notify('error', 'Gagal memuat zat aktif', ajaxErrorHtml(xhr, '')); }
            },
            columns: [
                { data: null, orderable: false, render: function (d, t, r, meta) { return meta.settings._iDisplayStart + meta.row + 1; } },
                { data: 'nama', name: 'nama', render: function (d, t) { return t === 'display' ? '<span class="zat-nama">' + (d ? escapeHtml(d) : '<em class="text-danger">(kosong)</em>') + '</span>' : d; } },
                { data: 'obat_count', name: 'obat_count', searchable: false, render: renderZatUsage },
                { data: null, orderable: false, searchable: false, className: 'text-right text-nowrap', render: renderZatActions }
            ],
            language: {
                info: '_START_–_END_ dari _TOTAL_ zat aktif',
                infoEmpty: 'Tidak ada data',
                infoFiltered: '',
                zeroRecords: 'Tidak ada zat aktif yang cocok.',
                emptyTable: 'Belum ada zat aktif.',
                paginate: { previous: '&lsaquo;', next: '&rsaquo;' }
            }
        });
    }

    function reloadZat(resetPaging) {
        if (zatTable) zatTable.ajax.reload(null, resetPaging === true);
    }

    function openZatAktifList() {
        hideZatMerge();
        $('#newZatNama').val('');
        $('#newZatFeedback').text('').removeClass('text-danger text-success');
        if (!zatTable) initZatTable(); else reloadZat(false);
        $('#zatAktifModal').modal('show');
    }

    // Zat aktif names appear in the obat table, so refresh it after any change
    function afterZatChange() {
        reloadZat(false);
        table.ajax.reload(null, false);
    }

    function addZatAktif(e) {
        e.preventDefault();
        const name = ($('#newZatNama').val() || '').trim();
        const $fb = $('#newZatFeedback').removeClass('text-danger text-success').text('');
        if (!name) { $('#newZatNama').focus(); return; }
        const $btn = $('#btnAddZat').prop('disabled', true);
        $.post(URLS.zatAktif, { nama: name })
            .done(function (res) {
                $('#newZatNama').val('').focus();
                $fb.addClass('text-success').text(res.message || 'Zat aktif ditambahkan.');
                $('#zatSearch').val((res.data && res.data.nama) || name);
                zatTable.search($('#zatSearch').val());
                reloadZat(true);
            })
            .fail(function (xhr) { $fb.addClass('text-danger').html(ajaxErrorHtml(xhr, 'Gagal menambahkan.')); })
            .always(function () { $btn.prop('disabled', false); });
    }

    function startRename($tr) {
        const row = zatTable.row($tr).data();
        if (!row || $tr.find('.zat-rename-input').length) return;
        const $cell = $tr.find('.zat-nama').parent();
        $cell.html('<div class="input-group input-group-sm">' +
            '<input type="text" class="form-control zat-rename-input" maxlength="191" value="' + escapeHtml(row.nama || '') + '">' +
            '<div class="input-group-append">' +
            '<button type="button" class="btn btn-primary zat-rename-save" title="Simpan"><i class="fas fa-check"></i></button>' +
            '<button type="button" class="btn btn-light border zat-rename-cancel" title="Batal"><i class="fas fa-times"></i></button>' +
            '</div></div>');
        $cell.find('input').trigger('focus').trigger('select');
    }

    function saveRename($tr) {
        const row = zatTable.row($tr).data();
        const nama = ($tr.find('.zat-rename-input').val() || '').trim();
        if (!row || !nama) return;
        if (nama.toUpperCase() === String(row.nama || '').toUpperCase()) { reloadZat(false); return; }
        const usage = row.obat_count + row.alergi_count;
        const go = usage > 0
            ? confirmDialog('Ubah nama zat aktif?', 'Nama baru akan tampil di ' + row.obat_count + ' obat dan ' + row.alergi_count + ' data alergi pasien.')
            : Promise.resolve(true);
        go.then(function (ok) {
            if (!ok) return;
            $.ajax({ url: URLS.zatAktif + '/' + row.id, type: 'PUT', data: { nama: nama } })
                .done(function (res) { notify('success', res.message || 'Nama diperbarui'); afterZatChange(); })
                .fail(function (xhr) { notify('error', 'Gagal mengubah nama', ajaxErrorHtml(xhr, '')); });
        });
    }

    function showZatMerge(row) {
        zatMergeSourceId = row.id;
        $('#zatMergeSource, #zatMergeSource2').text(row.nama || '(kosong)');
        $('#zatMergeTarget').empty().trigger('change');
        $('#zatMergePanel').removeClass('d-none');
        $('#zatAktifModal .modal-body').scrollTop(0);
        $('#zatMergeTarget').select2('open');
    }

    function hideZatMerge() {
        zatMergeSourceId = null;
        $('#zatMergePanel').addClass('d-none');
    }

    function saveZatMerge() {
        const targetId = $('#zatMergeTarget').val();
        if (!zatMergeSourceId || !targetId) { notify('warning', 'Pilih zat aktif tujuan.'); return; }
        const targetName = $('#zatMergeTarget').find('option:selected').text();
        confirmDialog('Gabungkan zat aktif?', '"' + $('#zatMergeSource').text() + '" akan digabung ke "' + targetName + '". Tidak bisa dibatalkan.').then(function (ok) {
            if (!ok) return;
            const $btn = $('#btnZatMergeSave');
            busy($btn, 'Menggabungkan...');
            $.post(URLS.zatAktif + '/' + zatMergeSourceId + '/merge', { target_id: targetId })
                .done(function (res) { hideZatMerge(); notify('success', res.message || 'Digabungkan'); afterZatChange(); })
                .fail(function (xhr) { notify('error', 'Gagal menggabungkan', ajaxErrorHtml(xhr, '')); })
                .always(function () { idle($btn); });
        });
    }

    function deleteZat(row) {
        confirmDialog('Hapus zat aktif?', row.nama || '(kosong)').then(function (ok) {
            if (!ok) return;
            $.ajax({ url: URLS.zatAktif + '/' + row.id, type: 'DELETE' })
                .done(function (res) { notify('success', res.message || 'Dihapus'); afterZatChange(); })
                .fail(function (xhr) { notify('error', 'Tidak bisa menghapus', ajaxErrorHtml(xhr, '')); });
        });
    }

    // "12 obat": close the modal and show those obat in the main table
    function showObatForZat(row) {
        $('#zatAktifModal').modal('hide');
        const $k = $('#filter_kandungan').empty().append(new Option(row.nama, row.id, true, true));
        $k.trigger('change.select2');
        $('#filter_status').val('').trigger('change.select2');
        reloadTable();
    }

    function wireZatAktif() {
        $('#zatMergeTarget').select2({
            width: '100%',
            dropdownParent: $('#zatAktifModal'),
            placeholder: 'Cari zat aktif tujuan...',
            minimumInputLength: 2,
            ajax: $.extend(zatAktifAjax(), {
                processResults: function (res, params) {
                    const page = params.page || 1;
                    return {
                        results: (res.data || []).filter(function (z) { return z.id !== zatMergeSourceId; })
                            .map(function (z) { return { id: z.id, text: z.nama }; }),
                        pagination: { more: page * 20 < (res.recordsFiltered || 0) }
                    };
                }
            })
        });

        let zatSearchTimer = null;
        $('#zatSearch').on('input search', function () {
            clearTimeout(zatSearchTimer);
            const value = $(this).val();
            zatSearchTimer = setTimeout(function () { zatTable.search(value.trim()); reloadZat(true); }, 350);
        });
        $('#zatFilter').on('change', function () { reloadZat(true); });
        $('#zatAktifModal').on('click', '#zatShowDuplicates', function (e) {
            e.preventDefault();
            $('#zatFilter').val('ganda');
            reloadZat(true);
        });

        const $tbl = $('#zataktif-table');
        $tbl.on('click', '.zat-rename', function () { startRename($(this).closest('tr')); });
        $tbl.on('click', '.zat-rename-save', function () { saveRename($(this).closest('tr')); });
        $tbl.on('click', '.zat-rename-cancel', function () { reloadZat(false); });
        $tbl.on('keydown', '.zat-rename-input', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); saveRename($(this).closest('tr')); }
            if (e.key === 'Escape') { e.preventDefault(); e.stopPropagation(); reloadZat(false); }
        });
        $tbl.on('click', '.zat-merge', function () { showZatMerge(zatTable.row($(this).closest('tr')).data()); });
        $tbl.on('click', '.zat-delete', function () { deleteZat(zatTable.row($(this).closest('tr')).data()); });
        $tbl.on('click', '.zat-show-obat', function (e) { e.preventDefault(); showObatForZat(zatTable.row($(this).closest('tr')).data()); });

        $('#btnZatMergeCancel').on('click', hideZatMerge);
        $('#btnZatMergeSave').on('click', saveZatMerge);
        $('#zatAktifModal').on('hidden.bs.modal', hideZatMerge);
    }

    // ---------- principal (pemasok is managed on Master Pembelian) ----------
    function setSupplierFilter(filter) {
        supplierFilter = filter;
        $('#supplierFilterInfo').toggleClass('d-none', !filter);
        if (filter) {
            $('#supplierFilterLabel').text(filter.label.toLowerCase());
            $('#supplierFilterNama').text(filter.nama);
        }
    }

    let principalManager = null;

    function wireSuppliers() {
        principalManager = SupplierManager.create({
            key: 'principal', label: 'Principal', url: URLS.principal, canDelete: CAN_DELETE,
            // Principal names show in the obat table
            onChanged: function () { table.ajax.reload(null, false); },
            showObatTitle: 'Lihat obatnya di tabel',
            onShowObat: function (row) {
                setSupplierFilter({ param: 'principal_id', id: row.id, nama: row.nama, label: 'Principal' });
                $('#filter_status').val('').trigger('change.select2');
                reloadTable();
            }
        });
        $('.btn-supplier').on('click', function () { principalManager.open(); });
        $('#btnClearSupplierFilter').on('click', function (e) {
            e.preventDefault();
            setSupplierFilter(null);
            reloadTable();
        });
    }

    // ---------- wiring ----------
    $(function () {
        initSelects();
        restoreFilters();
        initTable();
        updateActiveFilterInfo();
        loadSummary();

        // Filters
        $('.filter-select, #filter_kandungan').on('change', function () { reloadTable(); });
        let searchTimer = null;
        $('#filter_search').on('input search', function () {
            clearTimeout(searchTimer);
            const value = $(this).val();
            searchTimer = setTimeout(function () { table.search(value.trim()); reloadTable(); }, 400);
        });
        $('#btnResetFilter').on('click', function () {
            $('#filter_search').val('');
            table.search('');
            setFilters({ status_aktif: '1' }, null);
            setSupplierFilter(null);
            reloadTable();
        });

        // Row click opens edit; buttons keep their own action
        $('#obat-table tbody').on('click', 'tr', function (e) {
            if ($(e.target).closest('button, a, input, select').length) return;
            const row = table.row(this).data();
            if (row) openEditObat(row.id);
        });
        $('#obat-table').on('click', '.btn-edit-obat', function () { openEditObat($(this).data('id')); });
        $('#obat-table').on('click', '.btn-delete-obat', function () {
            const row = table.row($(this).closest('tr')).data();
            deleteObat($(this).data('id'), row ? row.nama : '');
        });

        // Form
        $(document).on('click', '.btn-tambah-obat', openTambahObat);
        $('#formObat').on('submit', submitForm);
        $('#nama').on('input', function () {
            clearTimeout(namaTimer);
            namaTimer = setTimeout(checkNama, 400);
        });
        $('#obatModal').on('shown.bs.modal', function () { $('#nama').trigger('focus'); });
        $('#dosis').on('input', updateKekuatanPreview);
        $('#satuan, #satuan_stok').on('change', function () {
            if (this.id === 'satuan_stok') setSatuanStokHint(false);
            updateKekuatanPreview();
        });
        $('#formObat').on('change input', '.is-invalid', function () { $(this).removeClass('is-invalid'); });
        $(document).on('focus', '.numeric-input', function () {
            $(this).val(normalizeNumericInput($(this).val()).replace('.', ','));
        });
        $(document).on('blur', '.numeric-input', function () {
            const n = normalizeNumericInput($(this).val());
            $(this).val(n === '' ? '' : formatRupiah(n));
        });
        $('#harga_nonfornas').on('input blur', updateHargaJualHint);

        // Export
        $('#btnExportExcel').on('click', function (e) { e.preventDefault(); $('#exportColumnsModal').modal('show'); });
        $('#selectAllColumns').on('change', function () { $('.column-choice').prop('checked', $(this).is(':checked')); });
        $(document).on('change', '.column-choice', function () {
            $('#selectAllColumns').prop('checked', $('.column-choice').length === $('.column-choice:checked').length);
        });
        $('#exportColumnsForm').on('submit', submitExport);

        // Import
        $('#csv_file').on('change', function () { $('#importPreviewArea').empty().hide(); $('#btnConfirmImport').prop('disabled', true); });
        $('#btnPreviewCsv').on('click', previewImport);
        $('#btnConfirmImport').on('click', confirmImport);
        $('#importCsvModal').on('hidden.bs.modal', function () {
            $('#importCsvForm')[0].reset();
            $('#importPreviewArea').empty().hide();
            $('#btnConfirmImport').prop('disabled', true);
        });

        // Zat aktif
        $('.btn-zat-aktif').on('click', openZatAktifList);
        $('#formAddZat').on('submit', addZatAktif);
        wireZatAktif();

        // Principal (old menu links redirect here with ?kelola=principal)
        wireSuppliers();
        $('#btnPrincipalBaru').on('click', function () { $('#principalBaruBox').toggleClass('d-none'); $('#principalBaruNama').trigger('focus'); });
        $('#btnPrincipalBaruBatal').on('click', function () { $('#principalBaruBox').addClass('d-none'); });
        $('#btnPrincipalBaruSimpan').on('click', addPrincipalInline);
        // Enter adds the principal instead of submitting the obat form
        $('#principalBaruNama').on('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); addPrincipalInline(); } });

        // One-time actions from the URL (?tambah=1, ?kelola=...): run once, then drop them from the
        // address bar so a refresh or a visit from browser history does not open the modal again
        const params = new URLSearchParams(window.location.search);
        const kelola = params.get('kelola');
        const tambah = params.get('tambah') === '1';
        if (kelola || tambah) {
            params.delete('kelola');
            params.delete('tambah');
            const query = params.toString();
            try { window.history.replaceState(null, '', window.location.pathname + (query ? '?' + query : '') + window.location.hash); } catch (e) { /* ignore */ }
        }
        if (tambah) openTambahObat();
        else if (kelola === 'principal') principalManager.open();
    });
})();
</script>
@endsection
