@extends('layouts.finance.app')
@section('title', 'Hitung Harga Paket')
@section('navbar')
    @include('layouts.finance.navbar')
@endsection

@section('content')
<div class="container-fluid">
    <style>
        .hh-card { border: 1px solid #e3e8f0; border-radius: .5rem; padding: .55rem .8rem; background: #fff; cursor: pointer; height: 100%; transition: box-shadow .15s, border-color .15s; }
        .hh-card:hover { box-shadow: 0 2px 8px rgba(0,0,0,.06); }
        .hh-card.active { border-color: #4e73df; box-shadow: 0 0 0 2px rgba(78,115,223,.25); }
        .hh-card .hh-num { font-size: 1.4rem; font-weight: 700; line-height: 1.1; }
        .hh-card .hh-lbl { font-size: .78rem; color: #6c7a92; }
        .hh-card .hh-hint { font-size: .72rem; color: #8a94a6; }
        .hh-name { font-weight: 600; }
        .hh-pills .nav-link { font-weight: 600; }
        #paketTable td, #alatTable td, #lainTable td, .hh-rows td { vertical-align: middle; }
        .hh-isi { max-width: 320px; }
        .hh-sec { border: 1px solid #e3e8f0; border-radius: .5rem; padding: .6rem .8rem; margin-bottom: .6rem; background: #fff; }
        .hh-sec-title { display: flex; align-items: center; justify-content: space-between; font-weight: 600; }
        .hh-sec-title .hh-sec-num { font-size: 1.05rem; }
        .hh-total { background: #f4f7fd; border-color: #c9d6f2; }
        .hh-total .hh-big { font-size: 1.5rem; font-weight: 700; }
        .hh-saran { background: #f2fbf5; border-color: #b9e2c6; }
        .hh-saran .hh-big { font-size: 1.5rem; font-weight: 700; color: #1e7e34; }
        .hh-note { font-size: .78rem; color: #6c7a92; }
        .hh-bar { height: 8px; background: #e9ecef; border-radius: 4px; overflow: hidden; min-width: 90px; }
        .hh-bar > div { height: 100%; background: #28a745; }
        .table-tipis { background: #fff3cd !important; }
        .hh-rows input[type=number] { min-width: 90px; }
    </style>

    <div class="d-flex flex-wrap align-items-center justify-content-between mt-3 mb-2">
        <div>
            <h4 class="page-title mb-0">Hitung Harga Paket</h4>
            <small class="text-muted">
                <strong>Modal per visit</strong> = Bahan (BHP × HPP) + Jasa Medis (tarif dokter + petugas) + Alat (biaya per pakai) + Biaya Lain.
                <strong>Harga saran</strong> = modal ÷ (1 − untung%), dibulatkan <strong>ke atas</strong> ke kelipatan Rp {{ number_format($pembulatan, 0, ',', '.') }}.
                Paket dengan untung di bawah <strong>{{ $untungMinimal }}%</strong> ditandai kuning, rugi ditandai merah.
                Pengajuan harga masuk ke approval <a href="{{ route('marketing.harga-tindakan.index') }}">Harga Paket Tindakan</a> (Admin / Finance).
            </small>
        </div>
    </div>

    <ul class="nav nav-pills hh-pills mb-3" role="tablist">
        <li class="nav-item"><a class="nav-link active" data-toggle="pill" href="#panePaket" role="tab" id="tabPaket"><i class="fas fa-calculator"></i> Paket</a></li>
        <li class="nav-item"><a class="nav-link" data-toggle="pill" href="#paneAlat" role="tab" id="tabAlat"><i class="fas fa-tools"></i> Alat Tindakan</a></li>
        <li class="nav-item"><a class="nav-link" data-toggle="pill" href="#paneLain" role="tab" id="tabLain"><i class="fas fa-list"></i> Biaya Lain</a></li>
    </ul>

    <div class="tab-content">
        {{-- ================= Paket ================= --}}
        <div class="tab-pane fade show active" id="panePaket" role="tabpanel">
            <div class="row mb-2" id="hhCards">
                <div class="col-6 col-md-4 col-xl mb-2"><div class="hh-card" data-filter="rugi"><div class="hh-lbl"><span class="badge badge-danger">Rugi</span></div><div class="hh-num text-danger" data-count="rugi">-</div><div class="hh-hint">harga di bawah modal</div></div></div>
                <div class="col-6 col-md-4 col-xl mb-2"><div class="hh-card" data-filter="tipis"><div class="hh-lbl"><span class="badge badge-warning">Untung tipis</span></div><div class="hh-num text-warning" data-count="tipis">-</div><div class="hh-hint">untung &lt; {{ $untungMinimal }}%</div></div></div>
                <div class="col-6 col-md-4 col-xl mb-2"><div class="hh-card" data-filter="belum_lengkap"><div class="hh-lbl"><span class="badge badge-secondary">Data belum lengkap</span></div><div class="hh-num text-dark" data-count="belum_lengkap">-</div><div class="hh-hint">HPP / jasa / alat belum diisi</div></div></div>
                <div class="col-6 col-md-4 col-xl mb-2"><div class="hh-card" data-filter="tanpa_harga"><div class="hh-lbl">Belum ada harga</div><div class="hh-num text-dark" data-count="tanpa_harga">-</div><div class="hh-hint">&nbsp;</div></div></div>
                <div class="col-6 col-md-4 col-xl mb-2"><div class="hh-card" data-filter="semua"><div class="hh-lbl">Semua paket aktif</div><div class="hh-num text-muted" data-count="semua">-</div><div class="hh-hint">&nbsp;</div></div></div>
            </div>
            <div class="card">
                <div class="card-body">
                    <input type="search" id="paketSearch" class="form-control form-control-sm mb-2" style="max-width:300px" placeholder="Cari nama paket...">
                    <table id="paketTable" class="table table-sm table-hover w-100">
                        <thead class="thead-light">
                            <tr>
                                <th>Paket</th>
                                <th class="text-right">Modal / visit</th>
                                <th class="text-right" title="Harga yang dibayar pasien: harga diskon kalau diskon aktif">Harga dibayar</th>
                                <th class="text-right">Untung</th>
                                <th class="text-right">Multi-visit</th>
                                <th>Pengajuan</th>
                                <th class="text-right">Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>

        {{-- ================= Alat ================= --}}
        <div class="tab-pane fade" id="paneAlat" role="tabpanel">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-wrap align-items-start mb-2" style="gap:8px">
                        <small class="text-muted" style="max-width:820px">
                            <strong>Biaya alat per pakai</strong> = (harga alat ÷ (umur × 12) + servis 12 bulan terakhir ÷ 12) ÷ target pemakaian per bulan.
                            Servis diambil dari menu <em>Inventory → Maintenance</em>. <strong>Terpakai</strong> = pemakaian sebenarnya dari riwayat tindakan, dibanding target.
                            <strong>Balik modal</strong> = perkiraan: jumlah tindakan sejak alat dipakai × untung kotor paket (harga sekarang − bahan − jasa − biaya lain) − servis.
                        </small>
                        <button type="button" class="btn btn-sm btn-primary ml-auto" id="alatTambah"><i class="fas fa-plus"></i> Tambah Alat Tindakan</button>
                    </div>
                    <table id="alatTable" class="table table-sm table-hover w-100">
                        <thead class="thead-light">
                            <tr>
                                <th>Alat</th>
                                <th class="text-right">Harga alat</th>
                                <th class="text-right">Umur</th>
                                <th class="text-right">Target / bln</th>
                                <th class="text-right">Servis 12 bln</th>
                                <th class="text-right">Biaya / pakai</th>
                                <th>Terpakai</th>
                                <th>Balik modal</th>
                                <th class="text-right">Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>

        {{-- ================= Biaya Lain ================= --}}
        <div class="tab-pane fade" id="paneLain" role="tabpanel">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-wrap align-items-start mb-2" style="gap:8px">
                        <small class="text-muted" style="max-width:820px">
                            Biaya per tindakan yang <strong>bukan</strong> BHP, jasa medis, atau alat: linen, sterilisasi, listrik, ruangan, limbah medis, sewa/bagi hasil alat.
                            Cara mudah mengisi: <strong>biaya sebulan ÷ jumlah tindakan sebulan</strong>. Nominal di sini adalah nilai awal; tiap paket boleh memakai nominalnya sendiri.
                            Jangan masukkan gaji tetap, marketing umum, atau pajak — itu ditutup dari % untung.
                        </small>
                        <button type="button" class="btn btn-sm btn-primary ml-auto" id="lainTambah"><i class="fas fa-plus"></i> Tambah Biaya Lain</button>
                    </div>
                    <table id="lainTable" class="table table-sm table-hover w-100">
                        <thead class="thead-light">
                            <tr>
                                <th>Nama</th>
                                <th class="text-right">Nominal awal / tindakan</th>
                                <th>Keterangan</th>
                                <th class="text-right">Dipakai</th>
                                <th>Status</th>
                                <th class="text-right">Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ================= Hitung Harga modal ================= --}}
<div class="modal fade" id="hitungModal" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="hitungTitle">Hitung Harga</h5>
                    <small class="text-muted" id="hitungIsi"></small>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body" style="background:#f8f9fc">
                <div id="hitungPeringatan"></div>
                <div class="row">
                    <div class="col-lg-7">
                        <div class="hh-sec">
                            <div class="hh-sec-title"><span>1. Bahan (BHP) <small class="text-muted font-weight-normal">otomatis</small></span><span class="hh-sec-num" id="secBahan">-</span></div>
                            <a href="#" class="small" data-toggle="collapse" data-target="#rincianBahan">lihat rincian</a>
                            <div class="collapse" id="rincianBahan"><div id="rincianBahanBody" class="small mt-1"></div></div>
                        </div>
                        <div class="hh-sec">
                            <div class="hh-sec-title"><span>2. Jasa Medis <small class="text-muted font-weight-normal">otomatis dari tarif Jasa Medis</small></span><span class="hh-sec-num" id="secJasa">-</span></div>
                            <a href="#" class="small" data-toggle="collapse" data-target="#rincianJasa">lihat rincian</a>
                            <div class="collapse" id="rincianJasa"><div id="rincianJasaBody" class="small mt-1"></div></div>
                        </div>
                        <div class="hh-sec">
                            <div class="hh-sec-title"><span>3. Alat <small class="text-muted font-weight-normal">biaya per pakai × jumlah pakai</small></span><span class="hh-sec-num" id="secAlat">-</span></div>
                            <table class="table table-sm mb-1 hh-rows"><tbody id="alatRows"></tbody></table>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="alatRowAdd"><i class="fas fa-plus"></i> Tambah alat</button>
                            <span class="hh-note ml-1">Alat belum ada di pilihan? Tambahkan di tab <strong>Alat Tindakan</strong>.</span>
                        </div>
                        <div class="hh-sec">
                            <div class="hh-sec-title"><span>4. Biaya Lain <small class="text-muted font-weight-normal">nominal bisa diubah per paket</small></span><span class="hh-sec-num" id="secLain">-</span></div>
                            <table class="table table-sm mb-1 hh-rows"><tbody id="lainRows"></tbody></table>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="lainRowAdd"><i class="fas fa-plus"></i> Tambah biaya</button>
                        </div>
                    </div>
                    <div class="col-lg-5">
                        <div class="hh-sec hh-total">
                            <div class="d-flex justify-content-between align-items-end">
                                <div><div class="small text-muted">Total modal per visit</div><div class="hh-big" id="totModal">-</div></div>
                                <div class="text-right small text-muted" id="totModalVisit"></div>
                            </div>
                            <div class="hh-note mt-1" id="totKomposisi"></div>
                        </div>
                        <div class="hh-sec hh-saran">
                            <div class="d-flex align-items-center" style="gap:8px">
                                <label class="mb-0 small font-weight-bold" for="untungTarget">Untung diinginkan</label>
                                <div class="input-group input-group-sm" style="width:110px">
                                    <input type="number" class="form-control" id="untungTarget" min="0" max="95" step="1" value="{{ $untungTarget }}">
                                    <div class="input-group-append"><span class="input-group-text">%</span></div>
                                </div>
                            </div>
                            <div class="mt-2 small text-muted">Harga saran</div>
                            <div class="hh-big" id="hargaSaran">-</div>
                            <div class="hh-note" id="hargaSaranNote"></div>
                            <div id="saranVisitWrap" class="mt-2" style="display:none">
                                <div class="small text-muted">Harga saran multi-visit (<span class="jml-visit"></span>× visit)</div>
                                <div class="font-weight-bold" style="font-size:1.15rem;color:#1e7e34" id="hargaSaranVisit">-</div>
                                <div class="hh-note" id="hargaSaranVisitNote"></div>
                            </div>
                        </div>
                        <div class="hh-sec">
                            <div class="small font-weight-bold mb-1">Harga sekarang</div>
                            <div id="hargaSekarang" class="small"></div>
                        </div>
                        <div class="hh-sec">
                            <div class="small font-weight-bold mb-1">Ajukan harga baru</div>
                            <div id="pengajuanInfo"></div>
                            <div class="form-group mb-2">
                                <label class="small mb-0" for="ajHarga">Harga normal (Rp) <span class="text-danger">*</span></label>
                                <div class="input-group input-group-sm">
                                    <input type="number" class="form-control" id="ajHarga" min="1" step="1">
                                    <div class="input-group-append"><button type="button" class="btn btn-outline-success" id="pakaiSaran" title="Isi dengan harga saran">Pakai saran</button></div>
                                </div>
                                <div class="hh-note" id="ajHargaInfo"></div>
                            </div>
                            <div class="form-group mb-2" id="ajVisitWrap" style="display:none">
                                <label class="small mb-0" for="ajHargaVisit">Harga multi-visit (Rp, <span class="jml-visit"></span>× visit)</label>
                                <div class="input-group input-group-sm">
                                    <input type="number" class="form-control" id="ajHargaVisit" min="1" step="1">
                                    <div class="input-group-append"><button type="button" class="btn btn-outline-success" id="pakaiSaranVisit">Pakai saran</button></div>
                                </div>
                                <div class="hh-note" id="ajHargaVisitInfo"></div>
                            </div>
                            <div class="hh-note mb-2">Harga diskon dan status diskon tidak diubah dari sini (ubah di menu Harga Paket Tindakan).</div>
                            <div class="form-group mb-0">
                                <label class="small mb-0" for="ajAlasan">Alasan <span class="text-danger">*</span></label>
                                <textarea class="form-control form-control-sm" id="ajAlasan" rows="2" maxlength="500" placeholder="Mis. hitung ulang modal: HPP BHP naik, alat baru, ..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <div class="mr-auto d-flex align-items-center" style="gap:6px;min-width:320px">
                    <select id="salinDari" class="form-control form-control-sm" style="width:260px"><option value="">Salin alat & biaya lain dari paket...</option></select>
                </div>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-outline-primary" id="btnSimpan"><i class="fas fa-save"></i> Simpan komponen</button>
                <button type="button" class="btn btn-primary" id="btnAjukan"><i class="fas fa-paper-plane"></i> Simpan & Ajukan Harga</button>
            </div>
        </div>
    </div>
</div>

{{-- ================= Alat edit modal ================= --}}
<div class="modal fade" id="alatModal" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-lg" role="document">
        <form class="modal-content" id="alatForm" autocomplete="off">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="alatModalTitle">Alat Tindakan</h5>
                    <small class="text-muted" id="alatModalInfo"></small>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="alatId">
                <div class="form-group" id="alatPilihWrap">
                    <label class="small font-weight-bold mb-1" for="alatPilih">Pilih barang dari Inventory</label>
                    <select id="alatPilih" class="form-control" style="width:100%"></select>
                </div>
                <div id="alatFormBody" style="display:none">
                    <div class="custom-control custom-checkbox mb-2">
                        <input type="checkbox" class="custom-control-input" id="alatAktif" checked>
                        <label class="custom-control-label font-weight-bold" for="alatAktif">Alat Tindakan</label>
                        <small class="text-muted d-block">Hilangkan centang kalau barang ini bukan alat tindakan.</small>
                    </div>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label class="small font-weight-bold mb-0" for="alatUmur">Umur pakai (tahun) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="alatUmur" min="1" max="50" step="1" placeholder="mis. 5">
                            <small class="text-muted">Laser/HIFU/RF ± 5, USG 5–7, dental unit 7–10, alat kecil 3–5.</small>
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="small font-weight-bold mb-0" for="alatTarget">Target pemakaian per bulan (per unit) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="alatTarget" min="1" step="1" placeholder="mis. 100">
                            <small class="text-muted">Berapa kali alat <em>seharusnya</em> dipakai dalam bulan yang normal — bukan pemakaian bulan sepi.</small>
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="small font-weight-bold mb-0" for="alatHarga">Harga alat per unit (Rp)</label>
                            <input type="number" class="form-control" id="alatHarga" min="0" step="1">
                            <small class="text-muted" id="alatHargaHint">Harga beli + ongkir + pasang + pelatihan. Kosong = harga pembelian terakhir. Alat lama tanpa nota: harga alat sejenis sekarang.</small>
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="small font-weight-bold mb-0" for="alatTanggal">Tanggal mulai dipakai</label>
                            <input type="date" class="form-control" id="alatTanggal" max="{{ date('Y-m-d') }}">
                            <small class="text-muted">Untuk balik modal. Kosong = tanggal pembelian terakhir.</small>
                        </div>
                    </div>
                    <div class="hh-sec hh-total mb-0">
                        <div class="small text-muted">Biaya alat per pakai</div>
                        <div class="hh-big" id="alatPreview">-</div>
                        <div class="hh-note" id="alatPreviewNote"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary" id="alatSimpan"><i class="fas fa-save"></i> Simpan</button>
            </div>
        </form>
    </div>
</div>

{{-- ================= Biaya Lain modal ================= --}}
<div class="modal fade" id="lainModal" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog" role="document">
        <form class="modal-content" id="lainForm" autocomplete="off">
            <div class="modal-header">
                <h5 class="modal-title" id="lainModalTitle">Biaya Lain</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="lainId">
                <div class="form-group">
                    <label class="small font-weight-bold mb-0" for="lainNama">Nama <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="lainNama" maxlength="100" placeholder="mis. Linen & Laundry">
                </div>
                <div class="form-group">
                    <label class="small font-weight-bold mb-0" for="lainNominal">Nominal awal per tindakan (Rp) <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="lainNominal" min="0" step="1">
                    <div class="border rounded p-2 mt-1" style="background:#f8f9fc">
                        <div class="small font-weight-bold mb-1"><i class="fas fa-calculator"></i> Bantu hitung</div>
                        <div class="d-flex flex-wrap align-items-center small" style="gap:6px">
                            <span>Biaya sebulan Rp</span><input type="number" class="form-control form-control-sm" id="lainBulan" min="0" style="width:130px">
                            <span>÷ tindakan sebulan</span><input type="number" class="form-control form-control-sm" id="lainJumlah" min="1" style="width:90px">
                            <button type="button" class="btn btn-sm btn-outline-success" id="lainHitung">Isi nominal</button>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label class="small font-weight-bold mb-0" for="lainKet">Keterangan</label>
                    <input type="text" class="form-control" id="lainKet" maxlength="255" placeholder="mis. cara menghitung / sumber angka">
                </div>
                <div class="custom-control custom-checkbox" id="lainTerapkanWrap" style="display:none">
                    <input type="checkbox" class="custom-control-input" id="lainTerapkan" checked>
                    <label class="custom-control-label small" for="lainTerapkan">Terapkan nominal baru ke paket yang masih memakai nominal lama</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(function () {
    var CSRF = '{{ csrf_token() }}';
    var MIN = {{ $untungMinimal }}, TARGET = {{ $untungTarget }}, BULAT = {{ $pembulatan }};
    var URL_BASE = '{{ url('/harga-paket') }}';
    var URLS = {
        data: '{{ route('harga-paket.data') }}',
        alatData: '{{ route('harga-paket.alat.data') }}',
        alatCari: '{{ route('harga-paket.alat.cari') }}',
        lainData: '{{ route('harga-paket.biaya-lain.data') }}',
        lainStore: '{{ route('harga-paket.biaya-lain.store') }}'
    };
    var state = { filter: null, rows: [], h: null, opsiAlat: {}, opsiLain: {}, lain: [] };

    function esc(v) { return $('<div>').text(v == null ? '' : v).html(); }
    function num(v) { return v === null || v === undefined || v === '' || isNaN(v) ? null : Number(v); }
    function rupiah(v) { return v === null || v === undefined || v === '' ? '-' : 'Rp ' + Number(v).toLocaleString('id-ID', { maximumFractionDigits: 0 }); }
    function pct(v) { return v === null || v === undefined ? '-' : Number(v).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + '%'; }
    function bulatkan(v) { return v > 0 ? Math.ceil(Math.round(v * 100) / 100 / BULAT) * BULAT : 0; }
    function untung(harga, modal) { return harga > 0 ? Math.round((harga - modal) / harga * 1000) / 10 : null; }
    function untungBadge(p) {
        if (p === null) return '<span class="text-muted">-</span>';
        var cls = p < 0 ? 'badge-danger' : (p < MIN ? 'badge-warning' : 'badge-success');
        return '<span class="badge ' + cls + '" style="font-size:.85rem">' + pct(p) + '</span>';
    }
    function ajaxJson(url, type, data) {
        return $.ajax({ url: url, type: type, contentType: 'application/json', headers: { 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' }, data: data ? JSON.stringify(data) : undefined });
    }
    function fail(xhr, fallback) {
        var json = (xhr && xhr.responseJSON) || {};
        var list = json.errors ? [].concat.apply([], Object.values(json.errors)) : [json.message || fallback];
        Swal.fire({ icon: 'warning', title: 'Gagal', html: '<div class="text-left small">' + list.filter(function (v, i, a) { return a.indexOf(v) === i; }).map(esc).join('<br>') + '</div>' });
    }
    var DT_LANG = { zeroRecords: 'Tidak ada data', emptyTable: 'Tidak ada data', info: '_START_–_END_ dari _TOTAL_', infoEmpty: '0 data', infoFiltered: '', lengthMenu: 'Tampil _MENU_', paginate: { previous: '‹', next: '›' } };

    // ======================================================= Paket list
    var paketTable = $('#paketTable').DataTable({
        data: [], autoWidth: false, pageLength: 25, order: [[3, 'asc']],
        dom: 'rt<"d-flex flex-wrap justify-content-between align-items-center mt-2"lip>', language: DT_LANG,
        columns: [
            {
                data: 'nama', render: function (d, t, r) {
                    if (t !== 'display') return d;
                    return '<div class="hh-name">' + esc(d) + '</div><div class="small text-muted">' + esc(r.spesialis || '-') + '</div>'
                        + (r.jumlah_tindakan ? '<div class="small hh-isi text-truncate" title="' + esc(r.isi) + '">' + r.jumlah_tindakan + ' tindakan: ' + esc(r.isi) + '</div>' : '')
                        + (r.peringatan.length ? '<div class="small text-warning" title="' + esc(r.peringatan.join('\n')) + '"><i class="fas fa-exclamation-triangle"></i> ' + r.peringatan.length + ' data belum lengkap</div>' : '');
                }
            },
            {
                data: 'modal', className: 'text-right text-nowrap', render: function (d, t, r) {
                    if (t !== 'display') return d;
                    return '<strong>' + rupiah(d) + '</strong><div class="small text-muted">bahan ' + rupiah(r.bahan) + ' · jasa ' + rupiah(r.jasa) + '</div>'
                        + '<div class="small text-muted">alat ' + rupiah(r.biaya_alat) + ' · lain ' + rupiah(r.biaya_lain_total) + '</div>';
                }
            },
            {
                data: 'harga_efektif', className: 'text-right text-nowrap', render: function (d, t, r) {
                    if (t !== 'display') return d || 0;
                    if (!d) return '<span class="badge badge-dark">belum ada</span>';
                    return '<strong>' + rupiah(d) + '</strong>' + (r.diskon_active && r.harga_diskon ? '<div class="small"><span class="badge badge-success">diskon</span> normal ' + rupiah(r.harga) + '</div>' : '');
                }
            },
            {
                data: 'untung_persen', className: 'text-right text-nowrap', render: function (d, t, r) {
                    if (t !== 'display') return d === null ? 9999 : d;
                    return untungBadge(d) + (r.jasa_persen !== null ? '<div class="small text-muted">jasa ' + pct(r.jasa_persen) + ' dari harga</div>' : '');
                }
            },
            {
                data: 'harga_paket_visit', className: 'text-right text-nowrap', orderable: false, render: function (d, t, r) {
                    if (!d) return '<span class="text-muted">-</span>';
                    return rupiah(d) + '<div class="small text-muted">' + r.jumlah_visit + '× visit · ' + untungBadge(r.untung_persen_paket_visit) + '</div>';
                }
            },
            {
                data: 'pengajuan', orderable: false, render: function (d) {
                    return d ? '<span class="badge badge-primary">Menunggu</span> <strong>' + rupiah(d.harga) + '</strong>' : '';
                }
            },
            {
                data: 'id', orderable: false, className: 'text-right text-nowrap', render: function (id) {
                    return '<button type="button" class="btn btn-sm btn-primary btn-hitung" data-id="' + id + '"><i class="fas fa-calculator"></i> Hitung Harga</button>';
                }
            }
        ],
        createdRow: function (tr, r) {
            if (r.status === 'rugi') $(tr).addClass('table-danger');
            else if (r.status === 'tipis') $(tr).addClass('table-tipis');
        }
    });

    $.fn.dataTable.ext.search.push(function (settings, data, idx) {
        if (settings.nTable.id !== 'paketTable' || !state.filter || state.filter === 'semua') return true;
        var r = paketTable.row(idx).data();
        if (state.filter === 'belum_lengkap') return r.peringatan.length > 0;
        return r.status === state.filter;
    });

    function loadPaket(keepPage) {
        return $.get(URLS.data).done(function (res) {
            state.rows = res.data;
            $.each(res.counts, function (k, v) { $('#hhCards [data-count="' + k + '"]').text(Number(v).toLocaleString('id-ID')); });
            if (state.filter === null) state.filter = res.counts.rugi > 0 ? 'rugi' : (res.counts.tipis > 0 ? 'tipis' : 'semua');
            $('#hhCards .hh-card').removeClass('active').filter('[data-filter="' + state.filter + '"]').addClass('active');
            paketTable.clear().rows.add(res.data).draw(!keepPage);
            var $s = $('#salinDari');
            $s.find('option:not(:first)').remove();
            res.data.forEach(function (r) { if (r.jumlah_alat || r.jumlah_biaya_lain) $s.append(new Option(r.nama + ' (' + r.jumlah_alat + ' alat, ' + r.jumlah_biaya_lain + ' biaya lain)', r.id)); });
        }).fail(function (xhr) { fail(xhr, 'Data paket tidak dapat dimuat.'); });
    }
    $('#hhCards').on('click', '.hh-card', function () {
        state.filter = $(this).data('filter');
        $('#hhCards .hh-card').removeClass('active');
        $(this).addClass('active');
        paketTable.draw();
    });
    var searchTimer;
    $('#paketSearch').on('input', function () {
        var v = this.value;
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function () { paketTable.search(v).draw(); }, 250);
    });

    // ======================================================= Hitung modal
    function alatOptionsHtml(selected) {
        var html = '<option value="">Pilih alat...</option>';
        $.each(state.opsiAlat, function (id, a) {
            html += '<option value="' + id + '"' + (Number(id) === Number(selected) ? ' selected' : '') + '>' + esc(a.nama) + (a.ruangan ? ' — ' + esc(a.ruangan) : '') + (a.per_pakai === null ? ' (belum lengkap)' : ' · ' + rupiah(a.per_pakai) + '/pakai') + '</option>';
        });
        return html;
    }
    function lainOptionsHtml(selected) {
        var html = '<option value="">Pilih biaya...</option>';
        $.each(state.opsiLain, function (id, b) { html += '<option value="' + id + '"' + (Number(id) === Number(selected) ? ' selected' : '') + '>' + esc(b.nama) + '</option>'; });
        return html;
    }
    function alatRow(a) {
        // An alat no longer offered (unmarked) still shows, so it can be removed
        if (a && a.barang_id && !state.opsiAlat[a.barang_id]) {
            state.opsiAlat[a.barang_id] = { id: a.barang_id, nama: a.nama + ' (bukan alat tindakan lagi)', per_pakai: a.per_pakai, peringatan: a.peringatan || [] };
        }
        return '<tr class="alat-row"><td style="width:55%"><select class="form-control form-control-sm ar-barang">' + alatOptionsHtml(a ? a.barang_id : null) + '</select><div class="small text-warning ar-warn"></div></td>'
            + '<td style="width:120px"><div class="input-group input-group-sm"><input type="number" class="form-control ar-qty" min="0.01" step="0.5" value="' + (a ? a.qty : 1) + '" title="Berapa kali alat dipakai per visit"><div class="input-group-append"><span class="input-group-text">×</span></div></div></td>'
            + '<td class="text-right text-nowrap small ar-sub">-</td>'
            + '<td class="text-center" style="width:30px"><button type="button" class="btn btn-sm btn-link text-danger p-0 row-del" title="Hapus"><i class="fas fa-times"></i></button></td></tr>';
    }
    function lainRow(l) {
        if (l && l.biaya_lain_id && !state.opsiLain[l.biaya_lain_id]) {
            state.opsiLain[l.biaya_lain_id] = { id: l.biaya_lain_id, nama: l.nama + ' (nonaktif)', nominal: l.nominal_master || 0 };
        }
        return '<tr class="lain-row"><td style="width:55%"><select class="form-control form-control-sm lr-biaya">' + lainOptionsHtml(l ? l.biaya_lain_id : null) + '</select><div class="small text-muted lr-ket"></div></td>'
            + '<td colspan="2"><div class="input-group input-group-sm"><div class="input-group-prepend"><span class="input-group-text">Rp</span></div><input type="number" class="form-control lr-nominal" min="0" step="1" value="' + (l ? Math.round(l.nominal) : '') + '"></div></td>'
            + '<td class="text-center" style="width:30px"><button type="button" class="btn btn-sm btn-link text-danger p-0 row-del" title="Hapus"><i class="fas fa-times"></i></button></td></tr>';
    }
    function readKomponen() {
        var alat = [], lain = [];
        $('#alatRows .alat-row').each(function () {
            var id = num($(this).find('.ar-barang').val());
            if (id) alat.push({ barang_id: id, qty: num($(this).find('.ar-qty').val()) || 0 });
        });
        $('#lainRows .lain-row').each(function () {
            var id = num($(this).find('.lr-biaya').val());
            if (id) lain.push({ biaya_lain_id: id, nominal: num($(this).find('.lr-nominal').val()) || 0 });
        });
        return { alat: alat, biaya_lain: lain };
    }

    function renderStatic(h) {
        $('#hitungTitle').text('Hitung Harga: ' + h.nama);
        $('#hitungIsi').text(h.tindakan.length ? h.tindakan.length + ' tindakan: ' + h.tindakan.map(function (k) { return k.nama; }).join(', ') : 'Paket belum berisi tindakan');
        $('#secBahan').text(rupiah(h.bahan));
        $('#secJasa').text(rupiah(h.jasa));
        $('#rincianBahanBody').html(h.tindakan.map(function (k) {
            return '<div class="font-weight-bold mt-1">' + esc(k.nama) + ' <span class="float-right">' + rupiah(k.bahan) + '</span></div>'
                + (k.obat.length ? k.obat.map(function (o) {
                    return '<div class="d-flex justify-content-between text-muted"><span>' + esc(o.nama) + ' · ' + o.qty + ' ' + esc(o.satuan || '') + ' × ' + rupiah(o.hpp)
                        + (o.estimasi ? ' <span class="badge badge-warning">estimasi</span>' : '') + (o.hpp <= 0 ? ' <span class="badge badge-danger">belum ada HPP</span>' : '') + '</span><span>' + rupiah(o.subtotal) + '</span></div>';
                }).join('') : '<div class="text-muted">tidak ada BHP</div>');
        }).join('') || '<span class="text-muted">-</span>');
        $('#rincianJasaBody').html(h.tindakan.map(function (k) {
            return '<div class="d-flex justify-content-between"><span>' + esc(k.nama) + (k.jasa_diatur ? ' <span class="text-muted">· dokter ' + rupiah(k.jasa_dokter) + ' + petugas ' + rupiah(k.jasa_petugas) + '</span>' : ' <span class="badge badge-warning">tarif belum diatur</span>') + '</span><span>' + rupiah(k.jasa) + '</span></div>';
        }).join('') + '<div class="hh-note mt-1">Tarif diatur di menu Jasa Medis. Untuk dokter/petugas bergaji tetap, ini adalah insentif per tindakan.</div>');
        $('#hitungPeringatan').html(h.peringatan.length ? '<div class="alert alert-warning py-2 small mb-2"><strong><i class="fas fa-exclamation-triangle"></i> Data belum lengkap — modal bisa lebih kecil dari seharusnya:</strong><ul class="mb-0 pl-3">' + h.peringatan.map(function (p) { return '<li>' + esc(p) + '</li>'; }).join('') + '</ul></div>' : '');
        $('.jml-visit').text(h.jumlah_visit);
        $('#saranVisitWrap, #ajVisitWrap').toggle(!!h.multi_visit);
        var now = '<div class="d-flex justify-content-between"><span>Normal</span><span>' + (h.harga ? rupiah(h.harga) : '<span class="badge badge-dark">belum ada</span>') + '</span></div>';
        if (h.harga_diskon) now += '<div class="d-flex justify-content-between"><span>Diskon ' + (h.diskon_active ? '<span class="badge badge-success">aktif</span>' : '<span class="badge badge-light">nonaktif</span>') + '</span><span>' + rupiah(h.harga_diskon) + '</span></div>';
        if (h.harga_paket_visit) now += '<div class="d-flex justify-content-between"><span>Multi-visit (' + h.jumlah_visit + '×)</span><span>' + rupiah(h.harga_paket_visit) + '</span></div>';
        now += '<div class="d-flex justify-content-between border-top mt-1 pt-1"><span>Untung saat ini <span class="text-muted">(dari harga dibayar)</span></span><span id="untungSekarang">-</span></div>';
        if (h.harga_paket_visit) now += '<div class="d-flex justify-content-between"><span>Untung multi-visit</span><span id="untungSekarangVisit">-</span></div>';
        $('#hargaSekarang').html(now);
    }

    function recalc() {
        var h = state.h;
        if (!h) return;
        var alat = 0, lain = 0;
        $('#alatRows .alat-row').each(function () {
            var a = state.opsiAlat[$(this).find('.ar-barang').val()];
            var qty = num($(this).find('.ar-qty').val()) || 0;
            var sub = a && a.per_pakai ? a.per_pakai * qty : 0;
            alat += sub;
            $(this).find('.ar-sub').html(a ? (a.per_pakai === null ? '<span class="text-warning">Rp 0</span>' : rupiah(a.per_pakai) + ' × ' + qty + '<br><strong>' + rupiah(sub) + '</strong>') : '-');
            $(this).find('.ar-warn').text(a && a.peringatan && a.peringatan.length ? a.peringatan.join(', ') + ' → dihitung Rp 0' : '');
        });
        $('#lainRows .lain-row').each(function () {
            lain += num($(this).find('.lr-nominal').val()) || 0;
            var b = state.opsiLain[$(this).find('.lr-biaya').val()];
            $(this).find('.lr-ket').text(b ? 'nilai awal ' + rupiah(b.nominal) + (b.keterangan ? ' · ' + b.keterangan : '') : '');
        });
        var modal = h.bahan + h.jasa + alat + lain;
        $('#secAlat').text(rupiah(alat));
        $('#secLain').text(rupiah(lain));
        $('#totModal').text(rupiah(modal));
        $('#totModalVisit').html(h.multi_visit ? 'Modal ' + h.jumlah_visit + '× visit<br><strong>' + rupiah(modal * h.jumlah_visit) + '</strong>' : '');
        var parts = [['Bahan', h.bahan], ['Jasa', h.jasa], ['Alat', alat], ['Lain', lain]].filter(function (p) { return p[1] > 0; });
        $('#totKomposisi').text(modal > 0 ? parts.map(function (p) { return p[0] + ' ' + pct(p[1] / modal * 100); }).join(' · ') : '');

        var u = num($('#untungTarget').val());
        var ok = u !== null && u >= 0 && u < 100 && modal > 0;
        if (ok) {
            var mentah = modal / (1 - u / 100), bulat = bulatkan(mentah);
            state.saran = bulat;
            $('#hargaSaran').text(rupiah(bulat));
            $('#hargaSaranNote').html('Hitungan pas: ' + rupiah(mentah) + '. <strong>Dibulatkan ke atas</strong> ke kelipatan ' + rupiah(BULAT)
                + (bulat - mentah >= 0.5 ? ' (+' + rupiah(bulat - mentah) + ')' : '') + ', jadi untung setelah dibulatkan <strong>' + pct(untung(bulat, modal)) + '</strong>.');
            if (h.multi_visit) {
                var mv = modal * h.jumlah_visit, mm = mv / (1 - u / 100), bv = bulatkan(mm);
                state.saranVisit = bv;
                $('#hargaSaranVisit').text(rupiah(bv));
                $('#hargaSaranVisitNote').html('Pas ' + rupiah(mm) + ', dibulatkan ke atas → untung ' + pct(untung(bv, mv)) + '. Kalau ingin harga multi-visit lebih murah dari ' + h.jumlah_visit + '× harga normal, turunkan % untung atau isi manual di bawah.');
            }
        } else {
            state.saran = state.saranVisit = null;
            $('#hargaSaran').text('-');
            $('#hargaSaranNote').text(modal > 0 ? 'Isi untung 0–95%.' : 'Modal masih Rp 0: lengkapi BHP, jasa, alat, atau biaya lain dulu.');
            $('#hargaSaranVisit').text('-');
            $('#hargaSaranVisitNote').text('');
        }

        $('#untungSekarang').html(untungBadge(untung(h.harga_efektif, modal)));
        $('#untungSekarangVisit').html(untungBadge(untung(h.harga_paket_visit, modal * h.jumlah_visit)));
        var aj = num($('#ajHarga').val());
        $('#ajHargaInfo').html(aj ? 'Untung di harga ini: ' + untungBadge(untung(aj, modal)) + (untung(aj, modal) < MIN ? ' <span class="text-danger">di bawah ' + MIN + '%</span>' : '') : '');
        var ajv = num($('#ajHargaVisit').val());
        $('#ajHargaVisitInfo').html(ajv ? 'Untung: ' + untungBadge(untung(ajv, modal * h.jumlah_visit)) : '');
        state.modal = modal;
    }

    function fillKomponen(alat, lain) {
        $('#alatRows').html(alat.map(alatRow).join(''));
        $('#lainRows').html(lain.map(lainRow).join(''));
        recalc();
    }

    function openHitung(id) {
        $.get(URL_BASE + '/paket/' + id).done(function (res) {
            state.h = res.hitung;
            state.opsiAlat = {};
            res.opsi_alat.forEach(function (a) { state.opsiAlat[a.id] = a; });
            state.opsiLain = {};
            res.opsi_biaya_lain.forEach(function (b) { state.opsiLain[b.id] = b; });
            renderStatic(state.h);
            $('#untungTarget').val(TARGET);
            $('#ajHarga').val(state.h.harga ? Math.round(state.h.harga) : '');
            $('#ajHargaVisit').val(state.h.harga_paket_visit ? Math.round(state.h.harga_paket_visit) : '');
            $('#ajAlasan').val('');
            $('#salinDari').val('');
            $('#pengajuanInfo').html(res.pengajuan ? '<div class="alert alert-primary py-1 px-2 small mb-2">Ada pengajuan menunggu: ' + rupiah(res.pengajuan.harga) + ' (' + esc(res.pengajuan.tanggal) + '). Pengajuan baru akan menggantikannya.</div>' : '');
            fillKomponen(state.h.alat, state.h.biaya_lain);
            $('#hitungModal').modal('show');
        }).fail(function (xhr) { fail(xhr, 'Data paket tidak dapat dimuat.'); });
    }
    $('#paketTable').on('click', '.btn-hitung', function () { openHitung($(this).data('id')); });

    $('#alatRowAdd').on('click', function () {
        if (!Object.keys(state.opsiAlat).length) {
            Swal.fire({ icon: 'info', title: 'Belum ada alat tindakan', text: 'Tandai alat dulu di tab Alat Tindakan (atau Inventory → List Barang), lalu buka lagi Hitung Harga.' });
            return;
        }
        $('#alatRows').append(alatRow(null));
        recalc();
    });
    $('#lainRowAdd').on('click', function () { $('#lainRows').append(lainRow(null)); recalc(); });
    $('#hitungModal').on('click', '.row-del', function () { $(this).closest('tr').remove(); recalc(); });
    $('#hitungModal').on('change', '.lr-biaya', function () {
        // Fill the master nominal when empty or when it still holds the previous choice's auto-filled value
        var b = state.opsiLain[this.value], $n = $(this).closest('tr').find('.lr-nominal');
        if (b && (!$n.val() || String($n.val()) === String($n.data('auto')))) {
            $n.val(Math.round(b.nominal)).data('auto', Math.round(b.nominal));
        }
        recalc();
    });
    $('#hitungModal').on('input change', '.ar-barang, .ar-qty, .lr-nominal, #untungTarget, #ajHarga, #ajHargaVisit', recalc);
    $('#pakaiSaran').on('click', function () { if (state.saran) { $('#ajHarga').val(state.saran); recalc(); } });
    $('#pakaiSaranVisit').on('click', function () { if (state.saranVisit) { $('#ajHargaVisit').val(state.saranVisit); recalc(); } });

    $('#salinDari').on('change', function () {
        var id = this.value;
        if (!id) return;
        $.get(URL_BASE + '/paket/' + id).done(function (res) {
            fillKomponen(res.hitung.alat, res.hitung.biaya_lain);
            Swal.fire({ icon: 'success', title: 'Disalin', text: 'Alat & biaya lain dari "' + res.hitung.nama + '" disalin. Klik Simpan supaya tersimpan.', timer: 2200, showConfirmButton: false });
        }).fail(function (xhr) { fail(xhr, 'Gagal menyalin.'); });
    });

    function validKomponen(k) {
        var problems = [];
        if ($('#alatRows .alat-row').filter(function () { return !$(this).find('.ar-barang').val(); }).length) problems.push('Ada baris alat yang belum dipilih (hapus atau pilih alatnya).');
        if ($('#lainRows .lain-row').filter(function () { return !$(this).find('.lr-biaya').val(); }).length) problems.push('Ada baris biaya lain yang belum dipilih.');
        k.alat.forEach(function (a) { if (!(a.qty > 0)) problems.push('Jumlah pakai alat harus lebih dari 0.'); });
        var ids = k.alat.map(function (a) { return a.barang_id; });
        if (ids.length !== new Set(ids).size) problems.push('Alat yang sama dipilih dua kali.');
        var lids = k.biaya_lain.map(function (b) { return b.biaya_lain_id; });
        if (lids.length !== new Set(lids).size) problems.push('Biaya lain yang sama dipilih dua kali.');
        if (problems.length) Swal.fire({ icon: 'warning', title: 'Periksa lagi', html: '<div class="text-left small">' + problems.map(esc).join('<br>') + '</div>' });
        return !problems.length;
    }

    $('#btnSimpan').on('click', function () {
        var k = readKomponen();
        if (!validKomponen(k)) return;
        var $b = $(this).prop('disabled', true);
        ajaxJson(URL_BASE + '/paket/' + state.h.id, 'POST', k).done(function (res) {
            state.h = res.hitung;
            // Warnings / breakdown follow the saved alat (e.g. an alat without umur)
            renderStatic(state.h);
            recalc();
            loadPaket(true);
            Swal.fire({ icon: 'success', title: 'Tersimpan', text: res.message, timer: 1600, showConfirmButton: false });
        }).fail(function (xhr) { fail(xhr, 'Gagal menyimpan.'); }).always(function () { $b.prop('disabled', false); });
    });

    $('#btnAjukan').on('click', function () {
        var k = readKomponen();
        if (!validKomponen(k)) return;
        var harga = num($('#ajHarga').val()), alasan = $.trim($('#ajAlasan').val());
        var problems = [];
        if (!(harga > 0)) problems.push('Isi harga normal yang diajukan (atau klik "Pakai saran").');
        if (!alasan) problems.push('Alasan wajib diisi.');
        if (problems.length) { Swal.fire({ icon: 'warning', title: 'Periksa lagi', html: problems.map(esc).join('<br>') }); return; }
        var ut = num($('#untungTarget').val());
        var payload = $.extend({}, k, { harga: harga, untung_target: ut !== null && ut >= 0 && ut <= 95 ? ut : null, alasan: alasan });
        if (state.h.multi_visit && num($('#ajHargaVisit').val())) payload.harga_paket_visit = num($('#ajHargaVisit').val());
        var u = untung(harga, state.modal);
        var kirim = function () {
            var $b = $('#btnAjukan').prop('disabled', true);
            ajaxJson(URL_BASE + '/paket/' + state.h.id + '/ajukan', 'POST', payload).done(function (res) {
                $('#hitungModal').modal('hide');
                loadPaket(true);
                Swal.fire({ icon: 'success', title: 'Terkirim', text: res.message });
            }).fail(function (xhr) { fail(xhr, 'Pengajuan gagal dikirim.'); loadPaket(true); }).always(function () { $b.prop('disabled', false); });
        };
        if (u !== null && u < MIN) {
            Swal.fire({
                icon: 'warning', title: u < 0 ? 'Harga di bawah modal (rugi)' : 'Untung hanya ' + pct(u),
                text: 'Di bawah batas minimal ' + MIN + '%. Tetap ajukan?', showCancelButton: true, confirmButtonText: 'Tetap ajukan', cancelButtonText: 'Periksa lagi', reverseButtons: true
            }).then(function (r) { if (r.value) kirim(); });
            return;
        }
        kirim();
    });

    // ======================================================= Alat tindakan
    var alatTable = $('#alatTable').DataTable({
        data: [], autoWidth: false, pageLength: 25, order: [[0, 'asc']],
        dom: 'frt<"d-flex flex-wrap justify-content-between align-items-center mt-2"lip>', language: $.extend({ search: 'Cari:' }, DT_LANG),
        columns: [
            {
                data: 'nama', render: function (d, t, a) {
                    if (t !== 'display') return d;
                    return '<div class="hh-name">' + esc(d) + '</div><div class="small text-muted">' + esc([a.kode, a.ruangan, a.unit > 1 ? a.unit + ' unit' : null].filter(Boolean).join(' · ')) + '</div>'
                        + (a.paket.length ? '<div class="small hh-isi text-truncate" title="' + esc(a.paket.map(function (p) { return p.nama; }).join(', ')) + '">dipakai di ' + a.paket.length + ' paket: ' + esc(a.paket.map(function (p) { return p.nama; }).join(', ')) + '</div>' : '<div class="small text-muted">belum dipakai di paket</div>')
                        + (a.peringatan.length ? '<div class="small text-warning"><i class="fas fa-exclamation-triangle"></i> ' + esc(a.peringatan.join(', ')) + '</div>' : '');
                }
            },
            {
                data: 'harga', className: 'text-right text-nowrap', render: function (d, t, a) {
                    if (t !== 'display') return d || 0;
                    return d ? rupiah(d) + '<div class="small text-muted">' + (a.harga_sumber === 'manual' ? 'diisi manual' : 'pembelian ' + esc(a.pembelian_terakhir ? a.pembelian_terakhir.tanggal : '')) + '</div>' : '<span class="badge badge-warning">belum diisi</span>';
                }
            },
            { data: 'umur_pakai_tahun', className: 'text-right', render: function (d, t) { return t !== 'display' ? (d || 0) : (d ? d + ' th' : '<span class="badge badge-warning">-</span>'); } },
            { data: 'target_pakai_per_bulan', className: 'text-right', render: function (d, t) { return t !== 'display' ? (d || 0) : (d ? Number(d).toLocaleString('id-ID') + '×' : '<span class="badge badge-warning">-</span>'); } },
            { data: 'servis_12_bulan', className: 'text-right text-nowrap', render: function (d, t, a) { return t !== 'display' ? d : (d > 0 ? rupiah(d) + '<div class="small text-muted">' + a.servis_kali + '× servis</div>' : '<span class="text-muted">-</span>'); } },
            { data: 'per_pakai', className: 'text-right text-nowrap', render: function (d, t) { return t !== 'display' ? (d || 0) : (d !== null ? '<strong>' + rupiah(d) + '</strong>' : '<span class="text-muted">-</span>'); } },
            {
                data: 'terpakai_persen', render: function (d, t, a) {
                    if (t !== 'display') return d || 0;
                    if (d === null) return '<span class="text-muted small">-</span>';
                    var cls = d < 50 ? 'text-danger' : (d < 80 ? 'text-warning' : 'text-success');
                    return '<span class="' + cls + ' font-weight-bold">' + pct(d) + '</span><div class="small text-muted">' + Number(a.pakai_aktual_bulan).toLocaleString('id-ID') + '× / bln dari target ' + Number(a.target_pakai_per_bulan * a.unit).toLocaleString('id-ID') + '×</div>';
                }
            },
            {
                data: 'balik_modal', orderable: false, render: function (b, t) {
                    if (t !== 'display' || !b) return '';
                    if (b.modal === null) return '<span class="text-muted small">isi harga alat</span>';
                    if (!b.sejak) return '<span class="text-muted small">belum ada tanggal / pemakaian</span>';
                    var p = b.kembali_persen || 0;
                    return '<div class="hh-bar" title="' + pct(p) + '"><div style="width:' + p + '%"></div></div>'
                        + '<div class="small">' + (b.lunas ? '<span class="badge badge-success">sudah balik modal</span>'
                            : (b.kembali < 0 ? '<span class="text-danger">belum menutup servis (−' + rupiah(-b.kembali) + ')</span>' : 'kembali ' + rupiah(b.kembali) + ' (' + pct(p) + ')')) + '</div>'
                        + (b.lunas ? '' : '<div class="small text-muted">sisa ' + rupiah(b.sisa) + (b.kembali < 0 ? ' (modal + servis)' : '') + (b.bulan_lagi ? ' · ± ' + b.bulan_lagi + ' bln lagi' : (b.per_bulan !== null && b.per_bulan <= 0 ? ' · belum menghasilkan' : '')) + '</div>')
                        + '<div class="small text-muted">' + b.jumlah_tindakan + ' tindakan sejak ' + esc(b.sejak) + '</div>';
                }
            },
            { data: 'id', orderable: false, className: 'text-right text-nowrap', render: function (id) { return '<button type="button" class="btn btn-sm btn-outline-primary btn-alat-edit" data-id="' + id + '"><i class="fas fa-edit"></i> Edit</button>'; } }
        ]
    });
    var alatLoaded = false;
    function loadAlat() {
        return $.get(URLS.alatData).done(function (res) { alatLoaded = true; alatTable.clear().rows.add(res.data).draw(false); })
            .fail(function (xhr) { fail(xhr, 'Data alat tidak dapat dimuat.'); });
    }
    $('#tabAlat').on('shown.bs.tab', function () { if (!alatLoaded) loadAlat(); alatTable.columns.adjust(); });

    var alatEdit = null;
    function alatPreview() {
        var umur = num($('#alatUmur').val()), target = num($('#alatTarget').val());
        var harga = num($('#alatHarga').val()) || (alatEdit && alatEdit.pembelian_terakhir ? alatEdit.pembelian_terakhir.harga : null);
        var unit = alatEdit ? alatEdit.unit : 1, servis = alatEdit ? alatEdit.servis_12_bulan : 0;
        if (!umur || !target) { $('#alatPreview').text('-'); $('#alatPreviewNote').text('Isi umur pakai dan target pemakaian.'); return; }
        var susut = harga ? harga * unit / (umur * 12) : 0, sv = servis / 12, per = (susut + sv) / (target * unit);
        $('#alatPreview').text(rupiah(per));
        $('#alatPreviewNote').html('(' + (harga ? rupiah(harga) + (unit > 1 ? ' × ' + unit + ' unit' : '') + ' ÷ ' + (umur * 12) + ' bln = ' + rupiah(susut) : '<span class="text-warning">harga alat belum ada</span>')
            + ' + servis ' + rupiah(sv) + '/bln) ÷ ' + (target * unit) + '× pakai/bln');
    }
    function openAlat(info) {
        alatEdit = info;
        $('#alatId').val(info ? info.id : '');
        $('#alatPilihWrap').toggle(!info);
        $('#alatFormBody').toggle(!!info);
        $('#alatModalTitle').text(info ? 'Alat Tindakan: ' + info.nama : 'Tambah Alat Tindakan');
        $('#alatModalInfo').text(info ? [info.kode, info.ruangan, info.unit + ' unit (stok)'].filter(Boolean).join(' · ') : 'Pilih barang Inventory yang dipakai untuk tindakan.');
        if (info) {
            $('#alatAktif').prop('checked', true);
            $('#alatUmur').val(info.umur_pakai_tahun || '');
            $('#alatTarget').val(info.target_pakai_per_bulan || '');
            $('#alatHarga').val(info.harga_perolehan ? Math.round(info.harga_perolehan) : '');
            $('#alatTanggal').val(info.tanggal_perolehan ? String(info.tanggal_perolehan).substring(0, 10) : '');
            $('#alatHargaHint').html('Harga beli + ongkir + pasang + pelatihan. ' + (info.pembelian_terakhir ? 'Kosong = pembelian terakhir <strong>' + rupiah(info.pembelian_terakhir.harga) + '</strong> (' + esc(info.pembelian_terakhir.tanggal) + ').' : 'Belum ada data pembelian, jadi <strong>wajib diisi</strong>. Alat lama tanpa nota: harga alat sejenis sekarang.'));
            alatPreview();
        } else {
            $('#alatPilih').val(null).trigger('change');
        }
        $('#alatModal').modal('show');
    }
    $('#alatPilih').select2({
        dropdownParent: $('#alatModal'), width: '100%', placeholder: 'Ketik nama / kode barang...', minimumInputLength: 0,
        ajax: { url: URLS.alatCari, dataType: 'json', delay: 250, data: function (p) { return { q: p.term || '' }; }, processResults: function (d) { return d; } }
    }).on('select2:select', function (e) {
        $.get(URL_BASE + '/alat/' + e.params.data.id).done(function (info) {
            $('#alatModal').one('hidden.bs.modal', function () { openAlat(info); }).modal('hide');
        });
    });
    $('#alatTambah').on('click', function () { openAlat(null); });
    $('#alatTable').on('click', '.btn-alat-edit', function () {
        $.get(URL_BASE + '/alat/' + $(this).data('id')).done(openAlat).fail(function (xhr) { fail(xhr, 'Data alat tidak dapat dimuat.'); });
    });
    $('#alatUmur, #alatTarget, #alatHarga').on('input', alatPreview);
    $('#alatForm').on('submit', function (e) {
        e.preventDefault();
        var id = $('#alatId').val();
        if (!id) return;
        var aktif = $('#alatAktif').is(':checked');
        if (aktif && (!num($('#alatUmur').val()) || !num($('#alatTarget').val()))) {
            Swal.fire({ icon: 'warning', title: 'Periksa lagi', text: 'Umur pakai dan target pemakaian wajib diisi untuk alat tindakan.' });
            return;
        }
        ajaxJson(URL_BASE + '/alat/' + id, 'POST', {
            is_alat_tindakan: aktif, umur_pakai_tahun: num($('#alatUmur').val()), target_pakai_per_bulan: num($('#alatTarget').val()),
            harga_perolehan: num($('#alatHarga').val()), tanggal_perolehan: $('#alatTanggal').val() || null
        }).done(function (res) {
            $('#alatModal').modal('hide');
            loadAlat();
            loadPaket(true);
            Swal.fire({ icon: 'success', title: 'Tersimpan', text: res.message, timer: res.message.length > 40 ? undefined : 1600, showConfirmButton: res.message.length > 40 });
        }).fail(function (xhr) { fail(xhr, 'Gagal menyimpan.'); });
    });

    // ======================================================= Biaya Lain
    var lainTable = $('#lainTable').DataTable({
        data: [], autoWidth: false, pageLength: 25, order: [],
        dom: 'rt<"d-flex flex-wrap justify-content-between align-items-center mt-2"lip>', language: DT_LANG,
        columns: [
            { data: 'nama', render: function (d) { return '<span class="hh-name">' + esc(d) + '</span>'; } },
            { data: 'nominal', className: 'text-right text-nowrap', render: function (d, t) { return t !== 'display' ? d : (d > 0 ? rupiah(d) : '<span class="badge badge-warning">Rp 0 — belum diisi</span>'); } },
            { data: 'keterangan', render: function (d) { return '<span class="small">' + esc(d || '-') + '</span>'; } },
            { data: 'dipakai', className: 'text-right', render: function (d) { return d + ' paket'; } },
            { data: 'is_active', render: function (d) { return d ? '<span class="badge badge-success">aktif</span>' : '<span class="badge badge-secondary">nonaktif</span>'; } },
            {
                data: 'id', orderable: false, className: 'text-right text-nowrap', render: function (id, t, b) {
                    return '<button type="button" class="btn btn-sm btn-outline-primary btn-lain-edit" data-id="' + id + '"><i class="fas fa-edit"></i> Edit</button> '
                        + '<button type="button" class="btn btn-sm ' + (b.is_active ? 'btn-outline-secondary' : 'btn-outline-success') + ' btn-lain-toggle" data-id="' + id + '">' + (b.is_active ? '<i class="fas fa-ban"></i> Nonaktifkan' : '<i class="fas fa-check"></i> Aktifkan') + '</button>';
                }
            }
        ],
        createdRow: function (tr, b) { if (!b.is_active) $(tr).addClass('text-muted'); }
    });
    var lainRowsData = {}, lainLoaded = false;
    function loadLain() {
        return $.get(URLS.lainData).done(function (res) {
            lainLoaded = true;
            lainRowsData = {};
            res.data.forEach(function (b) { lainRowsData[b.id] = b; });
            lainTable.clear().rows.add(res.data).draw(false);
        }).fail(function (xhr) { fail(xhr, 'Data biaya lain tidak dapat dimuat.'); });
    }
    $('#tabLain').on('shown.bs.tab', function () { if (!lainLoaded) loadLain(); lainTable.columns.adjust(); });
    function openLain(b) {
        $('#lainId').val(b ? b.id : '');
        $('#lainModalTitle').text(b ? 'Edit Biaya Lain' : 'Tambah Biaya Lain');
        $('#lainNama').val(b ? b.nama : '');
        $('#lainNominal').val(b ? Math.round(b.nominal) : '');
        $('#lainKet').val(b ? (b.keterangan || '') : '');
        $('#lainBulan, #lainJumlah').val('');
        $('#lainTerapkanWrap').toggle(!!(b && b.dipakai));
        $('#lainTerapkan').prop('checked', true);
        $('#lainModal').modal('show');
    }
    $('#lainTambah').on('click', function () { openLain(null); });
    $('#lainTable').on('click', '.btn-lain-edit', function () { openLain(lainRowsData[$(this).data('id')]); });
    $('#lainHitung').on('click', function () {
        var bulan = num($('#lainBulan').val()), n = num($('#lainJumlah').val());
        if (!(bulan >= 0) || !(n > 0)) { Swal.fire({ icon: 'info', text: 'Isi biaya sebulan dan jumlah tindakan sebulan.' }); return; }
        $('#lainNominal').val(Math.round(bulan / n));
    });
    $('#lainForm').on('submit', function (e) {
        e.preventDefault();
        var id = $('#lainId').val();
        var payload = { nama: $.trim($('#lainNama').val()), nominal: num($('#lainNominal').val()), keterangan: $.trim($('#lainKet').val()) || null };
        if (id) payload.terapkan_ke_paket = $('#lainTerapkanWrap').is(':visible') && $('#lainTerapkan').is(':checked');
        ajaxJson(id ? URL_BASE + '/biaya-lain/' + id : URLS.lainStore, id ? 'PUT' : 'POST', payload).done(function (res) {
            $('#lainModal').modal('hide');
            loadLain();
            loadPaket(true);
            Swal.fire({ icon: 'success', title: 'Tersimpan', text: res.message, timer: 1800, showConfirmButton: false });
        }).fail(function (xhr) { fail(xhr, 'Gagal menyimpan.'); });
    });
    $('#lainTable').on('click', '.btn-lain-toggle', function () {
        ajaxJson(URL_BASE + '/biaya-lain/' + $(this).data('id') + '/toggle', 'POST').done(function (res) {
            loadLain();
            Swal.fire({ icon: 'success', text: res.message, timer: 2200, showConfirmButton: false });
        }).fail(function (xhr) { fail(xhr, 'Gagal mengubah status.'); });
    });

    // ======================================================= Start
    loadPaket().done(function () {
        // ?paket=<tindakan id> opens Hitung Harga for that paket (link from Harga Paket Tindakan)
        var params = new URLSearchParams(window.location.search);
        if (params.get('paket')) {
            openHitung(params.get('paket'));
            window.history.replaceState({}, '', window.location.pathname);
        }
    });
});
</script>
@endsection
