<!-- Edit Non‑Racikan Modal -->
<div class="modal fade" id="editResepModal" tabindex="-1" role="dialog" aria-labelledby="editResepModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <form id="edit-resep-form">
      @csrf
      @method('PUT')
      <input type="hidden" name="resep_id" id="edit-resep-id">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="editResepModalLabel">Edit Resep</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Batal">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label for="edit-jumlah">Jumlah</label>
            <input type="number" class="form-control" id="edit-jumlah" name="jumlah" required>
          </div>
          <div class="form-group">
            <label for="edit-diskon">Diskon</label>
            <input type="number" class="form-control" id="edit-diskon" name="diskon" required>
          </div>
          <div class="form-group">
            <label for="edit-aturan">Aturan Pakai</label>
            <input type="text" class="form-control" id="edit-aturan" name="aturan_pakai" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
        </div>
      </div>
    </form>
  </div>
</div>