@extends('layouts.finance.app')
@section('title', 'Finance | Billing')
@section('navbar')
    @include('layouts.finance.navbar')
@endsection
@section('content')

@include('finance.partials.modal-billing-edititem')

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <div class="card shadow-sm mt-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-user-circle mr-2"></i>Data Pasien
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td width="120"><strong>Nama</strong></td>
                                    <td>: {{ $visitation->pasien->nama }}</td>
                                </tr>
                                <tr>
                                    <td><strong>ID Pasien</strong></td>
                                    <td>: {{ $visitation->pasien->id }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Alamat</strong></td>
                                    <td>: {{ $visitation->pasien->alamat }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td width="120"><strong>Jenis Kelamin</strong></td>
                                    <td>: {{ $visitation->pasien->gender }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Tanggal Lahir</strong></td>
                                    <td>: {{ $visitation->pasien->tanggal_lahir }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-9">
            <div class="card shadow-sm mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-file-invoice-dollar mr-2"></i>Rincian Billing
                    </h5>
                </div>
                <div class="card-body px-4 py-3">
                    <div class="table-responsive">
                        <table id="billingTable" class="table table-hover">
                            <thead class="thead-light">
                                <tr>
                                    <th style="width: 5%">No.</th>
                                    <th style="width: 20%">Nama Item</th>
                                    <th style="width: 20%">Rincian Item</th>
                                    <th style="width: 10%">Harga</th>
                                    <th style="width: 5%">Qty</th>
                                    <th style="width: 10%">Diskon</th>
                                    <th style="width: 10%">Total</th>
                                    <th style="width: 10%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-calculator mr-2"></i>Total Pembayaran
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-3">
                        <span>Subtotal:</span>
                        <span id="subtotal" class="font-weight-bold">Rp 0</span>
                    </div>
                    
                    <div class="form-group">
                        <label for="global_discount">Diskon Global</label>
                        <div class="input-group">
                            <input type="number" class="form-control" id="global_discount" min="0" value="0">
                            <div class="input-group-append">
                                <select class="form-control" id="global_discount_type">
                                    <option value="%">%</option>
                                    <option value="nominal">Rp</option>
                                </select>
                            </div>
                        </div>
                        <div class="text-right mt-1">
                            <small id="global_discount_amount" class="text-muted">- Rp 0</small>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="tax_percentage">Pajak (%)</label>
                        <input type="number" class="form-control" id="tax_percentage" min="0" value="0">
                        <div class="text-right mt-1">
                            <small id="tax_amount" class="text-muted">+ Rp 0</small>
                        </div>
                    </div>
                    
                    <div class="border-top pt-3 mt-3">
                        <div class="d-flex justify-content-between">
                            <h5>Total:</h5>
                            <h5 id="grand_total" class="text-primary font-weight-bold">Rp 0</h5>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <button id="createInvoiceBtn" class="btn btn-primary btn-block">
                            <i class="fas fa-file-invoice mr-1"></i> Buat Invoice
                        </button>
                        <button id="saveAllChangesBtn" class="btn btn-outline-secondary btn-block mt-2">
                            <i class="fas fa-save mr-1"></i> Simpan Billing
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        $('.select2').select2({ width: '100%' });
        // Store all billing data (with changes) here
        let billingData = [];
        let deletedItems = [];
        
        // Initialize DataTable
        const table = $('#billingTable').DataTable({
            processing: true,
            serverSide: true,
            responsive: false, // Turn off responsive to avoid column collapsing
            scrollX: false,    // Disable horizontal scrolling
            autoWidth: false,  // Don't automatically calculate column widths
            paging: true,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Semua"]],
            stripeClasses: ['odd', 'even'], // Add zebra-striping
            ajax: {
                url: "{{ route('finance.billing.create', $visitation->id) }}",
                type: "GET",
                dataSrc: function(json) {
                    // Store the initial data
                    if (!billingData.length) {
                        // Process each item to ensure it has proper raw values
                        json.data = json.data.map(function(item) {
                            // Extract raw numeric values from formatted strings
                            if (!item.harga_akhir_raw) {
                                // Remove currency symbol and dot separators, then parse
                                const hargaString = item.harga_akhir.replace(/Rp\s?/g, '').replace(/\./g, '');
                                item.harga_akhir_raw = parseInt(hargaString) || 0;
                                
                                // Also ensure other raw values are set
                                if (!item.jumlah_raw) {
                                    const jumlahString = item.jumlah.replace(/Rp\s?/g, '').replace(/\./g, '');
                                    item.jumlah_raw = parseInt(jumlahString) || 0;
                                }
                            }
                            return item;
                        });
                        
                        billingData = json.data;
                    } else {
                        // Merge new data with existing data that has deletion flags
                        json.data = json.data.map(function(item) {
                            // Find matching item in our existing data
                            const existingItem = billingData.find(i => i.id === item.id);
                            
                            // If it exists and is marked as deleted, keep deleted flag
                            if (existingItem && existingItem.deleted) {
                                item.deleted = true;
                            }
                            
                            // If it exists and has edited values, keep those values
                            if (existingItem && existingItem.edited) {
                                item.jumlah_raw = existingItem.jumlah_raw;
                                item.diskon_raw = existingItem.diskon_raw;
                                item.diskon_type = existingItem.diskon_type;
                                item.harga_akhir_raw = existingItem.harga_akhir_raw;
                                item.jumlah = existingItem.jumlah;
                                item.diskon = existingItem.diskon;
                                item.harga_akhir = existingItem.harga_akhir;
                                item.edited = true;
                            } else if (!item.harga_akhir_raw) {
                                // Extract raw numeric values for new or unchanged items
                                const hargaString = item.harga_akhir.replace(/Rp\s?/g, '').replace(/\./g, '');
                                item.harga_akhir_raw = parseInt(hargaString) || 0;
                                
                                if (!item.jumlah_raw) {
                                    const jumlahString = item.jumlah.replace(/Rp\s?/g, '').replace(/\./g, '');
                                    item.jumlah_raw = parseInt(jumlahString) || 0;
                                }
                            }
                            
                            return item;
                        });
                        
                        // Update our billingData 
                        billingData = json.data;
                    }
                    
                    // Filter out deleted items from display
                    const visibleData = json.data.filter(item => !item.deleted);
                    
                    // Calculate totals after data is loaded
                    calculateTotals();
                    
                    return visibleData;
                }
            },
            columns: [
                { 
                    data: null, 
                    orderable: false,
                    searchable: false,
                    width: "5%",
                    render: function (data, type, row, meta) {
                        return meta.row + meta.settings._iDisplayStart + 1;
                    }
                },
                { data: 'nama_item', name: 'nama_item', width: "20%" },
                { data: 'deskripsi', name: 'deskripsi', width: "20%" },
                { data: 'jumlah', name: 'jumlah', width: "10%" },
                { data: 'qty', name: 'qty', width: "5%" },
                { data: 'diskon', name: 'diskon', width: "10%" },
                { data: 'harga_akhir', name: 'harga_akhir', width: "10%" },
                { 
                    data: null,
                    width: "10%",
                    className: 'text-center',
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row, meta) {
                        return `
                            <div class="d-flex justify-content-center">
                                <button class="btn btn-sm btn-outline-primary mr-1 edit-btn" 
                                    data-id="${row.id}" 
                                    data-row-index="${meta.row}"
                                    data-jumlah="${row.is_racikan ? row.racikan_total_price : row.jumlah_raw}" 
                                    data-diskon="${row.diskon_raw || ''}" 
                                    data-diskon_type="${row.diskon_type || ''}">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger delete-btn"
                                    data-id="${row.id}"
                                    data-row-index="${meta.row}">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        `;
                    }
                }
            ],
            columnDefs: [
                { width: "5%", targets: 0 },
                { width: "20%", targets: 1 },
                { width: "20%", targets: 2 },
                { width: "10%", targets: 3, className: 'text-right' }, // Right-align price column
                { width: "5%", targets: 4 },
                { width: "10%", targets: 5, className: 'text-right' }, // Right-align discount column
                { width: "10%", targets: 6, className: 'text-right' }, // Right-align total column
                { width: "10%", targets: 7 }
            ],
            language: {
                emptyTable: "Tidak ada item billing untuk kunjungan ini",
                info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ item",
                search: "Cari:",
                paginate: {
                    first: "Pertama",
                    last: "Terakhir",
                    next: "Selanjutnya",
                    previous: "Sebelumnya"
                },
                lengthMenu: "Tampilkan _MENU_ item"
            },
            drawCallback: function() {
                // Force table width to match parent container
                $(this).css('width', '100%');
                calculateTotals();
            }
        });
        
        // Fix for action column - ensure the table fits its container
        $(window).resize(function() {
            table.columns.adjust();
        });
        
        // Fix: Directly attach event handlers using document delegation
        $(document).on('click', '.edit-btn', function() {
            const id = $(this).data('id');
            const rowIndex = $(this).data('row-index');
            const jumlah = $(this).data('jumlah');
            const diskon = $(this).data('diskon');
            const diskon_type = $(this).data('diskon_type');
            
            $('#edit_id').val(id);
            $('#edit_row_index').val(rowIndex);
            $('#jumlah').val(jumlah);
            $('#diskon').val(diskon);
            $('#diskon_type').val(diskon_type);
            
            $('#editModal').modal('show');
        });
        
        // Fix: Use document delegation for delete button
        $(document).on('click', '.delete-btn', function() {
            const id = $(this).data('id');
            const rowIndex = $(this).data('row-index');
            
            if (confirm('Apakah Anda yakin ingin menghapus item ini?')) {
                try {
                    console.log('Deleting row with index:', rowIndex, 'ID:', id);
                    
                    billingData[rowIndex].deleted = true;
                    deletedItems.push(id);
                    
                    const tr = $(this).closest('tr');
                    table.row(tr).remove().draw(false);
                    
                    calculateTotals();
                    
                    console.log('Item deleted successfully');
                } catch(e) {
                    console.error('Error deleting row:', e);
                    alert('Terjadi kesalahan saat menghapus item: ' + e.message);
                }
            }
        });
        
        // Save changes button in modal
        $('#saveChangesBtn').on('click', function(e) {
            e.preventDefault();
            
            const id = $('#edit_id').val();
            const rowIndex = $('#edit_row_index').val();
            const jumlah = parseFloat($('#jumlah').val());
            const diskon = $('#diskon').val() ? parseFloat($('#diskon').val()) : 0;
            const diskon_type = $('#diskon_type').val();
            
            // Update local data
            if (billingData[rowIndex]) {
                // Store raw values for further editing
                billingData[rowIndex].jumlah_raw = jumlah;
                billingData[rowIndex].diskon_raw = diskon;
                billingData[rowIndex].diskon_type = diskon_type;
                
                // Format for display
                billingData[rowIndex].jumlah = 'Rp ' + formatCurrency(jumlah);
                
                if (diskon && diskon > 0) {
                    if (diskon_type === '%') {
                        billingData[rowIndex].diskon = diskon + '%';
                    } else {
                        billingData[rowIndex].diskon = 'Rp ' + formatCurrency(diskon);
                    }
                } else {
                    billingData[rowIndex].diskon = '-';
                }
                
                // Calculate final price after discount
                let finalJumlah = jumlah;
                if (diskon && diskon > 0) {
                    if (diskon_type === '%') {
                        finalJumlah = jumlah - (jumlah * (diskon / 100));
                    } else {
                        finalJumlah = jumlah - diskon;
                    }
                }
                
                // Update harga_akhir - multiply by quantity
                const qty = billingData[rowIndex].qty || 1;
                billingData[rowIndex].harga_akhir_raw = finalJumlah * qty;
                billingData[rowIndex].harga_akhir = 'Rp ' + formatCurrency(finalJumlah * qty);
                
                // Mark as edited
                billingData[rowIndex].edited = true;
            }
            
            $('#editModal').modal('hide');
            updateTable();
            calculateTotals();
        });
        
        // Function to update the table without a full reload
        function updateTable() {
            const rowIndex = $('#edit_row_index').val();
            if (!billingData[rowIndex]) return;
            
            // IMPORTANT: Temporarily disable Ajax source & server-side processing
            const settings = table.settings()[0];
            const previousServerSide = settings.oFeatures.bServerSide;
            const previousAjax = settings.ajax;
            
            // Turn off server-side features
            settings.oFeatures.bServerSide = false;
            settings.ajax = null;
            
            // Get current table data as array
            const currentData = table.data().toArray();
            
            // Update the specific row data
            const updatedItem = billingData[rowIndex];
            currentData[rowIndex].jumlah = updatedItem.jumlah;
            currentData[rowIndex].jumlah_raw = updatedItem.jumlah_raw;
            currentData[rowIndex].diskon = updatedItem.diskon;
            currentData[rowIndex].diskon_raw = updatedItem.diskon_raw;
            currentData[rowIndex].diskon_type = updatedItem.diskon_type;
            currentData[rowIndex].harga_akhir = updatedItem.harga_akhir;
            currentData[rowIndex].harga_akhir_raw = updatedItem.harga_akhir_raw;
            
            // Clear and reload the entire table with our modified data
            table.clear().rows.add(currentData).draw();
            
            // Restore server-side settings AFTER drawing is complete
            setTimeout(function() {
                settings.oFeatures.bServerSide = previousServerSide;
                settings.ajax = previousAjax;
                table.columns.adjust(); // Readjust columns after update
            }, 100);
        }
        
        // Helper function for formatting currency (removes unnecessary 0s)
        function formatCurrency(value) {
            // Round to 2 decimal places
            let rounded = Math.round(value * 100) / 100;
            // If it's a whole number, don't show decimals
            if (rounded === Math.floor(rounded)) {
                return rounded.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
            }
            // Otherwise format with up to 2 decimals
            return rounded.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        }
        
        // Calculate totals for the bottom section
        function calculateTotals() {
            let subtotal = 0;
            
            // Sum up all harga_akhir_raw values from non-deleted items
            billingData.forEach(function(item) {
                if (!item.deleted) {
                    const value = parseFloat(item.harga_akhir_raw || 0);
                    subtotal += value;
                }
            });
            
            // Display subtotal
            $('#subtotal').text('Rp ' + formatCurrency(subtotal));
            
            // Calculate global discount
            const globalDiscount = parseFloat($('#global_discount').val() || 0);
            const globalDiscountType = $('#global_discount_type').val();
            let discountAmount = 0;
            
            if (globalDiscount > 0) {
                if (globalDiscountType === '%') {
                    discountAmount = subtotal * (globalDiscount / 100);
                } else {
                    discountAmount = globalDiscount;
                }
            }
            
            // Display discount amount
            $('#global_discount_amount').text('- Rp ' + formatCurrency(discountAmount));
            
            // Calculate tax
            const taxPercentage = parseFloat($('#tax_percentage').val() || 0);
            const afterDiscount = subtotal - discountAmount;
            const taxAmount = afterDiscount * (taxPercentage / 100);
            
            // Display tax amount
            $('#tax_amount').text('+ Rp ' + formatCurrency(taxAmount));
            
            // Calculate and display grand total
            const grandTotal = afterDiscount + taxAmount;
            $('#grand_total').text('Rp ' + formatCurrency(grandTotal));
            
            // Store these values for later use when saving/creating invoice
            window.billingTotals = {
                subtotal: subtotal,
                discountAmount: discountAmount,
                discountType: globalDiscountType,
                discountValue: globalDiscount,
                taxPercentage: taxPercentage,
                taxAmount: taxAmount,
                grandTotal: grandTotal
            };
        }
        
        // Event listeners for total calculation inputs
        $('#global_discount, #global_discount_type, #tax_percentage').on('change input', function() {
            calculateTotals();
        });
        
        // Save all changes button
$('#saveAllChangesBtn').on('click', function() {
    if (confirm('Simpan semua perubahan billing?')) {
        // Force the visitation ID to be treated as a string
        const correctVisitationId = "{{ $visitation->id }}";
        
        console.log('Visitation ID being sent:', correctVisitationId);
        console.log('Type of visitation ID:', typeof correctVisitationId);
        
        $.ajax({
            url: "{{ route('finance.billing.save') }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                visitation_id: correctVisitationId, // This will now be sent as a string
                edited_items: billingData.filter(item => item.edited),
                deleted_items: deletedItems,
                totals: window.billingTotals
            },
            success: function(response) {
                alert('Data billing berhasil disimpan');
            },
            error: function(xhr) {
                alert('Terjadi kesalahan: ' + xhr.responseText);
                console.error('Error details:', xhr.responseText);
            }
        });
    }
});
        
        // Create invoice button
        $('#createInvoiceBtn').on('click', function() {
    if (confirm('Buat invoice dari billing ini?')) {
        const items = billingData.filter(item => !item.deleted);
        
        if (items.length === 0) {
            alert('Tidak ada item billing yang valid!');
            return;
        }
        
        // Force the visitation ID to be treated as a string, just like in saveAllChangesBtn
        const correctVisitationId = "{{ $visitation->id }}";
        
        $.ajax({
            url: "{{ route('finance.billing.createInvoice') }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                visitation_id: correctVisitationId,
                items: items,
                totals: window.billingTotals
            },
            success: function(response) {
                alert('Invoice berhasil dibuat dengan nomor: ' + response.invoice_number);
            },
            error: function(xhr) {
                console.log('Error response:', xhr.responseText);
                try {
                    const errorObj = JSON.parse(xhr.responseText);
                    alert('Terjadi kesalahan: ' + (errorObj.message || xhr.responseText));
                } catch (e) {
                    alert('Terjadi kesalahan dalam pembuatan invoice');
                }
            }
        });
    }
});
        
        // Initial calculation of totals
        calculateTotals();
        
        // Force column widths to be applied immediately after init
        setTimeout(function() {
            table.columns.adjust();
        }, 100);
    });
</script>
@endsection