<script>
$(function() {
    // Show modal on button click
    $('#btnBuatSlipGaji').click(function() {
        $('#formBuatSlipGaji')[0].reset();
        $('#omsetBulananInputs').html('');
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
        $.ajax({
            url: '{{ url('hrd/payroll/slip-gaji/store-all') }}',
            type: 'POST',
            data: $(this).serialize(),
            success: function(res) {
                if(res.success) {
                    Swal.fire('Sukses', 'Slip gaji berhasil dibuat untuk semua pegawai!', 'success');
                    $('#modalBuatSlipGaji').modal('hide');
                    // Reload DataTable
                    $('#slipGajiTable').DataTable().ajax.reload();
                }
            },
            error: function(xhr) {
                var msg = 'Terjadi kesalahan!';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                Swal.fire('Error', msg, 'error');
            }
        });
    });
});
</script>
