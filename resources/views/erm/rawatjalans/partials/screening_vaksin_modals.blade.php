<!-- Modal Screening Vaksin -->
<div class="modal fade" id="modalScreeningVaksin" tabindex="-1" role="dialog" aria-labelledby="modalScreeningVaksinTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success">
                <h5 class="modal-title text-white" id="modalScreeningVaksinTitle">
                    <i class="fas fa-syringe mr-2"></i><span id="screening-vaksin-modal-title">Screening Vaksin</span>
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                <form id="form-screening-vaksin">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>
                        <strong>Penting:</strong> Harap isi screening vaksin sebelum tindakan vaksinasi dilakukan.
                    </div>

                    <h6 class="text-success mb-3"><i class="fas fa-clipboard-check mr-2"></i><strong>SCREENING VAKSIN</strong></h6>
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
                                    <td class="align-middle">Apakah Anda sakit hari ini?</td>
                                    <td class="text-center align-middle">
                                        <div class="custom-control custom-radio">
                                            <input type="radio" id="sakit_hari_ini_ya" name="sakit_hari_ini" value="ya" class="custom-control-input" required>
                                            <label class="custom-control-label" for="sakit_hari_ini_ya"></label>
                                        </div>
                                    </td>
                                    <td class="text-center align-middle">
                                        <div class="custom-control custom-radio">
                                            <input type="radio" id="sakit_hari_ini_tidak" name="sakit_hari_ini" value="tidak" class="custom-control-input" required checked>
                                            <label class="custom-control-label" for="sakit_hari_ini_tidak"></label>
                                        </div>
                                    </td>
                                </tr>
                                <tr class="bg-light">
                                    <td class="text-center align-middle font-weight-bold">2</td>
                                    <td class="align-middle">Apakah Anda mempunyai alergi obat, makanan, atau vaksin?</td>
                                    <td class="text-center align-middle">
                                        <div class="custom-control custom-radio">
                                            <input type="radio" id="alergi_obat_makanan_vaksin_ya" name="alergi_obat_makanan_vaksin" value="ya" class="custom-control-input" required>
                                            <label class="custom-control-label" for="alergi_obat_makanan_vaksin_ya"></label>
                                        </div>
                                    </td>
                                    <td class="text-center align-middle">
                                        <div class="custom-control custom-radio">
                                            <input type="radio" id="alergi_obat_makanan_vaksin_tidak" name="alergi_obat_makanan_vaksin" value="tidak" class="custom-control-input" required checked>
                                            <label class="custom-control-label" for="alergi_obat_makanan_vaksin_tidak"></label>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-center align-middle font-weight-bold">3</td>
                                    <td class="align-middle">Apakah Anda pernah mengalami efek samping berat setelah mendapat vaksinasi?</td>
                                    <td class="text-center align-middle">
                                        <div class="custom-control custom-radio">
                                            <input type="radio" id="efek_samping_vaksin_berat_ya" name="efek_samping_vaksin_berat" value="ya" class="custom-control-input" required>
                                            <label class="custom-control-label" for="efek_samping_vaksin_berat_ya"></label>
                                        </div>
                                    </td>
                                    <td class="text-center align-middle">
                                        <div class="custom-control custom-radio">
                                            <input type="radio" id="efek_samping_vaksin_berat_tidak" name="efek_samping_vaksin_berat" value="tidak" class="custom-control-input" required checked>
                                            <label class="custom-control-label" for="efek_samping_vaksin_berat_tidak"></label>
                                        </div>
                                    </td>
                                </tr>
                                <tr class="bg-light">
                                    <td class="text-center align-middle font-weight-bold">4</td>
                                    <td class="align-middle">Apakah Anda menderita kanker solid atau darah, AIDS, atau penyakit lain yang menyebabkan masalah kekebalan tubuh?</td>
                                    <td class="text-center align-middle">
                                        <div class="custom-control custom-radio">
                                            <input type="radio" id="gangguan_kekebalan_tubuh_ya" name="gangguan_kekebalan_tubuh" value="ya" class="custom-control-input" required>
                                            <label class="custom-control-label" for="gangguan_kekebalan_tubuh_ya"></label>
                                        </div>
                                    </td>
                                    <td class="text-center align-middle">
                                        <div class="custom-control custom-radio">
                                            <input type="radio" id="gangguan_kekebalan_tubuh_tidak" name="gangguan_kekebalan_tubuh" value="tidak" class="custom-control-input" required checked>
                                            <label class="custom-control-label" for="gangguan_kekebalan_tubuh_tidak"></label>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-center align-middle font-weight-bold">5</td>
                                    <td class="align-middle">Dalam waktu enam bulan terakhir, apakah Anda mendapatkan obat steroid (prednisone, methylprednisolone, kortison, atau sejenisnya), obat anti kanker, atau radioterapi?</td>
                                    <td class="text-center align-middle">
                                        <div class="custom-control custom-radio">
                                            <input type="radio" id="obat_steroid_atau_terapi_ya" name="obat_steroid_atau_terapi" value="ya" class="custom-control-input" required>
                                            <label class="custom-control-label" for="obat_steroid_atau_terapi_ya"></label>
                                        </div>
                                    </td>
                                    <td class="text-center align-middle">
                                        <div class="custom-control custom-radio">
                                            <input type="radio" id="obat_steroid_atau_terapi_tidak" name="obat_steroid_atau_terapi" value="tidak" class="custom-control-input" required checked>
                                            <label class="custom-control-label" for="obat_steroid_atau_terapi_tidak"></label>
                                        </div>
                                    </td>
                                </tr>
                                <tr class="bg-light">
                                    <td class="text-center align-middle font-weight-bold">6</td>
                                    <td class="align-middle">Dalam beberapa tahun terakhir, apakah Anda pernah mendapat transfusi darah atau produk darah, atau Anda pernah mendapat obat yang disebut imun (gama) globulin?</td>
                                    <td class="text-center align-middle">
                                        <div class="custom-control custom-radio">
                                            <input type="radio" id="transfusi_darah_atau_imunoglobulin_ya" name="transfusi_darah_atau_imunoglobulin" value="ya" class="custom-control-input" required>
                                            <label class="custom-control-label" for="transfusi_darah_atau_imunoglobulin_ya"></label>
                                        </div>
                                    </td>
                                    <td class="text-center align-middle">
                                        <div class="custom-control custom-radio">
                                            <input type="radio" id="transfusi_darah_atau_imunoglobulin_tidak" name="transfusi_darah_atau_imunoglobulin" value="tidak" class="custom-control-input" required checked>
                                            <label class="custom-control-label" for="transfusi_darah_atau_imunoglobulin_tidak"></label>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-center align-middle font-weight-bold">7</td>
                                    <td class="align-middle">Untuk wanita: Apakah Anda hamil, atau kemungkinan akan/mau hamil dalam beberapa bulan berikut?</td>
                                    <td class="text-center align-middle">
                                        <div class="custom-control custom-radio">
                                            <input type="radio" id="hamil_atau_rencana_hamil_ya" name="hamil_atau_rencana_hamil" value="ya" class="custom-control-input" required>
                                            <label class="custom-control-label" for="hamil_atau_rencana_hamil_ya"></label>
                                        </div>
                                    </td>
                                    <td class="text-center align-middle">
                                        <div class="custom-control custom-radio">
                                            <input type="radio" id="hamil_atau_rencana_hamil_tidak" name="hamil_atau_rencana_hamil" value="tidak" class="custom-control-input" required checked>
                                            <label class="custom-control-label" for="hamil_atau_rencana_hamil_tidak"></label>
                                        </div>
                                    </td>
                                </tr>
                                <tr class="bg-light">
                                    <td class="text-center align-middle font-weight-bold">8</td>
                                    <td class="align-middle">Apakah Anda pernah mendapat vaksinasi dalam 4 minggu sebelum ini?</td>
                                    <td class="text-center align-middle">
                                        <div class="custom-control custom-radio">
                                            <input type="radio" id="vaksinasi_4_minggu_terakhir_ya" name="vaksinasi_4_minggu_terakhir" value="ya" class="custom-control-input" required>
                                            <label class="custom-control-label" for="vaksinasi_4_minggu_terakhir_ya"></label>
                                        </div>
                                    </td>
                                    <td class="text-center align-middle">
                                        <div class="custom-control custom-radio">
                                            <input type="radio" id="vaksinasi_4_minggu_terakhir_tidak" name="vaksinasi_4_minggu_terakhir" value="tidak" class="custom-control-input" required checked>
                                            <label class="custom-control-label" for="vaksinasi_4_minggu_terakhir_tidak"></label>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="form-group">
                        <label for="catatan_screening_vaksin" class="font-weight-bold">Catatan Tambahan (Opsional):</label>
                        <textarea class="form-control" id="catatan_screening_vaksin" name="catatan" rows="3" placeholder="Masukkan catatan tambahan jika ada..."></textarea>
                    </div>

                    <input type="hidden" id="screening-vaksin-visitation-id" name="visitation_id">
                    <input type="hidden" id="screening-vaksin-edit-mode" value="false">
                    <input type="hidden" id="screening-vaksin-id" name="screening_id">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times mr-1"></i>Batal
                </button>
                <button type="button" class="btn btn-success" id="btn-simpan-screening-vaksin">
                    <i class="fas fa-save mr-1"></i><span id="screening-vaksin-btn-text">Simpan Screening</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal View Screening Vaksin -->
