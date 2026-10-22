{{-- Content Plan modal: workflow bar (status steps + next action) on top, then tabs.
     Full screen on phones; the footer keeps Simpan in reach. --}}
<div class="modal fade wf-modal" id="contentPlanModal" tabindex="-1" role="dialog" aria-labelledby="contentPlanModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
  <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
    <div class="modal-content">
      <form id="contentPlanForm" novalidate>
        <input type="hidden" name="content_list_id" value="">
        <div class="modal-header align-items-center">
          <div class="d-flex align-items-center flex-wrap" style="gap:8px;min-width:0">
            {{-- one section open: back to the summary of the content --}}
            <button type="button" class="btn btn-sm btn-light" id="cpBackSummary" style="display:none" title="Kembali ke ringkasan"><i class="fas fa-arrow-left mr-1"></i>Ringkasan</button>
            <h5 class="modal-title mb-0" id="contentPlanModalLabel">Jadwal Konten Baru</h5>
            <span id="cpModalStatus"></span>
          </div>
          <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
        </div>

        <div class="modal-body">
          {{-- Workflow: where the content is now and the next step(s) --}}
          <div id="cpWorkflow" class="cp-workflow mb-3" style="display:none">
            <div class="cp-steps" id="cpSteps"></div>
            <div id="cpWorkflowNote" class="cp-workflow-note" style="display:none"></div>
            <div class="wf-actions mt-2" id="cpWorkflowActions"></div>
          </div>

          <div class="row">
          <div class="col-12" id="cpMain">
          <div class="cp-wizard" id="cpWizard" style="display:none">
            <div class="cp-wizard-step" data-step="1"><span class="dot">1</span><span>Dasar</span></div>
            <span class="cp-wizard-line"></span>
            <div class="cp-wizard-step" data-step="2"><span class="dot">2</span><span>Penugasan <small>PIC, sebelum produksi</small></span></div>
            <span class="cp-wizard-line"></span>
            <div class="cp-wizard-step" data-step="3"><span class="dot">3</span><span>Brief <small>sebelum produksi</small></span></div>
          </div>
          <ul class="nav nav-tabs wf-tabs mb-3" id="cpTabs" role="tablist">
            <li class="nav-item"><a class="nav-link active" id="tab-detail-tab" data-toggle="tab" href="#tab-detail" role="tab">Detail</a></li>
            <li class="nav-item"><a class="nav-link" id="tab-brief-tab" data-toggle="tab" href="#tab-brief" role="tab">Brief <i class="fas fa-check-circle text-success brief-check" style="display:none"></i></a></li>
            <li class="nav-item cp-saved-only"><a class="nav-link" id="tab-design-tab" data-toggle="tab" href="#tab-design" role="tab">Desain <span class="wf-count" id="cpDesignCount" style="display:none">0</span></a></li>
            <li class="nav-item cp-saved-only"><a class="nav-link" id="tab-report-tab" data-toggle="tab" href="#tab-report" role="tab">Report</a></li>
          </ul>

          <div class="tab-content">
            {{-- RINGKASAN (saved content): read-only overview, one box per section with its own "Ubah" --}}
            <div class="tab-pane fade" id="tab-summary" role="tabpanel">
              <div class="cp-summary" id="cpSummary"></div>
            </div>

            {{-- DETAIL --}}
            <div class="tab-pane fade show active" id="tab-detail" role="tabpanel">
              {{-- New content = step by step: only step 1 is required; edit = all groups at once --}}
              <div class="row">
                <div class="col-12 col-lg-6 cp-col-a">
                  <div class="cp-group" data-group="1">
                    <div class="cp-group-title">Dasar</div>
                    <div class="form-group">
                      <label for="judul">Judul <span class="text-danger">*</span></label>
                      <input type="text" class="form-control" id="judul" name="judul" required maxlength="255" placeholder="Judul konten">
                    </div>
                    <div class="form-row">
                      <div class="form-group col-7">
                        <label for="tanggal_publish_date">Tanggal tayang <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="tanggal_publish_date" required>
                      </div>
                      <div class="form-group col-5">
                        <label for="tanggal_publish_time">Jam</label>
                        <input type="time" class="form-control" id="tanggal_publish_time" value="09:00">
                      </div>
                    </div>
                    {{-- best posting time from the insights (filled by script) --}}
                    <div class="cp-besttime" id="cpBestTime" style="display:none"></div>
                    <div class="form-group">
                      <label for="brand">Klinik / brand</label>
                      <select class="d-none js-chip-picker" id="brand" name="brand[]" multiple data-placeholder="Pilih klinik">
                        @foreach($brands as $b)<option value="{{ $b['value'] }}">{{ $b['label'] }}</option>@endforeach
                      </select>
                    </div>
                    <div class="form-row">
                      <div class="form-group col-12">
                        <label for="platform">Platform <span class="text-danger">*</span></label>
                        <select class="d-none js-chip-picker" id="platform" name="platform[]" multiple required data-placeholder="Pilih platform">
                          @foreach($platforms as $p)<option value="{{ $p }}">{{ $p }}</option>@endforeach
                        </select>
                      </div>
                      <div class="form-group col-12">
                        <label for="jenis_konten">Jenis konten <span class="text-danger">*</span></label>
                        <select class="d-none js-chip-picker" id="jenis_konten" name="jenis_konten[]" multiple required data-placeholder="Feed, Reels, …">
                          @foreach($jenisKonten as $j)<option value="{{ $j }}">{{ $j }}</option>@endforeach
                        </select>
                      </div>
                    </div>
                    <div class="form-group mb-0">
                      <label for="konten_pilar">Konten pilar</label>
                      <select class="form-control cp-select2" id="konten_pilar" name="konten_pilar" data-placeholder="Pilih pilar" data-allow-clear="1">
                        <option value=""></option>
                        @foreach($kontenPilar as $k)<option value="{{ $k }}">{{ $k }}</option>@endforeach
                      </select>
                    </div>
                  </div>
                </div>

                {{-- right column: sections that follow the workflow stage (see setStageGroups) --}}
                <div class="col-12 col-lg-6 cp-col-b">
                  <div class="cp-group" data-group="2">
                    <div class="cp-group-title">Penugasan <span class="cp-optional">PIC wajib sebelum mulai produksi</span></div>
                    <div class="form-row">
                      <div class="form-group col-sm-6">
                        <label for="assigned_to">PIC <span class="text-muted font-weight-normal text-lowercase">(sebelum produksi)</span></label>
                        <select class="form-control cp-select2" id="assigned_to" name="assigned_to" data-placeholder="Pilih PIC">
                          <option value="">(Belum ada)</option>
                          @foreach($users as $u)
                            <option value="{{ $u->id }}">{{ $u->name }}</option>
                          @endforeach
                        </select>
                      </div>
                      <div class="form-group col-sm-6">
                        <label for="tanggal_produksi">Deadline produksi</label>
                        <input type="date" class="form-control" id="tanggal_produksi" name="tanggal_produksi">
                      </div>
                    </div>
                    {{-- who produces it: "Butuh designer" creates the designer's job automatically on save --}}
                    <div class="form-group">
                      <label>Siapa yang mengerjakan?</label>
                      <input type="hidden" name="needs_designer" id="needs_designer" value="0">
                      <div class="cp-chips" id="cpWhoChips">
                        <button type="button" class="cp-chip-opt is-on" data-who="0" aria-pressed="true"><i class="fas fa-user-edit"></i><span>Content creator saja</span></button>
                        <button type="button" class="cp-chip-opt" data-who="1" aria-pressed="false"><i class="fas fa-pen-nib"></i><span>Butuh designer</span></button>
                      </div>
                      <div class="cp-who-lock wf-muted small mt-2" id="cpWhoLock" style="display:none"></div>
                    </div>
                    <div id="cpDesignerFields" style="display:none">
                      <div class="form-group">
                        <label for="designer_id">Designer <span class="text-muted font-weight-normal text-lowercase">(kosongkan bila designer mengambil sendiri)</span></label>
                        <select class="form-control cp-select2" id="designer_id" name="designer_id" data-placeholder="Biarkan designer mengambil sendiri" data-allow-clear="1">
                          <option value=""></option>
                          @foreach($users as $u)
                            <option value="{{ $u->id }}">{{ $u->name }}</option>
                          @endforeach
                        </select>
                        <div class="cp-designer-load" id="cpDesignerLoad"></div>
                      </div>
                    </div>
                  </div>

                  {{-- production stage onwards --}}
                  <div class="cp-group" data-group="caption">
                    <div class="cp-group-title">Caption &amp; aset</div>
                    <div class="form-group">
                      <label for="caption">Caption</label>
                      <textarea class="form-control" id="caption" name="caption" rows="4" placeholder="Caption untuk posting"></textarea>
                    </div>
                    <div class="form-row">
                      <div class="form-group col-sm-6 mb-0">
                        <label for="mention">Mention</label>
                        <input type="text" class="form-control" id="mention" name="mention" placeholder="@akun1, @akun2">
                      </div>
                      <div class="form-group col-sm-6 mb-0">
                        <label for="link_asset">Link aset (Drive)</label>
                        <input type="text" class="form-control" id="link_asset" name="link_asset" placeholder="https://drive…">
                      </div>
                    </div>
                  </div>

                  {{-- Siap Publish onwards --}}
                  <div class="cp-group" data-group="links">
                    <div class="cp-group-title">Link publikasi</div>
                    <div class="wf-muted small mb-2">Satu link per platform, diisi setelah konten tayang.</div>
                    <div id="link_publikasi_wrapper" class="row"></div>
                  </div>

                  <div class="cp-stage-hint" id="cpStageHint" style="display:none"></div>
                </div>
              </div>
            </div>

            {{-- BRIEF --}}
            <div class="tab-pane fade" id="tab-brief" role="tabpanel">
              <input type="hidden" id="cb_content_plan_id" value="">
              <input type="hidden" id="cb_id" value="">
              <p class="wf-muted small mb-3">Arahan untuk pembuat konten: pesan utama, script, dan contoh visual.</p>
              <div class="form-row">
                <div class="form-group col-sm-6">
                  <label for="cb_headline">Headline</label>
                  <input type="text" class="form-control" id="cb_headline" placeholder="Pesan utama konten">
                </div>
                <div class="form-group col-sm-6">
                  <label for="cb_sub_headline">Sub headline</label>
                  <input type="text" class="form-control" id="cb_sub_headline" placeholder="Pendukung (opsional)">
                </div>
              </div>
              <div class="form-group">
                <label for="cb_isi_konten">Isi konten / script</label>
                <textarea id="cb_isi_konten" class="form-control" rows="6"></textarea>
              </div>
              <div class="form-group">
                <label for="link_referensi">Link referensi</label>
                <input type="text" class="form-control" id="link_referensi" name="link_referensi" placeholder="Link Instagram / TikTok / YouTube…">
                <div id="cp_link_preview" class="wf-embed mt-2" style="display:none"></div>
              </div>
              <div class="form-group mb-0">
                <label>Visual referensi</label>
                <div id="cb_preview" class="cp-ref-grid mb-2"></div>
                <div id="cb_drop_area" class="cp-drop cp-drop-compact" tabindex="0" role="button">
                  <i class="fas fa-images mr-1"></i> Tambah gambar: ketuk, tarik ke sini, atau tempel (Ctrl+V)
                  <input type="file" id="cb_visual_references" accept="image/*" multiple class="d-none">
                </div>
              </div>
            </div>

            {{-- DESAIN: status of the designer job (choose "Butuh designer" in Detail > Penugasan) --}}
            <div class="tab-pane fade" id="tab-design" role="tabpanel">
              <p class="wf-muted mb-2">Konten dikerjakan designer? Pilih <a href="#" class="js-go-penugasan">Butuh designer</a> di Penugasan; job otomatis masuk ke daftar kerja designer. Designer membaca isi tab Brief.</p>
              <div id="cpDesignList"></div>
            </div>

            {{-- REPORT --}}
            <div class="tab-pane fade" id="tab-report" role="tabpanel">
              <input type="hidden" id="cr_id" value="">
              <input type="hidden" id="cr_content_plan_id" value="">
              <div id="cpReportHint" class="alert alert-info py-2" style="display:none">Report diisi setelah konten <strong>Published</strong>. Isi angka dari insight platform (mis. H+1 dan H+7).</div>
              {{-- Instagram numbers per day (filled by "Update insight") --}}
              <div class="cp-insight-box mb-3" id="cpInsightBox" style="display:none">
                <div class="d-flex align-items-center flex-wrap mb-2" style="gap:8px">
                  <strong><i class="fab fa-instagram mr-1" style="color:#E4405F"></i>Insight Instagram per hari</strong>
                  <button type="button" class="btn btn-sm btn-outline-primary ml-auto" id="cpInsightOne"><i class="fas fa-sync-alt mr-1"></i> Update konten ini</button>
                </div>
                <div id="cpInsightChart"></div>
                <div class="table-responsive mt-2">
                  <table class="table table-sm mb-0 cp-insight-table">
                    <thead><tr><th>Tanggal</th><th>Views</th><th>Reach</th><th>Likes</th><th>Komentar</th><th>Saves</th><th>Shares</th></tr></thead>
                    <tbody id="cpInsightRows"></tbody>
                  </table>
                </div>
              </div>
              {{-- manual numbers: only for platforms that are not read automatically (TikTok, YouTube, …) --}}
              <div id="cpManualReport">
              <div class="cp-manual-head mb-2">
                <strong id="cpManualTitle">Report manual</strong>
                <div class="wf-muted small" id="cpManualHint">Isi angka dari insight platform (mis. H+1 dan H+7).</div>
              </div>
              <div class="row">
                <div class="col-lg-7">
                  <div class="form-row">
                    <div class="form-group col-6 col-md-4"><label for="cr_likes">Likes</label><input type="number" inputmode="numeric" class="form-control" id="cr_likes" min="0" value="0"></div>
                    <div class="form-group col-6 col-md-4"><label for="cr_comments">Comments</label><input type="number" inputmode="numeric" class="form-control" id="cr_comments" min="0" value="0"></div>
                    <div class="form-group col-6 col-md-4"><label for="cr_saves">Saves</label><input type="number" inputmode="numeric" class="form-control" id="cr_saves" min="0" value="0"></div>
                    <div class="form-group col-6 col-md-4"><label for="cr_shares">Shares</label><input type="number" inputmode="numeric" class="form-control" id="cr_shares" min="0" value="0"></div>
                    <div class="form-group col-6 col-md-4"><label for="cr_reach">Reach</label><input type="number" inputmode="numeric" class="form-control" id="cr_reach" min="0" value="0"></div>
                    <div class="form-group col-6 col-md-4"><label for="cr_impressions">Impressions</label><input type="number" inputmode="numeric" class="form-control" id="cr_impressions" min="0" value="0"></div>
                  </div>
                  <div class="d-flex flex-wrap" style="gap:8px">
                    <span class="wf-chip">ERR <strong class="ml-1" id="cr_err">0.00</strong>%</span>
                    <span class="wf-chip">ERI <strong class="ml-1" id="cr_eri">0.00</strong>%</span>
                    <span class="wf-muted small align-self-center">Tersimpan saat klik <strong>Simpan</strong>.</span>
                  </div>
                </div>
                <div class="col-lg-5 mt-3 mt-lg-0">
                  <div class="d-flex align-items-center justify-content-between mb-2">
                    <strong>Riwayat report</strong>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="cr_new_btn"><i class="fas fa-plus mr-1"></i> Report baru</button>
                  </div>
                  <div class="table-responsive">
                    <table class="table table-sm mb-0">
                      <thead><tr><th>Waktu</th><th>ERI</th><th>ERR</th><th>Growth</th></tr></thead>
                      <tbody id="cr_history_tbody"></tbody>
                    </table>
                  </div>
                  <small class="wf-muted">Ketuk baris untuk mengubah report itu.</small>
                </div>
              </div>
              </div>
            </div>

          </div>
          </div>

          {{-- RIWAYAT: right-hand panel (below the form on phones) --}}
          <div class="col-lg-4" id="cpSide" style="display:none">
            <div class="wf-side">
              <div class="wf-side-title"><i class="fas fa-history"></i> Riwayat</div>
              <div id="cpTimeline"></div>
            </div>
          </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline-danger mr-auto btn-delete-plan" style="display:none"><i class="fas fa-trash"></i><span class="d-none d-sm-inline ml-1">Hapus</span></button>
          <button type="button" class="btn btn-light" data-dismiss="modal" id="cpCloseBtn">Tutup</button>
          {{-- wizard (new content only) --}}
          <button type="button" class="btn btn-light" id="cpPrevStep" style="display:none"><i class="fas fa-arrow-left mr-1"></i> Kembali</button>
          <button type="submit" class="btn btn-primary btn-save-plan"><i class="fas fa-save mr-1"></i> <span class="js-save-label">Simpan</span></button>
          <button type="button" class="btn btn-primary" id="cpNextStep" style="display:none"><span class="js-next-label">Lanjut</span> <i class="fas fa-arrow-right ml-1"></i></button>
        </div>
      </form>
    </div>
  </div>
</div>
