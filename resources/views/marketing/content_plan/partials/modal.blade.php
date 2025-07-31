<div class="modal fade" id="contentPlanModal" tabindex="-1" role="dialog" aria-labelledby="contentPlanModalLabel" aria-hidden="true">
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
            <label for="brand" class="form-label">Brand</label>
            <select class="form-control select2" id="brand" name="brand[]" multiple required>
              <option value="Premiere Belova">Premiere Belova</option>
              <option value="Belova Skin">Belova Skin</option>
              <option value="BCL">BCL</option>
              <option value="dr Fika">dr Fika</option>
            </select>
          </div>
            
            <div class="col-md-6 mb-2">
              <label for="judul" class="form-label">Judul</label>
              <input type="text" class="form-control" id="judul" name="judul" required>
            </div>
            <div class="col-md-6 mb-2">
              <label for="tanggal_publish" class="form-label">Tanggal Publish</label>
              <input type="datetime-local" class="form-control" id="tanggal_publish" name="tanggal_publish" required>
            </div>
            <div class="col-md-12 mb-2">
              <label for="deskripsi" class="form-label">Deskripsi</label>
              <textarea class="form-control" id="deskripsi" name="deskripsi"></textarea>
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
                <option value="Scheduled">Scheduled</option>
                <option value="Published">Published</option>
                <option value="Cancelled">Cancelled</option>
              </select>
            </div>
            <div class="col-md-6 mb-2">
              <label for="target_audience" class="form-label">Target Audience</label>
              <input type="text" class="form-control" id="target_audience" name="target_audience">
            </div>
            <div class="col-md-6 mb-2">
              <label for="link_asset" class="form-label">Link Asset</label>
              <input type="text" class="form-control" id="link_asset" name="link_asset">
            </div>
            <div class="col-md-6 mb-2">
              <label for="link_publikasi" class="form-label">Link Publikasi</label>
              <input type="text" class="form-control" id="link_publikasi" name="link_publikasi">
            </div>
            <div class="col-md-12 mb-2">
              <label for="catatan" class="form-label">Catatan</label>
              <textarea class="form-control" id="catatan" name="catatan"></textarea>
            </div>
            <!-- Gambar Referensi (Upload) -->
            <div class="col-md-12 mb-2">
              <label for="gambar_referensi" class="form-label">Gambar Referensi</label>
              <div id="gambarReferensiPreviewWrapper" style="display:none; margin-bottom:8px;">
                <img id="gambarReferensiPreview" src="" alt="Preview Gambar Referensi" style="max-width: 100%; max-height: 200px; border:1px solid #ddd; border-radius:4px;">
              </div>
              <input type="file" class="form-control-file" id="gambar_referensi" name="gambar_referensi" accept="image/*">
            </div>

</script>
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
