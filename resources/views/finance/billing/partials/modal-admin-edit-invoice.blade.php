{{-- Admin edit of a processed invoice: item price / discount + invoice discount / tax (qty and stock untouched) --}}
<style>
    #modalAdminEditInvoice .table td, #modalAdminEditInvoice .table th { vertical-align: middle; padding: .35rem .5rem; }
    #modalAdminEditInvoice .aei-input { max-width: 140px; }
    #modalAdminEditInvoice .aei-disc { display: flex; max-width: 190px; }
    #modalAdminEditInvoice .aei-disc input { min-width: 0; }
    #modalAdminEditInvoice .aei-disc select { flex: 0 0 64px; }
    #modalAdminEditInvoice .aei-summary td { padding: .15rem .5rem; border: 0; }
    #modalAdminEditInvoice tr.aei-changed > td { background: rgba(255, 193, 7, .12); }
    #modalAdminEditInvoice .aei-log { border-left: 3px solid rgba(128,128,128,.35); padding-left: .6rem; margin-bottom: .6rem; }
</style>
<div class="modal fade" id="modalAdminEditInvoice" tabindex="-1" role="dialog" aria-labelledby="modalAdminEditInvoiceLabel" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalAdminEditInvoiceLabel">Edit Invoice</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="aei-loading text-center py-4"><div class="spinner-border text-primary" role="status"><span class="sr-only">Memuat...</span></div></div>
                <div class="aei-content" style="display:none;">
                    <div class="alert alert-danger aei-error" style="display:none;"></div>
                    <div class="alert alert-info py-2 small mb-2">
                        Hanya harga dan diskon yang dapat diubah. Qty dan stok tidak berubah; jumlah yang sudah dibayar tetap, kekurangan / kelebihan dihitung ulang.
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-2">
                            <thead class="thead-light">
                                <tr>
                                    <th>Item</th>
                                    <th class="text-right" style="width:70px;">Qty</th>
                                    <th style="width:150px;">Harga Satuan</th>
                                    <th style="width:200px;">Diskon (per baris)</th>
                                    <th class="text-right" style="width:140px;">Total</th>
                                </tr>
                            </thead>
                            <tbody class="aei-items"></tbody>
                        </table>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-row">
                                <div class="form-group col-7">
                                    <label class="small mb-1">Diskon Invoice</label>
                                    <div class="aei-disc">
                                        <input type="number" min="0" step="any" class="form-control form-control-sm" id="aei-discount-value">
                                        <select class="form-control form-control-sm" id="aei-discount-type">
                                            <option value="%">%</option>
                                            <option value="nominal">Rp</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group col-5">
                                    <label class="small mb-1">Pajak (%)</label>
                                    <input type="number" min="0" max="100" step="any" class="form-control form-control-sm" id="aei-tax">
                                </div>
                            </div>
                            <div class="form-group mb-2">
                                <label class="small mb-1">Alasan perubahan <span class="text-danger">*</span></label>
                                <textarea class="form-control form-control-sm" id="aei-reason" rows="2" maxlength="1000" placeholder="Wajib diisi, minimal 5 karakter"></textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm aei-summary mb-0">
                                <tr><td>Subtotal</td><td class="text-right" data-sum="subtotal"></td></tr>
                                <tr><td>Diskon invoice</td><td class="text-right" data-sum="discount"></td></tr>
                                <tr><td>Pajak</td><td class="text-right" data-sum="tax"></td></tr>
                                <tr><td>Biaya admin / ongkir</td><td class="text-right" data-sum="fees"></td></tr>
                                <tr><td class="text-muted">Total lama</td><td class="text-right text-muted" data-sum="old_total"></td></tr>
                                <tr class="font-weight-bold"><td>Total baru</td><td class="text-right" data-sum="total"></td></tr>
                                <tr><td>Sudah dibayar</td><td class="text-right" data-sum="paid"></td></tr>
                                <tr class="font-weight-bold"><td data-sum="diff_label">Kekurangan</td><td class="text-right" data-sum="diff"></td></tr>
                            </table>
                        </div>
                    </div>

                    <div class="aei-logs-wrap mt-3" style="display:none;">
                        <h6 class="font-weight-bold mb-2">Riwayat Edit</h6>
                        <div class="aei-logs small"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-warning" id="aei-save" disabled><i class="fas fa-save mr-1"></i> Simpan Perubahan</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(function() {
    var showUrl = '{{ url('/finance/invoice') }}/';
    var csrf = '{{ csrf_token() }}';
    var $modal = $('#modalAdminEditInvoice');
    var current = null; // invoice payload from the server

    function rupiah(n) {
        return 'Rp ' + Math.round(Number(n) || 0).toLocaleString('id-ID');
    }
    function esc(s) {
        return $('<div>').text(s == null ? '' : String(s)).html();
    }
    function num($el) {
        var v = parseFloat($el.val());
        return isFinite(v) && v > 0 ? v : 0;
    }

    // Same math as InvoiceEditController::update (and the billing page)
    function recalc() {
        if (!current) return;
        var subtotal = 0, fees = 0;
        $modal.find('.aei-items tr').each(function() {
            var $tr = $(this);
            var qty = Number($tr.data('qty')) || 0;
            var price = num($tr.find('.aei-price'));
            var gross = price * qty;
            var disc = num($tr.find('.aei-item-disc'));
            if ($tr.find('.aei-item-disc-type').val() === '%') disc = gross * Math.min(100, disc) / 100;
            var line = Math.max(0, gross - Math.min(gross, disc));
            $tr.find('.aei-line').text(rupiah(line));
            $tr.toggleClass('aei-changed', Math.abs(line - Number($tr.data('orig-line'))) > 0.005);
            if ($tr.data('fee')) fees += line; else subtotal += line;
        });
        var dv = num($('#aei-discount-value'));
        var discount = $('#aei-discount-type').val() === '%' ? subtotal * Math.min(100, dv) / 100 : Math.min(subtotal, dv);
        var tax = (subtotal - discount) * num($('#aei-tax')) / 100;
        var total = Math.ceil(subtotal - discount + tax + fees);
        var paid = Number(current.amount_paid) || 0;
        var $s = function(k) { return $modal.find('[data-sum="' + k + '"]'); };
        $s('subtotal').text(rupiah(subtotal));
        $s('discount').text('- ' + rupiah(discount));
        $s('tax').text('+ ' + rupiah(tax));
        $s('fees').text(rupiah(fees));
        $s('old_total').text(rupiah(current.total_amount));
        $s('total').text(rupiah(total));
        $s('paid').text(rupiah(paid));
        if (paid > total) {
            $s('diff_label').text('Kelebihan bayar (kembalikan manual)');
            $s('diff').text(rupiah(paid - total)).attr('class', 'text-right text-warning');
        } else {
            $s('diff_label').text('Kekurangan (piutang)');
            $s('diff').text(rupiah(total - paid)).attr('class', 'text-right ' + (total > paid ? 'text-danger' : 'text-success'));
        }
    }

    function render(inv) {
        current = inv;
        $('#modalAdminEditInvoiceLabel').text('Edit Invoice ' + (inv.invoice_number || '') + (inv.pasien ? ' - ' + inv.pasien : ''));

        var rows = '';
        $.each(inv.items, function(i, it) {
            var gross = it.unit_price * it.quantity;
            var disc = it.discount_type === '%' ? gross * it.discount / 100 : it.discount;
            var line = Math.max(0, gross - Math.min(gross, disc));
            rows += '<tr data-id="' + it.id + '" data-qty="' + it.quantity + '" data-fee="' + (it.is_fee ? 1 : 0) + '" data-orig-line="' + line + '">'
                + '<td>' + esc(it.name) + (it.is_fee ? ' <span class="badge badge-light">biaya</span>' : '') + '</td>'
                + '<td class="text-right">' + esc(it.quantity) + '</td>'
                + '<td><input type="number" min="0" step="any" class="form-control form-control-sm aei-input aei-price" value="' + it.unit_price + '"></td>'
                + '<td><div class="aei-disc"><input type="number" min="0" step="any" class="form-control form-control-sm aei-item-disc" value="' + it.discount + '">'
                + '<select class="form-control form-control-sm aei-item-disc-type"><option value="nominal"' + (it.discount_type !== '%' ? ' selected' : '') + '>Rp</option><option value="%"' + (it.discount_type === '%' ? ' selected' : '') + '>%</option></select></div></td>'
                + '<td class="text-right font-weight-bold aei-line"></td>'
                + '</tr>';
        });
        $modal.find('.aei-items').html(rows);
        $('#aei-discount-value').val(inv.discount_value);
        $('#aei-discount-type').val(inv.discount_type === 'nominal' ? 'nominal' : '%');
        $('#aei-tax').val(inv.tax_percentage);
        $('#aei-reason').val('');

        var editable = !inv.editable_error;
        $modal.find('.aei-error').toggle(!editable).text(inv.editable_error || '');
        $modal.find('.aei-content').find('input, select, textarea').prop('disabled', !editable);
        $('#aei-save').prop('disabled', !editable);

        var logs = '';
        $.each(inv.logs || [], function(i, log) {
            var change = log.new_transaction_date
                ? 'tanggal transaksi ' + esc(log.old_transaction_date) + ' &rarr; ' + esc(log.new_transaction_date)
                : log.new_retur_date
                    ? 'tanggal retur ' + esc(log.retur_number) + ' ' + esc(log.old_retur_date) + ' &rarr; ' + esc(log.new_retur_date)
                    : 'total ' + rupiah(log.old_total) + ' &rarr; ' + rupiah(log.new_total);
            logs += '<div class="aei-log"><div><strong>' + esc(log.user) + '</strong> &middot; ' + esc(log.tanggal)
                + ' &middot; ' + change + '</div>'
                + '<div class="text-muted">Alasan: ' + esc(log.reason) + '</div>';
            $.each(log.items || [], function(j, it) {
                logs += '<div>&bull; ' + esc(it.name) + ': ' + rupiah(it.old_final) + ' &rarr; ' + rupiah(it.new_final) + '</div>';
            });
            logs += '</div>';
        });
        $modal.find('.aei-logs').html(logs);
        $modal.find('.aei-logs-wrap').toggle(!!logs);

        recalc();
        $modal.find('.aei-loading').hide();
        $modal.find('.aei-content').show();
    }

    $(document).on('click', '.btn-admin-edit-invoice', function(e) {
        e.preventDefault();
        var id = $(this).data('invoice-id');
        current = null;
        $modal.find('.aei-content').hide();
        $modal.find('.aei-loading').show();
        $('#aei-save').prop('disabled', true);
        $modal.modal('show');
        $.getJSON(showUrl + id + '/admin-edit').done(render).fail(function(xhr) {
            $modal.modal('hide');
            Swal.fire('Gagal', (xhr.responseJSON && xhr.responseJSON.message) || 'Gagal memuat invoice.', 'error');
        });
    });

    $modal.on('input change', 'input, select', recalc);

    $('#aei-save').on('click', function() {
        if (!current) return;
        var reason = $.trim($('#aei-reason').val());
        if (reason.length < 5) {
            Swal.fire('Alasan wajib diisi', 'Tulis alasan perubahan (minimal 5 karakter).', 'warning');
            return;
        }
        var items = [];
        $modal.find('.aei-items tr').each(function() {
            var $tr = $(this);
            items.push({
                id: $tr.data('id'),
                unit_price: num($tr.find('.aei-price')),
                discount: num($tr.find('.aei-item-disc')),
                discount_type: $tr.find('.aei-item-disc-type').val()
            });
        });
        var $btn = $(this).prop('disabled', true);
        $.ajax({
            url: showUrl + current.id + '/admin-edit',
            method: 'PUT',
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': csrf },
            data: JSON.stringify({
                reason: reason,
                discount_value: num($('#aei-discount-value')),
                discount_type: $('#aei-discount-type').val(),
                tax_percentage: num($('#aei-tax')),
                items: items
            })
        }).done(function(res) {
            $modal.modal('hide');
            Swal.fire('Tersimpan', res.message || 'Invoice berhasil diperbarui.', 'success');
            try { if (window.billingTable) window.billingTable.ajax.reload(null, false); } catch (err) {}
            if (typeof window.financeBillingFetchTabCounts === 'function') window.financeBillingFetchTabCounts();
        }).fail(function(xhr) {
            var r = xhr.responseJSON || {};
            var msg = r.message || 'Gagal menyimpan.';
            if (r.errors) msg = $.map(r.errors, function(v) { return v; }).join('\n');
            Swal.fire('Gagal', msg, 'error');
        }).always(function() {
            $btn.prop('disabled', false);
        });
    });
});
</script>
@endpush
