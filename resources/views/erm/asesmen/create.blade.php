@extends('layouts.erm.app')
@section('title', 'Asesmen Medis')
@section('content')
<style>
    /* Sembunyikan form wizard sebelum siap */
    #asesmen-form {
        visibility: hidden;
    }

    /* Tampilkan setelah wizard di-init */
    #asesmen-form.wizard-initialized {
        visibility: visible;
    }

    .is-invalid {
    border-color: red !important;    
    }

</style>
<div class="container-fluid">
    <!-- Page-Title -->
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="row">
                    <div class="col">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:void(0);">ERM</a></li>
                            <li class="breadcrumb-item active">Asesmen</li>
                        </ol>
                    </div><!--end col-->
                </div><!--end row-->                                                              
            </div><!--end page-title-box-->
        </div><!--end col-->
    </div><!--end row-->
    <!-- end page title end breadcrumb -->
    <div class="card">
    <div class="card-body">
        <div class="row">
            <div class="col-lg-6">
                <div class="mb-2 row">
                    <label class="col-sm-4 form-label text-end">No. RM</label>
                    <div class="col-sm-8">
                        <p class="form-control-plaintext"><strong>: {{ $visitation->pasien->id ?? '-' }}</strong></p>
                    </div>
                </div>
                <div class="mb-2 row">
                    <label class="col-sm-4 form-label text-end">Nama Pasien</label>
                    <div class="col-sm-8">
                        <p class="form-control-plaintext"><strong>: {{ $visitation->pasien->nama ?? '-' }}</strong></p>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="mb-2 row">
                    <label class="col-sm-4 form-label text-end">Tanggal Lahir</label>
                    <div class="col-sm-8">
                        <p class="form-control-plaintext">
                            <strong>: {{ $visitation->pasien->tanggal_lahir ? \Carbon\Carbon::parse($visitation->pasien->tanggal_lahir)->format('d-m-Y') : '-' }}</strong>
                        </p>
                    </div>
                </div>
                <div class="mb-2 row">
                    <label class="col-sm-4 form-label text-end">Jenis Kelamin</label>
                    <div class="col-sm-8">
                        <p class="form-control-plaintext">
                            <strong>: {{ ucfirst($visitation->pasien->gender ?? '-') }}</strong>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

                
    <div class="card">
        <div class="card-header bg-primary">
            <h4 class="card-title text-white">Asesmen Medis</h4>
        </div>
        <div class="card-body">

            @if ($errors->any())
                <div class="alert alert-danger">
                    <strong>Terjadi kesalahan:</strong>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <form id="asesmen-form" class="form-wizard-wrapper" action="{{ route('erm.pasiens.store') }}" method="POST">
                @csrf
                <h3>Personal Data</h3>
                    <fieldset>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="alergi">Riwayat Alergi (Nama Obat)</label>
                                    <select class="form-control select2" id="alergi" name="alergi[]" multiple="multiple" required>
                                        <option value="Paracetamol">Paracetamol</option>
                                        <option value="Amoxicillin">Amoxicillin</option>
                                        <option value="Ibuprofen">Ibuprofen</option>
                                        <option value="Cetirizine">Cetirizine</option>
                                        <option value="Aspirin">Aspirin</option>
                                        <option value="Metformin">Metformin</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="nama">Nama</label>
                                    <input type="text" class="form-control" id="nama" name="nama" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="nama">Nama</label>
                                    <input type="text" class="form-control" id="nama" name="nama" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                <label for="tanggal_lahir">Tanggal Lahir</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="tanggal_lahir" name="tanggal_lahir" placeholder="Select date" required>
                                        <div class="input-group-append">
                                            <span class="input-group-text">
                                                <i class="fas fa-calendar-alt"></i>
                                            </span>
                                        </div>
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
                                    <select class="form-control select2" id="agama" name="agama" required>>
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
                                    <select class="form-control select2" id="marital_status" name="marital_status" required>
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
                        <div class="col-md-6">
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
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="notes">Catatan Khusus</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                </fieldset>
                    
            </form>
        </div>
    </div>
</div><!-- container -->


@endsection
@section('scripts')
<script>  
   $(document).ready(function () {
    var wizard = $("#asesmen-form").steps({
    headerTag: "h3",
    bodyTag: "fieldset",
    transitionEffect: "slide",
    onStepChanged: function () {},
    onInit: function () {
        $('#asesmen-form').addClass('wizard-initialized');
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
            $('#asesmen-form').submit(); // 👈 THIS enables actual form submission
        }
    });
    
    $('.select2').select2({ width: '100%' });

    $('#tanggal_lahir').daterangepicker({
        singleDatePicker: true,
        showDropdowns: true,
        autoUpdateInput: false,
        locale: {
            format: 'YYYY-MM-DD',
            cancelLabel: 'Clear'
        }
    });

    $('#tanggal_lahir').on('apply.daterangepicker', function (ev, picker) {
        $(this).val(picker.startDate.format('YYYY-MM-DD'));
    });

    $('#tanggal_lahir').on('cancel.daterangepicker', function (ev, picker) {
        $(this).val('');
    });
});

</script>
@endsection
