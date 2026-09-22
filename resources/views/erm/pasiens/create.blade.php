@extends('layouts.erm.app')
@section('title', 'ERM | ' . ($isEditing ? 'Edit' : 'Tambah') . ' Pasien')
@section('navbar')
    @include('layouts.erm.navbar')
@endsection
@section('content')
<style>
    /* Tetap ada ini */
    #pasien-form {
        visibility: hidden;
    }

    #pasien-form.wizard-initialized {
        visibility: visible;
    }

    .is-invalid {
        border-color: red !important;    
    }

    .personal-section {
        border: 1px solid #e6ebf5;
        border-radius: 18px;
        padding: 22px;
        background: #ffffff;
        margin-bottom: 20px;
    }

    .personal-section-highlight {
        background: linear-gradient(180deg, #f5f9ff 0%, #ffffff 100%);
        border-color: #bfd4ff;
        box-shadow: 0 14px 30px rgba(47, 108, 229, 0.08);
    }

    .personal-section-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 18px;
    }

    .personal-section-title {
        margin: 0;
        font-size: 1.05rem;
        font-weight: 700;
        color: #183153;
    }

    .personal-section-copy {
        margin: 6px 0 0;
        color: #6b7a90;
        font-size: 0.9rem;
    }

    .personal-section-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 6px 12px;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.02em;
        white-space: nowrap;
    }

    .personal-section-pill.required {
        background: #2f6ce5;
        color: #fff;
    }

    .personal-section-pill.optional {
        background: #eef2f8;
        color: #51627c;
    }

    .personal-required-label::after {
        content: ' *';
        color: #d93025;
        font-weight: 700;
    }

    .personal-field-note {
        display: block;
        margin-top: 6px;
        color: #7b8aa3;
        font-size: 0.82rem;
    }

    @media (max-width: 767.98px) {
        .personal-section {
            padding: 16px;
            border-radius: 14px;
            margin-bottom: 16px;
        }

        .personal-section-header {
            flex-direction: column;
            align-items: flex-start;
            margin-bottom: 14px;
        }

        .personal-section-title {
            font-size: 1rem;
        }

        .personal-section-copy {
            font-size: 0.85rem;
        }
    }

</style>

@include('erm.partials.modal-daftarkunjungan')
@include('erm.partials.modal-ic-pendaftaran')

