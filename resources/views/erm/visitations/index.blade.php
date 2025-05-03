@extends('layouts.erm.app')
@section('title', 'ERM | Daftarkan Kunjungan')
@section('navbar')
    @include('layouts.erm.navbar')
@endsection
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
              <input type="date" class="form-control" id="tanggal_visitation" name="tanggal_visitation" required>
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

            <div class="form-group">
                <label for="no_antrian">No Antrian</label>
                <input type="text" name="no_antrian" id="modal-no-antrian" class="form-control" readonly>
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
                    </div> 
                </div>                                                             
            </div>
        </div>
    </div>

    {{-- Table Pasien --}}
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
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function () {
    // Inisialisasi select2
    $('.select2').select2({ width: '100%' });

    // Inisialisasi datatable
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

    // Tampilkan modal saat klik tombol daftar kunjungan
    $(document).on('click', '.btn-daftar-visitation', function () {
        let pasienId = $(this).data('id');
        let namaPasien = $(this).data('nama');

        $('#modal-pasien-id').val(pasienId);
        $('#modal-nama-pasien').val(namaPasien);
        $('#modalKunjungan').modal('show');
    });

    // Submit form kunjungan
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

    // Cek No Antrian otomatis
    function cekAntrian() {
        let dokterId = $('#dokter_id').val();
        let tanggal = $('#tanggal_visitation').val();
        

        if (dokterId && tanggal) {
            console.log('dokter_id:', dokterId, 'tanggal:', tanggal);
            $.ajax({
                url: "{{ route('erm.visitations.cekAntrian') }}",
                type: 'GET',
                data: {
                    dokter_id: dokterId,
                    tanggal: tanggal
                },
                success: function(response) {
                    console.log('Response:', response);
                    $('#modal-no-antrian').val(response.no_antrian);
                },
                error: function(xhr) {
                    console.log(xhr.responseText);
                    $('#modal-no-antrian').val('Error');
                }
            });
        }
    }

    // Jalankan cekAntrian saat dokter atau tanggal berubah
    $('#dokter_id, #tanggal_visitation').on('change', function () {
        cekAntrian();
    });
});
</script>
@endsection
