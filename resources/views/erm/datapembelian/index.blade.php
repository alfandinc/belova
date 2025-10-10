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
                render: function(data) {
                    return data + ' item';
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
});
</script>
@endpush