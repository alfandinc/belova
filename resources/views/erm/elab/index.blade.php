@extends('layouts.erm.app')
@section('title', 'ERM | E-Laboratorium')
@section('navbar')
    @include('layouts.erm.navbar')
@endsection  

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
                            <li class="breadcrumb-item active">Laboratorium</li>
                            <li class="breadcrumb-item">E-Lab</li>
                        </ol>
                    </div><!--end col-->
                </div><!--end row-->                                                              
            </div><!--end page-title-box-->
        </div><!--end col-->
    </div><!--end row-->
    <!-- end page title end breadcrumb -->

    <div class="card">
        <div class="card-header bg-primary">
            <h4 class="card-title text-white">Daftar Kunjungan Laboratorium</h4>
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
                        <th>No RM</th>
                        <th>Nama Pasien</th>
                        <th>Tanggal Kunjungan</th>
                        <th>Metode Bayar</th>
                        <th>Lab</th>
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
            url: '{{ route("erm.elab.index") }}',
            data: function(d) {
                d.tanggal = $('#filter_tanggal').val();
            }
        },
        columns: [
            { data: 'no_rm', searchable: false, orderable: false },
            { data: 'nama_pasien', searchable: false, orderable: false },
            { data: 'tanggal_visitation', name: 'tanggal_periksa' },
            { data: 'metode_bayar', searchable: false, orderable: false },
            { data: 'dokumen', searchable: false, orderable: false },
            { data: 'status_kunjungan', visible: false, searchable: false },
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
});
</script>
@endsection