
@extends('layouts.erm.app')
@section('title', 'ERM | Master Faktur Pembelian')
@section('navbar')
    @include('layouts.erm.navbar')
@endsection  



@section('content')

<style>
@keyframes blink {
    0%, 100% { opacity: 1; }
    50% { opacity: 0; }
}
.blink-warning {
    animation: blink 1s linear infinite;
}
</style>
<div class="container-fluid">

        <!-- Title and Button Row -->
    <div class="row mt-3 align-items-center">
        <div class="col-md-6">
            <h2 class="mb-0">Monitor Profit Obat</h2>
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
                            <li class="breadcrumb-item active">Monitor Profit Obat</li>
                        </ol>
                    </div><!--end col-->
                </div><!--end row-->                                                              
            </div><!--end page-title-box-->
        </div><!--end col-->
    </div><!--end row-->
    <!-- end page title end breadcrumb -->
    <div class="row">
        <div class="col-12">            
                    <div class="mb-2 d-flex justify-content-end">
                        <span class="badge badge-info" style="font-size:1rem !important; font-weight:500; padding:0.35em 0.7em; letter-spacing:0.2px; box-shadow:0 1px 4px rgba(0,0,0,0.08); border-radius:0.35em; line-height:1.2;">PPN yang berlaku: <b style="font-size:1.05rem !important;">11%</b></span>
                    </div>
                    <table id="monitor-profit-table" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Kode Obat</th>
                                <th>Nama Obat</th>
                                <th>HPP</th>
                                <th>HPP Jual</th>
                                <th>Saran Harga Jual<br><small>(HPP Jual × 1.3 × 1.11)</small></th>
                                <th>Harga Jual</th>
                                <th>Profit (%)<br><small>(Sebelum PPN)</small></th>
                                <th>Profit (%) Setelah PPN</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
        </div>
    </div>
</div>

<!-- Modal Edit Harga Jual -->
<div class="modal fade" id="editHargaModal" tabindex="-1" role="dialog" aria-labelledby="editHargaModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="form-edit-harga">
                <div class="modal-header">
                    <h5 class="modal-title" id="editHargaModalLabel">Edit Harga Jual</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id">
                    <div class="form-group">
                        <label for="harga_nonfornas">Harga Jual (Nonfornas)</label>
                        <input type="number" class="form-control" name="harga_nonfornas" required min="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection



@push('scripts')
<script>
$(function() {
    $('#monitor-profit-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route('erm.monitor-profit') }}',
            type: 'GET'
        },
    columns: [
        { data: 'kode_obat', name: 'kode_obat' },
        { data: 'nama', name: 'nama' },
        { data: 'hpp', name: 'hpp' },
        { data: 'hpp_jual', name: 'hpp_jual' },
        { data: 'saran_harga_jual', name: 'saran_harga_jual', orderable: false, searchable: false },
        { data: 'harga_nonfornas', name: 'harga_nonfornas', title: 'Harga Jual' },
    { data: 'profit_percent', name: 'profit_percent', orderable: true, searchable: false },
    { data: 'profit_percent_setelah_ppn', name: 'profit_percent_setelah_ppn', orderable: false, searchable: false },
        { data: 'aksi', name: 'aksi', orderable: false, searchable: false }
    ],
    order: [[6, 'desc']]
    });

        // Handle edit button click
        $(document).on('click', '.btn-edit-harga', function() {
                var id = $(this).data('id');
                var nama = $(this).data('nama');
                var harga = $(this).data('harga');
                $('#editHargaModal input[name="id"]').val(id);
                $('#editHargaModal .modal-title').text('Edit Harga Jual: ' + nama);
                $('#editHargaModal input[name="harga_nonfornas"]').val(harga);
                $('#editHargaModal').modal('show');
        });

        // Handle form submit
        $('#form-edit-harga').on('submit', function(e) {
                e.preventDefault();
                var form = $(this);
                var id = form.find('input[name="id"]').val();
                var harga = form.find('input[name="harga_nonfornas"]').val();
                $.ajax({
                        url: '/erm/obat/' + id + '/update-harga',
                        method: 'POST',
                        data: {
                                _token: '{{ csrf_token() }}',
                                harga_nonfornas: harga
                        },
                        success: function(res) {
                                $('#editHargaModal').modal('hide');
                                $('#monitor-profit-table').DataTable().ajax.reload(null, false);
                                toastr.success('Harga jual berhasil diperbarui');
                        },
                        error: function(xhr) {
                                toastr.error('Gagal memperbarui harga jual');
                        }
                });
        });
});
</script>
@endpush
