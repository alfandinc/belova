@extends('layouts.erm.app')
@section('title', 'ERM | Rawat Jalan')
@section('navbar')
    @include('layouts.erm.navbar')
@endsection  

@section('content')
<!-- Modal Reschedule -->
<div class="modal fade" id="modalReschedule" tabindex="-1" role="dialog" aria-labelledby="modalLabelReschedule" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <form id="form-reschedule">
      @csrf
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="modalLabelReschedule">Jadwal Ulang Kunjungan</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true"><i class="la la-times"></i></span>
          </button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="pasien_id" id="reschedule-pasien-id">
          <input type="hidden" name="visitation_id" id="reschedule-visitation-id">

          <div class="form-group">
            <label>Nama Pasien</label>
            <input type="text" id="reschedule-nama-pasien" class="form-control" readonly>
          </div>

          <div class="form-group">
            <label>Dokter</label>
            <select id="reschedule-dokter-id" name="dokter_id" class="form-control select2" required>
              <option value="" disabled selected>Pilih Dokter</option>
              @foreach($dokters as $dokter)
                  <option value="{{ $dokter->id }}">{{ $dokter->user->name }} - {{ $dokter->spesialisasi->nama }}</option>
              @endforeach
            </select>
          </div>

          <div class="form-group">
            <label>Tanggal Kunjungan</label>
            <input type="date" class="form-control" id="reschedule-tanggal-visitation" name="tanggal_visitation" required>
          </div>

          <div class="form-group">
            <label>No Antrian</label>
            <input type="text" name="no_antrian" id="reschedule-no-antrian" class="form-control" readonly>
          </div>

        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Simpan Jadwal Ulang</button>
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
                            <li class="breadcrumb-item active">Rawat Jalan</li>
                        </ol>
                    </div><!--end col-->
                </div><!--end row-->                                                              
            </div><!--end page-title-box-->
        </div><!--end col-->
    </div><!--end row-->
    <!-- end page title end breadcrumb -->

    <div class="card">
        <div class="card-header bg-primary">
            <h4 class="card-title text-white">Daftar Kunjungan Rawat Jalan</h4>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="filter_tanggal">Filter Tanggal Kunjungan</label>
                    <input type="date" id="filter_tanggal" class="form-control">
                </div>
            </div>
            <table class="table table-bordered w-100" id="rawatjalan-table">
                <thead>
                    <tr>
                        <th>Antrian</th>
                        <th>No RM</th>
                        <th>Nama Pasien</th>
                        <th>Tanggal Kunjungan</th>
                        <th>Status</th>
                        <th>Metode Bayar</th>
                        <th>Dokumen</th>
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
    // Set default value tanggal ke hari ini
    var today = new Date().toISOString().substr(0, 10);
    $('#filter_tanggal').val(today);

    let table = $('#rawatjalan-table').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: {
            url: '{{ route("erm.rawatjalans.index") }}',
            data: function(d) {
                d.tanggal = $('#filter_tanggal').val();
            }
        },
        order: [[3, 'asc'], [0, 'asc']], // Tanggal ASC, Antrian ASC
        columns: [
            { data: 'antrian', name: 'no_antrian', searchable: false, orderable: true },
            { data: 'no_rm', searchable: false, orderable: false },
            { data: 'nama_pasien', searchable: false, orderable: false },
            { data: 'tanggal', name: 'tanggal_visitation' },
            { data: 'status_dokumen', name: 'status_dokumen' },
            { data: 'metode_bayar', searchable: false, orderable: false },
            { data: 'dokumen', searchable: false, orderable: false },
            { data: 'progress', visible: false, searchable: false }, // 🛠️ Sembunyikan
        ],
        columnDefs: [
        { targets: 0, width: "5%" }, // Antrian
        { targets: 6, width: "20%" }, // Dokumen
    ],
        createdRow: function(row, data, dataIndex) {
        if (data.progress == 3) {
            $(row).css('color', 'orange'); // Warna teks kuning/orange
            // Kalau mau kasih background juga bisa:
            // $(row).css('background-color', '#fff3cd');
        }
    }
    });

    // Event ganti tanggal
    $('#filter_tanggal').on('change', function () {
        table.ajax.reload();
    });

    // ambil no antrian otomatis
    $('#reschedule-dokter-id, #reschedule-tanggal-visitation').on('change', function() {
        let dokterId = $('#reschedule-dokter-id').val();
        let tanggal = $('#reschedule-tanggal-visitation').val();

        if (dokterId && tanggal) {
            $.get('{{ route("erm.rawatjalans.cekAntrian") }}', { dokter_id: dokterId, tanggal: tanggal }, function(res) {
                $('#reschedule-no-antrian').val(res.no_antrian);
            });
        }
    });

    // submit form reschedule
    $('#form-reschedule').submit(function(e) {
        e.preventDefault();

        $.ajax({
            url: '{{ route("erm.rawatjalans.store") }}',
            method: 'POST',
            data: $(this).serialize(),
            success: function(res) {
                $('#modalReschedule').modal('hide');
                $('#rawatjalan-table').DataTable().ajax.reload();
                alert(res.message);
            },
            error: function(xhr) {
                alert('Terjadi kesalahan!');
            }
        });
    });
});

// 🛠️ Fungsi openRescheduleModal dibuat di luar $(document).ready supaya global
function openRescheduleModal(visitationId, namaPasien, pasienId) {
    $('#modalReschedule').modal('show');
    $('#reschedule-visitation-id').val(visitationId);
    $('#reschedule-pasien-id').val(pasienId);
    $('#reschedule-nama-pasien').val(namaPasien);
}
</script>


@endsection
