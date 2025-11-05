<div class="modal fade" id="contentPlanReportModal" tabindex="-1" role="dialog" aria-labelledby="contentPlanReportModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
  <div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
      <form id="contentPlanReportForm">
        <div class="modal-header">
          <h5 class="modal-title" id="contentPlanReportModalLabel">Content Report</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-lg-7">
          <input type="hidden" id="cr_id" name="id" value="">
          <input type="hidden" id="cr_content_plan_id" name="content_plan_id" value="">

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
                <!-- rows populated by JS -->
              </tbody>
            </table>
          </div>
          <small class="text-muted d-block mt-2">Click a row to load that report into the form for viewing/editing.</small>
        </div>
      </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" id="cr_new_btn">Baru</button>
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>