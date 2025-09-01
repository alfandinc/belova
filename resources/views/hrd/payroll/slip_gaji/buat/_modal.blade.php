<div class="modal fade" id="modalBuatSlipGaji" tabindex="-1" role="dialog" aria-labelledby="modalBuatSlipGajiLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form id="formBuatSlipGaji">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title" id="modalBuatSlipGajiLabel">Buat Slip Gaji</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label for="bulan">Bulan</label>
            <input type="month" class="form-control" id="bulan" name="bulan" required>
          </div>
          <div class="form-group">
            <label for="periode_penilaian_id">Periode Penilaian</label>
            <select class="form-control" id="periode_penilaian_id" name="periode_penilaian_id">
              <option value="">Pilih Periode Penilaian</option>
              <!-- Options will be loaded via AJAX -->
            </select>
          </div>
          <div id="omsetBulananInputs">
            <!-- Omset bulanan inputs will be loaded here via AJAX -->
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
