{{-- Script for bcl.partials.change_room_modal. Mirrors tr_renterController::changeRoomCalculation for the preview; the server stays authoritative. --}}
<script>
(function() {
    var optionsUrl = "{{ route('bcl.transaksi.change_room.options', ':id') }}";
    var unitMap = { hari: 'days', minggu: 'weeks', bulan: 'months', tahun: 'years' };
    var current = null;

    function rupiah(value) {
        return 'Rp ' + $.number(Math.round(value || 0), 0);
    }

    function esc(value) {
        return $('<div>').text(value == null ? '' : value).html();
    }

    function initPicker($input) {
        if ($.fn.daterangepicker && !$input.data('daterangepicker')) {
            $input.daterangepicker({ singleDatePicker: true, showDropdowns: true, autoApply: true, locale: { format: 'YYYY-MM-DD' } });
            $input.on('apply.daterangepicker', recalc);
        }
    }

    function recalc() {
        if (!current) {
            return;
        }
        var lama = parseInt(current.lama_sewa, 10) || 0;
        var unit = unitMap[(current.jangka_sewa || '').toLowerCase()] || 'days';
        var effective = moment($('#cr_effective_date').val(), 'YYYY-MM-DD');
        var start = moment(current.package_start, 'YYYY-MM-DD');
        var $selected = $('#cr_new_room_id').find(':selected');
        var newPrice = parseFloat($selected.data('price'));
        var oldPrice = current.old_price == null ? null : parseFloat(current.old_price);
        var outsideRange = effective.isValid() && (effective.isBefore(moment(current.tgl_mulai)) || !effective.isBefore(moment(current.tgl_selesai)));

        $('#cr_date_warning').toggle(outsideRange).text('Tanggal pindah harus di antara ' + current.tgl_mulai + ' dan sebelum ' + current.tgl_selesai + '.');
        $('#cr_old_price').text(oldPrice == null ? 'Tidak ada di Daftar Harga' : rupiah(oldPrice));
        $('#cr_outstanding').text(rupiah(Math.max((current.harga || 0) - (current.paid || 0), 0)));

        if (!effective.isValid() || !$selected.val() || isNaN(newPrice) || oldPrice == null) {
            $('#cr_new_price, #cr_remaining, #cr_result').text('-');
            $('#cr_pay_row, #cr_refund_row').hide();
            return;
        }

        var elapsed = effective.isAfter(start) ? Math.min(lama, Math.max(effective.diff(start, unit), 0)) : 0;
        var remaining = lama - elapsed;
        var percent = lama > 0 ? remaining / lama : 0;
        var payable = Math.round((newPrice - oldPrice) * percent);

        $('#cr_new_price').text(rupiah(newPrice));
        $('#cr_remaining').text(remaining + ' dari ' + lama + ' ' + (current.jangka_sewa || '') + ' (' + (percent * 100).toFixed(1) + '%)');

        if (payable > 0) {
            $('#cr_result_label').text('Tagihan upgrade');
            $('#cr_result').text(rupiah(payable));
            $('#cr_pay_row').show();
            $('#cr_refund_row').hide();
            $('#cr_refund_to_deposit').prop('checked', false);
            var payNow = parseFloat(($('#cr_pay_now').val() || '').toString().replace(/[^0-9]/g, '')) || 0;
            if (payNow > payable) {
                $('#cr_pay_now').val(payable);
            }
        } else if (payable < 0) {
            var refundable = Math.abs(payable);
            if (current.identity !== 'Pindah Kamar') {
                refundable = Math.min(refundable, current.paid || 0);
            }
            $('#cr_result_label').text('Refund downgrade');
            $('#cr_result').text('- ' + rupiah(refundable));
            $('#cr_pay_row').hide();
            $('#cr_pay_now').val(0);
            $('#cr_refund_row').toggle(refundable > 0);
        } else {
            $('#cr_result_label').text('Tagihan / Refund');
            $('#cr_result').text('Tidak ada selisih');
            $('#cr_pay_row, #cr_refund_row').hide();
            $('#cr_pay_now').val(0);
        }
    }

    window.bclOpenChangeRoom = function(transId) {
        current = null;
        $('#cr_trans_id').val(transId);
        $('#cr_trans_label').text(transId ? '(' + transId + ')' : '');
        $('#cr_package_label').text('');
        $('#cr_new_room_id').html('<option value="">Memuat opsi kamar...</option>');
        $('#cr_pay_now').val(0);
        $('#cr_refund_to_deposit').prop('checked', false);
        $('#cr_pay_row, #cr_refund_row, #cr_date_warning').hide();
        $('#cr_old_price, #cr_new_price, #cr_remaining, #cr_result, #cr_outstanding').text('-');
        initPicker($('#cr_effective_date'));
        initPicker($('#cr_payment_date'));
        $('#cr_effective_date').val(moment().format('YYYY-MM-DD'));
        $('#cr_payment_date').val(moment().format('YYYY-MM-DD'));
        $('#md_change_room').modal('show');

        $.getJSON(optionsUrl.replace(':id', encodeURIComponent(transId)))
            .done(function(response) {
                current = response.current || null;
                if (current) {
                    $('#cr_package_label').text('(paket ' + current.lama_sewa + ' ' + (current.jangka_sewa || '') + ')');
                }
                var options = ['<option value="">-- Pilih Kamar --</option>'];
                (response.rooms || []).forEach(function(option) {
                    var disabled = option.is_current_room || option.is_occupied;
                    var suffix = option.is_current_room ? ' [Kamar saat ini]' : (option.is_occupied ? ' [Terisi/Booking]' : '');
                    options.push('<option value="' + option.room.id + '" data-price="' + option.price + '"' + (disabled ? ' disabled' : '') + '>' +
                        esc(option.room.name + ' - ' + (option.room.category_name || '-') + ' (' + rupiah(option.price) + ')' + suffix) + '</option>');
                });
                $('#cr_new_room_id').html(options.join(''));
                if (current && current.old_price == null) {
                    $('#cr_date_warning').show().text('Harga paket ' + current.lama_sewa + ' ' + current.jangka_sewa + ' untuk kamar saat ini tidak ada di Daftar Harga, pindah kamar tidak bisa dihitung.');
                }
                recalc();
            })
            .fail(function() {
                $('#cr_new_room_id').html('<option value="">Gagal memuat opsi kamar</option>');
            });
    };

    $(document).on('change', '#cr_new_room_id, #cr_effective_date', recalc);
    $(document).on('input', '#cr_pay_now', recalc);
    $(document).on('submit', '#form_change_room', function() {
        $('#cr_pay_now').val(($('#cr_pay_now').val() || '0').toString().replace(/[^0-9]/g, '') || 0);
        $('#cr_submit').prop('disabled', true).text('Menyimpan...');
    });
})();
</script>
