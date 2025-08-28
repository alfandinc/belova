@extends('layouts.erm.app')
@section('title', 'ERM | Rawat Jalan')
@section('navbar')
    @include('layouts.erm.navbar')
@endsection  

@section('content')

<style>
/* Status Pasien Icons in DataTable */
.dataTables_wrapper .status-pasien-icon,
.dataTables_wrapper .status-akses-icon {
    display: inline-flex !important;
    align-items: center;
    justify-content: center;
    vertical-align: middle;
    margin-right: 8px;
}

.dataTables_wrapper td {
    vertical-align: middle;
}

/* Smooth blinking animation for lab and tindakan icons */
.blinking {
    animation: blinking-animation 1s linear infinite;
}
@keyframes blinking-animation {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.2;
    }
}

/* Statistics Cards Styling */
.stat-card {
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15) !important;
}

.stat-icon {
    transition: transform 0.2s ease-in-out;
}

.stat-card:hover .stat-icon {
    transform: scale(1.1);
}

.stat-number {
    font-size: 1.75rem;
    font-weight: 700;
    line-height: 1;
}

@media (max-width: 768px) {
    .stat-number {
        font-size: 1.5rem;
    }
}
</style>

@include('erm.partials.modal-reschedule')
<div class="modal fade" id="modalKonfirmasi" tabindex="-1" role="dialog" aria-labelledby="modalKonfirmasiTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalKonfirmasiTitle">Konfirmasi Kunjungan</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="konfirmasi-nama-pasien">Nama Pasien</label>
                    <input type="text" class="form-control" id="konfirmasi-nama-pasien" readonly>
                </div>
                <div class="form-group">
                    <label for="konfirmasi-no-telepon">Nomor Telepon</label>
                    <input type="text" class="form-control" id="konfirmasi-no-telepon">
                </div>
                <div class="form-group">
                    <label for="konfirmasi-pesan">Template Pesan</label>
                    <textarea class="form-control" id="konfirmasi-pesan" rows="5">Halo %PANGGILAN% %NAMA_PASIEN%, 

Kami ingin mengingatkan jadwal kunjungan Anda di Klinik Belova:
Tanggal: %TANGGAL_KUNJUNGAN%
Dokter: %DOKTER%
Nomor Antrian: %NO_ANTRIAN%

