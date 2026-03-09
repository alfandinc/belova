<!-- Modal: PDF Preview -->
<div class="modal fade" id="modalPdfPreview" tabindex="-1" role="dialog" aria-labelledby="modalPdfPreviewLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document" style="max-width:95%;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalPdfPreviewLabel">Preview PDF</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" style="padding:0; min-height:60vh;">
                <div id="pdf-preview-loading" style="text-align:center; padding:1.5rem; display:none;"><div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div></div>
                <div id="pdf-preview-container" style="width:100%; height:80vh;">
                    <!-- iframe inserted here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                <a id="pdf-preview-download" class="btn btn-primary" href="#" target="_blank">Buka di Tab Baru</a>
            </div>
        </div>
    </div>
</div>
