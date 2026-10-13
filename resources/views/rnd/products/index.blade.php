@extends('layouts.erm.app')
@section('title', 'R&D | Produk R&D')
@section('navbar')
    @include('layouts.rnd.navbar')
@endsection

@section('styles')
    <link rel="stylesheet" href="{{ asset('dastone/vendor/datatable/FixedColumns-4.3.0/css/fixedColumns.bootstrap4.min.css') }}">
@endsection

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">
<div class="container-fluid rnd-page">

    {{-- Header --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between mb-3 mt-2">
        <div>
            <h3 class="mb-0">Produk R&amp;D</h3>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0 bg-transparent mt-1">
                    <li class="breadcrumb-item">ERM</li>
                    <li class="breadcrumb-item">Master Data</li>
                    <li class="breadcrumb-item active">Produk R&amp;D</li>
                </ol>
            </nav>
        </div>
        <div class="btn-toolbar mt-2 mt-md-0">
            <button type="button" class="btn btn-primary mr-2" id="btnTambahProduk"><i class="fas fa-plus mr-1"></i> Tambah Produk</button>
            <button type="button" class="btn btn-outline-info mr-2 js-open-master" data-master="brand"><i class="fas fa-award mr-1"></i> Kelola Brand</button>
            <button type="button" class="btn btn-outline-info mr-2 js-open-master" data-master="kemasan"><i class="fas fa-box-open mr-1"></i> Kelola Kemasan</button>
            <button type="button" class="btn btn-outline-info mr-2 js-open-master" data-master="vendor"><i class="fas fa-handshake mr-1"></i> Kelola Vendor</button>
            <button type="button" class="btn btn-outline-success" id="btnExportExcel" title="Export Excel sesuai filter"><i class="fas fa-file-download mr-1"></i> Export Excel</button>
        </div>
    </div>

    <ul class="nav nav-pills rnd-pills mb-3" role="tablist">
        <li class="nav-item"><a class="nav-link active" data-toggle="pill" href="#paneProduk" role="tab" id="tabProduk"><i class="fas fa-flask"></i> Daftar Produk</a></li>
        <li class="nav-item"><a class="nav-link" data-toggle="pill" href="#paneTimeline" role="tab" id="tabTimeline"><i class="fas fa-calendar-alt"></i> Timeline</a></li>
    </ul>

    <div class="tab-content">
        {{-- ================= Daftar produk ================= --}}
        <div class="tab-pane fade show active" id="paneProduk" role="tabpanel">
            <div class="row mb-2" id="rndCards">
                <div class="col-6 col-md-4 col-xl-2 mb-2"><div class="rnd-card active" data-tahap=""><div class="rnd-card-lbl">Semua produk</div><div class="rnd-card-num text-dark" data-count="semua">-</div><div class="rnd-card-hint">&nbsp;</div></div></div>
                <div class="col-6 col-md-4 col-xl-2 mb-2"><div class="rnd-card" data-tahap="proses"><div class="rnd-card-lbl"><span class="badge badge-warning">Dalam proses</span></div><div class="rnd-card-num text-warning" data-count="proses">-</div><div class="rnd-card-hint">aktif, syarat rilis belum lengkap</div></div></div>
                <div class="col-6 col-md-4 col-xl-2 mb-2"><div class="rnd-card" data-tahap="siap_rilis"><div class="rnd-card-lbl"><span class="badge badge-primary">Siap rilis</span></div><div class="rnd-card-num text-primary" data-count="siap_rilis">-</div><div class="rnd-card-hint">semua syarat &amp; sertifikasi lengkap</div></div></div>
                <div class="col-6 col-md-4 col-xl-2 mb-2"><div class="rnd-card" data-tahap="dirilis"><div class="rnd-card-lbl"><span class="badge badge-success">Dirilis</span></div><div class="rnd-card-num text-success" data-count="dirilis">-</div><div class="rnd-card-hint">terhubung ke Master Obat</div></div></div>
                <div class="col-6 col-md-4 col-xl-2 mb-2"><div class="rnd-card" data-tahap="ditunda"><div class="rnd-card-lbl"><span class="badge badge-secondary">Ditunda / batal</span></div><div class="rnd-card-num text-muted" data-count="ditunda">-</div><div class="rnd-card-hint">tidak sedang dikerjakan</div></div></div>
                <div class="col-6 col-md-4 col-xl-2 mb-2"><div class="rnd-card" data-tahap="perpanjangan"><div class="rnd-card-lbl"><span class="badge badge-danger">Perlu perpanjangan</span></div><div class="rnd-card-num text-danger" data-count="perpanjangan">-</div><div class="rnd-card-hint">sertifikat habis / ≤ 90 hari</div></div></div>
            </div>

            <div class="card">
                <div class="card-body pb-2">
                    <div class="form-row align-items-end filter-bar">
                        <div class="col-lg-4 col-md-6 mb-2">
                            <label for="filterSearch" class="small text-muted mb-1">Cari</label>
                            <div class="input-group">
                                <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-search"></i></span></div>
                                <input type="search" id="filterSearch" class="form-control" placeholder="Nama, brand, zat aktif, no produksi, kode obat" autocomplete="off">
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-4 mb-2">
                            <label for="filterBrand" class="small text-muted mb-1">Brand</label>
                            <select id="filterBrand" class="form-control"></select>
                        </div>
                        <div class="col-lg-auto col-md-2 mb-2 ml-auto text-right">
                            <button type="button" class="btn btn-light border" id="btnResetFilter"><i class="fas fa-undo mr-1"></i> Reset filter</button>
                        </div>
                    </div>

                    {{-- scrollX + FixedColumns (same as Rawat Jalan): No & Produk stay left, Aksi stays right --}}
                    <table id="produkTable" class="table table-hover table-sm table-bordered w-100">
                        <thead class="thead-light">
                            {{-- Columns follow the product flow: 1 Sample -> 2 Kemasan -> 3 Administrasi -> 4 Legalitas -> Rilis --}}
                            <tr>
                                <th rowspan="2">No</th>
                                <th rowspan="2">Produk</th>
                                <th rowspan="2">Tahap</th>
                                <th rowspan="2">Produsen</th>
                                <th rowspan="2">Zat Aktif</th>
                                <th rowspan="2"><span class="rnd-step-no">1</span> Sample</th>
                                <th colspan="2" class="text-center"><span class="rnd-step-no">2</span> Kemasan &amp; Desain</th>
                                <th colspan="3" class="text-center"><span class="rnd-step-no">3</span> Administrasi</th>
                                <th rowspan="2"><span class="rnd-step-no">4</span> Legalitas</th>
                                <th rowspan="2" class="text-center">Aksi</th>
                            </tr>
                            <tr>
                                <th>Primer</th>
                                <th>Sekunder</th>
                                <th class="text-center">FPP</th>
                                <th class="text-center">SPK</th>
                                <th class="text-center">Notif BPOM</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ================= Timeline ================= --}}
        <div class="tab-pane fade" id="paneTimeline" role="tabpanel">
            <div class="card mb-3">
                <div class="card-body py-2">
                    <div class="form-row align-items-center">
                        <div class="col-md-5 mb-2 mb-md-0">
                            <select id="timelineFilterProduk" class="form-control"></select>
                        </div>
                        <div class="col-md mb-2 mb-md-0 small">
                            <span class="rnd-legend" style="--c:#4e73df"></span> Catatan
                            <span class="rnd-legend ml-3" style="--c:#6f42c1"></span> Target rilis
                            <span class="rnd-legend ml-3" style="--c:#e74a3b"></span> Sertifikat berakhir
                        </div>
                        <div class="col-md-auto text-right">
                            <button type="button" class="btn btn-primary" id="btnTambahTimeline"><i class="fas fa-plus mr-1"></i> Tambah Catatan</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-xl-8 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <div id="productTimelineCalendar"></div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h5 class="mb-0" id="agendaTitle">Agenda 30 hari ke depan</h5>
                                <button type="button" class="btn btn-sm btn-light border d-none" id="btnAgendaUpcoming">Kembali ke agenda</button>
                            </div>
                            <div class="rnd-timeline-list" id="productTimelineIndexList">
                                <div class="text-muted small text-center py-3">Memuat...</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal: Tambah/Edit Produk --}}
