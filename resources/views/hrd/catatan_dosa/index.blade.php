@extends('layouts.hrd.app')
@section('title', 'Catatan Dosa')
@section('navbar')
    @include('layouts.hrd.navbar')
@endsection
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title">Catatan Dosa</h4>
                    <button class="btn btn-primary" id="btnAddCatatan">Tambah Catatan</button>
                </div>
                <div class="card-body">
                    <table id="catatanDosaTable" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Pegawai</th>
                                <th>Jenis Pelanggaran</th>
                                <th>Kategori</th>
                                <th>Status Tindaklanjut</th>
                                <th>Waktu</th>
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
<div class="modal fade" id="catatanModal" tabindex="-1" role="dialog" aria-labelledby="catatanModalLabel" aria-hidden="true">
<div class="modal-dialog modal-lg" role="document">
    <form id="catatanForm" enctype="multipart/form-data">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="catatanModalLabel">Tambah Catatan Dosa</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id" id="catatan_id">
          <div class="form-row">
            <div class="form-group col-md-6">
              <label for="employee_id">Pegawai</label>
              <select name="employee_id" id="employee_id" class="form-control" required>
                <option value="">Pilih Pegawai</option>
                @foreach($employees as $emp)
                  <option value="{{ $emp->id }}">{{ $emp->nama }}</option>
                @endforeach
              </select>
            </div>
            <div class="form-group col-md-6">
              <label for="jenis_pelanggaran">Jenis Pelanggaran</label>
              <select name="jenis_pelanggaran" id="jenis_pelanggaran" class="form-control" required>
                  <option value="">Pilih Jenis Pelanggaran</option>
                  <option value="Keterlambatan">Keterlambatan</option>
                  <option value="Tidak hadir">Tidak hadir</option>
                  <option value="Etika">Etika</option>
                  <option value="Penyalahgunaan">Penyalahgunaan</option>
                  <option value="Lainnya">Lainnya</option>
              </select>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group col-md-6">
              <label for="kategori">Kategori</label>
              <select name="kategori" id="kategori" class="form-control" required>
                  <option value="">Pilih Kategori</option>
                  <option value="Ringan">Ringan</option>
                  <option value="Sedang">Sedang</option>
                  <option value="Berat">Berat</option>
              </select>
            </div>
            <div class="form-group col-md-6">
              <label for="bukti">Bukti (jpg, png, pdf)</label>
              <input type="file" name="bukti" id="bukti" class="form-control">
              <div id="buktiPreview" class="mt-2"></div>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group col-md-6">
              <label for="status_tindaklanjut">Status Tindaklanjut</label>
              <select name="status_tindaklanjut" id="status_tindaklanjut" class="form-control" required>
                  <option value="">Pilih Status</option>
                  <option value="sudah dibina">Sudah dibina</option>
                  <option value="dalam proses">Dalam proses</option>
                  <option value="sudah ditindak">Sudah ditindak</option>
                  <option value="dihapus">Dihapus</option>
                  <option value="diabaikan">Diabaikan</option>
              </select>
            </div>
            <div class="form-group col-md-6">
              <label for="tindakan">Tindakan</label>
              <select name="tindakan" id="tindakan" class="form-control" required>
                  <option value="">Pilih Tindakan</option>
                  <option value="SP1">SP1</option>
                  <option value="SP2">SP2</option>
                  <option value="Diskusi">Diskusi</option>
                  <option value="Skorsing">Skorsing</option>
                  <option value="Pemotongan Gaji">Pemotongan Gaji</option>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label for="deskripsi">Deskripsi</label>
            <textarea name="deskripsi" id="deskripsi" class="form-control" required></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary" id="saveCatatanBtn">Simpan</button>
        </div>
      </div>
    </form>
  </div>
