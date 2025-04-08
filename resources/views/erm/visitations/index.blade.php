@extends('layouts.erm.app')
@section('title', 'ERM | Daftarkan Kunjungan')

@section('content')
<!-- Modal Daftar Kunjungan -->
<div class="modal fade" id="modalKunjungan" tabindex="-1" role="dialog" aria-labelledby="modalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <form id="form-kunjungan">
      @csrf
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="modalLabel">Daftarkan Kunjungan Pasien</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true"><i class="la la-times"></i></span>
          </button>
        </div>
        <div class="modal-body">
            <input type="hidden" name="pasien_id" id="modal-pasien-id">
            <div class="form-group">
              <label for="nama_pasien">Nama Pasien</label>
              <input type="text" id="modal-nama-pasien" class="form-control" readonly>
            </div>

            <div class="form-group">
                <label for="dokter_id">Dokter</label>
                <select class="form-control select2" id="dokter_id" name="dokter_id" required>
                    <option value="" selected disabled>Select Dokter</option>
                    @foreach($dokters as $dokter)
        <option value="{{ $dokter->id }}">
            {{ $dokter->user->name }} - {{ $dokter->spesialisasi->nama }}
        </option>
    @endforeach
                </select>
            </div>

            <div class="form-group">
              <label for="tanggal_visitation">Tanggal Kunjungan</label>
                <div class="input-group">
                    <input type="text" class="form-control" id="tanggal_visitation" name="tanggal_visitation" placeholder="Select date" required>
                    <div class="input-group-append">
                        <span class="input-group-text">
                            <i class="fas fa-calendar-alt"></i>
                        </span>
                    </div>
                </div>
            </div>
            <div class="form-group">
              <label for="metode_bayar_id">Cara Bayar</label>
              <select class="form-control select2" id="metode_bayar_id" name="metode_bayar_id" required>
                  <option value="" selected disabled>Pilih Metode Bayar</option>
                  @foreach($metodeBayar as $metode)
                      <option value="{{ $metode->id }}">{{ $metode->nama }}</option>
                  @endforeach
              </select>
            </div>

        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Simpan</button>
        </div>
      </div>
    </form>
  </div>
</div>
<div class="container-fluid">
    <!-- Page-Title -->
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="row">
                    <div class="col">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:void(0);">ERM</a></li>
                            <li class="breadcrumb-item active">Pasien</li>
                        </ol>
                    </div><!--end col-->
                    >  
                </div><!--end row-->                                                              
            </div><!--end page-title-box-->
        </div><!--end col-->
    </div><!--end row-->
    <!-- end page title end breadcrumb -->

    {{-- Table Pasien  --}}
    <div class="card">
        <div class="card-header bg-primary">
            <h4 class="card-title text-white">Daftarkan Kunjungan Pasien</h4>
        </div>
        <div class="card-body">
            <table class="table table-bordered" id="pasiens-table">
                <thead>
                    <tr>
                        <th>No RM</th>
                        <th>Name</th>
                        <th>NIK</th>
                        <th>Alamat</th>
                        <th>No HP</th>
                        <th>Action</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div><!-- container -->
@endsection



@section('scripts')
<script>
$(document).ready(function() {
    $('#pasiens-table').DataTable({
    processing: true,
    serverSide: true,
    ajax: "{{ route('erm.visitations.index') }}",
    columns: [
        { data: 'id', name: 'id' },
        { data: 'nama', name: 'nama' },
        { data: 'nik', name: 'nik' },
        { data: 'alamat', name: 'alamat' },
        { data: 'no_hp', name: 'no_hp' },
        { data: 'actions', name: 'actions', orderable: false, searchable: false }
    ]
});
});
   $(document).ready(function () {
    $('.select2').select2({ width: '100%' });

    $('#tanggal_visitation').daterangepicker({
        singleDatePicker: true,
        showDropdowns: true,
        autoUpdateInput: false,
        locale: {
            format: 'YYYY-MM-DD',
            cancelLabel: 'Clear'
        }
    });

    $('#tanggal_visitation').on('apply.daterangepicker', function (ev, picker) {
        $(this).val(picker.startDate.format('YYYY-MM-DD'));
    });

    $('#tanggal_visitation').on('cancel.daterangepicker', function (ev, picker) {
        $(this).val('');
    });
});

$(document).ready(function () {
    // Handle klik tombol Daftarkan Kunjungan
    $(document).on('click', '.btn-daftar-visitation', function () {
        let pasienId = $(this).data('id');
        let namaPasien = $(this).data('nama');

        $('#modal-pasien-id').val(pasienId);
        $('#modal-nama-pasien').val(namaPasien);
        $('#modalKunjungan').modal('show');
    });

    // Submit form via AJAX
    $('#form-kunjungan').submit(function (e) {
        e.preventDefault();

        let formData = $(this).serialize();

        $.ajax({
            url: "{{ route('erm.visitations.store') }}",
            type: "POST",
            data: formData,
            success: function (res) {
                $('#modalKunjungan').modal('hide');
                $('#form-kunjungan')[0].reset();
                alert(res.message);
            },
            error: function (xhr) {
                alert("Terjadi kesalahan. Pastikan semua data valid.");
            }
        });
    });
});
</script>
@endsection

