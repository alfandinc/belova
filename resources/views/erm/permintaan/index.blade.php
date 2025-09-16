@extends('layouts.erm.app')
@section('title', 'ERM | Master Faktur Pembelian')
@section('navbar')
    @include('layouts.erm.navbar-farmasi')
@endsection  

@section('content')
<div class="container-fluid">
        <!-- Page-Title -->
    <!-- Title and Button Row -->
    <div class="row mt-3 align-items-center">
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
                            <li class="breadcrumb-item">Farmasi</li>
                            <li class="breadcrumb-item active">Permintaan Pembelian</li>
                        </ol>
                    </div><!--end col-->
                </div><!--end row-->                                                              
            </div><!--end page-title-box-->
        </div><!--end col-->
    </div><!--end row-->
    <!-- end page title end breadcrumb -->
    
    @if(session('success'))
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'success',
                title: 'Sukses',
                text: @json(session('success')),
                timer: 2000,
                showConfirmButton: false
            });
        });
        </script>
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
        search: {
            return: true
        },
        columns: [
            { data: 'no', name: 'no', orderable: false, searchable: false },
            { data: 'no_permintaan', name: 'no_permintaan' },
            { data: 'pemasok', name: 'pemasok' },
            { data: 'obats', name: 'obats', orderable: false },
            { data: 'request_date', name: 'request_date' },
            { 
                data: 'status', 
                name: 'status',
                render: function(data, type, row) {
                    if (data === 'waiting_approval' || data === 'waiting' || data === 'menunggu') {
                        return '<span class="badge badge-warning text-dark">Waiting Approval</span>';
                    } else if (data === 'approved' || data === 'disetujui') {
                        let html = '<span class="badge badge-success">Approved</span>';
                        if (row.approved_by_name) {
                            html += '<br><small class="text-muted">Approved by: ' + row.approved_by_name + '</small>';
                        }
                        return html;
                    } else {
                        return '<span class="badge badge-secondary">'+data+'</span>';
                    }
                }
            },
            { data: 'jumlah_item', name: 'jumlah_item', orderable: false, searchable: false },
            { data: 'aksi', name: 'aksi', orderable: false, searchable: false },
        ],
        language: {
            search: "Cari:",
            searchPlaceholder: "Cari no permintaan, pemasok, obat, dll...",
            processing: "Sedang memproses...",
            lengthMenu: "Tampilkan _MENU_ data",
            info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
            infoEmpty: "Menampilkan 0 sampai 0 dari 0 data",
            infoFiltered: "(difilter dari _MAX_ total data)",
            paginate: {
                first: "Pertama",
                last: "Terakhir", 
                next: "Selanjutnya",
                previous: "Sebelumnya"
            }
        }
    });

    // Approve button AJAX
    $('#permintaan-table').on('click', '.btn-approve', function() {
        var id = $(this).data('id');
        Swal.fire({
            title: 'Approve permintaan ini?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Approve',
            cancelButtonText: 'Batal',
        }).then((result) => {
            if (result.value) {
                $.ajax({
                    url: '/erm/permintaan/' + id + '/approve',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(res) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil',
                            text: 'Permintaan berhasil diapprove!',
                            timer: 1500,
                            showConfirmButton: false
                        });
                        table.ajax.reload();
                    },
                    error: function(xhr) {
                        let msg = 'Gagal approve permintaan!';
                        if(xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: msg,
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }
                });
            }
        });
    });
});
</script>
@endsection

