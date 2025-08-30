<script>
$(function() {
    // Show modal on button click
    $('#btnBuatSlipGaji').click(function() {
        $('#formBuatSlipGaji')[0].reset();
        $('#omsetBulananInputs').html('');
        $('#modalBuatSlipGaji').modal('show');
    });

    // Load omset data when bulan changes
    $('#bulan').on('change', function() {
        var bulan = $(this).val();
        if(bulan) {
            $.get('{{ url('hrd/payroll/slip-gaji/omset-bulanan') }}?bulan=' + bulan, function(res) {
                $('#omsetBulananInputs').html(res);
            });
        }
    });

    // Save slip gaji for all employees
    $('#formBuatSlipGaji').submit(function(e) {
        e.preventDefault();
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
                Swal.fire('Error', 'Terjadi kesalahan!', 'error');
            }
        });
    });
});
</script>
