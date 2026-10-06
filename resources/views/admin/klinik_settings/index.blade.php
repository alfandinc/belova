@extends('layouts.admin.app')

@section('title', 'Klinik Setting')

@section('navbar')
    @include('layouts.admin.navbar')
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
                <button type="button" class="btn btn-primary" id="btnAddKlinik">Tambah Klinik</button>
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-hover table-bordered" id="klinik-table" style="width:100%">
                    <thead class="thead-light">
                        <tr>
                            <th style="width:140px">Logo</th>
                            <th>Nama Klinik</th>
                            <th style="width:100px">Cut Off</th>
                            <th style="width:140px">Warna</th>
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
                        <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tabSatusehat" role="tab">SatuSehat <span id="ssTabBadge"></span></a></li>
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
@endsection

@section('scripts')
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
        setSatusehat(null);
        setLocation(null);
    }

    function openKlinik(id, tab) {
        $.get(baseUrl + '/' + id, function(data) {
            resetForm();
            $('#klinikId').val(data.id);
            $('#klinikNama').val(data.nama);
            $('#klinikCutoff').val(data.report_cutoff_time || '00:00');
            if (data.color) {
                $('#klinikColorText').val(data.color);
                $('#klinikColorPicker').val(data.color);
            }
            if (data.logo_url) {
                $('#klinikLogoPreview').attr('src', data.logo_url);
                $('#klinikLogoPreviewWrap').show();
            }
            setSatusehat(data.satusehat);
            setLocation(data.location);
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
