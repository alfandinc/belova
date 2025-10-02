@extends('layouts.laporan.app')
@section('title', 'laporan | Laporan farmasi')
@section('navbar')
    @include('layouts.laporan.navbar')
@endsection  

@section('content')


<div class="container-fluid mt-4">
    <!-- Global Filter Bar -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="bg-light p-3 rounded shadow-sm d-flex align-items-center flex-wrap">
                <label for="date-range" class="mb-0 mr-2 font-weight-bold"><i class="fa fa-calendar mr-1"></i>Filter Tanggal</label>
                <input type="text" id="date-range" class="form-control mr-2" style="max-width:200px;" placeholder="Pilih rentang tanggal">
                <span class="text-muted small">Filter ini berlaku untuk semua laporan di bawah</span>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-12 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white d-flex align-items-center">
                    <i class="fa fa-clipboard-list mr-2"></i>
                    <span>Laporan Rekap Pembelian Farmasi</span>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-3">Rekap pembelian obat dari pemasok/pbf, lengkap dengan harga, diskon, dan harga jadi.</p>
                    <div class="mb-3">
                        <a href="{{ route('laporan.farmasi.excel') }}" class="btn btn-success btn-sm mr-1"><i class="fa fa-file-excel-o mr-1"></i>Excel</a>
                        <a href="{{ route('laporan.farmasi.pdf') }}" class="btn btn-danger btn-sm"><i class="fa fa-file-pdf-o mr-1"></i>PDF</a>
                    </div>
                    <div class="table-responsive">
                        <table id="datatable-rekap" class="table table-bordered table-hover table-sm">
                            <thead class="thead-light">
                                <tr>
                                    <th>Nama Pemasok</th>
                                    <th>Nama Obat</th>
                                    <th>Harga Beli/Satuan</th>
                                    <th>Quantity</th>
                                    <th>Diskon Nominal</th>
                                    <th>Diskon (%)</th>
                                    <th>Harga Jadi<br><small>(Setelah Diskon + PPN)</small></th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white d-flex align-items-center">
                    <i class="fa fa-pills mr-2"></i>
                    <span>Laporan Rekap Penjualan Obat</span>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-3">Rekap penjualan obat ke pasien, termasuk harga jual dan diskon saat pelayanan.</p>
                    <div class="mb-3">
                        <a href="#" id="export-penjualan-excel" class="btn btn-success btn-sm mr-1"><i class="fa fa-file-excel-o mr-1"></i>Excel</a>
                        <a href="#" id="export-penjualan-pdf" class="btn btn-danger btn-sm"><i class="fa fa-file-pdf-o mr-1"></i>PDF</a>
                    </div>
                    <div class="table-responsive">
                        <table id="datatable-penjualan" class="table table-bordered table-hover table-sm">
                            <thead class="thead-light">
                                <tr>
                                    <th>Nama Obat</th>
                                    <th>Harga Jual</th>
                                    <th>Quantity</th>
                                    <th>Diskon Nominal</th>
                                    <th>Diskon (%)</th>
                                    <th>Diskon Obat Saat Pelayanan</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script>
$(function() {
    // Date range picker

    var today = moment().format('YYYY-MM-DD');
    $('#date-range').daterangepicker({
        locale: { format: 'YYYY-MM-DD' },
        autoUpdateInput: true,
        startDate: today,
        endDate: today,
        opens: 'left',
    });
    $('#date-range').val(today + ' - ' + today);

    var tablePenjualan = $('#datatable-penjualan').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route('laporan.farmasi.penjualan-obat') }}',
            data: function(d) {
                d.date_range = $('#date-range').val();
            }
        },
        columns: [
            { data: 'nama_obat', name: 'nama_obat' },
            { data: 'harga_jual', name: 'harga_jual' },
            { data: 'quantity', name: 'quantity' },
            { data: 'diskon_nominal', name: 'diskon_nominal' },
            { data: 'diskon_persen', name: 'diskon_persen' },
            { data: 'diskon_pelayanan', name: 'diskon_pelayanan' },
        ]
    });

    $('#date-range').on('apply.daterangepicker', function(ev, picker) {
        $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
        table.ajax.reload();
        tablePenjualan.ajax.reload();
    });
    $('#date-range').on('cancel.daterangepicker', function(ev, picker) {
        $(this).val('');
        table.ajax.reload();
        tablePenjualan.ajax.reload();
    });

    var table = $('#datatable-rekap').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: window.location.href,
            data: function(d) {
                d.date_range = $('#date-range').val();
            }
        },
        columns: [
            { data: 'nama_pemasok', name: 'nama_pemasok' },
            { data: 'nama_obat', name: 'nama_obat' },
            { data: 'harga_beli', name: 'harga_beli' },
            { data: 'quantity', name: 'quantity' },
            { data: 'diskon_nominal', name: 'diskon_nominal' },
            { data: 'diskon_persen', name: 'diskon_persen' },
            { data: 'harga_jadi', name: 'harga_jadi' },
        ]
    });

        // Export buttons for penjualan obat
    $('#export-penjualan-excel').on('click', function(e) {
        e.preventDefault();
        var dateRange = $('#date-range').val();
        var url = '{{ route('laporan.farmasi.penjualan-obat.excel') }}';
        if (dateRange) url += '?date_range=' + encodeURIComponent(dateRange);
        window.location.href = url;
    });
    $('#export-penjualan-pdf').on('click', function(e) {
        e.preventDefault();
        var dateRange = $('#date-range').val();
        var url = '{{ route('laporan.farmasi.penjualan-obat.pdf') }}';
        if (dateRange) url += '?date_range=' + encodeURIComponent(dateRange);
        window.location.href = url;
    });

    // No filter button needed; filtering is automatic
});
</script>
@endpush
@endsection
