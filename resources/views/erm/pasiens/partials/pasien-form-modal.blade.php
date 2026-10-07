{{-- Pasien baru / edit pasien in a modal. Needs $provinces, $employees, $dokters (with user, spesialisasi) and $events. --}}
{{-- The form lives in a <template> and is copied into the modal on every open, so each create/edit starts clean. --}}
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

    /* Wizard inside the modal */
    #pasienFormModal .modal-body {
        min-height: 420px;
    }

    #pasienFormModal .wizard > .content {
        min-height: 360px;
    }

    /* The duplicate list opens on top of the form modal */
    #duplicatePasienModal {
        z-index: 1060;
    }
</style>

{{-- Row template for the duplicate-patient list; placeholders are replaced in JS. --}}
<template id="tpl-daftar-kunjungan-dropdown">
    @include('erm.partials.daftar-kunjungan-dropdown', ['size' => 'sm', 'pasienId' => '__PASIEN_ID__', 'pasienNama' => '__PASIEN_NAMA__'])
</template>

<template id="tpl-pasien-form">
            <form id="pasien-form" class="form-wizard-wrapper" action="{{ route('erm.pasiens.store') }}" method="POST">
                @csrf
                <input type="hidden" id="consent_pdf_path" name="consent_pdf_path" value="">
                <input type="hidden" id="duplicate_name_birthdate_acknowledged" name="duplicate_name_birthdate_acknowledged" value="0">
                <input type="hidden" name="pasien_id" id="pasien_form_pasien_id" value="">
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
                                            <option value="ktp">KTP</option>
                                            <option value="sim">SIM</option>
                                            <option value="paspor">Paspor</option>
                                            <option value="kia">KIA</option>
                                        </select>
                                        <span class="personal-field-note">Pilih dokumen utama pasien.</span>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6 col-lg-6">
                                    <div class="form-group mb-3 mb-md-4">
                                        <label class="personal-required-label" for="identity_number" id="identity_number_label">Nomor Identitas</label>
                                        <input type="text" class="form-control" id="identity_number" name="identity_number" maxlength="50" required value="">
                                        <small class="form-text text-muted" id="identity_number_help">KTP wajib 16 digit. Dokumen lain boleh memakai nomor sesuai dokumen.</small>
                                        <small class="form-text d-none" id="identity_number_validation"></small>
                                    </div>
                                </div>
                                <div class="col-12 col-lg-6">
                                    <div class="form-group mb-3 mb-md-4">
                                        <label class="personal-required-label" for="nama">Nama</label>
                                        <input type="text" class="form-control" id="nama" name="nama" required value="">
                                        <span class="personal-field-note">Nama pasien akan otomatis disimpan dalam huruf kapital.</span>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6 col-lg-6">
                                    <div class="form-group mb-3 mb-md-0">
                                        <label class="personal-required-label" for="tanggal_lahir">Tanggal Lahir</label>
                                        <div class="input-group">
                                            <input type="date" class="form-control" id="tanggal_lahir" name="tanggal_lahir" required value="">
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
                                        <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Contoh: pasien lebih mudah dikenali dengan nama panggilan atau ciri tertentu"></textarea>
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
                                            <option value="0" selected>Bukan karyawan</option>
                                            <option value="1">Ya, pasien adalah karyawan</option>
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
                                                <option value="{{ $employee->id }}">
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
                    <textarea class="form-control" id="alamat" name="alamat" rows="3" required></textarea>
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
                            <label class="personal-required-label" for="village">DESA / KELURAHAN</label>
                            <select class="form-control select2" id="village" name="village" required>
                    <option value="">Pilih Desa</option>
                </select>
                            <small class="form-text text-muted">Bisa langsung ketik nama desa (min. 3 huruf); provinsi, kabupaten dan kecamatan terisi otomatis.</small>
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
                                <input type="text" inputmode="numeric" class="form-control" id="no_hp" name="no_hp" maxlength="13" required value="" placeholder="818896869">
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
                                <input type="text" inputmode="numeric" class="form-control" id="no_hp2" name="no_hp2" maxlength="13" value="" placeholder="818896869">
                            </div>
                            <small class="form-text text-muted">Jika diisi, cukup masukkan angka setelah kode negara.</small>
                        </div>
                    </div>    
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="istagram">Instagram</label>
                            <input type="text" class="form-control" id="instagram" name="instagram" value="">
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
                                    <option value="walk_in">Walk-in</option>
                                    <option value="pasien">Pasien</option>
                                    <option value="dokter">Dokter</option>
                                    <option value="employee">Karyawan</option>
                                    <option value="social_media">Social Media</option>
                                    <option value="marketplace">Marketplace</option>
                                    <option value="event">Event</option>
                                    <option value="website">Website</option>
                                    <option value="partnership">B2B Partnership</option>
                                    <option value="google_maps">Google Maps</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="d-none" id="referral_pasien_wrapper">
                                <div class="form-group">
                                    <label class="personal-required-label" for="referral_target_pasien_id">Pasien Referral</label>
                                    <select class="form-control" id="referral_target_pasien_id" name="referral_target_pasien_id">
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
                                            <option value="{{ $employee->id }}">
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
                                            <option value="{{ $dokter->id }}">
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
                                            <option value="{{ $event->id }}">
                                                {{ $event->nama_event }}@if($event->kode_event) ({{ $event->kode_event }})@endif
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="d-none" id="referral_detail_wrapper">
                                <div class="form-group">
                                    <label class="personal-required-label" for="referral_detail">Detail Referral</label>
                                    <input type="text" class="form-control" id="referral_detail" name="referral_detail" maxlength="255" value="" placeholder="Isi detail referral bila diperlukan.">
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
            </form>
</template>

<div class="modal fade" id="pasienFormModal" tabindex="-1" role="dialog" aria-labelledby="pasienFormModalLabel" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title text-white" id="pasienFormModalLabel">Data Pasien Baru</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body" id="pasienFormBody"></div>
        </div>
    </div>
</div>

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
