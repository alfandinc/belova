<div class="modal fade" id="modalBenefit" tabindex="-1" role="dialog" aria-labelledby="modalBenefitLabel" aria-hidden="true">
  @php
    $isPaid = isset($slip) && strtolower((string)($slip->status_gaji ?? '')) === 'paid';
  @endphp
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalBenefitLabel">Edit Benefit</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        @if($isPaid)
          <div class="alert alert-info">Slip ini sudah berstatus <strong>Paid</strong>. Tidak bisa diedit.</div>
        @endif
        <form id="formBenefit">
          <input type="hidden" name="id" id="benefit_slip_id" value="">
          <div class="form-group">
            <label>Benefit BPJS Kesehatan</label>
            <input type="number" step="0.01" name="benefit_bpjs_kesehatan" class="form-control" id="benefit_bpjs_kesehatan" {{ $isPaid ? 'disabled' : '' }}>
          </div>
          <div class="form-group">
            <label>Benefit JHT</label>
            <input type="number" step="0.01" name="benefit_jht" class="form-control" id="benefit_jht" {{ $isPaid ? 'disabled' : '' }}>
          </div>
          <div class="form-group">
            <label>Benefit JKK</label>
            <input type="number" step="0.01" name="benefit_jkk" class="form-control" id="benefit_jkk" {{ $isPaid ? 'disabled' : '' }}>
          </div>
          <div class="form-group">
            <label>Benefit JKM</label>
            <input type="number" step="0.01" name="benefit_jkm" class="form-control" id="benefit_jkm" {{ $isPaid ? 'disabled' : '' }}>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
        @if(!$isPaid)
          <button type="button" class="btn btn-primary" id="btnSimpanBenefit">Simpan Benefit</button>
        @endif
      </div>
    </div>
  </div>
</div>
