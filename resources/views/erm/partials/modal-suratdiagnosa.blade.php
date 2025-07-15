<!-- Modal Surat Diagnosis -->
<div class="modal fade" id="diagnosisModal" tabindex="-1" role="dialog" aria-labelledby="diagnosisModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="diagnosisModalLabel">Surat Keterangan Diagnosis</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="diagnosisForm">
                    <input type="hidden" id="visitation_id" name="visitation_id">
                    
                    <!-- Identitas Pasien -->
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Identitas Pasien</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Nama</label>
                                        <p id="pasien_nama" class="form-control-static"></p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>No. RM</label>
                                        <p id="pasien_rm" class="form-control-static"></p>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Tanggal Lahir</label>
                                        <p id="pasien_lahir" class="form-control-static"></p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Jenis Kelamin</label>
                                        <p id="pasien_gender" class="form-control-static"></p>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Alamat</label>
                                <p id="pasien_alamat" class="form-control-static"></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Diagnosis -->
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Diagnosis</h6>
                        </div>
                        <div class="card-body">
                            <div id="diagnosis_list">
                                <!-- Diagnoses will be populated here -->
                            </div>
                        </div>
                    </div>
                    
                    <!-- Keterangan -->
                    <div class="form-group">
                        <label for="keterangan">Keterangan</label>
                        <textarea class="form-control" id="keterangan" name="keterangan" rows="4"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-primary" id="saveDiagnosis">Simpan</button>
                <button type="button" class="btn btn-success" id="printDiagnosis">Cetak</button>
            </div>
        </div>
    </div>
</div>
