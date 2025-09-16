@extends('layouts.erm.app')

@section('title', 'Stok Opname')
@section('navbar')
    @include('layouts.erm.navbar-farmasi')
@endsection

@section('content')
<div class="container-fluid">
    <div class="row mt-3 mb-3">
        <div class="col-md-12 d-flex align-items-center justify-content-between flex-wrap flex-md-nowrap">
            <h4 class="mb-0">Stok Opname</h4>
            <button class="btn btn-success" data-toggle="modal" data-target="#stokOpnameModal">Tambah Stok Opname</button>
        </div>
    </div>
    <table class="table table-bordered yajra-datatable">
        <thead>
      <tr>
        <th>No</th>
        <th>Tanggal Opname</th>
        <th>Gudang</th>
        <th>Periode</th>
        <th>Status</th>
        <th>Dibuat Oleh</th>
        <th>Obat Selisih</th>
        <th>Aksi</th>
      </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>

<!-- Modal Tambah Stok Opname -->
<div class="modal fade" id="stokOpnameModal" tabindex="-1" role="dialog" aria-labelledby="stokOpnameModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form id="stokOpnameForm">
        <div class="modal-header">
          <h5 class="modal-title" id="stokOpnameModalLabel">Tambah Stok Opname</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label for="tanggal_opname">Tanggal Opname</label>
            <input type="date" class="form-control" name="tanggal_opname" required>
          </div>
          <div class="form-group">
            <label for="gudang_id">Gudang</label>
            <select class="form-control" name="gudang_id" required>
              <option value="">Pilih Gudang</option>
              @foreach($gudangs as $gudang)
                <option value="{{ $gudang->id }}">{{ $gudang->nama }}</option>
              @endforeach
            </select>
          </div>
          <div class="form-group">
            <label for="periode_bulan">Periode Bulan</label>
            <select class="form-control" name="periode_bulan" required>
              <option value="">Pilih Bulan</option>
              <option value="1">Januari</option>
              <option value="2">Februari</option>
              <option value="3">Maret</option>
              <option value="4">April</option>
              <option value="5">Mei</option>
              <option value="6">Juni</option>
              <option value="7">Juli</option>
              <option value="8">Agustus</option>
              <option value="9">September</option>
              <option value="10">Oktober</option>
              <option value="11">November</option>
              <option value="12">Desember</option>
            </select>
          </div>
          <div class="form-group">
            <label for="periode_tahun">Periode Tahun</label>
            <input type="number" class="form-control" name="periode_tahun" min="2020" max="2100" required id="periode_tahun_input">
          </div>
          <div class="form-group">
            <label for="notes">Catatan</label>
            <textarea class="form-control" name="notes"></textarea>
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
<style>
@keyframes blink {
  0%, 100% { opacity: 1; }
  50% { opacity: 0; }
}
.blink-warning {
  animation: blink 1s linear infinite;
}
</style>
<script>
$(function () {
    // Set default year for periode_tahun input
    var now = new Date();
    $('#periode_tahun_input').val(now.getFullYear());
    var table = $('.yajra-datatable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('erm.stokopname.index') }}",
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
      {
        data: 'tanggal_opname',
        name: 'tanggal_opname',
        render: function(data) {
          if (!data) return '';
          var bulan = [
            '', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
          ];
          var d = new Date(data);
          var tgl = d.getDate();
          var bln = bulan[d.getMonth() + 1];
          var thn = d.getFullYear();
          return tgl + ' ' + bln + ' ' + thn;
        }
      },
      {data: 'gudang.nama', name: 'gudang.nama'},
      {data: null, render: function(data) {
        return data.periode_bulan + '/' + data.periode_tahun;
      }},
      {
        data: 'status',
        name: 'status',
        render: function(data) {
          if (data === 'draft') {
            return '<span class="badge badge-primary">Draft</span>';
          } else if (data === 'proses') {
            return '<span class="badge badge-warning text-dark">Proses</span>';
          } else if (data === 'selesai') {
            return '<span class="badge badge-success">Selesai</span>';
          } else {
            return data;
          }
        }
      },
      {data: 'user.name', name: 'user.name'},
      {
        data: 'selisih_count',
        name: 'selisih_count',
        orderable: false,
        searchable: false,
        render: function(data, type, row) {
          if (data > 0) {
            return data + ' Obat Selisih <i class="fa fa-exclamation-triangle text-warning blink-warning" title="Ada selisih"></i>';
          } else {
            return data + ' Obat Selisih <i class="fa fa-check text-success" title="Tidak ada selisih"></i>';
          }
        }
      },
      {data: 'aksi', name: 'aksi', orderable: false, searchable: false},
    ]
    });

    $('#stokOpnameForm').submit(function(e){
        e.preventDefault();
        $.ajax({
            url: "{{ route('erm.stokopname.store') }}",
            method: 'POST',
            data: $(this).serialize(),
            headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
            success: function(res){
                $('#stokOpnameModal').modal('hide');
                table.ajax.reload();
            },
            error: function(xhr){
                alert('Gagal menyimpan data!');
            }
        });
    });
});
</script>
@endpush
