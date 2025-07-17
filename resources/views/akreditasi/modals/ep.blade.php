<!-- EP Modal -->
<div class="modal fade" id="epModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">EP</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form id="epForm">
        <div class="modal-body">
          @csrf
          <input type="hidden" name="id" id="epId">
          <div class="form-group">
            <label for="epName">Name</label>
            <input type="text" class="form-control" name="name" id="epName" required>
          </div>
          <div class="form-group">
            <label for="kelengkapanBukti">Kelengkapan Bukti</label>
            <input type="text" class="form-control" name="kelengkapan_bukti" id="kelengkapanBukti" required>
          </div>
          <div class="form-group">
            <label for="skorMaksimal">Skor Maksimal</label>
            <input type="number" class="form-control" name="skor_maksimal" id="skorMaksimal" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>
