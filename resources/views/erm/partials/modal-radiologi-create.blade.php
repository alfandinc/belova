<!-- Upload Radiologi Modal -->
<div class="modal fade" id="uploadRadiologiModal" tabindex="-1" role="dialog" aria-labelledby="uploadRadiologiModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="uploadRadiologiModalLabel">Upload Hasil Radiologi</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="uploadRadiologiForm" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="visitation_id" value="{{ $visitation->id }}">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="uploadRadiologiNamaPemeriksaan">Nama Pemeriksaan <span class="text-danger">*</span></label>
                                <select class="form-control" id="uploadRadiologiNamaPemeriksaan" name="nama_pemeriksaan" required>
                                    <option value="">-- Pilih Pemeriksaan --</option>
                                    <option value="USG">USG</option>
                                    <option value="Rontgen">Rontgen</option>
                                    <option value="MRI">MRI</option>
                                    <option value="CT Scan">CT Scan</option>
                                    <option value="Mammografi">Mammografi</option>
                                    <option value="Fluoroskopi">Fluoroskopi</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="uploadRadiologiDokter">Dokter Pengirim <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="uploadRadiologiDokter" name="dokter_pengirim" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="uploadRadiologiTanggal">Tanggal Pemeriksaan <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="uploadRadiologiTanggal" name="tanggal_pemeriksaan" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="uploadRadiologiFile">File Hasil <span class="text-danger">*</span></label>
                                <input type="file" class="form-control-file" id="uploadRadiologiFile" name="hasil_file" accept=".pdf,.jpg,.jpeg,.png" required>
                                <small class="form-text text-muted">Format: PDF, JPG, JPEG, PNG (Max: 20MB)</small>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="uploadRadiologiDeskripsi">Deskripsi</label>
                        <textarea class="form-control" id="uploadRadiologiDeskripsi" name="deskripsi" rows="3"></textarea>
                    </div>
                    <div class="text-right">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Upload</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>