<div class="container-fluid">
    <!-- Page-Title -->
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="row">
                    <div class="col">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:void(0);">ERM</a></li>
                            <li class="breadcrumb-item active">Pasien</li>
                            <li class="breadcrumb-item active">{{ $isEditing ? 'Edit' : 'Tambah' }}</li>
                        </ol>
                    </div><!--end col-->
                </div><!--end row-->                                                              
            </div><!--end page-title-box-->
        </div><!--end col-->
    </div><!--end row-->
    <!-- end page title end breadcrumb -->
    <div class="card">
        <div class="card-header bg-primary">
            <h4 class="card-title text-white">{{ $isEditing ? 'Edit Data Pasien' : 'Data Pasien Baru' }}</h4>
        </div>
        <div class="card-body">
            <form id="pasien-form" class="form-wizard-wrapper" action="{{ route('erm.pasiens.store') }}" method="POST">
                @csrf
                <input type="hidden" id="consent_pdf_path" name="consent_pdf_path" value="{{ old('consent_pdf_path', $pasien->consent_pdf_path ?? '') }}">
                <input type="hidden" id="duplicate_name_birthdate_acknowledged" name="duplicate_name_birthdate_acknowledged" value="{{ old('duplicate_name_birthdate_acknowledged', '0') }}">
                @if($isEditing && $pasien)
                    <input type="hidden" name="pasien_id" value="{{ $pasien->id }}">
                @endif
                <h3>Personal Data</h3>
                    <fieldset>
                        <div class="personal-section personal-section-highlight">
                            <div class="personal-section-header">
                                <div>
                                    <h5 class="personal-section-title">Data Utama Pasien</h5>
                                    <p class="personal-section-copy">Isi identitas utama pasien terlebih dahulu. Catatan khusus tidak wajib, tetapi bisa diisi untuk mempermudah pengenalan pasien.</p>
                                </div>
                                <span class="personal-section-pill required">Wajib Diisi</span>
                            </div>
                            <div class="row">
                                <div class="col-12 col-md-6 col-lg-6">
                                    <div class="form-group mb-3 mb-md-4">
                                        <label class="personal-required-label" for="identity_document">Dokumen Identitas</label>
                                        <select class="form-control select2" id="identity_document" name="identity_document" required>
                                            <option value="ktp" {{ ($pasien->identity_document ?? 'ktp') === 'ktp' ? 'selected' : '' }}>KTP</option>
                                            <option value="sim" {{ ($pasien->identity_document ?? '') === 'sim' ? 'selected' : '' }}>SIM</option>
                                            <option value="paspor" {{ ($pasien->identity_document ?? '') === 'paspor' ? 'selected' : '' }}>Paspor</option>
                                            <option value="kia" {{ ($pasien->identity_document ?? '') === 'kia' ? 'selected' : '' }}>KIA</option>
                                        </select>
                                        <span class="personal-field-note">Pilih dokumen utama pasien.</span>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6 col-lg-6">
                                    <div class="form-group mb-3 mb-md-4">
                                        <label class="personal-required-label" for="identity_number" id="identity_number_label">Nomor Identitas</label>
                                        <input type="text" class="form-control" id="identity_number" name="identity_number" maxlength="50" required value="{{ old('identity_number', $pasien->identity_number ?? $pasien->nik ?? '') }}">
                                        <small class="form-text text-muted" id="identity_number_help">KTP wajib 16 digit. Dokumen lain boleh memakai nomor sesuai dokumen.</small>
                                        <small class="form-text d-none" id="identity_number_validation"></small>
                                    </div>
                                </div>
                                <div class="col-12 col-lg-6">
                                    <div class="form-group mb-3 mb-md-4">
                                        <label class="personal-required-label" for="nama">Nama</label>
                                        <input type="text" class="form-control" id="nama" name="nama" required value="{{ $pasien->nama ?? '' }}">
                                        <span class="personal-field-note">Nama pasien akan otomatis disimpan dalam huruf kapital.</span>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6 col-lg-6">
                                    <div class="form-group mb-3 mb-md-0">
                                        <label class="personal-required-label" for="tanggal_lahir">Tanggal Lahir</label>
                                        <div class="input-group">
                                            <input type="date" class="form-control" id="tanggal_lahir" name="tanggal_lahir" required value="{{ $pasien->tanggal_lahir ?? '' }}">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6 col-lg-6">
                                    <div class="form-group mb-0">
                                        <label class="personal-required-label" for="gender">Gender</label>
                                        <select class="form-control select2" id="gender" name="gender" required>
                                            <option value="" selected disabled>Select Gender</option>
                                            <option value="Laki-laki">Laki-laki</option>
                                            <option value="Perempuan">Perempuan</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6 col-lg-6">
                                    <div class="form-group mb-0">
                                        <label for="notes">Catatan Khusus</label>
                                        <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Contoh: pasien lebih mudah dikenali dengan nama panggilan atau ciri tertentu">{{ $pasien->notes ?? '' }}</textarea>
                                        <span class="personal-field-note">Opsional. Gunakan hanya untuk mempermudah pengenalan pasien.</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="personal-section">
                            <div class="personal-section-header">
                                <div>
                                    <h5 class="personal-section-title">Informasi Tambahan</h5>
                                    <p class="personal-section-copy">Lengkapi data pendukung pasien bila tersedia. Bagian ini membantu administrasi dan pelaporan.</p>
                                </div>
                                <span class="personal-section-pill optional">Opsional</span>
                            </div>
                            <div class="row">
                                <div class="col-12 col-md-6 col-lg-6">
                                    <div class="form-group">
                                        <label for="agama">Agama</label>
                                        <select class="form-control select2" id="agama" name="agama">
                                            <option value="" disabled selected>Select Agama</option>
                                            <option value="Islam">Islam</option>
                                            <option value="Kristen Protestan">Kristen Protestan</option>
                                            <option value="Katolik">Katolik</option>
                                            <option value="Hindu">Hindu</option>
                                            <option value="Buddha">Buddha</option>
                                            <option value="Konghucu">Konghucu</option>
                                            <option value="Lainnya">Lainnya</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6 col-lg-6">
                                    <div class="form-group">
                                        <label for="marital_status">Marital Status</label>
                                        <select class="form-control select2" id="marital_status" name="marital_status">
                                            <option value="" selected disabled>Select Marital Status</option>
                                            <option value="Belum Menikah">Belum Menikah</option>
                                            <option value="Menikah">Menikah</option>
                                            <option value="Cerai Hidup">Cerai Hidup</option>
                                            <option value="Cerai Mati">Cerai Mati</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12 col-lg-6">
                                    <div class="form-group">
                                        <label for="pendidikan">Pendidikan</label>
                                        <select class="form-control select2" id="pendidikan" name="pendidikan">
                                            <option disabled selected>Select Tingkat Pendidikan</option>
                                            <option value="Tidak Sekolah">Tidak Sekolah</option>
                                            <option value="Tidak Tamat SD/Sederajat">Tidak Tamat SD/Sederajat</option>
                                            <option value="Tamat SD/Sederajat">Tamat SD/Sederajat</option>
                                            <option value="Tamat SMP/Sederajat">Tamat SMP/Sederajat</option>
                                            <option value="Tamat SMA/Sederajat">Tamat SMA/Sederajat</option>
                                            <option value="Diploma I (D1)">Diploma I (D1)</option>
                                            <option value="Diploma II (D2)">Diploma II (D2)</option>
                                            <option value="Diploma III (D3)">Diploma III (D3)</option>
                                            <option value="Strata I (S1) / Sarjana">Strata I (S1) / Sarjana</option>
                                            <option value="Strata II (S2) / Magister">Strata II (S2) / Magister</option>
                                            <option value="Strata III (S3) / Doktor">Strata III (S3) / Doktor</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12 col-lg-6">
                                    <div class="form-group">
                                        <label for="pekerjaan">Pekerjaan</label>
                                        <select class="form-control select2" id="pekerjaan" name="pekerjaan">
                                            <option disabled selected>Select Pekerjaan</option>
                                            <option value="Belum/Tidak Bekerja">Belum/Tidak Bekerja</option>
                                            <option value="Pelajar/Mahasiswa">Pelajar/Mahasiswa</option>
                                            <option value="Ibu Rumah Tangga">Ibu Rumah Tangga</option>
                                            <option value="Pegawai Negeri Sipil (PNS)">Pegawai Negeri Sipil (PNS)</option>
                                            <option value="Tentara Nasional Indonesia (TNI)">Tentara Nasional Indonesia (TNI)</option>
                                            <option value="Kepolisian RI (Polri)">Kepolisian RI (Polri)</option>
                                            <option value="Pegawai Swasta">Pegawai Swasta</option>
                                            <option value="Wiraswasta / Pengusaha">Wiraswasta / Pengusaha</option>
                                            <option value="Petani / Pekebun">Petani / Pekebun</option>
                                            <option value="Nelayan / Penangkap Ikan">Nelayan / Penangkap Ikan</option>
                                            <option value="Buruh / Karyawan Harian">Buruh / Karyawan Harian</option>
                                            <option value="Guru / Dosen">Guru / Dosen</option>
                                            <option value="Dokter / Tenaga Medis">Dokter / Tenaga Medis</option>
                                            <option value="Perangkat Desa / Kelurahan">Perangkat Desa / Kelurahan</option>
                                            <option value="Pensiunan">Pensiunan</option>
                                            <option value="Seniman / Artis / Sejenisnya">Seniman / Artis / Sejenisnya</option>
                                            <option value="Sopir / Ojek / Transportasi">Sopir / Ojek / Transportasi</option>
                                            <option value="Pedagang / UMKM">Pedagang / UMKM</option>
                                            <option value="Lainnya">Lainnya</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6 col-lg-6">
                                    <div class="form-group mb-3 mb-lg-0">
                                        <label for="gol_darah">Golongan Darah</label>
                                        <select class="form-control select2" id="gol_darah" name="gol_darah">
                                            <option selected disabled>Select Gol Darah</option>
                                            <option value="O">O</option>
                                            <option value="A">A</option>
                                            <option value="B">B</option>
                                            <option value="AB">AB</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6 col-lg-6">
                                    <div class="form-group mb-3 mb-lg-0">
                                        <label for="is_employee_patient">Apakah pasien adalah karyawan?</label>
                                        <select class="form-control select2" id="is_employee_patient" name="is_employee_patient">
                                            <option value="0" {{ old('is_employee_patient', !empty($pasien->employee_id) ? '1' : '0') === '0' ? 'selected' : '' }}>Bukan karyawan</option>
                                            <option value="1" {{ old('is_employee_patient', !empty($pasien->employee_id) ? '1' : '0') === '1' ? 'selected' : '' }}>Ya, pasien adalah karyawan</option>
                                        </select>
                                        <span class="personal-field-note">Jika pasien adalah karyawan, pilih data employee yang sesuai.</span>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6 col-lg-6 d-none" id="employee_patient_wrapper">
                                    <div class="form-group mb-0">
                                        <label for="employee_id">Employee</label>
                                        <select class="form-control select2" id="employee_id" name="employee_id">
                                            <option value="">Pilih Employee</option>
                                            @foreach($employees as $employee)
                                                <option value="{{ $employee->id }}" {{ (string) old('employee_id', $pasien->employee_id ?? '') === (string) $employee->id ? 'selected' : '' }}>
                                                    {{ $employee->nama }}@if($employee->no_induk) ({{ $employee->no_induk }})@endif
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                </fieldset>

                <h3>Address Data</h3>
                <fieldset>
                <div class="form-group">
                    <label class="personal-required-label" for="alamat">ALAMAT</label>
                    <textarea class="form-control" id="alamat" name="alamat" rows="3" required>{{ $pasien->alamat ?? '' }}</textarea>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="personal-required-label" for="province">PROVINSI</label>
                            <select class="form-control select2" id="province" name="province" required>
                    <option value="">Pilih Provinsi</option>
                    @foreach($provinces as $prov)
                        <option value="{{ $prov->id }}">{{ $prov->name }}</option>
                    @endforeach
                </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="personal-required-label" for="regency">KABUPATEN</label>

                <select class="form-control select2" id="regency" name="regency" required>
                    <option value="">Pilih Kabupaten</option>
                </select>

                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="personal-required-label" for="district">KECAMATAN</label>
                            <select class="form-control select2" id="district" name="district" required>
                    <option value="">Pilih Kecamatan</option>
                </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="personal-required-label" for="village">DESA</label>
                            <select class="form-control select2" id="village" name="village" required>
                    <option value="">Pilih Desa</option>
                </select>
                        </div>
                    </div>
                </div>
                </fieldset>
                <h3>Contact Data</h3>
                <fieldset>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                                <label class="personal-required-label" for="no_hp">No Telepon 1</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">+62</span>
                                </div>
                                <input type="text" inputmode="numeric" class="form-control" id="no_hp" name="no_hp" maxlength="13" required value="{{ preg_replace('/^(62|0)/', '', (string) ($pasien->no_hp ?? '')) }}" placeholder="818896869">
                            </div>
                            <small class="form-text text-muted">Isi tanpa 0 di depan. Contoh: 818896869.</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="no_hp2">No Telepon Darurat</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">+62</span>
                                </div>
                                <input type="text" inputmode="numeric" class="form-control" id="no_hp2" name="no_hp2" maxlength="13" value="{{ preg_replace('/^(62|0)/', '', (string) ($pasien->no_hp2 ?? '')) }}" placeholder="818896869">
                            </div>
                            <small class="form-text text-muted">Jika diisi, cukup masukkan angka setelah kode negara.</small>
                        </div>
                    </div>    
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="{{ $pasien->email ?? '' }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="istagram">Instagram</label>
                            <input type="text" class="form-control" id="instagram" name="instagram" value="{{ $pasien->instagram ?? '' }}">
                        </div>
                    </div>     
                </div>

                </fieldset>
                <h3>Referral Data</h3>
                <fieldset>
                    <div class="row">
                        <div class="col-12 col-md-6">
                            <div class="form-group">
                                <label class="personal-required-label" for="referral_type">Sumber Referral</label>
                                <select class="form-control select2" id="referral_type" name="referral_type" required>
                                    <option value="walk_in" {{ old('referral_type', $pasien->referral_type ?? 'walk_in') === 'walk_in' ? 'selected' : '' }}>Walk-in</option>
                                    <option value="pasien" {{ old('referral_type', $pasien->referral_type ?? '') === 'pasien' ? 'selected' : '' }}>Pasien</option>
                                    <option value="dokter" {{ old('referral_type', $pasien->referral_type ?? '') === 'dokter' ? 'selected' : '' }}>Dokter</option>
                                    <option value="employee" {{ old('referral_type', $pasien->referral_type ?? '') === 'employee' ? 'selected' : '' }}>Karyawan</option>
                                    <option value="social_media" {{ old('referral_type', $pasien->referral_type ?? '') === 'social_media' ? 'selected' : '' }}>Social Media</option>
                                    <option value="marketplace" {{ old('referral_type', $pasien->referral_type ?? '') === 'marketplace' ? 'selected' : '' }}>Marketplace</option>
                                    <option value="event" {{ old('referral_type', $pasien->referral_type ?? '') === 'event' ? 'selected' : '' }}>Event</option>
                                    <option value="website" {{ old('referral_type', $pasien->referral_type ?? '') === 'website' ? 'selected' : '' }}>Website</option>
                                    <option value="partnership" {{ old('referral_type', $pasien->referral_type ?? '') === 'partnership' ? 'selected' : '' }}>B2B Partnership</option>
                                    <option value="google_maps" {{ old('referral_type', $pasien->referral_type ?? '') === 'google_maps' ? 'selected' : '' }}>Google Maps</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="d-none" id="referral_pasien_wrapper">
                                <div class="form-group">
                                    <label class="personal-required-label" for="referral_target_pasien_id">Pasien Referral</label>
                                    <select class="form-control" id="referral_target_pasien_id" name="referral_target_pasien_id" data-selected-id="{{ old('referral_target_pasien_id', ($pasien->referral_type ?? '') === 'pasien' ? ($pasien->referralable_id ?? '') : '') }}" data-selected-text="{{ old('referral_pasien_text', ($pasien->referral_type ?? '') === 'pasien' && ($pasien?->referralable?->nama ?? false) ? $pasien->referralable->nama . ' (RM: ' . $pasien->referralable->id . ')' : '') }}">
                                    </select>
                                    <small class="form-text text-muted">Cari berdasarkan nama pasien, nomor RM, atau nomor identitas.</small>
                                </div>
                            </div>

                            <div class="d-none" id="referral_employee_wrapper">
                                <div class="form-group">
                                    <label class="personal-required-label" for="referral_employee_id">Karyawan Referral</label>
                                    <select class="form-control select2" id="referral_employee_id" name="referral_employee_id">
                                        <option value="">Pilih Karyawan Referral</option>
                                        @foreach($employees as $employee)
                                            <option value="{{ $employee->id }}" {{ (string) old('referral_employee_id', ($pasien->referral_type ?? '') === 'employee' ? ($pasien->referralable_id ?? '') : '') === (string) $employee->id ? 'selected' : '' }}>
                                                {{ $employee->nama }}@if($employee->no_induk) ({{ $employee->no_induk }})@endif
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="d-none" id="referral_dokter_wrapper">
                                <div class="form-group">
                                    <label class="personal-required-label" for="referral_dokter_id">Dokter Referral</label>
                                    <select class="form-control select2" id="referral_dokter_id" name="referral_dokter_id">
                                        <option value="">Pilih Dokter Referral</option>
                                        @foreach($dokters as $dokter)
                                            <option value="{{ $dokter->id }}" {{ (string) old('referral_dokter_id', ($pasien->referral_type ?? '') === 'dokter' ? ($pasien->referralable_id ?? '') : '') === (string) $dokter->id ? 'selected' : '' }}>
                                                {{ $dokter->user->name ?? ('Dokter ID ' . $dokter->id) }}@if($dokter->spesialisasi) ({{ $dokter->spesialisasi->nama }})@endif
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="d-none" id="referral_event_wrapper">
                                <div class="form-group">
                                    <label class="personal-required-label" for="referral_event_id">Event Referral</label>
                                    <select class="form-control select2" id="referral_event_id" name="referral_event_id">
                                        <option value="">Pilih Event Referral</option>
                                        @foreach($events as $event)
                                            <option value="{{ $event->id }}" {{ (string) old('referral_event_id', ($pasien->referral_type ?? '') === 'event' ? ($pasien->referralable_id ?? '') : '') === (string) $event->id ? 'selected' : '' }}>
                                                {{ $event->nama_event }}@if($event->kode_event) ({{ $event->kode_event }})@endif
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="d-none" id="referral_detail_wrapper">
                                <div class="form-group">
                                    <label class="personal-required-label" for="referral_detail">Detail Referral</label>
                                    <input type="text" class="form-control" id="referral_detail" name="referral_detail" maxlength="255" value="{{ old('referral_detail', $pasien->referral_detail ?? '') }}" placeholder="Isi detail referral bila diperlukan.">
                                    <select class="form-control select2 d-none mt-2" id="referral_detail_select" data-placeholder="Pilih detail referral">
                                        <option value="">Pilih Detail Referral</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="form-group">
                                <div class="form-check d-flex align-items-start">
                                    <input class="form-check-input mt-1 me-2" type="checkbox" id="terms" name="terms" required>
                                    <label class="form-check-label" for="terms" style="text-align: justify;">
                                        Saya menyatakan bahwa seluruh data yang saya isi adalah benar dan dapat dipertanggungjawabkan.
                                        Saya juga menyetujui bahwa data ini akan digunakan untuk keperluan pelayanan kesehatan
                                        sesuai dengan kebijakan yang berlaku.
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </fieldset>
                    {{-- <button type="submit">Test Submit</button> --}}
            </form>
        </div>
    </div>
