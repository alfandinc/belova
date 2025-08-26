<!-- Add this new modal after existing modals -->
<div class="modal fade" id="modalFotoHasil" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Foto Hasil Tindakan</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form id="fotoHasilForm" enctype="multipart/form-data">
          @csrf
          <input type="hidden" id="informConsentId" name="inform_consent_id">
          
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label for="beforeImage">Foto Before</label>
                <input type="file" class="form-control" id="beforeImage" name="before_image" accept="image/*">
                <div class="mt-2">
                  <img id="beforePreview" style="max-width: 100%; max-height: 200px; display: none;">
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label for="afterImage">Foto After</label>
                <input type="file" class="form-control" id="afterImage" name="after_image" accept="image/*">
                <div class="mt-2">
                  <img id="afterPreview" style="max-width: 100%; max-height: 200px; display: none;">
                </div>
              </div>
            </div>
          </div>
          <div class="form-group mt-3">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="allowPost" name="allow_post" value="1">
              <label class="form-check-label" for="allowPost">
                Izinkan Posting ke Sosial Media
              </label>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="saveFotoHasil">Upload</button>
      </div>
    </div>
  </div>
</div>