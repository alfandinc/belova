<div class="modal fade" id="modalImportAssessmentKpi" tabindex="-1" role="dialog" aria-labelledby="modalImportAssessmentKpiLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalImportAssessmentKpiLabel">Import KPI Poin dari KPI Assessment</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="form-row align-items-end mb-3">
          <div class="col-md-3">
            <label for="importAssessmentSlipBulan">Periode Slip Gaji</label>
            <input type="month" class="form-control" id="importAssessmentSlipBulan" value="{{ $bulan }}">
          </div>
          <div class="col-md-6">
            <label for="importAssessmentPeriodId">Periode KPI Assessment</label>
            <select class="form-control" id="importAssessmentPeriodId">
              <option value="">Pilih Periode Assessment</option>
              @foreach(($assessmentPeriods ?? collect()) as $period)
                <option value="{{ $period->id }}" data-assessment-month="{{ optional($period->assessment_month)->format('Y-m') }}">
                  {{ $period->name ?: 'KPI Assessment' }} - {{ optional($period->assessment_month)->translatedFormat('F Y') }} ({{ strtoupper($period->status) }})
                </option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3">
            <button type="button" class="btn btn-primary btn-block" id="btnPreviewAssessmentKpiImport">Preview</button>
          </div>
        </div>

        <div class="alert alert-light border mb-3">
          Preview ini mengambil nilai final dari periode KPI Assessment yang dipilih. Anda masih bisa mengubah KPI poin per karyawan sebelum menyimpannya ke slip gaji bulan yang dipilih.
        </div>

        <div id="importAssessmentKpiSummary" class="d-none mb-3"></div>

        <div id="importAssessmentKpiEmpty" class="text-muted">
          Pilih periode slip gaji dan periode KPI Assessment, lalu klik Preview.
        </div>

        <div id="importAssessmentKpiPreviewWrapper" class="table-responsive d-none">
          <table class="table table-bordered table-sm">
            <thead>
              <tr>
                <th>Nama</th>
                <th>Divisi</th>
                <th>Status Slip</th>
                <th>KPI Poin Saat Ini</th>
                <th>KPI dari Assessment</th>
                <th>KPI Poin Disimpan</th>
              </tr>
            </thead>
            <tbody id="importAssessmentKpiPreviewBody"></tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-success d-none" id="btnSaveAssessmentKpiImport">Simpan KPI Poin</button>
      </div>
    </div>
  </div>
</div>