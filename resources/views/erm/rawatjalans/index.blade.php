@extends('layouts.erm.app')
@section('title', 'ERM | Rawat Jalan')

@section('content')
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
        <div class="card-header bg-info">
            <h4 class="card-title text-white">Daftar Kunjungan Rawat Jalan</h4>
        </div>
        <div class="card-body">
            <div class="row mb-3">
        <div class="col-md-4">
            <label for="filter_tanggal">Filter Tanggal Kunjungan</label>
            <input type="date" id="filter_tanggal" class="form-control">
        </div>
    </div>
            <table class="table table-bordered" id="rawatjalan-table">
                <thead>
                    <tr>
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
    $('#filter_tanggal').daterangepicker({
        singleDatePicker: true,
        showDropdowns: true,
        autoUpdateInput: false, // ini harus false agar kita bisa kontrol manual
        locale: {
            format: 'YYYY-MM-DD',
            cancelLabel: 'Clear'
        }
    });

    // Saat tanggal dipilih
    $('#filter_tanggal').on('apply.daterangepicker', function (ev, picker) {
        $(this).val(picker.startDate.format('YYYY-MM-DD')); // isi nilai input
        $('#rawatjalan-table').DataTable().ajax.reload(); // reload datatable
    });

    // Saat tombol clear ditekan
    $('#filter_tanggal').on('cancel.daterangepicker', function (ev, picker) {
        $(this).val('');
        $('#rawatjalan-table').DataTable().ajax.reload(); // reload ulang juga
    });

    // Inisialisasi DataTable
    let table = $('#rawatjalan-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("erm.rawatjalans.index") }}',
            data: function(d) {
                d.tanggal = $('#filter_tanggal').val(); // ambil isi input
            }
        },
        columns: [
    { data: 'id', name: 'pasien.id' },
    { data: 'nama_pasien', name: 'pasien.nama' },
    { data: 'tanggal', name: 'tanggal_visitation' },
    { data: 'status', name: 'status' },
    { data: 'metode_bayar', name: 'metodeBayar.nama' }, // update di sini
    { data: 'dokumen', name: 'dokumen', orderable: false, searchable: false },
]
    });
});
</script>
@endsection
