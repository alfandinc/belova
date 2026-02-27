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
    white-space: normal; /* allow wrapping for long text like dokter names */
}

/* Uniform inline badge spacing */
.badge-group {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
}
.badge-group .badge { margin: 0; }
/* Pink badge for children */
.badge-pink {
    background-color: #ff69b4;
    color: #fff;
}

/* Black card explicit style (ensure visible on all themes) */
.badge-black {
    background-color: #343a40 !important;
    color: #fff !important;
}

/* Ensure patient name link matches surrounding text */
.open-manage-modal {
    color: inherit !important;
    text-decoration: none !important;
    font-family: inherit !important;
    font-weight: inherit !important;
    text-transform: none !important;
}
.open-manage-modal:hover { color: inherit !important; text-decoration: none !important; }

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

/* =============================
   Compact Statistic Card Tweaks
   ============================= */
.row.mb-4 > [class*='col-'] { /* tighten bottom margin between cards */
    margin-bottom: .75rem !important;
}
.stat-card { 
    border-width: 1px !important; 
    border-radius: 8px !important; 
}
.stat-card .card-body { 
    padding: .55rem .65rem !important; 
}
.stat-card .stat-icon { 
    width: 34px !important; 
    height: 34px !important; 
    font-size: 14px; 
}
.stat-card .stat-number { 
    font-size: 1.25rem !important; 
    font-weight: 600; 
}
.stat-card h6 { 
    font-size: .70rem; 
    letter-spacing: .25px; 
    margin-bottom: .15rem !important; 
    text-transform: uppercase; 
}
/* Slightly reduce hover lift & shadow for compact look */
.stat-card:hover { 
    transform: translateY(-1px); 
    box-shadow: 0 4px 14px rgba(0,0,0,.12) !important; 
}
@media (max-width: 992px) { /* medium */
    .stat-card .stat-number { font-size: 1.15rem !important; }
}
@media (max-width: 576px) { /* phones */
    .stat-card .card-body { padding: .5rem .6rem !important; }
    .stat-card .stat-icon { width: 30px !important; height: 30px !important; }
    .stat-card .stat-number { font-size: 1.05rem !important; }
}

/* =============================
   7-Column Responsive Layout
   ============================= */
.stats-row { /* custom flexible container */
    display: flex;
    flex-wrap: wrap;
    gap: 8px; /* consistent spacing */
    margin-left: 0 !important;
    margin-right: 0 !important;
}
.stats-row .stat-col { /* 7 columns on very large screens */
    flex: 1 1 calc(14.285% - 8px);
    max-width: calc(14.285% - 8px);
}
@media (max-width: 1600px) { /* fallback gracefully if narrower */
    .stats-row .stat-col { flex: 1 1 calc(16.666% - 8px); max-width: calc(16.666% - 8px); }
}
@media (max-width: 1400px) { /* 6 -> 5 */
    .stats-row .stat-col { flex: 1 1 calc(20% - 8px); max-width: calc(20% - 8px); }
}
@media (max-width: 1200px) { /* 5 -> 4 */
    .stats-row .stat-col { flex: 1 1 calc(25% - 8px); max-width: calc(25% - 8px); }
}
@media (max-width: 992px) { /* 4 -> 3 */
    .stats-row .stat-col { flex: 1 1 calc(33.333% - 8px); max-width: calc(33.333% - 8px); }
}
@media (max-width: 768px) { /* 3 -> 2 */
    .stats-row .stat-col { flex: 1 1 calc(50% - 8px); max-width: calc(50% - 8px); }
}
@media (max-width: 480px) { /* 2 -> 1 */
    .stats-row .stat-col { flex: 1 1 100%; max-width: 100%; }
}
</style>

@include('erm.partials.modal-reschedule')
<!-- Unified Manage Pasien Modal (copied from pasien index) -->
<div class="modal fade" id="modalManagePasien" tabindex="-1" role="dialog" aria-labelledby="modalManagePasienLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalManagePasienLabel">Kelola Pasien</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="font-weight-bold" id="managePasienNama">-</div>
                            <div class="text-muted small">No. RM: <span id="managePasienId">-</span></div>
                        </div>
                    </div>
                </div>
                <hr/>
                <div class="row">
                    <div class="col-md-6">
                        <form id="manageStatusForm">
                            <div class="form-group">
                                <label for="manage_status_pasien">Status Pasien</label>
                                <select class="form-control" id="manage_status_pasien" name="status_pasien" required>
                                    <option value="Regular">Regular</option>
                                    <option value="VIP">VIP</option>
                                    <option value="Familia">Familia</option>
                                    <option value="Black Card">Black Card</option>
                                    <option value="Red Flag">Red Flag</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="manage_status_akses">Status Akses</label>
                                <select class="form-control" id="manage_status_akses" name="status_akses" required>
                                    <option value="normal">Normal</option>
                                    <option value="akses cepat">Akses Cepat</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="manage_status_review">Status Review</label>
                                <select class="form-control" id="manage_status_review" name="status_review" required>
                                    <option value="sudah">Sudah</option>
                                    <option value="belum">Belum</option>
                                </select>
                            </div>
                        </form>
                    </div>
                    <div class="col-md-6">
                        <label class="d-block">Merchandise</label>
                        <div id="unifiedMerchChecklistContainer"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-primary" id="saveManagePasien">Simpan</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Edit Status Akses -->
<div class="modal fade" id="modalEditStatusAkses" tabindex="-1" role="dialog" aria-labelledby="modalEditStatusAksesLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEditStatusAksesLabel">Edit Status Akses</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="editStatusAksesForm">
                    <div class="form-group">
                        <label for="edit_status_akses">Status Akses</label>
                        <select class="form-control" id="edit_status_akses" name="status_akses" required>
                            <option value="normal">Normal</option>
                            <option value="akses cepat">Akses Cepat</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="saveEditStatusAkses">Simpan</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Edit Status Review -->
<div class="modal fade" id="modalEditStatusReview" tabindex="-1" role="dialog" aria-labelledby="modalEditStatusReviewLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEditStatusReviewLabel">Edit Status Review</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="editStatusReviewForm">
                    <div class="form-group">
                        <label for="edit_status_review">Status Review</label>
                        <select class="form-control" id="edit_status_review" name="status_review" required>
                            <option value="sudah">Sudah</option>
                            <option value="belum">Belum</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="saveEditStatusReview">Simpan</button>
            </div>
        </div>
    </div>
