<div class="modal fade" id="paymentModal" tabindex="-1" role="dialog" aria-labelledby="paymentModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="paymentModalLabel">Pembayaran</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="modal_amount_paid">Dibayar</label>
                    <input type="text" class="form-control" id="modal_amount_paid" value="0" placeholder="Jumlah uang yang diberikan pasien">
                    <small class="text-muted">Masukkan jumlah uang yang diberikan oleh pasien</small>
                </div>

                <div class="form-group">
                    <label for="modal_payment_method">Metode Pembayaran</label>
                    <select class="form-control" id="modal_payment_method">
                        <option value="cash">Tunai</option>
                        <option value="piutang">Piutang</option>
                        <option value="edc_bca">EDC BCA</option>
                        <option value="edc_bni">EDC BNI</option>
                        <option value="edc_bri">EDC BRI</option>
                        <option value="edc_mandiri">EDC Mandiri</option>
                        <option value="qris">QRIS</option>
                        <option value="transfer">Transfer</option>
                        <option value="shopee">Shopee</option>
                        <option value="tiktokshop">Tiktokshop</option>
                        <option value="tokopedia">Tokopedia</option>
                        <option value="asuransi_inhealth">Asuransi InHealth</option>
                        <option value="asuransi_brilife">Asuransi Brilife</option>
                        <option value="asuransi_admedika">Asuransi Admedika</option>
                        <option value="asuransi_bcalife">Asuransi BCA Life</option>
                    </select>
                </div>

                <div class="d-flex justify-content-between">
                    <span>Kembali:</span>
                    <span id="modal_change_amount" class="font-weight-bold text-success">Rp 0</span>
                </div>
                <div class="d-flex justify-content-between d-none" id="modal_shortage_label">
                    <span>Kekurangan:</span>
                    <span id="modal_shortage_amount" class="font-weight-bold text-danger">Rp 0</span>
                </div>
                <div id="modal_piutang_info" class="small text-muted d-none" style="margin-top:6px;">
                    Kekurangan akan <strong>dimasukkan ke piutang</strong>.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-success" id="confirmPaymentBtn">Terima Pembayaran</button>
            </div>
        </div>
    </div>
</div>
