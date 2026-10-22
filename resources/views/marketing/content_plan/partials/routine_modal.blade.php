{{-- Konten rutin: list of all routines --}}
<div class="modal fade wf-modal" id="routineListModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-redo-alt mr-2 text-muted"></i>Konten rutin</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
      </div>
      <div class="modal-body">
        <p class="wf-muted small mb-3">Konten yang dibuat sekali lalu diupload rutin (mis. <em>We Are Open</em> setiap hari jam 09.00). Muncul otomatis di jadwal; cukup tandai <strong>Sudah upload</strong> setiap hari.</p>
        <div id="routineList" class="d-flex flex-column" style="gap:8px"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-dismiss="modal">Tutup</button>
        @if($canManage)
          <button type="button" class="btn btn-primary js-new-routine"><i class="fas fa-plus mr-1"></i> Konten rutin</button>
        @endif
      </div>
    </div>
  </div>
</div>

{{-- Konten rutin: create / edit, with its upload history on the right --}}
<div class="modal fade wf-modal" id="routineModal" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
  <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
    <div class="modal-content">
      <form id="routineForm" novalidate>
        <div class="modal-header">
          <h5 class="modal-title" id="routineTitle">Konten rutin baru</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-12" id="rtMain">
              <div class="form-group">
                <label for="rt_judul">Judul <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="rt_judul" name="judul" maxlength="255" required placeholder="Contoh: We Are Open">
              </div>
              <div class="form-group">
                <label for="rt_brand">Klinik / brand</label>
                <select class="d-none js-chip-picker" id="rt_brand" name="brand[]" multiple data-placeholder="Pilih klinik">
                  @foreach($brands as $b)<option value="{{ $b['value'] }}">{{ $b['label'] }}</option>@endforeach
                </select>
              </div>
              <div class="form-group">
                <label for="rt_platform">Platform <span class="text-danger">*</span></label>
                <select class="d-none js-chip-picker" id="rt_platform" name="platform[]" multiple required data-placeholder="Pilih platform">
                  @foreach($platforms as $p)<option value="{{ $p }}">{{ $p }}</option>@endforeach
                </select>
              </div>
              <div class="form-group">
                <label for="rt_jenis_konten">Jenis konten</label>
                <select class="d-none js-chip-picker" id="rt_jenis_konten" name="jenis_konten[]" multiple data-placeholder="Story, Feed, …">
                  @foreach($jenisKonten as $j)<option value="{{ $j }}">{{ $j }}</option>@endforeach
                </select>
              </div>

              <div class="cp-group-title mt-4">Jadwal upload</div>
              <div class="form-group">
                <label>Hari <span class="text-danger">*</span></label>
                <div class="d-flex flex-wrap align-items-center" style="gap:8px">
                  <select class="d-none js-chip-picker" id="rt_days" name="days[]" multiple required data-placeholder="Pilih hari">
                    @foreach(\App\Models\Marketing\ContentRoutine::DAY_LABELS as $n => $label)<option value="{{ $n }}">{{ $label }}</option>@endforeach
                  </select>
                </div>
                <div class="mt-2 d-flex flex-wrap" style="gap:6px">
                  <button type="button" class="cp-chip js-rt-days" data-days="1,2,3,4,5,6,7">Setiap hari</button>
                  <button type="button" class="cp-chip js-rt-days" data-days="1,2,3,4,5,6">Senin–Sabtu</button>
                  <button type="button" class="cp-chip js-rt-days" data-days="1,2,3,4,5">Senin–Jumat</button>
                </div>
              </div>
              <div class="form-row">
                <div class="form-group col-sm-4">
                  <label for="rt_jam">Jam upload <span class="text-danger">*</span></label>
                  <input type="time" class="form-control" id="rt_jam" name="jam" value="09:00" required>
                </div>
                <div class="form-group col-sm-4">
                  <label for="rt_start">Mulai <span class="text-danger">*</span></label>
                  <input type="date" class="form-control" id="rt_start" name="start_date" required>
                </div>
                <div class="form-group col-sm-4">
                  <label for="rt_end">Sampai <span class="text-muted font-weight-normal text-lowercase">(kosong = terus)</span></label>
                  <input type="date" class="form-control" id="rt_end" name="end_date">
                </div>
              </div>
              <div class="form-group">
                <label for="rt_assigned">PIC upload</label>
                <select class="form-control" id="rt_assigned" name="assigned_to">
                  <option value="">Semua tim Marketing</option>
                  @foreach($users as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach
                </select>
                <small class="wf-muted">Dapat pengingat di jam upload; kosong = semua tim Marketing diingatkan.</small>
              </div>

              <div class="cp-group-title mt-4">Aset <span class="cp-optional">(opsional)</span></div>
              <div class="form-group">
                <label for="rt_asset">Link file final</label>
                <input type="text" class="form-control" id="rt_asset" name="asset_link" maxlength="1000" placeholder="Link Google Drive / folder desain">
              </div>
              <div class="form-group">
                <label for="rt_caption">Caption standar</label>
                <textarea class="form-control" id="rt_caption" name="caption" rows="3" placeholder="Caption yang dipakai setiap upload"></textarea>
              </div>
              <div class="form-group">
                <label for="rt_catatan">Catatan</label>
                <textarea class="form-control" id="rt_catatan" name="catatan" rows="2" placeholder="Mis. ganti stiker jam buka saat Ramadan"></textarea>
              </div>
              <div class="custom-control custom-switch" id="rtActiveWrap" style="display:none">
                <input type="checkbox" class="custom-control-input" id="rt_active">
                <label class="custom-control-label" for="rt_active" style="text-transform:none">Aktif (muncul di jadwal)</label>
              </div>
            </div>
            <div class="col-lg-5" id="rtSide" style="display:none">
              <div class="wf-side">
                <div class="wf-side-title"><i class="fas fa-calendar-check"></i> Upload 30 hari terakhir
                  <span class="ml-auto cp-tag" id="rtScore"></span>
                </div>
                <div id="rtHistory"></div>
                <details class="mt-3">
                  <summary class="small font-weight-bold">Riwayat perubahan</summary>
                  <div class="mt-2" id="rtTimeline"></div>
                </details>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light text-danger mr-auto" id="rtDelete" style="display:none"><i class="fas fa-trash mr-1"></i> Hapus</button>
          <button type="button" class="btn btn-light" data-dismiss="modal">Batal</button>
          @if($canManage)
            <button type="submit" class="btn btn-primary" id="rtSave"><i class="fas fa-save mr-1"></i> Simpan</button>
          @endif
        </div>
      </form>
    </div>
  </div>
</div>
