<div class="modal fade" id="suratKeluarModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="suratKeluarModalLabel">Surat Keluar</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">&times;</button>
            </div>
            <form id="suratKeluarForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="id" id="sk_id">
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Tanggal Dibuat</label>
                            <input type="date" name="tgl_dibuat" id="tgl_dibuat" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="form-group col-md-4">
                            <label>Jenis Surat</label>
                            <select name="jenis_surat" id="jenis_surat" class="form-control">
                                <option value="">-- Pilih Jenis --</option>
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Instansi</label>
                            <select name="instansi" id="instansi" class="form-control">
                                <option value="">-- Pilih Instansi --</option>
                                <option value="Premiere Belova">Premiere Belova</option>
                                <option value="Belova Skincare">Belova Skincare</option>
                                <option value="BCL">BCL</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-12">
                            <label>No Surat</label>
                            <input type="text" name="no_surat" id="no_surat" class="form-control bg-light" readonly placeholder="(otomatis, tidak dapat diedit)">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Perihal</label>
                        <textarea name="deskripsi" id="deskripsi" class="form-control" rows="4"></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Jenis Tujuan</label>
                            <select name="jenis_tujuan" id="jenis_tujuan" class="form-control">
                                <option value="">-- Pilih Jenis Tujuan --</option>
                                <option value="internal">Internal</option>
                                <option value="external">External</option>
                            </select>
                        </div>
                        <div class="form-group col-md-8" id="kepada_internal_group" style="display:none;">
                            <label>Kepada (Internal)</label>
                            <select id="kepada_user" class="form-control">
                                <option value="">-- Pilih User --</option>
                            </select>
                        </div>
                        <div class="form-group col-md-8" id="kepada_external_group" style="display:none;">
                            <label>Kepada (External)</label>
                            <input type="text" id="kepada_text" class="form-control" placeholder="Masukkan tujuan eksternal">
                        </div>
                    </div>
                    <input type="hidden" name="kepada" id="kepada">
                    <div class="form-group">
                        <label>Lampiran (PDF)</label>
                        <input type="file" name="lampiran" id="lampiran" accept="application/pdf" class="form-control-file">
                        <div id="existingLampiran" class="mt-2"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-primary" id="saveSuratBtn">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
