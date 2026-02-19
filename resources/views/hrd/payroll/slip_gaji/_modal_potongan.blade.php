<div class="modal fade" id="modalPotongan" tabindex="-1" role="dialog" aria-labelledby="modalPotonganLabel" aria-hidden="true">
  @php
    $isPaid = isset($slip) && strtolower((string)($slip->status_gaji ?? '')) === 'paid';
  @endphp
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalPotonganLabel">Edit Potongan</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        @if($isPaid)
          <div class="alert alert-info">Slip ini sudah berstatus <strong>Paid</strong>. Tidak bisa diedit.</div>
        @endif
        <form id="formPotongan">
          <input type="hidden" name="id" id="potongan_slip_id" value="">
          <div class="form-group">
            <label>Potongan Pinjaman</label>
            <input type="number" step="0.01" name="potongan_pinjaman" class="form-control" id="potongan_pinjaman" {{ $isPaid ? 'disabled' : '' }}>
          </div>
          <div class="form-group">
            <label>Potongan BPJS Kesehatan</label>
            <input type="number" step="0.01" name="potongan_bpjs_kesehatan" class="form-control" id="potongan_bpjs_kesehatan" {{ $isPaid ? 'disabled' : '' }}>
          </div>
          <div class="form-group">
            <label>Potongan Jamsostek</label>
            <input type="number" step="0.01" name="potongan_jamsostek" class="form-control" id="potongan_jamsostek" {{ $isPaid ? 'disabled' : '' }}>
          </div>
          <div class="form-group">
            <label>Potongan Penalty</label>
            <input type="number" step="0.01" name="potongan_penalty" class="form-control" id="potongan_penalty" {{ $isPaid ? 'disabled' : '' }}>
          </div>
          <div class="form-group">
            <label>Potongan Lain</label>
            <input type="number" step="0.01" name="potongan_lain" class="form-control" id="potongan_lain" {{ $isPaid ? 'disabled' : '' }}>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
        @if(!$isPaid)
          <button type="button" class="btn btn-primary" id="btnSimpanPotongan">Simpan Potongan</button>
        @endif
      </div>
    </div>
  </div>
</div>
