@extends('layouts.admin.app')

@section('title', 'Klinik Setting')

@section('navbar')
    @include('layouts.admin.navbar')
@endsection

@section('styles')
<link rel="stylesheet" href="{{ asset('vendor/cropperjs/cropper.min.css') }}">
<style>
    /* crop popup opens on top of the klinik / kop modal */
    #kopCropModal { z-index: 1065; }
    #kopCropStage { height: 360px; background: #f1f3f5; }
    #kopCropStage img { display: block; max-width: 100%; }
    .kop-a4 { width: 130px; height: 184px; border: 1px solid #ced4da; background: #fff; position: relative; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,.12); }
    .kop-a4 .kop-a4-top { position: absolute; top: 0; left: 0; width: 100%; }
    .kop-a4 .kop-a4-bottom { position: absolute; bottom: 0; left: 0; width: 100%; }
    .kop-a4 .kop-a4-logo { position: absolute; top: 8px; left: 9px; max-width: 46px; max-height: 22px; }
    .kop-a4 .kop-a4-ikon { position: absolute; top: 8px; left: 9px; width: 14px; height: 14px; }
    .kop-a4 .kop-a4-lines { position: absolute; left: 9px; right: 9px; top: 46px; }
    .kop-a4 .kop-a4-lines div { height: 3px; background: #e9ecef; margin-bottom: 5px; border-radius: 2px; }
    #kopCropInfo.ok { color: #28a745; }
    #kopCropInfo.bad { color: #dc3545; }
</style>
@endsection

@section('content')
<div class="container">
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div>
                    <h3 class="card-title mb-0">Klinik Setting</h3>
                    <small class="text-muted">Data klinik dan konfigurasi integrasi SatuSehat.</small>
                </div>
                <div>
                    <button type="button" class="btn btn-outline-primary mr-1" id="btnKopGlobal"><i class="fas fa-file-image mr-1"></i>Kop Perusahaan & Lab</button>
                    <button type="button" class="btn btn-primary" id="btnAddKlinik">Tambah Klinik</button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-hover table-bordered" id="klinik-table" style="width:100%">
                    <thead class="thead-light">
                        <tr>
                            <th style="width:140px">Logo</th>
                            <th>Nama Klinik</th>
                            <th style="width:100px">Cut Off</th>
                            <th style="width:140px">Warna</th>
                            <th style="width:200px">Layanan</th>
                            <th style="width:200px">SatuSehat</th>
                            <th style="width:200px">Action</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    @if($unlinkedConfigs->isNotEmpty())
    <div class="card shadow-sm border-warning">
        <div class="card-body">
            <h5 class="card-title mb-1">Konfigurasi SatuSehat tanpa klinik</h5>
            <p class="text-muted small mb-3">Konfigurasi ini tidak terhubung ke klinik mana pun (atau merupakan konfigurasi kedua untuk klinik yang sama), sehingga tidak dipakai oleh integrasi. Hubungkan ke klinik yang belum punya konfigurasi, atau hapus jika tidak diperlukan.</p>
            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>Klinik saat ini</th>
                            <th>Organization ID</th>
                            <th>Client ID</th>
                            <th style="width:340px">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($unlinkedConfigs as $config)
                        <tr>
                            <td>{{ $config->id }}</td>
                            <td>{{ optional($config->klinik)->nama ?? '-' }} @if($config->klinik)<span class="badge badge-soft-warning">duplikat</span>@endif</td>
                            <td>{{ $config->organization_id ?: '-' }}</td>
                            <td class="text-truncate" style="max-width:160px">{{ $config->client_id ?: '-' }}</td>
                            <td>
                                <div class="d-flex">
                                    @if($kliniksWithoutConfig->isNotEmpty())
                                    <select class="form-control form-control-sm mr-1 link-klinik-select" style="max-width:180px">
                                        @foreach($kliniksWithoutConfig as $k)
                                            <option value="{{ $k->id }}">{{ $k->nama }}</option>
                                        @endforeach
                                    </select>
                                    <button type="button" class="btn btn-sm btn-primary mr-1 btn-link-config" data-config-id="{{ $config->id }}">Hubungkan</button>
                                    @endif
                                    <button type="button" class="btn btn-sm btn-danger btn-delete-config" data-config-id="{{ $config->id }}">Hapus</button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    @if($unlinkedLocations->isNotEmpty())
    <div class="card shadow-sm border-warning">
        <div class="card-body">
            <h5 class="card-title mb-1">Lokasi SatuSehat tanpa klinik</h5>
            <p class="text-muted small mb-3">Lokasi ini tidak terhubung ke klinik mana pun (atau merupakan lokasi kedua untuk klinik yang sama), sehingga tidak dipakai oleh integrasi. Hubungkan ke klinik yang belum punya lokasi, atau hapus jika tidak diperlukan.</p>
            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>Klinik saat ini</th>
                            <th>Nama</th>
                            <th>Location ID</th>
                            <th style="width:340px">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($unlinkedLocations as $location)
                        <tr>
                            <td>{{ $location->id }}</td>
                            <td>{{ optional($location->klinik)->nama ?? '-' }} @if($location->klinik)<span class="badge badge-soft-warning">duplikat</span>@endif</td>
                            <td>{{ $location->name ?: '-' }}</td>
                            <td class="text-truncate" style="max-width:200px">{{ $location->location_id ?: '-' }}</td>
                            <td>
                                <div class="d-flex">
                                    @if($kliniksWithoutLocation->isNotEmpty())
                                    <select class="form-control form-control-sm mr-1 link-klinik-select" style="max-width:180px">
                                        @foreach($kliniksWithoutLocation as $k)
                                            <option value="{{ $k->id }}">{{ $k->nama }}</option>
                                        @endforeach
                                    </select>
                                    <button type="button" class="btn btn-sm btn-primary mr-1 btn-link-location" data-location-id="{{ $location->id }}">Hubungkan</button>
                                    @endif
                                    <button type="button" class="btn btn-sm btn-danger btn-delete-location" data-location-id="{{ $location->id }}">Hapus</button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>

<div class="modal fade" id="klinikModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form id="klinikForm" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="klinikModalLabel">Tambah Klinik</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="klinikId" name="id">

                    <ul class="nav nav-tabs mb-3" role="tablist">
                        <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#tabUmum" role="tab">Umum</a></li>
                        <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tabLayanan" role="tab">Layanan</a></li>
                        <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tabInvoice" role="tab">Invoice</a></li>
                        <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tabSatusehat" role="tab">SatuSehat <span id="ssTabBadge"></span></a></li>
                        <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tabKop" role="tab">Kop & Logo PDF</a></li>
                    </ul>

                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="tabUmum" role="tabpanel">
                            <div class="form-group">
                                <label for="klinikNama">Nama Klinik</label>
                                <input type="text" class="form-control" id="klinikNama" name="nama" maxlength="255" required>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="klinikCutoff">Jam Cut Off Laporan</label>
                                    <input type="time" class="form-control" id="klinikCutoff" name="report_cutoff_time" value="00:00">
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="klinikColorText">Warna</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <input type="color" class="form-control p-1" id="klinikColorPicker" value="#007bff" style="width:48px; height:38px;">
                                        </div>
                                        <input type="text" class="form-control" id="klinikColorText" name="color" placeholder="#007bff" maxlength="7">
                                    </div>
                                    <small class="text-muted">Kosongkan jika tidak ada warna.</small>
                                </div>
                            </div>
                            <div class="form-group mb-0">
                                <label for="klinikLogo">Logo</label>
                                <div id="klinikLogoPreviewWrap" class="mb-2" style="display:none;">
                                    <img id="klinikLogoPreview" src="" alt="Logo" style="max-height:80px; max-width:200px;" class="border rounded p-1">
                                    <div class="custom-control custom-checkbox mt-1" id="klinikRemoveLogoWrap">
                                        <input type="checkbox" class="custom-control-input" id="klinikRemoveLogo" name="remove_logo" value="1">
                                        <label class="custom-control-label" for="klinikRemoveLogo">Hapus logo</label>
                                    </div>
                                </div>
                                <input type="file" class="form-control-file" id="klinikLogo" name="logo" accept="image/png,image/jpeg,image/webp">
                                <small class="text-muted">Format JPG, PNG, atau WEBP. Maksimal 2 MB.</small>
                            </div>
                        </div>

                        {{-- Jenis kunjungan this klinik provides (erm_klinik_layanan); unchecked = cannot be registered here --}}
                        <div class="tab-pane fade" id="tabLayanan" role="tabpanel">
                            <input type="hidden" name="layanan_dikirim" value="1">
                            <div class="alert alert-secondary py-2 small mb-3">
                                Centang layanan (jenis kunjungan) yang tersedia di klinik ini. Layanan yang tidak dicentang
                                tidak bisa dipilih saat mendaftarkan kunjungan ke klinik ini. Kunjungan yang sudah ada tidak berubah.
                            </div>
                            @if (!\App\Models\ERM\KlinikLayanan::tracked())
                                <div class="alert alert-warning py-2 small mb-3">Tabel layanan belum ada (migration belum dijalankan): semua layanan dianggap tersedia dan pilihan di sini belum disimpan.</div>
                            @endif
                            <div class="row">
                                @foreach (\App\Models\ERM\KlinikLayanan::forKlinik(null) as $layanan)
                                    <div class="col-md-6">
                                        <div class="custom-control custom-checkbox mb-2">
                                            <input type="checkbox" class="custom-control-input klinik-layanan" id="klinikLayanan{{ $layanan['jenis'] }}" name="layanan[]" value="{{ $layanan['jenis'] }}" checked>
                                            <label class="custom-control-label" for="klinikLayanan{{ $layanan['jenis'] }}">{{ $layanan['label'] }}</label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- Footer of the invoice (finance/invoice/pdf); empty fields keep the invoice's built-in text --}}
                        <div class="tab-pane fade" id="tabInvoice" role="tabpanel">
                            <div class="alert alert-secondary py-2 small mb-3">
                                Tampil di bagian bawah invoice. Kosongkan untuk memakai teks bawaan invoice.
                            </div>
                            <h6 class="text-muted mb-2">Rekening Pembayaran</h6>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label for="klinikBankName">Bank</label>
                                    <input type="text" class="form-control" id="klinikBankName" name="bank_name" maxlength="100" placeholder="BRI">
                                </div>
                                <div class="form-group col-md-8">
                                    <label for="klinikBankAccountNumber">No. Rekening</label>
                                    <input type="text" class="form-control" id="klinikBankAccountNumber" name="bank_account_number" maxlength="50">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="klinikBankAccountName">Atas Nama</label>
                                <input type="text" class="form-control" id="klinikBankAccountName" name="bank_account_name" maxlength="255" placeholder="CV ...">
                            </div>
                            <h6 class="text-muted mb-2 mt-3">Kontak Klinik</h6>
                            <div class="form-group">
                                <label for="klinikAlamat">Alamat</label>
                                <textarea class="form-control" id="klinikAlamat" name="alamat" rows="2" maxlength="500"></textarea>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6 mb-0">
                                    <label for="klinikTelp">Telepon</label>
                                    <input type="text" class="form-control" id="klinikTelp" name="telp" maxlength="100">
                                </div>
                                <div class="form-group col-md-6 mb-0">
                                    <label for="klinikWebsite">Website</label>
                                    <input type="text" class="form-control" id="klinikWebsite" name="website" maxlength="255" placeholder="www.contoh.id">
                                </div>
                            </div>
                        </div>

                        {{-- Letterhead images of this klinik's PDFs (pdf_kop, Admin\PdfKopController); saved per upload --}}
                        <div class="tab-pane fade" id="tabKop" role="tabpanel">
                            <div id="kopKlinikBaru" class="alert alert-secondary py-2 small mb-0" style="display:none;">Simpan klinik terlebih dahulu, lalu buka Edit untuk mengatur kop & logo PDF.</div>
                            <div id="kopKlinikBox"></div>
                        </div>

                        <div class="tab-pane fade" id="tabSatusehat" role="tabpanel">
                            <div id="ssStatus" class="alert alert-secondary py-2 small mb-3"></div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="ssOrganizationId">Organization ID</label>
                                    <input type="text" class="form-control" id="ssOrganizationId" name="ss_organization_id" maxlength="255">
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="ssClientId">Client ID</label>
                                    <input type="text" class="form-control" id="ssClientId" name="ss_client_id" maxlength="255">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="ssClientSecret">Client Secret</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="ssClientSecret" name="ss_client_secret" maxlength="255" autocomplete="new-password">
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-outline-secondary" id="ssToggleSecret" title="Tampilkan"><i class="fas fa-eye"></i></button>
                                    </div>
                                </div>
                            </div>
                            <a class="small" data-toggle="collapse" href="#ssUrls">Endpoint URL (lanjutan)</a>
                            <div class="collapse mt-2" id="ssUrls">
                                <div class="form-group">
                                    <label for="ssBaseUrl" class="small">Base URL</label>
                                    <input type="text" class="form-control form-control-sm" id="ssBaseUrl" name="ss_base_url" maxlength="255">
                                </div>
                                <div class="form-group">
                                    <label for="ssAuthUrl" class="small">Auth URL</label>
                                    <input type="text" class="form-control form-control-sm" id="ssAuthUrl" name="ss_auth_url" maxlength="255">
                                </div>
                                <div class="form-group mb-0">
                                    <label for="ssConsentUrl" class="small">Consent URL</label>
                                    <input type="text" class="form-control form-control-sm" id="ssConsentUrl" name="ss_consent_url" maxlength="255">
                                </div>
                            </div>
                            <div id="ssActions" class="mt-3" style="display:none;">
                                <button type="button" class="btn btn-sm btn-info" id="ssBtnToken">Ambil Token</button>
                                <button type="button" class="btn btn-sm btn-outline-danger float-right" id="ssBtnDelete">Hapus konfigurasi SatuSehat</button>
                            </div>

                            <div class="mt-4 pt-3 border-top">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="m-0"><i class="fas fa-map-marker-alt mr-1"></i> Lokasi SatuSehat</h6>
                                    <button type="button" class="btn btn-sm btn-outline-danger" id="locBtnDelete" style="display:none;">Hapus lokasi</button>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="locLocationId" class="small">Location ID (dari SatuSehat)</label>
                                        <input type="text" class="form-control form-control-sm" id="locLocationId" name="loc_location_id">
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="locName" class="small">Nama Lokasi</label>
                                        <input type="text" class="form-control form-control-sm" id="locName" name="loc_name" placeholder="mis. Ruang Konsultasi">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="locIdentifier" class="small">Identifier Value</label>
                                    <input type="text" class="form-control form-control-sm" id="locIdentifier" name="loc_identifier_value">
                                </div>
                                <a class="small" data-toggle="collapse" href="#locAddress">Alamat & koordinat</a>
                                <div class="collapse mt-2" id="locAddress">
                                    <div class="form-group">
                                        <label for="locLine" class="small">Alamat</label>
                                        <input type="text" class="form-control form-control-sm" id="locLine" name="loc_line">
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group col-md-4"><label class="small" for="locProvince">Provinsi</label><input type="text" class="form-control form-control-sm" id="locProvince" name="loc_province"></div>
                                        <div class="form-group col-md-4"><label class="small" for="locCity">Kota</label><input type="text" class="form-control form-control-sm" id="locCity" name="loc_city"></div>
                                        <div class="form-group col-md-4"><label class="small" for="locDistrict">Kecamatan</label><input type="text" class="form-control form-control-sm" id="locDistrict" name="loc_district"></div>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group col-md-4"><label class="small" for="locVillage">Kelurahan</label><input type="text" class="form-control form-control-sm" id="locVillage" name="loc_village"></div>
                                        <div class="form-group col-md-2"><label class="small" for="locRt">RT</label><input type="text" class="form-control form-control-sm" id="locRt" name="loc_rt"></div>
                                        <div class="form-group col-md-2"><label class="small" for="locRw">RW</label><input type="text" class="form-control form-control-sm" id="locRw" name="loc_rw"></div>
                                        <div class="form-group col-md-4"><label class="small" for="locPostalCode">Kode Pos</label><input type="text" class="form-control form-control-sm" id="locPostalCode" name="loc_postal_code"></div>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group col-md-6"><label class="small" for="locLatitude">Latitude</label><input type="text" class="form-control form-control-sm" id="locLatitude" name="loc_latitude"></div>
                                        <div class="form-group col-md-6"><label class="small" for="locLongitude">Longitude</label><input type="text" class="form-control form-control-sm" id="locLongitude" name="loc_longitude"></div>
                                    </div>
                                    <div class="form-group mb-0">
                                        <label class="small" for="locDescription">Deskripsi</label>
                                        <textarea class="form-control form-control-sm" id="locDescription" name="loc_description" rows="2"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btnSaveKlinik">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Adjust a letterhead image before upload (Cropper.js): crop within the image's ratio limits or pad it with white --}}
<div class="modal fade" id="kopCropModal" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Sesuaikan Gambar: <span id="kopCropTitle"></span></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-lg-9">
                        <div id="kopCropStage"><img id="kopCropImg" src="" alt=""></div>
                    </div>
                    <div class="col-lg-3 mt-3 mt-lg-0">
                        <label class="small font-weight-bold mb-1">Mode</label>
                        <div class="custom-control custom-radio">
                            <input type="radio" class="custom-control-input" id="kopModeCrop" name="kopCropMode" value="crop" checked>
                            <label class="custom-control-label" for="kopModeCrop">Crop (potong)</label>
                        </div>
                        <div class="custom-control custom-radio mb-2">
                            <input type="radio" class="custom-control-input" id="kopModePad" name="kopCropMode" value="pad">
                            <label class="custom-control-label" for="kopModePad">Tambah latar putih</label>
                            <div class="small text-muted">Tidak memotong; ruang putih ditambah sampai rasionya pas.</div>
                        </div>
                        <div class="custom-control custom-checkbox mb-2" id="kopLockWrap">
                            <input type="checkbox" class="custom-control-input" id="kopLockStandar" checked>
                            <label class="custom-control-label" for="kopLockStandar">Kunci rasio standar <span id="kopStandarText"></span></label>
                        </div>
                        <div class="btn-group btn-group-sm mb-3" role="group">
                            <button type="button" class="btn btn-outline-secondary" id="kopCropFit" title="Pilih area terbesar yang sesuai"><i class="fas fa-expand mr-1"></i>Pas otomatis</button>
                            <button type="button" class="btn btn-outline-secondary" id="kopCropRotate" title="Putar 90°"><i class="fas fa-redo"></i></button>
                        </div>
                        <div class="small text-muted mb-1">Aturan: <span id="kopCropAturan"></span></div>
                        <div id="kopCropInfo" class="small font-weight-bold mb-3"></div>
                        <label class="small font-weight-bold mb-1">Pratinjau di kertas A4</label>
                        <div class="kop-a4" id="kopA4">
                            <img class="kop-a4-top" id="kopA4Top" alt="">
                            <img class="kop-a4-logo" id="kopA4Logo" alt="">
                            <img class="kop-a4-ikon" id="kopA4Ikon" alt="">
                            <div class="kop-a4-lines"><div></div><div></div><div style="width:70%"></div><div></div><div style="width:85%"></div><div></div><div style="width:60%"></div></div>
                            <img class="kop-a4-bottom" id="kopA4Bottom" alt="">
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="kopCropSave"><i class="fas fa-check mr-1"></i>Simpan & Pakai</button>
            </div>
        </div>
    </div>
</div>

{{-- Company + lab letterheads (not tied to one klinik) --}}
<div class="modal fade" id="kopGlobalModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Kop Perusahaan & Lab</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <ul class="nav nav-tabs mb-3" role="tablist">
                    <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#tabKopPerusahaan" role="tab">Perusahaan (Belova Corp)</a></li>
                    <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tabKopLab" role="tab">Laboratorium</a></li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="tabKopPerusahaan" role="tabpanel">
                        <form id="kopPerusahaanTeks" class="mb-3">
                            <div class="form-row">
                                <div class="form-group col-md-5 mb-2">
                                    <label class="small mb-1" for="kopPerusahaanNama">Nama perusahaan</label>
                                    <input type="text" class="form-control form-control-sm" id="kopPerusahaanNama" name="nama" maxlength="255">
                                </div>
                                <div class="form-group col-md-7 mb-2">
                                    <label class="small mb-1" for="kopPerusahaanAlamat">Alamat & kontak <span class="text-muted">(satu baris per baris)</span></label>
                                    <textarea class="form-control form-control-sm" id="kopPerusahaanAlamat" name="alamat" rows="2" maxlength="1000"></textarea>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-sm btn-primary">Simpan nama & alamat</button>
                            <small class="text-muted ml-2">Dipakai sebagai teks kop bila perusahaan belum punya banner.</small>
                        </form>
                        <div id="kopPerusahaanBox"></div>
                    </div>
                    <div class="tab-pane fade" id="tabKopLab" role="tabpanel">
                        <div id="kopLabBox"></div>
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

@section('scripts')
<script src="{{ asset('vendor/cropperjs/cropper.min.js') }}"></script>
<script>
$(document).ready(function() {
    var baseUrl = '{{ url('/admin/klinik-settings') }}';
    var ssBaseUrl = '{{ url('/satusehat/clinics') }}';
    var ssDefaults = {
        base_url: 'https://api-satusehat.kemkes.go.id/fhir-r4/v1',
        auth_url: 'https://api-satusehat.kemkes.go.id/oauth2/v1',
        consent_url: 'https://api-satusehat.kemkes.go.id/consent/v1'
    };
    var currentConfigId = null;
    var currentLocationId = null;
    var locBaseUrl = '{{ url('/satusehat/locations') }}';
    var locFields = {
        location_id: '#locLocationId', name: '#locName', identifier_value: '#locIdentifier', line: '#locLine',
        province: '#locProvince', city: '#locCity', district: '#locDistrict', village: '#locVillage',
        rt: '#locRt', rw: '#locRw', postal_code: '#locPostalCode', latitude: '#locLatitude',
        longitude: '#locLongitude', description: '#locDescription'
    };

    var table = $('#klinik-table').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: '{{ route('admin.klinik_settings.index') }}',
        order: [[1, 'asc']],
        columns: [
            { data: 'logo_html', name: 'logo', orderable: false, searchable: false },
            { data: 'nama', name: 'nama' },
            { data: 'report_cutoff_time', name: 'report_cutoff_time', searchable: false },
            { data: 'color_html', name: 'color', orderable: false, searchable: false },
            { data: 'layanan_html', name: 'layanan', orderable: false, searchable: false },
            { data: 'satusehat_html', name: 'satusehat', orderable: false, searchable: false },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ]
    });

    function errorMessage(xhr, fallback) {
        var res = xhr.responseJSON || {};
        if (res.errors) return Object.values(res.errors).map(function(m) { return m[0]; }).join('<br>');
        return res.message || fallback || 'Terjadi kesalahan.';
    }

    function setSatusehat(ss) {
        currentConfigId = ss ? ss.id : null;
        $('#ssOrganizationId').val(ss ? ss.organization_id : '');
        $('#ssClientId').val(ss ? ss.client_id : '');
        $('#ssClientSecret').val(ss ? ss.client_secret : '').attr('type', 'password');
        $('#ssBaseUrl').val(ss && ss.base_url ? ss.base_url : ssDefaults.base_url);
        $('#ssAuthUrl').val(ss && ss.auth_url ? ss.auth_url : ssDefaults.auth_url);
        $('#ssConsentUrl').val(ss && ss.consent_url ? ss.consent_url : ssDefaults.consent_url);
        $('#ssActions').toggle(!!ss);
        $('#ssTabBadge').html(ss ? '<i class="fas fa-check-circle text-success"></i>' : '');

        var status;
        if (!ss) {
            status = 'Belum dikonfigurasi. Isi Organization ID, Client ID dan Client Secret untuk mengaktifkan integrasi SatuSehat.';
            $('#ssStatus').attr('class', 'alert alert-secondary py-2 small mb-3');
        } else if (ss.token_valid) {
            status = 'Terhubung. Token aktif sampai ' + ss.token_expires_at + '.';
            $('#ssStatus').attr('class', 'alert alert-success py-2 small mb-3');
        } else {
            status = 'Terhubung. ' + (ss.has_token ? 'Token sudah kedaluwarsa' : 'Token belum diambil') + ', klik "Ambil Token".';
            $('#ssStatus').attr('class', 'alert alert-warning py-2 small mb-3');
        }
        $('#ssStatus').text(status);
    }

    function setLocation(loc) {
        currentLocationId = loc ? loc.id : null;
        $.each(locFields, function(field, selector) {
            $(selector).val(loc && loc[field] != null ? loc[field] : '');
        });
        $('#locBtnDelete').toggle(!!loc);
    }

    function resetForm() {
        $('#klinikForm')[0].reset();
        $('#klinikId').val('');
        $('#klinikCutoff').val('00:00');
        $('#klinikColorText').val('');
        $('#klinikColorPicker').val('#007bff');
        $('#klinikLogoPreview').attr('src', '');
        $('#klinikLogoPreviewWrap').hide();
        $('#klinikRemoveLogoWrap').show();
        $('#ssUrls').collapse('hide');
        $('#locAddress').collapse('hide');
        $('#klinikModal .nav-tabs a:first').tab('show');
        $('.klinik-layanan').prop('checked', true); // new klinik: every layanan
        setSatusehat(null);
        setLocation(null);
        $('#kopKlinikBox').empty();
        $('#kopKlinikBaru').show();
    }

    function openKlinik(id, tab) {
        $.get(baseUrl + '/' + id, function(data) {
            resetForm();
            $('#klinikId').val(data.id);
            $('#klinikNama').val(data.nama);
            $('#klinikCutoff').val(data.report_cutoff_time || '00:00');
            $.each(['bank_name', 'bank_account_number', 'bank_account_name', 'alamat', 'telp', 'website'], function(_, field) {
                $('#klinikForm [name="' + field + '"]').val(data[field] || '');
            });
            if (data.color) {
                $('#klinikColorText').val(data.color);
                $('#klinikColorPicker').val(data.color);
            }
            if (data.logo_url) {
                $('#klinikLogoPreview').attr('src', data.logo_url);
                $('#klinikLogoPreviewWrap').show();
            }
            $.each(data.layanan || [], function(_, l) {
                $('#klinikLayanan' + l.jenis).prop('checked', !!l.aktif);
            });
            setSatusehat(data.satusehat);
            setLocation(data.location);
            $('#kopKlinikBaru').hide();
            loadKop($('#kopKlinikBox'), { lingkup: 'klinik', klinik_id: data.id });
            if (tab) $('#klinikModal .nav-tabs a[href="' + tab + '"]').tab('show');
            $('#klinikModalLabel').text('Edit Klinik');
            $('#klinikModal').modal('show');
        });
    }

    function requestToken(configId, onDone) {
        Swal.fire({ title: 'Mengambil token...', allowOutsideClick: false, didOpen: function() { Swal.showLoading(); } });
        $.post(ssBaseUrl + '/' + configId + '/token', {}, function(res) {
            if (res.ok) {
                Swal.fire({ icon: 'success', title: res.message || 'Token diterima' });
                table.ajax.reload(null, false);
                if (onDone) onDone();
            } else {
                Swal.fire({ icon: 'error', title: 'Gagal', text: res.message || 'Gagal mengambil token' });
            }
        }).fail(function(xhr) {
            Swal.fire({ icon: 'error', title: 'Gagal', html: errorMessage(xhr, 'Gagal mengambil token') });
        });
    }

    // keep color picker and hex text in sync
    $('#klinikColorPicker').on('input change', function() {
        $('#klinikColorText').val($(this).val());
    });
    $('#klinikColorText').on('input', function() {
        var v = $.trim($(this).val());
        if (/^#[0-9A-Fa-f]{6}$/.test(v)) $('#klinikColorPicker').val(v);
    });

    // preview a newly chosen logo
    $('#klinikLogo').on('change', function() {
        var file = this.files && this.files[0];
        if (!file) return;
        var reader = new FileReader();
        reader.onload = function(e) {
            $('#klinikLogoPreview').attr('src', e.target.result);
            $('#klinikLogoPreviewWrap').show();
            $('#klinikRemoveLogoWrap').hide();
            $('#klinikRemoveLogo').prop('checked', false);
        };
        reader.readAsDataURL(file);
    });

    $('#ssToggleSecret').on('click', function() {
        var $input = $('#ssClientSecret');
        $input.attr('type', $input.attr('type') === 'password' ? 'text' : 'password');
    });

    // ---- Kop & Logo PDF (per klinik, perusahaan, lab): every upload is checked + saved at once ----
    var kopUrls = {
        show: '{{ route('admin.pdf_kop.show') }}',
        upload: '{{ route('admin.pdf_kop.upload') }}',
        hapus: '{{ route('admin.pdf_kop.hapus') }}',
        teks: '{{ route('admin.pdf_kop.teks') }}',
        template: '{{ url('/admin/pdf-kop/template') }}'
    };

    function esc(s) { return $('<div>').text(s == null ? '' : String(s)).html(); }

    function loadKop($box, target) {
        $box.data('kopTarget', target).html('<div class="text-muted small py-3"><i class="fas fa-spinner fa-spin mr-1"></i>Memuat kop...</div>');
        $.get(kopUrls.show, target, function(data) {
            if (target.lingkup === 'perusahaan') {
                $('#kopPerusahaanNama').val(data.nama).attr('placeholder', data.nama_bawaan);
                $('#kopPerusahaanAlamat').val(data.alamat);
            }
            var html = '';
            if (!data.tabel_ada) {
                html += '<div class="alert alert-warning py-2 small">Tabel <code>pdf_kop</code> belum ada (migration belum dijalankan): semua PDF memakai gambar bawaan dan upload belum bisa disimpan.</div>';
            }
            html += '<div class="d-flex align-items-center justify-content-between mb-2">'
                + '<small class="text-muted">Upload langsung tersimpan dan dipakai di PDF berikutnya. Gambar yang kosong memakai gambar bawaan.</small>'
                + '<a class="btn btn-sm btn-outline-info ml-2 text-nowrap" target="_blank" href="' + esc(data.contoh_url) + '"><i class="fas fa-file-pdf mr-1"></i>Lihat contoh PDF</a></div>';
            $.each(data.gambar, function(_, g) {
                var src = g.custom_url || g.bawaan_url;
                var badge = g.custom_url
                    ? '<span class="badge badge-success">Custom</span>'
                    : (g.bawaan_url ? '<span class="badge badge-secondary">Bawaan</span>' : '<span class="badge badge-light border">Belum ada</span>');
                var tall = (g.key === 'header' || g.key === 'footer');
                html += '<div class="border rounded p-2 mb-2 kop-item" data-gambar="' + g.key + '">'
                    + '<div class="d-flex justify-content-between align-items-start">'
                    +   '<div><strong>' + esc(g.label) + '</strong> ' + badge
                    +     '<div class="small text-muted">Dipakai di: ' + esc(g.dipakai_di) + '</div>'
                    +     '<div class="small text-muted"><i class="fas fa-ruler-combined mr-1"></i>' + esc(g.aturan) + '</div></div>'
                    +   '<div class="text-nowrap ml-2">'
                    +     '<input type="file" class="d-none kop-file" accept="image/png,image/jpeg">'
                    +     '<button type="button" class="btn btn-sm btn-primary kop-upload"><i class="fas fa-upload mr-1"></i>Upload</button>'
                    +     (g.custom_url ? ' <button type="button" class="btn btn-sm btn-outline-danger kop-hapus"><i class="fas fa-undo mr-1"></i>Kembalikan bawaan</button>' : '')
                    +     (g.template ? ' <a class="btn btn-sm btn-outline-secondary" href="' + kopUrls.template + '/' + g.key + '" title="Template 2000×381 dengan area aman"><i class="fas fa-download mr-1"></i>Template</a>' : '')
                    +   '</div></div>'
                    + (src ? '<div class="mt-2 p-1 bg-light border rounded text-center"><img src="' + esc(src) + '" alt="" style="max-width:100%; max-height:' + (tall ? '90' : '70') + 'px;"></div>' : '')
                    + '</div>';
            });
            $box.html(html);
            var byKey = {};
            $.each(data.gambar, function(_, g) { byKey[g.key] = g; });
            $box.data('kopGambar', byKey);
        }).fail(function(xhr) {
            $box.html('<div class="alert alert-danger py-2 small">' + errorMessage(xhr, 'Gagal memuat kop.') + '</div>');
        });
    }

    $(document).on('click', '.kop-upload', function() {
        $(this).closest('.kop-item').find('.kop-file').trigger('click');
    });

    // A chosen file first opens the crop popup; only the adjusted result is uploaded
    $(document).on('change', '.kop-file', function() {
        var file = this.files && this.files[0];
        this.value = '';
        if (!file) return;
        if (['image/png', 'image/jpeg'].indexOf(file.type) === -1) {
            Swal.fire({ icon: 'error', title: 'Format tidak didukung', text: 'Gunakan PNG atau JPG (bukan SVG, WebP, atau PDF).' });
            return;
        }
        var $item = $(this).closest('.kop-item');
        var $box = $item.parent();
        openCrop($box, $box.data('kopGambar')[$item.data('gambar')], file);
    });

    function uploadKop($box, gambar, blob, fileName, onDone) {
        var fd = new FormData();
        $.each($box.data('kopTarget'), function(k, v) { fd.append(k, v); });
        fd.append('gambar', gambar);
        fd.append('file', blob, fileName);
        Swal.fire({ title: 'Memeriksa & memproses gambar...', allowOutsideClick: false, didOpen: function() { Swal.showLoading(); } });
        $.ajax({ url: kopUrls.upload, type: 'POST', data: fd, processData: false, contentType: false })
            .done(function(res) {
                Swal.fire({ icon: 'success', title: 'Tersimpan', text: res.message, timer: 1800, showConfirmButton: false });
                if (onDone) onDone();
                loadKop($box, $box.data('kopTarget'));
            })
            .fail(function(xhr) {
                Swal.fire({ icon: 'error', title: 'Gambar ditolak', html: errorMessage(xhr, 'Upload gagal.') });
            });
    }

    // ---- crop popup (Cropper.js): crop box kept inside the image's ratio limits, or pad with white ----
    var kc = { cropper: null, g: null, $box: null, url: null, clamping: false, timer: null, canvas: null };
    // largest side sent to the server (it makes the small PDF JPEG from this anyway)
    var kopCap = { header: 2400, footer: 2400, logo: 1500, ikon: 512 };

    function fmtRatio(n) { return (Math.round(n * 10) / 10).toString().replace('.', ','); }
    function kopMode() { return $('input[name="kopCropMode"]:checked').val(); }

    // the ratio the crop box is locked to (NaN = free, kept inside the limits by clampRatio)
    function kopAspect() {
        if (kopMode() === 'pad') return NaN;
        if (kc.g.key === 'ikon') return 1;
        if (kc.g.key === 'header' && $('#kopLockStandar').is(':checked')) return kc.g.rules.standar;
        return NaN;
    }

    // ratio the padded output gets: the locked one, else the selection's own ratio pulled into the limits
    function kopPadTarget(r) {
        if (kc.g.key === 'ikon') return 1;
        if (kc.g.key === 'header' && $('#kopLockStandar').is(':checked')) return kc.g.rules.standar;
        return Math.min(Math.max(r, kc.g.rules.ratio[0]), kc.g.rules.ratio[1]);
    }

    function openCrop($box, g, file) {
        kc.g = g;
        kc.$box = $box;
        if (kc.url) URL.revokeObjectURL(kc.url);
        kc.url = URL.createObjectURL(file);
        $('#kopCropTitle').text(g.label);
        $('#kopCropAturan').text(g.aturan);
        $('#kopModeCrop').prop('checked', true);
        $('#kopLockStandar').prop('checked', true);
        $('#kopLockWrap').toggle(g.key === 'header');
        $('#kopStandarText').text(g.key === 'header' ? '(2000×381)' : '');
        $('#kopCropInfo').text('').removeClass('ok bad');
        $('#kopA4 img').hide();
        $('#kopCropImg').attr('src', kc.url);
        $('#kopCropModal').modal('show');
    }

    function initCropper() {
        if (kc.cropper) kc.cropper.destroy();
        kc.cropper = new Cropper(document.getElementById('kopCropImg'), {
            viewMode: 1,
            autoCropArea: 1,
            dragMode: 'move',
            background: false,
            aspectRatio: kopAspect(),
            ready: function() { kopUpdateSoon(); },
            crop: function(e) { clampRatio(e.detail); kopUpdateSoon(); }
        });
    }

    // free crop: never let the box leave the accepted ratio (shrinking a side always fits inside the image)
    function clampRatio(d) {
        if (!kc.cropper || kc.clamping || kopMode() === 'pad' || !isNaN(kopAspect()) || !d.width || !d.height) return;
        var r = d.width / d.height, min = kc.g.rules.ratio[0], max = kc.g.rules.ratio[1];
        if (r >= min && r <= max) return;
        kc.clamping = true;
        kc.cropper.setData(r < min ? { height: d.width / min } : { width: d.height * max });
        kc.clamping = false;
    }

    function kopUpdateSoon() {
        clearTimeout(kc.timer);
        kc.timer = setTimeout(kopUpdate, 150);
    }

    // build the output canvas (crop / pad), check it against the rules, refresh the A4 preview
    function kopUpdate() {
        if (!kc.cropper) return;
        var cap = kopCap[kc.g.key];
        var c = kc.cropper.getCroppedCanvas({ maxWidth: cap, maxHeight: cap, imageSmoothingQuality: 'high' });
        if (!c || !c.width) return;
        if (kopMode() === 'pad') {
            var r = c.width / c.height, t = kopPadTarget(r);
            if (Math.abs(r - t) > 0.001) {
                var W = r < t ? Math.round(c.height * t) : c.width;
                var H = r < t ? c.height : Math.round(c.width / t);
                var p = document.createElement('canvas');
                p.width = W; p.height = H;
                var ctx = p.getContext('2d');
                ctx.fillStyle = '#ffffff';
                ctx.fillRect(0, 0, W, H);
                ctx.drawImage(c, Math.round((W - c.width) / 2), Math.round((H - c.height) / 2));
                c = p;
            }
        }
        kc.canvas = c;

        var rules = kc.g.rules, w = c.width, h = c.height, ratio = w / h, errors = [];
        if (rules.min_w && w < rules.min_w) errors.push('lebar ' + w + ' px, minimal ' + rules.min_w + ' px');
        if (rules.min_h && h < rules.min_h) errors.push('tinggi ' + h + ' px, minimal ' + rules.min_h + ' px');
        if (rules.min_long && Math.max(w, h) < rules.min_long) errors.push('sisi terpanjang ' + Math.max(w, h) + ' px, minimal ' + rules.min_long + ' px');
        if (ratio < rules.ratio[0] - 0.01 || ratio > rules.ratio[1] + 0.01) errors.push('rasio ' + fmtRatio(ratio) + ':1, harus ' + fmtRatio(rules.ratio[0]) + ':1 – ' + fmtRatio(rules.ratio[1]) + ':1');
        var size = 'Hasil: ' + w + ' × ' + h + ' px';
        if (errors.length) {
            $('#kopCropInfo').removeClass('ok').addClass('bad').html(esc(size) + '<br>✘ ' + errors.map(esc).join('<br>✘ ')
                + (errors.some(function(e) { return e.indexOf('minimal') !== -1; }) ? '<br><span class="font-weight-normal">Resolusi terlalu kecil, akan pecah saat dicetak. Perbesar area pilihan atau gunakan file yang lebih besar.</span>' : ''));
        } else {
            $('#kopCropInfo').removeClass('bad').addClass('ok').text(size + '  ✔ sesuai aturan');
        }
        $('#kopCropSave').prop('disabled', errors.length > 0);

        // small JPEG for the A4 mock-up
        var s = document.createElement('canvas'), sw = Math.min(260, w);
        s.width = sw; s.height = Math.max(1, Math.round(h * sw / w));
        var sctx = s.getContext('2d');
        sctx.fillStyle = '#ffffff';
        sctx.fillRect(0, 0, s.width, s.height);
        sctx.drawImage(c, 0, 0, s.width, s.height);
        var target = { header: '#kopA4Top', footer: '#kopA4Bottom', logo: '#kopA4Logo', ikon: '#kopA4Ikon' }[kc.g.key];
        $('#kopA4 img').hide();
        $(target).attr('src', s.toDataURL('image/jpeg', 0.8)).show();
    }

    $('#kopCropModal').on('shown.bs.modal', function() {
        // stacked on top of another modal: lift its backdrop above that modal
        $('.modal-backdrop').last().css('z-index', 1060);
        initCropper();
    }).on('hidden.bs.modal', function() {
        if (kc.cropper) { kc.cropper.destroy(); kc.cropper = null; }
        if (kc.url) { URL.revokeObjectURL(kc.url); kc.url = null; }
        kc.canvas = null;
        if ($('.modal.show').length) $('body').addClass('modal-open'); // keep the modal below scrollable
    });

    $('input[name="kopCropMode"], #kopLockStandar').on('change', function() {
        if (!kc.cropper) return;
        kc.cropper.setAspectRatio(kopAspect());
        kopUpdateSoon();
    });

    $('#kopCropFit').on('click', function() {
        if (!kc.cropper) return;
        kc.cropper.reset();
        kc.cropper.setAspectRatio(kopAspect());
        kopUpdateSoon();
    });

    $('#kopCropRotate').on('click', function() {
        if (!kc.cropper) return;
        kc.cropper.rotate(90);
        kopUpdateSoon();
    });

    $('#kopCropSave').on('click', function() {
        clearTimeout(kc.timer);
        kopUpdate();
        if (!kc.canvas || $(this).prop('disabled')) return;
        var g = kc.g, $box = kc.$box, canvas = kc.canvas, maxBytes = g.rules.max_kb * 1024;
        canvas.toBlob(function(png) {
            var send = function(blob, name) {
                uploadKop($box, g.key, blob, name, function() { $('#kopCropModal').modal('hide'); });
            };
            if (png && png.size <= maxBytes) {
                send(png, 'kop-' + g.key + '.png');
                return;
            }
            // PNG too big for the limit: JPEG on white instead
            var j = document.createElement('canvas');
            j.width = canvas.width; j.height = canvas.height;
            var ctx = j.getContext('2d');
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, j.width, j.height);
            ctx.drawImage(canvas, 0, 0);
            j.toBlob(function(jpg) { send(jpg, 'kop-' + g.key + '.jpg'); }, 'image/jpeg', 0.92);
        }, 'image/png');
    });

    $(document).on('click', '.kop-hapus', function() {
        var $item = $(this).closest('.kop-item');
        var $box = $item.parent();
        Swal.fire({
            icon: 'question', title: 'Kembalikan ke gambar bawaan?',
            text: 'Gambar custom dihapus; PDF berikutnya memakai gambar bawaan.',
            showCancelButton: true, confirmButtonText: 'Ya, kembalikan', cancelButtonText: 'Batal'
        }).then(function(r) {
            if (!r.isConfirmed) return;
            $.post(kopUrls.hapus, $.extend({ gambar: $item.data('gambar') }, $box.data('kopTarget')))
                .done(function(res) {
                    Swal.fire({ icon: 'success', title: res.message, timer: 1500, showConfirmButton: false });
                    loadKop($box, $box.data('kopTarget'));
                })
                .fail(function(xhr) { Swal.fire({ icon: 'error', title: 'Gagal', html: errorMessage(xhr) }); });
        });
    });

    $('#kopPerusahaanTeks').on('submit', function(e) {
        e.preventDefault();
        $.post(kopUrls.teks, $(this).serialize())
            .done(function(res) { Swal.fire({ icon: 'success', title: res.message, timer: 1500, showConfirmButton: false }); })
            .fail(function(xhr) { Swal.fire({ icon: 'error', title: 'Gagal', html: errorMessage(xhr) }); });
    });

    $('#btnKopGlobal').on('click', function() {
        loadKop($('#kopPerusahaanBox'), { lingkup: 'perusahaan' });
        loadKop($('#kopLabBox'), { lingkup: 'lab' });
        $('#kopGlobalModal').modal('show');
    });

    $('#btnAddKlinik').on('click', function() {
        resetForm();
        $('#klinikModalLabel').text('Tambah Klinik');
        $('#klinikModal').modal('show');
    });

    $('#klinik-table').on('click', '.btn-edit-klinik', function() {
        openKlinik($(this).data('id'));
    });

    $('#klinik-table').on('click', '.btn-token-klinik', function() {
        requestToken($(this).data('config-id'));
    });

    $('#ssBtnToken').on('click', function() {
        var klinikId = $('#klinikId').val();
        requestToken(currentConfigId, function() { openKlinik(klinikId, '#tabSatusehat'); });
    });

    $('#ssBtnDelete').on('click', function() {
        var klinikId = $('#klinikId').val();
        Swal.fire({
            title: 'Hapus konfigurasi SatuSehat?',
            text: 'Client ID, Client Secret, Organization ID dan token klinik ini akan dihapus. Data klinik tetap tersimpan.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Hapus',
            cancelButtonText: 'Batal'
        }).then(function(result) {
            if (!result.value) return;
            $.ajax({ url: ssBaseUrl + '/' + currentConfigId, type: 'DELETE' })
                .done(function() {
                    table.ajax.reload(null, false);
                    Swal.fire('Terhapus', 'Konfigurasi SatuSehat dihapus.', 'success');
                    openKlinik(klinikId, '#tabSatusehat');
                })
                .fail(function(xhr) { Swal.fire('Gagal', errorMessage(xhr), 'error'); });
        });
    });

    $('#locBtnDelete').on('click', function() {
        var klinikId = $('#klinikId').val();
        Swal.fire({
            title: 'Hapus lokasi SatuSehat?',
            text: 'Lokasi klinik ini akan dihapus. Data klinik tetap tersimpan.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Hapus',
            cancelButtonText: 'Batal'
        }).then(function(result) {
            if (!result.value) return;
            $.ajax({ url: locBaseUrl + '/' + currentLocationId, type: 'DELETE' })
                .done(function() {
                    table.ajax.reload(null, false);
                    Swal.fire('Terhapus', 'Lokasi dihapus.', 'success');
                    openKlinik(klinikId, '#tabSatusehat');
                })
                .fail(function(xhr) { Swal.fire('Gagal', errorMessage(xhr), 'error'); });
        });
    });

    $('#klinikForm').on('submit', function(e) {
        e.preventDefault();
        var id = $('#klinikId').val();
        var formData = new FormData(this);
        // multipart requests can't be sent as PUT, so spoof the method
        if (id) formData.append('_method', 'PUT');

        var $btn = $('#btnSaveKlinik').prop('disabled', true);
        $.ajax({
            url: id ? baseUrl + '/' + id : baseUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function() {
                $('#klinikModal').modal('hide');
                table.ajax.reload(null, false);
                Swal.fire('Berhasil', 'Klinik berhasil disimpan.', 'success');
            },
            error: function(xhr) {
                Swal.fire({ title: 'Gagal', html: errorMessage(xhr), icon: 'error' });
            },
            complete: function() {
                $btn.prop('disabled', false);
            }
        });
    });

    $('#klinik-table').on('click', '.btn-delete-klinik', function() {
        var id = $(this).data('id');
        var name = $(this).data('name') || 'klinik ini';

        Swal.fire({
            title: 'Hapus klinik?',
            text: 'Anda akan menghapus ' + name + '.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Hapus',
            cancelButtonText: 'Batal'
        }).then(function(result) {
            if (!result.value) {
                return;
            }

            $.ajax({
                url: baseUrl + '/' + id,
                type: 'DELETE',
                success: function() {
                    table.ajax.reload(null, false);
                    Swal.fire('Terhapus', 'Klinik berhasil dihapus.', 'success');
                },
                error: function(xhr) {
                    Swal.fire('Gagal', errorMessage(xhr), 'error');
                }
            });
        });
    });

    // Configs not linked to a klinik
    $('.btn-link-config').on('click', function() {
        var klinikId = $(this).closest('td').find('.link-klinik-select').val();
        $.post(ssBaseUrl + '/' + $(this).data('config-id') + '/link', { klinik_id: klinikId })
            .done(function() { location.reload(); })
            .fail(function(xhr) { Swal.fire('Gagal', errorMessage(xhr), 'error'); });
    });

    $('.btn-link-location').on('click', function() {
        var klinikId = $(this).closest('td').find('.link-klinik-select').val();
        $.post(locBaseUrl + '/' + $(this).data('location-id') + '/link', { klinik_id: klinikId })
            .done(function() { location.reload(); })
            .fail(function(xhr) { Swal.fire('Gagal', errorMessage(xhr), 'error'); });
    });

    $('.btn-delete-location').on('click', function() {
        var locationId = $(this).data('location-id');
        Swal.fire({
            title: 'Hapus lokasi #' + locationId + '?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Hapus',
            cancelButtonText: 'Batal'
        }).then(function(result) {
            if (!result.value) return;
            $.ajax({ url: locBaseUrl + '/' + locationId, type: 'DELETE' })
                .done(function() { location.reload(); })
                .fail(function(xhr) { Swal.fire('Gagal', errorMessage(xhr), 'error'); });
        });
    });

    $('.btn-delete-config').on('click', function() {
        var configId = $(this).data('config-id');
        Swal.fire({
            title: 'Hapus konfigurasi #' + configId + '?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Hapus',
            cancelButtonText: 'Batal'
        }).then(function(result) {
            if (!result.value) return;
            $.ajax({ url: ssBaseUrl + '/' + configId, type: 'DELETE' })
                .done(function() { location.reload(); })
                .fail(function(xhr) { Swal.fire('Gagal', errorMessage(xhr), 'error'); });
        });
    });
});
</script>
@endsection
