@extends('layouts.erm.app')
@section('title', 'Master Pemasok')
@section('navbar')
    @include('layouts.erm.navbar')
@endsection
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">Master Pemasok</h4>
                    <div>
                        <button class="btn btn-success mr-2" id="btnExportExcel">Export Excel</button>
                        <button class="btn btn-primary" id="btnAddPemasok">Tambah Pemasok</button>
                    </div>
                </div>
                <div class="card-body">
                    <table id="pemasokTable" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>Alamat</th>
                                <th>Telepon</th>
                                <th>Email</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Data will be loaded via AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="pemasokModal" tabindex="-1" role="dialog" aria-labelledby="pemasokModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">

        <h5 class="modal-title" id="pemasokModalLabel">Tambah Pemasok</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form id="pemasokForm">
        <div class="modal-body">
            <input type="hidden" id="pemasokId" name="id">
            <div class="form-group">
                <label for="nama">Nama</label>
                <input type="text" class="form-control" id="nama" name="nama" required>
            </div>
            <div class="form-group">
                <label for="alamat">Alamat</label>
                <input type="text" class="form-control" id="alamat" name="alamat">
            </div>
            <div class="form-group">
                <label for="telepon">Telepon</label>
                <input type="text" class="form-control" id="telepon" name="telepon">
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" class="form-control" id="email" name="email">
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
@section('scripts')
<script>
$(document).ready(function() {
    var table = $('#pemasokTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '/erm/pemasok', // Endpoint to fetch data
            type: 'GET'
        },
        columns: [
            { data: 'nama' },
            { data: 'alamat' },
            { data: 'telepon' },
            { data: 'email' },
            { data: 'aksi', orderable: false, searchable: false }
        ]
    });

    $('#btnAddPemasok').click(function() {
        $('#pemasokForm')[0].reset();
        $('#pemasokId').val('');
        $('#pemasokModalLabel').text('Tambah Pemasok');
        $('#pemasokModal').modal('show');
    });

    // Submit form (add/edit)
    $('#pemasokForm').submit(function(e) {
        e.preventDefault();
        var id = $('#pemasokId').val();
        var url = id ? '/erm/pemasok/' + id : '/erm/pemasok';
        var method = id ? 'PUT' : 'POST';
        $.ajax({
            url: url,
            type: method,
            data: $(this).serialize(),
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function(res) {
                $('#pemasokModal').modal('hide');
                table.ajax.reload();
                Swal.fire('Sukses', res.message, 'success');
            },
            error: function(xhr) {
                Swal.fire('Error', xhr.responseJSON.message || 'Terjadi kesalahan', 'error');
            }
        });
    });

    // Edit button
    $('#pemasokTable').on('click', '.btn-edit', function() {
        var data = $(this).data('pemasok');
        $('#pemasokId').val(data.id);
        $('#nama').val(data.nama);
        $('#alamat').val(data.alamat);
        $('#telepon').val(data.telepon);
        $('#email').val(data.email);
        $('#pemasokModalLabel').text('Edit Pemasok');
        $('#pemasokModal').modal('show');
    });

    // Delete button
    $('#pemasokTable').on('click', '.btn-delete', function() {
        var id = $(this).data('id');
        Swal.fire({
            title: 'Hapus Pemasok?',
            text: 'Data yang dihapus tidak dapat dikembalikan!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.value) {
                $.ajax({
                    url: '/erm/pemasok/' + id,
                    type: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    success: function(res) {
                        table.ajax.reload();
                        Swal.fire('Sukses', res.message, 'success');
                    },
                    error: function(xhr) {
                        Swal.fire('Error', xhr.responseJSON.message || 'Terjadi kesalahan', 'error');
                    }
                });
            }
        });
    });

                                        // Export to Excel
                                    $('#btnExportExcel').click(function() {
                                        window.location.href = '/erm/pemasok/export-excel';
                                    });
});
</script>
@endsection
