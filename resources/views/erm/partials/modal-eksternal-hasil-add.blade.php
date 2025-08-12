<!-- Modal for Adding External Lab Results -->
<div class="modal fade" id="addEksternalHasilModal" tabindex="-1" role="dialog" aria-labelledby="addEksternalHasilModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addEksternalHasilModalLabel">Tambah Hasil Lab Eksternal</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="addEksternalHasilForm" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="visitation_id" value="{{ $visitation->id }}">
                    
                    <div class="form-group">
                        <label for="asal_lab">Asal Lab <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="asal_lab" name="asal_lab" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="nama_pemeriksaan">Nama Pemeriksaan <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nama_pemeriksaan" name="nama_pemeriksaan" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="tanggal_pemeriksaan">Tanggal Pemeriksaan <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="tanggal_pemeriksaan" name="tanggal_pemeriksaan" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="dokter">Dokter <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="dokter" name="dokter" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="catatan">Catatan</label>
                        <textarea class="form-control" id="catatan" name="catatan" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="file_hasil">File Hasil (PDF/Gambar) <span class="text-danger">*</span></label>
                        <input type="file" class="form-control-file" id="file_hasil" name="file_hasil" accept=".pdf,image/jpeg,image/png,image/jpg" required>
                        <small class="text-muted">File harus dalam format PDF, JPG, JPEG, atau PNG, maksimal 5MB</small>
                        <div id="eksternalHasilFilePreview" class="mt-2"></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="saveEksternalHasil">Simpan</button>
            </div>
        </div>
    </div>
</div>
