@extends('layouts.hrd.app')

@section('title','Workdoc - Memorandum')

@section('navbar')
    @include('layouts.hrd.navbar')
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">Memorandum</h4>
                    <div>
                        <a href="{{ route('workdoc.memorandum.create') }}" class="btn btn-primary">Buat Memorandum</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="memorandumTable" class="table table-striped table-bordered" style="width:100%">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Tanggal</th>
                                    <th>Nomor</th>
                                    <th>Perihal</th>
                                    <th>Dari Divisi</th>
                                    <th>Kepada</th>
                                    <th>Klinik</th>
                                    <th>Status</th>
                                    <th>Dibuat Oleh</th>
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
@endsection

@push('scripts')
<script>
$(function(){
    const table = $('#memorandumTable').DataTable({
        ajax: {
            url: '{{ route('workdoc.memorandum.data') }}',
            dataSrc: 'data'
        },
        columns: [
            {data: 'id'},
            {data: 'tanggal'},
            {data: 'nomor_memo'},
            {data: 'perihal'},
            {data: 'division'},
            {data: 'kepada'},
            {data: 'klinik'},
            {data: 'status'},
            {data: 'user'},
            {data: null, render: function(row){
                const editUrl = '{{ route('workdoc.memorandum.edit', ['memorandum' => 'MEMO_ID']) }}'.replace('MEMO_ID', row.id);
                const pdfUrl = '{{ route('workdoc.memorandum.print_pdf', ['memorandum' => 'MEMO_ID']) }}'.replace('MEMO_ID', row.id);
                return '<a class="btn btn-sm btn-info" href="'+editUrl+'">Edit</a>'+
                       ' <a class="btn btn-sm btn-secondary" target="_blank" href="'+pdfUrl+'">PDF</a>'+
                       ' <button class="btn btn-sm btn-danger deleteMemo" data-id="'+row.id+'">Delete</button>';
            }}
        ]
    });

    $('#memorandumTable').on('click', '.deleteMemo', function(){
        const id = $(this).data('id');
        Swal.fire({
            title: 'Hapus memorandum?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, hapus',
            cancelButtonText: 'Batal'
        }).then(function(result){
            if(result.isConfirmed){
                $.post('{{ url('/workdoc/memorandums') }}/'+id, { _method: 'DELETE', _token: '{{ csrf_token() }}' })
                    .done(function(resp){
                        Swal.fire({icon:'success', title:'Terhapus', text: resp.message || 'Memorandum dihapus'});
                        table.ajax.reload(null,false);
                    })
                    .fail(function(){
                        Swal.fire({icon:'error', title:'Error', text:'Gagal menghapus'});
                    });
            }
        });
    });
});
</script>
@endpush