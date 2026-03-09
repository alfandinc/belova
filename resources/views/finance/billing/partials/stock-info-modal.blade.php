<!-- Stok Tersedia Modal (lazy-loaded via AJAX when needed) -->
<div class="modal fade" id="stockInfoModal" tabindex="-1" role="dialog" aria-labelledby="stockInfoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="stockInfoModalLabel">Stok Tersedia</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="mb-2">
                    <div class="text-muted small">Item:</div>
                    <div id="stockInfoItemName" class="font-weight-bold">-</div>
                    <div class="text-muted small mt-2">Gudang:</div>
                    <div id="stockInfoGudangName" class="font-weight-bold">-</div>
                </div>
                <div id="stockInfoContent" class="mt-3">
                    <div class="text-muted">Memuat stok...</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
