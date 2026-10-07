{{-- Modal: Pasien Ganda (Admin only) — find duplicates and merge them into one patient --}}
<style>
#pasienGandaModal .pg-group { border: 1px solid rgba(0, 0, 0, .1); border-radius: 6px; margin-bottom: 12px; }
#pasienGandaModal .pg-group-head { display: flex; flex-wrap: wrap; align-items: center; gap: 6px; padding: 8px 12px; border-bottom: 1px solid rgba(0, 0, 0, .08); }
#pasienGandaModal .pg-group-foot { display: flex; justify-content: flex-end; align-items: center; gap: 8px; padding: 8px 12px; border-top: 1px solid rgba(0, 0, 0, .08); }
#pasienGandaModal .pg-group table { margin-bottom: 0; font-size: 12.5px; }
#pasienGandaModal .pg-group td, #pasienGandaModal .pg-group th { vertical-align: middle; padding: 6px 8px; }
#pasienGandaModal .pg-group tr.pg-target { background: rgba(40, 167, 69, .10); }
#pasienGandaModal .pg-group tr.pg-skip { opacity: .55; }
#pasienGandaModal .pg-alamat { max-width: 220px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
#pasienGandaModal .pg-diff { color: #e83e8c; font-weight: 600; }
</style>
<div class="modal fade" id="pasienGandaModal" tabindex="-1" role="dialog" aria-labelledby="pasienGandaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="pasienGandaModalLabel"><i class="fas fa-user-friends mr-1"></i> Duplikasi Pasien</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-light border small mb-3">
                    <i class="fas fa-info-circle mr-1"></i>
                    Pilih <strong>Utama</strong> (data yang dipertahankan) dan centang pasien yang akan <strong>digabung</strong>.
                    Semua kunjungan, resep, lab, alergi, surat, follow up, dll. dipindah ke pasien utama, data kosong pasien utama diisi dari pasien yang digabung,
                    lalu pasien yang digabung dihapus. <strong>Tidak bisa dibatalkan.</strong>
                </div>

                {{-- Gabungkan manual (for duplicates the list does not find) --}}
                <div class="mb-3">
                    <a href="#" id="pgToggleManual" class="small"><i class="fas fa-code-branch mr-1"></i> Gabungkan manual</a>
                    <div id="pgManualPanel" class="alert alert-info mt-2 d-none">
                        <div class="form-row">
                            <div class="col-md-5 mb-2">
                                <label class="small mb-1" for="pgManualSource">Pasien ganda (akan dihapus)</label>
                                <select id="pgManualSource" class="form-control"></select>
                            </div>
                            <div class="col-md-5 mb-2">
                                <label class="small mb-1" for="pgManualTarget">Gabungkan ke (pasien utama)</label>
                                <select id="pgManualTarget" class="form-control"></select>
                            </div>
                            <div class="col-md-2 mb-2 d-flex align-items-end">
                                <button type="button" class="btn btn-primary btn-block" id="pgManualSave"><i class="fas fa-check mr-1"></i> Gabungkan</button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Cari & filter --}}
                <div class="form-row">
                    <div class="col-md-6 mb-2">
                        <div class="input-group input-group-sm">
                            <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-search"></i></span></div>
                            <input type="search" id="pgSearch" class="form-control" placeholder="Cari nama, RM, no. identitas, no. HP" autocomplete="off">
                        </div>
                    </div>
                    <div class="col-md-4 mb-2">
                        <select id="pgLevel" class="form-control form-control-sm">
                            <option value="">Semua kemungkinan</option>
                            <option value="tinggi">Kemungkinan tinggi (nama sama / no. identitas sama)</option>
                            <option value="sedang">Perlu dicek (nama mirip)</option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <button type="button" class="btn btn-sm btn-light border btn-block" id="pgReload"><i class="fas fa-sync-alt mr-1"></i> Muat ulang</button>
                    </div>
                </div>
                <div id="pgSummary" class="small text-muted mb-2"></div>
                <div id="pgList"></div>
                <div class="text-center">
                    <button type="button" class="btn btn-sm btn-light border d-none" id="pgMore">Tampilkan lebih banyak</button>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