</div>
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

    <!-- Dokter-to-Perawat Notification Button -->
    @if (auth()->user() && auth()->user()->hasRole('Dokter'))
    <div class="row mb-3">
        <div class="col-md-12 d-flex gap-2">
            <button id="btn-buka-pintu" class="btn btn-danger mr-2">
                <i class="fas fa-door-open"></i> Perawat Buka Pintu
            </button>
            <button id="btn-panggil-perawat" class="btn btn-warning">
                <i class="fas fa-bell"></i> Panggil Perawat ke Ruang Dokter
            </button>
        </div>
    </div>
    @endif
    <!-- Statistics Cards -->
    <div class="row mb-4 stats-row">
        <div class="stat-col">
            <div class="card shadow-sm stat-card stat-card-clickable" data-status="total" style="border: 2px solid #007bff; border-radius: 10px; cursor:pointer;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="mr-3">
                            <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center stat-icon" style="width: 48px; height: 48px;">
                                <i class="fas fa-users text-white"></i>
                            </div>
                        </div>
                        <div class="flex-fill">
                            <h6 class="mb-1 font-weight-bold text-muted">Total Visit</h6>
                            <h4 class="mb-0 text-primary stat-number" id="stat-total">{{ $stats['total'] }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="stat-col">
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
        <div class="stat-col">
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
        <div class="stat-col">
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
        <div class="stat-col">
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
            <div class="stat-col">
                <div class="card shadow-sm stat-card stat-card-clickable" data-status="rujuk" style="border: 2px solid #6f42c1; border-radius: 10px; cursor:pointer;">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center">
                            <div class="mr-3">
                                <div class="rounded-circle bg-purple d-flex align-items-center justify-content-center stat-icon" style="width: 48px; height: 48px; background-color:#6f42c1;">
                                    <i class="fas fa-share-alt text-white"></i>
                                </div>
                            </div>
                            <div class="flex-fill">
                                <h6 class="mb-1 font-weight-bold text-muted">Rujuk/Konsultasi</h6>
                                <h4 class="mb-0 text-dark stat-number" id="stat-rujuk">{{ $stats['rujuk'] ?? 0 }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <div class="stat-col">
            <div class="card shadow-sm stat-card stat-card-clickable" data-status="lab_permintaan" style="border: 2px solid #20c997; border-radius: 10px; cursor:pointer;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="mr-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center stat-icon" style="width:48px;height:48px;background:linear-gradient(135deg,#20c997,#0d8865);">
                                <i class="fas fa-vials text-white"></i>
                            </div>
                        </div>
                        <div class="flex-fill">
                            <h6 class="mb-1 font-weight-bold text-muted">Permintaan Lab</h6>
                            <h4 class="mb-0 text-teal stat-number" id="stat-lab-permintaan">{{ $stats['lab_permintaan'] ?? 0 }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
            <!-- 7th Card Template (duplicate & adjust as needed) -->
            <!--
            <div class="stat-col">
                <div class="card shadow-sm stat-card stat-card-clickable" data-status="baru" style="border: 2px solid #0d6efd; border-radius: 10px; cursor:pointer;">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center">
                            <div class="mr-3">
                                <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center stat-icon">
                                    <i class="fas fa-star text-white"></i>
                                </div>
                            </div>
                            <div class="flex-fill">
                                <h6 class="mb-1 font-weight-bold text-muted">Label Baru</h6>
                                <h4 class="mb-0 text-primary stat-number" id="stat-baru">0</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            -->
    </div>

    <!-- Modal: Lab Permintaan List -->
    <div class="modal fade" id="modalLabPermintaanList" tabindex="-1" role="dialog" aria-labelledby="modalLabPermintaanListTitle" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-teal text-white" style="background:#109e7d;">
                    <h5 class="modal-title" id="modalLabPermintaanListTitle"><i class="fas fa-vials mr-2"></i>Permintaan Lab</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="lab-permintaan-list-content">
                        <div class="text-center"><span class="spinner-border"></span> Memuat data...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

        <!-- Modal for Rujuk List -->
        <div class="modal fade" id="modalRujukList" tabindex="-1" role="dialog" aria-labelledby="modalRujukListTitle" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-secondary text-white">
                        <h5 class="modal-title" id="modalRujukListTitle">Daftar Pasien Rujuk / Konsultasi</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div id="rujuk-list-content">
                            <div class="text-center"><span class="spinner-border"></span> Memuat data...</div>
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
                <div class="col-md-2">
                    <label for="filter_start_date">Start Date</label>
                    <input type="date" id="filter_start_date" class="form-control" />
                </div>
                <div class="col-md-2">
                    <label for="filter_end_date">End Date</label>
                    <input type="date" id="filter_end_date" class="form-control" />
                </div>
                {{-- Show dokter filter to everyone, but pre-select logged-in Dokter when available --}}
                <div class="col-md-4">
                    <label for="filter_dokter">Filter Dokter</label>
                    <select id="filter_dokter" class="form-control select2">
                        <option value="">Semua Dokter</option>
                        @foreach($dokters as $dokter)
                            <option value="{{ $dokter->id }}" {{ isset($defaultDokterId) && $defaultDokterId == $dokter->id ? 'selected' : '' }}>{{ $dokter->user->name }} - {{ $dokter->spesialisasi->nama }}</option>
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
                        <th>Nama Pasien</th>
                        <th>Tanggal Kunjungan</th>
                        <th>Dokter</th>
                        <!-- Selesai Asesmen column removed; will show under Dokumen -->
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
    // Dokter: Send 'Perawat Buka Pintu' notification
    $('#btn-buka-pintu').click(function() {
        $.post('/erm/send-notif-perawat', {
            _token: '{{ csrf_token() }}',
            message: 'Mohon buka pintu untuk pasien.'
        }, function(res) {
            if (res.success) {
                Swal.fire('Terkirim!', 'Notifikasi "Buka Pintu" berhasil dikirim ke Perawat.', 'success');
            } else {
                Swal.fire('Gagal', 'Notifikasi gagal dikirim.', 'error');
            }
        }).fail(function() {
            Swal.fire('Gagal', 'Terjadi kesalahan saat mengirim notifikasi.', 'error');
        });
        // Set sound type for Perawat
        localStorage.setItem('notifSoundType', 'bell');
    });

    // Dokter: Send 'Panggil Perawat ke Ruang Dokter' notification
    $('#btn-panggil-perawat').click(function() {
        $.post('/erm/send-notif-perawat', {
            _token: '{{ csrf_token() }}',
            message: 'Mohon datang ke ruang dokter.'
        }, function(res) {
            if (res.success) {
                Swal.fire('Terkirim!', 'Notifikasi "Panggil Perawat" berhasil dikirim ke Perawat.', 'success');
            } else {
                Swal.fire('Gagal', 'Notifikasi gagal dikirim.', 'error');
            }
        }).fail(function() {
            Swal.fire('Gagal', 'Terjadi kesalahan saat mengirim notifikasi.', 'error');
        });
        // Set sound type for Perawat
        localStorage.setItem('notifSoundType', 'notif');
    });

    // Perawat: Poll for notifications every 5 seconds
    @if ($role === 'Perawat')
    // Sound permission popup on page load
    window.soundEnabled = false;
    $(function() {
        Swal.fire({
            title: 'Aktifkan Notifikasi Suara?',
            text: 'Klik OK untuk mengaktifkan suara notifikasi. Anda hanya perlu melakukan ini sekali.',
            icon: 'question',
            confirmButtonText: 'OK'
        }).then(() => {
            var audio = new Audio('/sounds/confirm.mp3');
            audio.play();
            window.soundEnabled = true;
        });
    });

    setInterval(function() {
        $.get('/erm/get-notif', function(data) {
            if (data.new) {
                let soundFile = '/sounds/notif.mp3';
                if (data.message === 'Mohon buka pintu untuk pasien.') {
                    soundFile = '/sounds/bell.wav';
                }
                Swal.fire({
                    title: 'Notifikasi dari Dokter',
                    text: data.message + (data.sender ? ('\n(Dari: ' + data.sender + ')') : ''),
                    icon: 'info',
                    confirmButtonText: 'OK'
                });
                if (window.soundEnabled) {
                    var audio = new Audio(soundFile);
                    audio.play();
                }
            }
        });
    }, 2000);
    @endif

    // Ensure Moment.js uses Indonesian locale and set default value to today for date inputs
    if (typeof moment !== 'undefined' && typeof moment.locale === 'function') {
        moment.locale('id');
    }
    var today = moment().format('YYYY-MM-DD');
    $('#filter_start_date').val(today);
    $('#filter_end_date').val(today);

    // If server provided a default dokter id (logged-in Dokter), set the select value
    // now; Select2 will be initialized immediately after so we trigger change after init.
    @if(isset($defaultDokterId) && $defaultDokterId)
        var __defaultDokterId = '{{ $defaultDokterId }}';
        $('#filter_dokter').val(__defaultDokterId);
    @endif

    $('.select2').select2({
        width: '100%' 
    });

    // If we set a default, refresh select2 UI and notify change so initial load uses it
    @if(isset($defaultDokterId) && $defaultDokterId)
        $('#filter_dokter').trigger('change');
    @endif

    $.fn.dataTable.ext.order['antrian-number'] = function(settings, col) {
        return this.api().column(col, {order: 'index'}).nodes().map(function(td, i) {
            return parseInt($('span', td).data('order')) || 0;
        });
    };
var userRole = "{{ $role }}";
    // Map metode bayar ids to badge classes (consistent palette)
    @php
        $palette = ['badge-primary','badge-light text-dark','badge-success','badge-danger','badge-warning','badge-info','badge-dark'];
        $metodeMap = [];
        $i = 0;
        foreach($metodeBayar as $m) {
            $metodeMap[$m->id] = $palette[$i % count($palette)];
            $i++;
        }
        // Build a specialization -> badge class map from available dokters
        $spesialisasiMap = [];
        $j = 0;
        $seen = [];
        foreach($dokters as $d) {
            if ($d->spesialisasi && $d->spesialisasi->nama) {
                $name = $d->spesialisasi->nama;
                if (!isset($seen[$name])) {
                    $spesialisasiMap[$name] = $palette[$j % count($palette)];
                    $seen[$name] = true;
                    $j++;
                }
            }
        }
    @endphp
    var metodeColorMap = {!! json_encode($metodeMap) !!};
    // expose globally so other script blocks can access it
    window.metodeColorMap = metodeColorMap;
    var spesialisasiColorMap = {!! json_encode($spesialisasiMap) !!};
    window.spesialisasiColorMap = spesialisasiColorMap;
    let table = $('#rawatjalan-table').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        pageLength: 50, // Set default rows per page to 50
        ajax: {
            url: '{{ route("erm.rawatjalans.index") }}',
            data: function(d) {
                d.start_date = $('#filter_start_date').val();
                d.end_date = $('#filter_end_date').val();
                d.dokter_id = $('#filter_dokter').val();
                d.klinik_id = $('#filter_klinik').val();
            }
        },
        order: [[2, 'asc'], [0, 'asc']], // Tanggal ASC, Antrian ASC (adjusted after removing No RM column)
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
                {
                    data: 'nama_pasien',
                    name: 'nama_pasien',
                    searchable: true,
                    orderable: false,
                    render: function(data, type, row, meta) {
                            function getTxt(v){ return $('<div>').text(v||'').text().trim(); }
                            var sp = getTxt(row.status_pasien);
                            var sa = getTxt(row.status_akses);
                            var sr = getTxt(row.status_review);

                            function badgePasien(val){
                                var v = (val||'').toLowerCase();
                                if (v.includes('vip')) return '<span class="badge badge-warning"><i class="fas fa-crown mr-1"></i>VIP</span>';
                                if (v.includes('familia')) return '<span class="badge badge-primary"><i class="fas fa-users mr-1"></i>Familia</span>';
                                if (v.includes('black')) return '<span class="badge badge-black"><i class="fas fa-id-card mr-1"></i>Black</span>';
                                if (v.includes('red')) return '<span class="badge badge-danger"><i class="fas fa-flag mr-1"></i>Red</span>';
                                return ''; // hide badge for regular/other statuses
                            }
                            function badgeAkses(val){
                                var v = (val||'').toLowerCase();
                                // Only show the badge when the status explicitly indicates 'akses cepat'
                                if (v.includes('akses cepat') || v.includes('akses_cepat') || v.includes('akses-cep')) {
                                    return '<span class="badge badge-primary"><i class="fas fa-wheelchair mr-1"></i>Akses Cepat</span>';
                                }
                                return ''; // do not show any badge for normal/other statuses
                            }
                            function badgeReview(val){
                                var v = (val||'').toLowerCase();
                                // Do not show badge when already reviewed
                                if (v.includes('sudah')) return '';
                                // Show 'Belum Review' with a map marker icon for not-yet-reviewed
                                return '<span class="badge badge-light text-dark"><i class="fas fa-map-marker-alt mr-1"></i>Belum Review</span>';
                            }

                            var badgesArr = [];
                            badgesArr.push(badgePasien(sp));
                            badgesArr.push(badgeAkses(sa));
                            badgesArr.push(badgeReview(sr));

                            // Age badge (compute if tanggal_lahir present)
                            try {
                                if (row.tanggal_lahir) {
                                    var birth = new Date(row.tanggal_lahir);
                                    if (!isNaN(birth)) {
                                        var today = new Date();
                                        var age = today.getFullYear() - birth.getFullYear();
                                        var m = today.getMonth() - birth.getMonth();
                                        if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) age--;
                                        if (!isNaN(age) && age < 17) {
                                            badgesArr.push('<span class="badge badge-pink"><i class="fas fa-baby-carriage mr-1"></i>' + age + ' th</span>');
                                        }
                                    }
                                }
                            } catch(e) {}

                            // Merchandise badge / link (if pasien has merch)
                            try {
                                var merchCount = parseInt(row.merchandise_count || 0);
                                if (merchCount > 0) {
                                    var pasienId = row.pasien_id || '';
                                    var merchHtml = '<a href="#" class="pasien-merch" data-pasien-id="' + pasienId + '" title="Lihat merchandise yang diterima">'
                                        + '<span class="badge badge-primary"><i class="fas fa-gift mr-1"></i>Merch</span></a>';
                                    badgesArr.push(merchHtml);
                                }
                            } catch(e) {}

                            var badgesInner = badgesArr.join('');

                            // Render name with RM/id beside the name and any badges below
                            let rm = row.no_rm ? row.no_rm : '';

                            var pasienId = row.pasien_id || '';
                            let nameHtml = '<div class="d-flex flex-column">'
                                           + '<div class="align-self-start"><strong><a href="#" class="open-manage-modal" data-id="' + pasienId + '" style="color:inherit;text-decoration:none;">' + $('<div>').text(data||'').html() + (rm ? ' (' + $('<div>').text(rm).text() + ')' : '') + '</a></strong></div>'
                                           + '<div class="mt-2 badge-group">'
                                               + (badgesInner ? badgesInner : '')
                                           + '</div>'
                                           + '</div>';

                            return nameHtml;
                        }
                },
            { 
                data: 'tanggal', 
                name: 'tanggal_visitation', 
                searchable: true,
                render: function(data, type, row, meta) {
                    // Format tanggal to include weekday (Indonesian). Fallback to server string if parsing fails.
                    var formattedDate = data || '';
                    try {
                        // Prefer Moment.js with Indonesian locale when available
                        if (typeof moment !== 'undefined') {
                            var m = moment(data, moment.ISO_8601, true);
                            if (!m.isValid()) {
                                m = moment(data, 'D MMMM YYYY', 'id', true);
                            }
                            if (!m.isValid()) {
                                m = moment(data);
                            }
                            if (m && m.isValid()) {
                                // If moment has the locale loaded this will be localized
                                formattedDate = m.format('dddd, D MMMM YYYY');
                            }
                        }
                    } catch (e) {
                        // ignore
                    }

                    // Fallback: if formattedDate is English (or moment locale not present), build Indonesian string manually
                    var tryManual = false;
                    if (!formattedDate) tryManual = true;
                    // quick check for english weekday names
                    var engWeekdays = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
                    for (var i=0;i<engWeekdays.length;i++) {
                        if (formattedDate.indexOf(engWeekdays[i]) !== -1) { tryManual = true; break; }
                    }
                    if (tryManual) {
                        var monthsId = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
                        var daysId = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
                        var dt = null;
                        // try Date parse
                        try {
                            dt = new Date(data);
                            if (isNaN(dt.getTime())) dt = null;
                        } catch(e) { dt = null; }
                        if (!dt) {
                            // try extract numbers: D M YYYY or D MMMM YYYY
                            var parts = (data || '').trim().split(/\s+/);
                            // attempt to find day and year
                            var day = null, month = null, year = null;
                            // find numeric part for day and year
                            for (var p=0;p<parts.length;p++) {
                                if (/^\d{1,2}$/.test(parts[p])) {
                                    if (!day) day = parseInt(parts[p],10);
                                } else if (/^\d{4}$/.test(parts[p])) {
                                    year = parts[p];
                                } else {
                                    // try match month name (english or indonesian)
                                    var idx = monthsId.findIndex(function(m){ return m.toLowerCase()===parts[p].toLowerCase(); });
                                    if (idx !== -1) month = idx;
                                    // english months
                                    var engMonths = ['January','February','March','April','May','June','July','August','September','October','November','December'];
                                    idx = engMonths.findIndex(function(m){ return m.toLowerCase()===parts[p].toLowerCase(); });
                                    if (idx !== -1) month = idx;
                                }
                            }
                            if (day && month !== null && year) {
                                dt = new Date(year, month, day);
                            }
                        }
                        if (dt && !isNaN(dt.getTime())) {
                            var weekdayName = daysId[dt.getDay()];
                            var dayNum = dt.getDate();
                            var monthName = monthsId[dt.getMonth()];
                            var yearNum = dt.getFullYear();
                            formattedDate = weekdayName + ', ' + dayNum + ' ' + monthName + ' ' + yearNum;
                        }
                    }

                    // Make bold
                    if (formattedDate) formattedDate = '<strong>' + formattedDate + '</strong>';

                    var waktu = row.waktu_kunjungan || '';
                    var waktuHtml = '';
                    if (waktu && waktu !== '-') {
                        waktuHtml = '<small class="badge badge-light text-dark">' + waktu + '</small>';
                    }

                    // Metode bayar moved here
                    var metode = row.metode_bayar || '';
                    var metodeId = row.metode_bayar_id || '';
                    var visitationId = row.id || '';
                    var metodeHtml = '';
                    if (metode) {
                        var metodeEsc = ('' + metode).replace(/"/g, '&quot;').replace(/'/g, '&#39;');
                        var badgeClass = 'badge-info';
                        try {
                            // Name-based overrides for specific payment methods
                            var mLower = (metodeEsc || '').toLowerCase();
                            if (mLower.indexOf('umum') !== -1) {
                                badgeClass = 'badge-success'; // green for ID Umum / Umum
                            } else if (mLower.indexOf('inhealth') !== -1) {
                                badgeClass = 'badge-info'; // light blue
                            } else if (mLower.indexOf('bri life') !== -1 || mLower.indexOf('brilife') !== -1) {
                                badgeClass = 'badge-primary'; // blue
                            } else if (mLower.indexOf('bni life') !== -1 || mLower.indexOf('bnilife') !== -1) {
                                badgeClass = 'badge-warning'; // yellow
                            } else if (mLower.indexOf('admedika') !== -1) {
                                badgeClass = 'badge-danger'; // red
                            } else if (metodeId && window.metodeColorMap && window.metodeColorMap[metodeId]) {
                                badgeClass = window.metodeColorMap[metodeId];
                            }
                        } catch(e) {}
                        metodeHtml = ' <a href="#" class="metode-bayar-btn" data-metode="' + metodeEsc + '" data-metode-id="' + metodeId + '" data-visitation-id="' + visitationId + '"><small class="badge ' + badgeClass + ' ml-1">' + metode + '</small></a>';
                    }

                    // Jenis kunjungan badge (1: Konsultasi, 2: Produk/Obat, 3: Lab)
                    var jenis = row.jenis_kunjungan || '';
                    var jenisHtml = '';
                    if (jenis !== '' && jenis !== null && typeof jenis !== 'undefined') {
                        var jenisText = '';
                        var jenisClass = 'badge-light text-dark';
                        if (jenis == 1 || jenis === '1') { jenisText = 'Konsultasi'; jenisClass = 'badge-success'; }
                        else if (jenis == 2 || jenis === '2') { jenisText = 'Produk/Obat'; jenisClass = 'badge-primary'; }
                        else if (jenis == 3 || jenis === '3') { jenisText = 'Lab'; jenisClass = 'badge-warning'; }
                        if (jenisText) jenisHtml = ' <small class="badge ' + jenisClass + ' ml-1">' + jenisText + '</small>';
                    }

                    return '<div>' + formattedDate + '<div class="mt-1">' + metodeHtml + jenisHtml + (waktuHtml ? ' ' + waktuHtml : '') + '</div></div>';
                }
            },
            { 
                data: 'dokter_nama', 
                name: 'dokter_nama', 
                searchable: false, 
                orderable: false,
                render: function(data, type, row, meta) {
                    var nama = data || '-';
                    var spes = row.spesialisasi || '';
                    var badgeClass = 'badge-light text-dark';
                    try {
                        if (spes && window.spesialisasiColorMap && window.spesialisasiColorMap[spes]) badgeClass = window.spesialisasiColorMap[spes];
                    } catch(e) {}
                    var spesHtml = '';
                    if (spes) {
                        var spesEsc = (''+spes).replace(/"/g, '&quot;').replace(/'/g, '&#39;');
                        spesHtml = '<div class="mt-1"><small class="badge ' + badgeClass + '">' + spesEsc + '</small></div>';
                    }
                    return '<div><strong>' + nama + '</strong>' + spesHtml + '</div>';
                }
            },
            { data: 'dokumen', name: 'dokumen', searchable: false, orderable: false },
        ],
        columnDefs: [
            { targets: 0, width: "8%" },  // Antrian
            { targets: 1, width: "30%" }, // Nama Pasien
            { targets: 2, width: "20%" }, // Tanggal
            { targets: 3, width: "30%" }, // Dokter
            { targets: 4, width: "12%" }, // Dokumen
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

    // Prevent DataTables from showing blocking alert on AJAX errors; we'll handle errors gracefully
    $.fn.dataTable.ext.errMode = 'none';

    // Initial stats update (updateStats now returns the jqXHR so caller can handle errors)
    updateStats();

    // Helper: centralized AJAX error handler for table and stats
    function handleAjaxError(jqXHR, textStatus, errorThrown) {
        console.error('AJAX error:', textStatus, errorThrown, jqXHR);
        // Session expired or CSRF mismatch -> reload the page so user can re-authenticate
        if (jqXHR && (jqXHR.status === 419 || jqXHR.status === 401)) {
            console.warn('Session likely expired (status ' + jqXHR.status + '), reloading page...');
            location.reload();
            return;
        }
        // For other errors, do not show the DataTables alert; optionally show a non-blocking toast
        // We'll just log and rely on the next scheduled retry.
    }

    // Guarded auto-refresh: skip when page is hidden or when any modal is open
    var autoRefreshIntervalMs = 10000;
    function shouldAutoRefresh() {
        // Page visibility
        if (typeof document !== 'undefined' && document.visibilityState && document.visibilityState !== 'visible') {
            return false;
        }
        // Any bootstrap modal currently shown? if so, avoid refreshing to not disrupt user actions
        if ($('.modal.show').length > 0) {
            return false;
        }
        return true;
    }

    function autoRefresh() {
        if (!shouldAutoRefresh()) {
            console.debug('Auto-refresh skipped (hidden or modal open)');
            return;
        }

        // reload table, keep current page; attach fail handler
        try {
            var reloadPromise = table.ajax.reload(null, false);
            // DataTables' ajax.reload doesn't return a jqXHR in some setups; safeguard by using global ajaxStart/error
            // As a second layer, ensure updateStats is called and its failures handled
        } catch (e) {
            console.error('table.ajax.reload error:', e);
        }

        // Update stats and handle errors (updateStats returns the jqXHR)
        var statsPromise = updateStats();
        if (statsPromise && typeof statsPromise.fail === 'function') {
            statsPromise.fail(function(jqXHR, textStatus, errorThrown) {
                handleAjaxError(jqXHR, textStatus, errorThrown);
            });
        }
    }

    var autoRefreshTimer = setInterval(autoRefresh, autoRefreshIntervalMs);

    $('#filter_dokter, #filter_klinik, #filter_start_date, #filter_end_date').on('change', function () {
        table.ajax.reload();
        updateStats();
    });

    // Stat card click handler
    $('.stat-card-clickable').on('click', function() {
        var status = $(this).data('status');
        let startDate = $('#filter_start_date').val();
        let endDate = $('#filter_end_date').val();
        let filterDokter = $('#filter_dokter').val();
        let filterKlinik = $('#filter_klinik').val();
        // If user clicked the 'rujuk' stat, fetch rujuk list and show rujuk modal only
        if (status === 'rujuk') {
            $('#modalRujukList').modal('show');
            $('#rujuk-list-content').html('<div class="text-center"><span class="spinner-border"></span> Memuat data...</div>');
            $.ajax({
                url: '{{ route("erm.rawatjalans.rujuks") }}',
                method: 'GET',
                data: {
                    start_date: startDate,
                    end_date: endDate,
                    dokter_id: filterDokter
                },
                success: function(res2) {
                    if (res2.data && res2.data.length > 0) {
                        let html = '<table class="table table-bordered"><thead><tr><th>Waktu</th><th>Nama Pasien</th><th>Dokter Pengirim</th><th>Dokter Tujuan</th><th>Jenis</th><th>Penunjang</th><th>Surat</th><th>Keterangan</th></tr></thead><tbody>';
                        res2.data.forEach(function(item) {
                            let waktu = new Date(item.created_at).toLocaleString();
                            let pasien = item.pasien ? item.pasien.nama : '-';
                            let dokterPengirim = item.dokter_pengirim_id && item.dokter_pengirim ? item.dokter_pengirim.user.name : '-';
                            let dokterTujuan = item.dokter_tujuan_id && item.dokter_tujuan ? item.dokter_tujuan.user.name : '-';
                            // Build surat link/button (opens printable page in new tab) using named route template to avoid prefix issues
                            const suratTemplate = '{{ route("erm.rujuk.surat", ["id" => "__ID__"]) }}';
                            const suratUrl = suratTemplate.replace('__ID__', item.id);
                            const suratBtn = `<a href="${suratUrl}" target="_blank" class="btn btn-sm btn-secondary" title="Cetak Surat"><i class='fas fa-file-pdf'></i> Surat</a>`;
                            html += `<tr><td>${waktu}</td><td>${pasien}</td><td>${dokterPengirim}</td><td>${dokterTujuan}</td><td>${item.jenis_permintaan}</td><td>${item.penunjang || '-'}</td><td>${suratBtn}</td><td>${item.keterangan || '-'}</td></tr>`;
                        });
                        html += '</tbody></table>';
                        $('#rujuk-list-content').html(html);
                    } else {
                        $('#rujuk-list-content').html('<div class="text-center">Tidak ada data rujuk/konsultasi.</div>');
                    }
                },
                error: function() {
                    $('#rujuk-list-content').html('<div class="text-danger text-center">Gagal memuat data rujuk.</div>');
                }
            });
            return;
            return;
        }

        // Lab permintaan modal
        if (status === 'lab_permintaan') {
            $('#modalLabPermintaanList').modal('show');
            $('#lab-permintaan-list-content').html('<div class="text-center"><span class="spinner-border"></span> Memuat data...</div>');
            $.ajax({
                url: '{{ route("erm.rawatjalans.labpermintaan") }}',
                method: 'GET',
                data: {
                    start_date: startDate,
                    end_date: endDate,
                    dokter_id: filterDokter,
                    klinik_id: filterKlinik
                },
                success: function(res3) {
                    if (res3.data && res3.data.length) {
                        let html = '<table class="table table-bordered table-sm"><thead><tr>' +
                                   '<th>Waktu Diminta</th><th>Pasien</th><th>Pemeriksaan</th><th>Status</th><th>Diproses</th><th>Selesai</th><th>Durasi Proses</th>' +
                                   '</tr></thead><tbody>';
                        res3.data.forEach(function(item){
                            (item.lab_tests || []).forEach(function(t, idx){
                                const s = (t.status || '-');
                                const badgeClass = s === 'completed' ? 'badge-success' : (s === 'requested' ? 'badge-info' : 'badge-light text-dark');
                                const processedAt = t.processed_at || '-';
                                const completedAt = t.completed_at || '-';

                                // Recompute duration simply: completed - processed
                                let dur = '-';
                                if (processedAt !== '-' && completedAt !== '-') {
                                    // Parse timestamps (YYYY-MM-DD HH:MM:SS)
                                    const p = new Date(processedAt.replace(' ', 'T'));
                                    const c = new Date(completedAt.replace(' ', 'T'));
                                    if (!isNaN(p.getTime()) && !isNaN(c.getTime()) && c.getTime() >= p.getTime()) {
                                        const diffSec = Math.floor((c.getTime() - p.getTime()) / 1000);
                                        const m = Math.floor(diffSec / 60);
                                        const sRemain = diffSec % 60;
                                        dur = m > 0 ? (m + 'm' + (sRemain ? ' ' + sRemain + 's' : '')) : (sRemain + 's');
                                    } else {
                                        dur = '0s';
                                    }
                                }

                                html += '<tr>' +
                                    '<td>' + (idx === 0 ? (item.created_at || '-') : '') + '</td>' +
                                    '<td>' + (idx === 0 ? (item.pasien || '-') : '') + '</td>' +
                                    '<td>' + t.name + '</td>' +
                                    '<td><span class="badge ' + badgeClass + '">' + s + '</span></td>' +
                                    '<td>' + processedAt + '</td>' +
                                    '<td>' + completedAt + '</td>' +
                                    '<td>' + dur + '</td>' +
                                '</tr>';
                            });
                        });
                        html += '</tbody></table>';
                        $('#lab-permintaan-list-content').html(html);
                    } else {
                        $('#lab-permintaan-list-content').html('<div class="text-center">Tidak ada permintaan lab.</div>');
                    }
                },
                error: function() {
                    $('#lab-permintaan-list-content').html('<div class="text-danger text-center">Gagal memuat data permintaan lab.</div>');
                }
            });
            return;
        }

        // otherwise show visitation list modal and fetch by status
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
                            html += `<td>
                                <button class="btn btn-sm btn-success restore-status-btn" data-id="${item.id}">Pulihkan</button>
                                <button class="btn btn-sm btn-danger force-delete-btn ml-1" data-id="${item.id}">Hapus Permanen</button>
                            </td>`;
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

        // Delegate click for restore and force-delete buttons
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

        // Delegate click for force delete (permanent delete) button
        $('#visitation-list-content').on('click', '.force-delete-btn', function() {
            var visitationId = $(this).data('id');
            var btn = $(this);
            if (!confirm('Hapus permanen kunjungan ini? Tindakan ini tidak dapat dibatalkan.')) return;
            btn.prop('disabled', true).text('Menghapus...');
            $.ajax({
                url: '{{ route("erm.rawatjalans.forceDestroy") }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    visitation_id: visitationId
                },
                success: function(res) {
                    if (res.success) {
                        btn.removeClass('btn-danger').addClass('btn-secondary').text('Dihapus');
                        setTimeout(function(){
                            $('#modalVisitationList').modal('hide');
                            $('.stat-card-clickable[data-status="dibatalkan"]').click();
                        }, 800);
                    } else {
                        btn.prop('disabled', false).text('Hapus Permanen');
                        alert(res.message || 'Gagal menghapus kunjungan');
                    }
                },
                error: function(xhr) {
                    btn.prop('disabled', false).text('Hapus Permanen');
                    alert('Gagal menghapus kunjungan');
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

// Click lab icon inside table row (outside of other handlers)
$(document).on('click','.lab-icon', function(e){
    e.preventDefault();
    const visitationId = $(this).data('visitation-id');
    if(!visitationId) return;
    $('#modalLabPermintaanList').modal('show');
    $('#lab-permintaan-list-content').html('<div class="text-center"><span class="spinner-border"></span> Memuat data...</div>');
    const labVisitationUrlTemplate = '{{ route('erm.rawatjalans.labpermintaan.visitation',['visitationId'=>'__VID__']) }}';
    const labVisitationUrl = labVisitationUrlTemplate.replace('__VID__', visitationId);
    console.log('Fetching lab permintaan visitation', visitationId, labVisitationUrl);
    $.get(labVisitationUrl, function(res){
        console.log('Lab permintaan response', res);
        if(res.data && res.data.length){
            let html = '<table class="table table-bordered table-sm"><thead><tr><th>Pemeriksaan</th><th>Status</th><th>Requested</th><th>Diproses</th><th>Selesai</th><th>Durasi Proses</th></tr></thead><tbody>';
            res.data.forEach(function(t){
                const s = t.status || '-';
                const badgeClass = s === 'completed' ? 'badge-success' : (s === 'requested' ? 'badge-info' : (s==='processed'?'badge-warning':'badge-light text-dark'));
                html += '<tr>' +
                    '<td>'+ (t.lab_test || '-') +'</td>' +
                    '<td><span class="badge '+badgeClass+'">'+s+'</span></td>' +
                    '<td>'+ (t.requested_at || '-') +'</td>' +
                    '<td>'+ (t.processed_at || '-') +'</td>' +
                    '<td>'+ (t.completed_at || '-') +'</td>' +
                    '<td>'+ (t.process_time_human || '-') +'</td>' +
                '</tr>';
            });
            html += '</tbody></table>';
            $('#lab-permintaan-list-content').html(html);
        } else {
            const vid = (res.meta && res.meta.visitation_id) ? res.meta.visitation_id : visitationId;
            $('#lab-permintaan-list-content').html('<div class="text-center">Tidak ada permintaan lab untuk visitation ID '+vid+'.</div>');
            console.warn('No lab permintaan found for visitation', vid, res);
        }
    }).fail(function(){
        $('#lab-permintaan-list-content').html('<div class="text-center text-danger">Gagal memuat data.</div>');
        console.error('Failed to fetch lab permintaan visitation', visitationId);
    });
});

// Function to update statistics
function updateStats() {
    // Get current filter values
    let startDate = $('#filter_start_date').val();
    let endDate = $('#filter_end_date').val();
    let filterDokter = $('#filter_dokter').val();
    let filterKlinik = $('#filter_klinik').val();

    // Make AJAX request to get updated stats and return the jqXHR for callers
    return $.get('{{ route("erm.rawatjalans.stats") }}', {
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
        if (typeof stats.lab_permintaan !== 'undefined') {
            $('#stat-lab-permintaan').text(stats.lab_permintaan);
        }
    }).fail(function(jqXHR, textStatus, errorThrown) {
        console.error('Failed to update statistics', textStatus, errorThrown, jqXHR);
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

<script>
// Manage Pasien modal handlers (mirrors pasien index behavior)
function renderMerchChecklist(masterList, pasienReceipts) {
    let receivedIds = (pasienReceipts || []).map(r => (r.merchandise_id || r.merchandise_id === 0) ? r.merchandise_id : null).filter(Boolean);
    let $container = $('#unifiedMerchChecklistContainer');
    $container.empty();

    if (!masterList.length) {
        $container.html('<p class="text-muted">No merchandise items available.</p>');
        return;
    }

    let $form = $('<div class="list-group"></div>');
    let qtyMap = {};
    (pasienReceipts || []).forEach(r => { if (r.merchandise_id) qtyMap[r.merchandise_id] = r.quantity || 1; });

    masterList.forEach(item => {
        let received = receivedIds.includes(item.id);
        let checked = received ? 'checked' : '';
        let qty = received ? (qtyMap[item.id] || 1) : 1;
        let stock = item.stock || 0;
        let disabledAttr = stock <= 0 ? 'disabled' : '';
        let stockBadge = stock <= 0 ? '<span class="badge badge-danger ml-2">Habis</span>' : `<small class="text-muted ml-2">Stok: ${stock}</small>`;

        let $row = $(
            `<label class="list-group-item d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <input type="checkbox" class="merch-checkbox mr-3" data-id="${item.id}" data-stock="${stock}" ${checked} ${disabledAttr}>
                    <div>
                        <div><strong>${item.name}</strong> ${stockBadge}</div>
                        <div class="small text-muted">${item.description || ''}</div>
                    </div>
                </div>
                <div class="ml-3">
                    <input type="number" min="1" class="form-control form-control-sm merch-qty" data-id="${item.id}" data-stock="${stock}" value="${qty}" style="width:80px;" ${received ? '' : 'disabled'} ${stock <= 0 ? 'disabled' : ''}>
                </div>
            </label>`
        );
        $form.append($row);
    });

    $container.append($form);
}

function openManageModal(pasienId){
    if (!pasienId) return;
    $('#modalManagePasien').data('pasien-id', pasienId);
    $.get("{{ route('erm.pasien.show', '') }}/" + pasienId, function(resp){
        $('#manage_status_pasien').val(resp.status_pasien || 'Regular');
        $('#manage_status_akses').val(resp.status_akses || 'normal');
        $('#manage_status_review').val(resp.status_review || 'belum');
        $('#managePasienNama').text(resp.nama || '-');
        $('#managePasienId').text(resp.id || pasienId);
    }).always(function(){
        let pid = $('#modalManagePasien').data('pasien-id');
        $('#unifiedMerchChecklistContainer').html('<p class="text-muted">Memuat...</p>');
        $.when(
            $.get('/marketing/master-merchandise/data').fail(()=>{}),
            $.get('/erm/pasiens/' + pid + '/merchandises').fail(()=>{})
        ).done(function(masterResp, pasienResp){
            let masterData = masterResp && masterResp[0] ? (masterResp[0].data || masterResp[0]) : [];
            let pasienData = pasienResp && pasienResp[0] ? (pasienResp[0].data || pasienResp[0]) : [];
            renderMerchChecklist(masterData, pasienData);
        }).fail(function(){
            $('#unifiedMerchChecklistContainer').html('<p class="text-danger">Gagal memuat data.</p>');
        });
        $('#modalManagePasien').modal('show');
    });
}

$(document).on('click', '.open-manage-modal', function(e){ e.preventDefault(); openManageModal($(this).data('id')); });
$(document).on('click', '.btn-merch-checklist', function(){ openManageModal($(this).data('id')); });

$('#saveManagePasien').on('click', function(){
    let pasienId = $('#modalManagePasien').data('pasien-id');
    let p = $('#manage_status_pasien').val();
    let a = $('#manage_status_akses').val();
    let r = $('#manage_status_review').val();
    let reqs = [];
    reqs.push($.post('/erm/pasiens/' + pasienId + '/update-status', { _token: $('meta[name="csrf-token"]').attr('content'), status_pasien: p }));
    reqs.push($.post('/erm/pasiens/' + pasienId + '/update-status-akses', { _token: $('meta[name="csrf-token"]').attr('content'), status_akses: a }));
    reqs.push($.post('/erm/pasiens/' + pasienId + '/update-status-review', { _token: $('meta[name="csrf-token"]').attr('content'), status_review: r }));
    $.when.apply($, reqs).done(function(){
        Swal.fire({ icon: 'success', title: 'Tersimpan', text: 'Status pasien diperbarui.', timer: 1500, showConfirmButton: false });
        $('#rawatjalan-table').DataTable().ajax.reload(null, false);
    }).fail(function(){
        Swal.fire({ icon: 'error', title: 'Gagal', text: 'Tidak dapat menyimpan status.' });
    });
});

// Merchandise checkbox & qty handlers (delegated)
$(document).on('change', '.merch-checkbox', function(){
    let checked = $(this).is(':checked');
    let merchId = $(this).data('id');
    let pasienId = $('#modalManagePasien').data('pasien-id');
    if (!pasienId) return alert('Pasien ID missing');
    let $qtyInput = $('.merch-qty[data-id="' + merchId + '"]');
    let stock = parseInt($qtyInput.data('stock') || 0, 10);
    let qty = parseInt($qtyInput.val() || 1, 10);
    if (stock <= 0) { Swal.fire({ icon: 'warning', title: 'Stok habis', text: 'Stok item ini habis dan tidak dapat ditambahkan.' }); $(this).prop('checked', false); return; }
    if (qty > stock) { Swal.fire({ icon: 'warning', title: 'Stok tidak cukup', text: `Permintaan qty (${qty}) melebihi stok (${stock}).` }); $qtyInput.val(stock); return; }
    if (checked) {
        $qtyInput.prop('disabled', false);
        $.post('/erm/pasiens/' + pasienId + '/merchandises', { _token: $('meta[name="csrf-token"]').attr('content'), merchandise_id: merchId, quantity: qty }).done(function(resp){ if (resp && resp.id) $qtyInput.data('pm-id', resp.id); }).fail(function(){ alert('Failed to add merchandise'); $(this).prop('checked', false); $qtyInput.prop('disabled', true); });
    } else {
        $.get('/erm/pasiens/' + pasienId + '/merchandises', function(resp){ let rec = (resp.data || []).find(r => r.merchandise_id == merchId); if (!rec) return; $.ajax({ url: '/erm/pasiens/' + pasienId + '/merchandises/' + rec.id, type: 'DELETE', data: { _token: $('meta[name="csrf-token"]').attr('content') }, success: function(){ $qtyInput.prop('disabled', true); $qtyInput.removeData('pm-id'); }, error: function(){ alert('Failed to remove merchandise'); } }); });
    }
});

$(document).on('change', '.merch-qty', function(){
    let $input = $(this);
    let merchId = $input.data('id');
    let pasienId = $('#modalManagePasien').data('pasien-id');
    if (!pasienId) return alert('Pasien ID missing');
    let qty = parseInt($input.val() || 1, 10); if (qty < 1) { qty = 1; $input.val(1); }
    let stock = parseInt($input.data('stock') || 0, 10); if (stock <= 0) { Swal.fire({ icon: 'warning', title: 'Stok habis', text: 'Stok item ini habis dan tidak dapat diubah.' }); $input.val(1); return; }
    if (qty > stock) { Swal.fire({ icon: 'warning', title: 'Stok tidak cukup', text: `Permintaan qty (${qty}) melebihi stok (${stock}).` }); $input.val(stock); qty = stock; }
    let $checkbox = $('.merch-checkbox[data-id="' + merchId + '"]'); if (!$checkbox.is(':checked')) return; let pmId = $input.data('pm-id'); if (pmId) { $.ajax({ url: '/erm/pasiens/' + pasienId + '/merchandises/' + pmId, type: 'PUT', data: { _token: $('meta[name="csrf-token"]').attr('content'), quantity: qty } }); return; } $.get('/erm/pasiens/' + pasienId + '/merchandises', function(resp){ let rec = (resp.data || []).find(r => r.merchandise_id == merchId); if (!rec) return; $.ajax({ url: '/erm/pasiens/' + pasienId + '/merchandises/' + rec.id, type: 'PUT', data: { _token: $('meta[name="csrf-token"]').attr('content'), quantity: qty }, success: function(){ $input.data('pm-id', rec.id); } }); });
});

// Small edit-status handlers
$(document).on('click', '.edit-status-akses-btn', function() { let pasienId = $(this).data('pasien-id'); let currentStatus = $(this).data('current-status'); $('#edit_status_akses').val(currentStatus); $('#modalEditStatusAkses').data('pasien-id', pasienId); $('#modalEditStatusAkses').modal('show'); });
$('#saveEditStatusAkses').on('click', function() { let pasienId = $('#modalEditStatusAkses').data('pasien-id'); let newStatus = $('#edit_status_akses').val(); $.ajax({ url: '/erm/pasiens/' + pasienId + '/update-status-akses', type: 'POST', data: { _token: $('meta[name="csrf-token"]').attr('content'), status_akses: newStatus }, success: function(response) { if(response.success) { $('#modalEditStatusAkses').modal('hide'); $('#rawatjalan-table').DataTable().ajax.reload(); Swal.fire({ icon: 'success', title: 'Berhasil!', text: 'Status akses pasien berhasil diperbarui.', timer: 2000, showConfirmButton: false }); } } }); });

$(document).on('click', '.edit-status-review-btn', function() { let pasienId = $(this).data('pasien-id'); let currentStatus = $(this).data('current-status'); $('#edit_status_review').val(currentStatus); $('#modalEditStatusReview').data('pasien-id', pasienId); $('#modalEditStatusReview').modal('show'); });
$('#saveEditStatusReview').on('click', function() { let pasienId = $('#modalEditStatusReview').data('pasien-id'); let newStatus = $('#edit_status_review').val(); $.ajax({ url: '/erm/pasiens/' + pasienId + '/update-status-review', type: 'POST', data: { _token: $('meta[name="csrf-token"]').attr('content'), status_review: newStatus }, success: function(response) { if(response.success) { $('#modalEditStatusReview').modal('hide'); $('#rawatjalan-table').DataTable().ajax.reload(); Swal.fire({ icon: 'success', title: 'Berhasil!', text: 'Status review pasien berhasil diperbarui.', timer: 2000, showConfirmButton: false }); } } }); });
</script>

<!-- Modal: Pasien Merchandise -->
<div class="modal fade" id="modalPasienMerch" tabindex="-1" role="dialog" aria-labelledby="modalPasienMerchTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalPasienMerchTitle">Merchandise Pasien</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="pasien-merch-list">
                    <table class="table table-sm table-striped" id="table-pasien-merch">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nama Item</th>
                                <th>Deskripsi</th>
                                <th>Qty</th>
                                <th>Notes</th>
                                <th>Diberikan Pada</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
// Handler for clicking shopping bag icon next to patient name
$(document).on('click', '.pasien-merch', function(e) {
    e.preventDefault();
    var pasienId = $(this).data('pasien-id');
    if (!pasienId) return;
    // clear table body
    $('#table-pasien-merch tbody').empty();
    // fetch merchandises
    $.get("{{ url('erm/pasien') }}/" + pasienId + "/merchandises", function(res) {
        if (res && res.data) {
            var rows = '';
            res.data.forEach(function(item, idx) {
                rows += '<tr>' +
                    '<td>' + (idx + 1) + '</td>' +
                    '<td>' + (item.nama || '-') + '</td>' +
                    '<td>' + (item.description || '-') + '</td>' +
                    '<td>' + (item.quantity || '-') + '</td>' +
                    '<td>' + (item.notes || '-') + '</td>' +
                    '<td>' + (item.given_at || '-') + '</td>' +
                    '</tr>';
            });
            if (rows === '') {
                rows = '<tr><td colspan="6" class="text-center">Tidak ada merchandise.</td></tr>';
            }
            $('#table-pasien-merch tbody').html(rows);
            $('#modalPasienMerch').modal('show');
        } else {
            Swal.fire('Info', 'Tidak dapat mengambil data merchandise.', 'info');
        }
    }).fail(function() {
        Swal.fire('Error', 'Terjadi kesalahan saat mengambil data.', 'error');
    });
});
</script>

<!-- Modal: Metode Bayar Edit -->
<div class="modal fade" id="modalMetodeBayar" tabindex="-1" role="dialog" aria-labelledby="modalMetodeBayarTitle" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalMetodeBayarTitle">Ubah Metode Bayar</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="form-metode-bayar">
            <div class="modal-body">
                <input type="hidden" id="metode-visitation-id" name="visitation_id" />
                <div class="form-group mb-2">
                    <label for="metode-bayar-select">Pilih Metode Bayar</label>
                    <select id="metode-bayar-select" name="metode_bayar_id" class="form-control">
                        <option value="">-- Pilih --</option>
                        @foreach($metodeBayar as $m)
                            <option value="{{ $m->id }}">{{ $m->nama }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary" id="save-metode-bayar-btn">Simpan</button>
            </div>
            </form>
        </div>
    </div>
</div>

<script>
// Open metode bayar modal when badge clicked (populate select)
$(document).on('click', '.metode-bayar-btn', function(e){
    e.preventDefault();
    var metode = $(this).data('metode') || '-';
    var metodeId = $(this).data('metode-id') || '';
    var visitationId = $(this).data('visitation-id') || '';
    $('#metode-visitation-id').val(visitationId);
    $('#metode-bayar-select').val(metodeId);
    $('#modalMetodeBayar').modal('show');
});

// Submit metode bayar change
$('#form-metode-bayar').submit(function(e){
    e.preventDefault();
    var visitationId = $('#metode-visitation-id').val();
    var metodeId = $('#metode-bayar-select').val();
    if (!visitationId || !metodeId) {
        Swal.fire('Error', 'Pilih metode bayar terlebih dahulu.', 'warning');
        return;
    }
    var url = '{{ route("erm.rawatjalans.updateMetodeBayar") }}';
    $.post(url, {
        _token: '{{ csrf_token() }}',
        visitation_id: visitationId,
        metode_bayar_id: metodeId
    }, function(res){
            if (res.success) {
                // update badge text and class in table
                var selector = '.metode-bayar-btn[data-visitation-id="' + visitationId + '"]';
                var el = $(selector);
                var newText = res.metode || $('#metode-bayar-select option:selected').text();
                // Determine class: prefer metodeColorMap by id, otherwise apply name-based mapping same as renderer
                var newClass = 'badge-info';
                try {
                    if (metodeId && window.metodeColorMap && window.metodeColorMap[metodeId]) {
                        newClass = window.metodeColorMap[metodeId];
                    } else {
                        var nt = (newText || '').toLowerCase();
                        if (nt.indexOf('umum') !== -1) newClass = 'badge-success';
                        else if (nt.indexOf('inhealth') !== -1) newClass = 'badge-info';
                        else if (nt.indexOf('bri life') !== -1 || nt.indexOf('brilife') !== -1) newClass = 'badge-primary';
                        else if (nt.indexOf('bni life') !== -1 || nt.indexOf('bnilife') !== -1) newClass = 'badge-warning';
                        else if (nt.indexOf('admedika') !== -1) newClass = 'badge-danger';
                    }
                } catch(e) {}
                if (el.length) {
                    var small = el.find('small.badge');
                    small.text(newText);
                    // remove previous badge- classes and add new
                    small.removeClass(function(index, className) {
                        return (className.match(/(^|\s)badge-\S+/g) || []).join(' ');
                    }).addClass('badge ' + newClass + ' ml-1');
                    el.data('metode', newText);
                    el.data('metode-id', metodeId);
                }
                $('#modalMetodeBayar').modal('hide');
                Swal.fire('Berhasil', 'Metode bayar diperbarui.', 'success');
            } else {
            Swal.fire('Gagal', res.message || 'Gagal memperbarui metode bayar.', 'error');
        }
    }).fail(function(xhr){
        Swal.fire('Error', 'Terjadi kesalahan saat menyimpan.', 'error');
        console.error(xhr.responseText);
    });
});
</script>

@endsection
