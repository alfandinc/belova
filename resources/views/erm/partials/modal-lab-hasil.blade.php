<!-- View Lab Hasil Modal -->
<div class="modal fade" id="viewLabHasilModal" tabindex="-1" role="dialog" aria-labelledby="viewLabHasilModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewLabHasilModalLabel">Detail Hasil Laboratorium</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Nama Pemeriksaan:</strong> <span id="viewNamaPemeriksaan"></span></p>
                        <p><strong>Tanggal Pemeriksaan:</strong> <span id="viewTanggalPemeriksaan"></span></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Asal Lab:</strong> <span id="viewAsalLab"></span></p>
                        <p><strong>Dokter Pemeriksa:</strong> <span id="viewDokter"></span></p>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12">
                        <p><strong>Catatan:</strong> <span id="viewCatatan"></span></p>
                    </div>
                </div>
                
                <hr>
                
                <!-- Result table for internal lab results -->
                <div id="resultTableContainer">
                    <h5>Hasil Pemeriksaan</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="resultTable">
                            <thead>
                                <tr>
                                    <th>Nama Test</th>
                                    <th>Flag</th>
                                    <th>Hasil</th>
                                    <th>Satuan</th>
                                    <th>Nilai Rujukan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Result rows will be populated dynamically -->
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- PDF viewer for external lab results -->
                <div id="pdfViewerContainer" style="display: none;">
                    <h5>Hasil Pemeriksaan</h5>
                    <div class="embed-responsive embed-responsive-16by9">
                        <iframe id="pdfViewer" class="embed-responsive-item" src="" allowfullscreen></iframe>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>