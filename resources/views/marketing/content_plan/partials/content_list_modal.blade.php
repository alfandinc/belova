{{-- "Ide Konten" (content list) form: propose an idea; editing an approved/rejected idea sends it back for approval. --}}
<div class="modal fade wf-modal" id="contentListModal" tabindex="-1" role="dialog" aria-labelledby="contentListModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
  <div class="modal-dialog modal-lg modal-dialog-scrollable" id="clDialog" role="document">
    <div class="modal-content">
      <form id="contentListForm" data-action="store" data-id="" novalidate>
        <div class="modal-header">
          <h5 class="modal-title" id="contentListModalLabel">Ide Konten Baru</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
        </div>
        <div class="modal-body">
          <div class="row">
          <div class="col-12" id="clMain">
          <div id="clResubmitNote" class="alert alert-warning py-2" style="display:none"></div>
          <div class="form-group">
            <label for="cl_judul">Judul ide <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="cl_judul" name="judul" required maxlength="255" placeholder="Contoh: 3 mitos jerawat yang masih dipercaya">
          </div>
          <div class="form-row">
            <div class="form-group col-12">
              <label for="cl_platform">Platform <span class="text-danger">*</span></label>
              <select class="d-none js-chip-picker" id="cl_platform" name="platform[]" multiple required data-placeholder="Pilih platform">
                @foreach($platforms as $p)<option value="{{ $p }}">{{ $p }}</option>@endforeach
              </select>
            </div>
            <div class="form-group col-12">
              <label for="cl_jenis_konten">Jenis konten <span class="text-danger">*</span></label>
              <select class="d-none js-chip-picker" id="cl_jenis_konten" name="jenis_konten[]" multiple required data-placeholder="Feed, Reels, …">
                @foreach($jenisKonten as $j)<option value="{{ $j }}">{{ $j }}</option>@endforeach
              </select>
            </div>
          </div>
          {{-- only judul, platform and jenis are needed to propose an idea; the rest is optional --}}
          <button type="button" class="btn btn-link px-0 cl-more-toggle" id="clMoreToggle" aria-expanded="false" aria-controls="clMore">
            <i class="fas fa-plus-circle mr-1"></i> Detail tambahan <span class="wf-muted">(opsional: klinik, pilar, PIC, referensi, catatan)</span>
          </button>
          <div id="clMore" style="display:none">
          <div class="form-row">
            <div class="form-group col-12">
              <label for="cl_brand">Klinik / brand</label>
              <select class="d-none js-chip-picker" id="cl_brand" name="brand[]" multiple data-placeholder="Pilih klinik">
                @foreach($brands as $b)<option value="{{ $b['value'] }}">{{ $b['label'] }}</option>@endforeach
              </select>
            </div>
            <div class="form-group col-sm-6">
              <label for="cl_konten_pilar">Konten pilar</label>
              <select class="form-control cl-select2" id="cl_konten_pilar" name="konten_pilar" data-placeholder="Pilih pilar" data-allow-clear="1">
                <option value=""></option>
                @foreach($kontenPilar as $k)<option value="{{ $k }}">{{ $k }}</option>@endforeach
              </select>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group col-sm-6">
              <label for="cl_assigned_to">PIC</label>
              <select class="form-control cl-select2" id="cl_assigned_to" name="assigned_to" data-placeholder="Pilih PIC" data-allow-clear="1">
                <option value=""></option>
                @foreach($users as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach
              </select>
            </div>
            <div class="form-group col-sm-6">
              <label for="cl_link_referensi">Link referensi</label>
              <input type="text" class="form-control" id="cl_link_referensi" name="link_referensi" placeholder="Link Instagram / TikTok / YouTube…">
            </div>
          </div>
          <div id="cl_link_preview" class="wf-embed form-group" style="display:none"></div>
          <div class="form-group">
            <label>Gambar referensi</label>
            <input type="file" class="d-none" id="cl_gambar_referensi" name="gambar_referensi" accept="image/*">
            <input type="hidden" id="cl_remove_gambar" name="remove_gambar" value="0">
            <div id="cl_gambar_drop" class="cp-drop" tabindex="0" role="button">
              <i class="fas fa-image mb-1"></i>
              <div>Ketuk untuk pilih gambar, atau tempel screenshot (Ctrl+V)</div>
            </div>
            <div id="cl_gambar_preview" class="mt-2"></div>
            <small class="wf-muted d-block mt-1">Opsional. Saat ide dijadwalkan, gambar ini otomatis masuk ke Brief.</small>
          </div>
          <div class="form-group mb-0">
            <label for="cl_catatan">Catatan</label>
            <textarea class="form-control" id="cl_catatan" name="catatan" rows="3" placeholder="Angle, hook, detail lain…"></textarea>
          </div>
          </div>
          </div>

          {{-- RIWAYAT: right-hand panel when editing (below the form on phones) --}}
          <div class="col-lg-4" id="clHistoryWrap" style="display:none">
            <div class="wf-side">
              <div class="wf-side-title"><i class="fas fa-history"></i> Riwayat</div>
              <div id="clTimeline"></div>
            </div>
          </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-dismiss="modal">Tutup</button>
          <button type="submit" class="btn btn-primary" id="btnSaveContentList"><i class="fas fa-paper-plane mr-1"></i> Simpan & Ajukan</button>
        </div>
      </form>
    </div>
  </div>
</div>
