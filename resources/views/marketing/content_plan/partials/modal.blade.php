<div class="modal fade" id="contentPlanModal" tabindex="-1" role="dialog" aria-labelledby="contentPlanModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <form id="contentPlanForm">
        <div class="modal-header">
          <h5 class="modal-title" id="contentPlanModalLabel">Tambah Content Plan</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          
          <div class="row" style="max-height:65vh; overflow-y:auto;">
            <div class="col-md-12 mb-2">
              <label for="judul" class="form-label">Judul</label>
              <input type="text" class="form-control" id="judul" name="judul" required>
            </div>

            <div class="col-md-6 mb-2">
              <label for="brand" class="form-label">Brand</label>
              <select class="form-control select2" id="brand" name="brand[]" multiple required>
                <option value="Premiere Belova">Premiere Belova</option>
                <option value="Belova Skin">Belova Skin</option>
                <option value="BCL">BCL</option>
                <option value="dr Fika">dr Fika</option>
              </select>
            </div>
            <div class="col-md-6 mb-2">
              <label for="tanggal_publish" class="form-label">Tanggal Publish</label>
              <input type="datetime-local" class="form-control" id="tanggal_publish" name="tanggal_publish" required>
            </div>
            
            <div class="col-md-12 mb-2">
              <label for="caption" class="form-label">Caption</label>
              <textarea class="form-control" id="caption" name="caption" placeholder="Caption untuk posting (opsional)"></textarea>
            </div>
            <div class="col-md-6 mb-2">
              <label for="platform" class="form-label">Platform</label>
              <select class="form-control select2" id="platform" name="platform[]" multiple required>
                <option value="Instagram">Instagram</option>
                <option value="Facebook">Facebook</option>
                <option value="TikTok">TikTok</option>
                <option value="YouTube">YouTube</option>
                <option value="Website">Website</option>
                <option value="Other">Other</option>
              </select>
            </div>
            <div class="col-md-6 mb-2">
              <label for="jenis_konten" class="form-label">Jenis Konten</label>
              <select class="form-control select2" id="jenis_konten" name="jenis_konten[]" multiple required>
                <option value="Feed">Feed</option>
                <option value="Story">Story</option>
                <option value="Reels">Reels</option>
                <option value="Artikel">Artikel</option>
                <option value="Other">Other</option>
              </select>
            </div>
            <div class="col-md-6 mb-2">
              <label for="status" class="form-label">Status</label>
              <select class="form-control" id="status" name="status" required>
                <option value="Draft">Draft</option>
                <option value="Scheduled" selected>Scheduled</option>
                <option value="Published">Published</option>
                <option value="Cancelled">Cancelled</option>
              </select>
            </div>
            <div class="col-md-6 mb-2">
              <label for="link_asset" class="form-label">Link Asset</label>
              <input type="text" class="form-control" id="link_asset" name="link_asset">
            </div>
            <div class="col-md-12 mb-2">
              <label class="form-label">Link Publikasi per Platform</label>
              <div id="link_publikasi_wrapper" class="row">
                <div class="col-12 text-muted small mb-1">Pilih platform di kolom Platform untuk menambahkan link publikasi masing-masing. Anda dapat mengisi lebih dari satu link jika memilih beberapa platform.</div>
                <!-- dynamic inputs will be injected here: inputs named link_publikasi[Instagram], link_publikasi[Facebook], etc. -->
              </div>
            </div>
            <div class="col-md-6 mb-2">
              <label for="mention" class="form-label">Mention</label>
              <input type="text" class="form-control" id="mention" name="mention" placeholder="@user1, @user2 (opsional)">
            </div>
            
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
