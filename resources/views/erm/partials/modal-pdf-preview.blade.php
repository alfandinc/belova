{{-- In-page PDF preview, driven by public/js/erm-pdf-preview.js (.btn-pdf-preview links, ErmPdfPreview.open()) --}}
<div class="modal fade" id="modalPdfPreview" tabindex="-1" role="dialog" aria-labelledby="modalPdfPreviewLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header align-items-center">
                <h5 class="modal-title" id="modalPdfPreviewLabel">Preview PDF</h5>
                <div class="ml-auto d-flex align-items-center">
                    <button type="button" class="btn btn-sm btn-outline-secondary mr-2" id="pdfPreviewPrint" disabled><i class="fas fa-print mr-1"></i>Print</button>
                    <a class="btn btn-sm btn-outline-secondary mr-2 disabled" id="pdfPreviewDownload" href="#" download><i class="fas fa-download mr-1"></i>Download</a>
                    <button type="button" class="close ml-1" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
            </div>
            <div class="modal-body p-0 elab-pdf-body">
                <div class="elab-pdf-loading" id="pdfPreviewLoading">
                    <div class="spinner-border" role="status"></div>
                    <div class="mt-2">Menyiapkan PDF...</div>
                </div>
                <div class="elab-pdf-error" id="pdfPreviewError"></div>
                <iframe id="pdfPreviewFrame" title="Preview PDF"></iframe>
            </div>
        </div>
    </div>
</div>
