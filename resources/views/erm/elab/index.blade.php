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
                <div class="col-md-5">
                    <label for="filter_tanggal_range">Filter Rentang Tanggal Kunjungan</label>
                    <input type="text" id="filter_tanggal_range" class="form-control" autocomplete="off" placeholder="Pilih rentang tanggal">
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
    // Date range picker for filter
    $('#filter_tanggal_range').daterangepicker({
        autoUpdateInput: true,
        locale: {
            format: 'YYYY-MM-DD',
            cancelLabel: 'Clear',
            applyLabel: 'Terapkan',
            fromLabel: 'Dari',
            toLabel: 'Sampai',
            customRangeLabel: 'Custom',
            daysOfWeek: ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'],
            monthNames: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
            firstDay: 1
        },
        opens: 'left',
        startDate: moment().startOf('day'),
        endDate: moment().startOf('day'),
        maxDate: moment(),
    });

    let table = $('#rawatjalan-table').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: {
            url: '{{ route("erm.elab.index") }}',
            data: function(d) {
                let range = $('#filter_tanggal_range').val();
                if (range) {
                    let dates = range.split(' - ');
                    d.tanggal_start = dates[0];
                    d.tanggal_end = dates[1] ? dates[1] : dates[0];
                }
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

    // Event ganti rentang tanggal
    $('#filter_tanggal_range').on('apply.daterangepicker cancel.daterangepicker', function(ev, picker) {
        table.ajax.reload();
    });
});
</script>
@endsection