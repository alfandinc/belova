@extends('layouts.finance.app')
@section('title', 'Finance | Billing')
@section('navbar')
    @include('layouts.finance.navbar')
@endsection
@section('content')
<!-- Modal for Editing -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form id="editForm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editResepModalLabel">Edit Resep</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="edit_id">
                    <input type="hidden" id="edit_row_index">
                    <div class="mb-3">
                        <label for="jumlah" class="form-label">Jumlah (Harga)</label>
                        <input type="number" class="form-control" id="jumlah" name="jumlah" required>
                    </div>
                    <div class="mb-3">
                        <label for="diskon" class="form-label">Diskon</label>
                        <input type="number" class="form-control" id="diskon" name="diskon">
                    </div>
                    <div class="mb-3">
                        <label for="diskon_type" class="form-label">Tipe Diskon</label>
                        <select class="form-select" id="diskon_type" name="diskon_type">
                            <option value="">Tidak Ada</option>
                            <option value="%">Persentase (%)</option>
                            <option value="nominal">Nominal (Rp)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" id="saveChangesBtn" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="container">
    <h3>Billing Pasien</h3>
    <div class="card mb-3">
        <div class="card-body">
            <strong>Nama:</strong> {{ $visitation->pasien->nama }}<br>
            <strong>ID:</strong> {{ $visitation->pasien->id }}<br>
            <strong>Jenis Kelamin:</strong> {{ $visitation->pasien->jenis_kelamin }}<br>
            <strong>Tanggal Lahir:</strong> {{ $visitation->pasien->tanggal_lahir }}<br>
            <strong>Alamat:</strong> {{ $visitation->pasien->alamat }}
        </div>
    </div>

    <h4>Rincian Billing</h4>
    <div class="table-responsive">
        <table id="billingTable" class="table">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Nama Item</th>
                    <th>Deskripsi</th>
                    <th>Jumlah (Harga)</th>
                    <th>Qty</th>
                    <th>Diskon</th>
                    <th>Harga Akhir</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
    
    <div class="mt-4">
        <button id="saveAllChangesBtn" class="btn btn-success">Simpan Billing</button>
        <button id="createInvoiceBtn" class="btn btn-primary">Buat Invoice</button>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // Store all billing data (with changes) here
        let billingData = [];
        let deletedItems = [];
        
        // Initialize DataTable
        const table = $('#billingTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('finance.billing.create', $visitation->id) }}",
                type: "GET",
                dataSrc: function(json) {
                    // Calculate harga_akhir for each item initially
                    json.data.forEach(function(item) {
                        // Add harga_akhir initial value (same as jumlah initially)
                        item.harga_akhir_raw = item.jumlah_raw;
                        item.harga_akhir = item.jumlah;
                    });
                    
                    // Store the initial data
                    billingData = json.data;
                    return json.data;
                }
            },
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex' },
                { data: 'nama_item', name: 'nama_item' },
                { data: 'deskripsi', name: 'deskripsi' },
                { data: 'jumlah', name: 'jumlah' },
                { data: 'qty', name: 'qty' },
                { data: 'diskon', name: 'diskon' },
                { data: 'harga_akhir', name: 'harga_akhir' },
                { 
                    data: null, 
                    render: function(data, type, row, meta) {
                        return `
                            <button class="btn btn-sm btn-primary edit-btn me-1" 
                                data-id="${row.id}" 
                                data-row-index="${meta.row}"
                                data-jumlah="${row.is_racikan ? row.racikan_total_price : row.jumlah_raw}" 
                                data-diskon="${row.diskon_raw || ''}" 
                                data-diskon_type="${row.diskon_type || ''}">
                                Edit
                            </button>
                            <button class="btn btn-sm btn-danger delete-btn"
                                data-id="${row.id}"
                                data-row-index="${meta.row}">
                                Hapus
                            </button>
                        `;
                    },
                    orderable: false, 
                    searchable: false 
                }
            ],
            "language": {
                "emptyTable": "Tidak ada data billing tersedia untuk kunjungan ini"
            },
            "drawCallback": function() {
                // After table is drawn, reattach event handlers
                attachEventHandlers();
            }
        });
        
        // Custom DataTable processing
        function attachEventHandlers() {
            // Edit button handler
            $('.edit-btn').off('click').on('click', function() {
                const id = $(this).data('id');
                const rowIndex = $(this).data('row-index');
                const jumlah = $(this).data('jumlah');
                const diskon = $(this).data('diskon');
                const diskon_type = $(this).data('diskon_type');
                
                // Fill modal form with data
                $('#edit_id').val(id);
                $('#edit_row_index').val(rowIndex);
                $('#jumlah').val(jumlah);
                $('#diskon').val(diskon);
                $('#diskon_type').val(diskon_type);
                
                // Show modal
                $('#editModal').modal('show');
            });
            
            // Delete button handler
            $('.delete-btn').off('click').on('click', function() {
                const id = $(this).data('id');
                const rowIndex = $(this).data('row-index');
                
                if (confirm('Apakah Anda yakin ingin menghapus item ini?')) {
                    // Mark as deleted but keep in array
                    billingData[rowIndex].deleted = true;
                    deletedItems.push(id);
                    
                    // Remove from view
                    table.row($(this).closest('tr')).remove().draw();
                }
            });
        }
        
        // Save changes button in modal
        $('#saveChangesBtn').on('click', function(e) {
            // Prevent default form submission
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
                billingData[rowIndex].jumlah = 'Rp ' + numberWithCommas(jumlah);
                
                if (diskon && diskon > 0) {
                    if (diskon_type === '%') {
                        billingData[rowIndex].diskon = diskon + '%';
                    } else {
                        billingData[rowIndex].diskon = 'Rp ' + numberWithCommas(diskon);
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
                
                // Update harga_akhir
                billingData[rowIndex].harga_akhir_raw = finalJumlah;
                billingData[rowIndex].harga_akhir = 'Rp ' + numberWithCommas(finalJumlah);
                
                // Mark as edited
                billingData[rowIndex].edited = true;
            }
            
            // Close modal
            $('#editModal').modal('hide');
            
            // Redraw the table
            updateTable();
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
                
                // Reattach event handlers
                attachEventHandlers();
            }, 100);
        }
        
        // Helper function for formatting numbers
        function numberWithCommas(x) {
            return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        }
        
        // Save all changes button
        $('#saveAllChangesBtn').on('click', function() {
            if (confirm('Simpan semua perubahan billing?')) {
                const changedData = {
                    visitation_id: {{ $visitation->id }},
                    edited_items: billingData.filter(item => item.edited),
                    deleted_items: deletedItems
                };
                
                $.ajax({
                    url: "{{ route('finance.billing.save') }}",
                    type: "POST",
                    data: changedData,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        alert('Data billing berhasil disimpan');
                        // Refresh the page or update the table as needed
                    },
                    error: function(xhr) {
                        alert('Terjadi kesalahan: ' + xhr.responseText);
                    }
                });
            }
        });
        
        // Create invoice button
        $('#createInvoiceBtn').on('click', function() {
            if (confirm('Buat invoice dari billing ini?')) {
                const invoiceData = {
                    visitation_id: {{ $visitation->id }},
                    items: billingData.filter(item => !item.deleted)
                };
                
                $.ajax({
                    url: "{{ route('finance.billing.createInvoice') }}",
                    type: "POST",
                    data: invoiceData,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        alert('Invoice berhasil dibuat');
                        // Redirect to invoice page or show success message
                    },
                    error: function(xhr) {
                        alert('Terjadi kesalahan: ' + xhr.responseText);
                    }
                });
            }
        });
    });
</script>
@endsection