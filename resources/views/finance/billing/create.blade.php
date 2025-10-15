@extends('layouts.finance.app')
@section('title', 'Finance | Billing')
@section('navbar')
    @include('layouts.finance.navbar')
@endsection
@section('content')

@include('finance.partials.modal-billing-edititem')

<div class="container-fluid">
    <!-- Prefill billing fields with old invoice data if available -->
    <script>
        window.oldInvoice = {
            global_discount: @json($invoice->discount_value ?? ''),
            global_discount_type: @json($invoice->discount_type ?? ''),
            tax_percentage: @json($invoice->tax_percentage ?? ''),
            admin_fee: @json($invoice?->items?->first(function($item) { return stripos($item->name ?? '', 'Biaya Administrasi') !== false; })?->unit_price ?? ''),
            shipping_fee: @json($invoice?->items?->where('name', 'Biaya Ongkir')->first()?->unit_price ?? ''),
            amount_paid: @json($invoice->amount_paid ?? ''),
            payment_method: @json($invoice->payment_method ?? ''),
            change_amount: @json($invoice->change_amount ?? '')
        };
        
        // Global variables for gudang data
        window.gudangData = {
            gudangs: [],
            mappings: {},
            loaded: false
        };
        
        // Load gudang data on page load
        function loadGudangData() {
            return $.ajax({
                url: '{{ route('finance.billing.gudang-data') }}',
                type: 'GET',
                success: function(response) {
                    window.gudangData.gudangs = response.gudangs || [];
                    window.gudangData.mappings = response.mappings || {};
                    window.gudangData.loaded = true;
                },
                error: function(xhr, status, error) {
                    console.error('Failed to load gudang data:', error);
                }
            });
        }
    </script>
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

    <div class="row mb-4">
        <div class="col">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-2">
                            <label for="select-tindakan">Tambah Tindakan</label>
                            <select id="select-tindakan" class="form-control select2"></select>
                        </div>
                        <div class="col-md-3 mb-2">
                            <label for="select-lab">Tambah Lab</label>
                            <select id="select-lab" class="form-control select2"></select>
                        </div>
                        <div class="col-md-3 mb-2">
                            <label for="select-konsultasi">Tambah Biaya Konsultasi</label>
                            <select id="select-konsultasi" class="form-control select2"></select>
                        </div>
                        <div class="col-md-3 mb-2">
                            <label for="select-obat">Tambah Produk/Obat</label>
                            <select id="select-obat" class="form-control select2"></select>
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
                                    <th style="width: 18%">Nama Item</th>
                                    <th style="width: 18%">Rincian Item</th>
                                    <th style="width: 8%">Harga</th>
                                    <th style="width: 5%">Qty</th>
                                    <th style="width: 12%">Gudang</th>
                                    <th style="width: 8%">Diskon</th>
                                    <th style="width: 8%">Total</th>
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
                    
                    <div class="form-group row mb-2">
                        <div class="col-6">
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
                        <div class="col-6">
                            <label for="tax_percentage">Pajak (%)</label>
                            <input type="number" class="form-control" id="tax_percentage" min="0" value="0">
                            <div class="text-right mt-1">
                                <small id="tax_amount" class="text-muted">+ Rp 0</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group row mb-2">
                        <div class="col-6">
                            <label for="admin_fee">Biaya Administrasi</label>
                            <select class="form-control" id="admin_fee">
                                <option value="0">Rp 0</option>
                                <option value="10000">Rp 10.000</option>
                                <option value="15000">Rp 15.000</option>
                                <option value="20000">Rp 20.000</option>
                                <option value="25000">Rp 25.000</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label for="shipping_fee">Biaya Ongkir</label>
                            <input type="number" class="form-control" id="shipping_fee" min="0" value="0">
                        </div>
                    </div>
                    
                    <div class="border-top pt-3 mt-3">
                        <div class="d-flex justify-content-between">
                            <h5>Total:</h5>
                            <h5 id="grand_total" class="text-primary font-weight-bold">Rp 0</h5>
                        </div>
                    </div>
                    
                    <!-- Payment Section -->
                    <div class="border-top pt-3 mt-3">
                        <h6 class="mb-3">Pembayaran</h6>
                        <div class="form-group row mb-2">
                            <div class="col-6">
                                <label for="amount_paid">Dibayar</label>
                                <input type="text" class="form-control" id="amount_paid" value="0" placeholder="Jumlah uang yang diberikan pasien">
                                <small class="text-muted">Masukkan jumlah uang yang diberikan oleh pasien</small>
                            </div>
                            <div class="col-6">
                                <label for="payment_method">Metode Pembayaran</label>
                                <select class="form-control" id="payment_method">
                                    <option value="cash">Tunai</option>
                                    {{-- <option value="non_cash">Non Tunai</option> --}}
                                    <option value="edc_bca">EDC BCA</option>
                                    <option value="edc_bni">EDC BNI</option>
                                    <option value="qris">QRIS</option>
                                    <option value="transfer">Transfer</option>
                                    <option value="shopee">Shopee</option>
                                    <option value="tiktokshop">Tiktokshop</option>
                                    <option value="tokopedia">Tokopedia</option>
                                </select>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Kembali:</span>
                            <span id="change_amount" class="font-weight-bold text-success">Rp 0</span>
                        </div>
                        <div class="d-flex justify-content-between" style="display:none;" id="shortage_label">
                            <span>Kekurangan:</span>
                            <span id="shortage_amount" class="font-weight-bold text-danger">Rp 0</span>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <button id="createInvoiceBtn" class="btn btn-primary btn-block">
                            <i class="fas fa-file-invoice mr-1"></i> Buat Invoice
                        </button>
                        {{--<button id="saveAllChangesBtn" class="btn btn-outline-secondary btn-block mt-2">
                            <i class="fas fa-save mr-1"></i> Simpan Billing
                        </button>--}}
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
        // Prefill billing fields if old invoice exists
        if (window.oldInvoice) {
            if (window.oldInvoice.global_discount !== '') $('#global_discount').val(window.oldInvoice.global_discount);
            if (window.oldInvoice.global_discount_type !== '') $('#global_discount_type').val(window.oldInvoice.global_discount_type);
            if (window.oldInvoice.tax_percentage !== '') $('#tax_percentage').val(window.oldInvoice.tax_percentage);
            if (window.oldInvoice.admin_fee !== '') {
                let adminFeeValue = window.oldInvoice.admin_fee.toString().replace(/[,\.].*$/, '');
                $('#admin_fee').val(adminFeeValue).trigger('change');
            }
            if (window.oldInvoice.shipping_fee !== '') $('#shipping_fee').val(window.oldInvoice.shipping_fee);
            if (window.oldInvoice.amount_paid !== '') $('#amount_paid').val(window.oldInvoice.amount_paid);
            if (window.oldInvoice.payment_method !== '') $('#payment_method').val(window.oldInvoice.payment_method);
            if (window.oldInvoice.change_amount !== '') $('#change_amount').text('Rp ' + formatCurrency(window.oldInvoice.change_amount));
        }
        $('.select2').select2({ width: '100%' });
        
        // Load gudang data first
        loadGudangData();
        
        // Store all billing data (with changes) here
        let billingData = [];
        let deletedItems = [];
        
        // Helper function to get default gudang for an item
        function getDefaultGudangForItem(item) {
            // Determine transaction type based on item
            let transactionType = 'tindakan'; // default
            
            // Check if this is an obat/resep item
            if (item.billable_type === 'App\\Models\\ERM\\ResepFarmasi' || 
                item.billable_type === 'App\\Models\\ERM\\Racikan' ||
                (item.deskripsi && item.deskripsi.toLowerCase().includes('obat')) ||
                (item.nama_item && item.nama_item.toLowerCase().includes('obat'))) {
                transactionType = 'resep';
            }
            // Check if this is a RiwayatTindakan with kode tindakan obat
            else if (item.billable_type === 'App\\Models\\ERM\\RiwayatTindakan') {
                // For riwayat tindakan, use kode_tindakan transaction type for obat stock
                transactionType = 'kode_tindakan';
            }
            // Check if this is a bundled obat from tindakan
            else if (item.billable_type === 'App\\Models\\ERM\\Obat' && 
                     item.keterangan && item.keterangan.includes('Obat Bundled:')) {
                transactionType = 'tindakan';
            }
            
            return window.gudangData.mappings[transactionType] || 
                   (window.gudangData.gudangs.length ? window.gudangData.gudangs[0].id : null);
        }
        
        // Function to collect gudang selections for invoice creation
        function collectGudangSelections() {
            const selections = {};
            
            $('.gudang-selector').each(function() {
                const itemId = $(this).data('item-id');
                const gudangId = $(this).val();
                if (itemId && gudangId) {
                    selections[itemId] = gudangId;
                }
            });
            
            return selections;
        }
        
        // Initialize DataTable
        const table = $('#billingTable').DataTable({
            processing: true,
            serverSide: true,
            responsive: false, // Turn off responsive to avoid column collapsing
            scrollX: false,    // Disable horizontal scrolling
            autoWidth: false,  // Don't automatically calculate column widths
            paging: true,
            // Default to show all items on first load ("Semua")
            pageLength: -1,
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
                { data: 'nama_item', name: 'nama_item', width: "18%" },
                { data: 'deskripsi', name: 'deskripsi', width: "18%" },
                { data: 'jumlah', name: 'jumlah', width: "8%" },
                { data: 'qty', name: 'qty', width: "5%" },
                { 
                    data: null,
                    orderable: false,
                    searchable: false,
                    width: "12%",
                    render: function(data, type, row, meta) {
                        // Only show gudang dropdown for obat items (not tindakan)
                        const isObatItem = row.billable_type === 'App\\Models\\ERM\\ResepFarmasi' || 
                                         row.billable_type === 'App\\Models\\ERM\\Racikan' ||
                                         (row.deskripsi && row.deskripsi.toLowerCase().includes('obat')) ||
                                         (row.nama_item && row.nama_item.toLowerCase().includes('obat'));
                        
                        if (!isObatItem) {
                            return '<span class="text-muted">-</span>';
                        }
                        
                        if (!window.gudangData.loaded || !window.gudangData.gudangs.length) {
                            return '<span class="text-muted">Loading...</span>';
                        }
                        
                        // Get default gudang for this item type
                        const defaultGudangId = getDefaultGudangForItem(row);
                        const selectedGudangId = row.selected_gudang_id || defaultGudangId;
                        
                        let selectHtml = `<select class="form-control form-control-sm gudang-selector" 
                                               data-row-index="${meta.row}" 
                                               data-item-id="${row.id}">`;
                        
                        window.gudangData.gudangs.forEach(function(gudang) {
                            const selected = gudang.id == selectedGudangId ? 'selected' : '';
                            selectHtml += `<option value="${gudang.id}" ${selected}>${gudang.nama}</option>`;
                        });
                        
                        selectHtml += '</select>';
                        return selectHtml;
                    }
                },
                { data: 'diskon', name: 'diskon', width: "8%" },
                { data: 'harga_akhir', name: 'harga_akhir', width: "8%",
                  render: function(data, type, row) {
                      // Always calculate as harga (unit price) * qty
                      // Use jumlah_raw as the true unit price, and qty
                      const harga = (typeof row.jumlah_raw !== 'undefined' && !isNaN(row.jumlah_raw)) ? Number(row.jumlah_raw) : 0;
                      const qty = row.qty ? Number(row.qty) : 1;
                      // If diskon applies, calculate finalJumlah
                      let finalJumlah = harga;
                      if (row.diskon_raw && row.diskon_raw > 0) {
                          if (row.diskon_type === '%') {
                              finalJumlah = harga - (harga * (row.diskon_raw / 100));
                          } else {
                              finalJumlah = harga - row.diskon_raw;
                          }
                      }
                      const total = finalJumlah * qty;
                      return 'Rp ' + formatCurrency(total);
                  }
                },
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
                                    data-diskon_type="${row.diskon_type || ''}"
                                    data-qty="${row.qty || 1}">
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
                { width: "18%", targets: 1 },
                { width: "18%", targets: 2 },
                { width: "8%", targets: 3, className: 'text-right' }, // Right-align price column
                { width: "5%", targets: 4 },
                { width: "12%", targets: 5 }, // Gudang column
                { width: "8%", targets: 6, className: 'text-right' }, // Right-align discount column
                { width: "8%", targets: 7, className: 'text-right' }, // Right-align total column
                { width: "10%", targets: 8 }
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
        
        // Event handler for gudang selector changes
        $(document).on('change', '.gudang-selector', function() {
            const itemId = $(this).data('item-id');
            const selectedGudangId = $(this).val();
            const rowIndex = $(this).data('row-index');
            
            // Update the billingData with selected gudang
            if (billingData[rowIndex]) {
                billingData[rowIndex].selected_gudang_id = selectedGudangId;
            }
            
            console.log('Gudang selection updated for item', itemId, 'to gudang', selectedGudangId);
        });
        
        // Refresh table when gudang data is loaded
        function refreshTableAfterGudangLoad() {
            if (window.gudangData.loaded) {
                table.ajax.reload(null, false); // Don't reset paging
            }
        }
        
        // Check if gudang data is loaded, if not wait for it
        if (window.gudangData.loaded) {
            refreshTableAfterGudangLoad();
        } else {
            // Poll until loaded
            const checkInterval = setInterval(function() {
                if (window.gudangData.loaded) {
                    clearInterval(checkInterval);
                    refreshTableAfterGudangLoad();
                }
            }, 100);
        }
        
        // Fix: Directly attach event handlers using document delegation
        $(document).on('click', '.edit-btn', function() {
            const id = $(this).data('id');
            const rowIndex = $(this).data('row-index');
            const jumlah = $(this).data('jumlah');
            const diskon = $(this).data('diskon');
            const diskon_type = $(this).data('diskon_type');
            const qty = $(this).data('qty');

            $('#edit_id').val(id);
            $('#edit_row_index').val(rowIndex);
            $('#jumlah').val(jumlah);
            $('#diskon').val(diskon);
            $('#diskon_type').val(diskon_type);
            $('#edit_qty').val(qty);

            // Debug: Show harga before edit
            // console.log('[DEBUG] Harga before edit:', jumlah);
            $('#editModal').modal('show');
        });
        
        // Fix: Use document delegation for delete button
        $(document).on('click', '.delete-btn', function() {
            const id = $(this).data('id');
            Swal.fire({
                title: 'Konfirmasi Hapus',
                text: 'Apakah Anda yakin ingin menghapus item ini?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.value) {
                    try {
                        // console.log('Deleting item with id:', id);
                        const idx = billingData.findIndex(item => item.id == id);
                        if (idx !== -1) {
                            billingData[idx].deleted = true;
                            // Only push numeric IDs (real DB items)
                            if (!isNaN(Number(billingData[idx].id))) {
                                deletedItems.push(billingData[idx].id);
                            }
                            updateTable();
                            calculateTotals();
                            // console.log('Item deleted successfully');
                            Swal.fire({
                                title: 'Berhasil!',
                                text: 'Item berhasil dihapus.',
                                icon: 'success',
                                timer: 1500,
                                showConfirmButton: false
                            });
                        }
                    } catch(e) {
                        console.error('Error deleting row:', e);
                        Swal.fire({
                            title: 'Error!',
                            text: 'Terjadi kesalahan saat menghapus item: ' + e.message,
                            icon: 'error'
                        });
                    }
                }
            });
        });
        
        // Save changes button in modal
        $('#saveChangesBtn').on('click', function(e) {
            e.preventDefault();
            const id = $('#edit_id').val();
            const jumlah = parseFloat($('#jumlah').val());
            const diskon = $('#diskon').val() ? parseFloat($('#diskon').val()) : 0;
            const diskon_type = $('#diskon_type').val();
            const qty = parseInt($('#edit_qty').val()) || 1;
            // Update local data using id
            const idx = billingData.findIndex(item => item.id == id);
            if (idx !== -1) {
                billingData[idx].jumlah_raw = jumlah;
                billingData[idx].diskon_raw = diskon;
                billingData[idx].diskon_type = diskon_type;
                billingData[idx].qty = qty;
                billingData[idx].jumlah = 'Rp ' + formatCurrency(jumlah);
                if (diskon && diskon > 0) {
                    if (diskon_type === '%') {
                        billingData[idx].diskon = diskon + '%';
                    } else {
                        billingData[idx].diskon = 'Rp ' + formatCurrency(diskon);
                    }
                } else {
                    billingData[idx].diskon = '-';
                }
                let finalJumlah = jumlah;
                if (diskon && diskon > 0) {
                    if (diskon_type === '%') {
                        finalJumlah = jumlah - (jumlah * (diskon / 100));
                    } else {
                        finalJumlah = jumlah - diskon;
                    }
                }
                // Store only the final unit price, not multiplied by qty
                billingData[idx].harga_akhir_raw = finalJumlah;
                billingData[idx].harga_akhir = 'Rp ' + formatCurrency(finalJumlah * qty);
                billingData[idx].edited = true;
                // Ensure racikan_ke is included for racikan items
                if (billingData[idx].is_racikan && billingData[idx].billable && billingData[idx].billable.racikan_ke) {
                    billingData[idx].racikan_ke = billingData[idx].billable.racikan_ke;
                    // Set racikan_total_price to the edited value
                    billingData[idx].racikan_total_price = jumlah;
                }
            }
            $('#editModal').modal('hide');
            // Debug: Show harga after edit
            // console.log('[DEBUG] Harga after edit:', jumlah);
            updateTable();
            calculateTotals();

            // Debug: Show total for this item in DataTable
            setTimeout(function() {
                const item = billingData.find(item => item.id == id);
                if (item) {
                    const qty = item.qty || 1;
                    const total = item.harga_akhir_raw * qty;
                    // console.log('[DEBUG] Total in DataTable for item', id, ':', total);
                }
            }, 200);
        });
        
        // Function to update the table with all billingData (for add/edit/delete)
        function updateTable() {
    // Temporarily disable Ajax source & server-side processing
    const settings = table.settings()[0];
    const previousServerSide = settings.oFeatures.bServerSide;
    const previousAjax = settings.ajax;
    settings.oFeatures.bServerSide = false;
    settings.ajax = null;

    // Only show non-deleted items
    const currentData = billingData.filter(item => !item.deleted);
    table.clear().rows.add(currentData).draw();

    // Restore server-side settings AFTER drawing is complete
    setTimeout(function() {
        settings.oFeatures.bServerSide = previousServerSide;
        settings.ajax = previousAjax;
        table.columns.adjust();
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
        
        // Helper to parse harga string with comma/dot
        function parseHarga(hargaStr) {
            // console.log('parseHarga input:', hargaStr, 'type:', typeof hargaStr);
            
            if (typeof hargaStr === 'number') {
                // console.log('parseHarga returning number:', hargaStr);
                return hargaStr;
            }
            
            if (typeof hargaStr === 'string') {
                // Handle different formats:
                // "150000.00" -> 150000
                // "150.000,00" -> 150000 (European format)
                // "150,000.00" -> 150000 (US format)
                // "150000" -> 150000
                
                let cleaned = hargaStr.trim();
                
                // Check if it's in format like "150000.00" (decimal with dot, no thousand separators)
                if (/^\d+\.\d{1,2}$/.test(cleaned)) {
                    const result = parseFloat(cleaned);
                    // console.log('parseHarga decimal format:', hargaStr, '->', result);
                    return result;
                }
                
                // Check if it's in format like "150.000,00" (European: dots for thousands, comma for decimal)
                if (/^\d{1,3}(\.\d{3})*,\d{1,2}$/.test(cleaned)) {
                    cleaned = cleaned.replace(/\./g, '').replace(',', '.');
                    const result = parseFloat(cleaned);
                    // console.log('parseHarga European format:', hargaStr, '->', cleaned, '->', result);
                    return result;
                }
                
                // Check if it's in format like "150,000.00" (US: commas for thousands, dot for decimal)
                if (/^\d{1,3}(,\d{3})*\.\d{1,2}$/.test(cleaned)) {
                    cleaned = cleaned.replace(/,/g, '');
                    const result = parseFloat(cleaned);
                    // console.log('parseHarga US format:', hargaStr, '->', cleaned, '->', result);
                    return result;
                }
                
                // Handle plain numbers (no decimal)
                if (/^\d+$/.test(cleaned)) {
                    const result = parseInt(cleaned);
                    // console.log('parseHarga plain number:', hargaStr, '->', result);
                    return result;
                }
                
                // Handle Indonesian format (dots for thousands, no decimal or comma for decimal)
                if (/^\d{1,3}(\.\d{3})*$/.test(cleaned)) {
                    cleaned = cleaned.replace(/\./g, '');
                    const result = parseInt(cleaned);
                    // console.log('parseHarga Indonesian thousands:', hargaStr, '->', cleaned, '->', result);
                    return result;
                }
                
                // Fallback: try to parse as float
                const result = parseFloat(cleaned);
                // console.log('parseHarga fallback:', hargaStr, '->', result);
                return isNaN(result) ? 0 : result;
            }
            
            // console.log('parseHarga returning 0 for:', hargaStr);
            return 0;
        }

        // Add selected Racikan to billingData (example, adapt as needed)
        function addRacikanToBillingData(data) {
            // data.harga should be the unit price, data.qty the quantity, or adapt as needed
            const harga = parseHarga(data.harga);
            const qty = parseInt(data.qty) || 1;
            
            // Always use 'Obat Racikan' as the name for consistency
            const racikanName = 'Obat Racikan';
                
            // Build the description with the list of medications
            let description = '';
            if (data.obat_list && Array.isArray(data.obat_list)) {
                description = data.obat_list.map(obat => `- ${obat}`).join('<br>');
            } else if (data.deskripsi) {
                description = data.deskripsi;
            }
            
            billingData.push({
                id: 'racikan-' + (data.id || Date.now()),
                billable_id: data.id,
                billable_type: 'App\\Models\\ERM\\ResepFarmasi',
                nama_item: racikanName,
                jumlah: 'Rp ' + formatCurrency(harga),
                qty: qty,
                diskon: 0,
                diskon_type: 'nominal',
                harga_akhir: 'Rp ' + formatCurrency(harga * qty),
                harga_akhir_raw: harga,
                deleted: false,
                deskripsi: description,
                is_racikan: true,
                racikan_ke: data.racikan_ke || 0,
                racikan_obat_list: data.obat_list || []
            });
            updateTable();
            calculateTotals();
        }
        
        // Calculate totals for the bottom section
        function calculateTotals() {
            // console.log('Current billingData for totals:', billingData);
            let subtotal = 0;
            // Sum up all harga_akhir_raw * qty values from non-deleted items
            billingData.forEach(function(item) {
                if (!item.deleted && !isNaN(item.harga_akhir_raw) && item.harga_akhir_raw > 0) {
                    const qty = item.qty || 1;
                    const value = parseFloat(item.harga_akhir_raw) * qty;
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
            // Get admin fee and shipping fee
            const adminFee = parseFloat($('#admin_fee').val() || 0);
            const shippingFee = parseFloat($('#shipping_fee').val() || 0);
            // Calculate and display grand total
            const grandTotal = afterDiscount + taxAmount + adminFee + shippingFee;
            // integer ceil versions to align with backend (always round up)
            const grandTotalInt = Math.ceil(grandTotal);
            $('#grand_total').text('Rp ' + formatCurrency(grandTotalInt));

            // Calculate change amount using integer ceil values to match displayed total
            const amountPaid = parseHarga($('#amount_paid').val() || 0);
            const amountPaidInt = Math.ceil(amountPaid);
            const changeAmount = Math.max(0, amountPaidInt - grandTotalInt);
            const shortageAmount = Math.max(0, grandTotalInt - amountPaidInt);
            $('#change_amount').text('Rp ' + formatCurrency(changeAmount));
            // Update shortage display (kekurangan)
            if (shortageAmount > 0) {
                $('#shortage_amount').text('Rp ' + formatCurrency(shortageAmount)).show();
                $('#shortage_label').show();
            } else {
                $('#shortage_amount').hide();
                $('#shortage_label').hide();
            }
            
            // Store these values for later use when saving/creating invoice
            window.billingTotals = {
                subtotal: subtotal,
                discountAmount: discountAmount,
                discountType: globalDiscountType,
                discountValue: globalDiscount,
                taxPercentage: taxPercentage,
                taxAmount: taxAmount,
                adminFee: adminFee,
                shippingFee: shippingFee,
                grandTotal: grandTotal,
                // integer-rounded rupiah values to avoid mismatch when frontend strips decimals
                // use Math.ceil to always round up so payment value will never be under the actual price
                grandTotalInt: grandTotalInt,
                amountPaid: amountPaid,
                amountPaidInt: amountPaidInt,
                changeAmount: changeAmount,
                shortageAmount: shortageAmount,
                paymentMethod: $('#payment_method').val()
            };
        }
        
        // Event listeners for total calculation inputs
        $('#global_discount, #global_discount_type, #tax_percentage, #admin_fee, #shipping_fee, #payment_method').on('change input', function() {
            calculateTotals();
        });

        // Handle amount_paid input separately: parse/ceil on blur and recalc
        $('#amount_paid').on('change input', function() {
            // live updates while typing
            calculateTotals();
        }).on('blur', function() {
            // On blur, normalize: parse, ceil, and format for display
            const raw = $(this).val();
            const parsed = parseHarga(raw);
            const ceilVal = Math.ceil(parsed);
            // formatCurrency returns e.g. '257.000' for integers; append ',00' to match user's style
            $(this).val(formatCurrency(ceilVal) + ',00');
            calculateTotals();
        });
        
        // Save all changes button
$('#saveAllChangesBtn').on('click', function() {
    Swal.fire({
        title: 'Konfirmasi Simpan',
        text: 'Simpan semua perubahan billing?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Simpan!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.value) {
            // Force the visitation ID to be treated as a string
            const correctVisitationId = "{{ $visitation->id }}";
            
            // console.log('=== SAVE BILLING DEBUG START ===');
            // console.log('All billingData:', billingData);
            // console.log('Visitation ID being sent:', correctVisitationId);
            // console.log('Type of visitation ID:', typeof correctVisitationId);
            
            // Categorize items
            const editedItems = billingData.filter(item => item.edited && !item.deleted);
            const newItems = billingData.filter(item => 
                !item.edited && 
                !item.deleted && 
                (item.id.toString().startsWith('tindakan-') || 
                 item.id.toString().startsWith('lab-') || 
                 item.id.toString().startsWith('konsultasi-') ||
                 item.id.toString().startsWith('obat-') ||
                 item.id.toString().startsWith('racikan-'))
            );
            
            // console.log('Edited items:', editedItems);
            // console.log('New items:', newItems);
            // console.log('Deleted items:', deletedItems);
            // console.log('=== SAVE BILLING DEBUG END ===');
            
            const requestData = {
                _token: "{{ csrf_token() }}",
                visitation_id: correctVisitationId,
                edited_items: editedItems,
                new_items: newItems,
                deleted_items: deletedItems,
                totals: window.billingTotals
            };
            
            // console.log('Request data being sent:', requestData);
            
            // Show loading
            Swal.fire({
                title: 'Menyimpan...',
                text: 'Harap tunggu, sedang memproses data billing.',
                icon: 'info',
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            $.ajax({
                url: "{{ route('finance.billing.save') }}",
                type: "POST",
                data: requestData,
                success: function(response) {
                    // console.log('Save response:', response);
                    Swal.fire({
                        title: 'Berhasil!',
                        text: 'Data billing berhasil disimpan',
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        // Refresh the table to get the new IDs from database
                        location.reload();
                    });
                },
                error: function(xhr) {
                    console.error('Save error:', xhr);
                    Swal.fire({
                        title: 'Error!',
                        text: 'Terjadi kesalahan: ' + xhr.responseText,
                        icon: 'error'
                    });
                    console.error('Error details:', xhr.responseText);
                }
            });
        }
    });
});
        
        // Create invoice button (single-Swal flow to avoid stacked alerts)
        $('#createInvoiceBtn').on('click', function() {
            Swal.fire({
                title: 'Konfirmasi',
                text: 'Simpan semua perubahan billing dan buat invoice sekarang?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Simpan & Buat Invoice!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.value) {
                    // Prepare request data
                    const correctVisitationId = "{{ $visitation->id }}";
                    const editedItems = billingData.filter(item => item.edited && !item.deleted);
                    const newItems = billingData.filter(item =>
                        !item.edited &&
                        !item.deleted &&
                        (item.id.toString().startsWith('tindakan-') ||
                         item.id.toString().startsWith('lab-') ||
                         item.id.toString().startsWith('konsultasi-') ||
                         item.id.toString().startsWith('obat-') ||
                         item.id.toString().startsWith('racikan-'))
                    );
                    const requestData = {
                        _token: "{{ csrf_token() }}",
                        visitation_id: correctVisitationId,
                        edited_items: editedItems,
                        new_items: newItems,
                        deleted_items: deletedItems,
                        totals: window.billingTotals
                    };

                    // Open a single loading modal and update it through the process
                    Swal.fire({
                        title: 'Memproses...',
                        text: 'Harap tunggu, sedang menyimpan dan membuat invoice.',
                        icon: 'info',
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        didOpen: () => { Swal.showLoading(); }
                    });

                    // First: save billing
                    $.ajax({
                        url: "{{ route('finance.billing.save') }}",
                        type: "POST",
                        data: requestData,
                        success: function(saveResponse) {
                            // After save, create invoice
                            const items = billingData.filter(item => !item.deleted);
                            if (items.length === 0) {
                                Swal.fire({
                                    title: 'Peringatan!',
                                    text: 'Tidak ada item billing yang valid!',
                                    icon: 'warning'
                                });
                                return;
                            }

                            // Update the same modal to indicate invoice creation
                            try {
                                Swal.update({ title: 'Membuat Invoice...', text: 'Harap tunggu, sedang memproses invoice.' });
                            } catch(e) {
                                // Fallback: if Swal.update isn't available, close and open a new loading modal
                                Swal.close();
                                Swal.fire({
                                    title: 'Membuat Invoice...',
                                    text: 'Harap tunggu, sedang memproses invoice.',
                                    icon: 'info',
                                    allowOutsideClick: false,
                                    showConfirmButton: false,
                                    didOpen: () => { Swal.showLoading(); }
                                });
                            }

                                // Debug: log payload being sent for invoice creation
                                console.debug('Creating invoice payload', {
                                    visitation_id: correctVisitationId,
                                    items: items,
                                    totals: window.billingTotals,
                                    gudang_selections: collectGudangSelections()
                                });

                                $.ajax({
                                url: "{{ route('finance.billing.createInvoice') }}",
                                type: "POST",
                                data: {
                                    _token: "{{ csrf_token() }}",
                                    visitation_id: correctVisitationId,
                                    items: items,
                                    totals: window.billingTotals,
                                    gudang_selections: collectGudangSelections()
                                },
                                success: function(invoiceResponse) {
                                    // The backend returns stock_reduced and stock_message
                                    var stockReduced = invoiceResponse.stock_reduced === true || invoiceResponse.stock_reduced === 1;
                                    var stockMessage = invoiceResponse.stock_message || '';
                                    var icon = stockReduced ? 'success' : 'warning';
                                    var html = 'Invoice berhasil dibuat dengan nomor: <strong>' + (invoiceResponse.invoice_number || invoiceResponse.invoice_number) + '</strong>';
                                    if (stockMessage) {
                                        html += '<br><small style="display:block;margin-top:8px;color:#555;">' + stockMessage + '</small>';
                                    }

                                    Swal.fire({
                                        title: 'Berhasil!',
                                        html: html,
                                        icon: icon,
                                        confirmButtonText: 'OK'
                                    }).then(() => {
                                        window.location.href = "{{ route('finance.billing.index') }}";
                                    });
                                },
                                error: function(xhr) {
                                    Swal.fire({
                                        title: 'Error!',
                                        text: 'Terjadi kesalahan dalam pembuatan invoice: ' + xhr.responseText,
                                        icon: 'error'
                                    });
                                }
                            });
                        },
                        error: function(xhr) {
                            Swal.fire({
                                title: 'Error!',
                                text: 'Terjadi kesalahan saat menyimpan billing: ' + xhr.responseText,
                                icon: 'error'
                            });
                        }
                    });
                }
            });
        });
        
        // --- Select2 AJAX for Tindakan ---
        $('#select-tindakan').select2({
            placeholder: 'Cari tindakan...',
            ajax: {
                url: '/tindakan/search',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return { q: params.term };
                },
                processResults: function(data) {
                    // Support both {results: [...]} and plain array
                    var items = Array.isArray(data) ? data : (data.results || []);
                    return {
                        results: items.map(function(item) {
                            return { id: item.id, text: item.text || item.nama, harga: item.harga };
                        })
                    };
                },
                cache: true
            },
            minimumInputLength: 1
        });
        // --- Select2 AJAX for Lab ---
        $('#select-lab').select2({
            placeholder: 'Cari lab...',
            ajax: {
                url: '/labtest/search',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return { q: params.term };
                },
                processResults: function(data) {
                    return {
                        results: data.map(function(item) {
                            return { id: item.id, text: item.nama, harga: item.harga };
                        })
                    };
                },
                cache: true
            },
            minimumInputLength: 1
        });
        // --- Select2 AJAX for Konsultasi ---
        $('#select-konsultasi').select2({
            placeholder: 'Cari biaya konsultasi...',
            ajax: {
                url: '/konsultasi/search',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return { q: params.term };
                },
                processResults: function(data) {
                    return {
                        results: data.map(function(item) {
                            return { id: item.id, text: item.nama, harga: item.harga };
                        })
                    };
                },
                cache: true
            },
            minimumInputLength: 1
        });

        // --- Select2 AJAX for Obat/Produk ---
        $('#select-obat').select2({
            placeholder: 'Cari obat/produk...',
            ajax: {
                url: '/obat/search',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return { q: params.term };
                },
                processResults: function(data) {
                    // Support both {results: [...]} and array response
                    var items = Array.isArray(data) ? data : (data.results || []);
                    return {
                        results: items.map(function(item) {
                            return { id: item.id, text: item.text || item.nama, harga: item.harga_nonfornas || item.harga || 0 };
                        })
                    };
                },
                cache: true
            },
            minimumInputLength: 1
        });

        // Add selected Tindakan to billingData
        $('#select-tindakan').on('select2:select', function(e) {
            const data = e.params.data;
            const harga = parseHarga(data.harga);
            const qty = parseInt(data.qty) || 1;
            billingData.push({
                id: 'tindakan-' + data.id,
                billable_id: data.id,
                billable_type: 'App\\Models\\ERM\\Tindakan',
                nama_item: data.text,
                jumlah: 'Rp ' + formatCurrency(harga),
                jumlah_raw: harga,
                qty: qty,
                diskon: 0,
                diskon_type: 'nominal',
                harga_akhir: 'Rp ' + formatCurrency(harga),
                harga_akhir_raw: harga,
                deleted: false,
                deskripsi: ''
            });
            updateTable();
            calculateTotals();
            $(this).val(null).trigger('change');
            Swal.fire({
                icon: 'info',
                title: 'Perhatian',
                text: 'Harap informasikan perawat untuk menambah tindakan ini ke riwayat tindakan.',
                timer: 2500,
                showConfirmButton: false
            });
        });
        // Add selected Lab to billingData
        $('#select-lab').on('select2:select', function(e) {
            const data = e.params.data;
            const harga = parseHarga(data.harga);
            const qty = parseInt(data.qty) || 1;
            billingData.push({
                id: 'lab-' + data.id,
                billable_id: data.id,
                billable_type: 'App\\Models\\ERM\\LabTest',
                nama_item: data.text,
                jumlah: 'Rp ' + formatCurrency(harga),
                jumlah_raw: harga,
                qty: qty,
                diskon: 0,
                diskon_type: 'nominal',
                harga_akhir: 'Rp ' + formatCurrency(harga),
                harga_akhir_raw: harga,
                deleted: false,
                deskripsi: ''
            });
            updateTable();
            calculateTotals();
            $(this).val(null).trigger('change');
            Swal.fire({
                icon: 'info',
                title: 'Perhatian',
                text: 'Harap informasikan petugas lab untuk menambah pemeriksaan ini ke riwayat lab.',
                timer: 2500,
                showConfirmButton: false
            });
        });
        // Add selected Konsultasi to billingData
        $('#select-konsultasi').on('select2:select', function(e) {
            const data = e.params.data;
            const harga = parseHarga(data.harga);
            const qty = parseInt(data.qty) || 1;
            billingData.push({
                id: 'konsultasi-' + data.id,
                billable_id: data.id,
                billable_type: 'App\\Models\\ERM\\Konsultasi',
                nama_item: data.text,
                jumlah: 'Rp ' + formatCurrency(harga),
                jumlah_raw: harga,
                qty: qty,
                diskon: 0,
                diskon_type: 'nominal',
                harga_akhir: 'Rp ' + formatCurrency(harga),
                harga_akhir_raw: harga,
                deleted: false,
                deskripsi: ''
            });
            updateTable();
            calculateTotals();
            $(this).val(null).trigger('change');
        });

        // Add selected Obat/Produk to billingData
        $('#select-obat').on('select2:select', function(e) {
            const data = e.params.data;
            const harga = parseHarga(data.harga);
            const qty = parseInt(data.qty) || 1;
            billingData.push({
                id: 'obat-' + data.id,
                billable_id: data.id,
                billable_type: 'App\\Models\\ERM\\Obat',
                nama_item: data.text,
                jumlah: 'Rp ' + formatCurrency(harga),
                jumlah_raw: harga,
                qty: qty,
                diskon: 0,
                diskon_type: 'nominal',
                harga_akhir: 'Rp ' + formatCurrency(harga),
                harga_akhir_raw: harga,
                deleted: false,
                deskripsi: ''
            });
            updateTable();
            calculateTotals();
            $(this).val(null).trigger('change');
            Swal.fire({
                icon: 'info',
                title: 'Perhatian',
                text: 'Harap informasikan farmasi untuk menambah produk/obat ini ke riwayat farmasi.',
                timer: 2500,
                showConfirmButton: false
            });
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