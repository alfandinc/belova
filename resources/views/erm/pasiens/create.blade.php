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

</style>

@include('erm.partials.modal-daftarkunjungan')

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
                @if($isEditing && $pasien)
                    <input type="hidden" name="pasien_id" value="{{ $pasien->id }}">
                @endif
                <h3>Personal Data</h3>
                    <fieldset>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="nik">NIK</label>
                                    <input type="text" class="form-control" id="nik" name="nik" maxlength="16" value="{{ $pasien->nik ?? '' }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="nama">Nama</label>
                                    <input type="text" class="form-control" id="nama" name="nama" required value="{{ $pasien->nama ?? '' }}">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="tanggal_lahir">Tanggal Lahir</label>
                                    <div class="input-group">
                                        <input type="date" class="form-control" id="tanggal_lahir" name="tanggal_lahir" required value="{{ $pasien->tanggal_lahir ?? '' }}">
                                    </div>
                                </div>

                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="gender">Gender</label>
                                    <select class="form-control select2" id="gender" name="gender" required>
                                        <option value="" selected disabled>Select Gender</option>
                                        <option value="Laki-laki">Laki-laki</option>
                                        <option value="Perempuan">Perempuan</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="agama">Agama</label>
                                    <select class="form-control select2" id="agama" name="agama" >>
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
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="marital_status">Marital Status</label>
                                    <select class="form-control select2" id="marital_status" name="marital_status" >
                                        <option value="" selected disabled>Select Marital Status</option>
                                        <option value="Belum Menikah">Belum Menikah</option>
                                        <option value="Menikah">Menikah</option>
                                        <option value="Cerai Hidup">Cerai Hidup</option>
                                        <option value="Cerai Mati">Cerai Mati</option>
                                    </select>
                                </div>
                            </div>
                        </div>     
                    <div class="row">
                        <div class="col-md-6">
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
                        <div class="col-md-6">
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
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
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
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="status_pasien">Status Pasien</label>
                                <select class="form-control select2" id="status_pasien" name="status_pasien">
                                    <option value="Regular">Regular</option>
                                    <option value="VIP">VIP</option>
                                    <option value="Familia">Familia</option>
                                    <option value="Black Card">Black Card</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="notes">Catatan Khusus</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3">{{ $pasien->notes ?? '' }}</textarea>
                            </div>
                        </div>
                    </div>
                </fieldset>

                <h3>Address Data</h3>
                <fieldset>
                <div class="form-group">
                    <label for="alamat">ALAMAT</label>
                    <textarea class="form-control" id="alamat" name="alamat" rows="3" required>{{ $pasien->alamat ?? '' }}</textarea>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="province">PROVINSI</label>
                            <select class="form-control select2" id="province" name="province">
                    <option value="">Pilih Provinsi</option>
                    @foreach($provinces as $prov)
                        <option value="{{ $prov->id }}">{{ $prov->name }}</option>
                    @endforeach
                </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="regency">KABUPATEN</label>

                <select class="form-control select2" id="regency" name="regency">
                    <option value="">Pilih Kabupaten</option>
                </select>

                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="district">KECAMATAN</label>
                            <select class="form-control select2" id="district" name="district">
                    <option value="">Pilih Kecamatan</option>
                </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="village">DESA</label>
                            <select class="form-control select2" id="village" name="village">
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
                            <label for="no_hp">No Telepon 1</label>
                            <input type="number" class="form-control" id="no_hp" name="no_hp" required value="{{ $pasien->no_hp ?? '' }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="no_hp2">No Telepon 2</label>
                            <input type="number" class="form-control" id="no_hp2" name="no_hp2" value="{{ $pasien->no_hp2 ?? '' }}">
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

                <div class="form-group mt-4">
                    <div class="form-check d-flex align-items-start">
                        <input class="form-check-input mt-1 me-2" type="checkbox" id="terms" name="terms" required>
                        <label class="form-check-label" for="terms" style="text-align: justify;">
                            Saya menyatakan bahwa seluruh data yang saya isi adalah benar dan dapat dipertanggungjawabkan. 
                            Saya juga menyetujui bahwa data ini akan digunakan untuk keperluan pelayanan kesehatan 
                            sesuai dengan kebijakan yang berlaku.
                        </label>
                    </div>
                </div>
                </fieldset>
                    {{-- <button type="submit">Test Submit</button> --}}
            </form>
        </div>
    </div>
