@extends('layouts.finance.app')
@section('title', 'Finance |Retur Pembelian')
@section('navbar')
    @include('layouts.finance.navbar')
@endsection

@section('content')
<div class="page-content">
    <div class="container-fluid">
        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-flex align-items-center justify-content-between">
                    <h4 class="mb-0">Retur Pembelian</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="javascript: void(0);">Finance</a></li>
                            <li class="breadcrumb-item active">Retur Pembelian</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        <!-- end page title -->

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="row align-items-center">
                            <div class="col">
                                <h4 class="card-title">Daftar Retur Pembelian</h4>
                            </div>
                            <div class="col-auto">
                                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addReturModal" id="addReturBtn">
                                    <i class="fas fa-plus me-1"></i> Tambah Retur
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="returTable" class="table table-bordered dt-responsive nowrap" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>No. Retur</th>
                                        <th>No. Invoice</th>
                                        <th>Patient</th>
                                        <th>Tanggal</th>
                                        <th>Total Amount</th>
                                        <th>Items</th>
                                        <th>User</th>
                                        <th>Reason</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Retur Modal -->
<div class="modal fade" id="addReturModal" tabindex="-1" aria-labelledby="addReturModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addReturModalLabel">Tambah Retur Pembelian</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="addReturForm">
                <div class="modal-body">
                    <!-- Step 1: Filter Invoice -->
                    <div id="step1" class="step-content">
                        <h6 class="mb-3">1. Filter Invoice</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <label for="dateRange" class="form-label">Pilih Tanggal</label>
                                <input type="text" class="form-control" id="dateRange" placeholder="Click to select date range">
                            </div>
                            <div class="col-md-6">
                                <label for="invoiceSearch" class="form-label">Cari Pasien / ID</label>
                                <input type="text" class="form-control" id="invoiceSearch" placeholder="Cari nama pasien atau invoice ID...">
                            </div>
                        </div>

                        <div class="mt-3" id="invoiceList" style="display: none;">
                            <h6>Pilih Invoice:</h6>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Select</th>
                                            <th>No. Invoice</th>
                                            <th>Tanggal</th>
                                            <th>Patient</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody id="invoiceTableBody">
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Select Items -->
                    <div id="step2" class="step-content" style="display: none;">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0">2. Pilih Item untuk Retur</h6>
                            <button type="button" class="btn btn-secondary btn-sm" id="backToStep1Btn">
                                <i class="fas fa-arrow-left"></i> Kembali
                            </button>
                        </div>
                        
                        <div id="selectedInvoiceInfo" class="alert alert-info">
                        </div>

                        <div id="itemsList">
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-4">
                                <label for="percentage_cut" class="form-label">Potongan Harga (%) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="percentage_cut" name="percentage_cut" 
                                       min="0" max="100" step="0.01" value="0" required>
                                <small class="text-muted">Masukkan persentase potongan harga (0-100%)</small>
                            </div>
                            <div class="col-md-4">
                                <label for="reason" class="form-label">Alasan Retur <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="reason" name="reason" rows="3" required></textarea>
                            </div>
                            <div class="col-md-4">
                                <label for="notes" class="form-label">Catatan</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" id="submitBtn" class="btn btn-primary" style="display: none;">
                        <i class="fas fa-save"></i> Simpan Retur
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Detail Modal -->
<div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detailModalLabel">Detail Retur Pembelian</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="detailContent">
                <!-- Detail content will be loaded here -->
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
$(document).ready(function() {
    let selectedInvoice = null;
    let selectedItems = [];
    let invoicesCache = [];

    // Initialize DataTable
    $('#returTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('finance.retur-pembelian.index') }}",
        columns: [
            { data: 'retur_number', name: 'retur_number' },
            { data: 'invoice.invoice_number', name: 'invoice.invoice_number' },
            { data: 'patient', name: 'patient' },
            { data: 'processed_date', name: 'processed_date' },
            { data: 'total_amount', name: 'total_amount' },
            { data: 'items_count', name: 'items_count', orderable: false },
            { data: 'user.name', name: 'user.name' },
            { data: 'reason', name: 'reason' },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ],
        responsive: true,
        order: [[2, 'desc']]
    });

    // Initialize date range picker
    if (typeof $.fn.daterangepicker === 'function') {
        $('#dateRange').daterangepicker({
            locale: {
                format: 'DD/MM/YYYY'
            },
            // make selection quicker (auto apply) and show dropdowns for easier single-date selection
            autoApply: true,
            showDropdowns: true,
            opens: 'right'
        }, function(start, end, label) {
            filterInvoices();
        });
    } else {
        // Fallback: Replace with two separate date inputs
        $('#dateRange').replaceWith(`
            <div class="row">
                <div class="col-6">
                    <input type="date" class="form-control" id="startDate" placeholder="Start Date">
                </div>
                <div class="col-6">
                    <input type="date" class="form-control" id="endDate" placeholder="End Date">
                </div>
            </div>
            <button type="button" class="btn btn-info btn-sm mt-2" id="filterBtn">Filter</button>
        `);
        
        // Set default dates
        const today = new Date().toISOString().split('T')[0];
        const firstDay = new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0];
        $('#startDate').val(firstDay);
        $('#endDate').val(today);
        
        // Add filter button handler
        $('#filterBtn').on('click', function() {
            filterInvoicesFallback();
        });
    }

    // Modal button handler
    $('#addReturBtn').on('click', function() {
        $('#addReturModal').modal('show');
    });

    // Back to step 1 button
    $('#backToStep1Btn').on('click', function() {
        backToStep1();
    });

    function filterInvoices() {
        const $dateInput = $('#dateRange');

        // Prefer reading directly from the daterangepicker instance if available
        const drInstance = $dateInput.data('daterangepicker');
        let startDate, endDate;

        if (drInstance) {
            startDate = drInstance.startDate.format('YYYY-MM-DD');
            endDate = drInstance.endDate.format('YYYY-MM-DD');
        } else {
            // Fall back to reading the input value (handles the case where the input value
            // may not be populated by the picker for some reason)
            const dateRange = $dateInput.val();

            if (!dateRange || dateRange.trim() === '') {
                alert('Please select a date or date range');
                return;
            }

            // Support both a range "DD/MM/YYYY - DD/MM/YYYY" and a single date "DD/MM/YYYY"
            if (dateRange.indexOf(' - ') !== -1) {
                const dates = dateRange.split(' - ');
                startDate = moment(dates[0], 'DD/MM/YYYY').format('YYYY-MM-DD');
                endDate = moment(dates[1], 'DD/MM/YYYY').format('YYYY-MM-DD');
            } else {
                // single date selected -> use same day for start and end
                const single = moment(dateRange, 'DD/MM/YYYY').format('YYYY-MM-DD');
                startDate = single;
                endDate = single;
            }
        }

        $.ajax({
            url: "{{ route('finance.retur-pembelian.invoices') }}",
            method: 'GET',
            data: {
                start_date: startDate,
                end_date: endDate
            },
            success: function(data) {
                displayInvoices(data);
            },
            error: function() {
                alert('Error loading invoices');
            }
        });
    }

    function filterInvoicesFallback() {
        const startDate = $('#startDate').val();
        const endDate = $('#endDate').val();
        
        if (!startDate || !endDate) {
            alert('Please select both start and end dates');
            return;
        }

        console.log('Filtering invoices from', startDate, 'to', endDate);

        $.ajax({
            url: "{{ route('finance.retur-pembelian.invoices') }}",
            method: 'GET',
            data: {
                start_date: startDate,
                end_date: endDate
            },
            success: function(data) {
                console.log('Invoices loaded:', data.length);
                displayInvoices(data);
            },
            error: function() {
                alert('Error loading invoices');
            }
        });
    }

    function displayInvoices(invoices) {
        // Cache invoices for client-side searching
        invoicesCache = invoices || [];
        renderInvoiceRows(invoicesCache);
        $('#invoiceList').show();
    }

    function renderInvoiceRows(invoices) {
        const tbody = $('#invoiceTableBody');
        tbody.empty();

        if (!invoices || invoices.length === 0) {
            tbody.append('<tr><td colspan="5" class="text-center">Tidak ada invoice ditemukan</td></tr>');
            return;
        }

        invoices.forEach(function(invoice) {
            const patientName = invoice.visitation && invoice.visitation.pasien ? invoice.visitation.pasien.nama : '-';
            const row = `
                <tr>
                    <td>
                        <input type="radio" name="selected_invoice" value="${invoice.id}" 
                               data-invoice-number="${invoice.invoice_number}" class="invoice-radio">
                    </td>
                    <td>${invoice.invoice_number}</td>
                    <td>${new Date(invoice.created_at).toLocaleDateString('id-ID')}</td>
                    <td>${patientName}</td>
                    <td>Rp ${parseInt(invoice.total_amount).toLocaleString('id-ID')}</td>
                </tr>
            `;
            tbody.append(row);
        });
    }

    // Handle invoice selection
    $(document).on('change', '.invoice-radio', function() {
        const invoiceId = $(this).val();
        const invoiceNumber = $(this).data('invoice-number');
        selectInvoice(invoiceId, invoiceNumber);
    });

    function selectInvoice(invoiceId, invoiceNumber) {
        selectedInvoice = {
            id: invoiceId,
            number: invoiceNumber
        };

        // Load invoice items
        $.ajax({
            url: `/finance/retur-pembelian/invoice/${invoiceId}/items`,
            method: 'GET',
            success: function(data) {
                displayInvoiceItems(data);
                showStep2();
            },
            error: function() {
                alert('Error loading invoice items');
            }
        });
    }

    // Search input handler - filter cached invoices by invoice number, patient name, or id
    $(document).on('input', '#invoiceSearch', function() {
        const q = $(this).val().trim().toLowerCase();

        if (!q) {
            renderInvoiceRows(invoicesCache);
            return;
        }

        const filtered = invoicesCache.filter(function(inv) {
            const patientName = inv.visitation && inv.visitation.pasien ? inv.visitation.pasien.nama : '';
            const invoiceNumber = inv.invoice_number ? String(inv.invoice_number) : '';
            const id = inv.id ? String(inv.id) : '';

            return patientName.toLowerCase().includes(q) || invoiceNumber.toLowerCase().includes(q) || id.includes(q);
        });

        renderInvoiceRows(filtered);
    });

    function displayInvoiceItems(data) {
        const invoice = data.invoice;
        const items = data.items;
        
        // Store items globally for price calculations
        selectedItems = items;

        $('#selectedInvoiceInfo').html(`
            <strong>Invoice:</strong> ${invoice.invoice_number} | 
            <strong>Tanggal:</strong> ${new Date(invoice.created_at).toLocaleDateString('id-ID')} |
            <strong>Total:</strong> Rp ${parseInt(invoice.total_amount).toLocaleString('id-ID')}
        `);

        const itemsContainer = $('#itemsList');
        itemsContainer.empty();

        if (items.length === 0) {
            itemsContainer.append('<div class="alert alert-warning">Tidak ada item yang dapat diretur</div>');
            return;
        }

        items.forEach(function(item) {
            if (item.can_return) {
                const itemHtml = `
                    <div class="invoice-item border rounded p-3 mb-2" data-item-id="${item.id}" style="background-color: #f8f9fa;">
                        <div class="row align-items-center">
                            <div class="col-md-1">
                                <input type="checkbox" class="form-check-input item-checkbox" 
                                       value="${item.id}" data-item-id="${item.id}">
                            </div>
                            <div class="col-md-4">
                                <strong>${item.name}</strong><br>
                                <small class="text-muted">
                                    Original: ${item.original_quantity} | 
                                    Returned: ${item.returned_quantity} | 
                                    Available: ${item.remaining_quantity}
                                </small>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Jumlah Retur</label>
                                <input type="number" class="form-control quantity-input" 
                                       id="qty_${item.id}" step="0.01" min="0.01" 
                                       max="${item.remaining_quantity}" disabled style="width: 100px;">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Unit Price</label>
                                <div>
                                    <small class="text-muted">Original:</small> Rp ${parseInt(item.unit_price).toLocaleString('id-ID')}<br>
                                    <small class="text-muted">After cut:</small> <span id="reduced_price_${item.id}">Rp ${parseInt(item.unit_price).toLocaleString('id-ID')}</span>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Subtotal</label>
                                <div id="subtotal_${item.id}">Rp 0</div>
                            </div>
                        </div>
                    </div>
                `;
                itemsContainer.append(itemHtml);
            }
        });
    }

    // Handle item checkbox toggle
    $(document).on('change', '.item-checkbox', function() {
        const itemId = $(this).data('item-id');
        toggleItem(this, itemId);
    });

    function toggleItem(checkbox, itemId) {
        const qtyInput = $(`#qty_${itemId}`);
        const itemDiv = $(checkbox).closest('.invoice-item');

        if (checkbox.checked) {
            qtyInput.prop('disabled', false).focus();
            itemDiv.addClass('border-primary').css('background-color', '#e7f1ff');
            qtyInput.on('input', function() {
                updateSubtotal(itemId);
            });
        } else {
            qtyInput.prop('disabled', true).val('');
            itemDiv.removeClass('border-primary').css('background-color', '#f8f9fa');
            updateSubtotal(itemId);
        }
    }

    function updateSubtotal(itemId) {
        const qty = parseFloat($(`#qty_${itemId}`).val()) || 0;
        const percentageCut = parseFloat($('#percentage_cut').val()) || 0;
        
        // Get original price from the item data
        const itemData = selectedItems.find(item => item.id == itemId);
        const originalPrice = itemData ? itemData.unit_price : 0;
        const reducedPrice = originalPrice * (1 - (percentageCut / 100));
        
        const subtotal = qty * reducedPrice;
        $(`#subtotal_${itemId}`).text(`Rp ${subtotal.toLocaleString('id-ID')}`);
    }

    function updateAllReducedPrices() {
        const percentageCut = parseFloat($('#percentage_cut').val()) || 0;
        
        selectedItems.forEach(function(item) {
            const originalPrice = item.unit_price;
            const reducedPrice = originalPrice * (1 - (percentageCut / 100));
            
            // Update the reduced price display
            $(`#reduced_price_${item.id}`).text(`Rp ${parseInt(reducedPrice).toLocaleString('id-ID')}`);
            
            // Update subtotal if item is checked and has quantity
            const checkbox = $(`.item-checkbox[data-item-id="${item.id}"]`);
            if (checkbox.is(':checked')) {
                updateSubtotal(item.id);
            }
        });
    }

    // Event handler for percentage cut change
    $('#percentage_cut').on('input', function() {
        updateAllReducedPrices();
    });

    function showStep2() {
        $('#step1').hide();
        $('#step2').show();
        $('#submitBtn').show();
    }

    function backToStep1() {
        $('#step2').hide();
        $('#step1').show();
        $('#submitBtn').hide();
        selectedInvoice = null;
        selectedItems = [];
    }

    $('#addReturForm').on('submit', function(e) {
        e.preventDefault();

        // Collect selected items
        const items = [];
        $('.item-checkbox:checked').each(function() {
            const itemId = $(this).val();
            const quantity = parseFloat($(`#qty_${itemId}`).val());
            
            if (quantity > 0) {
                items.push({
                    invoice_item_id: itemId,
                    quantity_returned: quantity
                });
            }
        });

        if (items.length === 0) {
            alert('Silakan pilih minimal satu item untuk diretur');
            return;
        }

        const formData = {
            invoice_id: selectedInvoice.id,
            reason: $('#reason').val(),
            notes: $('#notes').val(),
            percentage_cut: $('#percentage_cut').val(),
            items: items,
            _token: $('meta[name="csrf-token"]').attr('content')
        };

        $.ajax({
            url: "{{ route('finance.retur-pembelian.store') }}",
            method: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    alert('Retur pembelian berhasil disimpan dengan nomor: ' + response.retur_number);
                    $('#addReturModal').modal('hide');
                    $('#returTable').DataTable().ajax.reload();
                    resetForm();
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON;
                    alert('Error: ' + errors.message);
                } else {
                    alert('Terjadi kesalahan sistem');
                }
            }
        });
    });

    function resetForm() {
        $('#addReturForm')[0].reset();
        $('#step2').hide();
        $('#step1').show();
        $('#submitBtn').hide();
        $('#invoiceList').hide();
        selectedInvoice = null;
        selectedItems = [];
    }

    // Reset form when modal is closed
    $('#addReturModal').on('hidden.bs.modal', function() {
        resetForm();
    });

    // Make viewReturDetail available globally
    window.viewReturDetail = function(id) {
        $.ajax({
            url: `/finance/retur-pembelian/${id}`,
            method: 'GET',
            success: function(data) {
                let itemsHtml = '';
                data.items.forEach(function(item) {
                    itemsHtml += `
                        <tr>
                            <td>${item.name}</td>
                            <td>${item.quantity_returned}</td>
                            <td>
                                <small class="text-muted">Original:</small> Rp ${parseInt(item.original_unit_price).toLocaleString('id-ID')}<br>
                                <small class="text-muted">Cut ${item.percentage_cut}%:</small> Rp ${parseInt(item.unit_price).toLocaleString('id-ID')}
                            </td>
                            <td>Rp ${parseInt(item.total_amount).toLocaleString('id-ID')}</td>
                        </tr>
                    `;
                });

                const detailHtml = `
                    <div class="row">
                        <div class="col-md-6">
                            <strong>No. Retur:</strong> ${data.retur_number}<br>
                            <strong>No. Invoice:</strong> ${data.invoice.invoice_number}<br>
                            <strong>Tanggal:</strong> ${new Date(data.processed_date).toLocaleString('id-ID')}<br>
                            <strong>User:</strong> ${data.user.name}
                        </div>
                        <div class="col-md-6">
                            <strong>Total Amount:</strong> Rp ${parseInt(data.total_amount).toLocaleString('id-ID')}<br>
                            <strong>Percentage Cut:</strong> ${data.items[0]?.percentage_cut || 0}%<br>
                            <strong>Alasan:</strong> ${data.reason}<br>
                            <strong>Catatan:</strong> ${data.notes || '-'}
                        </div>
                    </div>
                    
                    <hr>
                    
                    <h6>Items Retur:</h6>
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Qty Retur</th>
                                <th>Price (Original / After Cut)</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${itemsHtml}
                        </tbody>
                    </table>
                `;

                $('#detailContent').html(detailHtml);
                $('#detailModal').modal('show');
            },
            error: function() {
                alert('Error loading detail');
            }
        });
    };
});
</script>
@endsection