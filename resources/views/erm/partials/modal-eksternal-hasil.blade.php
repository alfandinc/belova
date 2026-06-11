<!-- Modal for Hasil Eksternal Details -->
<style>
    #hasilEksternalModal .modal-dialog {
        max-width: 95vw;
        width: 95vw;
    }

    #hasilEksternalModal #fileViewerContainer {
        min-height: 72vh;
    }

    #hasilEksternalModal #fileViewerContent iframe,
    #hasilEksternalModal #fileViewerContent img {
        width: 100%;
        min-height: 72vh;
        object-fit: contain;
    }
    
    @media (max-width: 768px) {
        #hasilEksternalModal .modal-dialog {
            max-width: calc(100vw - 1rem);
            width: calc(100vw - 1rem);
            margin: 0.5rem auto;
        }

        #hasilEksternalModal #fileViewerContainer,
        #hasilEksternalModal #fileViewerContent iframe,
        #hasilEksternalModal #fileViewerContent img {
            min-height: 60vh;
        }
    }
</style>
<div class="modal fade" id="hasilEksternalModal" tabindex="-1" role="dialog" aria-labelledby="hasilEksternalModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="hasilEksternalModalLabel">Detail Hasil Lab Eksternal</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <p><strong>Asal Lab:</strong> <span id="hasilEksternalAsalLab"></span></p>
                        <p><strong>Nama Pemeriksaan:</strong> <span id="hasilEksternalNamaPemeriksaan"></span></p>
                        <p><strong>Tanggal Pemeriksaan:</strong> <span id="hasilEksternalTanggalPemeriksaan"></span></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Dokter:</strong> <span id="hasilEksternalDokter"></span></p>
                        <p><strong>Catatan:</strong> <span id="hasilEksternalCatatan"></span></p>
                    </div>
                </div>
                
                <!-- File viewer for lab results (image or PDF) -->
                <div id="fileViewerContainer">
                    <div id="fileViewerContent"></div>
                </div>
            </div>
            <div class="modal-footer">
                <a id="downloadPdfLink" href="#" class="btn btn-primary" target="_blank"><i class="fas fa-download"></i> Download PDF</a>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
