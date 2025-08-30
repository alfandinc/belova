<!-- Modal for all payroll master tables -->
<div class="modal fade" id="payrollMasterModal" tabindex="-1" role="dialog" aria-labelledby="payrollMasterModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="payrollMasterModalLabel">Tambah/Edit Data</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form id="payrollMasterForm">
        <div class="modal-body">
          <input type="hidden" name="id" id="payrollMasterId">
          <input type="hidden" name="type" id="payrollMasterType">
          <div id="payrollMasterFields"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>
