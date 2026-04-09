<script>
$(function() {
    function formatRupiah(value) {
        var amount = parseFloat(value);
        if (isNaN(amount)) {
            amount = 0;
        }

        return 'Rp ' + amount.toLocaleString('id-ID', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function normalizeStatus(status) {
        var value = String(status || 'draft').toLowerCase().trim();
        return value === 'diapprove' ? 'approved' : value;
    }

    function updateTotalBebanGaji(api) {
        var total = 0;

        api.rows({ search: 'applied' }).every(function() {
            var row = this.data() || {};
            var amount = parseFloat(row.total_gaji);
            total += isNaN(amount) ? 0 : amount;
        });

        $('#slipTotalBeban').text(formatRupiah(total));
    }

    var table = $('#slipGajiTable').DataTable({
        processing: true,
        serverSide: true,
        dom: "<'row mb-2 align-items-center'<'col-md-6 d-flex align-items-center'f><'col-md-6 d-flex justify-content-end align-items-center'<'slipgaji-dt-toolbar d-flex align-items-center'>>>" +
            "<'row'<'col-12'tr>>" +
            "<'row mt-2'<'col-md-5'i><'col-md-7'p>>",
        ajax: {
            url: '{{ route('hrd.payroll.slip_gaji.data') }}',
            data: function(d) {
                d.bulan = $('#filterBulan').val();
                d.status = 'submitted';
                d.division_id = $('#filterDivision').val();
            }
        },
        pageLength: 25,
        order: [[1, 'asc']],
        autoWidth: false,
        initComplete: function() {
            var api = this.api();
            var $container = $(api.table().container());
            var $holder = $('#slipGajiToolbarHolder');
            var $toolbar = $container.find('.slipgaji-dt-toolbar');

            if ($holder.length && $toolbar.length) {
                $toolbar.empty().append($holder.children());
                $holder.remove();
            }

            updateTotalBebanGaji(api);
        },
        drawCallback: function() {
            updateTotalBebanGaji(this.api());
        },
        columns: [
            { data: 'id', name: 'pr_slip_gaji.id', visible: false },
            {
                data: 'nama',
                name: 'e.nama',
                render: function(data, type, row) {
                    if (type === 'display') {
                        var division = row.division_name ? '<div class="text-muted small">' + row.division_name + '</div>' : '';
                        return '<div><strong>' + (data || '-') + '</strong>' + division + '</div>';
                    }

                    return data;
                }
            },
            {
                data: 'last_month_total_gaji',
                name: 'prev_slip.total_gaji',
                className: 'text-right',
                render: function(data, type) {
                    if (type === 'display') {
                        return '<span class="text-nowrap">' + formatRupiah(data) + '</span>';
                    }

                    return data || 0;
                }
            },
            {
                data: 'total_gaji',
                name: 'pr_slip_gaji.total_gaji',
                className: 'text-right',
                render: function(data, type) {
                    if (type === 'display') {
                        return '<strong class="text-nowrap">' + formatRupiah(data) + '</strong>';
                    }

                    return data || 0;
                }
            },
            {
                data: null,
                orderable: false,
                searchable: false,
                render: function(data, type, row) {
                    if (type !== 'display') {
                        return normalizeStatus(row.status);
                    }

                    var status = normalizeStatus(row.status);
                    var actions =
                        '<button type="button" class="btn btn-sm btn-outline-primary mr-1 btn-print">Slip Gaji</button>';

                    if (status === 'submitted') {
                        actions += '' +
                            '<div class="btn-group btn-group-sm" role="group" aria-label="Approval Actions">' +
                            '  <button type="button" class="btn btn-danger btn-reject-slip" data-id="' + row.id + '">Reject</button>' +
                            '  <button type="button" class="btn btn-success btn-approve-slip" data-id="' + row.id + '">Approve</button>' +
                            '</div>';
                    }

                    return actions;
                }
            }
        ]
    });

    $('#filterBulan, #filterDivision').on('change', function() {
        table.ajax.reload();
    });

    $('#slipGajiTable').on('click', '.btn-approve-slip', function() {
        var rowData = table.row($(this).closest('tr')).data();
        if (!rowData || normalizeStatus(rowData.status) !== 'submitted') {
            Swal.fire('Info', 'Hanya slip Submitted yang bisa di-approve.', 'info');
            return;
        }

        Swal.fire({
            title: 'Approve Slip Gaji',
            text: 'Ubah status slip gaji menjadi Approved?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Approve',
            cancelButtonText: 'Batal'
        }).then(function(result) {
            if (!result.value) {
                return;
            }

            $.ajax({
                url: '/hrd/payroll/slip-gaji/update/' + rowData.id,
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    status_gaji: 'approved'
                },
                success: function(res) {
                    if (res && res.success) {
                        Swal.fire('Sukses', 'Slip gaji berhasil diubah menjadi Approved.', 'success');
                        table.ajax.reload(null, false);
                    } else {
                        Swal.fire('Error', (res && res.message) ? res.message : 'Gagal approve slip gaji.', 'error');
                    }
                },
                error: function(xhr) {
                    var message = 'Terjadi kesalahan saat approve slip gaji.';
                    if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }
                    Swal.fire('Error', message, 'error');
                }
            });
        });
    });

    $('#slipGajiTable').on('click', '.btn-reject-slip', function() {
        var rowData = table.row($(this).closest('tr')).data();
        if (!rowData || normalizeStatus(rowData.status) !== 'submitted') {
            Swal.fire('Info', 'Hanya slip Submitted yang bisa di-reject.', 'info');
            return;
        }

        Swal.fire({
            title: 'Reject Slip Gaji',
            text: 'Ubah status slip gaji menjadi Rejected agar HRD dapat revisi?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Reject',
            cancelButtonText: 'Batal'
        }).then(function(result) {
            if (!result.value) {
                return;
            }

            $.ajax({
                url: '/hrd/payroll/slip-gaji/update/' + rowData.id,
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    status_gaji: 'rejected'
                },
                success: function(res) {
                    if (res && res.success) {
                        Swal.fire('Sukses', 'Slip gaji berhasil diubah menjadi Rejected.', 'success');
                        table.ajax.reload(null, false);
                    } else {
                        Swal.fire('Error', (res && res.message) ? res.message : 'Gagal reject slip gaji.', 'error');
                    }
                },
                error: function(xhr) {
                    var message = 'Terjadi kesalahan saat reject slip gaji.';
                    if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }
                    Swal.fire('Error', message, 'error');
                }
            });
        });
    });

    $('#slipGajiTable').on('click', '.btn-print', function() {
        var data = table.row($(this).closest('tr')).data();
        if (!data || !data.id) {
            return;
        }

        var previewUrl = '{{ url('hrd/payroll/slip-gaji/print') }}/' + data.id;
        var employeeName = data.nama ? (' - ' + data.nama) : '';

        $('#modalSlipGajiPreviewLabel').text('Preview Slip Gaji' + employeeName);
        $('#slipGajiPreviewFrame').attr('src', previewUrl);
        $('#modalSlipGajiPreview').modal('show');
    });

    $('#modalSlipGajiPreview').on('hidden.bs.modal', function() {
        $('#slipGajiPreviewFrame').attr('src', 'about:blank');
    });
});
</script>