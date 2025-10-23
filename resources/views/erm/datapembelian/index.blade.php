@extends('layouts.erm.app')
@section('title', 'ERM | Data Pembelian')
@section('navbar')
    @include('layouts.erm.navbar-farmasi')
@endsection  
@section('content')
<div class="container-fluid">
    <!-- Page-Title -->
    <div class="row mt-3 align-items-center">
        <div class="col-md-6">
            <h2 class="mb-0">Data Pembelian per Pemasok</h2>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="row">
                    <div class="col">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/erm">ERM</a></li>
                            <li class="breadcrumb-item"><a href="#" onclick="return false;">Pembelian</a></li>
                            <li class="breadcrumb-item active">Data Pembelian</li>
                        </ol>
                    </div><!--end col-->
                </div><!--end row-->                                                              
            </div><!--end page-title-box-->
        </div><!--end col-->
    </div><!--end row-->
    <!-- end page title end breadcrumb -->
    
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Ringkasan Pembelian per Pemasok</h4>
                    <p class="text-muted mb-0">Data pembelian dikelompokkan berdasarkan pemasok dengan total nominal, pembelian terakhir, dan jumlah jenis item.</p>
                </div><!--end card-header-->
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="data-pembelian-table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama Pemasok</th>
                                    <th>Alamat</th>
                                    <th>Telepon</th>
                                    <th>Total Nominal Pembelian</th>
                                    <th>Pembelian Terakhir</th>
                                    <th>Qty Jenis Item</th>
                                    <th>Jumlah Faktur</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div><!--end card-body-->
            </div><!--end card-->
        </div><!--end col-->
    </div><!--end row-->

    <!-- Modal for Item List -->
    <div class="modal fade" id="modalItemList" tabindex="-1" role="dialog" aria-labelledby="modalItemListLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalItemListLabel">Daftar Item yang Dibeli</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <!-- Search Input -->
                    <div class="mb-3">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fa fa-search"></i></span>
                            </div>
                            <input type="text" class="form-control" id="searchItemInput" placeholder="Cari nama obat/item...">
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary" type="button" id="clearSearchBtn">
                                    <i class="fa fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <small class="text-muted">Ketik untuk mencari item berdasarkan nama</small>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama Obat/Item</th>
                                    <th>Principal</th>
                                    <th>Total Qty Dibeli</th>
                                    <th>Harga Terakhir</th>
                                </tr>
                            </thead>
                            <tbody id="itemListTableBody">
                                <!-- Content will be populated by JavaScript -->
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- No results message -->
                    <div id="noResultsMessage" class="text-center text-muted mt-3" style="display: none;">
                        <i class="fa fa-search fa-2x mb-2"></i>
                        <p>Tidak ada item yang sesuai dengan pencarian "<span id="searchTerm"></span>"</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function() {
    var dataPembelianTable = $('#data-pembelian-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route('erm.datapembelian.index') }}',
        },
        order: [[4, 'desc']], // Order by total nominal (descending)
        columns: [
            { 
                data: null, 
                name: 'no', 
                orderable: false, 
                searchable: false, 
                render: function (data, type, row, meta) {
                    return meta.row + meta.settings._iDisplayStart + 1;
                }
            },
            { data: 'nama_pemasok', name: 'nama_pemasok' },
            { 
                data: 'alamat', 
                name: 'alamat',
                render: function(data) {
                    return data || '-';
                }
            },
            { 
                data: 'telepon', 
                name: 'telepon',
                render: function(data) {
                    return data || '-';
                }
            },
            { 
                data: 'total_nominal', 
                name: 'total_nominal',
                render: function(data) {
                    return 'Rp ' + parseFloat(data || 0).toLocaleString('id-ID');
                }
            },
            { 
                data: 'pembelian_terakhir', 
                name: 'pembelian_terakhir',
                render: function(data) {
                    return data === '-' ? '-' : new Date(data).toLocaleDateString('id-ID');
                }
            },
            { 
                data: 'qty_jenis_item', 
                name: 'qty_jenis_item',
                render: function(data, type, row) {
                    return data + ' item <button class="btn btn-sm btn-outline-info ml-2 btn-view-items" data-items=\'' + JSON.stringify(row.items_detail) + '\' data-pemasok="' + row.nama_pemasok + '"><i class="fa fa-eye"></i> Lihat</button>';
                }
            },
            { 
                data: 'jumlah_faktur', 
                name: 'jumlah_faktur',
                render: function(data) {
                    return data + ' faktur';
                }
            },
            { 
                data: 'action', 
                name: 'action', 
                orderable: false, 
                searchable: false 
            }
        ],
        language: {
            processing: "Memuat data...",
            lengthMenu: "Tampilkan _MENU_ data per halaman",
            zeroRecords: "Data tidak ditemukan",
            info: "Menampilkan halaman _PAGE_ dari _PAGES_",
            infoEmpty: "Tidak ada data yang tersedia",
            infoFiltered: "(difilter dari _MAX_ total data)",
            search: "Cari:",
            paginate: {
                first: "Pertama",
                last: "Terakhir",
                next: "Selanjutnya",
                previous: "Sebelumnya"
            }
        }
    });

    // Handle view items button click
    $('#data-pembelian-table').on('click', '.btn-view-items', function() {
        var items = JSON.parse($(this).attr('data-items'));
        var pemasokName = $(this).attr('data-pemasok');
        
        // Store items globally for search functionality
        window.currentModalItems = items;
        
        // Update modal title
        $('#modalItemListLabel').text('Daftar Item yang Dibeli - ' + pemasokName);
        
        // Clear search input
        $('#searchItemInput').val('');
        $('#noResultsMessage').hide();
        
        // Populate table with items
        populateItemTable(items);
        
        // Show modal
        $('#modalItemList').modal('show');
    });

    // Function to populate item table
        function populateItemTable(items) {
        // Clear existing content
        $('#itemListTableBody').empty();
        
        if (items && items.length > 0) {
            $.each(items, function(index, item) {
                var principalName = item.principal_name || item.principal || '-';
                var row = '<tr class="item-row" data-item-name="' + item.nama_obat.toLowerCase() + '" data-principal-name="' + principalName.toLowerCase() + '">' +
                    '<td>' + (index + 1) + '</td>' +
                    '<td>' + item.nama_obat + '</td>' +
                    '<td>' + principalName + '</td>' +
                    '<td>' + item.total_qty + '</td>' +
                    '<td>Rp ' + parseFloat(item.last_price || 0).toLocaleString('id-ID') + '</td>' +
                    '</tr>';
                $('#itemListTableBody').append(row);
            });
        } else {
            $('#itemListTableBody').append('<tr><td colspan="5" class="text-center">Tidak ada data item</td></tr>');
        }
    }

    // Search functionality for modal
        $('#searchItemInput').on('input', function() {
        var searchTerm = $(this).val().toLowerCase().trim();
        var visibleRows = 0;
        
        if (searchTerm === '') {
            // Show all rows if search is empty
            $('.item-row').show();
            $('#noResultsMessage').hide();
            // Re-number the rows
            $('.item-row').each(function(index) {
                $(this).find('td:first').text(index + 1);
            });
        } else {
            // Filter rows based on search term (match item name or principal)
            $('.item-row').each(function() {
                var itemName = $(this).data('item-name') || '';
                var principalName = $(this).data('principal-name') || '';
                if (itemName.includes(searchTerm) || principalName.includes(searchTerm)) {
                    $(this).show();
                    visibleRows++;
                } else {
                    $(this).hide();
                }
            });
            
            // Re-number visible rows
            var counter = 1;
            $('.item-row:visible').each(function() {
                $(this).find('td:first').text(counter++);
            });
            
            // Show/hide no results message
            if (visibleRows === 0) {
                $('#searchTerm').text(searchTerm);
                $('#noResultsMessage').show();
            } else {
                $('#noResultsMessage').hide();
            }
        }
    });

    // Clear search button
    $('#clearSearchBtn').on('click', function() {
        $('#searchItemInput').val('').trigger('input');
        $('#searchItemInput').focus();
    });

    // Reset search when modal is closed
    $('#modalItemList').on('hidden.bs.modal', function() {
        $('#searchItemInput').val('');
        $('#noResultsMessage').hide();
        window.currentModalItems = null;
    });
});
</script>
@endpush