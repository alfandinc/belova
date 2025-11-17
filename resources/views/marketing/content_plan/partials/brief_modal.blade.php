<div class="modal fade" id="contentBriefModal" tabindex="-1" aria-labelledby="contentBriefModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
  <div class="modal-dialog modal-xl modal-dialog-centered" style="max-width:1400px;">
    <div class="modal-content">
      <form id="contentBriefForm" enctype="multipart/form-data">
        <div class="modal-header">
          <h5 class="modal-title" id="contentBriefModalLabel">Tambah Content Brief</h5>
          <button type="button" class="close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="cb_content_plan_id" name="content_plan_id" value="">
          <input type="hidden" id="cb_id" name="id" value="">

          <div class="row">
            <div class="col-md-8">
              <div class="mb-2">
                <div class="row" style="font-size:0.98rem;line-height:1.25">
                  <div class="col-md-6">
                    <div><strong style="font-weight:600">Judul:</strong> <span id="cb_judul_text" class="text-body">&nbsp;</span></div>
                    <div class="mt-1"><strong style="font-weight:600">Brand:</strong> <span id="cb_brand_text" class="text-body">&nbsp;</span></div>
                  </div>
                  <div class="col-md-6">
                    <div><strong style="font-weight:600">Jenis:</strong> <span id="cb_jenis_konten_text" class="text-body">&nbsp;</span></div>
                    <div class="mt-1"><strong style="font-weight:600">Platform:</strong> <span id="cb_platform_text" class="text-body">&nbsp;</span></div>
                  </div>
                </div>
              </div>
              <div class="mb-3">
                <label for="cb_headline" class="form-label">Headline</label>
                <input type="text" class="form-control" id="cb_headline" name="headline" placeholder="Masukkan headline">
              </div>

              <div class="mb-3">
                <label for="cb_sub_headline" class="form-label">Sub Headline</label>
                <input type="text" class="form-control" id="cb_sub_headline" name="sub_headline" placeholder="Masukkan sub headline">
              </div>

              <div class="mb-3">
                <label for="cb_isi_konten" class="form-label">Isi Konten</label>
                <textarea id="cb_isi_konten" name="isi_konten" class="form-control" rows="8"></textarea>
              </div>
            </div>

            <div class="col-md-4">
              <label class="form-label">Visual References (multiple images)</label>
              <div id="cb_drop_area" class="border rounded p-3 text-center mb-2" style="min-height:100px;background:#f8f9fa;cursor:pointer;">
                <div id="cb_drop_hint">Tarik dan lepaskan gambar di sini atau klik untuk memilih (JPG/PNG)</div>
                <input type="file" id="cb_visual_references" name="visual_references[]" accept="image/*" multiple style="display:none;">
              </div>

              <div id="cb_preview_wrap" style="max-height:80vh;overflow:auto;padding-right:6px;">
                <div id="cb_preview" class="d-flex flex-column" style="gap:12px;min-width:240px"></div>
              </div>

              <small class="text-muted d-block mt-2">Klik thumbnail untuk melihat gambar penuh. Gunakan tombol × untuk menghapus.</small>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">Simpan Brief</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Image viewer modal for full-size preview -->
<div class="modal fade" id="cb_image_viewer_modal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content bg-transparent border-0">
      <div class="modal-body p-0 text-center">
        <button type="button" class="close btn btn-light position-absolute m-3" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close">&times;</button>
        <img id="cb_image_viewer_img" src="" alt="Preview" style="max-width:100%;max-height:80vh;border-radius:6px;box-shadow:0 6px 24px rgba(0,0,0,0.3)">
      </div>
    </div>
  </div>
</div>
