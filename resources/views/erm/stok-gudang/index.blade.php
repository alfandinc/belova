@extends('layouts.erm.app')
@section('title', 'Finance | Billing')
@section('navbar')
    @include('layouts.erm.navbar')
@endsection
@section('content')
<div class="container-fluid">
    <!-- Page-Title -->
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="row">
                    <div class="col">
                        <h4 class="page-title">Stok per Gudang</h4>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:void(0);">Farmasi</a></li>
                            <li class="breadcrumb-item active">Stok per Gudang</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="row align-items-center mb-3">
                        <div class="col">
                            <h4 class="card-title">Data Stok Obat per Gudang</h4>
                        </div>
                    </div>
                    <!-- Filter Row -->
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="filter_gudang">Pilih Gudang:</label>
                                <select class="form-control select2" id="filter_gudang">
                                    @foreach($gudangs as $gudang)
                                        <option value="{{ $gudang->id }}" {{ $gudang->id === $defaultGudang->id ? 'selected' : '' }}>
                                            {{ $gudang->nama }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="search_obat">Cari Obat:</label>
                                <input type="text" class="form-control" id="search_obat" placeholder="Ketik nama obat atau kode obat...">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="filter_status">Filter Status:</label>
                                <select class="form-control" id="filter_status">
                                    <option value="">Semua Status</option>
                                    <option value="minimum">Stok Minimum</option>
                                    <option value="maksimum">Stok Maksimum</option>
                                    <option value="normal">Normal</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <div class="form-group">
                                <button type="button" class="btn btn-secondary" id="btn-reset-filter">
                                    <i class="fas fa-undo"></i> Reset Filter
                                </button>
                            </div>
                        </div>
                    </div>
                    <!-- Additional Filter Row -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="hide_inactive_obat" checked>
                                <label class="form-check-label" for="hide_inactive_obat">
                                    <strong>Sembunyikan obat yang tidak aktif</strong>
                                </label>
                                <small class="form-text text-muted">Centang untuk hanya menampilkan obat yang aktif saja</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;" id="stok-table">
                            <thead>
                                <tr>
                                    <th>Kode Obat</th>
                                    <th>Nama Obat</th>
                                    <th>Gudang</th>
                                    <th>Total Stok</th>
                                    <th>Min Stok</th>
                                    <th>Max Stok</th>
                                    <th>Status Stok</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal for Batch Details -->
<div class="modal fade" id="batchDetailsModal" tabindex="-1" role="dialog" aria-labelledby="batchDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="batchDetailsTitle">Detail Batch</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="batchDetailsContent">
                    <!-- Content will be loaded dynamically -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script type="text/javascript">
$(document).ready(function() {
    // Initialize select2
    $('.select2').select2();

    // Initialize DataTable
    var table = $('#stok-table').DataTable({
        processing: true,
        serverSide: true,
        searching: false, // Disable built-in search
        ajax: {
            url: '{{ route("erm.stok-gudang.data") }}',
            data: function(d) {
                d.gudang_id = $('#filter_gudang').val();
                d.search_obat = $('#search_obat').val();
                d.filter_status = $('#filter_status').val();
                d.hide_inactive = $('#hide_inactive_obat').is(':checked') ? 1 : 0;
            }
        },
        columns: [
            { data: 'kode_obat', name: 'kode_obat', searchable: false },
            { data: 'nama_obat', name: 'nama_obat', searchable: false },
            { data: 'nama_gudang', name: 'nama_gudang', searchable: false },
            { data: 'total_stok', name: 'total_stok', searchable: false },
            { data: 'min_stok', name: 'min_stok', searchable: false },
            { data: 'max_stok', name: 'max_stok', searchable: false },
            { 
                data: 'status_stok', 
                name: 'status_stok',
                searchable: true,
                render: function(data) {
                    return data;
                }
            },
            { 
                data: 'actions', 
                name: 'actions',
                orderable: false,
                searchable: false
            }
        ],
        order: [[1, 'asc']],
        pageLength: 25,
        responsive: true,
        drawCallback: function(settings) {
            // Re-initialize tooltips after table redraw
            $('[data-toggle="tooltip"]').tooltip();
            feather.replace();
        }
    });

    // Reload table when warehouse filter changes
    $('#filter_gudang').change(function() {
        table.ajax.reload();
    });

    // Search obat with delay
    var searchTimeout;
    $('#search_obat').on('keyup', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            table.ajax.reload();
        }, 500); // 500ms delay
    });

    // Filter status change - client side filtering
    $('#filter_status').change(function() {
        var status = $(this).val();
        if (status === '') {
            // Show all rows
            table.column(6).search('').draw();
        } else {
            // Filter by status
            var searchTerm = '';
            if (status === 'minimum') {
                searchTerm = 'Stok Minimum';
            } else if (status === 'maksimum') {
                searchTerm = 'Stok Maksimum';
            } else if (status === 'normal') {
                searchTerm = 'Normal';
            }
            table.column(6).search(searchTerm).draw();
        }
    });

    // Reload table when checkbox filter changes
    $('#hide_inactive_obat').change(function() {
        table.ajax.reload();
    });

    // Reset filter button
    $('#btn-reset-filter').click(function() {
        $('#search_obat').val('');
        $('#filter_status').val('');
        $('#hide_inactive_obat').prop('checked', true); // Reset to default (checked)
        table.ajax.reload();
    });

    // Handle batch details button click
    $(document).on('click', '.show-batch-details', function() {
        var obatId = $(this).data('obat-id');
        var gudangId = $(this).data('gudang-id');
        
        $.ajax({
            url: '{{ route("erm.stok-gudang.batch-details") }}',
            type: 'GET',
            data: {
                obat_id: obatId,
                gudang_id: gudangId
            },
            success: function(response) {
                $('#batchDetailsTitle').text('Detail Batch - ' + response.obat + ' (' + response.gudang + ')');
                var tableHtml = '<table class="table table-bordered">';
                tableHtml += '<thead><tr><th>Batch</th><th>Stok</th><th>Tanggal Expired</th><th>Status</th></tr></thead><tbody>';
                
                response.data.forEach(function(item) {
                    tableHtml += '<tr>';
                    tableHtml += '<td>' + item.batch + '</td>';
                    tableHtml += '<td>' + item.stok + '</td>';
                    tableHtml += '<td>' + item.expiration_date + '</td>';
                    tableHtml += '<td>' + item.status + '</td>';
                    tableHtml += '</tr>';
                });
                
                tableHtml += '</tbody></table>';
                $('#batchDetailsContent').html(tableHtml);
                $('#batchDetailsModal').modal('show');
            },
            error: function() {
                alert('Terjadi kesalahan saat mengambil data batch');
            }
        });
    });
});
</script>
@endsection
