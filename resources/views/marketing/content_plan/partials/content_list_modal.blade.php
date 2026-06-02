<div class="modal fade" id="contentListModal" tabindex="-1" role="dialog" aria-labelledby="contentListModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <form id="contentListForm" data-action="store" data-id="">
        <div class="modal-header">
          <h5 class="modal-title" id="contentListModalLabel">Tambah Content List</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-md-12 mb-3">
              <label for="cl_judul" class="form-label">Judul</label>
              <input type="text" class="form-control" id="cl_judul" name="judul" required>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="cl_brand" class="form-label">Brand</label>
              <select class="form-control content-list-select2" id="cl_brand" name="brand[]" multiple>
                <option value="Premiere Belova">Premiere Belova</option>
                <option value="Belova Skin">Belova Skin</option>
                <option value="Belova Dental Care">Belova Dental Care</option>
                <option value="BCL">BCL</option>
                <option value="dr Fika">dr Fika</option>
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label for="cl_assigned_to" class="form-label">Assigned To</label>
              <select class="form-control content-list-select2" id="cl_assigned_to" name="assigned_to">
                <option value="">(Unassigned)</option>
                @if(isset($users) && $users->count())
                  @foreach($users as $u)
                    <option value="{{ $u->id }}">{{ $u->name }}</option>
                  @endforeach
                @endif
              </select>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="cl_platform" class="form-label">Platform</label>
              <select class="form-control content-list-select2" id="cl_platform" name="platform[]" multiple required>
                <option value="Instagram">Instagram</option>
                <option value="Facebook">Facebook</option>
                <option value="TikTok">TikTok</option>
                <option value="YouTube">YouTube</option>
                <option value="Website">Website</option>
                <option value="Other">Other</option>
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label for="cl_jenis_konten" class="form-label">Jenis Konten</label>
              <select class="form-control content-list-select2" id="cl_jenis_konten" name="jenis_konten[]" multiple required>
                <option value="Feed">Feed</option>
                <option value="Story">Story</option>
                <option value="Reels">Reels</option>
                <option value="Artikel">Artikel</option>
                <option value="Other">Other</option>
              </select>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="cl_konten_pilar" class="form-label">Konten Pilar</label>
              <select class="form-control content-list-select2" id="cl_konten_pilar" name="konten_pilar">
                <option value="">Pilih Konten Pilar</option>
                <option value="Edukasi">Edukasi</option>
                <option value="Awareness">Awareness</option>
                <option value="Engagement/Interaktif">Engagement/Interaktif</option>
                <option value="Promo/Testimoni">Promo/Testimoni</option>
                <option value="Lifestyle/Tips">Lifestyle/Tips</option>
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label for="cl_link_referensi" class="form-label">Link Referensi</label>
              <input type="text" class="form-control" id="cl_link_referensi" name="link_referensi" placeholder="https://...">
            </div>
          </div>
          <div class="row">
            <div class="col-md-12 mb-3">
              <label for="cl_gambar_referensi" class="form-label">Visual Reference</label>
              <input type="file" class="form-control-file" id="cl_gambar_referensi" name="gambar_referensi" accept="image/*">
              <small class="text-muted d-block mt-1">Opsional. Saat content list yang sudah di-approve dijadwalkan menjadi content plan, gambar ini akan otomatis masuk ke Brief sebagai visual reference.</small>
              <div id="cl_gambar_referensi_preview" class="mt-2"></div>
            </div>
          </div>
          <div class="row">
            <div class="col-md-12 mb-3">
              <label for="cl_catatan" class="form-label">Catatan</label>
              <textarea class="form-control" id="cl_catatan" name="catatan" rows="3"></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary" id="btnSaveContentList">Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>