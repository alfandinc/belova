<script>
$(function() {
    // Show modal on button click
    $('#btnBuatSlipGaji').click(function() {
        $('#formBuatSlipGaji')[0].reset();
        $('#omsetBulananInputs').html('');
        // default to the month currently shown in the table
        $('#bulan').val($('#filterBulan').val());
        $('#modalBuatSlipGaji').modal('show');
    });

    // Only require bulan; omset inputs are no longer auto-loaded
    $('#bulan').attr('required', true);

    // Save slip gaji for all employees
    $('#formBuatSlipGaji').submit(function(e) {
        e.preventDefault();
        // Client-side validation: ensure form is valid
        var form = this;
        if (!form.checkValidity()) {
            // Let browser show validation UI
            form.reportValidity();
            return;
        }
        var bulan = $('#bulan').val();
        var $btn = $(form).find('button[type="submit"]');
        $btn.prop('disabled', true);
        $.ajax({
            url: '{{ url('hrd/payroll/slip-gaji/store-all') }}',
            type: 'POST',
            data: $(this).serialize(),
            success: function(res) {
                if(res.success) {
                    Swal.fire('Sukses', res.message || 'Slip gaji berhasil dibuat untuk semua pegawai!', 'success');
                    $('#modalBuatSlipGaji').modal('hide');
                    // Show the month that was just generated
                    if (bulan && $('#filterBulan').val() !== bulan) {
                        $('#filterBulan').val(bulan).trigger('change');
                    } else {
                        $('#slipGajiTable').DataTable().ajax.reload();
                    }
                } else {
                    Swal.fire('Error', res.message || 'Gagal membuat slip gaji.', 'error');
                }
            },
            error: function(xhr) {
                var msg = 'Terjadi kesalahan!';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                Swal.fire('Error', msg, 'error');
            },
            complete: function() {
                $btn.prop('disabled', false);
            }
        });
    });
});
</script>