</div><!-- container -->

<div class="modal fade" id="duplicatePasienModal" tabindex="-1" role="dialog" aria-labelledby="duplicatePasienModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title" id="duplicatePasienModalLabel">Kemungkinan Pasien Sudah Terdaftar</h5>
            </div>
            <div class="modal-body">
                <p id="duplicate-pasien-summary" class="mb-3 text-muted">Ditemukan data pasien dengan kombinasi nama dan tanggal lahir yang sama.</p>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped mb-0">
                        <thead>
                            <tr>
                                <th>No RM</th>
                                <th>Nama</th>
                                <th>Tanggal Lahir</th>
                                <th>Alamat</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="duplicate-pasien-list"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="confirm-new-patient-btn">Pasien ini adalah pasien baru</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>  
   $(document).ready(function () {
    var wizard = $("#pasien-form").steps({
    headerTag: "h3",
    bodyTag: "fieldset",
    transitionEffect: "slide",
    onStepChanged: function () {},
    onInit: function () {
        $('#pasien-form').addClass('wizard-initialized');
        },
    onStepChanging: function (event, currentIndex, newIndex) {
        var currentStep = $('.body:eq(' + currentIndex + ')');
        var isValid = true;

        currentStep.find('input, select, textarea').each(function () {
            if (!this.checkValidity()) {
                isValid = false;
                $(this).addClass('is-invalid');

                // ✅ Tambahkan class ke Select2
                if ($(this).hasClass('select2-hidden-accessible')) {
                    $(this).next('.select2-container').find('.select2-selection').addClass('is-invalid');
                }
            } else {
                $(this).removeClass('is-invalid');

                if ($(this).hasClass('select2-hidden-accessible')) {
                    $(this).next('.select2-container').find('.select2-selection').removeClass('is-invalid');
                }
            }
        });

        return isValid; // ⬅️ Hanya lanjut step jika valid
    },
        onFinished: function (event, currentIndex) {
                // Submit the form immediately (IC is optional and can be done later)
                $('#pasien-form').submit();
            }
    });
    
    $('.select2').select2({ width: '100%' });

    $('#referral_target_pasien_id').select2({
        width: '100%',
        placeholder: 'Cari pasien referral',
        allowClear: true,
        ajax: {
            url: '{{ route('erm.pasiens.select2') }}',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term || ''
                };
            },
            processResults: function (data) {
                return data;
            }
        }
    });

    // VALIDASI INPUT

    $('#nama').on('input', function () {
        this.value = this.value.toUpperCase();
        invalidateDuplicateCheckIfNeeded();
    });

    $('#nama').on('blur', function () {
        invalidateDuplicateCheckIfNeeded();
        checkDuplicateNameBirthdate({ force: true });
    });
    $('#alamat').on('input', function () {
        this.value = this.value.toUpperCase();
    });
    const identityDocumentLabels = {
        ktp: 'NIK',
        sim: 'Nomor SIM',
        paspor: 'Nomor Paspor',
        kia: 'Nomor KIA'
    };

    const identityDocumentHints = {
        ktp: 'KTP wajib 16 digit angka.',
        sim: 'Masukkan nomor SIM sesuai dokumen.',
        paspor: 'Masukkan nomor paspor sesuai dokumen.',
        kia: 'Masukkan nomor KIA sesuai dokumen anak.'
    };

    const referralDetailOptionMap = {
        social_media: [
            { value: 'instagram', label: 'Instagram' },
            { value: 'tiktok', label: 'Tiktok' },
            { value: 'facebook', label: 'Facebook' },
            { value: 'threads', label: 'Threads' },
            { value: 'twitter', label: 'Twitter' },
            { value: 'whatsapp', label: 'Whatsapp' }
        ],
        marketplace: [
            { value: 'shopee', label: 'Shopee' },
            { value: 'tiktokshop', label: 'Tiktokshop' },
            { value: 'tokopedia', label: 'Tokopedia' },
            { value: 'lazada', label: 'Lazada' }
        ]
    };

    const duplicateCheckState = {
        signature: null,
        response: null,
        acknowledgedSignature: $('#duplicate_name_birthdate_acknowledged').val() === '1' ? [($('#nama').val() || '').trim().toUpperCase(), ($('#tanggal_lahir').val() || '').trim()].join('|') : null,
        promptedSignature: null,
        pendingSubmit: false
    };

    const identityNumberCheckState = {
        signature: null,
        exists: false
    };

    let duplicateModalAllowClose = false;

    function getDuplicateSignature() {
        const nama = ($('#nama').val() || '').trim().toUpperCase();
        const tanggalLahir = ($('#tanggal_lahir').val() || '').trim();

        if (!nama || !tanggalLahir) {
            return '';
        }

        return nama + '|' + tanggalLahir;
    }

    function setDuplicateAcknowledged(isAcknowledged) {
        const signature = getDuplicateSignature();
        $('#duplicate_name_birthdate_acknowledged').val(isAcknowledged ? '1' : '0');
        duplicateCheckState.acknowledgedSignature = isAcknowledged ? signature : null;
    }

    function invalidateDuplicateCheckIfNeeded() {
        const signature = getDuplicateSignature();

        if (duplicateCheckState.signature && duplicateCheckState.signature !== signature) {
            duplicateCheckState.signature = null;
            duplicateCheckState.response = null;
            duplicateCheckState.promptedSignature = null;
            duplicateCheckState.pendingSubmit = false;
            setDuplicateAcknowledged(false);
        }

        if (!signature) {
            duplicateCheckState.promptedSignature = null;
            setDuplicateAcknowledged(false);
        }
    }

    function renderDuplicatePatients(patients) {
        const rows = (patients || []).map(function (patient) {
            const safeId = $('<div>').text(patient.id || '-').html();
            const safeNama = $('<div>').text(patient.nama || '-').html();
            const safeTanggalLahir = $('<div>').text(patient.tanggal_lahir || '-').html();
            const safeAlamat = $('<div>').text(patient.alamat || '-').html();

            return `
                <tr>
                    <td>${safeId}</td>
                    <td>${safeNama}</td>
                    <td>${safeTanggalLahir}</td>
                    <td>${safeAlamat}</td>
                    <td class="text-center">
                        <button type="button" class="btn btn-success btn-sm btn-daftar-visitation duplicate-patient-visit" data-id="${safeId}" data-nama="${safeNama}">
                            Daftarkan Kunjungan
                        </button>
                    </td>
                </tr>
            `;
        }).join('');

        $('#duplicate-pasien-list').html(rows || '<tr><td colspan="5" class="text-center text-muted">Tidak ada data pasien.</td></tr>');
    }

    function openDuplicatePatientsModal(response) {
        const total = response?.count || 0;
        $('#duplicate-pasien-summary').text('Terdapat ' + total + ' pasien dengan kombinasi nama dan tanggal lahir yang sama. Silakan cek sebelum menyimpan pasien baru.');
        renderDuplicatePatients(response?.patients || []);
        duplicateModalAllowClose = false;
        $('#duplicatePasienModal').modal('show');
    }

    function promptDuplicatePatients(response) {
        const currentSignature = getDuplicateSignature();

        if (!currentSignature || duplicateCheckState.acknowledgedSignature === currentSignature || duplicateCheckState.promptedSignature === currentSignature || Swal.isVisible()) {
            return;
        }

        duplicateCheckState.promptedSignature = currentSignature;

        Swal.fire({
            icon: 'warning',
            title: 'Pasien Serupa Ditemukan',
            html: 'Terdapat <strong>' + (response.count || 0) + '</strong> pasien dengan kombinasi nama dan tanggal lahir yang sama di sistem.',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showCancelButton: false,
            confirmButtonText: 'Check',
        }).then(function (result) {
            if (result.value) {
                openDuplicatePatientsModal(response);
                return;
            }

            duplicateCheckState.promptedSignature = null;
        });
    }

    function checkDuplicateNameBirthdate(options = {}) {
        const currentSignature = getDuplicateSignature();
        const pasienId = $('input[name="pasien_id"]').val() || '';

        if (!currentSignature) {
            return $.Deferred().resolve({ exists: false, count: 0, patients: [] }).promise();
        }

        if (!options.force && duplicateCheckState.signature === currentSignature && duplicateCheckState.response) {
            return $.Deferred().resolve(duplicateCheckState.response).promise();
        }

        return $.ajax({
            url: '{{ route('erm.pasiens.check-duplicate-name-birthdate') }}',
            type: 'GET',
            dataType: 'json',
            data: {
                nama: ($('#nama').val() || '').trim(),
                tanggal_lahir: $('#tanggal_lahir').val(),
                pasien_id: pasienId
            }
        }).done(function (response) {
            duplicateCheckState.signature = currentSignature;
            duplicateCheckState.response = response;

            if (!response.exists) {
                duplicateCheckState.promptedSignature = null;
                setDuplicateAcknowledged(false);
                return;
            }

            if (duplicateCheckState.acknowledgedSignature !== currentSignature) {
                setDuplicateAcknowledged(false);
                promptDuplicatePatients(response);
            }
        });
    }

    function submitPasienForm() {
        let form = $('#pasien-form');
        let formData = new FormData(form[0]);

        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            dataType: 'json',

            success: function (response) {
                duplicateCheckState.pendingSubmit = false;
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: 'Data berhasil disimpan.',
                    confirmButtonText: 'OK'
                }).then((result) => {
                    if (result.value) {
                        Swal.fire({
                            title: 'Buka kunjungan?',
                            text: 'Apakah Anda ingin membuka form kunjungan?',
                            icon: 'question',
                            showCancelButton: true,
                            confirmButtonText: 'Ya',
                            cancelButtonText: 'Tidak'
                        }).then((result2) => {
                            if (result2.value) {
                                $('#modal-pasien-id').val(response.pasien.id);
                                $('#modal-nama-pasien').val(response.pasien.nama);
                                $('#modalKunjungan').modal('show');
                            } else {
                                location.reload();
                            }
                        });
                    }
                });
            },
            error: function (xhr) {
                let errors = xhr.responseJSON?.errors;
                let errorMsg = 'Terjadi kesalahan saat mengirim data.';

                if (xhr.status === 422 && xhr.responseJSON?.duplicate_name_birthdate) {
                    duplicateCheckState.pendingSubmit = true;
                    duplicateCheckState.response = {
                        exists: true,
                        count: xhr.responseJSON.count || 0,
                        patients: xhr.responseJSON.patients || []
                    };
                    duplicateCheckState.signature = getDuplicateSignature();
                    promptDuplicatePatients(duplicateCheckState.response);
                    return;
                }

                duplicateCheckState.pendingSubmit = false;

                if (errors) {
                    errorMsg = Object.values(errors).map(err => `• ${err}`).join('<br>');
                }

                Swal.fire({
                    title: 'Gagal!',
                    html: errorMsg,
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            }
        });
    }

    function syncReferralDetailInput(referralType) {
        const detailInput = $('#referral_detail');
        const detailSelect = $('#referral_detail_select');
        const detailSelectContainer = detailSelect.next('.select2-container');
        const selectedOptions = referralDetailOptionMap[referralType] || null;
        const currentValue = (detailInput.val() || '').trim().toLowerCase();

        if (!selectedOptions) {
            detailSelect.addClass('d-none').prop('disabled', true).empty().append('<option value="">Pilih Detail Referral</option>').trigger('change.select2');
            detailSelectContainer.addClass('d-none');
            detailInput.removeClass('d-none');
            return;
        }

        let optionsHtml = '<option value="">Pilih Detail Referral</option>';
        selectedOptions.forEach(function(option) {
            optionsHtml += '<option value="' + option.value + '">' + option.label + '</option>';
        });

    detailSelect.html(optionsHtml).prop('disabled', false).removeClass('d-none');
    detailSelectContainer.removeClass('d-none');
        detailInput.addClass('d-none');

        if (currentValue) {
            detailSelect.val(currentValue);
        }

        detailSelect.trigger('change.select2');
    }

    function syncIdentityInput() {
        const documentType = $('#identity_document').val() || 'ktp';
        const identityInput = $('#identity_number');

        $('#identity_number_label').text(identityDocumentLabels[documentType] || 'Nomor Identitas');
        $('#identity_number_help').text(identityDocumentHints[documentType] || 'Masukkan nomor identitas sesuai dokumen.');

        if (documentType === 'ktp') {
            identityInput.attr('maxlength', 16);
            identityInput.attr('inputmode', 'numeric');
            identityInput.attr('placeholder', '16 digit nomor KTP');
            identityInput.val(identityInput.val().replace(/\D/g, '').slice(0, 16));
            return;
        }

        identityInput.attr('maxlength', 50);
        identityInput.attr('inputmode', 'text');
        identityInput.attr('placeholder', 'Masukkan nomor dokumen');
    }

    function setIdentityNumberValidationState(isValid, message, isWarning = false) {
        const identityInput = $('#identity_number');
        const validationHint = $('#identity_number_validation');

        identityInput[0].setCustomValidity(isValid ? '' : (message || 'Nomor identitas tidak valid.'));
        identityInput.toggleClass('is-invalid', !isValid);
        validationHint.removeClass('d-none text-danger text-success text-warning');

        if (!message) {
            validationHint.addClass('d-none').text('');
            return;
        }

        validationHint
            .addClass(isValid ? 'text-success' : (isWarning ? 'text-warning' : 'text-danger'))
            .text(message);
    }

    function resetIdentityNumberValidationState() {
        identityNumberCheckState.signature = null;
        identityNumberCheckState.exists = false;
        setIdentityNumberValidationState(true, '');
        $('#identity_number').nextAll('.select2-container').find('.select2-selection').removeClass('is-invalid');
    }

    function validateIdentityNumberImmediately(options = {}) {
        const identityInput = $('#identity_number');
        const documentType = $('#identity_document').val() || 'ktp';
        const identityNumber = (identityInput.val() || '').trim();
        const pasienId = $('input[name="pasien_id"]').val() || '';
        const signature = documentType + '|' + identityNumber + '|' + pasienId;

        if (!identityNumber) {
            resetIdentityNumberValidationState();
            return $.Deferred().resolve({ valid: true, exists: false }).promise();
        }

        if (documentType === 'ktp' && !/^\d{16}$/.test(identityNumber)) {
            identityNumberCheckState.signature = signature;
            identityNumberCheckState.exists = false;
            setIdentityNumberValidationState(false, 'Nomor identitas untuk KTP harus 16 digit angka.');
            return $.Deferred().resolve({ valid: false, exists: false }).promise();
        }

        if (!options.force && identityNumberCheckState.signature === signature) {
            return $.Deferred().resolve({ valid: !identityNumberCheckState.exists, exists: identityNumberCheckState.exists }).promise();
        }

        setIdentityNumberValidationState(true, 'Sedang mengecek nomor identitas...', true);

        return $.ajax({
            url: '{{ route('erm.pasiens.check-identity-number') }}',
            type: 'GET',
            dataType: 'json',
            data: {
                identity_document: documentType,
                identity_number: identityNumber,
                pasien_id: pasienId
            }
        }).done(function (response) {
            identityNumberCheckState.signature = signature;
            identityNumberCheckState.exists = !!response.exists;

            if (response.valid && !response.exists) {
                setIdentityNumberValidationState(true, 'Nomor identitas tersedia.');
                return;
            }

            const duplicateMessage = response?.pasien
                ? 'Nomor identitas sudah dipakai ' + response.pasien.nama + ' (RM: ' + response.pasien.id + ').'
                : (response.message || 'Nomor identitas sudah digunakan pasien lain.');

            setIdentityNumberValidationState(false, duplicateMessage);
        }).fail(function (xhr) {
            identityNumberCheckState.signature = null;
            identityNumberCheckState.exists = false;

            const responseMessage = xhr.responseJSON?.message || 'Gagal memvalidasi nomor identitas. Coba lagi.';
            setIdentityNumberValidationState(false, responseMessage, true);
        });
    }

    function syncReferralFields() {
        const referralType = $('#referral_type').val() || '';
        const referralPasienWrapper = $('#referral_pasien_wrapper');
        const referralEmployeeWrapper = $('#referral_employee_wrapper');
        const referralDokterWrapper = $('#referral_dokter_wrapper');
        const referralEventWrapper = $('#referral_event_wrapper');
        const referralDetailWrapper = $('#referral_detail_wrapper');
        const referralPasienInput = $('#referral_target_pasien_id');
        const referralEmployeeInput = $('#referral_employee_id');
        const referralDokterInput = $('#referral_dokter_id');
        const referralEventInput = $('#referral_event_id');
        const referralDetailInput = $('#referral_detail');
        const referralDetailSelect = $('#referral_detail_select');

        const isPasien = referralType === 'pasien';
        const isEmployee = referralType === 'employee';
        const isDokter = referralType === 'dokter';
        const isEvent = referralType === 'event';
        const needsDetail = ['social_media', 'marketplace', 'partnership', 'google_maps'].includes(referralType);

        referralPasienWrapper.toggleClass('d-none', !isPasien);
        referralEmployeeWrapper.toggleClass('d-none', !isEmployee);
        referralDokterWrapper.toggleClass('d-none', !isDokter);
        referralEventWrapper.toggleClass('d-none', !isEvent);
        referralDetailWrapper.toggleClass('d-none', !needsDetail);

        referralPasienInput.prop('required', isPasien);
        referralPasienInput.prop('disabled', !isPasien);
        referralEmployeeInput.prop('required', isEmployee);
        referralEmployeeInput.prop('disabled', !isEmployee);
        referralDokterInput.prop('required', isDokter);
        referralDokterInput.prop('disabled', !isDokter);
        referralEventInput.prop('required', isEvent);
        referralEventInput.prop('disabled', !isEvent);
        referralDetailInput.prop('required', needsDetail);
        referralDetailInput.prop('disabled', !needsDetail);
        referralDetailSelect.prop('required', needsDetail);
        referralDetailSelect.prop('disabled', !needsDetail);

        syncReferralDetailInput(referralType);

        if (!isPasien) {
            referralPasienInput.val(null).trigger('change');
        }

        if (!isEmployee) {
            referralEmployeeInput.val('').trigger('change');
        }

        if (!isDokter) {
            referralDokterInput.val('').trigger('change');
        }

        if (!isEvent) {
            referralEventInput.val('').trigger('change');
        }

        if (!needsDetail) {
            referralDetailInput.val('');
            referralDetailSelect.val('').trigger('change');
        }
    }

    function syncEmployeePatientField() {
        const isEmployeePatient = $('#is_employee_patient').val() === '1';
        const employeeWrapper = $('#employee_patient_wrapper');
        const employeeInput = $('#employee_id');

        employeeWrapper.toggleClass('d-none', !isEmployeePatient);
        employeeInput.prop('required', isEmployeePatient);
        employeeInput.prop('disabled', !isEmployeePatient);

        if (!isEmployeePatient) {
            employeeInput.val('').trigger('change');
        }
    }

    function normalizePhoneNumber(value) {
        const digits = (value || '').replace(/\D/g, '');

        if (!digits) {
            return '';
        }

        if (digits.startsWith('62')) {
            return digits.slice(2, 15);
        }

        if (digits.startsWith('0')) {
            return digits.slice(1, 14);
        }

        return digits.slice(0, 13);
    }

    function validatePhonePrefix(selector, isRequired) {
        const input = $(selector);
        const rawValue = input.val() || '';
        const normalizedValue = normalizePhoneNumber(rawValue);

        input.val(normalizedValue);

        if (!normalizedValue) {
            input[0].setCustomValidity(isRequired ? 'Nomor telepon wajib diisi.' : '');
            input.toggleClass('is-invalid', !!isRequired);
            return;
        }

        if (normalizedValue.startsWith('0')) {
            input[0].setCustomValidity('Isi nomor telepon tanpa 0 di depan.');
            input.addClass('is-invalid');
            return;
        }

        input[0].setCustomValidity('');
        input.removeClass('is-invalid');
    }

    function hydrateReferralPasienSelection() {
        const referralPasienInput = $('#referral_target_pasien_id');
        const selectedId = referralPasienInput.data('selected-id');
        const selectedText = referralPasienInput.data('selected-text');

        if (!selectedId || !selectedText) {
            return;
        }

        if (referralPasienInput.find("option[value='" + selectedId + "']").length === 0) {
            const option = new Option(selectedText, selectedId, true, true);
            referralPasienInput.append(option).trigger('change');
        } else {
            referralPasienInput.val(selectedId).trigger('change');
        }
    }

    $('#identity_document').on('change', function () {
        $('#identity_number').removeClass('is-invalid');
        $(this).next('.select2-container').find('.select2-selection').removeClass('is-invalid');
        resetIdentityNumberValidationState();
        syncIdentityInput();

        if (($('#identity_number').val() || '').trim()) {
            validateIdentityNumberImmediately({ force: true });
        }
    });

    $('#referral_type').on('change', function () {
        $('#referral_target_pasien_id').removeClass('is-invalid');
        $('#referral_employee_id').removeClass('is-invalid');
        $('#referral_dokter_id').removeClass('is-invalid');
        $('#referral_event_id').removeClass('is-invalid');
        $('#referral_detail').removeClass('is-invalid');
        $(this).next('.select2-container').find('.select2-selection').removeClass('is-invalid');
        syncReferralFields();
    });

    $('#referral_target_pasien_id, #referral_employee_id, #referral_dokter_id, #referral_event_id').on('change', function () {
        $(this).removeClass('is-invalid');
        $(this).next('.select2-container').find('.select2-selection').removeClass('is-invalid');
    });

    $('#is_employee_patient').on('change', function () {
        $('#employee_id').removeClass('is-invalid');
        $(this).next('.select2-container').find('.select2-selection').removeClass('is-invalid');
        syncEmployeePatientField();
    });

    $('#employee_id').on('change', function () {
        $(this).removeClass('is-invalid');
        $(this).next('.select2-container').find('.select2-selection').removeClass('is-invalid');
    });

    $('#referral_detail_select').select2({ width: '100%' });

    $('#referral_detail_select').on('change', function () {
        $('#referral_detail').val($(this).val() || '');
        $(this).removeClass('is-invalid');
        $(this).next('.select2-container').find('.select2-selection').removeClass('is-invalid');
    });

    $('#identity_number').on('input', function () {
        if (($('#identity_document').val() || 'ktp') === 'ktp') {
            this.value = this.value.replace(/\D/g, '').slice(0, 16);
            resetIdentityNumberValidationState();
            return;
        }

        this.value = this.value.toUpperCase().slice(0, 50);
        resetIdentityNumberValidationState();
    });

    $('#identity_number').on('blur', function () {
        validateIdentityNumberImmediately({ force: true });
    });

    $('#no_hp, #no_hp2').on('input', function () {
        this.value = normalizePhoneNumber(this.value);
        $(this).removeClass('is-invalid');
        this.setCustomValidity('');
    });

    $('#no_hp').on('blur', function () {
        validatePhonePrefix('#no_hp', true);
    });

    $('#no_hp2').on('blur', function () {
        validatePhonePrefix('#no_hp2', false);
    });

    syncIdentityInput();
    hydrateReferralPasienSelection();
    syncReferralFields();
    syncEmployeePatientField();

    $('#tanggal_lahir').on('change', function () {
        const selectedDate = new Date(this.value);
        const today = new Date();
        
        // Set time to 00:00:00 for accurate date-only comparison
        selectedDate.setHours(0, 0, 0, 0);
        today.setHours(0, 0, 0, 0);

        if (selectedDate > today) {
            Swal.fire({
                icon: 'error',
                title: 'Tanggal tidak valid',
                text: 'Tanggal lahir tidak boleh lebih dari hari ini!',
            }).then(() => {
                $(this).val('').focus();
            });
            invalidateDuplicateCheckIfNeeded();
            return;
        }

        invalidateDuplicateCheckIfNeeded();
        checkDuplicateNameBirthdate({ force: true });
    });

    $('#tanggal_lahir').on('blur', function () {
        invalidateDuplicateCheckIfNeeded();
        checkDuplicateNameBirthdate({ force: true });
    });

    $('#email').on('blur', function () {
        const email = $(this).val();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        if (email && !emailRegex.test(email)) {
            Swal.fire({
                icon: 'error',
                title: 'Email tidak valid',
                text: 'Harap masukkan alamat email yang benar!',
            }).then(() => {
                $(this).val('').focus();
            });
        }
    });

    // Initially disable all except province
        $('#regency').prop('disabled', true);
        $('#district').prop('disabled', true);
        $('#village').prop('disabled', true);

        $('#province').on('change', function() {
            let provinceID = $(this).val();

            // Reset and disable next dropdowns
            $('#regency').html('<option value="">Pilih Kabupaten</option>').prop('disabled', true);
            $('#district').html('<option value="">Pilih Kecamatan</option>').prop('disabled', true);
            $('#village').html('<option value="">Pilih Desa</option>').prop('disabled', true);

            if (provinceID) {
                $('#regency').html('<option value="">Loading...</option>');
                $.get('/get-regencies/' + provinceID, function(data) {
                    let options = '<option value="">Pilih Kabupaten</option>';
                    data.forEach(function(item) {
                        options += `<option value="${item.id}">${item.name}</option>`;
                    });
                    $('#regency').html(options).prop('disabled', false).trigger('change.select2');
                });
            }
        });

        $('#regency').on('change', function() {
            let regencyID = $(this).val();

            $('#district').html('<option value="">Pilih Kecamatan</option>').prop('disabled', true);
            $('#village').html('<option value="">Pilih Desa</option>').prop('disabled', true);

            if (regencyID) {
                $('#district').html('<option value="">Loading...</option>');
                $.get('/get-districts/' + regencyID, function(data) {
                    let options = '<option value="">Pilih Kecamatan</option>';
                    data.forEach(function(item) {
                        options += `<option value="${item.id}">${item.name}</option>`;
                    });
                    $('#district').html(options).prop('disabled', false).trigger('change.select2');
                });
            }
        });

        $('#district').on('change', function() {
            let districtID = $(this).val();

            $('#village').html('<option value="">Pilih Desa</option>').prop('disabled', true);

            if (districtID) {
                $('#village').html('<option value="">Loading...</option>');
                $.get('/get-villages/' + districtID, function(data) {
                    let options = '<option value="">Pilih Desa</option>';
                    data.forEach(function(item) {
                        options += `<option value="${item.id}">${item.name}</option>`;
                    });
                    $('#village').html(options).prop('disabled', false).trigger('change.select2');
                });
            }
        });

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    $('#pasien-form').on('submit', function (e) {
        e.preventDefault();

        const currentSignature = getDuplicateSignature();
        duplicateCheckState.pendingSubmit = true;

        if (currentSignature && duplicateCheckState.acknowledgedSignature !== currentSignature) {
            checkDuplicateNameBirthdate({ force: true }).done(function (response) {
                if (response.exists && duplicateCheckState.acknowledgedSignature !== currentSignature) {
                    promptDuplicatePatients(response);
                    return;
                }

                submitPasienForm();
            }).fail(function () {
                duplicateCheckState.pendingSubmit = false;
            });

            return;
        }

        submitPasienForm();
    });

    $('#confirm-new-patient-btn').on('click', function () {
        setDuplicateAcknowledged(true);
        duplicateModalAllowClose = true;
        $('#duplicatePasienModal').modal('hide');

        if (duplicateCheckState.pendingSubmit) {
            submitPasienForm();
        }
    });

    $(document).on('click', '.duplicate-patient-visit', function () {
        duplicateCheckState.pendingSubmit = false;
        duplicateModalAllowClose = true;
        $('#duplicatePasienModal').modal('hide');
    });

    $('#duplicatePasienModal').on('hide.bs.modal', function (event) {
        if (!duplicateModalAllowClose) {
            event.preventDefault();
        }
    });

    $('#duplicatePasienModal').on('hidden.bs.modal', function () {
        duplicateModalAllowClose = false;

        if (duplicateCheckState.acknowledgedSignature !== getDuplicateSignature()) {
            duplicateCheckState.promptedSignature = null;
        }
    });