Mohon konfirmasi kehadiran Anda. 
Terima kasih.
</textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-primary" id="btn-kirim-wa">Kirim WhatsApp</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Screening Batuk -->
<div class="modal fade" id="modalScreeningBatuk" tabindex="-1" role="dialog" aria-labelledby="modalScreeningBatukTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title text-white" id="modalScreeningBatukTitle">
                    <i class="fas fa-lungs mr-2"></i><span id="screening-modal-title">Screening Batuk</span>
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                <form id="form-screening-batuk">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>
                        <strong>Penting:</strong> Harap isi screening batuk sebelum melanjutkan ke asesmen perawat.
                    </div>
                    
                    <!-- Sesi Gejala -->
                    <h6 class="text-primary mb-3"><i class="fas fa-thermometer-half mr-2"></i><strong>GEJALA</strong></h6>
                    <div class="table-responsive mb-4">
                        <table class="table table-borderless mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="border-0 font-weight-bold text-center" style="width: 8%;">No</th>
                                    <th class="border-0 font-weight-bold" style="width: 62%;">Pertanyaan</th>
                                    <th class="border-0 font-weight-bold text-center" style="width: 15%;">Ya</th>
                                    <th class="border-0 font-weight-bold text-center" style="width: 15%;">Tidak</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="text-center align-middle font-weight-bold">1</td>
                                    <td class="align-middle">Apakah Saudara/Saudari saat ini demam/Badan Panas?</td>
                                    <td class="text-center align-middle">
                                        <div class="custom-control custom-radio">
                                            <input type="radio" id="demam_badan_panas_ya" name="demam_badan_panas" value="ya" class="custom-control-input" required>
                                            <label class="custom-control-label" for="demam_badan_panas_ya"></label>
                                        </div>
                                    </td>
                                    <td class="text-center align-middle">
                                        <div class="custom-control custom-radio">
                                            <input type="radio" id="demam_badan_panas_tidak" name="demam_badan_panas" value="tidak" class="custom-control-input" required checked>
                                            <label class="custom-control-label" for="demam_badan_panas_tidak"></label>
                                        </div>
                                    </td>
                                </tr>
                                <tr class="bg-light">
                                    <td class="text-center align-middle font-weight-bold">2</td>
                                    <td class="align-middle">Apakah Saudara/Saudari saat ini batuk-pilek?</td>
                                    <td class="text-center align-middle">
                                        <div class="custom-control custom-radio">
                                            <input type="radio" id="batuk_pilek_ya" name="batuk_pilek" value="ya" class="custom-control-input" required>
                                            <label class="custom-control-label" for="batuk_pilek_ya"></label>
                                        </div>
                                    </td>
                                    <td class="text-center align-middle">
                                        <div class="custom-control custom-radio">
                                            <input type="radio" id="batuk_pilek_tidak" name="batuk_pilek" value="tidak" class="custom-control-input" required checked>
                                            <label class="custom-control-label" for="batuk_pilek_tidak"></label>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-center align-middle font-weight-bold">3</td>
                                    <td class="align-middle">Apakah Saudara/Saudari saat ini sesak nafas?</td>
                                    <td class="text-center align-middle">
                                        <div class="custom-control custom-radio">
                                            <input type="radio" id="sesak_nafas_ya" name="sesak_nafas" value="ya" class="custom-control-input" required>
                                            <label class="custom-control-label" for="sesak_nafas_ya"></label>
                                        </div>
                                    </td>
                                    <td class="text-center align-middle">
                                        <div class="custom-control custom-radio">
                                            <input type="radio" id="sesak_nafas_tidak" name="sesak_nafas" value="tidak" class="custom-control-input" required checked>
                                            <label class="custom-control-label" for="sesak_nafas_tidak"></label>
                                        </div>
                                    </td>
                                </tr>
                                <tr class="bg-light">
                                    <td class="text-center align-middle font-weight-bold">4</td>
                                    <td class="align-middle">Apakah Saudara/Saudari pernah kontak dengan pasien covid-19?</td>
                                    <td class="text-center align-middle">
                                        <div class="custom-control custom-radio">
                                            <input type="radio" id="kontak_covid_ya" name="kontak_covid" value="ya" class="custom-control-input" required>
                                            <label class="custom-control-label" for="kontak_covid_ya"></label>
                                        </div>
                                    </td>
                                    <td class="text-center align-middle">
                                        <div class="custom-control custom-radio">
                                            <input type="radio" id="kontak_covid_tidak" name="kontak_covid" value="tidak" class="custom-control-input" required checked>
                                            <label class="custom-control-label" for="kontak_covid_tidak"></label>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-center align-middle font-weight-bold">5</td>
                                    <td class="align-middle">Apakah Saudara/Saudari pernah berpergian ke luar negeri?</td>
                                    <td class="text-center align-middle">
                                        <div class="custom-control custom-radio">
                                            <input type="radio" id="perjalanan_luar_negeri_ya" name="perjalanan_luar_negeri" value="ya" class="custom-control-input" required>
                                            <label class="custom-control-label" for="perjalanan_luar_negeri_ya"></label>
                                        </div>
                                    </td>
                                    <td class="text-center align-middle">
                                        <div class="custom-control custom-radio">
                                            <input type="radio" id="perjalanan_luar_negeri_tidak" name="perjalanan_luar_negeri" value="tidak" class="custom-control-input" required checked>
                                            <label class="custom-control-label" for="perjalanan_luar_negeri_tidak"></label>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Sesi Faktor Resiko -->
                    <h6 class="text-warning mb-3"><i class="fas fa-exclamation-triangle mr-2"></i><strong>FAKTOR RESIKO</strong></h6>
                    <div class="table-responsive mb-4">
                        <table class="table table-borderless mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="border-0 font-weight-bold text-center" style="width: 8%;">No</th>
                                    <th class="border-0 font-weight-bold" style="width: 62%;">Pertanyaan</th>
                                    <th class="border-0 font-weight-bold text-center" style="width: 15%;">Ya</th>
                                    <th class="border-0 font-weight-bold text-center" style="width: 15%;">Tidak</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="text-center align-middle font-weight-bold">1</td>
                                    <td class="align-middle">Riwayat perjalanan keluar negeri atau kota-kota terjangkit dalam waktu 14 hari sebelum timbul gejala</td>
                                    <td class="text-center align-middle">
                                        <div class="custom-control custom-radio">
                                            <input type="radio" id="riwayat_perjalanan_ya" name="riwayat_perjalanan" value="ya" class="custom-control-input" required>
                                            <label class="custom-control-label" for="riwayat_perjalanan_ya"></label>
                                        </div>
                                    </td>
                                    <td class="text-center align-middle">
                                        <div class="custom-control custom-radio">
                                            <input type="radio" id="riwayat_perjalanan_tidak" name="riwayat_perjalanan" value="tidak" class="custom-control-input" required checked>
                                            <label class="custom-control-label" for="riwayat_perjalanan_tidak"></label>
                                        </div>
                                    </td>
                                </tr>
                                <tr class="bg-light">
                                    <td class="text-center align-middle font-weight-bold">2</td>
                                    <td class="align-middle">Riwayat kontak erat dengan kasus konfirmasi Covid-19</td>
                                    <td class="text-center align-middle">
                                        <div class="custom-control custom-radio">
                                            <input type="radio" id="kontak_erat_covid_ya" name="kontak_erat_covid" value="ya" class="custom-control-input" required>
                                            <label class="custom-control-label" for="kontak_erat_covid_ya"></label>
                                        </div>
                                    </td>
                                    <td class="text-center align-middle">
                                        <div class="custom-control custom-radio">
                                            <input type="radio" id="kontak_erat_covid_tidak" name="kontak_erat_covid" value="tidak" class="custom-control-input" required checked>
                                            <label class="custom-control-label" for="kontak_erat_covid_tidak"></label>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-center align-middle font-weight-bold">3</td>
                                    <td class="align-middle">Bekerja atau mengunjungi fasilitas kesehatan yang berhubungan dengan pasien konfirmasi covid-19</td>
                                    <td class="text-center align-middle">
                                        <div class="custom-control custom-radio">
                                            <input type="radio" id="faskes_covid_ya" name="faskes_covid" value="ya" class="custom-control-input" required>
                                            <label class="custom-control-label" for="faskes_covid_ya"></label>
                                        </div>
                                    </td>
                                    <td class="text-center align-middle">
                                        <div class="custom-control custom-radio">
                                            <input type="radio" id="faskes_covid_tidak" name="faskes_covid" value="tidak" class="custom-control-input" required checked>
                                            <label class="custom-control-label" for="faskes_covid_tidak"></label>
                                        </div>
                                    </td>
                                </tr>
                                <tr class="bg-light">
                                    <td class="text-center align-middle font-weight-bold">4</td>
                                    <td class="align-middle">Memiliki riwayat kontak dengan hewan penular (jika hewan penular sudah teridentifikasi)</td>
                                    <td class="text-center align-middle">
                                        <div class="custom-control custom-radio">
                                            <input type="radio" id="kontak_hewan_ya" name="kontak_hewan" value="ya" class="custom-control-input" required>
                                            <label class="custom-control-label" for="kontak_hewan_ya"></label>
                                        </div>
                                    </td>
                                    <td class="text-center align-middle">
                                        <div class="custom-control custom-radio">
                                            <input type="radio" id="kontak_hewan_tidak" name="kontak_hewan" value="tidak" class="custom-control-input" required checked>
                                            <label class="custom-control-label" for="kontak_hewan_tidak"></label>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-center align-middle font-weight-bold">5</td>
                                    <td class="align-middle">Memiliki demam atau riwayat demam</td>
                                    <td class="text-center align-middle">
                                        <div class="custom-control custom-radio">
                                            <input type="radio" id="riwayat_demam_ya" name="riwayat_demam" value="ya" class="custom-control-input" required>
                                            <label class="custom-control-label" for="riwayat_demam_ya"></label>
                                        </div>
                                    </td>
                                    <td class="text-center align-middle">
                                        <div class="custom-control custom-radio">
                                            <input type="radio" id="riwayat_demam_tidak" name="riwayat_demam" value="tidak" class="custom-control-input" required checked>
                                            <label class="custom-control-label" for="riwayat_demam_tidak"></label>
                                        </div>
                                    </td>
                                </tr>
                                <tr class="bg-light">
                                    <td class="text-center align-middle font-weight-bold">6</td>
                                    <td class="align-middle">Memiliki riwayat perjalanan keluar negeri atau kontak dengan orang yang memiliki riwayat perjalanan keluar negeri</td>
                                    <td class="text-center align-middle">
                                        <div class="custom-control custom-radio">
                                            <input type="radio" id="riwayat_kontak_luar_negeri_ya" name="riwayat_kontak_luar_negeri" value="ya" class="custom-control-input" required>
                                            <label class="custom-control-label" for="riwayat_kontak_luar_negeri_ya"></label>
                                        </div>
                                    </td>
                                    <td class="text-center align-middle">
                                        <div class="custom-control custom-radio">
                                            <input type="radio" id="riwayat_kontak_luar_negeri_tidak" name="riwayat_kontak_luar_negeri" value="tidak" class="custom-control-input" required checked>
                                            <label class="custom-control-label" for="riwayat_kontak_luar_negeri_tidak"></label>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Sesi Tools Screening Batuk -->
                    <h6 class="text-success mb-3"><i class="fas fa-lungs mr-2"></i><strong>TOOLS SCREENING BATUK</strong></h6>
                    <div class="table-responsive mb-4">
                        <table class="table table-borderless mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="border-0 font-weight-bold text-center" style="width: 8%;">No</th>
                                    <th class="border-0 font-weight-bold" style="width: 62%;">Pertanyaan</th>
                                    <th class="border-0 font-weight-bold text-center" style="width: 15%;">Ya</th>
                                    <th class="border-0 font-weight-bold text-center" style="width: 15%;">Tidak</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="text-center align-middle font-weight-bold">1</td>
                                    <td class="align-middle">Apakah pernah riwayat pengobatan TB?</td>
                                    <td class="text-center align-middle">
                                        <div class="custom-control custom-radio">
                                            <input type="radio" id="riwayat_pengobatan_tb_ya" name="riwayat_pengobatan_tb" value="ya" class="custom-control-input" required>
                                            <label class="custom-control-label" for="riwayat_pengobatan_tb_ya"></label>
                                        </div>
                                    </td>
                                    <td class="text-center align-middle">
                                        <div class="custom-control custom-radio">
                                            <input type="radio" id="riwayat_pengobatan_tb_tidak" name="riwayat_pengobatan_tb" value="tidak" class="custom-control-input" required checked>
                                            <label class="custom-control-label" for="riwayat_pengobatan_tb_tidak"></label>
                                        </div>
                                    </td>
                                </tr>
                                <tr class="bg-light">
                                    <td class="text-center align-middle font-weight-bold">2</td>
                                    <td class="align-middle">Apakah sekarang sedang pengobatan TB?</td>
                                    <td class="text-center align-middle">
                                        <div class="custom-control custom-radio">
                                            <input type="radio" id="sedang_pengobatan_tb_ya" name="sedang_pengobatan_tb" value="ya" class="custom-control-input" required>
                                            <label class="custom-control-label" for="sedang_pengobatan_tb_ya"></label>
                                        </div>
                                    </td>
                                    <td class="text-center align-middle">
                                        <div class="custom-control custom-radio">
                                            <input type="radio" id="sedang_pengobatan_tb_tidak" name="sedang_pengobatan_tb" value="tidak" class="custom-control-input" required checked>
                                            <label class="custom-control-label" for="sedang_pengobatan_tb_tidak"></label>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-center align-middle font-weight-bold">3</td>
                                    <td class="align-middle">Adakah batuk dan demam/riwayat demam?</td>
                                    <td class="text-center align-middle">
                                        <div class="custom-control custom-radio">
                                            <input type="radio" id="batuk_demam_ya" name="batuk_demam" value="ya" class="custom-control-input" required>
                                            <label class="custom-control-label" for="batuk_demam_ya"></label>
                                        </div>
                                    </td>
                                    <td class="text-center align-middle">
                                        <div class="custom-control custom-radio">
                                            <input type="radio" id="batuk_demam_tidak" name="batuk_demam" value="tidak" class="custom-control-input" required checked>
                                            <label class="custom-control-label" for="batuk_demam_tidak"></label>
                                        </div>
                                    </td>
                                </tr>
                                <tr class="bg-light">
                                    <td class="text-center align-middle font-weight-bold">4</td>
                                    <td class="align-middle">Nafsu makan menurun</td>
                                    <td class="text-center align-middle">
                                        <div class="custom-control custom-radio">
                                            <input type="radio" id="nafsu_makan_menurun_ya" name="nafsu_makan_menurun" value="ya" class="custom-control-input" required>
                                            <label class="custom-control-label" for="nafsu_makan_menurun_ya"></label>
                                        </div>
                                    </td>
                                    <td class="text-center align-middle">
                                        <div class="custom-control custom-radio">
                                            <input type="radio" id="nafsu_makan_menurun_tidak" name="nafsu_makan_menurun" value="tidak" class="custom-control-input" required checked>
                                            <label class="custom-control-label" for="nafsu_makan_menurun_tidak"></label>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-center align-middle font-weight-bold">5</td>
                                    <td class="align-middle">BB turun</td>
                                    <td class="text-center align-middle">
                                        <div class="custom-control custom-radio">
                                            <input type="radio" id="bb_turun_ya" name="bb_turun" value="ya" class="custom-control-input" required>
                                            <label class="custom-control-label" for="bb_turun_ya"></label>
                                        </div>
                                    </td>
                                    <td class="text-center align-middle">
                                        <div class="custom-control custom-radio">
                                            <input type="radio" id="bb_turun_tidak" name="bb_turun" value="tidak" class="custom-control-input" required checked>
                                            <label class="custom-control-label" for="bb_turun_tidak"></label>
                                        </div>
                                    </td>
                                </tr>
                                <tr class="bg-light">
                                    <td class="text-center align-middle font-weight-bold">6</td>
                                    <td class="align-middle">Keringat malam</td>
                                    <td class="text-center align-middle">
                                        <div class="custom-control custom-radio">
                                            <input type="radio" id="keringat_malam_ya" name="keringat_malam" value="ya" class="custom-control-input" required>
                                            <label class="custom-control-label" for="keringat_malam_ya"></label>
                                        </div>
                                    </td>
                                    <td class="text-center align-middle">
                                        <div class="custom-control custom-radio">
                                            <input type="radio" id="keringat_malam_tidak" name="keringat_malam" value="tidak" class="custom-control-input" required checked>
                                            <label class="custom-control-label" for="keringat_malam_tidak"></label>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-center align-middle font-weight-bold">7</td>
                                    <td class="align-middle">Sesak nafas</td>
                                    <td class="text-center align-middle">
                                        <div class="custom-control custom-radio">
                                            <input type="radio" id="sesak_nafas_tb_ya" name="sesak_nafas_tb" value="ya" class="custom-control-input" required>
                                            <label class="custom-control-label" for="sesak_nafas_tb_ya"></label>
                                        </div>
                                    </td>
                                    <td class="text-center align-middle">
                                        <div class="custom-control custom-radio">
                                            <input type="radio" id="sesak_nafas_tb_tidak" name="sesak_nafas_tb" value="tidak" class="custom-control-input" required checked>
                                            <label class="custom-control-label" for="sesak_nafas_tb_tidak"></label>
                                        </div>
                                    </td>
                                </tr>
                                <tr class="bg-light">
                                    <td class="text-center align-middle font-weight-bold">8</td>
                                    <td class="align-middle">Kontak erat dengan pasien TB</td>
                                    <td class="text-center align-middle">
                                        <div class="custom-control custom-radio">
                                            <input type="radio" id="kontak_erat_tb_ya" name="kontak_erat_tb" value="ya" class="custom-control-input" required>
                                            <label class="custom-control-label" for="kontak_erat_tb_ya"></label>
                                        </div>
                                    </td>
                                    <td class="text-center align-middle">
                                        <div class="custom-control custom-radio">
                                            <input type="radio" id="kontak_erat_tb_tidak" name="kontak_erat_tb" value="tidak" class="custom-control-input" required checked>
                                            <label class="custom-control-label" for="kontak_erat_tb_tidak"></label>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-center align-middle font-weight-bold">9</td>
                                    <td class="align-middle">Ada hasil rontgen pneumonia/mendukung TB</td>
                                    <td class="text-center align-middle">
                                        <div class="custom-control custom-radio">
                                            <input type="radio" id="hasil_rontgen_ya" name="hasil_rontgen" value="ya" class="custom-control-input" required>
                                            <label class="custom-control-label" for="hasil_rontgen_ya"></label>
                                        </div>
                                    </td>
                                    <td class="text-center align-middle">
                                        <div class="custom-control custom-radio">
                                            <input type="radio" id="hasil_rontgen_tidak" name="hasil_rontgen" value="tidak" class="custom-control-input" required checked>
                                            <label class="custom-control-label" for="hasil_rontgen_tidak"></label>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="form-group">
                        <label for="catatan_screening" class="font-weight-bold">Catatan Tambahan (Opsional):</label>
                        <textarea class="form-control" id="catatan_screening" name="catatan" rows="3" placeholder="Masukkan catatan tambahan jika ada..."></textarea>
                    </div>

                    <input type="hidden" id="screening-visitation-id" name="visitation_id">
                    <input type="hidden" id="screening-edit-mode" value="false">
                    <input type="hidden" id="screening-id" name="screening_id">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times mr-1"></i>Batal
                </button>
                <button type="button" class="btn btn-primary" id="btn-simpan-screening">
                    <i class="fas fa-save mr-1"></i><span id="screening-btn-text">Simpan & Lanjutkan</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal View Screening Batuk -->
