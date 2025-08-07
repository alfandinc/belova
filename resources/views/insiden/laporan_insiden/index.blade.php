@extends('layouts.insiden.app')
@section('title', 'Laporan Insiden')
@section('navbar')
    @include('layouts.insiden.navbar')
@endsection

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12">
            <a href="{{ route('insiden.laporan_insiden.create') }}" class="btn btn-primary"><i class="fa fa-plus"></i> Tambah Laporan Insiden</a>
        </div>
    </div>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <table class="table table-bordered table-striped" id="laporanInsidenTable" style="width:100%">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Pasien</th>
                                <th>Tanggal dan Waktu Insiden</th>
                                <th>Jenis Insiden</th>
                                <th>Lokasi</th>
                                <th>Grading</th>
                                <th>Pembuat</th>
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
<!-- Modal for Diterima -->
<div class="modal fade" id="modalDiterima" tabindex="-1" role="dialog" aria-labelledby="modalDiterimaLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalDiterimaLabel">Terima Laporan Insiden</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form id="formDiterima">
        <div class="modal-body">
          <input type="hidden" name="laporan_id" id="diterima_laporan_id">
          <div class="form-group">
            <label for="grading_resiko">Grading Resiko</label>
            <select name="grading_resiko" id="grading_resiko" class="form-control" required>
              <option value="">Pilih Grading</option>
              <option value="Biru">Biru</option>
              <option value="Hijau">Hijau</option>
              <option value="Kuning">Kuning</option>
              <option value="Merah">Merah</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-success">Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('scripts')



<script>
$(function() {
    // Diterima button handler
    $('#laporanInsidenTable').on('click', '.btn-diterima-laporan', function() {
        var id = $(this).data('id');
        $('#diterima_laporan_id').val(id);
        $('#grading_resiko').val('');
        $('#modalDiterima').modal('show');
    });

    // Handle Diterima form submit
    $('#formDiterima').on('submit', function(e) {
        e.preventDefault();
        var id = $('#diterima_laporan_id').val();
        var grading = $('#grading_resiko').val();
        if (!grading) {
            Swal.fire('Pilih grading terlebih dahulu!');
            return;
        }
        $.ajax({
            url: '/insiden/laporan_insiden/' + id + '/diterima',
            type: 'POST',
            data: {
                grading_resiko: grading,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(res) {
                $('#modalDiterima').modal('hide');
                Swal.fire('Berhasil', 'Status laporan diubah ke Diterima.', 'success');
                table.ajax.reload(null, false);
            },
            error: function(xhr) {
                Swal.fire('Gagal', 'Terjadi kesalahan saat menyimpan.', 'error');
            }
        });
    });
    var table = $('#laporanInsidenTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route('insiden.laporan_insiden.data') }}',
        columns: [
            {
                data: null,
                name: 'no',
                orderable: false,
                searchable: false,
                render: function (data, type, row, meta) {
                    return meta.row + meta.settings._iDisplayStart + 1;
                }
            },
            { data: 'pasien.nama', name: 'pasien.nama' },
            {
                data: 'tanggal_insiden',
                render: function(data, type, row) {
                    if (!data) return '';
                    // Format: 1 Januari 2025 pukul 16.00
                    return moment(data).locale('id').format('D MMMM YYYY [pukul] HH.mm');
                }
            },
            { data: 'jenis_insiden' },
            { data: 'lokasi_insiden' },
            {
                data: 'grading_resiko',
                name: 'grading_resiko',
                render: function(data, type, row) {
                    if (!data) return '';
                    var color = '';
                    switch (data) {
                        case 'Biru': color = '#007bff'; break;
                        case 'Hijau': color = '#28a745'; break;
                        case 'Kuning': color = '#ffc107'; break;
                        case 'Merah': color = '#dc3545'; break;
                        default: color = '#6c757d';
                    }
                    return '<span style="color: #fff; background:' + color + '; padding:2px 10px; border-radius:10px; display:inline-block; min-width:60px; text-align:center;">' + data + '</span>';
                }
            },
            { data: 'pembuat_laporan_nama', orderable: false, searchable: false },
            { data: 'action', orderable: false, searchable: false }
        ]
    });

    // Edit button event (delegated, still using modal or you can change to full page)
    // $('#laporanInsidenTable').on('click', '.btn-edit', function() {
    //     var id = $(this).data('id');
    //     $.get('/insiden/laporan_insiden/' + id + '/edit', function(html) {
    //         $('#modalLaporanInsidenBody').html(html);
    //         $('#modalLaporanInsiden').modal('show');
    //     });
    // });
    // Delete button handler
    $('#laporanInsidenTable').on('click', '.btn-delete-laporan', function() {
        var id = $(this).data('id');
        Swal.fire({
            title: 'Hapus Laporan?',
            text: 'Data yang dihapus tidak dapat dikembalikan.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.value) {
                $.ajax({
                    url: '/insiden/laporan_insiden/' + id,
                    type: 'DELETE',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(res) {
                        Swal.fire('Berhasil!', 'Laporan berhasil dihapus.', 'success');
                        table.ajax.reload(null, false);
                    },
                    error: function(xhr) {
                        Swal.fire('Gagal', 'Terjadi kesalahan saat menghapus.', 'error');
                    }
                });
            }
        });
    });
});
// Set moment.js to Indonesian locale
if (typeof moment !== 'undefined') {
    moment.locale('id');
}
</script>
@endpush