@if($isEditing)
        $('#gender').val('{{ $pasien->gender }}').trigger('change');
        $('#agama').val('{{ $pasien->agama }}').trigger('change');
        $('#marital_status').val('{{ $pasien->marital_status }}').trigger('change');
        $('#pendidikan').val('{{ $pasien->pendidikan }}').trigger('change');
        $('#pekerjaan').val('{{ $pasien->pekerjaan }}').trigger('change');
        $('#gol_darah').val('{{ $pasien->gol_darah }}').trigger('change');
        $('#is_employee_patient').val('{{ !empty($pasien->employee_id) ? "1" : "0" }}').trigger('change');
        $('#employee_id').val('{{ $pasien->employee_id ?? "" }}').trigger('change');
    $('#referral_type').val('{{ $pasien->referral_type ?? "" }}').trigger('change');
        
        // Handle address selection
        @if($pasien->village && $pasien->village->district && $pasien->village->district->regency && $pasien->village->district->regency->province)
            (function() {
                const provId = '{{ $pasien->village->district->regency->province->id }}';
                const regId  = '{{ $pasien->village->district->regency->id }}';
                const distId = '{{ $pasien->village->district->id }}';
                const villId = '{{ $pasien->village_id }}';

                // set province and load regencies via AJAX, then select stored values in sequence
                $('#province').val(provId).trigger('change');

                $.get('/get-regencies/' + provId, function(regencies) {
                    let options = '<option value="">Pilih Kabupaten</option>';
                    regencies.forEach(function(item) {
                        options += `<option value="${item.id}">${item.name}</option>`;
                    });
                    $('#regency').html(options).prop('disabled', false).trigger('change.select2');

                    // select regency and load districts
                    $('#regency').val(regId).trigger('change');

                    $.get('/get-districts/' + regId, function(districts) {
                        let dOptions = '<option value="">Pilih Kecamatan</option>';
                        districts.forEach(function(item) {
                            dOptions += `<option value="${item.id}">${item.name}</option>`;
                        });
                        $('#district').html(dOptions).prop('disabled', false).trigger('change.select2');

                        // select district and load villages
                        $('#district').val(distId).trigger('change');

                        $.get('/get-villages/' + distId, function(villages) {
                            let vOptions = '<option value="">Pilih Desa</option>';
                            villages.forEach(function(item) {
                                vOptions += `<option value="${item.id}">${item.name}</option>`;
                            });
                            $('#village').html(vOptions).prop('disabled', false).trigger('change.select2');

                            // finally select village
                            $('#village').val(villId).trigger('change');
                        });
                    });
                });
            })();
        @endif
    @endif

    // ...existing code...

    // Update page title and form button text
    @if($isEditing)
        $('#pasien-form').find('.actions ul li').last().find('a').text('Update');
    @endif

    // IC (consent) is optional and can be captured later if needed

    // end document.ready
    });

</script>
@endsection