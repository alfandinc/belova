@extends('layouts.erm.app')
@section('title', 'ERM | Daftar Obat')
@section('navbar')
    @include('layouts.erm.navbar')
@endsection  

@section('content')
<div class="container-fluid">
    <div class="d-flex align-items-center mb-0 mt-2">
        <h3 class="mb-0 mr-2">Daftar Obat Farmasi</h3>
    </div>
    
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="row">
                    <div class="col">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:void(0);">ERM</a></li>
                            <li class="breadcrumb-item">Farmasi</li>
                            <li class="breadcrumb-item active">Stok Obat</li>
                        </ol>
                    </div><!--end col-->
                </div><!--end row-->                                                              
            </div><!--end page-title-box-->
        </div><!--end col-->
    </div><!--end row-->  

    <a href="{{ route('erm.obat.create') }}" class="btn btn-primary mb-3">+ Tambah Obat</a>
    <a href="{{ route('erm.obat.export-excel', request()->all()) }}" class="btn btn-success mb-3" target="_blank">
        <i class="fas fa-file-excel"></i> Export Excel
    </a>

        <button id="fill-stok-btn" class="btn btn-warning mb-3"><i class="fas fa-sync"></i> Isi Stok 100 untuk Obat Stok 0</button>

    <div class="row mb-3">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Filter</h5>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="filter_kategori">Kategori</label>
                                <select id="filter_kategori" class="form-control select2">
                                    <option value="">Semua Kategori</option>
                                    @foreach($kategoris as $kategori)
                                        <option value="{{ $kategori }}">{{ $kategori }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="filter_metode_bayar">Metode Bayar</label>
                                <select id="filter_metode_bayar" class="form-control select2">
                                    <option value="">Semua Metode Bayar</option>
                                    @foreach($metodeBayars as $metodeBayar)
                                        <option value="{{ $metodeBayar->id }}">{{ $metodeBayar->nama }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="filter_status">Status</label>
                                <select id="filter_status" class="form-control select2">
                                    <option value="">Semua Status</option>
                                    <option value="1">Aktif</option>
                                    <option value="0">Tidak Aktif</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-12">
                            <button id="reload-table" class="btn btn-secondary">Refresh Data</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
     
    <div class="card">
        <div class="card-body">
            <table id="obat-table" class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Kode Obat</th>
                        <th>Nama Obat</th>
                        <th class="text-right">HPP</th>
                        <th class="text-right">Harga Non-Fornas</th>
                        <th>Kategori</th>
                        <th>Zat Aktif</th>
                        <th class="text-right">Stok</th>
                        <th>Batch/Sisa</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between">
                <span class="font-weight-bold">Total Nilai Stok (HPP × Stok):</span>
                <span id="total-hpp-stok" style="font-size:1.5rem;font-weight:bold;color:#155724;">Rp 0</span>
            </div>
            <div class="mt-2 text-right" style="font-style:italic;color:#444;">
                <span id="terbilang-hpp-stok">-</span>
            </div>
        </div>
    </div>
</div>
    <!-- Batch Info Modal -->
    <div class="modal fade" id="batchInfoModal" tabindex="-1" role="dialog" aria-labelledby="batchInfoModalLabel" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="batchInfoModalLabel">Batch, Exp Date & Sisa</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <table class="table table-bordered">
              <thead>
                <tr>
                  <th>Batch</th>
                  <th>Exp Date</th>
                  <th>Sisa</th>
                </tr>
              </thead>
              <tbody id="batchInfoTableBody">
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
    
@endsection

@section('scripts')
<style>
    /* Right align for price and stock columns */
    #obat-table td:nth-child(3), 
    #obat-table td:nth-child(6), 
    #obat-table td:nth-child(8) {
        text-align: right;
    }
    
    /* Style for inactive medications */
    tr.inactive-medication {
        background-color: #ffe0e0 !important;
    }
    
    .status-badge {
        font-weight: bold;
        padding: 4px 8px;
        border-radius: 4px;
    }
    
    .status-active {
        background-color: #d4edda;
        color: #155724;
    }
    
    .status-inactive {
        background-color: #f8d7da;
        color: #721c24;
    }

    .blinking-warning {
        animation: blink-warning 1s linear infinite;
    }
    @keyframes blink-warning {
        0%, 100% { opacity: 1; }
        50% { opacity: 0; }
    }
</style>
<script>
    $(document).ready(function () {
        // Initialize select2
        $('.select2').select2({
            width: '100%'
        });

            // Handle fill stok button click
            $('#fill-stok-btn').on('click', function() {
                if (confirm('Isi stok 100 untuk semua obat dengan stok 0?')) {
                    $.ajax({
                        url: '/erm/obat/fill-stok',
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            alert(response.message || 'Stok berhasil diisi!');
                            table.ajax.reload();
                        },
                        error: function(xhr) {
                            alert('Terjadi kesalahan: ' + (xhr.responseJSON?.message || xhr.responseText));
                        }
                    });
                }
            });
        
        // Make sure filter_status has an empty value initially
        $('#filter_status').val('').trigger('change.select2');

        // Initialize DataTable
        let table = $('#obat-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('erm.obat.index') }}",
                data: function(d) {
                    d.kategori = $('#filter_kategori').val();
                    d.metode_bayar_id = $('#filter_metode_bayar').val();
                    
                    // Always send the status_aktif parameter
                    // Even when it's empty, to ensure the controller gets it
                    d.status_aktif = $('#filter_status').val();
                    
                    console.log('Sending filters:', {
                        kategori: d.kategori,
                        metode_bayar_id: d.metode_bayar_id,
                        status_aktif: d.status_aktif
                    });
                }
            },
            columns: [
                { data: 'kode_obat', name: 'kode_obat' },
                { data: 'nama', name: 'nama' },
                { 
                    data: 'hpp',
                    name: 'hpp',
                    className: 'text-right',
                    render: function(data) {
                        return data ? 'Rp ' + data.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".") : '-';
                    }
                },
                { 
                    data: 'harga_nonfornas', 
                    name: 'harga_nonfornas',
                    className: 'text-right',
                    render: function(data) {
                        return data ? 'Rp ' + data.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".") : '-';
                    }
                },
                { data: 'kategori', name: 'kategori' },
                { data: 'zat_aktif', name: 'zat_aktif' },
                { 
                    data: 'stok', 
                    name: 'stok',
                    className: 'text-right'
                },
                { 
                    data: 'batch_info',
                    name: 'min_exp_date', // must match the backend alias for ordering
                    orderable: true,
                    searchable: false,
                    render: function(data, type, row) {
                        // Parse batch info from button data attribute
                        var match = data.match(/data-batchinfo=\"([^\"]*)\"/);
                        var batchJson = match ? match[1] : null;
                        var showWarning = false;
                        if (batchJson) {
                            try {
                                var batchArr = JSON.parse(batchJson.replace(/&quot;/g, '"'));
                                var now = new Date();
                                var threeMonths = new Date(now.getFullYear(), now.getMonth() + 3, now.getDate());
                                batchArr.forEach(function(item) {
                                    if (item.expiration_date) {
                                        var exp = new Date(item.expiration_date);
                                        if (exp < threeMonths) {
                                            showWarning = true;
                                        }
                                    }
                                });
                            } catch (e) {}
                        }
                        var html = data;
                        if (showWarning) {
                            html += ' <span class="blinking-warning" title="Ada batch exp < 3 bulan" style="color:#e67e22;font-size:18px;vertical-align:middle;"><i class="fas fa-exclamation-triangle"></i></span>';
                        }
                        return html;
                    }
                },
                { 
                    data: 'status_aktif', 
                    name: 'status_aktif',
                    render: function(data) {
                        if (data === 1) {
                            return '<span class="status-badge status-active">Aktif</span>';
                        } else {
                            return '<span class="status-badge status-inactive">Tidak Aktif</span>';
                        }
                    }
                },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ],
            order: [[ 7, 'asc' ]], // Default ordering by batch/exp date ascending
            rowCallback: function(row, data) {
                // Add a class to rows with inactive medications
                if (data.status_aktif === 0) {
                    $(row).addClass('inactive-medication');
                }
            },
            drawCallback: function(settings) {
                // Calculate total nilai stok (HPP x Stok) for all rows (filtered)
                let api = this.api();
                let total = 0;
                api.rows({ filter: 'applied' }).every(function() {
                    let data = this.data();
                    let hpp = parseFloat(data.hpp) || 0;
                    let stok = parseFloat(data.stok) || 0;
                    total += hpp * stok;
                });
                // Format as perfect Rupiah
                function formatRupiah(angka) {
                    if (!angka || isNaN(angka)) return '-';
                    return 'Rp ' + angka.toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, ".") + ',-';
                }
                // Terbilang (spell out in Indonesian)
                function terbilang(n) {
                    var satuan = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas'];
                    n = Math.floor(n);
                    if (n < 12) return satuan[n];
                    if (n < 20) return terbilang(n - 10) + ' belas';
                    if (n < 100) return terbilang(Math.floor(n / 10)) + ' puluh' + (n % 10 !== 0 ? ' ' + terbilang(n % 10) : '');
                    if (n < 200) return 'seratus' + (n - 100 !== 0 ? ' ' + terbilang(n - 100) : '');
                    if (n < 1000) return terbilang(Math.floor(n / 100)) + ' ratus' + (n % 100 !== 0 ? ' ' + terbilang(n % 100) : '');
                    if (n < 2000) return 'seribu' + (n - 1000 !== 0 ? ' ' + terbilang(n - 1000) : '');
                    if (n < 1000000) return terbilang(Math.floor(n / 1000)) + ' ribu' + (n % 1000 !== 0 ? ' ' + terbilang(n % 1000) : '');
                    if (n < 1000000000) return terbilang(Math.floor(n / 1000000)) + ' juta' + (n % 1000000 !== 0 ? ' ' + terbilang(n % 1000000) : '');
                    if (n < 1000000000000) return terbilang(Math.floor(n / 1000000000)) + ' miliar' + (n % 1000000000 !== 0 ? ' ' + terbilang(n % 1000000000) : '');
                    if (n < 1000000000000000) return terbilang(Math.floor(n / 1000000000000)) + ' triliun' + (n % 1000000000000 !== 0 ? ' ' + terbilang(n % 1000000000000) : '');
                    return '';
                }
                let formatted = total > 0 ? formatRupiah(total) : '-';
                $('#total-hpp-stok').html(formatted);
                // Show terbilang
                let terbilangText = total > 0 ? (terbilang(total) + ' rupiah').replace(/ +/g, ' ').replace(/^\s+|\s+$/g, '') : '-';
                $('#terbilang-hpp-stok').text(terbilangText.charAt(0).toUpperCase() + terbilangText.slice(1));
            }
        });

        // Handle batch info button click
    $(document).on('click', '.batch-info-btn', function() {
        var batchData = $(this).data('batchinfo');
        var tbody = '';
        var now = new Date();
        var threeMonths = new Date(now.getFullYear(), now.getMonth() + 3, now.getDate());
        if (Array.isArray(batchData) && batchData.length > 0) {
            batchData.forEach(function(item) {
                var expDate = item.expiration_date ? new Date(item.expiration_date) : null;
                var isExpSoon = expDate && expDate < threeMonths;
                var rowClass = isExpSoon ? ' style="background-color:#f8d7da;color:#721c24;"' : '';
                tbody += '<tr'+rowClass+'>' +
                    '<td>' + (item.batch || '-') + '</td>' +
                    '<td>' + (item.expiration_date || '-') + '</td>' +
                    '<td>' + (item.sisa !== null ? item.sisa : '-') + '</td>' +
                    '</tr>';
            });
        } else {
            tbody = '<tr><td colspan="3">Tidak ada data batch</td></tr>';
        }
        $('#batchInfoTableBody').html(tbody);
        $('#batchInfoModal').modal('show');
    });

        // Apply filter when select changes (no button needed)
        $('#filter_kategori, #filter_metode_bayar, #filter_status').on('change', function() {
            var statusFilter = $('#filter_status').val();
            console.log('Status filter changed to:', statusFilter);
            
            // Add special handling for the "All" option
            if (statusFilter === '') {
                console.log('All statuses selected');
            }
            
            table.ajax.reload();
        });
        
        // Add reload button functionality
        $('#reload-table').on('click', function() {
            console.log('Manually reloading table...');
            $('#filter_status').val('').trigger('change.select2');
            table.ajax.reload();
        });

        // Handle delete button clicks
        $(document).on('click', '.delete-btn', function() {
            if (confirm('Apakah Anda yakin ingin menghapus obat ini?')) {
                let id = $(this).data('id');
                
                $.ajax({
                    url: '/erm/obat/' + id,
                    type: 'DELETE',
                    data: {
                        '_token': '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        alert('Obat berhasil dihapus');
                        table.ajax.reload();
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