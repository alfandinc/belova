<div class="modal fade" id="contentPlanModal" tabindex="-1" role="dialog" aria-labelledby="contentPlanModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
  <div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
      <form id="contentPlanForm">
        <div class="modal-header">
          <h5 class="modal-title" id="contentPlanModalLabel">Tambah Content Plan</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <ul class="nav nav-tabs mb-3" id="contentPlanTab" role="tablist">
            <li class="nav-item" role="presentation">
              <a class="nav-link active" id="tab-edit-tab" data-toggle="tab" href="#tab-edit" role="tab" aria-controls="tab-edit" aria-selected="true">Plan</a>
            </li>
            <li class="nav-item" role="presentation">
              <a class="nav-link" id="tab-brief-tab" data-toggle="tab" href="#tab-brief" role="tab" aria-controls="tab-brief" aria-selected="false">Brief <span class="brief-check text-success ms-1" style="display:none;"><i class="fas fa-check-circle"></i></span></a>
            </li>
            <li class="nav-item" role="presentation">
              <a class="nav-link" id="tab-report-tab" data-toggle="tab" href="#tab-report" role="tab" aria-controls="tab-report" aria-selected="false">Report</a>
            </li>
          </ul>

          <div class="tab-content" id="contentPlanTabContent" style="max-height:65vh; overflow-y:auto;">
            <div class="tab-pane fade show active" id="tab-edit" role="tabpanel" aria-labelledby="tab-edit-tab">
              <div class="row">
                <div class="col-md-12 mb-2">
                  <div class="row">
                    <div class="col-md-6"> <!-- LEFT column -->
                      <div class="mb-3"><h6 class="mb-2 font-weight-bold">Content Detail</h6></div>
                      <div class="content-group border rounded p-3 bg-white">
                      <div class="mb-3">
                        <label for="judul" class="form-label">Judul</label>
                        <input type="text" class="form-control" id="judul" name="judul" required>
                      
                      <input type="hidden" id="status" name="status" value="Scheduled">

                      </div>
                      <div class="row">
                        <div class="col-md-6 mb-2">
                          <label for="brand" class="form-label">Brand</label>
                          <select class="form-control select2" id="brand" name="brand[]" multiple required>
                            <option value="Premiere Belova">Premiere Belova</option>
                            <option value="Belova Skin">Belova Skin</option>
                            <option value="Belova Dental Care">Belova Dental Care</option>
                            <option value="BCL">BCL</option>
                            <option value="dr Fika">dr Fika</option>
                          </select>
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
                      </div>

                      <div class="row">
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
                          <label for="konten_pilar" class="form-label">Konten Pilar</label>
                          <select class="form-control select2" id="konten_pilar" name="konten_pilar">
                            <option value="">Pilih Konten Pilar</option>
                            <option value="Edukasi">Edukasi</option>
                            <option value="Awareness">Awareness</option>
                            <option value="Engagement/Interaktif">Engagement/Interaktif</option>
                            <option value="Promo/Testimoni">Promo/Testimoni</option>
                            <option value="Lifestyle/Tips">Lifestyle/Tips</option>
                          </select>
                        </div>
                      </div>

                      <div class="mb-2">
                        <label for="link_asset" class="form-label">Link Asset</label>
                        <input type="text" class="form-control" id="link_asset" name="link_asset">
                      </div>

                      <div class="mb-2">
                        <label for="assigned_to" class="form-label">Assigned To</label>
                        <select class="form-control select2" id="assigned_to" name="assigned_to">
                          <option value="">(Unassigned)</option>
                          @if(isset($users) && $users->count())
                            @foreach($users as $u)
                              <option value="{{ $u->id }}">{{ $u->name }}</option>
                            @endforeach
                          @endif
                        </select>
                      </div>
                      </div>
                    </div>

                    <div class="col-md-6"> <!-- RIGHT column -->
                      <div class="mb-3"><h6 class="mb-2 font-weight-bold">Publication Detail</h6></div>
                      <div class="content-group border rounded p-3 bg-white">
                      <div class="mb-2">
                        <label class="form-label">Tanggal Publish / Deadline</label>
                        <div class="row gx-2">
                          <div class="col-7">
                            <input type="date" class="form-control" id="tanggal_publish_date" placeholder="YYYY-MM-DD">
                          </div>
                          <div class="col-5">
                            <input type="time" class="form-control" id="tanggal_publish_time" placeholder="HH:MM">
                          </div>
                        </div>
                      </div>

                      <div class="mb-2">
                        <label for="caption" class="form-label">Caption</label>
                        <textarea class="form-control" id="caption" name="caption" rows="5" style="min-height:120px" placeholder="Caption untuk posting (opsional)"></textarea>
                      </div>

                      {{--
                      <div class="mb-2">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-control" id="status" name="status" required>
                          <option value="Draft">Draft</option>
                          <option value="Scheduled" selected>Scheduled</option>
                          <option value="Published">Published</option>
                          <option value="Cancelled">Cancelled</option>
                        </select>
                      </div>
                      --}}

                      <div class="mb-2">
                        <label for="mention" class="form-label">Mention</label>
                        <input type="text" class="form-control" id="mention" name="mention" placeholder="@user1, @user2 (opsional)">
                      </div>

                      <div class="mb-2">
                        <label class="form-label">Link Publikasi per Platform</label>
                        <div id="link_publikasi_wrapper" class="row">
                          <div class="col-12 text-muted small mb-1">Pilih platform di kolom Platform untuk menambahkan link publikasi masing-masing. Anda dapat mengisi lebih dari satu link jika memilih beberapa platform.</div>
                          <!-- dynamic inputs will be injected here: inputs named link_publikasi[Instagram], link_publikasi[Facebook], etc. -->
                        </div>
                      </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="tab-pane fade" id="tab-brief" role="tabpanel" aria-labelledby="tab-brief-tab">
              <div id="contentBriefForm" data-enctype="multipart/form-data">
                <input type="hidden" id="cb_content_plan_id" name="content_plan_id" value="">
                <input type="hidden" id="cb_id" name="id" value="">

                <div class="row">
                  <div class="col-md-8">
                    <div class="row">
                      <div class="col-md-6 mb-3">
                        <label for="cb_headline" class="form-label">Headline</label>
                        <input type="text" class="form-control" id="cb_headline" name="headline" placeholder="Masukkan headline">
                      </div>
                      <div class="col-md-6 mb-3">
                        <label for="cb_sub_headline" class="form-label">Sub Headline</label>
                        <input type="text" class="form-control" id="cb_sub_headline" name="sub_headline" placeholder="Masukkan sub headline">
                      </div>
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
            </div>
            <div class="tab-pane fade" id="tab-report" role="tabpanel" aria-labelledby="tab-report-tab">
              <div id="contentPlanReportForm">
                <input type="hidden" id="cr_id" name="id" value="">
                <input type="hidden" id="cr_content_plan_id" name="content_plan_id" value="">

                <div class="row">
                  <div class="col-lg-7">
                    <div class="form-group">
                      <label><i class="fas fa-clipboard-list mr-2 text-muted" style="width:18px;text-align:center"></i> Content Plan</label>
                      <div id="cr_content_plan_title" class="form-control-plaintext" style="padding:.375rem .75rem;border:1px solid #e9ecef;border-radius:.25rem;background:#f8f9fa"></div>
                    </div>

                    <div class="form-row">
                      <div class="form-group col-md-6">
                        <label for="cr_likes"><i class="fas fa-heart mr-2 text-danger" style="width:18px;text-align:center"></i> Likes</label>
                        <input type="number" class="form-control" id="cr_likes" name="likes" min="0" value="0">
                      </div>
                      <div class="form-group col-md-6">
                        <label for="cr_comments"><i class="fas fa-comments mr-2 text-primary" style="width:18px;text-align:center"></i> Comments</label>
                        <input type="number" class="form-control" id="cr_comments" name="comments" min="0" value="0">
                      </div>
                    </div>

                    <div class="form-row">
                      <div class="form-group col-md-6">
                        <label for="cr_saves"><i class="fas fa-bookmark mr-2 text-muted" style="width:18px;text-align:center"></i> Saves</label>
                        <input type="number" class="form-control" id="cr_saves" name="saves" min="0" value="0">
                      </div>
                      <div class="form-group col-md-6">
                        <label for="cr_shares"><i class="fas fa-share-alt mr-2 text-warning" style="width:18px;text-align:center"></i> Shares</label>
                        <input type="number" class="form-control" id="cr_shares" name="shares" min="0" value="0">
                      </div>
                    </div>

                    <div class="form-row">
                      <div class="form-group col-md-6">
                        <label for="cr_reach"><i class="fas fa-users mr-2 text-info" style="width:18px;text-align:center"></i> Reach</label>
                        <input type="number" class="form-control" id="cr_reach" name="reach" min="0" value="0">
                      </div>
                      <div class="form-group col-md-6">
                        <label for="cr_impressions"><i class="fas fa-bullseye mr-2 text-secondary" style="width:18px;text-align:center"></i> Impressions</label>
                        <input type="number" class="form-control" id="cr_impressions" name="impressions" min="0" value="0">
                      </div>
                    </div>

                    <div class="form-row">
                      <div class="form-group col-md-6">
                        <label for="cr_err"><i class="fas fa-percentage mr-2 text-muted" style="width:18px;text-align:center"></i> ERR (%)</label>
                        <input type="text" readonly class="form-control-plaintext" id="cr_err" name="err" value="">
                      </div>
                      <div class="form-group col-md-6">
                        <label for="cr_eri"><i class="fas fa-percentage mr-2 text-muted" style="width:18px;text-align:center"></i> ERI (%)</label>
                        <input type="text" readonly class="form-control-plaintext" id="cr_eri" name="eri" value="">
                      </div>
                    </div>

                    <div class="form-group">
                      <label for="cr_recorded_at"><i class="fas fa-calendar-alt mr-2 text-muted" style="width:18px;text-align:center"></i> Recorded At</label>
                      <input type="datetime-local" class="form-control" id="cr_recorded_at" name="recorded_at">
                    </div>
                  </div>
                  <div class="col-lg-5">
                    <h6>History</h6>
                    <div style="max-height:420px;overflow:auto;border:1px solid #e9ecef;padding:8px;border-radius:.25rem;background:#fff;">
                      <table class="table table-sm table-striped mb-0">
                        <thead>
                          <tr>
                            <th>When</th>
                            <th>ERI</th>
                            <th>ERR</th>
                            <th>Growth</th>
                          </tr>
                        </thead>
                        <tbody id="cr_history_tbody">
                        </tbody>
                      </table>
                    </div>
                    <small class="text-muted d-block mt-2">Click a row to load that report into the form for viewing/editing.</small>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary btn-save-plan">Simpan</button>
          <button type="button" class="btn btn-primary btn-save-brief" style="display:none;">Simpan Brief</button>
        </div>
      </form>
    </div>
  </div>
</div>
