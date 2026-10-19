{{-- Script for bcl.partials.refund_modal. --}}
<script>
(function() {
    var showUrl = "{{ route('bcl.transaksi.show', ':id') }}";

    window.bclOpenRefund = function(transId, label) {
        var today = moment().format('YYYY-MM-DD');
        $('#rf_trans_id').val(transId);
        $('#rf_label').text(label ? '(' + label + ')' : '');
        $('#rf_tgl_keluar, #rf_tgl_refund').val(today);
        $('#rf_nominal').val(0).removeAttr('max');
        $('#rf_alasan').val('');
        $('#rf_period, #rf_paid').text('Memuat...');
        $('#md_refund').modal('show');

        $.getJSON(showUrl.replace(':id', encodeURIComponent(transId)), { id: transId }).done(function(trx) {
            var paid = (trx.jurnal || []).reduce(function(total, line) {
                return total + (parseFloat(line.kredit) || 0);
            }, 0);
            $('#rf_period').text((trx.tgl_mulai || '-') + ' s/d ' + (trx.tgl_selesai || '-'));
            $('#rf_paid').text('Rp ' + $.number(paid, 0));
            $('#rf_nominal').attr('max', paid);
        });
    };
})();
</script>