</div><!-- container -->
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
            $('#pasien-form').submit(); // 👈 THIS enables actual form submission
        }
    });
    
    $('.select2').select2({ width: '100%' });

    // VALIDASI INPUT

    $('#nama').on('input', function () {
        this.value = this.value.toUpperCase();
    });
    $('#alamat').on('input', function () {
        this.value = this.value.toUpperCase();
    });
    $('#nik').on('input', function () {
    // Remove non-digits and cut off at 16 characters
    this.value = this.value.replace(/\D/g, '').slice(0, 16);
    });
     $('#nik').on('blur', function () {
        const value = $(this).val();
        if (value.length > 0 && value.length !== 16) {
            Swal.fire({
                icon: 'warning',
                title: 'Invalid NIK',
                text: 'NIK must be exactly 16 digits!',
            }).then(() => {
                $(this).val('').focus();
            });
        }
    });

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
        }
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
            // Check if checkbox is checked
        if (!$('#terms').is(':checked')) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Persetujuan diperlukan',
                text: 'Anda harus menyetujui persetujuan sebelum mengirim data.',
            }).then(() => {
                $('#terms').focus();
            });
            return false;  // stop submission
        }

        // If checkbox is checked, proceed with your AJAX submit
        e.preventDefault();

        let form = $(this);
        let formData = new FormData(this);

        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            dataType: 'json', // ✅ ensure it's parsed

            success: function (response) {
                Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: 'Data berhasil disimpan.',
                confirmButtonText: 'OK'
            }).then((result) => {
                console.log('Swal pertama result:', result);
                if (result.value) {  // gunakan result.value, bukan result.isConfirmed
                    // swal kedua muncul di sini
                    Swal.fire({
                        title: 'Buka kunjungan?',
                        text: "Apakah Anda ingin membuka form kunjungan?",
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Ya',
                        cancelButtonText: 'Tidak'
                    }).then((result2) => {
                        console.log('Swal kedua response:', result2);
                        if (result2.value) {  // juga gunakan result2.value
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
                let errorMsg = "Terjadi kesalahan saat mengirim data.";

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
    });
@if($isEditing)
        $('#gender').val('{{ $pasien->gender }}').trigger('change');
        $('#agama').val('{{ $pasien->agama }}').trigger('change');
        $('#marital_status').val('{{ $pasien->marital_status }}').trigger('change');
        $('#pendidikan').val('{{ $pasien->pendidikan }}').trigger('change');
        $('#pekerjaan').val('{{ $pasien->pekerjaan }}').trigger('change');
        $('#gol_darah').val('{{ $pasien->gol_darah }}').trigger('change');
        $('#status_pasien').val('{{ $pasien->status_pasien ?? "Regular" }}').trigger('change');
        
        // Handle address selection
        @if($pasien->village && $pasien->village->district && $pasien->village->district->regency && $pasien->village->district->regency->province)
            // Set province and trigger loading of regencies
            $('#province').val('{{ $pasien->village->district->regency->province->id }}').trigger('change');
            
            // We need timeouts to ensure each cascading dropdown has loaded
            setTimeout(function() {
                $('#regency').val('{{ $pasien->village->district->regency->id }}').trigger('change');
                
                setTimeout(function() {
                    $('#district').val('{{ $pasien->village->district->id }}').trigger('change');
                    
                    setTimeout(function() {
                        $('#village').val('{{ $pasien->village_id }}').trigger('change');
                    }, 500);
                }, 500);
            }, 500);
        @endif
    @endif

    // ...existing code...

    // Update page title and form button text
    @if($isEditing)
        $('#pasien-form').find('.actions ul li').last().find('a').text('Update');
    @endif




});

</script>
@endsection