<div class="modal fade" id="produkModal" tabindex="-1" role="dialog" aria-labelledby="produkModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <div class="flex-grow-1">
                    <h5 class="modal-title" id="produkModalLabel">Tambah Produk</h5>
                    <div id="produkModalStepper" class="mt-2 d-none"></div>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
            </div>
            <form id="produkForm" novalidate>
                <div class="modal-body">
                    <input type="hidden" id="produk_id" value="">
                    <div class="row">
                        <div class="col" id="produkFormCol">
                            {{-- Tabs follow the product flow; the flow tabs need a saved product --}}
                            <ul class="nav nav-tabs mb-3 rnd-flow-tabs" id="produkFormTabs" role="tablist">
                                <li class="nav-item"><a class="nav-link active" id="produk-base-tab" data-toggle="tab" href="#produk-base-pane" role="tab">Produk</a></li>
                                <li class="nav-item js-edit-only"><a class="nav-link" id="produk-sample-tab" data-toggle="tab" href="#produk-sample-pane" role="tab"><span class="rnd-step-no" data-step="sample">1</span> Sample</a></li>
                                <li class="nav-item"><a class="nav-link" id="produk-kemasan-tab" data-toggle="tab" href="#produk-kemasan-pane" role="tab"><span class="rnd-step-no" data-step="kemasan">2</span> Kemasan</a></li>
                                <li class="nav-item"><a class="nav-link" id="produk-administrasi-tab" data-toggle="tab" href="#produk-administrasi-pane" role="tab"><span class="rnd-step-no" data-step="administrasi">3</span> Administrasi</a></li>
                                <li class="nav-item js-edit-only"><a class="nav-link" id="produk-legalitas-tab" data-toggle="tab" href="#produk-legalitas-pane" role="tab"><span class="rnd-step-no" data-step="legalitas">4</span> Legalitas <span class="badge badge-danger d-none" id="legalitasTabBadge"></span></a></li>
                                <li class="nav-item js-edit-only ml-auto"><a class="nav-link" id="produk-dokumen-tab" data-toggle="tab" href="#produk-dokumen-pane" role="tab"><i class="fas fa-paperclip"></i> Dokumen</a></li>
                                <li class="nav-item js-edit-only"><a class="nav-link" id="produk-riwayat-tab" data-toggle="tab" href="#produk-riwayat-pane" role="tab"><i class="fas fa-history"></i> Riwayat</a></li>
                            </ul>

                            <div class="tab-content">
                                <div class="tab-pane fade show active" id="produk-base-pane" role="tabpanel">
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label for="brand_id">Brand <span class="text-danger">*</span></label>
                                            <select class="form-control select2-produk" id="brand_id" name="brand_id" required></select>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label for="nama_produk">Nama Produk <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="nama_produk" name="nama_produk" maxlength="255" required>
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group col-md-2 col-6">
                                            <label for="netto">Netto</label>
                                            <input type="text" class="form-control" id="netto" name="netto" inputmode="decimal" placeholder="mis. 15">
                                        </div>
                                        <div class="form-group col-md-2 col-6">
                                            <label for="netto_satuan">Satuan Netto</label>
                                            <select class="form-control" id="netto_satuan" name="netto_satuan">
                                                <option value="">-</option>
                                                @foreach($nettoSatuans as $satuan)
                                                    <option value="{{ $satuan }}">{{ $satuan }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label for="sediaan">Sediaan <span class="text-danger">*</span></label>
                                            <select class="form-control select2-produk" id="sediaan" name="sediaan" required>
                                                <option value="">Pilih Sediaan</option>
                                                {{-- Options from the Satuan master; the product stores the text --}}
                                                @foreach($sediaans as $sediaan)
                                                    <option value="{{ $sediaan }}">{{ $sediaan }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group col-md-5">
                                            <label for="produsen_vendor_id">Produsen</label>
                                            <select class="form-control select2-produk" id="produsen_vendor_id" name="produsen_vendor_id"></select>
                                        </div>
                                    </div>
                                    <div class="form-group mb-0">
                                        <label for="zat_aktif_ids">Zat Aktif</label>
                                        <select class="form-control select2-produk" id="zat_aktif_ids" name="zat_aktif_ids[]" multiple>
                                            @foreach($zatAktifs as $zatAktif)
                                                <option value="{{ $zatAktif->id }}">{{ $zatAktif->nama }}</option>
                                            @endforeach
                                        </select>
                                        <small class="form-text text-muted">Dari master Zat Aktif (sama dengan Master Obat).</small>
                                    </div>

                                    <div class="form-section">Rencana &amp; Biaya</div>
                                    <div class="form-row">
                                        <div class="form-group col-md-3">
                                            <label for="status_produk">Status Produk</label>
                                            <select class="form-control" id="status_produk" name="status_produk">
                                                @foreach($statusProdukOptions as $status)
                                                    <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group col-md-5">
                                            <label for="pic_user_id">PIC</label>
                                            <select class="form-control" id="pic_user_id" name="pic_user_id"></select>
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="target_rilis">Target Rilis</label>
                                            <input type="date" class="form-control" id="target_rilis" name="target_rilis">
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group col-md-3 col-6">
                                            <label for="masa_simpan_bulan">Masa Simpan</label>
                                            <div class="input-group">
                                                <input type="number" class="form-control" id="masa_simpan_bulan" name="masa_simpan_bulan" min="1" max="240">
                                                <div class="input-group-append"><span class="input-group-text">bulan</span></div>
                                            </div>
                                        </div>
                                        <div class="form-group col-md-3 col-6">
                                            <label for="target_hpp">Target HPP <small class="text-muted">(excl. PPN)</small></label>
                                            <input type="number" class="form-control" id="target_hpp" name="target_hpp" min="0" step="any">
                                        </div>
                                        <div class="form-group col-md-3 col-6">
                                            <label for="target_harga_jual">Target Harga Jual</label>
                                            <input type="number" class="form-control" id="target_harga_jual" name="target_harga_jual" min="0" step="any">
                                        </div>
                                        <div class="form-group col-md-3 col-6">
                                            <label for="estimasi_biaya">Estimasi Biaya Pengembangan</label>
                                            <input type="number" class="form-control" id="estimasi_biaya" name="estimasi_biaya" min="0" step="any">
                                        </div>
                                    </div>
                                    <small class="text-muted d-block mb-0" id="targetMarginInfo"></small>
                                </div>

                                <div class="tab-pane fade" id="produk-kemasan-pane" role="tabpanel">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-section">Kemasan Primer</div>
                                            <div class="form-group">
                                                <label for="kemasan_premier_id">Jenis Kemasan <span class="text-danger">*</span></label>
                                                <select class="form-control select2-produk" id="kemasan_premier_id" name="kemasan_premier_id" required></select>
                                            </div>
                                            <div class="form-group">
                                                <label for="kemasan_primer_vendor_id">Vendor Kemasan</label>
                                                <select class="form-control select2-produk" id="kemasan_primer_vendor_id" name="kemasan_primer_vendor_id"></select>
                                            </div>
                                            <div class="form-group">
                                                <label for="desain_kemasan_primer_id">Vendor Desain</label>
                                                <select class="form-control select2-produk" id="desain_kemasan_primer_id" name="desain_kemasan_primer_id"></select>
                                            </div>
                                            <div class="form-row">
                                                <div class="form-group col-6">
                                                    <label for="status_kemasan_primer">Status Kemasan</label>
                                                    <select class="form-control js-status-select" id="status_kemasan_primer" name="status_kemasan_primer" data-options="kemasan"></select>
                                                </div>
                                                <div class="form-group col-6">
                                                    <label for="status_desain_kemasan_primer">Status Desain</label>
                                                    <select class="form-control js-status-select" id="status_desain_kemasan_primer" name="status_desain_kemasan_primer" data-options="desain"></select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6" id="produkSekunderCol">
                                            <div class="form-section">Kemasan Sekunder <small class="text-lowercase">(opsional)</small></div>
                                            <div class="form-group">
                                                <label for="kemasan_sekunder_id">Jenis Kemasan</label>
                                                <select class="form-control select2-produk" id="kemasan_sekunder_id" name="kemasan_sekunder_id"></select>
                                            </div>
                                            <div class="form-group">
                                                <label for="kemasan_sekunder_vendor_id">Vendor Kemasan</label>
                                                <select class="form-control select2-produk js-sekunder-dependent" id="kemasan_sekunder_vendor_id" name="kemasan_sekunder_vendor_id"></select>
                                            </div>
                                            <div class="form-group">
                                                <label for="desain_kemasan_sekunder_id">Vendor Desain</label>
                                                <select class="form-control select2-produk js-sekunder-dependent" id="desain_kemasan_sekunder_id" name="desain_kemasan_sekunder_id"></select>
                                            </div>
                                            <div class="form-row">
                                                <div class="form-group col-6">
                                                    <label for="status_kemasan_sekunder">Status Kemasan</label>
                                                    <select class="form-control js-status-select js-sekunder-dependent" id="status_kemasan_sekunder" name="status_kemasan_sekunder" data-options="kemasan"></select>
                                                </div>
                                                <div class="form-group col-6">
                                                    <label for="status_desain_kemasan_sekunder">Status Desain</label>
                                                    <select class="form-control js-status-select js-sekunder-dependent" id="status_desain_kemasan_sekunder" name="status_desain_kemasan_sekunder" data-options="desain"></select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <small class="text-muted">Kemasan atau vendor belum ada? Tambahkan lewat <strong>Kelola Kemasan</strong> / <strong>Kelola Vendor</strong>; pilihan di sini langsung ikut diperbarui.</small>
                                </div>

                                <div class="tab-pane fade" id="produk-administrasi-pane" role="tabpanel">
                                    <div class="form-section">Status Administrasi</div>
                                    <div class="form-row">
                                        <div class="form-group col-md-4">
                                            <label for="status_administrasi_fpp">FPP</label>
                                            <select class="form-control js-status-select" id="status_administrasi_fpp" name="status_administrasi_fpp" data-options="fpp"></select>
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="status_administrasi_spk">SPK</label>
                                            <select class="form-control js-status-select" id="status_administrasi_spk" name="status_administrasi_spk" data-options="spk"></select>
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="status_administrasi_notif">Notifikasi BPOM</label>
                                            <select class="form-control js-status-select" id="status_administrasi_notif" name="status_administrasi_notif" data-options="notif"></select>
                                            <small class="form-text text-muted">"done" otomatis saat Notifikasi BPOM ditambahkan di tab Legalitas.</small>
                                        </div>
                                    </div>
                                </div>

                                {{-- Sample: summary only, managed in the Sample modal --}}
                                <div class="tab-pane fade" id="produk-sample-pane" role="tabpanel">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-muted small">Sample terakhir menentukan status tahap Sample.</span>
                                        <button type="button" class="btn btn-sm btn-primary" id="btnKelolaSample"><i class="fas fa-vial mr-1"></i> Tambah / Kelola Sample</button>
                                    </div>
                                    <div id="produkSampleSummary"></div>
                                </div>

                                <div class="tab-pane fade" id="produk-dokumen-pane" role="tabpanel">
                                    <div class="form-section mt-0">Dokumen Tambahan</div>
                                    <input type="file" id="additional_documents" class="d-none" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png">
                                    <div class="d-flex flex-wrap align-items-center" style="gap:8px">
                                        <button type="button" class="btn btn-sm btn-outline-primary" id="pickAdditionalDocumentsBtn"><i class="fas fa-paperclip mr-1"></i> Pilih File</button>
                                        <button type="button" class="btn btn-sm btn-primary" id="submitAdditionalDocumentsBtn" disabled><i class="fas fa-upload mr-1"></i> Upload</button>
                                        <span class="small text-muted" id="additionalDocumentsSummary">Belum ada file dipilih.</span>
                                    </div>
                                    <small class="form-text text-muted">PDF, DOC, DOCX, XLS, XLSX, JPG, PNG. Maksimum 10 MB per file.</small>
                                    <ul class="list-group list-group-flush mt-2" id="additionalDocumentsList"></ul>
                                </div>

                                {{-- Inputs here have no name: they are sent by their own buttons, not with the product form --}}
                                <div class="tab-pane fade" id="produk-legalitas-pane" role="tabpanel">
                                    <div id="legalitasNeedSave" class="alert alert-light border small">Simpan produk dulu, lalu buka lagi untuk menambah sertifikasi.</div>
                                    <div id="legalitasBody" class="d-none">
                                        <div class="form-section mt-0">Syarat Rilis ke Master Obat (obat baru)</div>
                                        <ul class="list-unstyled small mb-1 rnd-checklist" id="releaseChecklist"></ul>
                                        <small class="text-muted d-block mb-2">Produk lama yang sudah ada di Master Obat cukup <em>dihubungkan</em> lewat tombol Rilis, tanpa syarat ini.</small>

                                        <div class="form-section">Sertifikasi</div>
                                        <div id="sertifikasiList"></div>

                                        <div class="form-section" id="sertifikasiFormTitle">Tambah Sertifikasi</div>
                                        <input type="hidden" id="sertifikasi_id">
                                        <div class="form-row">
                                            <div class="form-group col-md-6">
                                                <label for="sertifikasi_jenis">Jenis <span class="text-danger">*</span></label>
                                                <select class="form-control" id="sertifikasi_jenis">
                                                    @foreach($jenisSertifikasi as $key => $meta)
                                                        <option value="{{ $key }}">{{ $meta['label'] }}{{ $meta['wajib'] ? ' (wajib)' : '' }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="form-group col-md-3 col-6">
                                                <label for="sertifikasi_nomor">Nomor <span class="text-danger d-none" id="sertifikasiNomorWajib">*</span></label>
                                                <input type="text" class="form-control" id="sertifikasi_nomor" maxlength="255">
                                            </div>
                                            <div class="form-group col-md-3 col-6">
                                                <label for="sertifikasi_penerbit">Penerbit / Lab</label>
                                                <input type="text" class="form-control" id="sertifikasi_penerbit" maxlength="255">
                                            </div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-3 col-6">
                                                <label for="sertifikasi_tanggal_mulai">Tanggal Mulai <span class="text-danger">*</span></label>
                                                <input type="date" class="form-control" id="sertifikasi_tanggal_mulai">
                                            </div>
                                            <div class="form-group col-md-3 col-6">
                                                <label for="sertifikasi_tanggal_selesai">Berlaku s/d <span class="text-danger">*</span></label>
                                                <input type="date" class="form-control" id="sertifikasi_tanggal_selesai">
                                            </div>
                                            <div class="form-group col-md-6 d-none" id="sertifikasiSampleGroup">
                                                <label for="sertifikasi_sample_log_id">Batch / No Produksi</label>
                                                <select class="form-control" id="sertifikasi_sample_log_id"></select>
                                            </div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-6">
                                                <label for="sertifikasi_dokumen">Dokumen (PDF/JPG/PNG, maks. 10 MB) <span class="text-danger" id="sertifikasiDokumenWajib">*</span></label>
                                                <input type="file" class="form-control-file" id="sertifikasi_dokumen" accept=".pdf,.jpg,.jpeg,.png">
                                                <small class="form-text text-muted d-none" id="sertifikasiDokumenHint">Kosongkan bila dokumen tidak diganti.</small>
                                            </div>
                                            <div class="form-group col-md-6">
                                                <label for="sertifikasi_catatan">Catatan</label>
                                                <input type="text" class="form-control" id="sertifikasi_catatan">
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <button type="button" class="btn btn-sm btn-light border d-none" id="btnSertifikasiCancel">Batal edit</button>
                                            <button type="button" class="btn btn-sm btn-primary" id="btnSertifikasiSave"><i class="fas fa-plus mr-1"></i> Tambah Sertifikasi</button>
                                        </div>
                                        <small class="text-muted d-block mt-2">Perpanjangan: tambahkan sebagai sertifikasi baru dengan jenis yang sama; yang lama tetap tersimpan sebagai riwayat.</small>
                                    </div>
                                </div>

                                <div class="tab-pane fade" id="produk-riwayat-pane" role="tabpanel">
                                    <div class="input-group mb-3">
                                        <textarea class="form-control" id="produkNoteInput" rows="2" placeholder="Tambah catatan untuk produk ini"></textarea>
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-outline-primary" id="btnSaveProdukNote"><i class="fas fa-paper-plane mr-1"></i> Simpan</button>
                                        </div>
                                    </div>
                                    <div class="rnd-log-list" id="produkLogList"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-danger mr-auto d-none" id="deleteProdukBtn"><i class="fas fa-trash mr-1"></i> Hapus</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="saveProdukBtn"><i class="fas fa-save mr-1"></i> Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal: ubah status (badge di tabel) --}}
<div class="modal fade" id="statusModal" tabindex="-1" role="dialog" aria-labelledby="statusModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form class="modal-content" id="statusForm" novalidate>
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="statusModalLabel">Ubah Status</h5>
                    <small class="text-muted" id="statusModalProduk"></small>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="status_produk_id">
                <input type="hidden" id="status_field">
                <input type="hidden" id="status_current">
                <label class="small text-muted mb-1">Status</label>
                <div class="list-group mb-3" id="statusOptions"></div>

                {{-- Notifikasi BPOM "done" needs the notification dates and PDF --}}
                <div id="notifFields" class="d-none">
                    <div class="form-section mt-0">Data Notifikasi BPOM</div>
                    <div class="form-group">
                        <label for="notif_nomor">Nomor notifikasi (NA) <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="notif_nomor" maxlength="255" placeholder="mis. NA18231200123">
                    </div>
                    <div class="form-row">
                        <div class="form-group col-6">
                            <label for="notif_tanggal_mulai">Tanggal mulai <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="notif_tanggal_mulai">
                        </div>
                        <div class="form-group col-6">
                            <label for="notif_tanggal_selesai">Berlaku s/d <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="notif_tanggal_selesai">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="notif_doc">Dokumen notifikasi (PDF, maks. 10 MB) <span class="text-danger">*</span></label>
                        <input type="file" class="form-control-file" id="notif_doc" accept="application/pdf">
                    </div>
                </div>

                <div class="form-group mb-0">
                    <label for="status_log_notes">Catatan log <small class="text-muted">(opsional)</small></label>
                    <textarea class="form-control" id="status_log_notes" rows="2" placeholder="Alasan / keterangan perubahan status"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary" id="statusSaveBtn"><i class="fas fa-save mr-1"></i> Simpan</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal: sample (tambah + riwayat) --}}
<div class="modal fade" id="sampleModal" tabindex="-1" role="dialog" aria-labelledby="sampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="sampleModalLabel">Sample</h5>
                    <small class="text-muted" id="sampleModalProduk"></small>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="sample_produk_id">
                <form id="sampleForm" novalidate autocomplete="off">
                    <div class="form-section mt-0">Tambah Sample</div>
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="sample_no_produksi">No Produksi <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="sample_no_produksi" maxlength="255">
                        </div>
                        <div class="form-group col-md-4 col-6">
                            <label for="sample_tanggal_terima">Tanggal Terima</label>
                            <input type="date" class="form-control" id="sample_tanggal_terima">
                        </div>
                        <div class="form-group col-md-4 col-6">
                            <label for="sample_status">Status</label>
                            <select class="form-control" id="sample_status"></select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="sample_notes">Catatan</label>
                        <input type="text" class="form-control" id="sample_notes">
                    </div>
                    <a class="small" data-toggle="collapse" href="#sampleEvaluasiCollapse" role="button" aria-expanded="false"><i class="fas fa-plus-circle mr-1"></i>Isi evaluasi (tekstur, aroma, warna, reaksi kulit)</a>
                    <div class="collapse" id="sampleEvaluasiCollapse">
                        <div class="form-row mt-2">
                            @foreach($evaluasiFields as $key => $label)
                                <div class="form-group col-md-3 col-6">
                                    <label for="sample_evaluasi_{{ $key }}" class="small mb-1">{{ $label }}</label>
                                    <input type="text" class="form-control form-control-sm js-sample-evaluasi" id="sample_evaluasi_{{ $key }}" data-key="{{ $key }}">
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="text-right">
                        <button type="submit" class="btn btn-sm btn-primary" id="sampleSaveBtn"><i class="fas fa-plus mr-1"></i> Tambah Sample</button>
                    </div>
                </form>

                <div class="form-section">Riwayat Sample</div>
                <div id="sampleHistoryList"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal: tambah timeline --}}
<div class="modal fade" id="timelineModal" tabindex="-1" role="dialog" aria-labelledby="timelineModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form class="modal-content" id="timelineForm" novalidate>
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="timelineModalLabel">Tambah Timeline</h5>
                    <small class="text-muted" id="timelineModalProduk"></small>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="timeline_produk_id">
                <input type="hidden" id="timeline_id">
                <div class="form-group d-none" id="timelineProdukGroup">
                    <label for="timeline_produk_select">Produk <span class="text-danger">*</span></label>
                    <select class="form-control" id="timeline_produk_select" style="width:100%"></select>
                </div>
                <div class="form-group">
                    <label for="timeline_date">Tanggal <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="timeline_date">
                </div>
                <div class="form-group mb-0">
                    <label for="timeline_notes">Catatan <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="timeline_notes" rows="3" placeholder="mis. Kirim sample ke vendor, revisi desain, submit notifikasi"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary" id="timelineSaveBtn"><i class="fas fa-save mr-1"></i> Simpan</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal: ganti kemasan dari tabel --}}
<div class="modal fade" id="kemasanPickerModal" tabindex="-1" role="dialog" aria-labelledby="kemasanPickerModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="kemasanPickerModalLabel">Pilih Kemasan</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="input-group input-group-sm mb-2">
                    <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-search"></i></span></div>
                    <input type="search" class="form-control" id="kemasanPickerSearch" placeholder="Cari nama kemasan" autocomplete="off">
                </div>
                <div class="list-group rnd-picker-list" id="kemasanPickerList"></div>
                <div class="form-group mt-3">
                    <label for="kemasanPickerVendorId">Vendor Kemasan</label>
                    <select class="form-control select2-kemasan-modal" id="kemasanPickerVendorId"></select>
                </div>
                <div class="form-group mb-0">
                    <label for="kemasanPickerDesainVendorId">Vendor Desain</label>
                    <select class="form-control select2-kemasan-modal" id="kemasanPickerDesainVendorId"></select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="saveKemasanPickerBtn"><i class="fas fa-save mr-1"></i> Simpan</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal: Kelola Brand / Kemasan / Vendor --}}
<div class="modal fade" id="masterModal" tabindex="-1" role="dialog" aria-labelledby="masterModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="masterModalLabel">Kelola</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <form id="masterForm" class="mb-3" autocomplete="off">
                    <label class="small text-muted mb-1" id="masterFormTitle">Tambah baru</label>
                    <div class="form-row align-items-start" id="masterFormFields"></div>
                    <div class="text-right">
                        <button type="button" class="btn btn-sm btn-light border d-none" id="btnMasterCancelEdit">Batal edit</button>
                        <button type="submit" class="btn btn-sm btn-primary" id="btnMasterSave"><i class="fas fa-plus mr-1"></i> Tambah</button>
                    </div>
                </form>

                <div class="input-group input-group-sm mb-2">
                    <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-search"></i></span></div>
                    <input type="search" id="masterSearch" class="form-control" placeholder="Cari" autocomplete="off">
                </div>
                <table id="masterTable" class="table table-sm table-hover w-100">
                    {{-- Header cells come from the DataTables column titles of the active master --}}
                    <thead class="thead-light"></thead>
                    <tbody></tbody>
                </table>
                <small class="text-muted d-block">Data yang masih dipakai produk tidak bisa dihapus; ganti dulu di produknya.</small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal: Rilis ke Master Obat --}}
