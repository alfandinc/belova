<!-- BAB Modal -->
<div class="modal fade" id="babModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">BAB</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form id="babForm">
        <div class="modal-body">
          @csrf
          <input type="hidden" name="id" id="babId">
          <div class="form-group">
            <label for="babName">Name</label>
            <input type="text" class="form-control" name="name" id="babName" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>