<div class="modal fade" id="modalViewScreeningBatuk" tabindex="-1" role="dialog" aria-labelledby="modalViewScreeningBatukTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="modalViewScreeningBatukTitle">
                    <i class="fas fa-lungs mr-2"></i>Data Screening Batuk
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                <!-- Sesi Gejala -->
                <h6 class="text-primary mb-3"><i class="fas fa-thermometer-half mr-2"></i><strong>GEJALA</strong></h6>
                <div class="table-responsive mb-4">
                    <table class="table table-bordered">
                        <tbody>
                            <tr>
                                <td class="font-weight-bold" style="width: 70%;">Apakah Saudara/Saudari saat ini demam/Badan Panas?</td>
                                <td class="text-center" style="width: 30%;"><span id="view-demam-badan-panas"></span></td>
                            </tr>
                            <tr class="bg-light">
                                <td class="font-weight-bold">Apakah Saudara/Saudari saat ini batuk-pilek?</td>
                                <td class="text-center"><span id="view-batuk-pilek"></span></td>
                            </tr>
                            <tr>
                                <td class="font-weight-bold">Apakah Saudara/Saudari saat ini sesak nafas?</td>
                                <td class="text-center"><span id="view-sesak-nafas"></span></td>
                            </tr>
                            <tr class="bg-light">
                                <td class="font-weight-bold">Apakah Saudara/Saudari pernah kontak dengan pasien covid-19?</td>
                                <td class="text-center"><span id="view-kontak-covid"></span></td>
                            </tr>
                            <tr>
                                <td class="font-weight-bold">Apakah Saudara/Saudari pernah berpergian ke luar negeri?</td>
                                <td class="text-center"><span id="view-perjalanan-luar-negeri"></span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Sesi Faktor Resiko -->
                <h6 class="text-warning mb-3"><i class="fas fa-exclamation-triangle mr-2"></i><strong>FAKTOR RESIKO</strong></h6>
                <div class="table-responsive mb-4">
                    <table class="table table-bordered">
                        <tbody>
                            <tr>
                                <td class="font-weight-bold" style="width: 70%;">Riwayat perjalanan keluar negeri atau kota-kota terjangkit dalam waktu 14 hari sebelum timbul gejala</td>
                                <td class="text-center" style="width: 30%;"><span id="view-riwayat-perjalanan"></span></td>
                            </tr>
                            <tr class="bg-light">
                                <td class="font-weight-bold">Riwayat kontak erat dengan kasus konfirmasi Covid-19</td>
                                <td class="text-center"><span id="view-kontak-erat-covid"></span></td>
                            </tr>
                            <tr>
                                <td class="font-weight-bold">Bekerja atau mengunjungi fasilitas kesehatan yang berhubungan dengan pasien konfirmasi covid-19</td>
                                <td class="text-center"><span id="view-faskes-covid"></span></td>
                            </tr>
                            <tr class="bg-light">
                                <td class="font-weight-bold">Memiliki riwayat kontak dengan hewan penular (jika hewan penular sudah teridentifikasi)</td>
                                <td class="text-center"><span id="view-kontak-hewan"></span></td>
                            </tr>
                            <tr>
                                <td class="font-weight-bold">Memiliki demam atau riwayat demam</td>
                                <td class="text-center"><span id="view-riwayat-demam"></span></td>
                            </tr>
                            <tr class="bg-light">
                                <td class="font-weight-bold">Memiliki riwayat perjalanan keluar negeri atau kontak dengan orang yang memiliki riwayat perjalanan keluar negeri</td>
                                <td class="text-center"><span id="view-riwayat-kontak-luar-negeri"></span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Sesi Tools Screening Batuk -->
                <h6 class="text-success mb-3"><i class="fas fa-lungs mr-2"></i><strong>TOOLS SCREENING BATUK</strong></h6>
                <div class="table-responsive mb-4">
                    <table class="table table-bordered">
                        <tbody>
                            <tr>
                                <td class="font-weight-bold" style="width: 70%;">Apakah pernah riwayat pengobatan TB?</td>
                                <td class="text-center" style="width: 30%;"><span id="view-riwayat-pengobatan-tb"></span></td>
                            </tr>
                            <tr class="bg-light">
                                <td class="font-weight-bold">Apakah sekarang sedang pengobatan TB?</td>
                                <td class="text-center"><span id="view-sedang-pengobatan-tb"></span></td>
                            </tr>
                            <tr>
                                <td class="font-weight-bold">Adakah batuk dan demam/riwayat demam?</td>
                                <td class="text-center"><span id="view-batuk-demam"></span></td>
                            </tr>
                            <tr class="bg-light">
                                <td class="font-weight-bold">Nafsu makan menurun</td>
                                <td class="text-center"><span id="view-nafsu-makan-menurun"></span></td>
                            </tr>
                            <tr>
                                <td class="font-weight-bold">BB turun</td>
                                <td class="text-center"><span id="view-bb-turun"></span></td>
                            </tr>
                            <tr class="bg-light">
                                <td class="font-weight-bold">Keringat malam</td>
                                <td class="text-center"><span id="view-keringat-malam"></span></td>
                            </tr>
                            <tr>
                                <td class="font-weight-bold">Sesak nafas</td>
                                <td class="text-center"><span id="view-sesak-nafas-tb"></span></td>
                            </tr>
                            <tr class="bg-light">
                                <td class="font-weight-bold">Kontak erat dengan pasien TB</td>
                                <td class="text-center"><span id="view-kontak-erat-tb"></span></td>
                            </tr>
                            <tr>
                                <td class="font-weight-bold">Ada hasil rontgen pneumonia/mendukung TB</td>
                                <td class="text-center"><span id="view-hasil-rontgen"></span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Catatan -->
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-info mb-2"><i class="fas fa-sticky-note mr-2"></i>Catatan Tambahan</h6>
                        <p id="view-catatan" class="border p-3 bg-light">-</p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-info mb-2"><i class="fas fa-clock mr-2"></i>Waktu Pengisian</h6>
                        <p id="view-created-at" class="border p-3 bg-light">-</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times mr-1"></i>Tutup
                </button>
                <button type="button" class="btn btn-warning" id="btn-edit-screening">
                    <i class="fas fa-edit mr-1"></i>Edit Screening
                </button>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid">
    <!-- Page-Title -->
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="row">
                    <div class="col">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:void(0);">ERM</a></li>
                            <li class="breadcrumb-item active">Rawat Jalan</li>
                        </ol>
                    </div><!--end col-->
                </div><!--end row-->                                                              
            </div><!--end page-title-box-->
        </div><!--end col-->
    </div><!--end row-->
    <!-- end page title end breadcrumb -->

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
            <div class="card shadow-sm stat-card stat-card-clickable" data-status="total" style="border: 2px solid #007bff; border-radius: 10px; cursor:pointer;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="mr-3">
                            <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center stat-icon" style="width: 48px; height: 48px;">
                                <i class="fas fa-users text-white"></i>
                            </div>
                        </div>
                        <div class="flex-fill">
                            <h6 class="mb-1 font-weight-bold text-muted">Total Kunjungan</h6>
                            <h4 class="mb-0 text-primary stat-number" id="stat-total">{{ $stats['total'] }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
            <div class="card shadow-sm stat-card stat-card-clickable" data-status="belum_diperiksa" style="border: 2px solid #ffc107; border-radius: 10px; cursor:pointer;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="mr-3">
                            <div class="rounded-circle bg-warning d-flex align-items-center justify-content-center stat-icon" style="width: 48px; height: 48px;">
                                <i class="fas fa-clock text-white"></i>
                            </div>
                        </div>
                        <div class="flex-fill">
                            <h6 class="mb-1 font-weight-bold text-muted">Belum Diperiksa</h6>
                            <h4 class="mb-0 text-warning stat-number" id="stat-belum-diperiksa">{{ $stats['belum_diperiksa'] }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
            <div class="card shadow-sm stat-card stat-card-clickable" data-status="sudah_diperiksa" style="border: 2px solid #28a745; border-radius: 10px; cursor:pointer;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="mr-3">
                            <div class="rounded-circle bg-success d-flex align-items-center justify-content-center stat-icon" style="width: 48px; height: 48px;">
                                <i class="fas fa-check text-white"></i>
                            </div>
                        </div>
                        <div class="flex-fill">
                            <h6 class="mb-1 font-weight-bold text-muted">Sudah Diperiksa</h6>
                            <h4 class="mb-0 text-success stat-number" id="stat-sudah-diperiksa">{{ $stats['sudah_diperiksa'] }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
            <div class="card shadow-sm stat-card stat-card-clickable" data-status="tidak_datang" style="border: 2px solid #17a2b8; border-radius: 10px; cursor:pointer;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="mr-3">
                            <div class="rounded-circle bg-info d-flex align-items-center justify-content-center stat-icon" style="width: 48px; height: 48px;">
                                <i class="fas fa-user-times text-white"></i>
                            </div>
                        </div>
                        <div class="flex-fill">
                            <h6 class="mb-1 font-weight-bold text-muted">Tidak Datang</h6>
                            <h4 class="mb-0 text-info stat-number" id="stat-tidak-datang">{{ $stats['tidak_datang'] }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
            <div class="card shadow-sm stat-card stat-card-clickable" data-status="dibatalkan" style="border: 2px solid #dc3545; border-radius: 10px; cursor:pointer;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="mr-3">
                            <div class="rounded-circle bg-danger d-flex align-items-center justify-content-center stat-icon" style="width: 48px; height: 48px;">
                                <i class="fas fa-times text-white"></i>
                            </div>
                        </div>
                        <div class="flex-fill">
                            <h6 class="mb-1 font-weight-bold text-muted">Dibatalkan</h6>
                            <h4 class="mb-0 text-danger stat-number" id="stat-dibatalkan">{{ $stats['dibatalkan'] }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Visitation List by Stat -->
    <div class="modal fade" id="modalVisitationList" tabindex="-1" role="dialog" aria-labelledby="modalVisitationListTitle" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalVisitationListTitle">Daftar Pasien</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="visitation-list-content">
                        <div class="text-center"><span class="spinner-border"></span> Memuat data...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-primary">
            <h4 class="card-title text-white">Daftar Kunjungan Rawat Jalan</h4>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="filter_tanggal">Periode Tanggal Kunjungan</label>
                    <div class="input-group">
                        <input type="text" id="filter_tanggal" class="form-control" placeholder="Pilih Rentang Tanggal">
                        <div class="input-group-append">
                            <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                        </div>
                    </div>
                </div>
                @if ($role !== 'Dokter')
                <div class="col-md-4">
                    <label for="filter_dokter">Filter Dokter</label>
                    <select id="filter_dokter" class="form-control select2">
                        <option value="">Semua Dokter</option>
                        @foreach($dokters as $dokter)
                            <option value="{{ $dokter->id }}">{{ $dokter->user->name }} - {{ $dokter->spesialisasi->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="filter_klinik">Filter Klinik</label>
                    <select id="filter_klinik" class="form-control select2">
                        <option value="">Semua Klinik</option>
                        @foreach($kliniks as $klinik)
                            <option value="{{ $klinik->id }}">{{ $klinik->nama }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
            </div>
            <table class="table table-bordered w-100" id="rawatjalan-table">
                <thead>
                    <tr>
                        <th>
                            @if ($role === 'Dokter')
                                No
                            @else
                                Antrian
                            @endif
                        </th>
                        <th>No RM</th>
                        <th>Nama Pasien</th>
                        <th>Tanggal Kunjungan</th>
                        <th>Waktu Kunjungan</th> <!-- Add header for waktu_kunjungan -->
                        <th>Spesialisasi</th>
                        <th>Dokter</th>
                        <th>Selesai Asesmen</th>
                        <th>Metode Bayar</th>
                        <th>Dokumen</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function () {

    $('.select2').select2({
        width: '100%' 
    });
    // Initialize daterangepicker
    $('#filter_tanggal').daterangepicker({
        locale: {
            format: 'DD-MM-YYYY',
            separator: ' s/d ',
            applyLabel: 'Pilih',
            cancelLabel: 'Batal',
            fromLabel: 'Dari',
            toLabel: 'Hingga',
            customRangeLabel: 'Kustom',
            weekLabel: 'M',
            daysOfWeek: ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'],
            monthNames: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
            firstDay: 1
        },
        opens: 'left',
        autoUpdateInput: false
    });
    
    // Set default value to today
    var today = moment().format('DD-MM-YYYY');
    $('#filter_tanggal').val(today + ' s/d ' + today);
    
    // Handle apply event
    $('#filter_tanggal').on('apply.daterangepicker', function(ev, picker) {
        $(this).val(picker.startDate.format('DD-MM-YYYY') + ' s/d ' + picker.endDate.format('DD-MM-YYYY'));
        table.ajax.reload();
        updateStats();
    });
    
    // Handle cancel event
    $('#filter_tanggal').on('cancel.daterangepicker', function(ev, picker) {
        $(this).val('');
        table.ajax.reload();
        updateStats();
    });

    $.fn.dataTable.ext.order['antrian-number'] = function(settings, col) {
        return this.api().column(col, {order: 'index'}).nodes().map(function(td, i) {
            return parseInt($('span', td).data('order')) || 0;
        });
    };
var userRole = "{{ $role }}";
    let table = $('#rawatjalan-table').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        pageLength: 50, // Set default rows per page to 50
        ajax: {
            url: '{{ route("erm.rawatjalans.index") }}',
            data: function(d) {
                var dateRange = $('#filter_tanggal').val().split(' s/d ');
                d.start_date = dateRange[0] ? moment(dateRange[0], 'DD-MM-YYYY').format('YYYY-MM-DD') : '';
                d.end_date = dateRange[1] ? moment(dateRange[1], 'DD-MM-YYYY').format('YYYY-MM-DD') : '';
                d.dokter_id = $('#filter_dokter').val();
                d.klinik_id = $('#filter_klinik').val();
            }
        },
        order: [[3, 'asc'], [0, 'asc']], // Tanggal ASC, Antrian ASC
        columns: [
            { 
                data: 'antrian', 
                name: 'no_antrian', 
                searchable: true, 
                orderable: true,
                render: function(data, type, row, meta) {
                    if (userRole === 'Dokter') {
                        return meta.row + 1;
                    } else {
                        return data;
                    }
                }
            },
            { data: 'no_rm', name: 'no_rm', searchable: true, orderable: false },
            { data: 'nama_pasien', name: 'nama_pasien', searchable: true, orderable: false },
            { data: 'tanggal', name: 'tanggal_visitation', searchable: true },
            { data: 'waktu_kunjungan', name: 'waktu_kunjungan', searchable: false, orderable: false }, // Add waktu_kunjungan column
            { data: 'spesialisasi', name: 'spesialisasi', searchable: false, orderable: false },
            { data: 'dokter_nama', name: 'dokter_nama', searchable: false, orderable: false },
            { data: 'selesai_asesmen', name: 'selesai_asesmen', searchable: false, orderable: false },
            { data: 'metode_bayar', name: 'metode_bayar', searchable: true, orderable: false },
            { data: 'dokumen', name: 'dokumen', searchable: false, orderable: false },
        ],
        columnDefs: [
            { targets: 0, width: "8%" }, // Antrian
            { targets: 6, width: "15%" }, // Dokumen
            { targets: 9, width: "15%" }, // Dokumen
        ],
        createdRow: function(row, data, dataIndex) {
    if (data.status_kunjungan == 2) {
        $(row).css('color', 'orange'); 
    } else if (data.status_kunjungan == 1 && userRole === 'Perawat') {
        $(row).css('color', 'yellow');
    }
    // No color change for status_kunjungan == 1 and userRole === 'Dokter'
}
    });

    // Initial stats update
    updateStats();

    // Auto-refresh DataTable every 10 seconds
    setInterval(function() {
        table.ajax.reload(null, false); // false to keep current page
        updateStats(); // Also update statistics
    }, 10000);

    $('#filter_dokter, #filter_klinik').on('change', function () {
        table.ajax.reload();
        updateStats();
    });

    // Stat card click handler
    $('.stat-card-clickable').on('click', function() {
        var status = $(this).data('status');
        let filterTanggal = $('#filter_tanggal').val();
        let filterDokter = $('#filter_dokter').val();
        let filterKlinik = $('#filter_klinik').val();
        let startDate = '';
        let endDate = '';
        if (filterTanggal) {
            let dates = filterTanggal.split(' s/d ');
            if (dates.length === 2) {
                startDate = moment(dates[0], 'DD-MM-YYYY').format('YYYY-MM-DD');
                endDate = moment(dates[1], 'DD-MM-YYYY').format('YYYY-MM-DD');
            }
        }
        $('#modalVisitationList').modal('show');
        $('#visitation-list-content').html('<div class="text-center"><span class="spinner-border"></span> Memuat data...</div>');
        $.ajax({
            url: '{{ url("erm/rawatjalans/list-by-status") }}',
            method: 'GET',
            data: {
                status: status,
                start_date: startDate,
                end_date: endDate,
                dokter_id: filterDokter,
                klinik_id: filterKlinik
            },
            success: function(res) {
                if (res.data && res.data.length > 0) {
                    let html = '<table class="table table-bordered"><thead><tr><th>Nama Pasien</th><th>Dokter</th><th>Tanggal</th><th>No Antrian</th>';
                    if (status === 'dibatalkan') {
                        html += '<th>Aksi</th>';
                    }
                    html += '</tr></thead><tbody>';
                    res.data.forEach(function(item) {
                        html += `<tr><td>${item.pasien_nama}</td><td>${item.dokter_nama}</td><td>${item.tanggal_visitation}</td><td>${item.no_antrian ?? '-'}</td>`;
                        if (status === 'dibatalkan') {
                            html += `<td><button class="btn btn-sm btn-success restore-status-btn" data-id="${item.id}">Pulihkan</button></td>`;
                        }
                        html += '</tr>';
                    });
                    html += '</tbody></table>';
                    $('#visitation-list-content').html(html);
                } else {
                    $('#visitation-list-content').html('<div class="text-center">Tidak ada data kunjungan.</div>');
                }
            },
            error: function() {
                $('#visitation-list-content').html('<div class="text-danger text-center">Gagal memuat data.</div>');
            }
        });

        // Delegate click for restore button
        $('#visitation-list-content').off('click').on('click', '.restore-status-btn', function() {
            var visitationId = $(this).data('id');
            var btn = $(this);
            btn.prop('disabled', true).text('Memproses...');
            $.ajax({
                url: '{{ url("erm/rawatjalans/restore-status") }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    visitation_id: visitationId
                },
                success: function(res) {
                    btn.removeClass('btn-success').addClass('btn-secondary').text('Berhasil');
                    setTimeout(function(){
                        $('#modalVisitationList').modal('hide');
                        $('.stat-card-clickable[data-status="dibatalkan"]').click(); // refresh dibatalkan list
                    }, 1000);
                },
                error: function() {
                    btn.prop('disabled', false).text('Pulihkan');
                    alert('Gagal memulihkan status');
                }
            });
        });
    });

    // ambil no antrian otomatis
    $('#reschedule-dokter-id, #reschedule-tanggal-visitation').on('change', function() {
        let dokterId = $('#reschedule-dokter-id').val();
        let tanggal = $('#reschedule-tanggal-visitation').val();

        if (dokterId && tanggal) {
            $.get('{{ route("erm.rawatjalans.cekAntrian") }}', { dokter_id: dokterId, tanggal: tanggal }, function(res) {
                $('#reschedule-no-antrian').val(res.no_antrian);
            });
        }
    });

    // submit form reschedule
    $('#form-reschedule').submit(function(e) {
        e.preventDefault();

        $.ajax({
            url: '{{ route("erm.rawatjalans.store") }}',
            method: 'POST',
            data: $(this).serialize(),
            success: function(res) {
                $('#modalReschedule').modal('hide');
                $('#rawatjalan-table').DataTable().ajax.reload();
                alert(res.message);
            },
            error: function(xhr) {
                alert('Terjadi kesalahan!');
            }
        });
    });
    $('#btn-kirim-wa').click(function() {
    let phoneNumber = $('#konfirmasi-no-telepon').val().replace(/\D/g, '');
    
    // Convert phone number format if needed (0 → 62)
    if (phoneNumber.startsWith('0')) {
        phoneNumber = '62' + phoneNumber.substring(1);
    }
    // Make sure it starts with 62 if not already
    else if (!phoneNumber.startsWith('62')) {
        phoneNumber = '62' + phoneNumber;
    }
    
    const message = encodeURIComponent($('#konfirmasi-pesan').val());
    
    if (phoneNumber) {
        // Open WhatsApp with the message in a new tab
        window.open(`https://wa.me/${phoneNumber}?text=${message}`, '_blank');
        $('#modalKonfirmasi').modal('hide');
    } else {
        alert('Nomor telepon tidak valid');
    }
});

// Function to update statistics
function updateStats() {
    // Get current filter values
    let filterTanggal = $('#filter_tanggal').val();
    let filterDokter = $('#filter_dokter').val();
    let filterKlinik = $('#filter_klinik').val();
    
    // Parse date range
    let startDate = '';
    let endDate = '';
    if (filterTanggal) {
        let dates = filterTanggal.split(' s/d ');
        if (dates.length === 2) {
            startDate = moment(dates[0], 'DD-MM-YYYY').format('YYYY-MM-DD');
            endDate = moment(dates[1], 'DD-MM-YYYY').format('YYYY-MM-DD');
        }
    }
    
    // Make AJAX request to get updated stats
    $.get('{{ route("erm.rawatjalans.stats") }}', {
        start_date: startDate,
        end_date: endDate,
        dokter_id: filterDokter,
        klinik_id: filterKlinik
    }, function(stats) {
        // Update the statistics display
        $('#stat-total').text(stats.total);
        $('#stat-tidak-datang').text(stats.tidak_datang);
        $('#stat-belum-diperiksa').text(stats.belum_diperiksa);
        $('#stat-sudah-diperiksa').text(stats.sudah_diperiksa);
        $('#stat-dibatalkan').text(stats.dibatalkan);
    }).fail(function() {
        console.error('Failed to update statistics');
    });
}


});

// Batalkan Kunjungan
function batalkanKunjungan(visitationId, btn) {
    Swal.fire({
        title: 'Batalkan Kunjungan?',
        text: 'Status kunjungan akan diubah menjadi dibatalkan.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Batalkan',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.value) {
            $.ajax({
                url: '/erm/rawatjalans/batalkan',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    visitation_id: visitationId
                },
                success: function(res) {
                    // Remove row from datatable
                    $('#rawatjalan-table').DataTable().ajax.reload();
                    Swal.fire('Dibatalkan!', 'Kunjungan berhasil dibatalkan.', 'success');
                },
                error: function() {
                    Swal.fire('Gagal', 'Terjadi kesalahan.', 'error');
                }
            });
        }
    });
}

// Edit Antrian
function editAntrian(visitationId, currentAntrian, currentWaktuKunjungan = null) {
    Swal.fire({
        title: 'Edit Nomor Antrian & Waktu Kunjungan',
        html: `<input id="swal-input1" class="swal2-input" type="number" min="1" value="${currentAntrian}" placeholder="Nomor Antrian">
               <input id="swal-input2" class="swal2-input" type="time" value="${currentWaktuKunjungan ? currentWaktuKunjungan : ''}" placeholder="Waktu Kunjungan (opsional)">`,
        focusConfirm: false,
        showCancelButton: true,
        confirmButtonText: 'Simpan',
        cancelButtonText: 'Batal',
        preConfirm: () => {
            const noAntrian = document.getElementById('swal-input1').value;
            const waktuKunjungan = document.getElementById('swal-input2').value;
            if (!noAntrian || noAntrian < 1) {
                Swal.showValidationMessage('Nomor antrian tidak valid');
                return false;
            }
            return { noAntrian, waktuKunjungan };
        }
    }).then((result) => {
        if (result.value) {
            $.ajax({
                url: '/erm/rawatjalans/edit-antrian',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    visitation_id: visitationId,
                    no_antrian: result.value.noAntrian,
                    waktu_kunjungan: result.value.waktuKunjungan
                },
                success: function(res) {
                    $('#rawatjalan-table').DataTable().ajax.reload();
                    Swal.fire('Berhasil', 'Nomor antrian & waktu kunjungan berhasil diubah.', 'success');
                },
                error: function() {
                    Swal.fire('Gagal', 'Terjadi kesalahan.', 'error');
                }
            });
        }
    });
}

function openRescheduleModal(visitationId, namaPasien, pasienId) {
    $('#modalReschedule').modal('show');
    $('#reschedule-visitation-id').val(visitationId);
    $('#reschedule-pasien-id').val(pasienId);
    $('#reschedule-nama-pasien').val(namaPasien);
}

function openKonfirmasiModal(namaPasien, telepon, dokterNama, tanggalKunjungan, noAntrian, gender, tanggalLahir) {
    $('#konfirmasi-nama-pasien').val(namaPasien);
    $('#konfirmasi-no-telepon').val(telepon);
    
    // Calculate age based on tanggal_lahir
    let age = 0;
    if (tanggalLahir) {
        const birthDate = new Date(tanggalLahir);
        const today = new Date();
        age = today.getFullYear() - birthDate.getFullYear();
        const monthDiff = today.getMonth() - birthDate.getMonth();
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
            age--;
        }
    }
    
    // Determine the appropriate honorific based on gender and age
    let honorific = '';
    if (age < 17) {
        honorific = 'Adik';
    } else if (age < 30) {
        honorific = 'Kakak';
    } else if (gender === 'Laki-laki' || gender === 'L' || gender === 'M') {
        honorific = 'Bapak';
    } else if (gender === 'Perempuan' || gender === 'P' || gender === 'F') {
        honorific = 'Ibu';
    } else {
        honorific = 'Bapak/Ibu'; // Default if gender is unknown
    }
    
    // Format template message with patient data
    const templateMessage = $('#konfirmasi-pesan').val()
        .replace('%NAMA_PASIEN%', namaPasien)
        .replace('%PANGGILAN%', honorific)
        .replace('%TANGGAL_KUNJUNGAN%', tanggalKunjungan)
        .replace('%DOKTER%', dokterNama)
        .replace('%NO_ANTRIAN%', noAntrian);
    
    $('#konfirmasi-pesan').val(templateMessage);
    $('#modalKonfirmasi').modal('show');
}

