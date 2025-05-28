@extends('layouts.erm.app')
@section('title', 'ERM | Rawat Jalan')
@section('navbar')
    @include('layouts.erm.navbar')
@endsection  

@section('content')

@include('erm.partials.modal-reschedule')



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
                @if ($role !== 'Dokter')
                <div class="col-md-4">
                    <label for="filter_dokter">Filter Dokter</label>
                    <select id="filter_dokter" class="form-control select2">
                        <option value="">Semua Dokter</option>
                        @foreach($dokters as $dokter)
                            <option value="{{ $dokter->id }}">{{ $dokter->user->name }} - {{ $dokter->spesialisasi->nama }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
            </div>
            <table class="table table-bordered w-100" id="rawatjalan-table">
                <thead>
                    <tr>
                        <th>Antrian</th>
                        <th>No RM</th>
                        <th>Nama Pasien</th>
                        <th>Tanggal Kunjungan</th>                        
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

    $('.select2').select2({
        width: '100%' 
    });
    // Set default value tanggal ke hari ini
    var today = new Date().toISOString().substr(0, 10);
    $('#filter_tanggal').val(today);

    $.fn.dataTable.ext.order['antrian-number'] = function(settings, col) {
        return this.api().column(col, {order: 'index'}).nodes().map(function(td, i) {
            return parseInt($('span', td).data('order')) || 0;
        });
    };

    let table = $('#rawatjalan-table').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: {
            url: '{{ route("erm.rawatjalans.index") }}',
            data: function(d) {
                d.tanggal = $('#filter_tanggal').val();
                d.dokter_id = $('#filter_dokter').val();
            }
        },
        order: [[3, 'asc'], [0, 'asc']], // Tanggal ASC, Antrian ASC
        columns: [
            { data: 'antrian', name: 'no_antrian', searchable: true, orderable: true },
            { data: 'no_rm', name: 'no_rm', searchable: true, orderable: false }, // Match alias
            { data: 'nama_pasien', name: 'nama_pasien', searchable: true, orderable: false }, // Match alias
            { data: 'tanggal', name: 'tanggal_visitation', searchable: true },
            { data: 'metode_bayar', name: 'metode_bayar', searchable: true, orderable: false },
            { data: 'dokumen', name: 'dokumen', searchable: false, orderable: false },
        ],
        columnDefs: [
            { targets: 0, width: "5%" }, // Antrian
            { targets: 5, width: "25%" }, // Dokumen
        ],
        createdRow: function(row, data, dataIndex) {
            if (data.status_kunjungan == 2) {
                $(row).css('color', 'orange'); 
            }
        }
    });

    // Event ganti tanggal
    $('#filter_tanggal').on('change', function () {
        table.ajax.reload();
    });
    $('#filter_dokter').on('change', function () {
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
