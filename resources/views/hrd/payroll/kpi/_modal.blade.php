<div class="modal fade" id="modalKpi" tabindex="-1" role="dialog" aria-labelledby="modalKpiLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form id="formKpi">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title" id="modalKpiLabel">Tambah/Edit KPI</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="id" name="id">
          <div class="form-group">
            <label for="nama_poin">Nama Poin</label>
            <input type="text" class="form-control" id="nama_poin" name="nama_poin" required>
          </div>
          <div class="form-group">
            <label for="initial_poin">Initial Poin</label>
            <input type="number" class="form-control" id="initial_poin" name="initial_poin" required step="any">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>