</div>
@endsection
@section('scripts')
<script>
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': '{{ csrf_token() }}'
    }
});
$(document).ready(function() {
    // Enable Select2 for all selects in modal
    $('#employee_id, #jenis_pelanggaran, #kategori, #status_tindaklanjut, #tindakan').select2({
        dropdownParent: $('#catatanModal'),
        width: '100%'
    });
    var table = $('#catatanDosaTable').DataTable({
        ajax: {
            url: '{{ route('hrd.catatan-dosa.index') }}',
            dataSrc: 'data'
        },
        columns: [
            { data: null, render: function (data, type, row, meta) {
                return meta.row + 1;
            }, orderable: false },
            { data: 'employee.nama', defaultContent: '-' },
            { data: 'jenis_pelanggaran' },
            { data: 'kategori' },
            { data: 'status_tindaklanjut', render: function(data) {
                let color = 'secondary';
                if (data === 'sudah dibina' || data === 'sudah ditindak') color = 'success';
                else if (data === 'dalam proses') color = 'warning';
                else if (data === 'dihapus' || data === 'diabaikan') color = 'danger';
                return `<span class="badge badge-${color}">${data}</span>`;
            } },
            { data: 'timestamp' },
            { data: null, render: function(data, type, row) {
                return `<button class='btn btn-sm btn-info editCatatan' data-id='${row.id}' title='Edit'><i class='fas fa-eye'></i></button>
                        <button class='btn btn-sm btn-danger deleteCatatan' data-id='${row.id}' title='Hapus'><i class='fas fa-trash'></i></button>`;
            }, orderable: false }
        ]
    });

    // Open modal for create
    $('#btnAddCatatan').click(function() {
        $('#catatanForm')[0].reset();
        $('#catatan_id').val('');
        $('#buktiPreview').html('');
        $('#catatanModalLabel').text('Tambah Catatan Dosa');
        // Reset select2 values
        $('#employee_id, #jenis_pelanggaran, #kategori, #status_tindaklanjut, #tindakan').val('').trigger('change');
        $('#catatanModal').modal('show');
    });

    // Edit
    $('#catatanDosaTable').on('click', '.editCatatan', function() {
        var id = $(this).data('id');
        $.get(`{{ url('hrd/catatan-dosa') }}/${id}`, function(res) {
            var d = res.data;
            $('#catatan_id').val(d.id);
            $('#employee_id').val(d.employee_id).trigger('change');
            $('#jenis_pelanggaran').val(d.jenis_pelanggaran).trigger('change');
            $('#kategori').val(d.kategori).trigger('change');
            $('#deskripsi').val(d.deskripsi);
            $('#status_tindaklanjut').val(d.status_tindaklanjut).trigger('change');
            $('#tindakan').val(d.tindakan).trigger('change');
            if (d.bukti) {
                $('#buktiPreview').html(`<a href='/storage/${d.bukti}' target='_blank'>Lihat Bukti</a>`);
            } else {
                $('#buktiPreview').html('');
            }
            $('#catatanModalLabel').text('Edit Catatan Dosa');
            $('#catatanModal').modal('show');
        });
    });

    // Save (Create/Update)
    $('#catatanForm').submit(function(e) {
        e.preventDefault();
        var formData = new FormData(this);
        var id = $('#catatan_id').val();
        var url = id ? `{{ url('hrd/catatan-dosa') }}/${id}` : `{{ route('hrd.catatan-dosa.store') }}`;
        $.ajax({
            url: url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
                $('#catatanModal').modal('hide');
                table.ajax.reload();
                Swal.fire('Berhasil!', 'Data berhasil disimpan.', 'success');
            },
            error: function(xhr) {
                var err = xhr.responseJSON.errors;
                var msg = '';
                $.each(err, function(k, v) { msg += v+'<br>'; });
                Swal.fire('Gagal!', msg, 'error');
            }
        });
    });

    // Delete
    $('#catatanDosaTable').on('click', '.deleteCatatan', function() {
        var id = $(this).data('id');
        Swal.fire({
            title: 'Yakin hapus?',
            text: 'Data akan dihapus permanen!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.value) {
                $.ajax({
                    url: `{{ url('hrd/catatan-dosa') }}/${id}`,
                    type: 'DELETE',
                    data: {_token: '{{ csrf_token() }}'},
                    success: function(res) {
                        table.ajax.reload();
                        Swal.fire('Berhasil!', 'Data berhasil dihapus.', 'success');
                    },
                    error: function() {
                        Swal.fire('Gagal!', 'Terjadi kesalahan.', 'error');
                    }
                });
            }
        });
    });
});
</script>
@endsection