<div class="modal fade" id="releaseModal" tabindex="-1" role="dialog" aria-labelledby="releaseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <form class="modal-content" id="releaseForm">
            <div class="modal-header">
                <h5 class="modal-title" id="releaseModalLabel">Rilis ke Master Obat</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="release_produk_id">

                {{-- Shown when the product is already linked --}}
                <div id="releaseLinkedPane" class="d-none">
                    <div class="alert alert-success mb-0">
                        Produk ini sudah terhubung ke Master Obat: <strong id="releaseLinkedObat"></strong>.
                        <div class="small mt-1">Harga, stok, dan nama obat dikelola Farmasi di Master Obat. Perubahan data R&amp;D tidak mengubah obat.</div>
                    </div>
                </div>

                <div id="releaseFormPane">
                    <div class="btn-group btn-group-toggle w-100 mb-3" data-toggle="buttons">
                        <label class="btn btn-outline-primary active" id="releaseModeNewLabel">
                            <input type="radio" name="release_mode" value="new" checked> Buat obat baru
                        </label>
                        <label class="btn btn-outline-primary">
                            <input type="radio" name="release_mode" value="link"> Hubungkan ke obat yang sudah ada
                        </label>
                    </div>

                    <div id="releaseNotReady" class="alert alert-warning small d-none">
                        <strong>Belum bisa dibuat sebagai obat baru.</strong> Lengkapi dulu:
                        <ul class="mb-1 pl-3" id="releaseMissingList"></ul>
                        Produk lama yang sudah ada di Master Obat tetap bisa <strong>dihubungkan</strong> tanpa syarat ini.
                    </div>

                    <div id="releaseNewFields">
                        <div class="form-group">
                            <label for="release_nama">Nama Obat</label>
                            <input type="text" class="form-control" id="release_nama" maxlength="191">
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label for="release_satuan_stok">Satuan Stok</label>
                                <select class="form-control" id="release_satuan_stok"></select>
                            </div>
                            <div class="form-group col-md-4">
                                <label for="release_dosis">Dosis / Netto</label>
                                <input type="text" class="form-control" id="release_dosis" maxlength="191">
                            </div>
                            <div class="form-group col-md-4">
                                <label for="release_satuan">Satuan Dosis</label>
                                <select class="form-control" id="release_satuan"></select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="release_metode_bayar_id">Metode Bayar</label>
                            <select class="form-control" id="release_metode_bayar_id"></select>
                        </div>
                        <div class="small text-muted">
                            Kategori <strong>Produk</strong> (kode PRD dibuat otomatis). Zat aktif disalin dari produk:
                            <strong id="releaseZatAktif">-</strong>.
                            Harga &amp; HPP tidak diisi di sini: HPP dari faktur pembelian, harga jual lewat pengajuan harga.
                        </div>
                    </div>

                    <div id="releaseLinkFields" class="d-none">
                        <div class="form-group mb-1">
                            <label for="release_obat_id">Obat di Master Obat</label>
                            <select class="form-control" id="release_obat_id" style="width:100%"></select>
                        </div>
                        <div class="small text-muted">Hanya obat yang belum terhubung ke produk R&amp;D lain. Data obat tidak diubah.</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-danger mr-auto d-none" id="releaseUnlinkBtn">Lepas Tautan</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                <button type="submit" class="btn btn-primary" id="releaseSubmitBtn">Rilis</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<style>
    .rnd-page .filter-bar label { font-weight: 600; }
    .page-wrapper { min-width: 0; }
    .rnd-pills .nav-link { font-weight: 600; }
    .rnd-card { border: 1px solid #e3e8f0; border-radius: .5rem; padding: .55rem .8rem; background: #fff; cursor: pointer; height: 100%; transition: box-shadow .15s, border-color .15s; }
    .rnd-card:hover { box-shadow: 0 2px 8px rgba(0,0,0,.06); }
    .rnd-card.active { border-color: #4e73df; box-shadow: 0 0 0 2px rgba(78,115,223,.25); }
    .rnd-card .rnd-card-num { font-size: 1.4rem; font-weight: 700; line-height: 1.1; }
    .rnd-card .rnd-card-lbl { font-size: .78rem; color: #6c7a92; }
    .rnd-card .rnd-card-hint { font-size: .72rem; color: #8a94a6; }
    #produkTable td { vertical-align: top; }
    #produkTable_wrapper th { white-space: nowrap; vertical-align: middle; }
    #produkTable td.col-produk { min-width: 240px; max-width: 300px; white-space: normal; }
    #produkTable td.rnd-w-md { min-width: 160px; }
    #produkTable td.rnd-w-lg { min-width: 210px; }
    #produkTable td.col-aksi { white-space: nowrap; vertical-align: middle; }
    .rnd-actions .btn { white-space: nowrap; }
    /* Product flow stepper: Sample > Kemasan > Administrasi > Legalitas > Rilis */
    .rnd-stepper { display: flex; align-items: center; }
    .rnd-stepper-item { display: inline-flex; align-items: center; gap: 4px; white-space: nowrap; }
    .rnd-stepper-dot { width: 20px; height: 20px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center;
        font-size: 10px; font-weight: 700; background: #e9ecef; color: #6c757d; border: 1px solid #dee2e6; }
    .rnd-stepper-item.is-done .rnd-stepper-dot { background: #1cc88a; border-color: #1cc88a; color: #fff; }
    .rnd-stepper-item.is-current .rnd-stepper-dot { background: #fff; border: 2px solid #4e73df; color: #4e73df; }
    .rnd-stepper-item.is-current .rnd-stepper-label { color: #4e73df; font-weight: 600; }
    .rnd-stepper-label { font-size: .8rem; color: #6c757d; }
    .rnd-stepper-line { flex: 1 1 12px; min-width: 8px; height: 2px; background: #dee2e6; margin: 0 4px; }
    .rnd-stepper-sm .rnd-stepper-dot { width: 16px; height: 16px; font-size: 8px; }
    .rnd-stepper-sm .rnd-stepper-line { flex: 0 0 8px; margin: 0 2px; }
    .rnd-step-no { display: inline-flex; width: 18px; height: 18px; border-radius: 50%; align-items: center; justify-content: center;
        font-size: 10px; font-weight: 700; background: #4e73df; color: #fff; margin-right: 2px; }
    .rnd-step-no.is-done { background: #1cc88a; }
    .rnd-flow-tabs .nav-link { font-weight: 600; }
    .rnd-status-pair { white-space: nowrap; }
    /* Calendar event: note (max 2 lines) + product name */
    .rnd-event { padding: 1px 3px; overflow: hidden; white-space: normal; line-height: 1.25; }
    .rnd-event-notes { font-size: .78rem; font-weight: 600; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; white-space: normal; }
    .rnd-event-produk { font-size: .68rem; opacity: .85; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .rnd-legend { display: inline-block; width: 10px; height: 10px; border-radius: 2px; background: var(--c); margin-right: 3px; vertical-align: middle; }
    .rnd-timeline-item { border-left-width: 4px !important; }
    .rnd-chips { display: flex; flex-wrap: wrap; gap: 3px; max-width: 230px; }
    .rnd-chips .badge { font-weight: 500; }
    .rnd-checklist li { padding: 1px 0; }
    .rnd-sertifikasi-group { border: 1px solid #e3e8f0; border-radius: .4rem; padding: .5rem .7rem; margin-bottom: .5rem; }
    .rnd-sertifikasi-item + .rnd-sertifikasi-item { border-top: 1px dashed #e3e8f0; margin-top: .4rem; padding-top: .4rem; }
    /* Fixed columns (No, Produk, Aksi) need an opaque background so scrolled cells do not show through */
    #produkTable_wrapper table.dataTable thead .dtfc-fixed-left,
    #produkTable_wrapper table.dataTable thead .dtfc-fixed-right { background-color: #f8f9fa !important; }
    #produkTable_wrapper table.dataTable tbody .dtfc-fixed-left,
    #produkTable_wrapper table.dataTable tbody .dtfc-fixed-right { background-color: #fff !important; }
    #produkTable_wrapper table.dataTable tbody tr:hover > .dtfc-fixed-left,
    #produkTable_wrapper table.dataTable tbody tr:hover > .dtfc-fixed-right { background-color: #f5f7fb !important; }
    #produkTable_wrapper td.col-produk { box-shadow: 3px 0 4px -2px rgba(0,0,0,.12); }
    #produkTable_wrapper td.dtfc-fixed-right, #produkTable_wrapper th.dtfc-fixed-right { box-shadow: -3px 0 4px -2px rgba(0,0,0,.12); }
    .theme-dark #produkTable_wrapper table.dataTable tbody .dtfc-fixed-left,
    .theme-dark #produkTable_wrapper table.dataTable tbody .dtfc-fixed-right { background-color: #2a3042 !important; }
    .theme-dark #produkTable_wrapper table.dataTable thead .dtfc-fixed-left,
    .theme-dark #produkTable_wrapper table.dataTable thead .dtfc-fixed-right { background-color: #2f3548 !important; }
    #produkTable .rnd-name { font-weight: 600; color: inherit; text-align: left; white-space: normal; }
    #produkTable .rnd-name:hover { color: #0b5ed7; text-decoration: underline; }
    .rnd-meta { font-size: .8rem; color: #6c757d; }
    .rnd-sub { font-size: .7rem; font-weight: 600; text-transform: uppercase; letter-spacing: .03em; color: #8a94a6; }
    .rnd-line { display: flex; justify-content: space-between; align-items: center; gap: 6px; }
    .rnd-status, .rnd-link { border: 0; background: none; padding: 0; cursor: pointer; line-height: 1.2; }
    .rnd-status .badge { font-weight: 500; }
    .rnd-link { color: #0b5ed7; font-size: .8rem; text-align: left; }
    .rnd-link:hover { text-decoration: underline; }
    #produkTable .btn-group .btn { min-width: 30px; }
    .badge-zat { background: #e7f1ff; color: #0b5ed7; font-weight: 500; margin: 1px; }
    .form-section { font-size: .78rem; text-transform: uppercase; letter-spacing: .04em; color: #6c757d; border-bottom: 1px solid #e9ecef; padding-bottom: 4px; margin: 14px 0 10px; }
    .tab-pane > .form-section:first-child { margin-top: 0; }
    .rnd-log-list { max-height: 460px; overflow-y: auto; }
    .rnd-log-item { border-bottom: 1px solid #f1f3f7; padding: 6px 0; font-size: .82rem; }
    .rnd-log-item .rnd-log-notes { white-space: pre-wrap; }
    .rnd-picker-list { max-height: 280px; overflow-y: auto; }
    .rnd-timeline-list { max-height: 560px; overflow-y: auto; }
    .rnd-timeline-item { border: 1px solid #e3e8f0; border-radius: .4rem; padding: .5rem .7rem; margin-bottom: .5rem; }
    .rnd-timeline-item .rnd-timeline-notes { white-space: pre-wrap; font-size: .85rem; }
    #productTimelineCalendar .fc-toolbar-title { font-size: 1.1rem; font-weight: 600; }
    .select2-container .select2-selection.is-invalid { border-color: #dc3545; }
    /* Selected zat aktif chips: readable dark-on-light pair (same as Master Obat) */
    #produkModal .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background: #e7f1ff !important; border: 1px solid #b6d4fe !important; color: #084298 !important; font-weight: 500; padding: 2px 8px;
    }
    #produkModal .select2-container--default .select2-selection--multiple .select2-selection__choice__remove { color: #084298 !important; margin-right: 4px; }
    .theme-dark .rnd-card { background: #2c3144; border-color: #3a4057; }
</style>
<script src="{{ asset('dastone/vendor/datatable/FixedColumns-4.3.0/js/dataTables.fixedColumns.min.js') }}"></script>
<script src="{{ asset('fullcalendar/dist/index.global.js') }}"></script>
<script src="{{ asset('fullcalendar/packages/core/locales/id.global.min.js') }}"></script>
<script>
(function () {
    'use strict';

    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

    const URLS = {
        produk: @json(url('/rnd/produk')),
        data: @json(route('rnd.products.data')),
        store: @json(route('rnd.products.store')),
        options: @json(route('rnd.products.options')),
        timelineData: @json(route('rnd.products.timeline-data')),
        obatSearch: @json(route('rnd.products.obat-search')),
        sampleLog: @json(url('/rnd/produk/sample-log')),
        documents: @json(url('/rnd/produk/documents')),
        masters: @json(url('/rnd/masters')),
        export: @json(route('rnd.products.export')),
        userSearch: @json(route('rnd.products.user-search')),
        timelines: @json(url('/rnd/produk/timelines')),
        sertifikasi: @json(url('/rnd/sertifikasi'))
    };
    const JENIS_SERTIFIKASI = @json($jenisSertifikasi);
    const EVALUASI_FIELDS = @json($evaluasiFields);
    const STATUS_OPTIONS = {
        kemasan: @json($statusKemasanOptions),
        desain: @json($statusDesainOptions),
        fpp: @json($statusAdministrasiFppOptions),
        spk: @json($statusAdministrasiSpkOptions),
        notif: @json($statusAdministrasiNotifOptions),
        sample: @json($statusSampleOptions)
    };
    const INLINE_STATUS_OPTIONS = {
        status_kemasan_primer: STATUS_OPTIONS.kemasan,
        status_kemasan_sekunder: STATUS_OPTIONS.kemasan,
        status_desain_kemasan_primer: STATUS_OPTIONS.desain,
        status_desain_kemasan_sekunder: STATUS_OPTIONS.desain,
        status_administrasi_fpp: STATUS_OPTIONS.fpp,
        status_administrasi_spk: STATUS_OPTIONS.spk,
        status_administrasi_notif: STATUS_OPTIONS.notif,
        status_sample: STATUS_OPTIONS.sample
    };
    const MASTERS = {};
    @json($masters).forEach(function (m) { MASTERS[m.key] = m; });

    let masterOptions = @json($masterOptions);
    let table = null;
    let tahap = '';

    // ---------- helpers ----------
    function esc(value) {
        return $('<div>').text(value == null ? '' : String(value)).html();
    }

    function dash(value) {
        return value == null || String(value).trim() === '' ? '-' : esc(value);
    }

    function ucfirst(value) {
        value = String(value || '');
        return value.charAt(0).toUpperCase() + value.slice(1);
    }

    function errorMessage(xhr, fallback) {
        const res = xhr.responseJSON || {};
        if (xhr.status === 422 && res.errors) {
            return Object.values(res.errors).flat().join('<br>');
        }
        return res.message || fallback;
    }

    function toast(message) {
        // A successful action makes an earlier error in an open modal stale
        $('.modal.show').each(function () { formError($(this), ''); });
        Swal.fire({ title: 'Sukses', text: message, icon: 'success', timer: 1400, showConfirmButton: false });
    }

    /** Validation / save errors of a modal form are shown inside that modal (Swal is only for notices and confirmations). */
    function formError($modal, message) {
        const $body = $modal.find('.modal-body').first();
        let $alert = $body.children('.js-form-error');
        if (!$alert.length) {
            $alert = $('<div class="alert alert-danger small py-2 js-form-error d-none" role="alert"></div>').prependTo($body);
        }
        $alert.html(message || '').toggleClass('d-none', !message);
        if (message) $body.scrollTop(0);
    }

    $(document).on('show.bs.modal', '.modal', function (e) {
        if (e.target === this) formError($(this), '');
    });

    /** Today in the browser's timezone (toISOString() is UTC, a day behind before 07:00 WIB). */
    function localToday() {
        const d = new Date();
        return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    }

    // Rows of the current table page, so modals can show which product they are for
    const rowsById = {};

    function produkName(row) {
        if (!row) return '';
        // Older products use a unit (mL, g) as sediaan: do not repeat it after the netto satuan
        const sediaan = row.sediaan_name === '-' || String(row.sediaan_name || '').toLowerCase() === String(row.netto_satuan || '').toLowerCase() ? '' : row.sediaan_name;
        return [row.brand_name === '-' ? '' : row.brand_name, row.nama_produk, row.netto, row.netto_satuan, sediaan]
            .filter(function (p) { return p && String(p).trim() !== ''; }).join(' ');
    }

    function formatRupiah(value) {
        return 'Rp ' + Number(value || 0).toLocaleString('id-ID', { maximumFractionDigits: 2 });
    }

    const MASA_BERLAKU = {
        berlaku: { cls: 'badge-success', text: 'berlaku' },
        segera_habis: { cls: 'badge-warning', text: 'segera habis' },
        kedaluwarsa: { cls: 'badge-danger', text: 'kedaluwarsa' },
        belum: { cls: 'badge-light border text-muted', text: 'belum ada' }
    };

    const STATUS_LABELS = {
        status_kemasan_primer: 'Status Kemasan Primer',
        status_kemasan_sekunder: 'Status Kemasan Sekunder',
        status_desain_kemasan_primer: 'Status Desain Kemasan Primer',
        status_desain_kemasan_sekunder: 'Status Desain Kemasan Sekunder',
        status_administrasi_fpp: 'Status FPP',
        status_administrasi_spk: 'Status SPK',
        status_administrasi_notif: 'Status Notifikasi BPOM',
        status_sample: 'Status Sample (terbaru)'
    };

    function formatDate(value, withTime) {
        if (!value) return '-';
        const date = new Date(withTime ? value : value + 'T00:00:00');
        if (isNaN(date.getTime())) return esc(value);
        const opts = { day: '2-digit', month: 'short', year: 'numeric' };
        if (withTime) { opts.hour = '2-digit'; opts.minute = '2-digit'; }
        return esc(date.toLocaleString('id-ID', opts));
    }

    function statusBadge(value) {
        if (!value) return '<span class="badge badge-light border text-muted">belum</span>';
        const s = String(value).toLowerCase();
        const cls = s === 'done' ? 'badge-success' : (s === 'revisi' ? 'badge-danger' : 'badge-warning');
        return '<span class="badge ' + cls + '">' + esc(value) + '</span>';
    }

    function statusTrigger(productId, field, value) {
        return '<button type="button" class="rnd-status js-inline-status" data-id="' + productId + '" data-field="' + field + '" data-value="' + esc(value || '') + '" title="Ubah status">' + statusBadge(value) + '</button>';
    }

    // ---------- master options (Brand / Kemasan / Vendor) ----------
    function fillSelect($select, items, placeholder) {
        const current = $select.val();
        $select.empty();
        if (placeholder !== null) $select.append(new Option(placeholder, ''));
        items.forEach(function (item) { $select.append(new Option(item.label, item.id)); });
        if (current !== null && current !== '' && $select.find('option[value="' + current + '"]').length) {
            $select.val(current);
        } else {
            $select.val('');
        }
        $select.trigger('change.select2');
    }

    function kemasansOf(tipe) {
        return masterOptions.kemasans.filter(function (k) { return k.tipe_kemasan === tipe; });
    }

    function applyMasterOptions() {
        const v = masterOptions.vendors;
        fillSelect($('#filterBrand'), masterOptions.brands, 'Semua brand');
        fillSelect($('#brand_id'), masterOptions.brands, 'Pilih Brand');
        fillSelect($('#produsen_vendor_id'), v.produsen, 'Pilih Produsen');
        fillSelect($('#kemasan_premier_id'), kemasansOf('primer'), 'Pilih Kemasan Primer');
        fillSelect($('#kemasan_sekunder_id'), kemasansOf('sekunder'), 'Tanpa Kemasan Sekunder');
        fillSelect($('#kemasan_primer_vendor_id'), v.kemasan, 'Pilih Vendor Kemasan');
        fillSelect($('#kemasan_sekunder_vendor_id'), v.kemasan, 'Pilih Vendor Kemasan');
        fillSelect($('#desain_kemasan_primer_id'), v.desain, 'Pilih Vendor Desain');
        fillSelect($('#desain_kemasan_sekunder_id'), v.desain, 'Pilih Vendor Desain');
        fillSelect($('#kemasanPickerVendorId'), v.kemasan, 'Pilih Vendor Kemasan');
        fillSelect($('#kemasanPickerDesainVendorId'), v.desain, 'Pilih Vendor Desain');
        fillSelect($('#timelineFilterProduk'), masterOptions.produks || [], 'Semua produk');
        fillSelect($('#timeline_produk_select'), masterOptions.produks || [], 'Pilih produk');
    }

    function refreshMasterOptions() {
        $.getJSON(URLS.options, function (res) {
            masterOptions = res;
            applyMasterOptions();
        });
    }

    /** Set a select to an id, adding the option when the master no longer lists it there (so saving does not clear it). */
    function setSelectValue($select, id, label) {
        id = id == null ? '' : String(id);
        if (id !== '' && !$select.find('option[value="' + id + '"]').length) {
            $select.append(new Option((label || ('#' + id)) + ' (tidak sesuai tipe di master)', id));
        }
        $select.val(id).trigger('change');
    }

    function kemasanLabel(k) {
        return k ? k.nama_kemasan + (k.ukuran ? ' (' + k.ukuran + ')' : '') : '';
    }

    // ---------- table ----------
    // ---------- product flow: 1 Sample -> 2 Kemasan -> 3 Administrasi -> 4 Legalitas -> Rilis ----------
    const FLOW = [
        { key: 'sample', label: 'Sample' },
        { key: 'kemasan', label: 'Kemasan' },
        { key: 'administrasi', label: 'Administrasi' },
        { key: 'legalitas', label: 'Legalitas' },
        { key: 'rilis', label: 'Rilis' }
    ];

    /** Which flow steps are done, from a table row. */
    function flowFromRow(row) {
        const done = function (v) { return v === 'done'; };
        return {
            sample: done(row.latest_sample_status),
            kemasan: done(row.status_kemasan_primer) && done(row.status_desain_kemasan_primer)
                && (!row.kemasan_sekunder_id || (done(row.status_kemasan_sekunder) && done(row.status_desain_kemasan_sekunder))),
            administrasi: done(row.status_administrasi_fpp) && done(row.status_administrasi_spk) && done(row.status_administrasi_notif),
            legalitas: (row.legalitas || []).filter(function (c) { return c.wajib; })
                .every(function (c) { return c.state === 'berlaku' || c.state === 'segera_habis'; }),
            rilis: !!row.obat_id
        };
    }

    /** Same, from the release checklist of the product modal. */
    function flowFromChecklist(checklist, released) {
        const ok = function (prefix) {
            const items = (checklist || []).filter(function (i) { return i.key.indexOf(prefix) === 0; });
            return items.length > 0 && items.every(function (i) { return i.ok; });
        };
        return { sample: ok('sample'), kemasan: ok('kemasan_'), administrasi: ok('administrasi_'), legalitas: ok('sertifikasi_'), rilis: !!released };
    }

    /** Current step = first step not done (released products are at "Rilis"). */
    function currentStep(flow) {
        if (flow.rilis) return 'rilis';
        const step = FLOW.filter(function (s) { return !flow[s.key]; })[0];
        return step ? step.key : 'rilis';
    }

    function renderStepper(flow, compact) {
        const current = currentStep(flow);
        return '<div class="rnd-stepper' + (compact ? ' rnd-stepper-sm' : '') + '">' + FLOW.map(function (s, i) {
            const state = flow[s.key] ? 'done' : (s.key === current ? 'current' : 'todo');
            const icon = flow[s.key] ? '<i class="fas fa-check"></i>' : (i + 1);
            return '<span class="rnd-stepper-item is-' + state + '" title="' + esc(s.label) + (flow[s.key] ? ': selesai' : (state === 'current' ? ': sedang dikerjakan' : '')) + '">'
                + '<span class="rnd-stepper-dot">' + (s.key === 'rilis' ? (flow.rilis ? icon : '<i class="fas fa-flag-checkered"></i>') : icon) + '</span>'
                + (compact ? '' : '<span class="rnd-stepper-label">' + esc(s.label) + '</span>')
                + '</span>';
        }).join('<span class="rnd-stepper-line"></span>') + '</div>';
    }

    function renderTahap(row) {
        if (row.status_produk && row.status_produk !== 'aktif' && !row.obat_id) {
            return '<span class="badge badge-secondary">' + esc(ucfirst(row.status_produk)) + '</span>';
        }
        const flow = flowFromRow(row);
        const current = FLOW.filter(function (s) { return s.key === currentStep(flow); })[0];
        let label = current.label;
        if (flow.rilis) label = 'Dirilis';
        else if (row.siap_rilis) label = 'Siap rilis';
        return renderStepper(flow, true) + '<div class="small mt-1 ' + (flow.rilis ? 'text-success' : (row.siap_rilis ? 'text-primary' : 'text-muted')) + '">' + esc(label) + '</div>';
    }

    function renderProduk(row) {
        const name = produkName(row);
        const badges = [];
        if (row.obat_kode || row.obat_nama) {
            badges.push('<span class="badge badge-success" title="' + esc(row.obat_nama || '') + '"><i class="fas fa-check-circle mr-1"></i>' + esc(row.obat_kode || 'Master Obat') + '</span>');
        }
        const meta = [];
        if (row.pic_name) meta.push('<i class="fas fa-user mr-1"></i>' + esc(row.pic_name));
        if (row.target_rilis) {
            const late = !row.obat_id && row.target_rilis < localToday() && row.status_produk === 'aktif';
            meta.push('<span class="' + (late ? 'text-danger font-weight-bold' : '') + '" title="Target rilis"><i class="far fa-flag mr-1"></i>' + formatDate(row.target_rilis) + (late ? ' (lewat)' : '') + '</span>');
        }
        return '<button type="button" class="btn btn-link p-0 rnd-name js-edit-product" data-id="' + row.id + '">' + esc(name || '-') + '</button>'
            + (badges.length ? '<div class="mt-1">' + badges.join(' ') + '</div>' : '')
            + (meta.length ? '<div class="rnd-meta mt-1">' + meta.join(' &nbsp;·&nbsp; ') + '</div>' : '');
    }

    function renderZatAktif(row) {
        if (!row.zat_aktif_names || row.zat_aktif_names === '-') return '<span class="text-muted">-</span>';
        return row.zat_aktif_names.split(', ').map(function (z) { return '<span class="badge badge-zat">' + esc(z) + '</span>'; }).join(' ');
    }

    function renderSample(row) {
        if (!row.has_sample_log) {
            return '<button type="button" class="rnd-link js-add-sample" data-id="' + row.id + '"><i class="fas fa-plus"></i> Tambah sample</button>';
        }
        return '<div class="rnd-line"><button type="button" class="rnd-link font-weight-bold js-add-sample" data-id="' + row.id + '" title="Kelola sample">'
            + dash(row.latest_sample_no_produksi) + '</button>' + statusTrigger(row.id, 'status_sample', row.latest_sample_status) + '</div>';
    }

    function renderKemasanBlock(row, side) {
        const primer = side === 'primer';
        const field = primer ? 'kemasan_premier_id' : 'kemasan_sekunder_id';
        const kemasanId = row[field];
        const vendorId = primer ? row.kemasan_primer_vendor_id : row.kemasan_sekunder_vendor_id;
        const desainId = primer ? row.desain_kemasan_primer_id : row.desain_kemasan_sekunder_id;
        const trigger = '<button type="button" class="rnd-link js-inline-kemasan" data-id="' + row.id + '" data-field="' + field + '"'
            + ' data-value="' + esc(kemasanId || '') + '" data-vendor="' + esc(vendorId || '') + '" data-desain="' + esc(desainId || '') + '" title="Ganti kemasan / vendor">';

        if (!kemasanId) {
            return primer ? trigger + '<i class="fas fa-plus"></i> Pilih kemasan</button>'
                : '<span class="text-muted small">Tidak ada</span> ' + trigger + '<i class="fas fa-plus"></i></button>';
        }

        const name = primer ? row.kemasan_premier_name : row.kemasan_sekunder_name;
        const vendor = primer ? row.kemasan_primer_vendor_name : row.kemasan_sekunder_vendor_name;
        const desain = primer ? row.desain_kemasan_primer_vendor_name : row.desain_kemasan_sekunder_vendor_name;
        // One line for the kemasan, one for the two statuses; vendors in the tooltip to keep the cell short
        const vendors = 'Vendor kemasan: ' + (vendor || '-') + '\nVendor desain: ' + (desain || '-');
        return trigger.replace('title="Ganti kemasan / vendor"', 'title="' + esc(vendors + '\n(klik untuk ganti)') + '"') + esc(name) + '</button>'
            + '<div class="rnd-status-pair mt-1">'
            + '<span class="rnd-sub">Kemasan</span> ' + statusTrigger(row.id, primer ? 'status_kemasan_primer' : 'status_kemasan_sekunder', primer ? row.status_kemasan_primer : row.status_kemasan_sekunder)
            + ' <span class="rnd-sub ml-2">Desain</span> ' + statusTrigger(row.id, primer ? 'status_desain_kemasan_primer' : 'status_desain_kemasan_sekunder', primer ? row.status_desain_kemasan_primer : row.status_desain_kemasan_sekunder)
            + '</div>';
    }

    function renderAdministrasi(row, field) {
        const value = row[field];
        let extra = '';
        if (field === 'status_administrasi_notif' && value === 'done' && row.latest_notif_tanggal_selesai) {
            extra = (row.latest_notif_nomor ? '<div class="rnd-meta mt-1">' + esc(row.latest_notif_nomor) + '</div>' : '')
                + '<div class="rnd-meta">s/d ' + formatDate(row.latest_notif_tanggal_selesai)
                + (row.latest_notif_document_url ? ' · <a href="' + encodeURI(row.latest_notif_document_url) + '" target="_blank" rel="noopener noreferrer">PDF</a>' : '')
                + '</div>';
        }
        return statusTrigger(row.id, field, value) + extra;
    }

    function renderLegalitas(row) {
        return '<div class="rnd-chips">' + (row.legalitas || []).map(function (chip) {
            const state = MASA_BERLAKU[chip.state] || MASA_BERLAKU.belum;
            const title = chip.label + (chip.wajib ? ' (wajib)' : '') + ': '
                + (chip.berlaku_sampai ? 'berlaku s/d ' + chip.berlaku_sampai : 'belum ada') + ' (' + state.text + ')';
            return '<button type="button" class="rnd-status js-open-legalitas" data-id="' + row.id + '" title="' + esc(title) + '">'
                + '<span class="badge ' + state.cls + '">' + esc(chip.short) + '</span></button>';
        }).join(' ') + '</div>';
    }

    function renderAksi(row) {
        let release;
        if (row.obat_kode || row.obat_nama) {
            release = '<button type="button" class="btn btn-sm btn-outline-success js-release-product" data-id="' + row.id + '" title="Terhubung ke Master Obat ' + esc(row.obat_kode || '') + '"><i class="fas fa-check-circle mr-1"></i>Dirilis</button>';
        } else if (row.siap_rilis) {
            release = '<button type="button" class="btn btn-sm btn-outline-primary js-release-product" data-id="' + row.id + '" title="Semua syarat lengkap: rilis sebagai obat baru"><i class="fas fa-share-square mr-1"></i>Rilis</button>';
        } else {
            // Not ready as a new obat, but an older product can still be linked to an existing obat
            release = '<button type="button" class="btn btn-sm btn-outline-secondary js-release-product" data-id="' + row.id + '" title="Syarat rilis obat baru belum lengkap; masih bisa dihubungkan ke obat yang sudah ada"><i class="fas fa-link mr-1"></i>Rilis</button>';
        }
        return '<div class="btn-group btn-group-sm rnd-actions" role="group">'
            + '<button type="button" class="btn btn-sm btn-outline-primary js-edit-product" data-id="' + row.id + '"><i class="fas fa-edit mr-1"></i>Edit</button>'
            + '<button type="button" class="btn btn-sm btn-outline-info js-add-product-timeline" data-id="' + row.id + '"><i class="fas fa-calendar-plus mr-1"></i>Timeline</button>'
            + release
            + '<button type="button" class="btn btn-sm btn-outline-danger js-delete-product" data-id="' + row.id + '"><i class="fas fa-trash mr-1"></i>Hapus</button>'
            + '</div>';
    }

    function initTable() {
        table = $('#produkTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: URLS.data,
                data: function (d) {
                    d.tahap = tahap;
                    d.brand_id = $('#filterBrand').val() || '';
                }
            },
            order: [],
            pageLength: 25,
            scrollX: true,
            scrollCollapse: true,
            autoWidth: false,
            fixedColumns: { left: 2, right: 1 },
            dom: "rt<'row align-items-center mt-2'<'col-md-4'l><'col-md-4 text-center'i><'col-md-4'p>>",
            language: {
                lengthMenu: 'Tampilkan _MENU_',
                info: '_START_–_END_ dari _TOTAL_ produk',
                infoEmpty: 'Tidak ada produk',
                infoFiltered: '',
                zeroRecords: 'Tidak ada produk yang cocok dengan filter/pencarian.',
                processing: 'Memuat...',
                paginate: { previous: '&lsaquo;', next: '&rsaquo;' }
            },
            columns: [
                { data: null, orderable: false, searchable: false, render: function (d, t, row, meta) { return meta.row + meta.settings._iDisplayStart + 1; } },
                { data: 'nama_produk', name: 'nama_produk', className: 'col-produk', render: function (d, t, row) { return renderProduk(row); } },
                { data: null, orderable: false, searchable: false, className: 'text-nowrap', render: function (d, t, row) { return renderTahap(row); } },
                { data: null, orderable: false, searchable: false, className: 'rnd-w-md', render: function (d, t, row) { return row.produsen_vendor_name ? esc(row.produsen_vendor_name) : '<span class="text-muted">-</span>'; } },
                { data: null, orderable: false, searchable: false, className: 'rnd-w-lg', render: function (d, t, row) { return renderZatAktif(row); } },
                { data: null, orderable: false, searchable: false, className: 'rnd-w-md', render: function (d, t, row) { return renderSample(row); } },
                { data: null, orderable: false, searchable: false, className: 'rnd-w-lg', render: function (d, t, row) { return renderKemasanBlock(row, 'primer'); } },
                { data: null, orderable: false, searchable: false, className: 'rnd-w-lg', render: function (d, t, row) { return renderKemasanBlock(row, 'sekunder'); } },
                { data: null, orderable: false, searchable: false, className: 'text-center', render: function (d, t, row) { return renderAdministrasi(row, 'status_administrasi_fpp'); } },
                { data: null, orderable: false, searchable: false, className: 'text-center', render: function (d, t, row) { return renderAdministrasi(row, 'status_administrasi_spk'); } },
                { data: null, orderable: false, searchable: false, className: 'text-center text-nowrap', render: function (d, t, row) { return renderAdministrasi(row, 'status_administrasi_notif'); } },
                { data: null, orderable: false, searchable: false, className: 'rnd-w-lg', render: function (d, t, row) { return renderLegalitas(row); } },
                { data: null, orderable: false, searchable: false, className: 'col-aksi', render: function (d, t, row) { return renderAksi(row); } }
            ]
        });

        table.on('xhr.dt', function (e, settings, json) {
            ((json && json.data) || []).forEach(function (row) { rowsById[row.id] = row; });
            const counts = (json && json.counts) || {};
            Object.keys(counts).forEach(function (key) {
                $('[data-count="' + key + '"]').text(counts[key]);
            });
        });
    }

    function reloadTable() {
        if (table) table.ajax.reload(null, false);
    }

    // ---------- filters ----------
    let searchTimer = null;
    $('#filterSearch').on('input', function () {
        clearTimeout(searchTimer);
        const value = $(this).val();
        searchTimer = setTimeout(function () { table.search(value.trim()).draw(); }, 350);
    });

    $('#filterBrand').on('change', function () { table.draw(); });

    $('#rndCards').on('click', '.rnd-card', function () {
        $('#rndCards .rnd-card').removeClass('active');
        $(this).addClass('active');
        tahap = $(this).data('tahap') || '';
        table.draw();
    });

    $('#btnExportExcel').on('click', function () {
        const params = new URLSearchParams({ tahap: tahap, brand_id: $('#filterBrand').val() || '', q: ($('#filterSearch').val() || '').trim() });
        window.location.href = URLS.export + '?' + params.toString();
    });

    $('#btnResetFilter').on('click', function () {
        $('#filterSearch').val('');
        $('#filterBrand').val('').trigger('change.select2');
        $('#rndCards .rnd-card').removeClass('active').first().addClass('active');
        tahap = '';
        table.search('').draw();
    });

    // ---------- ubah status (modal) ----------
    function selectedStatus() {
        return $('#statusOptions input[name="status_value"]:checked').val();
    }

    function toggleNotifFields() {
        const show = $('#status_field').val() === 'status_administrasi_notif' && selectedStatus() === 'done'
            && $('#status_current').val() !== 'done';
        $('#notifFields').toggleClass('d-none', !show);
    }

    $('body').on('click', '.js-inline-status', function () {
        const id = $(this).data('id');
        const field = String($(this).data('field'));
        const current = String($(this).data('value') || '');
        const options = (INLINE_STATUS_OPTIONS[field] || []).map(function (o) { return { value: o, label: ucfirst(o) }; });
        options.push({ value: '', label: 'Kosongkan status' });

        $('#statusForm')[0].reset();
        $('#status_produk_id').val(id);
        $('#status_field').val(field);
        $('#status_current').val(current);
        $('#statusModalLabel').text(STATUS_LABELS[field] || 'Ubah Status');
        $('#statusModalProduk').text(produkName(rowsById[id]));
        $('#statusOptions').html(options.map(function (o, i) {
            return '<label class="list-group-item list-group-item-action py-2 mb-0 d-flex align-items-center" for="status_opt_' + i + '" style="cursor:pointer">'
                + '<input type="radio" class="mr-2" name="status_value" id="status_opt_' + i + '" value="' + esc(o.value) + '"' + (o.value === current ? ' checked' : '') + '>'
                + (o.value ? statusBadge(o.value) : '<span class="text-muted">' + esc(o.label) + '</span>')
                + (o.value === current ? '<small class="text-muted ml-auto">saat ini</small>' : '')
                + '</label>';
        }).join(''));
        $('#notif_nomor').val('');
        $('#notif_tanggal_mulai').val('');
        $('#notif_tanggal_selesai').val('');
        toggleNotifFields();
        $('#statusModal').modal('show');
    });

    $('#statusOptions').on('change', 'input[name="status_value"]', toggleNotifFields);

    $('#statusForm').on('submit', function (e) {
        e.preventDefault();
        const $modal = $('#statusModal');
        const id = $('#status_produk_id').val();
        const field = $('#status_field').val();
        const next = selectedStatus();
        if (typeof next === 'undefined') return formError($modal, 'Pilih salah satu status.');
        if (next === $('#status_current').val()) { $modal.modal('hide'); return; }

        const fd = new FormData();
        fd.append('_method', 'PUT');
        fd.append('inline_status_update', '1');
        fd.append('field', field);
        fd.append('value', next);
        fd.append('log_notes', $.trim($('#status_log_notes').val() || ''));

        if (!$('#notifFields').hasClass('d-none')) {
            const nomor = $.trim($('#notif_nomor').val() || '');
            if (!nomor) return formError($modal, 'Nomor notifikasi BPOM wajib diisi.');
            fd.append('nomor', nomor);
            const mulai = $('#notif_tanggal_mulai').val();
            const selesai = $('#notif_tanggal_selesai').val();
            const file = ($('#notif_doc')[0].files || [])[0];
            if (!mulai || !selesai) return formError($modal, 'Tanggal mulai dan tanggal berlaku notifikasi wajib diisi.');
            if (selesai < mulai) return formError($modal, 'Tanggal berlaku tidak boleh sebelum tanggal mulai.');
            if (!file) return formError($modal, 'Dokumen notifikasi (PDF) wajib diupload.');
            if (!/\.pdf$/i.test(file.name)) return formError($modal, 'Dokumen notifikasi harus berupa PDF.');
            fd.append('tanggal_mulai', mulai);
            fd.append('tanggal_selesai', selesai);
            fd.append('notif_doc', file);
        }

        const $btn = $('#statusSaveBtn').prop('disabled', true);
        $.ajax({ url: URLS.produk + '/' + id, type: 'POST', data: fd, processData: false, contentType: false })
            .done(function (res) { $modal.modal('hide'); reloadTable(); toast(res.message || 'Status berhasil diperbarui.'); })
            .fail(function (xhr) { formError($modal, errorMessage(xhr, 'Terjadi kesalahan saat memperbarui status.')); })
            .always(function () { $btn.prop('disabled', false); });
    });

    // ---------- kemasan picker (from the table) ----------
    let picker = { productId: null, field: null, selected: '' };

    function renderKemasanPickerList() {
        const tipe = picker.field === 'kemasan_premier_id' ? 'primer' : 'sekunder';
        const keyword = $.trim($('#kemasanPickerSearch').val() || '').toLowerCase();
        const items = kemasansOf(tipe).filter(function (k) { return !keyword || k.label.toLowerCase().indexOf(keyword) !== -1; });
        let html = '';
        if (tipe === 'sekunder') {
            html += '<button type="button" class="list-group-item list-group-item-action py-2 js-pick-kemasan' + (picker.selected === '' ? ' active' : '') + '" data-value="">Tanpa kemasan sekunder</button>';
        }
        items.forEach(function (k) {
            html += '<button type="button" class="list-group-item list-group-item-action py-2 js-pick-kemasan' + (String(k.id) === picker.selected ? ' active' : '') + '" data-value="' + k.id + '">' + esc(k.label) + '</button>';
        });
        if (!items.length && tipe === 'primer') {
            html = '<div class="text-muted small text-center py-3">Tidak ada kemasan primer yang cocok. Tambahkan lewat Kelola Kemasan.</div>';
        }
        $('#kemasanPickerList').html(html);
    }

    $('body').on('click', '.js-inline-kemasan', function () {
        const $btn = $(this);
        picker = { productId: $btn.data('id'), field: $btn.data('field'), selected: String($btn.data('value') || '') };
        $('#kemasanPickerModalLabel').text(picker.field === 'kemasan_premier_id' ? 'Kemasan Primer' : 'Kemasan Sekunder');
        $('#kemasanPickerSearch').val('');
        $('#kemasanPickerVendorId').val(String($btn.data('vendor') || '')).trigger('change');
        $('#kemasanPickerDesainVendorId').val(String($btn.data('desain') || '')).trigger('change');
        renderKemasanPickerList();
        $('#kemasanPickerModal').modal('show');
    });

    $('#kemasanPickerSearch').on('input', renderKemasanPickerList);

    $('#kemasanPickerList').on('click', '.js-pick-kemasan', function () {
        picker.selected = String($(this).data('value') || '');
        renderKemasanPickerList();
    });

    $('#saveKemasanPickerBtn').on('click', function () {
        const primer = picker.field === 'kemasan_premier_id';
        if (primer && !picker.selected) {
            formError($('#kemasanPickerModal'), 'Kemasan primer wajib dipilih.');
            return;
        }
        const data = { _method: 'PUT', inline_relation_update: 1, field: picker.field, value: picker.selected };
        data[primer ? 'kemasan_primer_vendor_id' : 'kemasan_sekunder_vendor_id'] = $('#kemasanPickerVendorId').val() || '';
        data[primer ? 'desain_kemasan_primer_id' : 'desain_kemasan_sekunder_id'] = $('#kemasanPickerDesainVendorId').val() || '';

        $.post(URLS.produk + '/' + picker.productId, data)
            .done(function (res) {
                $('#kemasanPickerModal').modal('hide');
                reloadTable();
                toast(res.message || 'Kemasan berhasil diperbarui.');
            })
            .fail(function (xhr) { formError($('#kemasanPickerModal'), errorMessage(xhr, 'Gagal memperbarui kemasan.')); });
    });

    // ---------- sample (modal: tambah + riwayat) ----------
    function renderSampleHistory(logs) {
        if (!logs.length) return '<div class="text-muted small text-center py-3">Belum ada sample.</div>';
        const statusOptions = function (current) {
            return STATUS_OPTIONS.sample.map(function (o) { return '<option value="' + esc(o) + '"' + (o === current ? ' selected' : '') + '>' + esc(ucfirst(o)) + '</option>'; }).join('');
        };
        return '<ul class="list-group">' + logs.map(function (log, i) {
            const evaluasi = log.evaluasi || {};
            const evaluasiView = Object.keys(EVALUASI_FIELDS).filter(function (k) { return evaluasi[k]; }).map(function (k) {
                return '<span class="mr-3"><span class="text-muted">' + esc(EVALUASI_FIELDS[k]) + ':</span> ' + esc(evaluasi[k]) + '</span>';
            }).join('');
            const evaluasiEdit = Object.keys(EVALUASI_FIELDS).map(function (k) {
                return '<div class="col-md-3 col-6 mb-1"><input type="text" class="form-control form-control-sm js-edit-evaluasi" data-key="' + k + '" placeholder="' + esc(EVALUASI_FIELDS[k]) + '" value="' + esc(evaluasi[k] || '') + '"></div>';
            }).join('');
            return '<li class="list-group-item" data-sample-id="' + log.id + '">'
                + '<div class="rnd-line"><span><strong>' + dash(log.no_produksi) + '</strong>' + (i === 0 ? ' <small class="text-muted">(terbaru)</small>' : '') + '</span>' + statusBadge(log.status_sample) + '</div>'
                + '<div class="rnd-meta">Diterima: ' + formatDate(log.tanggal_terima) + ' · dicatat ' + formatDate(log.created_at, true) + '</div>'
                + '<div class="js-sample-view">'
                + (evaluasiView ? '<div class="small mt-1">' + evaluasiView + '</div>' : '')
                + '<div class="small mt-1 rnd-log-notes">' + dash(log.notes) + '</div>'
                + '<div class="mt-1"><button type="button" class="btn btn-sm btn-outline-primary mr-1 js-edit-sample"><i class="fas fa-pen mr-1"></i>Edit</button>'
                + '<button type="button" class="btn btn-sm btn-outline-danger js-delete-sample"><i class="fas fa-trash mr-1"></i>Hapus</button></div>'
                + '</div>'
                + '<div class="js-sample-edit d-none mt-2">'
                + '<div class="form-row">'
                + '<div class="col-md-4 col-6 mb-1"><input type="date" class="form-control form-control-sm js-edit-tanggal" value="' + esc(log.tanggal_terima || '') + '" title="Tanggal terima"></div>'
                + '<div class="col-md-4 col-6 mb-1"><select class="form-control form-control-sm js-edit-status">' + statusOptions(log.status_sample) + '</select></div>'
                + '</div>'
                + '<div class="form-row">' + evaluasiEdit + '</div>'
                + '<textarea class="form-control form-control-sm js-edit-notes" rows="2" placeholder="Catatan">' + esc(log.notes || '') + '</textarea>'
                + '<div class="text-right mt-1"><button type="button" class="btn btn-sm btn-light border mr-1 js-cancel-sample">Batal</button>'
                + '<button type="button" class="btn btn-sm btn-primary js-save-sample"><i class="fas fa-check mr-1"></i>Simpan</button></div>'
                + '</div>'
                + '</li>';
        }).join('') + '</ul>';
    }

    function evaluasiFrom($inputs) {
        const evaluasi = {};
        $inputs.each(function () { evaluasi[$(this).data('key')] = $.trim($(this).val() || ''); });
        return evaluasi;
    }

    function loadSampleHistory() {
        const id = $('#sample_produk_id').val();
        $('#sampleHistoryList').html('<div class="text-muted small text-center py-3">Memuat...</div>');
        $.get(URLS.produk + '/' + id, function (res) {
            $('#sampleHistoryList').html(renderSampleHistory((res.data || {}).sample_logs || []));
        }).fail(function (xhr) { formError($('#sampleModal'), errorMessage(xhr, 'Gagal mengambil riwayat sample.')); });
    }

    $('body').on('click', '.js-add-sample, .js-sample-history', function () { openSampleModal($(this).data('id')); });

    let sampleReturnToProduk = null;

    /** returnToProduk: reopen the product modal (Sample tab) when this modal closes. */
    function openSampleModal(id, returnToProduk) {
        sampleReturnToProduk = returnToProduk ? id : null;
        const statusOptions = STATUS_OPTIONS.sample.map(function (o) { return '<option value="' + esc(o) + '">' + esc(ucfirst(o)) + '</option>'; }).join('');
        $('#sampleForm')[0].reset();
        $('#sample_status').html(statusOptions).val(STATUS_OPTIONS.sample[0]);
        $('#sample_tanggal_terima').val(localToday());
        $('#sample_produk_id').val(id);
        $('#sampleModalProduk').text(produkName(rowsById[id]));
        $('#sampleEvaluasiCollapse').collapse('hide');
        loadSampleHistory();
        $('#sampleModal').modal('show');
    }

    $('#sampleModal').on('shown.bs.modal', function () { $('#sample_no_produksi').trigger('focus'); });
    $('#sampleModal').on('hidden.bs.modal', function () {
        if (sampleReturnToProduk) {
            const id = sampleReturnToProduk;
            sampleReturnToProduk = null;
            openEditProduk(id, '#produk-sample-tab');
        }
    });

    $('#sampleForm').on('submit', function (e) {
        e.preventDefault();
        const $modal = $('#sampleModal');
        const no = $.trim($('#sample_no_produksi').val() || '');
        if (!no) {
            $('#sample_no_produksi').addClass('is-invalid').trigger('focus');
            return formError($modal, 'No Produksi wajib diisi.');
        }
        $('#sample_no_produksi').removeClass('is-invalid');
        const $btn = $('#sampleSaveBtn').prop('disabled', true);
        $.post(URLS.produk + '/' + $('#sample_produk_id').val(), {
            _method: 'PUT', add_sample_log: 1, no_produksi: no,
            tanggal_terima: $('#sample_tanggal_terima').val() || '',
            status_sample: $('#sample_status').val(), notes: $.trim($('#sample_notes').val() || ''),
            evaluasi: evaluasiFrom($('#sampleForm .js-sample-evaluasi'))
        })
            .done(function () {
                formError($modal, '');
                $('#sampleForm')[0].reset();
                $('#sample_status').val(STATUS_OPTIONS.sample[0]);
                $('#sample_tanggal_terima').val(localToday());
                loadSampleHistory();
                reloadTable();
            })
            .fail(function (xhr) { formError($modal, errorMessage(xhr, 'Gagal menambahkan sample.')); })
            .always(function () { $btn.prop('disabled', false); });
    });

    $('#sampleHistoryList').on('click', '.js-edit-sample, .js-cancel-sample', function () {
        const $item = $(this).closest('li');
        const editing = $(this).hasClass('js-edit-sample');
        $item.find('.js-sample-view').toggleClass('d-none', editing);
        $item.find('.js-sample-edit').toggleClass('d-none', !editing);
    });

    $('#sampleHistoryList').on('click', '.js-save-sample', function () {
        const $item = $(this).closest('li');
        const $btn = $(this).prop('disabled', true);
        $.post(URLS.sampleLog + '/' + $item.data('sample-id'), {
            _method: 'PUT',
            tanggal_terima: $item.find('.js-edit-tanggal').val() || '',
            status_sample: $item.find('.js-edit-status').val(),
            evaluasi: evaluasiFrom($item.find('.js-edit-evaluasi')),
            notes: $.trim($item.find('.js-edit-notes').val() || '')
        })
            .done(function () { loadSampleHistory(); reloadTable(); })
            .fail(function (xhr) { formError($('#sampleModal'), errorMessage(xhr, 'Gagal memperbarui sample.')); })
            .always(function () { $btn.prop('disabled', false); });
    });

    $('#sampleHistoryList').on('click', '.js-delete-sample', function () {
        const sampleId = $(this).closest('li').data('sample-id');
        Swal.fire({
            title: 'Hapus sample?',
            text: 'Sample yang dihapus tidak bisa dikembalikan.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, hapus',
            cancelButtonText: 'Batal'
        }).then(function (result) {
            if (!result.value) return;
            $.post(URLS.sampleLog + '/' + sampleId, { _method: 'DELETE' })
                .done(function () { loadSampleHistory(); reloadTable(); })
                .fail(function (xhr) { formError($('#sampleModal'), errorMessage(xhr, 'Gagal menghapus sample.')); });
        });
    });

    // ---------- product form ----------
    function fillStatusSelects() {
        $('.js-status-select').each(function () {
            const $s = $(this).empty().append(new Option('Belum diisi', ''));
            (STATUS_OPTIONS[$(this).data('options')] || []).forEach(function (o) { $s.append(new Option(ucfirst(o), o)); });
        });
    }

    function initProdukSelect2() {
        $('.select2-produk').each(function () {
            $(this).select2({ width: '100%', dropdownParent: $('#produkModal') });
        });
        $('.select2-kemasan-modal').each(function () {
            $(this).select2({ width: '100%', dropdownParent: $('#kemasanPickerModal'), allowClear: true, placeholder: $(this).find('option:first').text() });
        });
        $('#filterBrand').select2({ width: '100%', allowClear: true, placeholder: 'Semua brand' });
        $('#timelineFilterProduk').select2({ width: '100%', allowClear: true, placeholder: 'Semua produk' });
        $('#timeline_produk_select').select2({ width: '100%', dropdownParent: $('#timelineModal'), placeholder: 'Pilih produk' });
        $('#pic_user_id').select2({
            width: '100%',
            dropdownParent: $('#produkModal'),
            allowClear: true,
            placeholder: 'Cari nama user',
            ajax: { url: URLS.userSearch, delay: 250, data: function (p) { return { q: p.term }; }, processResults: function (d) { return d; } }
        });
    }

    /** Target margin from target HPP (excl. PPN) and target harga jual, as a quick sanity check. */
    function updateTargetMarginInfo() {
        const hpp = parseFloat($('#target_hpp').val());
        const jual = parseFloat($('#target_harga_jual').val());
        $('#targetMarginInfo').text(hpp > 0 && jual > 0
            ? 'Target margin dari HPP: ' + ((jual - hpp) / hpp * 100).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + '% (' + formatRupiah(jual - hpp) + ' per unit, sebelum PPN)'
            : '');
    }

    $('#target_hpp, #target_harga_jual').on('input', updateTargetMarginInfo);

    function toggleSekunderFields() {
        const has = $.trim($('#kemasan_sekunder_id').val() || '') !== '';
        $('#produkSekunderCol .js-sekunder-dependent').prop('disabled', !has).trigger('change.select2');
    }

    function renderLogs(logs) {
        if (!logs || !logs.length) return '<div class="text-muted small text-center py-3">Belum ada riwayat.</div>';
        return logs.map(function (log) {
            const isUser = String(log.status_activity || '').toLowerCase() === 'user-note';
            return '<div class="rnd-log-item">'
                + '<div class="rnd-line"><span class="badge ' + (isUser ? 'badge-primary' : 'badge-light border') + '">' + (isUser ? 'Catatan' : esc(log.status_activity || '-')) + '</span>'
                + '<small class="text-muted">' + formatDate(log.log_date_time, true) + '</small></div>'
                + '<div class="rnd-log-notes mt-1">' + dash(log.notes) + '</div></div>';
        }).join('');
    }

    function setLogPanel(logs, isEdit) {
        $('#produkLogList').html(isEdit ? renderLogs(logs) : '');
        $('#produkNoteInput').val('');
    }

    /** Header stepper + a check on each flow tab that is complete. */
    function setModalFlow(d) {
        const isEdit = !!(d && d.id);
        $('#produkFormTabs .js-edit-only').toggleClass('d-none', !isEdit);
        $('#produkModalStepper').toggleClass('d-none', !isEdit);
        $('#produkFormTabs .rnd-step-no').each(function () {
            $(this).removeClass('is-done').text($(this).data('fallback') || $(this).text());
        });
        if (!isEdit) return;
        const flow = flowFromChecklist(d.release_checklist, d.obat_id);
        $('#produkModalStepper').html(renderStepper(flow, false));
        $('#produkFormTabs .rnd-step-no').each(function () {
            const $no = $(this);
            if (!$no.data('fallback')) $no.data('fallback', $no.text());
            if (flow[$no.data('step')]) $no.addClass('is-done').html('<i class="fas fa-check"></i>');
        });
    }

    function renderSampleSummary(logs) {
        if (!logs || !logs.length) return '<div class="text-muted small text-center py-3 border rounded">Belum ada sample. Klik <strong>Tambah / Kelola Sample</strong>.</div>';
        return '<table class="table table-sm mb-0"><thead class="thead-light"><tr><th>No Produksi</th><th>Diterima</th><th>Status</th><th>Catatan</th></tr></thead><tbody>'
            + logs.map(function (s, i) {
                return '<tr' + (i === 0 ? ' class="font-weight-bold"' : '') + '><td>' + dash(s.no_produksi) + (i === 0 ? ' <small class="text-muted">(terbaru)</small>' : '') + '</td>'
                    + '<td>' + formatDate(s.tanggal_terima) + '</td><td>' + statusBadge(s.status_sample) + '</td><td class="small">' + dash(s.notes) + '</td></tr>';
            }).join('') + '</tbody></table>';
    }

    $('#btnKelolaSample').on('click', function () {
        const id = $('#produk_id').val();
        // One modal at a time: close the product modal, then open the sample modal
        $('#produkModal').one('hidden.bs.modal', function () { openSampleModal(id, true); }).modal('hide');
    });

    function renderDocuments(documents) {
        if (!documents || !documents.length) return '<li class="list-group-item px-0 text-muted small">Belum ada dokumen tambahan.</li>';
        return documents.map(function (doc) {
            const size = doc.size_bytes ? (doc.size_bytes >= 1048576 ? (doc.size_bytes / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(doc.size_bytes / 1024)) + ' KB') : '';
            return '<li class="list-group-item px-0 d-flex justify-content-between align-items-center">'
                + '<span><i class="far fa-file mr-1 text-muted"></i><a href="' + encodeURI(doc.url) + '" target="_blank" rel="noopener noreferrer">' + esc(doc.original_name || 'Dokumen') + '</a>'
                + (size ? ' <small class="text-muted">' + size + '</small>' : '') + '</span>'
                + '<button type="button" class="btn btn-sm btn-outline-danger js-delete-document" data-id="' + doc.id + '" title="Hapus"><i class="fas fa-trash"></i></button></li>';
        }).join('');
    }

    function updateDocumentsSummary() {
        const files = $('#additional_documents')[0].files || [];
        const saved = !!$('#produk_id').val();
        $('#submitAdditionalDocumentsBtn').prop('disabled', !(saved && files.length));
        $('#pickAdditionalDocumentsBtn').prop('disabled', !saved);
        $('#additionalDocumentsSummary').text(!saved ? 'Simpan produk dulu sebelum upload dokumen.'
            : (!files.length ? 'Belum ada file dipilih.' : (files.length === 1 ? files[0].name : files.length + ' file dipilih')));
    }

    /** Notif "done" needs dates + PDF, so the form may keep it but never pick it. */
    function lockNotifDone(currentValue) {
        $('#status_administrasi_notif option[value="done"]').prop('disabled', currentValue !== 'done');
    }

    function clearFieldError($field) {
        $field.removeClass('is-invalid');
        $field.next('.select2-container').find('.select2-selection').removeClass('is-invalid');
    }

    function validateProdukForm() {
        const isEdit = !!$('#produk_id').val();
        const required = [
            { sel: '#brand_id', label: 'Brand', tab: '#produk-base-tab', create: true },
            { sel: '#nama_produk', label: 'Nama Produk', tab: '#produk-base-tab' },
            { sel: '#sediaan', label: 'Sediaan', tab: '#produk-base-tab', create: true },
            { sel: '#kemasan_premier_id', label: 'Kemasan Primer', tab: '#produk-kemasan-tab', create: true }
        ];
        const missing = required.filter(function (f) {
            clearFieldError($(f.sel));
            return (!f.create || !isEdit) && $.trim($(f.sel).val() || '') === '';
        })[0];
        if (!missing) return true;

        const $field = $(missing.sel).addClass('is-invalid');
        $field.next('.select2-container').find('.select2-selection').addClass('is-invalid');
        $(missing.tab).tab('show');
        formError($('#produkModal'), missing.label + ' wajib diisi.');
        return false;
    }

    function resetProdukForm() {
        $('#produkForm')[0].reset();
        $('#produk_id').val('');
        $('#produkForm .select2-produk').val(null).trigger('change');
        $('#produkForm .is-invalid').removeClass('is-invalid');
        $('#produkForm .select2-selection.is-invalid').removeClass('is-invalid');
        $('#additional_documents').val('');
        $('#additionalDocumentsList').html(renderDocuments([]));
        $('#deleteProdukBtn').addClass('d-none').data('id', '');
        $('#pic_user_id').empty().trigger('change');
        $('#status_produk').val('aktif');
        setLogPanel([], false);
        setLegalitas(null);
        setModalFlow(null);
        $('#produkSampleSummary').html('');
        updateTargetMarginInfo();
        lockNotifDone('');
        updateDocumentsSummary();
        toggleSekunderFields();
        $('#produk-base-tab').tab('show');
    }

    function openEditProduk(id, tabSelector) {
        $.get(URLS.produk + '/' + id, function (res) {
            const d = res.data || {};
            resetProdukForm();
            $('#produkModalLabel').text('Edit Produk');
            $('#produk_id').val(d.id).data('obat', d.obat_id || null);
            $('#nama_produk').val(d.nama_produk || '');
            $('#netto').val(d.netto || '');
            $('#netto_satuan').val(d.netto_satuan || '');
            $('#status_produk').val(d.status_produk || 'aktif');
            if (d.pic) {
                $('#pic_user_id').append(new Option(d.pic.name, d.pic.id, true, true)).trigger('change');
            }
            ['target_rilis', 'masa_simpan_bulan', 'target_hpp', 'target_harga_jual', 'estimasi_biaya'].forEach(function (f) {
                $('#' + f).val(d[f] == null ? '' : d[f]);
            });
            updateTargetMarginInfo();
            setSelectValue($('#brand_id'), d.brand_id, d.brand && d.brand.nama_brand);
            setSelectValue($('#sediaan'), d.sediaan, d.sediaan);
            setSelectValue($('#produsen_vendor_id'), d.produsen_vendor_id, d.produsen_vendor && d.produsen_vendor.nama_vendor);
            $('#zat_aktif_ids').val(d.zat_aktif_ids || []).trigger('change');
            setSelectValue($('#kemasan_premier_id'), d.kemasan_premier_id, kemasanLabel(d.kemasan_premier));
            setSelectValue($('#kemasan_sekunder_id'), d.kemasan_sekunder_id, kemasanLabel(d.kemasan_sekunder));
            toggleSekunderFields();
            setSelectValue($('#kemasan_primer_vendor_id'), d.kemasan_primer_vendor_id, d.kemasan_primer_vendor && d.kemasan_primer_vendor.nama_vendor);
            setSelectValue($('#kemasan_sekunder_vendor_id'), d.kemasan_sekunder_vendor_id, d.kemasan_sekunder_vendor && d.kemasan_sekunder_vendor.nama_vendor);
            setSelectValue($('#desain_kemasan_primer_id'), d.desain_kemasan_primer_id, d.desain_kemasan_primer_vendor && d.desain_kemasan_primer_vendor.nama_vendor);
            setSelectValue($('#desain_kemasan_sekunder_id'), d.desain_kemasan_sekunder_id, d.desain_kemasan_sekunder_vendor && d.desain_kemasan_sekunder_vendor.nama_vendor);
            ['status_administrasi_fpp', 'status_administrasi_spk', 'status_administrasi_notif', 'status_kemasan_primer', 'status_kemasan_sekunder',
                'status_desain_kemasan_primer', 'status_desain_kemasan_sekunder'].forEach(function (f) { $('#' + f).val(d[f] || ''); });
            lockNotifDone(d.status_administrasi_notif || '');
            $('#deleteProdukBtn').removeClass('d-none').data('id', d.id);
            setLogPanel(d.product_logs || [], true);
            $('#additionalDocumentsList').html(renderDocuments(d.additional_documents || []));
            updateDocumentsSummary();
            setLegalitas(d);
            setModalFlow(d);
            $('#produkSampleSummary').html(renderSampleSummary(d.sample_logs || []));
            $('#produkModalLabel').text(produkName({
                brand_name: d.brand ? d.brand.nama_brand : '', nama_produk: d.nama_produk, netto: d.netto, netto_satuan: d.netto_satuan, sediaan_name: d.sediaan
            }) || 'Edit Produk');
            if (tabSelector) $(tabSelector).tab('show');
            $('#produkModal').modal('show');
        }).fail(function (xhr) { Swal.fire('Error', errorMessage(xhr, 'Gagal mengambil data produk.'), 'error'); });
    }

    // ---------- Legalitas & Sertifikasi (tab in the product modal) ----------
    let sertifikasiRows = [];

    function renderChecklist(items) {
        return (items || []).map(function (item) {
            return '<li class="' + (item.ok ? 'text-success' : 'text-danger') + '">'
                + '<i class="fas ' + (item.ok ? 'fa-check-circle' : 'fa-times-circle') + ' mr-1"></i>' + esc(item.label)
                + ' <span class="text-muted">(' + esc(item.detail) + ')</span></li>';
        }).join('');
    }

    function renderSertifikasiList(rows) {
        // Every jenis gets a row: wajib ones show "belum ada" when missing, so the gaps are visible
        const html = Object.keys(JENIS_SERTIFIKASI).map(function (jenis) {
            const meta = JENIS_SERTIFIKASI[jenis];
            const items = rows.filter(function (r) { return r.jenis === jenis; });
            if (!items.length && !meta.wajib) return '';
            const head = '<div class="rnd-line mb-1"><strong>' + esc(meta.label) + '</strong>'
                + (meta.wajib ? '<span class="badge badge-light border">wajib</span>' : '<span class="badge badge-light border text-muted">opsional</span>') + '</div>';
            if (!items.length) {
                return '<div class="rnd-sertifikasi-group">' + head + '<span class="badge ' + MASA_BERLAKU.belum.cls + '">belum ada</span>'
                    + ' <button type="button" class="rnd-link js-add-sertifikasi" data-jenis="' + jenis + '"><i class="fas fa-plus"></i> Tambah</button></div>';
            }
            return '<div class="rnd-sertifikasi-group">' + head + items.map(function (r) {
                const state = MASA_BERLAKU[r.masa_berlaku] || MASA_BERLAKU.belum;
                return '<div class="rnd-sertifikasi-item' + (r.terkini ? '' : ' text-muted') + '">'
                    + '<div class="rnd-line">'
                    + '<span>' + (r.nomor ? '<strong>' + esc(r.nomor) + '</strong> · ' : '') + formatDate(r.tanggal_mulai) + ' – ' + formatDate(r.tanggal_selesai)
                    + (r.terkini ? '' : ' <small>(riwayat)</small>') + '</span>'
                    + '<span class="badge ' + state.cls + '">' + state.text + '</span></div>'
                    + '<div class="rnd-meta">'
                    + [r.penerbit ? esc(r.penerbit) : '', r.no_produksi ? 'Batch ' + esc(r.no_produksi) : '', r.catatan ? esc(r.catatan) : ''].filter(Boolean).join(' · ')
                    + '</div>'
                    + '<div class="mt-1">'
                    + (r.url ? '<a class="btn btn-sm btn-outline-secondary mr-1" href="' + encodeURI(r.url) + '" target="_blank" rel="noopener noreferrer"><i class="far fa-file-pdf mr-1"></i>Dokumen</a>' : '')
                    + '<button type="button" class="btn btn-sm btn-outline-primary mr-1 js-edit-sertifikasi" data-id="' + r.id + '"><i class="fas fa-pen mr-1"></i>Edit</button>'
                    + '<button type="button" class="btn btn-sm btn-outline-danger js-delete-sertifikasi" data-id="' + r.id + '"><i class="fas fa-trash mr-1"></i>Hapus</button>'
                    + '</div></div>';
            }).join('') + '</div>';
        }).join('');
        return html || '<div class="text-muted small">Belum ada sertifikasi.</div>';
    }

    function applySertifikasiData(rows, checklist) {
        sertifikasiRows = rows || [];
        $('#sertifikasiList').html(renderSertifikasiList(sertifikasiRows));
        $('#releaseChecklist').html(renderChecklist(checklist));
        const problems = Object.keys(JENIS_SERTIFIKASI).filter(function (jenis) {
            if (!JENIS_SERTIFIKASI[jenis].wajib) return false;
            const current = sertifikasiRows.filter(function (r) { return r.jenis === jenis && r.terkini; })[0];
            return !current || current.masa_berlaku !== 'berlaku';
        }).length;
        $('#legalitasTabBadge').text(problems).toggleClass('d-none', !problems);
        // Adding / removing a sertifikasi can complete the Legalitas step
        if ($('#produk_id').val() && checklist && checklist.length) {
            setModalFlow({ id: $('#produk_id').val(), release_checklist: checklist, obat_id: $('#produk_id').data('obat') });
        }
    }

    function setLegalitas(d) {
        const saved = !!(d && d.id);
        $('#legalitasNeedSave').toggleClass('d-none', saved);
        $('#legalitasBody').toggleClass('d-none', !saved);
        $('#sertifikasi_sample_log_id').empty().append(new Option('-', ''));
        ((d && d.sample_logs) || []).forEach(function (s) {
            $('#sertifikasi_sample_log_id').append(new Option(s.no_produksi + (s.status_sample ? ' (' + s.status_sample + ')' : ''), s.id));
        });
        resetSertifikasiForm();
        applySertifikasiData(saved ? d.sertifikasi : [], saved ? d.release_checklist : []);
    }

    function updateSertifikasiFormForJenis() {
        const meta = JENIS_SERTIFIKASI[$('#sertifikasi_jenis').val()] || {};
        $('#sertifikasiNomorWajib').toggleClass('d-none', !meta.nomor_wajib);
        $('#sertifikasiSampleGroup').toggleClass('d-none', !meta.per_batch);
        if (!$('#sertifikasi_id').val() && !$('#sertifikasi_penerbit').data('touched')) {
            $('#sertifikasi_penerbit').val(meta.penerbit || '');
        }
    }

    function resetSertifikasiForm(jenis) {
        $('#sertifikasi_id').val('');
        $('#produk-legalitas-pane').find('#sertifikasi_nomor, #sertifikasi_penerbit, #sertifikasi_tanggal_mulai, #sertifikasi_tanggal_selesai, #sertifikasi_catatan, #sertifikasi_dokumen').val('');
        $('#sertifikasi_penerbit').data('touched', false);
        $('#sertifikasi_sample_log_id').val('');
        $('#sertifikasi_jenis').val(jenis || Object.keys(JENIS_SERTIFIKASI)[0]).prop('disabled', false);
        $('#sertifikasiFormTitle').text('Tambah Sertifikasi');
        $('#btnSertifikasiSave').html('<i class="fas fa-plus mr-1"></i> Tambah Sertifikasi');
        $('#btnSertifikasiCancel').addClass('d-none');
        $('#sertifikasiDokumenWajib').removeClass('d-none');
        $('#sertifikasiDokumenHint').addClass('d-none');
        updateSertifikasiFormForJenis();
    }

    $('#sertifikasi_jenis').on('change', updateSertifikasiFormForJenis);
    $('#sertifikasi_penerbit').on('input', function () { $(this).data('touched', true); });
    $('#btnSertifikasiCancel').on('click', function () { resetSertifikasiForm(); });

    // Enter in these inputs must not submit the product form around them
    $('#produk-legalitas-pane').on('keydown', 'input', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); $('#btnSertifikasiSave').trigger('click'); }
    });

    $('#sertifikasiList').on('click', '.js-add-sertifikasi', function () {
        resetSertifikasiForm($(this).data('jenis'));
        $('#sertifikasi_nomor').trigger('focus');
    });

    $('#sertifikasiList').on('click', '.js-edit-sertifikasi', function () {
        const r = sertifikasiRows.filter(function (row) { return String(row.id) === String($(this).data('id')); }, this)[0];
        if (!r) return;
        resetSertifikasiForm(r.jenis);
        $('#sertifikasi_id').val(r.id);
        $('#sertifikasi_jenis').prop('disabled', true);
        $('#sertifikasi_nomor').val(r.nomor || '');
        $('#sertifikasi_penerbit').val(r.penerbit || '').data('touched', true);
        $('#sertifikasi_tanggal_mulai').val(r.tanggal_mulai);
        $('#sertifikasi_tanggal_selesai').val(r.tanggal_selesai);
        $('#sertifikasi_sample_log_id').val(r.sample_log_id || '');
        $('#sertifikasi_catatan').val(r.catatan || '');
        $('#sertifikasiFormTitle').text('Edit ' + r.jenis_label);
        $('#btnSertifikasiSave').html('<i class="fas fa-check mr-1"></i> Simpan Perubahan');
        $('#btnSertifikasiCancel').removeClass('d-none');
        $('#sertifikasiDokumenWajib').addClass('d-none');
        $('#sertifikasiDokumenHint').removeClass('d-none');
        $('#sertifikasi_nomor').trigger('focus');
    });

    $('#btnSertifikasiSave').on('click', function () {
        const $modal = $('#produkModal');
        const produkId = $('#produk_id').val();
        const id = $('#sertifikasi_id').val();
        const jenis = $('#sertifikasi_jenis').val();
        const meta = JENIS_SERTIFIKASI[jenis] || {};
        const mulai = $('#sertifikasi_tanggal_mulai').val();
        const selesai = $('#sertifikasi_tanggal_selesai').val();
        const file = ($('#sertifikasi_dokumen')[0].files || [])[0];

        if (meta.nomor_wajib && !$.trim($('#sertifikasi_nomor').val() || '')) return formError($modal, 'Nomor wajib diisi untuk ' + meta.label + '.');
        if (!mulai || !selesai) return formError($modal, 'Tanggal mulai dan tanggal berlaku s/d wajib diisi.');
        if (selesai < mulai) return formError($modal, 'Tanggal berlaku s/d tidak boleh sebelum tanggal mulai.');
        if (!id && !file) return formError($modal, 'Dokumen sertifikat wajib diupload.');

        const fd = new FormData();
        if (id) fd.append('_method', 'PUT');
        fd.append('jenis', jenis);
        fd.append('nomor', $.trim($('#sertifikasi_nomor').val() || ''));
        fd.append('penerbit', $.trim($('#sertifikasi_penerbit').val() || ''));
        fd.append('tanggal_mulai', mulai);
        fd.append('tanggal_selesai', selesai);
        fd.append('sample_log_id', meta.per_batch ? ($('#sertifikasi_sample_log_id').val() || '') : '');
        fd.append('catatan', $.trim($('#sertifikasi_catatan').val() || ''));
        if (file) fd.append('dokumen', file);

        const $btn = $(this).prop('disabled', true);
        $.ajax({ url: id ? URLS.sertifikasi + '/' + id : URLS.produk + '/' + produkId + '/sertifikasi', type: 'POST', data: fd, processData: false, contentType: false })
            .done(function (res) {
                applySertifikasiData((res.data || {}).sertifikasi, (res.data || {}).release_checklist);
                resetSertifikasiForm();
                reloadTable();
                toast(res.message || 'Sertifikasi berhasil disimpan.');
            })
            .fail(function (xhr) { formError($modal, errorMessage(xhr, 'Gagal menyimpan sertifikasi.')); })
            .always(function () { $btn.prop('disabled', false); });
    });

    $('#sertifikasiList').on('click', '.js-delete-sertifikasi', function () {
        const id = $(this).data('id');
        Swal.fire({
            title: 'Hapus sertifikasi?',
            text: 'Data dan dokumennya dihapus permanen. Untuk perpanjangan, tambahkan sertifikasi baru saja.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, hapus',
            cancelButtonText: 'Batal'
        }).then(function (result) {
            if (!result.value) return;
            $.post(URLS.sertifikasi + '/' + id, { _method: 'DELETE' })
                .done(function (res) {
                    applySertifikasiData((res.data || {}).sertifikasi, (res.data || {}).release_checklist);
                    if (String($('#sertifikasi_id').val()) === String(id)) resetSertifikasiForm();
                    reloadTable();
                    toast(res.message || 'Sertifikasi berhasil dihapus.');
                })
                .fail(function (xhr) { formError($('#produkModal'), errorMessage(xhr, 'Gagal menghapus sertifikasi.')); });
        });
    });

    $('body').on('click', '.js-open-legalitas', function () { openEditProduk($(this).data('id'), '#produk-legalitas-tab'); });

    function confirmDeleteProduk(id) {
        Swal.fire({
            title: 'Hapus produk?',
            text: 'Produk beserta sample, log, timeline, dan dokumennya dihapus permanen. Obat yang sudah dirilis tetap ada di Master Obat.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, hapus',
            cancelButtonText: 'Batal'
        }).then(function (result) {
            if (!result.value) return;
            $.post(URLS.produk + '/' + id, { _method: 'DELETE' })
                .done(function (res) { $('#produkModal').modal('hide'); reloadTable(); loadTimeline(); refreshMasterOptions(); toast(res.message || 'Produk berhasil dihapus.'); })
                .fail(function (xhr) { Swal.fire('Error', errorMessage(xhr, 'Gagal menghapus produk.'), 'error'); });
        });
    }

    $('#btnTambahProduk').on('click', function () {
        resetProdukForm();
        $('#produkModalLabel').text('Tambah Produk');
        $('#produkModal').modal('show');
    });

    $('body').on('click', '.js-edit-product', function () { openEditProduk($(this).data('id')); });
    $('body').on('click', '.js-delete-product', function () { confirmDeleteProduk($(this).data('id')); });
    $('#deleteProdukBtn').on('click', function () { if ($(this).data('id')) confirmDeleteProduk($(this).data('id')); });

    $('#kemasan_sekunder_id').on('change', function () {
        if (!$.trim($(this).val() || '')) {
            $('#produkSekunderCol .js-sekunder-dependent').val('').trigger('change.select2');
        }
        toggleSekunderFields();
    });

    $('#produkForm').on('input change', 'input, select, textarea', function () { clearFieldError($(this)); });

    $('#produkForm').on('submit', function (e) {
        e.preventDefault();
        if (!validateProdukForm()) return;

        const id = $('#produk_id').val();
        const fd = new FormData(this);
        if (id) fd.append('_method', 'PUT');
        // Disabled sekunder fields are not posted: send them empty so the server clears them
        if (!$.trim($('#kemasan_sekunder_id').val() || '')) {
            ['kemasan_sekunder_vendor_id', 'desain_kemasan_sekunder_id', 'status_kemasan_sekunder', 'status_desain_kemasan_sekunder']
                .forEach(function (f) { fd.set(f, ''); });
        }
        const $btn = $('#saveProdukBtn').prop('disabled', true);

        $.ajax({ url: id ? URLS.produk + '/' + id : URLS.store, type: 'POST', data: fd, processData: false, contentType: false })
            .done(function (res) {
                reloadTable();
                refreshMasterOptions();
                if (!id && res.data && res.data.id) {
                    // New product: continue with the first flow step instead of closing
                    $('#produkModal').one('hidden.bs.modal', function () { openEditProduk(res.data.id, '#produk-sample-tab'); }).modal('hide');
                    toast('Produk dibuat. Lanjutkan ke tahap 1: Sample.');
                    return;
                }
                $('#produkModal').modal('hide');
                toast(res.message || 'Produk berhasil disimpan.');
            })
            .fail(function (xhr) { formError($('#produkModal'), errorMessage(xhr, 'Terjadi kesalahan saat menyimpan data.')); })
            .always(function () { $btn.prop('disabled', false); });
    });

    $('#btnSaveProdukNote').on('click', function () {
        const id = $('#produk_id').val();
        const notes = $.trim($('#produkNoteInput').val() || '');
        if (!id || !notes) return;
        $.post(URLS.produk + '/' + id + '/notes', { notes: notes })
            .done(function (res) { setLogPanel((res.data || {}).product_logs || [], true); })
            .fail(function (xhr) { formError($('#produkModal'), errorMessage(xhr, 'Catatan gagal disimpan.')); });
    });

    // ---------- documents ----------
    $('#pickAdditionalDocumentsBtn').on('click', function () { $('#additional_documents').trigger('click'); });
    $('#additional_documents').on('change', updateDocumentsSummary);

    $('#submitAdditionalDocumentsBtn').on('click', function () {
        const id = $('#produk_id').val();
        const files = Array.prototype.slice.call($('#additional_documents')[0].files || []);
        if (!id || !files.length) return;
        const fd = new FormData();
        files.forEach(function (f) { fd.append('additional_documents[]', f); });
        $.ajax({ url: URLS.produk + '/' + id + '/documents', type: 'POST', data: fd, processData: false, contentType: false })
            .done(function (res) {
                $('#additional_documents').val('');
                $('#additionalDocumentsList').html(renderDocuments((res.data || {}).additional_documents || []));
                updateDocumentsSummary();
                toast(res.message || 'Dokumen berhasil diunggah.');
            })
            .fail(function (xhr) { formError($('#produkModal'), errorMessage(xhr, 'Gagal mengunggah dokumen.')); });
    });

    $('#additionalDocumentsList').on('click', '.js-delete-document', function () {
        const docId = $(this).data('id');
        Swal.fire({
            title: 'Hapus dokumen?',
            text: 'Dokumen yang dihapus tidak bisa dikembalikan.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, hapus',
            cancelButtonText: 'Batal'
        }).then(function (result) {
            if (!result.value) return;
            $.post(URLS.documents + '/' + docId, { _method: 'DELETE' })
                .done(function (res) {
                    $('#additionalDocumentsList').html(renderDocuments((res.data || {}).additional_documents || []));
                    toast(res.message || 'Dokumen berhasil dihapus.');
                })
                .fail(function (xhr) { formError($('#produkModal'), errorMessage(xhr, 'Gagal menghapus dokumen.')); });
        });
    });

    // ---------- timeline ----------
    // Items: "catatan" (editable notes), "target_rilis" and "sertifikat" (read-only, from the product / Legalitas)
    const TIMELINE_TYPES = {
        catatan: { color: '#4e73df', label: 'Catatan' },
        target_rilis: { color: '#6f42c1', label: 'Target rilis' },
        sertifikat: { color: '#e74a3b', label: 'Sertifikat berakhir' }
    };
    let timelineEntries = [];
    let calendar = null;
    let selectedTimelineDate = null;

    function renderTimelineList(entries, emptyText) {
        if (!entries.length) return '<div class="text-muted small text-center py-3">' + esc(emptyText) + '</div>';
        return entries.map(function (entry) {
            const type = TIMELINE_TYPES[entry.type] || TIMELINE_TYPES.catatan;
            const actions = '<button type="button" class="btn btn-sm btn-outline-secondary mr-1 js-timeline-open-produk" data-id="' + entry.produk_id + '"><i class="fas fa-external-link-alt mr-1"></i>Buka produk</button>'
                + (entry.type === 'catatan'
                    ? '<button type="button" class="btn btn-sm btn-outline-primary mr-1 js-edit-timeline" data-id="' + entry.id + '"><i class="fas fa-pen mr-1"></i>Edit</button>'
                    + '<button type="button" class="btn btn-sm btn-outline-danger js-delete-timeline" data-id="' + entry.id + '"><i class="fas fa-trash mr-1"></i>Hapus</button>'
                    : '');
            return '<div class="rnd-timeline-item" style="border-left-color:' + type.color + '">'
                + '<div class="rnd-line"><small class="font-weight-bold" style="color:' + type.color + '">' + esc(type.label) + '</small>'
                + '<small class="text-muted">' + formatDate(entry.timeline_date) + '</small></div>'
                + '<div class="font-weight-bold">' + dash(entry.produk_name) + '</div>'
                + '<div class="rnd-timeline-notes">' + dash(entry.notes) + '</div>'
                + '<div class="mt-1">' + actions + '</div>'
                + '</div>';
        }).join('');
    }

    function dateAfterDays(days) {
        const d = new Date();
        d.setDate(d.getDate() + days);
        return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    }

    /** Default agenda: target rilis already passed, plus everything in the next 30 days. */
    function showAgenda() {
        selectedTimelineDate = null;
        const today = localToday();
        const end = dateAfterDays(30);
        const entries = timelineEntries.filter(function (e) {
            return (e.timeline_date >= today && e.timeline_date <= end) || (e.type === 'target_rilis' && e.timeline_date < today);
        }).sort(function (a, b) { return a.timeline_date < b.timeline_date ? -1 : 1; });
        $('#agendaTitle').text('Agenda 30 hari ke depan');
        $('#btnAgendaUpcoming').addClass('d-none');
        $('#productTimelineIndexList').html(renderTimelineList(entries, 'Tidak ada agenda 30 hari ke depan. Klik tanggal di kalender untuk melihat hari lain.'));
    }

    function showTimelineDate(dateString) {
        selectedTimelineDate = dateString;
        const entries = timelineEntries.filter(function (e) { return String(e.timeline_date) === String(dateString); });
        $('#agendaTitle').html(formatDate(dateString));
        $('#btnAgendaUpcoming').removeClass('d-none');
        $('#productTimelineIndexList').html(renderTimelineList(entries, 'Tidak ada agenda pada tanggal ini.'));
    }

    function ensureCalendar() {
        const el = document.getElementById('productTimelineCalendar');
        if (!el || typeof FullCalendar === 'undefined') return;
        if (!calendar) {
            calendar = new FullCalendar.Calendar(el, {
                initialView: 'dayGridMonth',
                locale: 'id',
                height: 620,
                dayMaxEvents: 3,
                headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,listMonth' },
                buttonText: { today: 'Hari ini', month: 'Bulan', list: 'Daftar' },
                noEventsContent: 'Belum ada agenda.',
                eventClick: function (info) { showTimelineDate(info.event.startStr); },
                dateClick: function (info) { showTimelineDate(info.dateStr); },
                // The note is what matters: show it first, the product as a small second line
                eventContent: function (arg) {
                    const produk = arg.event.extendedProps.produk || '';
                    if (arg.view.type === 'listMonth') {
                        return { html: '<div class="rnd-event-notes">' + esc(arg.event.title) + '</div><div class="rnd-event-produk text-muted">' + esc(produk) + '</div>' };
                    }
                    return { html: '<div class="rnd-event">' + '<div class="rnd-event-notes">' + esc(arg.event.title) + '</div>'
                        + '<div class="rnd-event-produk">' + esc(produk) + '</div></div>' };
                },
                eventDidMount: function (info) {
                    info.el.title = info.event.title + '\n' + (info.event.extendedProps.produk || '');
                }
            });
            calendar.render();
        }
        calendar.removeAllEvents();
        calendar.addEventSource(timelineEntries.map(function (e) {
            const type = TIMELINE_TYPES[e.type] || TIMELINE_TYPES.catatan;
            return {
                id: String(e.id), title: e.notes || type.label, start: e.timeline_date, allDay: true,
                backgroundColor: type.color, borderColor: type.color, textColor: '#fff',
                extendedProps: { produk: e.produk_name || '' }
            };
        }));
        calendar.updateSize();
    }

    function loadTimeline() {
        $.get(URLS.timelineData, { produk_id: $('#timelineFilterProduk').val() || '' }, function (res) {
            timelineEntries = res.data || [];
            if ($('#paneTimeline').hasClass('active')) ensureCalendar();
            // Stay on the date being looked at after an add / edit / delete
            if (selectedTimelineDate) {
                showTimelineDate(selectedTimelineDate);
            } else {
                showAgenda();
            }
        });
    }

    $('#tabTimeline').on('shown.bs.tab', ensureCalendar);
    // scrollX header widths are measured while visible; re-measure when coming back to the list
    $('#tabProduk').on('shown.bs.tab', function () { if (table) table.columns.adjust(); });
    $('#btnAgendaUpcoming').on('click', showAgenda);
    $('#timelineFilterProduk').on('change', loadTimeline);

    $('#productTimelineIndexList').on('click', '.js-timeline-open-produk', function () {
        openEditProduk($(this).data('id'));
    });

    function openTimelineModal(entry, produkId) {
        $('#timelineForm')[0].reset();
        $('#timeline_id').val(entry ? entry.id : '');
        $('#timeline_produk_id').val(entry ? entry.produk_id : (produkId || ''));
        $('#timeline_date').val(entry ? entry.timeline_date : (selectedTimelineDate || localToday()));
        $('#timeline_notes').val(entry ? (entry.notes || '') : '');
        $('#timelineModalLabel').text(entry ? 'Edit Catatan Timeline' : 'Tambah Catatan Timeline');
        // Product is fixed when opened from a row or an entry; chosen in the modal when opened from the Timeline tab
        const chooseProduk = !entry && !produkId;
        $('#timelineProdukGroup').toggleClass('d-none', !chooseProduk);
        $('#timeline_produk_select').val(chooseProduk ? ($('#timelineFilterProduk').val() || '') : '').trigger('change');
        $('#timelineModalProduk').text(entry ? entry.produk_name : (produkId ? produkName(rowsById[produkId]) : ''));
        $('#timelineModal').modal('show');
    }

    $('body').on('click', '.js-add-product-timeline', function () { openTimelineModal(null, $(this).data('id')); });
    $('#btnTambahTimeline').on('click', function () { openTimelineModal(null, null); });

    $('#productTimelineIndexList').on('click', '.js-edit-timeline', function () {
        const id = String($(this).data('id'));
        const entry = timelineEntries.filter(function (e) { return e.type === 'catatan' && String(e.id) === id; })[0];
        if (entry) openTimelineModal(entry, null);
    });

    $('#productTimelineIndexList').on('click', '.js-delete-timeline', function () {
        const id = $(this).data('id');
        Swal.fire({
            title: 'Hapus catatan timeline?',
            text: 'Catatan ini dihapus permanen.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, hapus',
            cancelButtonText: 'Batal'
        }).then(function (result) {
            if (!result.value) return;
            $.post(URLS.timelines + '/' + id, { _method: 'DELETE' })
                .done(function (res) { loadTimeline(); toast(res.message || 'Timeline berhasil dihapus.'); })
                .fail(function (xhr) { Swal.fire('Error', errorMessage(xhr, 'Gagal menghapus timeline.'), 'error'); });
        });
    });

    $('#timelineModal').on('shown.bs.modal', function () {
        $($('#timelineProdukGroup').hasClass('d-none') ? '#timeline_notes' : '#timeline_produk_select').trigger('focus');
    });

    $('#timelineForm').on('submit', function (e) {
        e.preventDefault();
        const $modal = $('#timelineModal');
        const timelineId = $('#timeline_id').val();
        const produkId = $('#timelineProdukGroup').hasClass('d-none') ? $('#timeline_produk_id').val() : $('#timeline_produk_select').val();
        const payload = { timeline_date: $.trim($('#timeline_date').val() || ''), notes: $.trim($('#timeline_notes').val() || '') };
        if (!timelineId && !produkId) return formError($modal, 'Pilih produk.');
        if (!payload.timeline_date) return formError($modal, 'Tanggal wajib diisi.');
        if (!payload.notes) return formError($modal, 'Catatan wajib diisi.');
        if (timelineId) payload._method = 'PUT';
        const $btn = $('#timelineSaveBtn').prop('disabled', true);
        $.post(timelineId ? URLS.timelines + '/' + timelineId : URLS.produk + '/' + produkId + '/timelines', payload)
            .done(function (res) { $modal.modal('hide'); loadTimeline(); toast(res.message || 'Timeline berhasil disimpan.'); })
            .fail(function (xhr) { formError($modal, errorMessage(xhr, 'Gagal menyimpan timeline.')); })
            .always(function () { $btn.prop('disabled', false); });
    });

    // ---------- Rilis ke Master Obat ----------
    function fillReleaseSelect($select, options, value, placeholder) {
        $select.empty().append(new Option(placeholder, ''));
        options.forEach(function (o) {
            $select.append(new Option(typeof o === 'object' ? o.nama : o, typeof o === 'object' ? o.id : o));
        });
        $select.val(value || '');
    }

    function setReleaseMode(mode) {
        $('#releaseNewFields').toggleClass('d-none', mode !== 'new');
        $('#releaseLinkFields').toggleClass('d-none', mode !== 'link');
        $('#releaseSubmitBtn').text(mode === 'link' ? 'Hubungkan' : 'Rilis');
    }

    $('#release_obat_id').select2({
        dropdownParent: $('#releaseModal'),
        placeholder: 'Cari kode atau nama obat...',
        allowClear: true,
        minimumInputLength: 2,
        ajax: { url: URLS.obatSearch, delay: 250, data: function (p) { return { q: p.term }; }, processResults: function (d) { return d; } }
    });

    $('input[name="release_mode"]').on('change', function () { setReleaseMode($(this).val()); });

    $('body').on('click', '.js-release-product', function () {
        const id = $(this).data('id');
        $.get(URLS.produk + '/' + id + '/rilis', function (res) {
            const linked = !!res.released;
            $('#release_produk_id').val(id);
            $('#releaseLinkedPane').toggleClass('d-none', !linked);
            $('#releaseFormPane').toggleClass('d-none', linked);
            $('#releaseSubmitBtn').toggleClass('d-none', linked);
            $('#releaseUnlinkBtn').toggleClass('d-none', !linked);
            if (linked) {
                $('#releaseLinkedObat').text((res.obat.kode_obat ? res.obat.kode_obat + ' - ' : '') + res.obat.nama);
            } else {
                const d = res.defaults;
                $('#release_nama').val(d.nama || '');
                $('#release_dosis').val(d.dosis || '');
                fillReleaseSelect($('#release_satuan_stok'), res.options.satuan_stok, d.satuan_stok, '-- Pilih Satuan Stok --');
                fillReleaseSelect($('#release_satuan'), res.options.satuan_dosis, d.satuan, '-- Pilih Satuan Dosis --');
                fillReleaseSelect($('#release_metode_bayar_id'), res.options.metode_bayar, '', '-- Pilih Metode Bayar --');
                $('#releaseZatAktif').text(d.zat_aktif.length ? d.zat_aktif.join(', ') : '-');
                $('#release_obat_id').val(null).trigger('change');

                // A new obat needs every requirement; linking an existing obat (older products) does not
                const missing = (res.checklist || []).filter(function (item) { return !item.ok; });
                $('#releaseMissingList').html(missing.map(function (item) {
                    return '<li>' + esc(item.label) + ' <span class="text-muted">(' + esc(item.detail) + ')</span></li>';
                }).join(''));
                $('#releaseNotReady').toggleClass('d-none', res.can_release_new);
                $('#releaseModeNewLabel').toggleClass('disabled', !res.can_release_new)
                    .find('input').prop('disabled', !res.can_release_new);
                const mode = res.can_release_new ? 'new' : 'link';
                $('input[name="release_mode"][value="' + mode + '"]').prop('checked', true).closest('label').addClass('active').siblings().removeClass('active');
                setReleaseMode(mode);
            }
            $('#releaseModal').modal('show');
        }).fail(function () { Swal.fire('Gagal', 'Data rilis produk tidak bisa dimuat.', 'error'); });
    });

    $('#releaseForm').on('submit', function (e) {
        e.preventDefault();
        const id = $('#release_produk_id').val();
        const mode = $('input[name="release_mode"]:checked').val();
        const payload = mode === 'link'
            ? { mode: 'link', obat_id: $('#release_obat_id').val() }
            : {
                mode: 'new',
                nama: $('#release_nama').val(),
                dosis: $('#release_dosis').val(),
                satuan: $('#release_satuan').val(),
                satuan_stok: $('#release_satuan_stok').val(),
                metode_bayar_id: $('#release_metode_bayar_id').val()
            };
        const $btn = $('#releaseSubmitBtn').prop('disabled', true);
        $.post(URLS.produk + '/' + id + '/rilis', payload)
            .done(function (res) { $('#releaseModal').modal('hide'); reloadTable(); toast(res.message); })
            .fail(function (xhr) { formError($('#releaseModal'), errorMessage(xhr, 'Terjadi kesalahan.')); })
            .always(function () { $btn.prop('disabled', false); });
    });

    $('#releaseUnlinkBtn').on('click', function () {
        const id = $('#release_produk_id').val();
        Swal.fire({
            title: 'Lepas tautan ke Master Obat?',
            text: 'Hanya tautannya yang dilepas; data obat di Master Obat tidak dihapus.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, lepas',
            cancelButtonText: 'Batal'
        }).then(function (result) {
            if (!result.value) return;
            $.ajax({ url: URLS.produk + '/' + id + '/rilis', type: 'DELETE' })
                .done(function (res) { $('#releaseModal').modal('hide'); reloadTable(); toast(res.message); })
                .fail(function (xhr) { formError($('#releaseModal'), errorMessage(xhr, 'Terjadi kesalahan.')); });
        });
    });

    // ---------- Kelola Brand / Kemasan / Vendor ----------
    let activeMaster = null;
    let masterTable = null;
    let editingMasterId = null;

    function masterFieldHtml(field) {
        const id = 'master_' + field.name;
        const req = field.required ? ' required' : '';
        const label = '<label for="' + id + '" class="small mb-1">' + esc(field.label) + (field.required ? ' <span class="text-danger">*</span>' : '') + '</label>';
        if (field.type === 'multiselect') {
            return '<div class="col-12 mb-2">' + label.replace('for="' + id + '"', '') + '<div>'
                + (field.options || []).map(function (o) {
                    return '<div class="custom-control custom-checkbox custom-control-inline">'
                        + '<input type="checkbox" class="custom-control-input" id="' + id + '_' + o + '" name="' + field.name + '[]" value="' + esc(o) + '">'
                        + '<label class="custom-control-label" for="' + id + '_' + o + '">' + esc(ucfirst(o)) + '</label></div>';
                }).join('') + '</div></div>';
        }
        if (field.type === 'select') {
            return '<div class="col-md-3 mb-2">' + label + '<select class="form-control form-control-sm" id="' + id + '" name="' + field.name + '"' + req + '>'
                + '<option value="">Pilih</option>'
                + (field.options || []).map(function (o) { return '<option value="' + esc(o) + '">' + esc(ucfirst(o)) + '</option>'; }).join('')
                + '</select></div>';
        }
        if (field.type === 'date') {
            return '<div class="col-md-3 mb-2">' + label + '<input type="date" class="form-control form-control-sm" id="' + id + '" name="' + field.name + '"' + req + '></div>';
        }
        if (field.type === 'textarea') {
            return '<div class="col-12 mb-2">' + label + '<textarea class="form-control form-control-sm" id="' + id + '" name="' + field.name + '" rows="2"' + req + '></textarea></div>';
        }
        return '<div class="col-md mb-2">' + label + '<input type="text" class="form-control form-control-sm" id="' + id + '" name="' + field.name + '" maxlength="255"' + req + '></div>';
    }

    function setMasterEditMode(id) {
        editingMasterId = id;
        $('#masterFormTitle').text(id ? 'Edit ' + MASTERS[activeMaster].label.toLowerCase() : 'Tambah ' + MASTERS[activeMaster].label.toLowerCase() + ' baru');
        $('#btnMasterSave').html(id ? '<i class="fas fa-check mr-1"></i> Simpan perubahan' : '<i class="fas fa-plus mr-1"></i> Tambah');
        $('#btnMasterCancelEdit').toggleClass('d-none', !id);
        if (!id) $('#masterForm')[0].reset();
    }

    function masterColumns(master) {
        const columns = [{ data: null, title: 'No', orderable: false, searchable: false, width: '40px', render: function (d, t, r, meta) { return meta.row + meta.settings._iDisplayStart + 1; } }];
        const dateFields = master.fields.filter(function (f) { return f.type === 'date'; }).map(function (f) { return f.name; });
        master.columns.forEach(function (col) {
            if (master.key === 'kemasan' && col.data === 'ukuran') return;
            if (dateFields.indexOf(col.data) !== -1) {
                columns.push({
                    data: col.data, title: col.title, defaultContent: '-', className: 'text-nowrap',
                    render: function (data) {
                        if (!data) return '-';
                        const value = String(data).slice(0, 10);
                        return formatDate(value) + (value < localToday() ? ' <span class="badge badge-danger">kedaluwarsa</span>' : '');
                    }
                });
                return;
            }
            columns.push({
                data: col.data,
                title: col.title,
                defaultContent: '-',
                render: function (data, type, row) {
                    if (master.key === 'kemasan' && col.data === 'nama_kemasan') {
                        return '<strong>' + dash(data) + '</strong>' + (row.ukuran ? ' <span class="text-muted">(' + esc(row.ukuran) + ')</span>' : '');
                    }
                    if (col.data === 'tipe_kemasan') {
                        return data ? '<span class="badge ' + (data === 'primer' ? 'badge-info' : 'badge-secondary') + '">' + esc(data) + '</span>' : '-';
                    }
                    if (Array.isArray(data)) {
                        return data.length ? data.map(function (v) { return '<span class="badge badge-light border mr-1">' + esc(v) + '</span>'; }).join('') : '-';
                    }
                    if (/^nama_/.test(col.data)) return '<strong>' + dash(data) + '</strong>';
                    return dash(data);
                }
            });
        });
        columns.push({
            data: 'dipakai', title: 'Dipakai', orderable: false, searchable: false, className: 'text-nowrap',
            render: function (n) { return n > 0 ? '<span class="badge badge-primary">' + n + ' produk</span>' : '<span class="text-muted small">tidak dipakai</span>'; }
        });
        columns.push({
            data: null, title: 'Aksi', orderable: false, searchable: false, className: 'text-right text-nowrap', width: '90px',
            render: function (d, t, row) {
                return '<div class="btn-group btn-group-sm">'
                    + '<button type="button" class="btn btn-outline-primary js-master-edit" data-id="' + row.id + '" title="Edit"><i class="fas fa-pen"></i></button>'
                    + '<button type="button" class="btn btn-outline-danger js-master-delete" data-id="' + row.id + '"'
                    + (row.dipakai > 0 ? ' disabled title="Masih dipakai ' + row.dipakai + ' produk"' : ' title="Hapus"') + '><i class="fas fa-trash"></i></button></div>';
            }
        });
        return columns;
    }

    function afterMasterChange() {
        if (masterTable) masterTable.ajax.reload(null, false);
        refreshMasterOptions();
        reloadTable();
    }

    $('.js-open-master').on('click', function () {
        activeMaster = $(this).data('master');
        const master = MASTERS[activeMaster];
        $('#masterModalLabel').text('Kelola ' + master.label);
        $('#masterFormFields').html(master.fields.map(masterFieldHtml).join(''));
        $('#masterSearch').val('').attr('placeholder', 'Cari ' + master.label.toLowerCase());
        setMasterEditMode(null);

        if (masterTable) {
            masterTable.destroy();
            $('#masterTable').empty().append('<thead class="thead-light"></thead><tbody></tbody>');
        }
        masterTable = $('#masterTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: URLS.masters + '/' + activeMaster + '/data',
            columns: masterColumns(master),
            order: [[1, 'asc']],
            pageLength: 10,
            dom: "rt<'row align-items-center mt-2'<'col-sm-6 small'i><'col-sm-6'p>>",
            language: { info: '_START_–_END_ dari _TOTAL_', infoEmpty: 'Belum ada data', infoFiltered: '', zeroRecords: 'Tidak ada yang cocok.', processing: 'Memuat...', paginate: { previous: '&lsaquo;', next: '&rsaquo;' } }
        });
        $('#masterModal').modal('show');
    });

    let masterSearchTimer = null;
    $('#masterSearch').on('input', function () {
        clearTimeout(masterSearchTimer);
        const value = $(this).val();
        masterSearchTimer = setTimeout(function () { masterTable.search(value.trim()).draw(); }, 300);
    });

    $('#btnMasterCancelEdit').on('click', function () { setMasterEditMode(null); });

    $('#masterForm').on('submit', function (e) {
        e.preventDefault();
        const id = editingMasterId;
        const data = $(this).serializeArray();
        if (id) data.push({ name: '_method', value: 'PUT' });
        const $btn = $('#btnMasterSave').prop('disabled', true);
        $.post(URLS.masters + '/' + activeMaster + (id ? '/' + id : ''), $.param(data))
            .done(function (res) { setMasterEditMode(null); afterMasterChange(); toast(res.message || 'Data berhasil disimpan.'); })
            .fail(function (xhr) { formError($('#masterModal'), errorMessage(xhr, 'Gagal menyimpan data.')); })
            .always(function () { $btn.prop('disabled', false); });
    });

    $('#masterTable').on('click', '.js-master-edit', function () {
        const id = $(this).data('id');
        $.getJSON(URLS.masters + '/' + activeMaster + '/' + id, function (res) {
            const record = res.data || {};
            setMasterEditMode(id);
            MASTERS[activeMaster].fields.forEach(function (field) {
                const value = record[field.name];
                if (field.type === 'multiselect') {
                    const values = Array.isArray(value) ? value : (value ? [value] : []);
                    $('#masterFormFields input[name="' + field.name + '[]"]').each(function () { this.checked = values.indexOf(this.value) !== -1; });
                } else {
                    $('#master_' + field.name).val(value == null ? '' : value);
                }
            });
            $('#master_' + MASTERS[activeMaster].fields[0].name).trigger('focus');
        }).fail(function () { formError($('#masterModal'), 'Gagal mengambil detail data.'); });
    });

    $('#masterTable').on('click', '.js-master-delete', function () {
        const id = $(this).data('id');
        Swal.fire({
            title: 'Hapus data?',
            text: 'Data yang dihapus tidak bisa dikembalikan.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, hapus',
            cancelButtonText: 'Batal'
        }).then(function (result) {
            if (!result.value) return;
            $.post(URLS.masters + '/' + activeMaster + '/' + id, { _method: 'DELETE' })
                .done(function (res) { if (editingMasterId === id) setMasterEditMode(null); afterMasterChange(); toast(res.message || 'Data berhasil dihapus.'); })
                .fail(function (xhr) { formError($('#masterModal'), errorMessage(xhr, 'Gagal menghapus data.')); });
        });
    });

    // ---------- init ----------
    fillStatusSelects();
    applyMasterOptions(); // before select2, which reads the placeholder from the first option
    initProdukSelect2();
    initTable();
    loadTimeline();
})();
</script>
@endsection
