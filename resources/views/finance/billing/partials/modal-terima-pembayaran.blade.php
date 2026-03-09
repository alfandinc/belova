<!-- Modal: Terima Pembayaran (from Piutang page) -->
<div class="modal fade" id="modalTerimaPembayaran" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Terima Pembayaran</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <form id="form-terima-pembayaran">
                    <input type="hidden" name="piutang_id" id="piutang_id">
                    <div class="mb-2">
                        <label>Invoice</label>
                        <input type="text" id="piutang_invoice" class="form-control" readonly>
                    </div>
                    <!-- Kekurangan moved to inline label next to Jumlah -->
                    <div class="mb-2">
                        <label>Jumlah (Rp) <small id="piutang_kekurangan_label" class="ml-2 text-danger"></small></label>
                        <input type="number" step="0.01" name="amount" id="piutang_amount" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label>Tanggal Bayar</label>
                        <input type="datetime-local" name="payment_date" id="piutang_payment_date" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label>Metode Pembayaran</label>
                        <select name="payment_method" id="piutang_payment_method" class="form-control">
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
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" id="btn-submit-terima" class="btn btn-primary">Simpan Pembayaran</button>
            </div>
        </div>
    </div>
</div>
