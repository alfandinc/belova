@extends('layouts.erm.app')
@section('title', 'ERM | Master Faktur Pembelian')
@section('navbar')
    @include('layouts.erm.navbar')
@endsection  

@section('content')
<div class="container-fluid">
        <!-- Page-Title -->
    <!-- Title and Button Row -->
    <div class="row mb-3 mt-3 align-items-center">
        <div class="col-md-6">
            <h2 class="mb-0">Daftar Permintaan Pembelian</h2>
        </div>
        <div class="col-md-6 text-right">
            <a href="{{ route('erm.permintaan.create') }}" class="btn btn-primary">Buat Permintaan</a>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="row">
                    <div class="col">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:void(0);">ERM</a></li>
                            <li class="breadcrumb-item active">Farmasi</li>
                            <li class="breadcrumb-item">E-Resep</li>
                        </ol>
                    </div><!--end col-->
                </div><!--end row-->                                                              
            </div><!--end page-title-box-->
        </div><!--end col-->
    </div><!--end row-->
    <!-- end page title end breadcrumb -->
    
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    <table class="table table-bordered" id="permintaan-table">
        <thead>
            <tr>
                <th>No</th>
                <th>No Permintaan</th>
                <th>Pemasok</th>
                <th>Obats</th>
                <th>Tanggal Permintaan</th>
                <th>Status</th>
                <th>Jumlah Item</th>
                <th>Aksi</th>
            </tr>
        </thead>
    </table>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    var table = $('#permintaan-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route('erm.permintaan.data') }}',
        columns: [
            { data: 'no', name: 'no' },
            { data: 'no_permintaan', name: 'no_permintaan' },
            { data: 'pemasok', name: 'pemasok' },
            { data: 'obats', name: 'obats', orderable: false, searchable: false },
            { data: 'request_date', name: 'request_date' },
            { data: 'status', name: 'status' },
            { data: 'jumlah_item', name: 'jumlah_item', orderable: false, searchable: false },
            { data: 'aksi', name: 'aksi', orderable: false, searchable: false },
        ]
    });

    // Approve button AJAX
    $('#permintaan-table').on('click', '.btn-approve', function() {
        var id = $(this).data('id');
        if(confirm('Approve permintaan ini?')) {
            $.ajax({
                url: '/erm/permintaan/' + id + '/approve',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(res) {
                    table.ajax.reload();
                },
                error: function(xhr) {
                    alert('Gagal approve permintaan!');
                }
            });
        }
    });
});
</script>
@endsection

