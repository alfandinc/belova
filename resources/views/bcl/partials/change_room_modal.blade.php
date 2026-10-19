{{-- Pindah Kamar modal. Open with bclOpenChangeRoom(transId); script: bcl.partials.change_room_script --}}
<div class="modal fade" id="md_change_room" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h6 class="modal-title m-0">Pindah Kamar <span id="cr_trans_label" class="font-weight-normal"></span></h6>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true"><i class="la la-times"></i></span>
                </button>
            </div>
            <form action="{{ route('bcl.transaksi.change_room') }}" method="POST" id="form_change_room">
                @csrf
                <input type="hidden" name="trans_id" id="cr_trans_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="cr_new_room_id">Kamar Baru <small class="text-muted" id="cr_package_label"></small></label>
                        <select name="new_room_id" id="cr_new_room_id" class="form-control" required>
                            <option value="">Memuat opsi kamar...</option>
                        </select>
                        <small class="form-text text-muted">Kamar yang terisi/dibooking pada sisa periode tidak bisa dipilih.</small>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="cr_effective_date">Tanggal Pindah</label>
                            <input type="text" name="effective_date" id="cr_effective_date" class="form-control" required autocomplete="off">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="cr_payment_date">Tanggal Bayar/Refund</label>
                            <input type="text" name="payment_date" id="cr_payment_date" class="form-control" autocomplete="off">
                        </div>
                    </div>
                    <div id="cr_date_warning" class="alert alert-warning py-1 px-2" style="display:none;"></div>
                    <div class="form-group" id="cr_pay_row" style="display:none;">
                        <label for="cr_pay_now">Bayar Sekarang (Rp)</label>
                        <input type="text" name="pay_now" id="cr_pay_now" class="form-control text-right" value="0" autocomplete="off">
                        <small class="form-text text-muted">Sisa yang belum dibayar akan masuk daftar Belum Lunas.</small>
                    </div>
                    <div class="form-check mb-2" id="cr_refund_row" style="display:none;">
                        <input class="form-check-input" type="checkbox" value="1" id="cr_refund_to_deposit" name="refund_to_deposit">
                        <label class="form-check-label" for="cr_refund_to_deposit">Simpan refund sebagai deposit penyewa (tidak dibayar tunai)</label>
                    </div>
                    <table class="table table-sm mb-0">
                        <tr><td width="45%">Harga paket kamar lama</td><td class="text-right" id="cr_old_price">-</td></tr>
                        <tr><td>Harga paket kamar baru</td><td class="text-right" id="cr_new_price">-</td></tr>
                        <tr><td>Sisa durasi paket</td><td class="text-right" id="cr_remaining">-</td></tr>
                        <tr class="font-weight-bold border-top"><td id="cr_result_label">Tagihan / Refund</td><td class="text-right" id="cr_result">-</td></tr>
                        <tr><td>Tunggakan transaksi saat ini</td><td class="text-right text-danger" id="cr_outstanding">-</td></tr>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm" id="cr_submit">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
