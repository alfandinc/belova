<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form id="editForm">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="editResepModalLabel">Edit Item</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="edit_id">
                    <input type="hidden" id="edit_row_index">
                    <div class="form-group">
                        <label for="edit_qty">Qty</label>
                        <input type="number" class="form-control" id="edit_qty" name="edit_qty" min="1" required>
                    </div>
                    <div class="form-group">
                        <label for="jumlah">Harga (Rp)</label>
                        <input type="number" class="form-control" id="jumlah" name="jumlah" required>
                    </div>
                    <div class="form-group">
                        <label for="diskon">Diskon</label>
                        <input type="number" class="form-control" id="diskon" name="diskon">
                    </div>
                    <div class="form-group">
                        <label for="diskon_type">Tipe Diskon</label>
                        <select class="form-control select2" id="diskon_type" name="diskon_type">
                            <option value="">Tidak Ada</option>
                            <option value="%">Persentase (%)</option>
                            <option value="nominal">Nominal (Rp)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="button" id="saveChangesBtn" class="btn btn-primary">Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>