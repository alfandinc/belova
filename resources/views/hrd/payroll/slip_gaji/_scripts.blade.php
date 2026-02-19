<script>
$(function() {
    function bindSwipeScroll(api) {
        var $container = $(api.table().container());
        var el = $container.find('.dataTables_scrollBody').get(0);
        if (!el || el.dataset.swipeBound === '1') {
            return;
        }
        el.dataset.swipeBound = '1';

        function isInteractive(target) {
            return $(target).closest('input,textarea,select,button,a,label,.dropdown-menu,.dropdown,.btn,.slip-inline-edit').length > 0;
        }

        var isDown = false;
        var pointerId = null;
        var startX = 0;
        var startScrollLeft = 0;
        var dragged = false;

        el.addEventListener('pointerdown', function(e) {
            // Only left mouse button; allow touch/pen
            if (e.pointerType === 'mouse' && e.button !== 0) return;
            if (isInteractive(e.target)) return;

            isDown = true;
            dragged = false;
            pointerId = e.pointerId;
            startX = e.clientX;
            startScrollLeft = el.scrollLeft;

            try {
                el.setPointerCapture(pointerId);
            } catch (err) {}

            el.classList.add('dt-dragging');
        }, { passive: true });

        el.addEventListener('pointermove', function(e) {
            if (!isDown) return;
            if (pointerId !== null && e.pointerId !== pointerId) return;

            var dx = e.clientX - startX;
            if (Math.abs(dx) > 2) {
                dragged = true;
            }
            el.scrollLeft = startScrollLeft - dx;
            if (dragged) {
                e.preventDefault();
            }
        }, { passive: false });

        function endDrag(e) {
            if (!isDown) return;
            if (pointerId !== null && e && e.pointerId !== undefined && e.pointerId !== pointerId) return;
            isDown = false;
            pointerId = null;
            el.classList.remove('dt-dragging');
            // Keep dragged flag for the click-capture below
            setTimeout(function() { dragged = false; }, 0);
        }

        el.addEventListener('pointerup', endDrag, { passive: true });
        el.addEventListener('pointercancel', endDrag, { passive: true });
        el.addEventListener('lostpointercapture', endDrag, { passive: true });

        // Prevent accidental clicks after dragging
        el.addEventListener('click', function(e) {
            if (!dragged) return;
            e.preventDefault();
            e.stopPropagation();
        }, true);
    }

    function formatRupiah(value) {
        if (value === null || value === undefined || value === '') {
            return 'Rp 0,00';
        }
        var raw = String(value)
            .replace(/\s+/g, '')
            .replace(/[^0-9,.-]/g, '')
            .replace(/\.(?=\d{3}(\D|$))/g, '');

        // Handle id-ID decimal comma
        if (raw.indexOf(',') >= 0 && raw.indexOf('.') >= 0) {
            raw = raw.replace(/\./g, '').replace(',', '.');
        } else if (raw.indexOf(',') >= 0) {
            raw = raw.replace(',', '.');
        }

        var num = parseFloat(raw);
        if (isNaN(num)) num = 0;
        return 'Rp ' + num.toLocaleString('id-ID', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function parseToNumber(value) {
        if (value === null || value === undefined || value === '') {
            return 0;
        }
        if (typeof value === 'number') {
            return isNaN(value) ? 0 : value;
        }
        var raw = String(value)
            .replace(/\s+/g, '')
            .replace(/[^0-9,.-]/g, '')
            .replace(/\.(?=\d{3}(\D|$))/g, '');

        if (raw.indexOf(',') >= 0 && raw.indexOf('.') >= 0) {
            raw = raw.replace(/\./g, '').replace(',', '.');
        } else if (raw.indexOf(',') >= 0) {
            raw = raw.replace(',', '.');
        }

        var num = parseFloat(raw);
        return isNaN(num) ? 0 : num;
    }

    function updateTotalBebanGaji(api) {
        var $el = $('#slipTotalBeban');
        if (!$el.length) return;

        var sum = 0;
        try {
            api.rows({ search: 'applied' }).every(function() {
                var row = this.data() || {};
                sum += parseToNumber(row.total_gaji);
            });
        } catch (err) {
            sum = 0;
        }
        $el.text(formatRupiah(sum));
    }

    // Submit form edit slip gaji (detail modal)
    $(document).on('click', '#btnSimpanSlipGaji', function() {
        var form = $('#formEditSlipGaji');
        var id = $('#slipGajiTable').DataTable().row('.selected').data()?.id || form.data('id');
        if (!id) {
            // fallback: cari id dari input hidden jika ada
            id = form.find('input[name="id"]').val();
        }
        var formData = new FormData(form[0]);
        formData.append('_token', '{{ csrf_token() }}');
        $.ajax({
            url: '/hrd/payroll/slip-gaji/update/' + id,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
                if (res.success) {
                    Swal.fire('Sukses', 'Data slip gaji berhasil diupdate!', 'success');
                    $('#modalSlipGajiDetail').modal('hide');
                    table.ajax.reload(null, false);
                } else {
                    Swal.fire('Error', 'Gagal update data!', 'error');
                }
            },
            error: function() {
                Swal.fire('Error', 'Terjadi kesalahan!', 'error');
            }
        });
    });

    // Column indices (match the `columns:` array below)
    var COL_ID = 0;
    var COL_NAMA = 1;
    var COL_TOTAL_GAJI = 15;
    var COL_CHECKBOX = 16;

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
                d.status = $('#filterStatus').val();
                d.division_id = $('#filterDivision').val();
            }
        },
        pageLength: -1,
        lengthMenu: [[-1, 10, 25, 50, 100], ['All', 10, 25, 50, 100]],
        scrollX: true,
        scrollCollapse: true,
        autoWidth: false,
        initComplete: function() {
            // Pin right columns (Total Gaji + checkbox) on the right
            var api = this.api();
            var $container = $(api.table().container());

            // Move Sync/Bulk controls next to DataTables search (left side)
            var $holder = $('#slipGajiToolbarHolder');
            var $toolbar = $container.find('.slipgaji-dt-toolbar');
            if ($holder.length && $toolbar.length) {
                $toolbar.empty().append($holder.children());
                $holder.remove();
            }

            var visibleIndexCheckbox = api.column(COL_CHECKBOX).index('visible');
            var visibleIndexTotalGaji = api.column(COL_TOTAL_GAJI).index('visible');
            var visibleIndexNama = api.column(COL_NAMA).index('visible');

            // Original header
            $(api.column(COL_CHECKBOX).header()).addClass('dt-sticky-right dt-sticky-right-0 bg-white');
            $(api.column(COL_TOTAL_GAJI).header()).addClass('dt-sticky-right dt-sticky-right-36 bg-white');
            $(api.column(COL_NAMA).header()).addClass('dt-col-nama dt-sticky-left bg-white');

            // DataTables scrollX clones header into .dataTables_scrollHead
            $container.find('.dataTables_scrollHead thead tr').each(function() {
                $(this).find('th').eq(visibleIndexCheckbox).addClass('dt-sticky-right dt-sticky-right-0 bg-white');
                $(this).find('th').eq(visibleIndexTotalGaji).addClass('dt-sticky-right dt-sticky-right-36 bg-white');
                $(this).find('th').eq(visibleIndexNama).addClass('dt-col-nama dt-sticky-left bg-white');
            });

            bindSwipeScroll(api);

            updateTotalBebanGaji(api);
        },
        drawCallback: function() {
            // Re-apply on redraw (safety for column sizing / header rebuild)
            var api = this.api();
            var $container = $(api.table().container());
            var visibleIndexCheckbox = api.column(COL_CHECKBOX).index('visible');
            var visibleIndexTotalGaji = api.column(COL_TOTAL_GAJI).index('visible');
            var visibleIndexNama = api.column(COL_NAMA).index('visible');
            $container.find('.dataTables_scrollHead thead tr').each(function() {
                $(this).find('th').eq(visibleIndexCheckbox).addClass('dt-sticky-right dt-sticky-right-0 bg-white');
                $(this).find('th').eq(visibleIndexTotalGaji).addClass('dt-sticky-right dt-sticky-right-36 bg-white');
                $(this).find('th').eq(visibleIndexNama).addClass('dt-col-nama dt-sticky-left bg-white');
            });

            // reset header select-all each draw
            $('#slipChkAll').prop('checked', false);
            updateBulkStatusUi();

            updateTotalBebanGaji(api);

            bindSwipeScroll(api);
        },
        columnDefs: [
            {
                targets: COL_TOTAL_GAJI,
                createdCell: function(td, cellData, rowData) {
                    var status = (rowData && rowData.status) ? String(rowData.status).toLowerCase() : 'draft';

                    $(td)
                        .addClass('dt-sticky-right dt-sticky-right-36')
                        .removeClass('bg-success bg-warning bg-white text-white text-dark');

                    if (status === 'paid') {
                        $(td).addClass('bg-success text-white');
                    } else if (status === 'diapprove') {
                        $(td).addClass('bg-warning text-dark');
                    } else {
                        // draft / fallback
                        $(td).addClass('bg-white');
                    }
                }
            },
            {
                targets: COL_NAMA,
                createdCell: function(td) {
                    $(td).addClass('dt-col-nama dt-sticky-left bg-white');
                }
            },
            {
                targets: COL_CHECKBOX,
                createdCell: function(td) {
                    $(td).addClass('dt-sticky-right dt-sticky-right-0 bg-white');
                }
            }
        ],
        columns: [
            { data: 'id', name: 'id', visible: false },
            {
                data: 'nama',
                name: 'e.nama',
                render: function(data, type, row) {
                    if (type === 'display') {
                        var nama = data || '';
                        var divName = (row && row.division_name) ? row.division_name : '';
                        var divHtml = divName ? ('<div class="text-muted small">' + divName + '</div>') : '';
                        return '<div><strong>' + nama + '</strong>' + divHtml + '</div>';
                    }
                    return data;
                }
            },
            { data: 'jumlah_hari_masuk', name: 'pr_slip_gaji.total_hari_masuk', render: function(data, type, row) {
                    if (type === 'display') {
                        var isPaid = String((row && row.status) ? row.status : '').toLowerCase() === 'paid';
                        var dis = isPaid ? 'disabled' : '';
                        var v = (row.jumlah_hari_masuk !== undefined && row.jumlah_hari_masuk !== null) ? row.jumlah_hari_masuk : (data || 0);
                        return '<input type="number" step="1" min="0" class="form-control form-control-sm slip-inline-edit" ' + dis + ' data-id="' + row.id + '" data-field="total_hari_masuk" value="' + (v || 0) + '">';
                    }
                    return data;
                }
            },
            { data: 'gaji_pokok', name: 'pr_slip_gaji.gaji_pokok', render: function(data, type, row) {
                    if (type === 'display') {
                        var isPaid = String((row && row.status) ? row.status : '').toLowerCase() === 'paid';
                        var dis = isPaid ? 'disabled' : '';
                        return '<input type="number" step="0.01" class="form-control form-control-sm slip-inline-edit slip-money" ' + dis + ' data-id="' + row.id + '" data-field="gaji_pokok" value="' + (row.gaji_pokok || 0) + '">';
                    }
                    return data;
                }
            },
            { data: 'tunjangan_jabatan', name: 'pr_slip_gaji.tunjangan_jabatan', render: function(data, type, row) {
                    if (type === 'display') {
                        var isPaid = String((row && row.status) ? row.status : '').toLowerCase() === 'paid';
                        var dis = isPaid ? 'disabled' : '';
                        return '<input type="number" step="0.01" class="form-control form-control-sm slip-inline-edit slip-money" ' + dis + ' data-id="' + row.id + '" data-field="tunjangan_jabatan" value="' + (row.tunjangan_jabatan || 0) + '">';
                    }
                    return data;
                }
            },
            { data: 'tunjangan_masa_kerja', name: 'pr_slip_gaji.tunjangan_masa_kerja', render: function(data, type, row) {
                    if (type === 'display') {
                        var isPaid = String((row && row.status) ? row.status : '').toLowerCase() === 'paid';
                        var dis = isPaid ? 'disabled' : '';
                        return '<input type="number" step="0.01" class="form-control form-control-sm slip-inline-edit slip-money" ' + dis + ' data-id="' + row.id + '" data-field="tunjangan_masa_kerja" value="' + (row.tunjangan_masa_kerja || 0) + '">';
                    }
                    return data;
                }
            },
            { data: 'uang_makan', name: 'pr_slip_gaji.uang_makan', render: function(data, type, row) {
                    if (type === 'display') {
                        var isPaid = String((row && row.status) ? row.status : '').toLowerCase() === 'paid';
                        var dis = isPaid ? 'disabled' : '';
                        return '<input type="number" step="0.01" class="form-control form-control-sm slip-inline-edit slip-money" ' + dis + ' data-id="' + row.id + '" data-field="uang_makan" value="' + (row.uang_makan || 0) + '">';
                    }
                    return data;
                }
            },
            { data: 'uang_kpi', name: 'pr_slip_gaji.uang_kpi', render: function(data, type, row) {
                    if (type === 'display') {
                        var isPaid = String((row && row.status) ? row.status : '').toLowerCase() === 'paid';
                        var dis = isPaid ? 'disabled' : '';
                        return '<input type="number" step="0.01" class="form-control form-control-sm slip-inline-edit slip-money" ' + dis + ' data-id="' + row.id + '" data-field="uang_kpi" value="' + (row.uang_kpi || 0) + '">';
                    }
                    return data;
                }
            },
            { data: 'total_jam_lembur', name: 'pr_slip_gaji.total_jam_lembur', render: function(data, type, row) {
                    if (type === 'display') {
                        var isPaid = String((row && row.status) ? row.status : '').toLowerCase() === 'paid';
                        var dis = isPaid ? 'disabled' : '';
                        // Stored as minutes; show/edit as hours
                        var minutes = (row.total_jam_lembur !== undefined && row.total_jam_lembur !== null) ? row.total_jam_lembur : (data || 0);
                        var n = parseFloat(minutes);
                        if (isNaN(n)) n = 0;
                        n = n > 0 ? (n / 60) : 0;
                        return '<input type="number" step="0.01" min="0" class="form-control form-control-sm slip-inline-edit" ' + dis + ' data-id="' + row.id + '" data-field="total_jam_lembur" data-unit="hours" value="' + n.toFixed(2) + '">';
                    }
                    return data;
                }
            },
            { data: 'uang_lembur', name: 'pr_slip_gaji.uang_lembur', render: function(data, type, row) {
                    if (type === 'display') {
                        var isPaid = String((row && row.status) ? row.status : '').toLowerCase() === 'paid';
                        var dis = isPaid ? 'disabled' : '';
                        return '<input type="number" step="0.01" class="form-control form-control-sm slip-inline-edit slip-money" ' + dis + ' data-id="' + row.id + '" data-field="uang_lembur" value="' + (row.uang_lembur || 0) + '">';
                    }
                    return data;
                }
            },
            { data: 'jasa_medis', name: 'pr_slip_gaji.jasa_medis', render: function(data, type, row) {
                    if (type === 'display') {
                        var isPaid = String((row && row.status) ? row.status : '').toLowerCase() === 'paid';
                        var dis = isPaid ? 'disabled' : '';
                        var inputHtml = '<input type="number" step="0.01" class="form-control form-control-sm slip-inline-edit slip-money" ' + dis + ' data-id="' + row.id + '" data-field="jasa_medis" value="' + (row.jasa_medis || 0) + '">';
                        var hasFile = !!(row && row.jasmed_file);
                        var iconClass = hasFile ? 'fa fa-upload text-success' : 'fa fa-upload';
                        var uploadBtn = '' +
                            '<button type="button" class="btn btn-sm btn-outline-secondary ml-1 btn-upload-jasmed" ' + dis + ' data-id="' + row.id + '" title="Upload file" aria-label="Upload file">' +
                            '  <i class="' + iconClass + '"></i>' +
                            '</button>';
                        return '<div class="d-flex align-items-center">' + inputHtml + uploadBtn + '</div>';
                    }
                    return data;
                }
            },
            { data: 'pendapatan_tambahan_total', name: 'pendapatan_tambahan_total', orderable: false, searchable: false, render: function(data, type, row) {
                    if (type === 'display') {
                        var total = data || row.pendapatan_tambahan_total || '0.00';
                        return '<a href="#" class="btn-edit-pendapatan text-primary" data-id="' + row.id + '">' + total + '</a>';
                    }
                    return data;
                }
            },
            { data: 'jumlah_pendapatan', name: 'total_pendapatan', render: function(data, type, row) {
                    if (type === 'display') {
                        return '<span class="font-weight-semibold">' + (row.jumlah_pendapatan || '0.00') + '</span>';
                    }
                    return data;
                }
            },
            { data: 'jumlah_potongan', name: 'total_potongan', render: function(data, type, row) {
                    if (type === 'display') {
                        return '<a href="#" class="btn-edit-potongan text-primary" data-id="' + row.id + '">' + (row.jumlah_potongan || '0.00') + '</a>';
                    }
                    return data;
                }
            },
            { data: 'total_benefit', name: 'total_benefit', render: function(data, type, row) {
                    if (type === 'display') {
                        return '<a href="#" class="btn-edit-benefit text-primary" data-id="' + row.id + '">' + (row.total_benefit || '0.00') + '</a>';
                    }
                    return data;
                }
            },
            { data: 'total_gaji', name: 'pr_slip_gaji.total_gaji', className: 'dt-sticky-right dt-sticky-right-36 text-right', render: function(data, type, row) {
                    if (type === 'display') {
                        var current = row.status || 'draft';
                        var isPaid = String(current).toLowerCase() === 'paid';
                        var amount = (row.total_gaji !== undefined && row.total_gaji !== null) ? row.total_gaji : data;
                        var formattedAmount = formatRupiah(amount);
                        var statusLabelMap = {
                            'draft': 'Draft',
                            'diapprove': 'Diapprove',
                            'paid': 'Paid'
                        };
                        var currentLabel = statusLabelMap[current] || current;

                        var actionsHtml = '' +
                            '<div class="btn-group dropleft">' +
                            '  <button type="button" class="btn btn-sm btn-light dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">' +
                            '    <i class="fa fa-ellipsis-v"></i>' +
                            '  </button>' +
                            '  <div class="dropdown-menu dropdown-menu-right p-2" style="min-width: 220px;">' +
                            '    <div class="small text-muted mb-1">Status Saat Ini</div>' +
                            '    <div class="mb-2"><span class="badge badge-secondary">' + currentLabel + '</span></div>' +
                            (isPaid ? '' : (
                                '    <div class="dropdown-divider"></div>' +
                                '    <div class="small text-muted mb-1">Ubah Status</div>' +
                                '    <button type="button" class="dropdown-item action-set-status" data-id="' + row.id + '" data-status="draft">Draft</button>' +
                                '    <button type="button" class="dropdown-item action-set-status" data-id="' + row.id + '" data-status="diapprove">Diapprove</button>' +
                                '    <button type="button" class="dropdown-item action-set-status" data-id="' + row.id + '" data-status="paid">Paid</button>'
                            )) +
                            '    <div class="dropdown-divider"></div>' +
                            '    <button type="button" class="dropdown-item btn-detail">Detail Slip</button>' +
                            '    <button type="button" class="dropdown-item btn-print">Print</button>' +
                            '  </div>' +
                            '</div>';

                        return '<div class="d-flex align-items-center">' +
                            '<div class="flex-grow-1 text-right pr-2 font-weight-bold text-nowrap">' + formattedAmount + '</div>' +
                            '<div>' + actionsHtml + '</div>' +
                            '</div>';
                    }
                    return data;
                }
            },
            {
                data: null,
                name: null,
                orderable: false,
                searchable: false,
                className: 'dt-sticky-right dt-sticky-right-0 text-center',
                render: function(data, type, row) {
                    if (type === 'display') {
                        var isPaid = String((row && row.status) ? row.status : '').toLowerCase() === 'paid';
                        var dis = isPaid ? 'disabled' : '';
                        var title = isPaid ? 'title="Paid (tidak bisa diubah)"' : '';
                        return '<input type="checkbox" class="slip-row-check" value="' + row.id + '" ' + dis + ' ' + title + '>';
                    }
                    return '';
                }
            }
        ]
    });

    function getSelectedSlipIds() {
        var ids = [];
        $('#slipGajiTable').find('input.slip-row-check:checked').each(function() {
            var v = $(this).val();
            if (v) ids.push(v);
        });
        return ids;
    }

    function updateBulkStatusUi() {
        var anyChecked = $('#slipGajiTable').find('input.slip-row-check:checked').length > 0;
        var hasStatus = String($('#bulkStatus').val() || '').length > 0;
        $('#btnBulkStatus').prop('disabled', !(anyChecked && hasStatus));
    }

    // Select all (only affects current drawn rows)
    $(document).on('change', '#slipChkAll', function() {
        var checked = $(this).is(':checked');
        $('#slipGajiTable').find('input.slip-row-check').each(function() {
            if ($(this).is(':disabled')) return;
            $(this).prop('checked', checked);
        });
        updateBulkStatusUi();
    });

    // Row checkbox change
    $(document).on('change', 'input.slip-row-check', function() {
        updateBulkStatusUi();
    });

    // Bulk status dropdown change
    $(document).on('change', '#bulkStatus', function() {
        updateBulkStatusUi();
    });

    // Apply bulk status
    $(document).on('click', '#btnBulkStatus', function(e) {
        e.preventDefault();
        var status = String($('#bulkStatus').val() || '').toLowerCase();
        var ids = getSelectedSlipIds();
        if (!status) {
            Swal.fire('Info', 'Pilih status terlebih dahulu.', 'info');
            return;
        }
        if (!ids.length) {
            Swal.fire('Info', 'Pilih minimal 1 slip.', 'info');
            return;
        }

        var statusLabelMap = { draft: 'Draft', diapprove: 'Diapprove', paid: 'Paid' };
        var nextLabel = statusLabelMap[status] || status;
        var confirmText = 'Ubah status ' + ids.length + ' slip menjadi ' + nextLabel + '?';
        if (status === 'paid') {
            confirmText = 'Ubah status ' + ids.length + ' slip menjadi Paid? Setelah Paid, slip tidak bisa diedit lagi.';
        }

        Swal.fire({
            title: 'Konfirmasi',
            text: confirmText,
            icon: (status === 'paid') ? 'warning' : 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Ubah',
            cancelButtonText: 'Batal'
        }).then(function(result) {
            if (!result.value) return;

            $.ajax({
                url: '/hrd/payroll/slip-gaji/bulk-status',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    status_gaji: status,
                    ids: ids
                },
                success: function(res) {
                    if (res && res.success) {
                        Swal.fire('Sukses', res.message || ('Bulk status berhasil: ' + nextLabel), 'success');
                        // reset UI + reload
                        $('#bulkStatus').val('');
                        $('#slipChkAll').prop('checked', false);
                        reloadTablePreserveScroll(table);
                    } else {
                        Swal.fire('Error', (res && res.message) ? res.message : 'Bulk status gagal.', 'error');
                    }
                },
                error: function(xhr) {
                    var msg = 'Terjadi kesalahan saat bulk status.';
                    if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                    Swal.fire('Error', msg, 'error');
                }
            });
        });
    });

    function reloadTablePreserveScroll(dt) {
        var $win = $(window);
        var winTop = $win.scrollTop();
        var winLeft = $win.scrollLeft();

        var $container = $(dt.table().container());
        var $scrollBody = $container.find('.dataTables_scrollBody');
        var bodyTop = $scrollBody.length ? $scrollBody.scrollTop() : null;
        var bodyLeft = $scrollBody.length ? $scrollBody.scrollLeft() : null;

        dt.one('draw', function() {
            setTimeout(function() {
                // Re-query after draw
                var $c = $(dt.table().container());
                var $b = $c.find('.dataTables_scrollBody');
                if ($b.length && bodyTop !== null) {
                    $b.scrollTop(bodyTop);
                    $b.scrollLeft(bodyLeft);
                }
                $win.scrollTop(winTop);
                $win.scrollLeft(winLeft);
            }, 0);
        });

        dt.ajax.reload(null, false);
    }

    // Keep column widths aligned within the page on resize
    $(window).on('resize', function() {
        if (table) {
            table.columns.adjust();
        }
    });

    // Month filter change
    $('#filterBulan').on('change', function() {
        table.ajax.reload();
    });

    // Status filter change
    $(document).on('change', '#filterStatus', function() {
        table.ajax.reload();
    });

    // Division filter change
    $(document).on('change', '#filterDivision', function() {
        table.ajax.reload();
    });

    // Inline edit handler (change on input/select)
    $('#slipGajiTable').on('change', '.slip-inline-edit', function() {
        var $el = $(this);
        // Hard guard: paid slips are read-only
        var $tr = $el.closest('tr');
        var rowData = null;
        try {
            rowData = table.row($tr).data();
        } catch (e) {}
        if (rowData && String(rowData.status || '').toLowerCase() === 'paid') {
            Swal.fire('Info', 'Slip dengan status Paid tidak bisa diedit.', 'info');
            return;
        }
        var id = $el.data('id');
        var field = $el.data('field');
        var value = $el.val();
        var unit = $el.data('unit');

        function parseNum(v) {
            if (v === null || v === undefined) return 0;
            var n = parseFloat(v);
            return isNaN(n) ? 0 : n;
        }

        function getRowVal($row, fieldName) {
            var $inp = $row.find('.slip-inline-edit[data-field="' + fieldName + '"]');
            if ($inp.length) return parseNum($inp.val());
            return 0;
        }

        function calcUangLemburFromHours(jamLemburHours, gajiPokok, totalHariMasuk) {
            var gajiPerJam = gajiPokok > 0 ? (gajiPokok / 173) : 0;
            var gajiPerHari = (gajiPokok > 0 && totalHariMasuk > 0) ? (gajiPokok / totalHariMasuk) : 0;
            var jam = jamLemburHours > 0 ? jamLemburHours : 0;
            var uang = 0;

            if (jam <= 0) return 0;

            if (jam <= 6) {
                uang += Math.min(1, jam) * 1.5 * gajiPerJam;
                if (jam > 1) {
                    uang += Math.min(5, jam - 1) * 2 * gajiPerJam;
                }
            } else {
                uang += gajiPerHari; // 6 jam pertama
                var sisaJam = jam - 6;
                if (sisaJam > 0) {
                    uang += Math.min(1, sisaJam) * 1.5 * gajiPerJam;
                    if (sisaJam > 1) {
                        uang += Math.min(3, sisaJam - 1) * 2 * gajiPerJam;
                    }
                }
            }

            return uang;
        }

        // Clear previous success indicator when value changes again
        $el.removeClass('is-valid');

        if (!id || !field) {
            return;
        }

        // Normalize special fields
        if (field === 'total_hari_masuk') {
            var intVal = parseInt(value, 10);
            value = isNaN(intVal) ? 0 : intVal;
        }

        var extraPayload = {};
        if (field === 'total_jam_lembur' && unit === 'hours') {
            var hoursVal = parseFloat(value);
            var minutesVal = isNaN(hoursVal) ? 0 : Math.round(hoursVal * 60);
            value = minutesVal;

            // Auto-recalculate uang lembur when jam lembur changes
            var $row = $el.closest('tr');
            var gajiPokok = getRowVal($row, 'gaji_pokok');
            var totalHariMasuk = getRowVal($row, 'total_hari_masuk');
            var uangLembur = calcUangLemburFromHours(isNaN(hoursVal) ? 0 : hoursVal, gajiPokok, totalHariMasuk);
            extraPayload['uang_lembur'] = uangLembur;

            // Update uang lembur input UI immediately
            var $uangInp = $row.find('.slip-inline-edit[data-field="uang_lembur"]');
            if ($uangInp.length) {
                $uangInp.val(uangLembur);
            }
        }

        var payload = {
            _token: '{{ csrf_token() }}'
        };
        payload[field] = value;
        $.extend(payload, extraPayload);

        $.ajax({
            url: '/hrd/payroll/slip-gaji/update/' + id,
            type: 'POST',
            data: payload,
            success: function(res) {
                if (res && res.success) {
                    // Show a temporary success check icon in the input
                    var oldTimer = $el.data('slipOkTimer');
                    if (oldTimer) {
                        clearTimeout(oldTimer);
                    }
                    $el.addClass('is-valid');
                    var timer = setTimeout(function() {
                        $el.removeClass('is-valid');
                    }, 1500);
                    $el.data('slipOkTimer', timer);

                    // Reload (slightly delayed) to refresh aggregates without hiding the indicator instantly
                    setTimeout(function() {
                        reloadTablePreserveScroll(table);
                    }, 600);
                } else {
                    Swal.fire('Error', 'Gagal menyimpan perubahan.', 'error');
                }
            },
            error: function() {
                Swal.fire('Error', 'Terjadi kesalahan saat menyimpan.', 'error');
            }
        });
    });

    // Live update uang lembur as user types jam lembur (no save yet)
    $('#slipGajiTable').on('input', '.slip-inline-edit[data-field="total_jam_lembur"][data-unit="hours"]', function() {
        var $el = $(this);
        var hoursVal = parseFloat($el.val());
        if (isNaN(hoursVal)) hoursVal = 0;

        function parseNum(v) {
            var n = parseFloat(v);
            return isNaN(n) ? 0 : n;
        }

        function getRowVal($row, fieldName) {
            var $inp = $row.find('.slip-inline-edit[data-field="' + fieldName + '"]');
            if ($inp.length) return parseNum($inp.val());
            return 0;
        }

        function calcUangLemburFromHours(jamLemburHours, gajiPokok, totalHariMasuk) {
            var gajiPerJam = gajiPokok > 0 ? (gajiPokok / 173) : 0;
            var gajiPerHari = (gajiPokok > 0 && totalHariMasuk > 0) ? (gajiPokok / totalHariMasuk) : 0;
            var jam = jamLemburHours > 0 ? jamLemburHours : 0;
            var uang = 0;
            if (jam <= 0) return 0;
            if (jam <= 6) {
                uang += Math.min(1, jam) * 1.5 * gajiPerJam;
                if (jam > 1) {
                    uang += Math.min(5, jam - 1) * 2 * gajiPerJam;
                }
            } else {
                uang += gajiPerHari;
                var sisaJam = jam - 6;
                if (sisaJam > 0) {
                    uang += Math.min(1, sisaJam) * 1.5 * gajiPerJam;
                    if (sisaJam > 1) {
                        uang += Math.min(3, sisaJam - 1) * 2 * gajiPerJam;
                    }
                }
            }
            return uang;
        }

        var $row = $el.closest('tr');
        var gajiPokok = getRowVal($row, 'gaji_pokok');
        var totalHariMasuk = getRowVal($row, 'total_hari_masuk');
        var uangLembur = calcUangLemburFromHours(hoursVal, gajiPokok, totalHariMasuk);
        var $uangInp = $row.find('.slip-inline-edit[data-field="uang_lembur"]');
        if ($uangInp.length) {
            $uangInp.val(uangLembur);
        }
    });

    // Upload Jasmed file (image/pdf) from Jasa Medis column
    $('#slipGajiTable').on('click', '.btn-upload-jasmed', function(e) {
        e.preventDefault();
        if ($(this).is(':disabled')) return;
        var id = $(this).data('id');
        if (!id) return;

        var $picker = $('<input type="file" accept="image/*,.pdf" style="display:none;" />');
        $('body').append($picker);

        $picker.on('change', function() {
            var file = this.files && this.files[0] ? this.files[0] : null;
            if (!file) {
                $picker.remove();
                return;
            }

            var fd = new FormData();
            fd.append('_token', '{{ csrf_token() }}');
            fd.append('jasmed_file', file);

            $.ajax({
                url: '/hrd/payroll/slip-gaji/update/' + id,
                type: 'POST',
                data: fd,
                processData: false,
                contentType: false,
                success: function(res) {
                    if (res && res.success) {
                        Swal.fire('Sukses', 'File jasa medis berhasil diupload.', 'success');
                        table.ajax.reload(null, false);
                    } else {
                        Swal.fire('Error', 'Gagal upload file jasa medis.', 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Terjadi kesalahan saat upload file.', 'error');
                },
                complete: function() {
                    $picker.remove();
                }
            });
        });

        $picker.trigger('click');
    });

    // Change status via dropdown actions
    $('#slipGajiTable').on('click', '.action-set-status', function(e) {
        e.preventDefault();
        var rowData = null;
        try {
            rowData = table.row($(this).closest('tr')).data();
        } catch (err) {}
        if (rowData && String(rowData.status || '').toLowerCase() === 'paid') {
            Swal.fire('Info', 'Slip dengan status Paid tidak bisa diubah.', 'info');
            return;
        }
        var id = $(this).data('id');
        var status = $(this).data('status');
        if (!id || !status) {
            return;
        }

        var nextStatus = String(status || '').toLowerCase();
        var statusLabelMap = {
            'draft': 'Draft',
            'diapprove': 'Diapprove',
            'paid': 'Paid'
        };
        var nextLabel = statusLabelMap[nextStatus] || nextStatus;

        var confirmText = 'Ubah status slip gaji menjadi ' + nextLabel + '?';
        if (nextStatus === 'paid') {
            confirmText = 'Ubah status slip gaji menjadi Paid? Setelah Paid, slip tidak bisa diedit lagi.';
        }

        Swal.fire({
            title: 'Konfirmasi',
            text: confirmText,
            icon: (nextStatus === 'paid') ? 'warning' : 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Ubah',
            cancelButtonText: 'Batal'
        }).then(function(result){
            if (!result.value) return;
            $.ajax({
                url: '/hrd/payroll/slip-gaji/update/' + id,
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    status_gaji: status
                },
                success: function(res) {
                    if (res && res.success) {
                        Swal.fire('Sukses', 'Status berhasil diubah menjadi ' + nextLabel + '.', 'success');
                        $('#slipGajiTable').DataTable().ajax.reload(null, false);
                    } else {
                        Swal.fire('Error', (res && res.message) ? res.message : 'Gagal mengubah status gaji.', 'error');
                    }
                },
                error: function(xhr) {
                    var msg = 'Terjadi kesalahan saat mengubah status.';
                    if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                    Swal.fire('Error', msg, 'error');
                }
            });
        });
    });

    $('#slipGajiTable').on('click', '.btn-detail', function() {
        var data = table.row($(this).parents('tr')).data();
        $.get('{{ url('hrd/payroll/slip-gaji/detail') }}/' + data.id, function(res) {
            $('#slipGajiDetailBody').html(res);
            $('#modalSlipGajiDetail').modal('show');

            // If paid, lock the modal fields + disable save
            var isPaid = data && String(data.status || '').toLowerCase() === 'paid';
            $('#btnSimpanSlipGaji').prop('disabled', isPaid);
            if (isPaid) {
                $('#slipGajiDetailBody').find('input,select,textarea,button').not('[data-dismiss="modal"], .close').prop('disabled', true);
            }
        });
    });

    // Open potongan edit modal when clicking total potongan
    $('#slipGajiTable').on('click', '.btn-edit-potongan', function(e) {
        e.preventDefault();
        var data = table.row($(this).closest('tr')).data();
        if (!data || !data.id) return;
        $.get('{{ url('hrd/payroll/slip-gaji/detail') }}/' + data.id + '?only=potongan', function(res) {
            // load partial potongan modal HTML
            $('#modalPotongan').remove();
            $('body').append(res);

            var empName = data.nama || '';
            var title = empName ? ('Edit Potongan - ' + empName) : 'Edit Potongan';
            $('#modalPotongan').find('#modalPotonganLabel').text(title);

            // fill values if server returned inputs with values (our partial uses blank inputs, fill here)
            $('#modalPotongan').find('#potongan_slip_id').val(data.id);
            $('#modalPotongan').find('#potongan_pinjaman').val(data.potongan_pinjaman || '');
            $('#modalPotongan').find('#potongan_bpjs_kesehatan').val(data.potongan_bpjs_kesehatan || '');
            $('#modalPotongan').find('#potongan_jamsostek').val(data.potongan_jamsostek || '');
            $('#modalPotongan').find('#potongan_penalty').val(data.potongan_penalty || '');
            $('#modalPotongan').find('#potongan_lain').val(data.potongan_lain || '');

            var isPaid = String(data.status || '').toLowerCase() === 'paid';
            if (isPaid) {
                $('#modalPotongan').find('input,select,textarea').prop('disabled', true);
                $('#modalPotongan').find('#btnSimpanPotongan').prop('disabled', true).hide();
            }
            $('#modalPotongan').modal('show');
            setTimeout(function(){
                $('#modalPotongan').find('#potongan_pinjaman').focus();
            }, 250);
        });
    });

    // Open benefit edit modal when clicking total benefit
    $('#slipGajiTable').on('click', '.btn-edit-benefit', function(e) {
        e.preventDefault();
        var data = table.row($(this).closest('tr')).data();
        if (!data || !data.id) return;
        $.get('{{ url('hrd/payroll/slip-gaji/detail') }}/' + data.id + '?only=benefit', function(res) {
            $('#modalBenefit').remove();
            $('body').append(res);

            var empName = data.nama || '';
            var title = empName ? ('Edit Benefit - ' + empName) : 'Edit Benefit';
            $('#modalBenefit').find('#modalBenefitLabel').text(title);

            $('#modalBenefit').find('#benefit_slip_id').val(data.id);
            $('#modalBenefit').find('#benefit_bpjs_kesehatan').val(data.benefit_bpjs_kesehatan || '');
            $('#modalBenefit').find('#benefit_jht').val(data.benefit_jht || '');
            $('#modalBenefit').find('#benefit_jkk').val(data.benefit_jkk || '');
            $('#modalBenefit').find('#benefit_jkm').val(data.benefit_jkm || '');

            var isPaid = String(data.status || '').toLowerCase() === 'paid';
            if (isPaid) {
                $('#modalBenefit').find('input,select,textarea').prop('disabled', true);
                $('#modalBenefit').find('#btnSimpanBenefit').prop('disabled', true).hide();
            }
            $('#modalBenefit').modal('show');
            setTimeout(function(){
                $('#modalBenefit').find('#benefit_bpjs_kesehatan').focus();
            }, 250);
        });
    });

    // Open pendapatan edit modal when clicking pendapatan total
    $('#slipGajiTable').on('click', '.btn-edit-pendapatan', function(e) {
        e.preventDefault();
        var data = table.row($(this).closest('tr')).data();
        if (!data || !data.id) return;
        $.get('{{ url('hrd/payroll/slip-gaji/detail') }}/' + data.id + '?only=pendapatan', function(res) {
            $('#modalPendapatan').remove();
            $('body').append(res);

            var empName = data.nama || '';
            var title = empName ? ('Edit Pendapatan Tambahan - ' + empName) : 'Edit Pendapatan Tambahan';
            $('#modalPendapatan').find('#modalPendapatanLabel').text(title);

            // set slip id; rows are rendered server-side from $slip->pendapatan_tambahan
            $('#modalPendapatan').find('#pendapatan_slip_id').val(data.id);

            var isPaid = String(data.status || '').toLowerCase() === 'paid';
            if (isPaid) {
                $('#modalPendapatan').find('input,select,textarea').prop('disabled', true);
                $('#modalPendapatan').find('#btnAddPendapatanRow').prop('disabled', true).hide();
                $('#modalPendapatan').find('#btnSimpanPendapatan').prop('disabled', true).hide();
                $('#modalPendapatan').find('.btn-remove-pendapatan').prop('disabled', true).hide();
            }
            $('#modalPendapatan').modal('show');
            setTimeout(function(){
                $('#modalPendapatan').find('.pendapatan-label:first').focus();
            }, 250);
        });
    });

    // add row in pendapatan modal
    $(document).on('click', '#btnAddPendapatanRow', function(){
        var $rows = $('#modalPendapatan').find('#pendapatanRows');
        var html = '<div class="form-row mb-2 pendapatan-row">'
            + '<div class="col-6"><input type="text" class="form-control form-control-sm pendapatan-label" placeholder="Label"></div>'
            + '<div class="col-5"><input type="number" step="0.01" class="form-control form-control-sm pendapatan-amount" placeholder="Amount"></div>'
            + '<div class="col-1"><button type="button" class="btn btn-sm btn-danger btn-remove-pendapatan">&times;</button></div>'
            + '</div>';
        $rows.append(html);
    });

    // remove row
    $(document).on('click', '.btn-remove-pendapatan', function(){
        $(this).closest('.pendapatan-row').remove();
    });

    // save pendapatan
    $(document).on('click', '#btnSimpanPendapatan', function(){
        var form = $('#formPendapatan');
        var id = form.find('#pendapatan_slip_id').val();
        if (!id) return;
        var items = [];
        $('#modalPendapatan').find('.pendapatan-row').each(function(){
            var lbl = $(this).find('.pendapatan-label').val();
            var amt = $(this).find('.pendapatan-amount').val();
            if (lbl && amt && amt !== '') {
                items.push({label: lbl, amount: amt});
            }
        });
        var payload = { _token: '{{ csrf_token() }}', pendapatan_tambahan: items };
        $.ajax({
            url: '/hrd/payroll/slip-gaji/update/' + id,
            type: 'POST',
            contentType: 'application/json; charset=utf-8',
            data: JSON.stringify(payload),
            success: function(res){
                if (res && res.success) {
                    $('#modalPendapatan').modal('hide');
                    table.ajax.reload(null, false);
                } else {
                    Swal.fire('Error', 'Gagal menyimpan pendapatan tambahan.', 'error');
                }
            },
            error: function(){
                Swal.fire('Error', 'Terjadi kesalahan saat menyimpan.', 'error');
            }
        });
    });

    // Save benefit from the benefit-only modal
    $(document).on('click', '#btnSimpanBenefit', function() {
        var form = $('#formBenefit');
        var id = form.find('#benefit_slip_id').val();
        if (!id) return;
        var data = {
            _token: '{{ csrf_token() }}',
            benefit_bpjs_kesehatan: form.find('input[name="benefit_bpjs_kesehatan"]').val(),
            benefit_jht: form.find('input[name="benefit_jht"]').val(),
            benefit_jkk: form.find('input[name="benefit_jkk"]').val(),
            benefit_jkm: form.find('input[name="benefit_jkm"]').val()
        };
        $.post('/hrd/payroll/slip-gaji/update/' + id, data, function(res) {
            if (res && res.success) {
                $('#modalBenefit').modal('hide');
                table.ajax.reload(null, false);
            } else {
                Swal.fire('Error', 'Gagal menyimpan benefit.', 'error');
            }
        }).fail(function() {
            Swal.fire('Error', 'Terjadi kesalahan saat menyimpan.', 'error');
        });
    });

    // Save potongan from the potongan-only modal
    $(document).on('click', '#btnSimpanPotongan', function() {
        var form = $('#formPotongan');
        var id = form.find('#potongan_slip_id').val();
        if (!id) return;
        var data = {
            _token: '{{ csrf_token() }}',
            potongan_pinjaman: form.find('input[name="potongan_pinjaman"]').val(),
            potongan_bpjs_kesehatan: form.find('input[name="potongan_bpjs_kesehatan"]').val(),
            potongan_jamsostek: form.find('input[name="potongan_jamsostek"]').val(),
            potongan_penalty: form.find('input[name="potongan_penalty"]').val(),
            potongan_lain: form.find('input[name="potongan_lain"]').val()
        };
        $.post('/hrd/payroll/slip-gaji/update/' + id, data, function(res) {
            if (res && res.success) {
                $('#modalPotongan').modal('hide');
                table.ajax.reload(null, false);
            } else {
                Swal.fire('Error', 'Gagal menyimpan potongan.', 'error');
            }
        }).fail(function() {
            Swal.fire('Error', 'Terjadi kesalahan saat menyimpan.', 'error');
        });
    });


    $('#slipGajiTable').on('click', '.btn-print', function() {
        var data = table.row($(this).parents('tr')).data();
        window.open('/hrd/payroll/slip-gaji/print/' + data.id, '_blank');
    });

    // Sync derived values (hari masuk, uang makan, lembur, totals) from latest data
    $('#btnSyncSlipGaji').on('click', function() {
        var bulan = $('#filterBulan').val();
        if (!bulan) return;

        Swal.fire({
            title: 'Sync Slip Gaji?',
            text: 'Ini akan update Hari Masuk, Uang Makan, Tunjangan Masa Kerja, Lembur, dan total dari data terbaru (skip slip yang sudah Paid).',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Sync',
            cancelButtonText: 'Batal'
        }).then(function(result) {
            if (!result.value) return;
            $.ajax({
                url: '/hrd/payroll/slip-gaji/sync',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    bulan: bulan
                },
                success: function(res) {
                    if (res && res.success) {
                        Swal.fire('Sukses', res.message || 'Sync berhasil.', 'success');
                        table.ajax.reload(null, false);
                    } else {
                        Swal.fire('Error', (res && res.message) ? res.message : 'Sync gagal.', 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Terjadi kesalahan saat sync.', 'error');
                }
            });
        });
    });

    // Prevent dropdown menu in sticky Total Gaji cell from being covered by other sticky cells
    $(document).on('show.bs.dropdown', '#slipGajiTable td.dt-sticky-right .btn-group, #slipGajiTable td.dt-sticky-right .dropdown', function() {
        $(this).closest('td.dt-sticky-right').addClass('dt-dropdown-open');
    });
    $(document).on('hide.bs.dropdown', '#slipGajiTable td.dt-sticky-right .btn-group, #slipGajiTable td.dt-sticky-right .dropdown', function() {
        $(this).closest('td.dt-sticky-right').removeClass('dt-dropdown-open');
    });

});
</script>
