@extends('layouts.laporan.app')
@section('title', 'laporan | Laporan farmasi')
@section('navbar')
    @include('layouts.laporan.navbar')
@endsection  

@section('content')
<div class="container mt-4">
    <h1 class="mb-4">Laporan Rekap Pembelian Farmasi</h1>
    <div class="mb-3 d-flex align-items-center gap-2">
        <input type="text" id="date-range" class="form-control" style="max-width:220px; display:inline-block;" placeholder="Filter tanggal">
        <a href="{{ route('laporan.farmasi.excel') }}" class="btn btn-success ms-2">Export Excel</a>
        <a href="{{ route('laporan.farmasi.pdf') }}" class="btn btn-danger ms-2">Export PDF</a>
    </div>
    <div class="table-responsive mb-5">
        <table id="datatable-rekap" class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Nama Pemasok</th>
                    <th>Nama Obat</th>
                    <th>Harga Beli/Satuan</th>
                    <th>Diskon</th>
                    <th>Harga Jadi (Setelah Diskon + PPN)</th>
                </tr>
            </thead>
        </table>
    </div>

    <h2 class="mb-3">Laporan Rekap Penjualan Obat</h2>
    <div class="mb-3 d-flex align-items-center gap-2">
        <a href="#" id="export-penjualan-excel" class="btn btn-success">Export Excel</a>
        <a href="#" id="export-penjualan-pdf" class="btn btn-danger">Export PDF</a>
    </div>
    <div class="table-responsive">
        <table id="datatable-penjualan" class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Nama Obat</th>
                    <th>Harga Jual</th>
                    <th>Diskon Obat Saat Pelayanan</th>
                </tr>
            </thead>
        </table>
    </div>

</div>
@push('scripts')
<script>
$(function() {
    // Date range picker
    $('#date-range').daterangepicker({
        locale: { format: 'YYYY-MM-DD' },
        autoUpdateInput: false,
        opens: 'left',
    });

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
            { data: 'diskon', name: 'diskon' },
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
