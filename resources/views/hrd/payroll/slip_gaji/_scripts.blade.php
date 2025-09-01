<script>
$(function() {
    // Submit form edit slip gaji
    $(document).on('click', '#btnSimpanSlipGaji', function() {
        var form = $('#formEditSlipGaji');
        var id = $('#slipGajiTable').DataTable().row('.selected').data()?.id || form.data('id');
        if (!id) {
            // fallback: cari id dari input hidden jika ada
            id = form.find('input[name="id"]').val();
        }
        var formData = form.serialize();
        $.ajax({
            url: '/hrd/payroll/slip-gaji/update/' + id,
            type: 'POST',
            data: formData + '&_token={{ csrf_token() }}',
            success: function(res) {
                if(res.success) {
                    Swal.fire('Sukses', 'Data slip gaji berhasil diupdate!', 'success');
                    $('#modalSlipGajiDetail').modal('hide');
                    table.ajax.reload();
                } else {
                    Swal.fire('Error', 'Gagal update data!', 'error');
                }
            },
            error: function(xhr) {
                Swal.fire('Error', 'Terjadi kesalahan!', 'error');
            }
        });
    });
    var table = $('#slipGajiTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route('hrd.payroll.slip_gaji.data') }}',
            data: function(d) {
                d.bulan = $('#filterBulan').val();
            }
        },
        columns: [
            { data: 'id', name: 'id', visible: false }, // slip gaji id, hidden
            { data: 'no_induk', name: 'employee.no_induk' },
            { data: 'nama', name: 'employee.nama' },
            { data: 'divisi', name: 'employee.division.name' },
            { data: 'jumlah_hari_masuk', name: 'total_hari_masuk' },
            { data: 'kpi_poin', name: 'kpi_poin' },
            { data: 'jumlah_pendapatan', name: 'total_pendapatan' },
            { data: 'jumlah_potongan', name: 'total_potongan' },
            { data: 'total_gaji', name: 'total_gaji' },
            { data: 'status', name: 'status' },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ]
    });

    // Month filter change
    $('#filterBulan').on('change', function() {
        var bulan = $(this).val();
        var bulanText = bulan ? bulan.split('-')[1] + '-' + bulan.split('-')[0] : '';
        $('#omsetInfoBulan').text(bulanText);
        // Fetch omset for selected month
        $.get('{{ url('hrd/payroll/slip-gaji/omset-bulanan-total') }}?bulan=' + bulan, function(res) {
            $('#omsetInfoNominal').text(res.total_omset);
        });
        // Fetch KPI summary for selected month
        $.get('{{ url('hrd/payroll/slip-gaji/kpi-summary') }}?bulan=' + bulan, function(res) {
            $('#kpiInfoTotal').text(res.total_kpi_poin);
            $('#kpiInfoAvg').text(res.average_kpi_poin);
        });
        table.ajax.reload();
    });

    // Initial load omset info
    $(document).ready(function() {
        var bulan = $('#filterBulan').val();
        var bulanText = bulan ? bulan.split('-')[1] + '-' + bulan.split('-')[0] : '';
        $('#omsetInfoBulan').text(bulanText);
        $.get('{{ url('hrd/payroll/slip-gaji/omset-bulanan-total') }}?bulan=' + bulan, function(res) {
            $('#omsetInfoNominal').text(res.total_omset);
        });
    });

    $('#slipGajiTable').on('click', '.btn-detail', function() {
        var data = table.row($(this).parents('tr')).data();
        $.get('{{ url('hrd/payroll/slip-gaji/detail') }}/' + data.id, function(res) {
            $('#slipGajiDetailBody').html(res);
            $('#modalSlipGajiDetail').modal('show');
        });
    });

    $('#slipGajiTable').on('click', '.btn-status', function() {
        var data = table.row($(this).parents('tr')).data();
        Swal.fire({
            title: 'Ubah Status?',
            text: 'Ubah status slip gaji ini?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if(result.isConfirmed) {
                $.ajax({
                    url: '{{ url('hrd/payroll/slip-gaji/status') }}/' + data.id,
                    type: 'PUT',
                    data: {_token: '{{ csrf_token() }}'},
                    success: function(res) {
                        if(res.success) {
                            Swal.fire('Sukses', 'Status berhasil diubah!', 'success');
                            table.ajax.reload();
                        }
                    },
                    error: function(xhr) {
                        Swal.fire('Error', 'Terjadi kesalahan!', 'error');
                    }
                });
            }
        });
    });
});
</script>
