<div class="modal fade" id="modalInsentifOmset" tabindex="-1" role="dialog" aria-labelledby="modalInsentifOmsetLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form id="formInsentifOmset">
    @csrf
        <div class="modal-header">
          <h5 class="modal-title" id="modalInsentifOmsetLabel">Tambah/Edit Insentif Omset</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="id" name="id">
          <div class="form-group">
            <label for="nama_penghasil">Nama Penghasil</label>
            <input type="text" class="form-control" id="nama_penghasil" name="nama_penghasil" required>
          </div>
          <div class="form-group">
            <label for="omset_min">Omset Min</label>
            <input type="number" class="form-control" id="omset_min" name="omset_min" required>
          </div>
          <div class="form-group">
            <label for="omset_max">Omset Max</label>
            <input type="number" class="form-control" id="omset_max" name="omset_max" required>
          </div>
          <div class="form-group">
            <label for="insentif_normal">Insentif Normal</label>
             <input type="number" class="form-control" id="insentif_normal" name="insentif_normal" required step="any">
          </div>
          <div class="form-group">
            <label for="insentif_up">Insentif Up</label>
             <input type="number" class="form-control" id="insentif_up" name="insentif_up" required step="any">
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
