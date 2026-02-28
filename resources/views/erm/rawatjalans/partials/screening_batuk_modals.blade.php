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
                    <i class="fas fa-save mr-1"></i><span id="screening-btn-text">Simpan &amp; Lanjutkan</span>
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