// Open Screening Batuk Modal
function openScreeningBatukModal(visitationId, editMode = false) {
    console.log('Opening screening modal for visitation ID:', visitationId, 'Edit mode:', editMode);
    $('#screening-visitation-id').val(visitationId);
    $('#screening-edit-mode').val(editMode);
    
    if (editMode) {
        // Load existing data for editing
        loadScreeningDataForEdit(visitationId);
        $('#screening-modal-title').text('Edit Screening Batuk');
        $('#screening-btn-text').text('Update Screening');
    } else {
        // Reset form for new entry
        $('#form-screening-batuk')[0].reset();
        // Set all radio buttons to default "tidak" values
        $('input[name$="_tidak"]').prop('checked', true);
        $('#screening-modal-title').text('Screening Batuk');
        $('#screening-btn-text').text('Simpan & Lanjutkan');
        $('#screening-id').val('');
    }
    
    $('#modalScreeningBatuk').modal('show');
}

// Load existing screening data for editing
function loadScreeningDataForEdit(visitationId) {
    $.ajax({
        url: '{{ url("erm/screening/batuk") }}/' + visitationId,
        method: 'GET',
        success: function(res) {
            if (res.success && res.data) {
                const data = res.data;
                $('#screening-id').val(data.id);
                
                // Populate form fields
                populateScreeningForm(data);
            } else {
                console.error('No screening data found');
                Swal.fire({
                    title: 'Error',
                    text: 'Data screening tidak ditemukan.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            }
        },
        error: function(xhr) {
            console.error('Error loading screening data:', xhr);
            Swal.fire({
                title: 'Error',
                text: 'Gagal memuat data screening.',
                icon: 'error',
                confirmButtonText: 'OK'
            });
        }
    });
}

// Populate screening form with existing data
function populateScreeningForm(data) {
    // Sesi Gejala
    $('input[name="demam_badan_panas"][value="' + data.demam_badan_panas + '"]').prop('checked', true);
    $('input[name="batuk_pilek"][value="' + data.batuk_pilek + '"]').prop('checked', true);
    $('input[name="sesak_nafas"][value="' + data.sesak_nafas + '"]').prop('checked', true);
    $('input[name="kontak_covid"][value="' + data.kontak_covid + '"]').prop('checked', true);
    $('input[name="perjalanan_luar_negeri"][value="' + data.perjalanan_luar_negeri + '"]').prop('checked', true);
    
    // Sesi Faktor Resiko
    $('input[name="riwayat_perjalanan"][value="' + data.riwayat_perjalanan + '"]').prop('checked', true);
    $('input[name="kontak_erat_covid"][value="' + data.kontak_erat_covid + '"]').prop('checked', true);
    $('input[name="faskes_covid"][value="' + data.faskes_covid + '"]').prop('checked', true);
    $('input[name="kontak_hewan"][value="' + data.kontak_hewan + '"]').prop('checked', true);
    $('input[name="riwayat_demam"][value="' + data.riwayat_demam + '"]').prop('checked', true);
    $('input[name="riwayat_kontak_luar_negeri"][value="' + data.riwayat_kontak_luar_negeri + '"]').prop('checked', true);
    
    // Sesi Tools Screening Batuk
    $('input[name="riwayat_pengobatan_tb"][value="' + data.riwayat_pengobatan_tb + '"]').prop('checked', true);
    $('input[name="sedang_pengobatan_tb"][value="' + data.sedang_pengobatan_tb + '"]').prop('checked', true);
    $('input[name="batuk_demam"][value="' + data.batuk_demam + '"]').prop('checked', true);
    $('input[name="nafsu_makan_menurun"][value="' + data.nafsu_makan_menurun + '"]').prop('checked', true);
    $('input[name="bb_turun"][value="' + data.bb_turun + '"]').prop('checked', true);
    $('input[name="keringat_malam"][value="' + data.keringat_malam + '"]').prop('checked', true);
    $('input[name="sesak_nafas_tb"][value="' + data.sesak_nafas_tb + '"]').prop('checked', true);
    $('input[name="kontak_erat_tb"][value="' + data.kontak_erat_tb + '"]').prop('checked', true);
    $('input[name="hasil_rontgen"][value="' + data.hasil_rontgen + '"]').prop('checked', true);
    
    // Catatan
    $('#catatan_screening').val(data.catatan || '');
}

// Event handler for screening button using data attribute
$(document).on('click', '.screening-btn', function(e) {
    e.preventDefault();
    const visitationId = $(this).data('visitation-id');
    console.log('Opening screening modal for visitation ID from data attribute:', visitationId);
    openScreeningBatukModal(visitationId, false);
});

// Event handler for edit screening button
$(document).on('click', '#btn-edit-screening', function(e) {
    e.preventDefault();
    const visitationId = $('#screening-visitation-id').val();
    console.log('Editing screening for visitation ID:', visitationId);
    
    // Close view modal first
    $('#modalViewScreeningBatuk').modal('hide');
    
    // Open edit modal after a short delay to ensure smooth transition
    setTimeout(function() {
        openScreeningBatukModal(visitationId, true);
    }, 300);
});

// Handle Screening Batuk Form Submission
$('#btn-simpan-screening').click(function() {
    // Validate that all required radio buttons are selected
    let isValid = true;
    const requiredFields = [
        // Sesi Gejala
        'demam_badan_panas', 'batuk_pilek', 'sesak_nafas', 'kontak_covid', 'perjalanan_luar_negeri',
        // Sesi Faktor Resiko
        'riwayat_perjalanan', 'kontak_erat_covid', 'faskes_covid', 'kontak_hewan', 'riwayat_demam', 'riwayat_kontak_luar_negeri',
        // Sesi Tools Screening Batuk
        'riwayat_pengobatan_tb', 'sedang_pengobatan_tb', 'batuk_demam', 'nafsu_makan_menurun', 'bb_turun', 'keringat_malam', 'sesak_nafas_tb', 'kontak_erat_tb', 'hasil_rontgen'
    ];
    
    requiredFields.forEach(function(field) {
        if (!$('input[name="' + field + '"]:checked').length) {
            isValid = false;
        }
    });
    
    if (!isValid) {
        Swal.fire({
            title: 'Lengkapi Form',
            text: 'Harap jawab semua pertanyaan screening batuk.',
            icon: 'warning',
            confirmButtonText: 'OK'
        });
        return;
    }
    
    const visitationId = $('#screening-visitation-id').val();
    const editMode = $('#screening-edit-mode').val() === 'true';
    const screeningId = $('#screening-id').val();
    const formData = $('#form-screening-batuk').serialize();
    
    console.log('Visitation ID:', visitationId);
    console.log('Edit Mode:', editMode);
    console.log('Screening ID:', screeningId);
    console.log('Form Data:', formData);
    
    // Determine URL and method based on edit mode
    let url = '{{ route("erm.screening.batuk.store") }}';
    let method = 'POST';
    let additionalData = '&_token={{ csrf_token() }}';
    
    if (editMode && screeningId) {
        url = '{{ url("erm/screening/batuk/update") }}/' + screeningId;
        method = 'PUT';
        additionalData = '&_token={{ csrf_token() }}&_method=PUT';
    }
    
    // Save or update screening data via AJAX
    $.ajax({
        url: url,
        method: method,
        data: formData + additionalData,
        success: function(res) {
            console.log('Success response:', res);
            $('#modalScreeningBatuk').modal('hide');
            
            // Show success notification
            Swal.fire({
                title: 'Berhasil!',
                text: editMode ? 'Data screening batuk berhasil diperbarui.' : 'Data screening batuk berhasil disimpan. Akan dialihkan ke halaman asesmen perawat.',
                icon: 'success',
                timer: editMode ? 2000 : 2000,
                timerProgressBar: true,
                showConfirmButton: false,
                allowOutsideClick: false,
                allowEscapeKey: false
            }).then(() => {
                if (editMode) {
                    // Refresh datatable for edit mode
                    if (typeof table !== 'undefined') {
                        table.ajax.reload(null, false);
                    }
                } else {
                    // Redirect to asesmen perawat create page for new entries
                    window.location.href = '{{ url("erm/asesmenperawat") }}/' + visitationId + '/create';
                }
            });
        },
        error: function(xhr) {
            console.error('Error saving screening data:', xhr);
            console.error('Response text:', xhr.responseText);
            
            let errorMessage = editMode ? 'Gagal memperbarui data screening. Silakan coba lagi.' : 'Gagal menyimpan data screening. Silakan coba lagi.';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            }
            
            Swal.fire({
                title: 'Terjadi Kesalahan',
                text: errorMessage,
                icon: 'error',
                confirmButtonText: 'OK'
            });
        }
    });
});

// Event handler for view screening button
$(document).on('click', '.view-screening-btn', function(e) {
    e.preventDefault();
    const visitationId = $(this).data('visitation-id');
    console.log('Viewing screening data for visitation ID:', visitationId);
    
    // Store visitation ID for edit button
    $('#screening-visitation-id').val(visitationId);
    
    // Fetch and display screening data
    $.ajax({
        url: '{{ url("erm/screening/batuk") }}/' + visitationId,
        method: 'GET',
        success: function(res) {
            if (res.success) {
                displayScreeningData(res.data);
                $('#modalViewScreeningBatuk').modal('show');
            } else {
                Swal.fire({
                    title: 'Error',
                    text: res.message,
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            }
        },
        error: function(xhr) {
            console.error('Error fetching screening data:', xhr);
            Swal.fire({
                title: 'Terjadi Kesalahan',
                text: 'Gagal mengambil data screening batuk.',
                icon: 'error',
                confirmButtonText: 'OK'
            });
        }
    });
});

// Function to display screening data in modal
function displayScreeningData(data) {
    // Helper function to convert value to readable text with proper styling
    function getReadableValue(value) {
        if (value === 'ya') {
            return '<span class="badge badge-danger">Ya</span>';
        } else {
            return '<span class="badge badge-success">Tidak</span>';
        }
    }
    
    // Sesi Gejala
    $('#view-demam-badan-panas').html(getReadableValue(data.demam_badan_panas));
    $('#view-batuk-pilek').html(getReadableValue(data.batuk_pilek));
    $('#view-sesak-nafas').html(getReadableValue(data.sesak_nafas));
    $('#view-kontak-covid').html(getReadableValue(data.kontak_covid));
    $('#view-perjalanan-luar-negeri').html(getReadableValue(data.perjalanan_luar_negeri));
    
    // Sesi Faktor Resiko
    $('#view-riwayat-perjalanan').html(getReadableValue(data.riwayat_perjalanan));
    $('#view-kontak-erat-covid').html(getReadableValue(data.kontak_erat_covid));
    $('#view-faskes-covid').html(getReadableValue(data.faskes_covid));
    $('#view-kontak-hewan').html(getReadableValue(data.kontak_hewan));
    $('#view-riwayat-demam').html(getReadableValue(data.riwayat_demam));
    $('#view-riwayat-kontak-luar-negeri').html(getReadableValue(data.riwayat_kontak_luar_negeri));
    
    // Sesi Tools Screening Batuk
    $('#view-riwayat-pengobatan-tb').html(getReadableValue(data.riwayat_pengobatan_tb));
    $('#view-sedang-pengobatan-tb').html(getReadableValue(data.sedang_pengobatan_tb));
    $('#view-batuk-demam').html(getReadableValue(data.batuk_demam));
    $('#view-nafsu-makan-menurun').html(getReadableValue(data.nafsu_makan_menurun));
    $('#view-bb-turun').html(getReadableValue(data.bb_turun));
    $('#view-keringat-malam').html(getReadableValue(data.keringat_malam));
    $('#view-sesak-nafas-tb').html(getReadableValue(data.sesak_nafas_tb));
    $('#view-kontak-erat-tb').html(getReadableValue(data.kontak_erat_tb));
    $('#view-hasil-rontgen').html(getReadableValue(data.hasil_rontgen));
    
    // Catatan
    $('#view-catatan').text(data.catatan || '-');
    
    // Created info
    const createdAt = new Date(data.created_at);
    $('#view-created-at').text(createdAt.toLocaleString('id-ID'));
}
</script>


@endsection
