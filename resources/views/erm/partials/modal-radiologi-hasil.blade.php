<!-- View Radiologi Modal -->
<div class="modal fade" id="viewRadiologiModal" tabindex="-1" role="dialog" aria-labelledby="viewRadiologiModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewRadiologiModalLabel">Detail Hasil Radiologi</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Pemeriksaan:</strong> <span id="viewRadiologiNamaPemeriksaan"></span></p>
                            <p><strong>Dokter Pengirim:</strong> <span id="viewRadiologiDokter"></span></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Tanggal:</strong> <span id="viewRadiologiTanggal"></span></p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <p><strong>Deskripsi:</strong></p>
                            <div class="card">
                                <div class="card-body" id="viewRadiologiDeskripsi">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12">
                            <p><strong>File:</strong></p>
                            <div id="viewRadiologiFilePreview" class="text-center p-3 border">
                                <!-- File preview will be inserted here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>