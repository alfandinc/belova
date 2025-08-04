@extends('layouts.erm.app')
@section('title', 'ERM | FakturPembelian')
@section('navbar')
    @include('layouts.erm.navbar')
@endsection  
@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Daftar Faktur Pembelian</h4>
            <a href="{{ route('erm.fakturbeli.create') }}" class="btn btn-success">Tambah Faktur</a>
        </div>
        <div class="card-body">
            <table class="table table-bordered" id="fakturbeli-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>No Faktur</th>
                        <th>Pemasok</th>
                        <th>Tanggal Terima</th>
                        <th>Tanggal Kirim</th>
                        <th>Catatan</th>
                        <th>Bukti</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
$(function() {
    $('#fakturbeli-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route('erm.fakturbeli.index') }}',
        columns: [
            { data: 'id', name: 'id' },
            { data: 'no_faktur', name: 'no_faktur' },
            { data: 'pemasok', name: 'pemasok' },
            { data: 'received_date', name: 'received_date' },
            { data: 'ship_date', name: 'ship_date' },
            { data: 'notes', name: 'notes' },
            { data: 'bukti', name: 'bukti', render: function(data) {
                return data ? `<a href='/storage/${data}' target='_blank'>Lihat</a>` : '-';
            }},
            { data: 'action', name: 'action', orderable: false, searchable: false },
        ]
    });
    // Delete handler
    $('#fakturbeli-table').on('click', '.btn-delete-faktur', function() {
        if(confirm('Yakin ingin menghapus faktur ini?')) {
            let id = $(this).data('id');
            $.ajax({
                url: '/erm/fakturpembelian/' + id,
                type: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(res) {
                    if(res.success) {
                        alert(res.message);
                        $('#fakturbeli-table').DataTable().ajax.reload();
                    }
                },
                error: function() {
                    alert('Gagal menghapus faktur!');
                }
            });
        }
    });
});
</script>
@endpush
