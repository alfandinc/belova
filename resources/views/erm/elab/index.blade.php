@extends('layouts.erm.app')
@section('title', 'ERM | E-Laboratorium')
@section('navbar')
    @include('layouts.erm.navbar-lab')
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
                        <th>Nominal</th>
                        <th>Tanggal Kunjungan</th>
                        <th>Metode Bayar</th>
                        <th>Lab</th>
                    </tr>
                </thead>
            </table>
            <!-- Total nominal card -->
            <div class="row mt-3">
                <div class="col-md-4 offset-md-8">
                    <div class="card border-primary">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0">Total Nominal</h6>
                                <small class="text-muted">(berdasarkan filter)</small>
                            </div>
                            <div>
                                <h5 id="total-nominal" class="mb-0">Rp 0</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
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
            { data: 'nominal', searchable: false, orderable: false, render: function(data, type, row) {
                    // data is numeric nominal (sum of harga). Format to 'Rp 1.000'
                    if (!data) return 'Rp 0';
                    return 'Rp ' + Number(data).toLocaleString('id-ID');
                }
            },
            // Format tanggal_visitation using moment.js to 'D MMMM YYYY' (e.g. '1 Januari 2025')
            { data: 'tanggal_visitation', name: 'tanggal_periksa', render: function(data, type, row) {
                    if (!data) return '-';
                    // Ensure moment has Indonesian locale available; format and capitalize month
                    try {
                        moment.locale('id');
                        let formatted = moment(data).format('D MMMM YYYY');
                        // Capitalize first letter of month (moment with 'id' returns lowercase months)
                        // e.g. '1 januari 2025' -> '1 Januari 2025'
                        return formatted.replace(/\b([a-z])/g, function(m) { return m.toUpperCase(); });
                    } catch (e) {
                        return data;
                    }
                }
            },
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

    // Helper to format number to Indonesian Rupiah
    function formatRupiah(number) {
        if (!number) return 'Rp 0';
        return 'Rp ' + Number(number).toLocaleString('id-ID');
    }

    // Update total nominal display. Prefer server-provided aggregated total if available
    function updateTotalNominal(json) {
        // If server returned aggregated total (total_nominal), use it
        if (json && json.total_nominal !== undefined && json.total_nominal !== null) {
            $('#total-nominal').text(formatRupiah(json.total_nominal));
            return;
        }

        // Otherwise, sum the nominal column from the currently displayed rows
        let api = table.api ? table.api() : table; // support different DataTables init
        let data = api.rows({ page: 'current' }).data();
        let sum = 0;
        for (let i = 0; i < data.length; i++) {
            let val = data[i].nominal;
            if (!val) continue;
            // ensure numeric
            let num = Number(val);
            if (!isNaN(num)) sum += num;
        }
        $('#total-nominal').text(formatRupiah(sum));
    }

    // When table draws (paging, filter, sort, initial load), update total
    table.on('draw.dt', function(e, settings) {
        // If server-side processing with returned JSON, the draw event's settings.json may contain our payload
        // DataTables exposes the last JSON in settings.json
        let json = settings.json || null;
        updateTotalNominal(json);
    });

    // Also intercept the ajax response to capture server-provided total_nominal when available
    $.fn.dataTable.ext.errMode = 'throw';
    // If using DataTables ajax option as object, we can provide a dataSrc wrapper
    // Instead, attach a global ajax handler for this specific table's ajax
    $(document).on('xhr.dt', '#rawatjalan-table', function(e, settings, json, xhr) {
        updateTotalNominal(json);
    });

    // Event ganti rentang tanggal
    $('#filter_tanggal_range').on('apply.daterangepicker cancel.daterangepicker', function(ev, picker) {
        table.ajax.reload();
    });
});
</script>
@endsection