<div class="modal fade" id="modalViewScreeningVaksin" tabindex="-1" role="dialog" aria-labelledby="modalViewScreeningVaksinTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="modalViewScreeningVaksinTitle">
                    <i class="fas fa-syringe mr-2"></i>Data Screening Vaksin
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                <h6 class="text-success mb-3"><i class="fas fa-clipboard-check mr-2"></i><strong>HASIL SCREENING VAKSIN</strong></h6>
                <div class="table-responsive mb-4">
                    <table class="table table-bordered">
                        <tbody>
                            <tr>
                                <td class="font-weight-bold" style="width: 70%;">Apakah Anda sakit hari ini?</td>
                                <td class="text-center" style="width: 30%;"><span id="view-vaksin-sakit-hari-ini"></span></td>
                            </tr>
                            <tr class="bg-light">
                                <td class="font-weight-bold">Apakah Anda mempunyai alergi obat, makanan, atau vaksin?</td>
                                <td class="text-center"><span id="view-vaksin-alergi-obat-makanan-vaksin"></span></td>
                            </tr>
                            <tr>
                                <td class="font-weight-bold">Apakah Anda pernah mengalami efek samping berat setelah mendapat vaksinasi?</td>
                                <td class="text-center"><span id="view-vaksin-efek-samping-vaksin-berat"></span></td>
                            </tr>
                            <tr class="bg-light">
                                <td class="font-weight-bold">Apakah Anda menderita kanker solid atau darah, AIDS, atau penyakit lain yang menyebabkan masalah kekebalan tubuh?</td>
                                <td class="text-center"><span id="view-vaksin-gangguan-kekebalan-tubuh"></span></td>
                            </tr>
                            <tr>
                                <td class="font-weight-bold">Dalam waktu enam bulan terakhir, apakah Anda mendapatkan obat steroid (prednisone, methylprednisolone, kortison, atau sejenisnya), obat anti kanker, atau radioterapi?</td>
                                <td class="text-center"><span id="view-vaksin-obat-steroid-atau-terapi"></span></td>
                            </tr>
                            <tr class="bg-light">
                                <td class="font-weight-bold">Dalam beberapa tahun terakhir, apakah Anda pernah mendapat transfusi darah atau produk darah, atau Anda pernah mendapat obat yang disebut imun (gama) globulin?</td>
                                <td class="text-center"><span id="view-vaksin-transfusi-darah-atau-imunoglobulin"></span></td>
                            </tr>
                            <tr>
                                <td class="font-weight-bold">Untuk wanita: Apakah Anda hamil, atau kemungkinan akan/mau hamil dalam beberapa bulan berikut?</td>
                                <td class="text-center"><span id="view-vaksin-hamil-atau-rencana-hamil"></span></td>
                            </tr>
                            <tr class="bg-light">
                                <td class="font-weight-bold">Apakah Anda pernah mendapat vaksinasi dalam 4 minggu sebelum ini?</td>
                                <td class="text-center"><span id="view-vaksin-vaksinasi-4-minggu-terakhir"></span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-info mb-2"><i class="fas fa-sticky-note mr-2"></i>Catatan Tambahan</h6>
                        <p id="view-vaksin-catatan" class="border p-3 bg-light">-</p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-info mb-2"><i class="fas fa-clock mr-2"></i>Waktu Pengisian</h6>
                        <p id="view-vaksin-created-at" class="border p-3 bg-light">-</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times mr-1"></i>Tutup
                </button>
                <button type="button" class="btn btn-warning" id="btn-edit-screening-vaksin">
                    <i class="fas fa-edit mr-1"></i>Edit Screening
                </button>
            </div>
        </div>
    </div>
</div>