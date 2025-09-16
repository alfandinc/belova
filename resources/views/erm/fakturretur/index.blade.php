@extends('layouts.erm.app')
@section('title', 'ERM | FakturPembelian')
@section('navbar')
    @include('layouts.erm.navbar-farmasi')
@endsection  
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">Daftar Retur Obat</h4>
                    <button class="btn btn-primary" id="btn-create-retur"><i class="fa fa-plus"></i> Ajukan Retur</button>
                </div>
                <div class="card-body">
                    <table id="returTable" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>No Retur</th>
                                <th>Tanggal</th>
                                <th>Faktur</th>
                                <th>Pemasok</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                           <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Create/Edit -->
<div class="modal fade" id="modalRetur" tabindex="-1" role="dialog" aria-labelledby="modalReturLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalReturLabel">Ajukan Retur Obat</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="modalReturBody">
                <!-- Form akan di-load via AJAX -->
            </div>
        </div>
    </div>
</div>
<!-- Modal Detail/Approve -->
<div class="modal fade" id="modalDetailRetur" tabindex="-1" role="dialog" aria-labelledby="modalDetailReturLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalDetailReturLabel">Detail Retur Obat</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="modalDetailReturBody">
                <!-- Detail akan di-load via AJAX -->
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function() {
    var table = $('#returTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route('erm.fakturretur.index') }}',
        columns: [
            { data: 'no_retur', name: 'no_retur' },
            { data: 'tanggal_retur', name: 'tanggal_retur' },
            { data: 'fakturbeli.no_faktur', name: 'fakturbeli.no_faktur' },
            { data: 'pemasok.nama', name: 'pemasok.nama' },
            { data: 'status', name: 'status' },
            { data: 'action', name: 'action', orderable: false, searchable: false },
        ]
    });

    // Show modal create
    $('#btn-create-retur').on('click', function() {
        $.ajax({
            url: '{{ route('erm.fakturretur.create') }}',
            type: 'GET',
            success: function(res) {
                $('#modalReturBody').html(res);
                $('#modalRetur').modal('show');
            }
        });
    });

    // Handle form submit (delegated)
    $(document).on('submit', '#formRetur', function(e) {
        e.preventDefault();
        var form = $(this);
        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: form.serialize(),
            success: function(res) {
                if(res.success) {
                    $('#modalRetur').modal('hide');
                    table.ajax.reload();
                    toastr.success(res.message);
                } else {
                    toastr.error(res.message);
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Terjadi kesalahan');
            }
        });
    });

    // Show modal detail
        $(document).on('click', '.btn-detail-retur', function(e) {
            e.preventDefault();
            var id = $(this).data('id');
            $.ajax({
                url: '/erm/fakturretur/' + id,
                type: 'GET',
                success: function(res) {
                    $('#modalDetailReturBody').html(res);
                    $('#modalDetailRetur').modal('show');
                },
                error: function(xhr) {
                    toastr.error('Gagal load detail retur');
                }
            });
        });

    // Handle approve in modal
    $(document).on('click', '#btn-approve-retur', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        Swal.fire({
            title: 'Approve Retur?',
            text: 'Yakin ingin approve retur ini?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Approve',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.value) {
                $.post('/erm/fakturretur/' + id + '/approve', {_token: $('meta[name="csrf-token"]').attr('content')}, function(res) {
                    if(res.success) {
                        $('#modalDetailRetur').modal('hide');
                        table.ajax.reload();
                        Swal.fire('Berhasil!', res.message, 'success');
                    } else {
                        Swal.fire('Gagal!', res.message, 'error');
                    }
                }).fail(function(xhr) {
                    Swal.fire('Error!', xhr.responseJSON?.message || 'Terjadi kesalahan', 'error');
                });
            }
        });
    });

});
</script>
@endpush
