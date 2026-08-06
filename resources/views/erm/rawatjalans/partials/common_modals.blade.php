<!-- Rawat Jalan: Common modals (lazy-loaded to keep initial HTML light) -->

<!-- Unified Manage Pasien Modal (copied from pasien index) -->
<div class="modal fade manage-pasien-modal" id="modalManagePasien" tabindex="-1" role="dialog" aria-labelledby="modalManagePasienLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalManagePasienLabel">Kelola Pasien</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="manage-pasien-summary mb-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="manage-pasien-summary__name" id="managePasienNama">-</div>
                            <div class="manage-pasien-summary__meta">No. RM: <span id="managePasienId">-</span></div>
                        </div>
                    </div>
                </div>
                <form id="managePasienForm">
                    <div class="row">
                        <div class="col-lg-6 mb-4 mb-lg-0">
                            <div class="manage-pasien-panel">
                                <div class="manage-pasien-panel__title">Data Identitas</div>
                                <div class="form-group">
                                <label for="manage_identity_document">Dokumen Identitas</label>
                                <select class="form-control" id="manage_identity_document" name="identity_document" required>
                                    <option value="ktp">KTP</option>
                                    <option value="sim">SIM</option>
                                    <option value="paspor">Paspor</option>
                                    <option value="kia">KIA</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="manage_identity_number">Nomor Identitas</label>
                                <input type="text" class="form-control" id="manage_identity_number" name="identity_number" required>
                            </div>
                            <div class="form-group">
                                <label for="manage_nama">Nama Pasien</label>
                                <input type="text" class="form-control" id="manage_nama" name="nama" required>
                            </div>
                            <div class="form-group">
                                <label for="manage_tanggal_lahir">Tanggal Lahir</label>
                                <input type="date" class="form-control" id="manage_tanggal_lahir" name="tanggal_lahir" required>
                            </div>
                            <div class="form-group mb-md-0">
                                <label for="manage_gender">Gender</label>
                                <select class="form-control" id="manage_gender" name="gender" required>
                                    <option value="Laki-laki">Laki-laki</option>
                                    <option value="Perempuan">Perempuan</option>
                                </select>
                            </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="manage-pasien-panel manage-pasien-panel--accent">
                                <div class="manage-pasien-panel__title">Kontak dan Status</div>
                                <div class="form-group">
                                <label for="manage_alamat">Alamat</label>
                                <textarea class="form-control" id="manage_alamat" name="alamat" rows="4" required></textarea>
                            </div>
                            <div class="form-group">
                                <label for="manage_no_hp">No. HP</label>
                                <input type="text" class="form-control" id="manage_no_hp" name="no_hp" required>
                            </div>
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
                            <div class="form-group mb-0">
                                <label for="manage_status_review">Status Review</label>
                                <select class="form-control" id="manage_status_review" name="status_review" required>
                                    <option value="sudah">Sudah</option>
                                    <option value="belum">Belum</option>
                                </select>
                            </div>
                            </div>
                        </div>
                    </div>
                </form>
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

<div class="modal fade" id="modalNotificationHistory" tabindex="-1" role="dialog" aria-labelledby="modalNotificationHistoryTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalNotificationHistoryTitle">Riwayat Notifikasi Dokter ke Perawat</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-end mb-3">
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btn-mark-all-notification-read" style="display:none;">
                        <i class="fas fa-check-double mr-1"></i>Tandai Semua Telah Dibaca
                    </button>
                </div>
                <div id="notification-history-content">
                    <div class="text-center"><span class="spinner-border"></span> Memuat data...</div>
                </div>
            </div>
        </div>
    </div>
</div>

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

<div class="modal fade" id="modalEditAntrian" tabindex="-1" role="dialog" aria-labelledby="modalEditAntrianTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEditAntrianTitle">Edit Kunjungan</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="form-edit-antrian">
                <div class="modal-body">
                    <input type="hidden" id="edit-antrian-visitation-id" name="visitation_id">
                    <input type="hidden" id="edit-antrian-klinik-id" name="klinik_id">
                    <div class="form-group">
                        <label for="edit-antrian-tanggal">Tanggal Kunjungan</label>
                        <input type="date" class="form-control" id="edit-antrian-tanggal" name="tanggal_visitation" required>
                    </div>
                    <div class="form-group">
                        <label for="edit-antrian-dokter">Dokter</label>
                        <select class="form-control select2-edit-antrian" id="edit-antrian-dokter" name="dokter_id" required>
                            <option value="">Pilih Dokter</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit-antrian-metode-bayar">Metode Bayar</label>
                        <select class="form-control select2-edit-antrian" id="edit-antrian-metode-bayar" name="metode_bayar_id" required>
                            <option value="">Pilih Metode Bayar</option>
                            @foreach($metodeBayar as $metode)
                                <option value="{{ $metode->id }}">{{ $metode->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit-antrian-no">Nomor Antrian</label>
                        <input type="number" min="1" class="form-control" id="edit-antrian-no" name="no_antrian" required>
                    </div>
                    <div class="form-group mb-0">
                        <label for="edit-antrian-waktu">Waktu Kunjungan</label>
                        <input type="time" class="form-control" id="edit-antrian-waktu" name="waktu_kunjungan">
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-danger" id="btn-batalkan-kunjungan-modal">Batalkan Kunjungan</button>
                    <div>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" id="btn-save-edit-antrian">Simpan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
