<div class="modal fade" id="modalMerchandiseStockOut" tabindex="-1" role="dialog" aria-labelledby="modalMerchandiseStockOutTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <style>
                #modalMerchandiseStockOut .tab-content {
                    max-height: 60vh;
                    overflow-y: auto;
                    padding: 0 1rem 1rem 1rem;
                }

                #modalMerchandiseStockOut .tab-pane .modal-body {
                    padding-top: 0.75rem;
                    padding-bottom: 0.5rem;
                }
            </style>
            <div class="modal-header">
                <h5 class="modal-title" id="modalMerchandiseStockOutTitle">Keluar Merchandise</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body pb-0">
                <ul class="nav nav-tabs" id="merchandiseStockOutTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="tab-berikan-merchandise" data-toggle="tab" href="#pane-berikan-merchandise" role="tab" aria-controls="pane-berikan-merchandise" aria-selected="true">Berikan Merchandise</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="tab-riwayat-merchandise" data-toggle="tab" href="#pane-riwayat-merchandise" role="tab" aria-controls="pane-riwayat-merchandise" aria-selected="false">Riwayat Merchandise</a>
                    </li>
                </ul>
            </div>
            <div class="tab-content">
                <div class="tab-pane fade show active" id="pane-berikan-merchandise" role="tabpanel" aria-labelledby="tab-berikan-merchandise">
                    <form id="form-merchandise-stock-out">
                        <div class="modal-body">
                            <div class="form-group">
                                <label for="merchandise-stock-out-item">Pilih Merchandise</label>
                                <select id="merchandise-stock-out-item" name="merchandise_id" class="form-control" required>
                                    <option value="">Pilih merchandise</option>
                                </select>
                                <small class="form-text text-muted" id="merchandise-stock-out-summary">Pilih item untuk melihat stok tersedia.</small>
                            </div>
                            <div class="form-group">
                                <label for="merchandise-stock-out-qty">Qty Keluar</label>
                                <input type="number" min="1" class="form-control" id="merchandise-stock-out-qty" name="quantity" value="1" required>
                            </div>
                            <div class="form-group mb-2">
                                <label class="d-block">Tujuan Pengeluaran</label>
                                <div class="custom-control custom-radio custom-control-inline">
                                    <input type="radio" id="merchandise-target-pasien" name="target_type" class="custom-control-input" value="pasien" checked>
                                    <label class="custom-control-label" for="merchandise-target-pasien">Untuk Pasien</label>
                                </div>
                                <div class="custom-control custom-radio custom-control-inline">
                                    <input type="radio" id="merchandise-target-manual" name="target_type" class="custom-control-input" value="manual">
                                    <label class="custom-control-label" for="merchandise-target-manual">Keperluan Lain</label>
                                </div>
                            </div>
                            <div class="form-group" id="merchandise-pasien-field">
                                <label for="merchandise-stock-out-pasien">Cari Pasien</label>
                                <select id="merchandise-stock-out-pasien" name="pasien_id" class="form-control" style="width: 100%;"></select>
                            </div>
                            <div class="form-group d-none" id="merchandise-reason-field">
                                <label for="merchandise-stock-out-reason">Keperluan Pengeluaran</label>
                                <input type="text" class="form-control" id="merchandise-stock-out-reason" name="reason" maxlength="255" placeholder="Contoh: Sample event, internal kebutuhan klinik">
                            </div>
                            <div class="form-group mb-0">
                                <label for="merchandise-stock-out-notes">Catatan</label>
                                <textarea class="form-control" id="merchandise-stock-out-notes" name="notes" rows="3" placeholder="Catatan tambahan jika diperlukan"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary" id="btn-submit-merchandise-stock-out">Simpan</button>
                        </div>
                    </form>
                </div>
                <div class="tab-pane fade" id="pane-riwayat-merchandise" role="tabpanel" aria-labelledby="tab-riwayat-merchandise">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="merchandise-history-item">Pilih Merchandise</label>
                            <select id="merchandise-history-item" class="form-control">
                                <option value="">Pilih merchandise</option>
                            </select>
                            <small class="form-text text-muted">Pilih item untuk melihat riwayat kartu stok merchandise.</small>
                        </div>
                        <div id="merchandise-history-content" class="table-responsive">
                            <div class="text-muted">Pilih merchandise untuk melihat riwayat.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>