@extends('layouts.erm.app')
@section('title', 'Master Principal')
@section('navbar')
    @include('layouts.erm.navbar-farmasi')
@endsection
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">Master Principal</h4>
                    <div>
                        <button class="btn btn-success mr-2" id="btnExportExcel">Export Excel</button>
                        <button class="btn btn-primary" id="btnAddPrincipal">Tambah Principal</button>
                    </div>
                </div>
                <div class="card-body">
                    <table id="principalTable" class="table table-bordered table-striped">
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
<div class="modal fade" id="principalModal" tabindex="-1" role="dialog" aria-labelledby="principalModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">

        <h5 class="modal-title" id="principalModalLabel">Tambah Principal</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form id="principalForm">
        <div class="modal-body">
            <input type="hidden" id="principalId" name="id">
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
    var table = $('#principalTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '/erm/principal', // Endpoint to fetch data
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

    $('#btnAddPrincipal').click(function() {
        $('#principalForm')[0].reset();
        $('#principalId').val('');
        $('#principalModalLabel').text('Tambah Principal');
        $('#principalModal').modal('show');
    });

    // Submit form (add/edit)
    $('#principalForm').submit(function(e) {
        e.preventDefault();
        var id = $('#principalId').val();
        var url = id ? '/erm/principal/' + id : '/erm/principal';
        var method = id ? 'PUT' : 'POST';
        $.ajax({
            url: url,
            type: method,
            data: $(this).serialize(),
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function(res) {
                $('#principalModal').modal('hide');
                table.ajax.reload();
                Swal.fire('Sukses', res.message, 'success');
            },
            error: function(xhr) {
                Swal.fire('Error', xhr.responseJSON.message || 'Terjadi kesalahan', 'error');
            }
        });
    });

    // Edit button
    $('#principalTable').on('click', '.btn-edit', function() {
        var data = $(this).data('principal');
        $('#principalId').val(data.id);
        $('#nama').val(data.nama);
        $('#alamat').val(data.alamat);
        $('#telepon').val(data.telepon);
        $('#email').val(data.email);
        $('#principalModalLabel').text('Edit Principal');
        $('#principalModal').modal('show');
    });

    // Delete button
    $('#principalTable').on('click', '.btn-delete', function() {
        var id = $(this).data('id');
        Swal.fire({
            title: 'Hapus Principal?',
            text: 'Data yang dihapus tidak dapat dikembalikan!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.value) {
                $.ajax({
                    url: '/erm/principal/' + id,
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
        window.location.href = '/erm/principal/export-excel';
    });
});
</script>
@endsection
