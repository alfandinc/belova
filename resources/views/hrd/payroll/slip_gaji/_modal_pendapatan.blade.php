<div class="modal fade" id="modalPendapatan" tabindex="-1" role="dialog" aria-labelledby="modalPendapatanLabel" aria-hidden="true">
  @php
    $isPaid = isset($slip) && strtolower((string)($slip->status_gaji ?? '')) === 'paid';
  @endphp
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalPendapatanLabel">Edit Pendapatan Tambahan</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        @if($isPaid)
          <div class="alert alert-info">Slip ini sudah berstatus <strong>Paid</strong>. Tidak bisa diedit.</div>
        @endif
        <form id="formPendapatan">
          <input type="hidden" name="id" id="pendapatan_slip_id" value="">
          <div id="pendapatanRows">
            @php
              $items = is_array($slip->pendapatan_tambahan ?? null) ? $slip->pendapatan_tambahan : [];
            @endphp

            @if(count($items))
              @foreach($items as $it)
                <div class="form-row mb-2 pendapatan-row">
                  <div class="col-6">
                    <input type="text" class="form-control form-control-sm pendapatan-label" placeholder="Label" value="{{ $it['label'] ?? '' }}" {{ $isPaid ? 'disabled' : '' }}>
                  </div>
                  <div class="col-5">
                    <input type="number" step="0.01" class="form-control form-control-sm pendapatan-amount" placeholder="Amount" value="{{ $it['amount'] ?? '' }}" {{ $isPaid ? 'disabled' : '' }}>
                  </div>
                  <div class="col-1">
                    @if(!$isPaid)
                      <button type="button" class="btn btn-sm btn-danger btn-remove-pendapatan">&times;</button>
                    @endif
                  </div>
                </div>
              @endforeach
            @else
              <div class="form-row mb-2 pendapatan-row">
                <div class="col-6">
                  <input type="text" class="form-control form-control-sm pendapatan-label" placeholder="Label" value="" {{ $isPaid ? 'disabled' : '' }}>
                </div>
                <div class="col-5">
                  <input type="number" step="0.01" class="form-control form-control-sm pendapatan-amount" placeholder="Amount" value="" {{ $isPaid ? 'disabled' : '' }}>
                </div>
                <div class="col-1">
                  @if(!$isPaid)
                    <button type="button" class="btn btn-sm btn-danger btn-remove-pendapatan">&times;</button>
                  @endif
                </div>
              </div>
            @endif
          </div>
          @if(!$isPaid)
            <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="btnAddPendapatanRow">Tambah Pendapatan Tambahan</button>
          @endif
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
        @if(!$isPaid)
          <button type="button" class="btn btn-primary" id="btnSimpanPendapatan">Simpan Pendapatan</button>
        @endif
      </div>
    </div>
  </div>
</div>
