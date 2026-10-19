{{-- Refund modal. Open with bclOpenRefund(transId, label); script: bcl.partials.refund_script --}}
<div class="modal fade" id="md_refund" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h6 class="modal-title m-0">Refund / Keluar Lebih Awal <span id="rf_label" class="font-weight-normal"></span></h6>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true"><i class="la la-times"></i></span>
                </button>
            </div>
            <form action="{{ route('bcl.transaksi.refund') }}" method="POST" id="form_refund">
                @csrf
                <input type="hidden" name="kode_trans" id="rf_trans_id">
                <div class="modal-body">
                    <div class="alert alert-light border py-2 mb-3">
                        Periode: <strong id="rf_period">-</strong><br>
                        Sudah dibayar: <strong id="rf_paid">-</strong>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="rf_tgl_keluar">Tanggal Keluar</label>
                            <input type="date" id="rf_tgl_keluar" name="tgl_keluar" class="form-control" required>
                            <small class="text-muted">Sewa berakhir pada tanggal ini.</small>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="rf_tgl_refund">Tanggal Refund</label>
                            <input type="date" id="rf_tgl_refund" name="tgl_refund" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="rf_nominal">Nominal Refund (Rp)</label>
                            <input type="number" min="0" step="1" id="rf_nominal" name="nominal_refund" class="form-control" required value="0">
                            <small class="text-muted">Isi 0 jika hanya keluar tanpa refund.</small>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="rf_alasan">Alasan</label>
                            <input type="text" id="rf_alasan" name="alasan" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-danger btn-sm">Simpan Refund</button>
                </div>
            </form>
        </div>
    </div>
</div>
