@extends('layouts.erm.app')
@section('title', 'ERM | Master Faktur Pembelian')
@section('navbar')
    @include('layouts.erm.navbar')
@endsection  

@section('content')
<div class="container">
    <h1>Daftar Permintaan Pembelian</h1>
    <a href="{{ route('erm.permintaan.create') }}" class="btn btn-primary mb-3">Buat Permintaan</a>
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

