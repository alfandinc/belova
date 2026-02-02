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
                                    <th>Principal</th>
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
                                    <th>No Invoice</th>
                                    <th>Nama Pasien</th>
                                    <th>No Resep</th>
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
        <div class="col-12 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white d-flex align-items-center">
                    <i class="fa fa-history mr-2"></i>
                    <span>Laporan Stok Obat pada Tanggal Tertentu</span>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-3">Lihat stok obat pada tanggal tertentu. Perhitungan berdasarkan stok hari ini dikurangi stok keluar dari periode tanggal yang dipilih sampai hari ini.</p>
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label for="selected-date" class="font-weight-bold"><i class="fa fa-calendar mr-1"></i>Pilih Tanggal</label>
                            <input type="date" id="selected-date" class="form-control" value="">
                        </div>
                        <div class="col-md-3">
                            <label for="filter-kategori" class="font-weight-bold"><i class="fa fa-tags mr-1"></i>Kategori</label>
                            <select id="filter-kategori" class="form-control">
                                <option value="">Semua Kategori</option>
                                @foreach($kategoris as $kategori)
                                    <option value="{{ $kategori }}">{{ $kategori }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <span class="text-muted small">Pilih tanggal dan kategori untuk filter stok obat.</span>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <a href="#" id="export-stok-excel" class="btn btn-success btn-sm" disabled><i class="fa fa-file-excel-o mr-1"></i>Download Excel</a>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table id="datatable-stok-tanggal" class="table table-bordered table-hover table-sm">
                            <thead class="thead-light">
                                <tr>
                                    <th>Kode Obat</th>
                                    <th>Nama Obat</th>
                                    <th>Kategori</th>
                                    <th>Satuan</th>
                                    <th>Stok pada Tanggal</th>
                                    <th>Stok Saat Ini</th>
                                    <th>Status</th>
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

    // Set default selected date to empty (no default date)
    $('#selected-date').val('');

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
            { data: 'invoice_number', name: 'invoice_number' },
            { data: 'nama_pasien', name: 'nama_pasien' },
            { data: 'no_resep', name: 'no_resep' },
            { data: 'nama_obat', name: 'nama_obat' },
            { data: 'harga_jual', name: 'harga_jual' },
            { data: 'quantity', name: 'quantity' },
            { data: 'diskon_nominal', name: 'diskon_nominal' },
            { data: 'diskon_persen', name: 'diskon_persen' },
            { data: 'diskon_pelayanan', name: 'diskon_pelayanan' },
        ]
    });

    // Stock by date table
    var tableStokTanggal = $('#datatable-stok-tanggal').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route('laporan.farmasi.stok-tanggal') }}',
            data: function(d) {
                d.selected_date = $('#selected-date').val();
                d.kategori = $('#filter-kategori').val();
            }
        },
        columns: [
            { data: 'kode_obat', name: 'kode_obat' },
            { data: 'nama_obat', name: 'nama_obat' },
            { data: 'kategori', name: 'kategori' },
            { data: 'satuan', name: 'satuan' },
            { data: 'stok_on_date', name: 'stok_on_date', className: 'text-right' },
            { data: 'stok_current', name: 'stok_current', className: 'text-right' },
            { data: 'status_stok', name: 'status_stok', className: 'text-center' },
        ],
        order: [[ 1, 'asc' ]] // Sort by nama_obat
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

    // Reload stock table when date changes
    $('#selected-date').on('change', function() {
        var selectedDate = $(this).val();
        if (selectedDate) {
            tableStokTanggal.ajax.reload();
            // Enable export button when date is selected
            $('#export-stok-excel').removeClass('disabled').removeAttr('disabled');
        } else {
            // Clear table if no date selected
            tableStokTanggal.clear().draw();
            // Disable export button when no date selected
            $('#export-stok-excel').addClass('disabled').attr('disabled', 'disabled');
        }
    });

    // Reload stock table when kategori filter changes
    $('#filter-kategori').on('change', function() {
        var selectedDate = $('#selected-date').val();
        if (selectedDate) {
            tableStokTanggal.ajax.reload();
        }
    });

    // Export stok tanggal to Excel
    $('#export-stok-excel').on('click', function(e) {
        e.preventDefault();
        var selectedDate = $('#selected-date').val();
        if (!selectedDate) {
            alert('Silakan pilih tanggal terlebih dahulu');
            return;
        }
        var kategori = $('#filter-kategori').val();
        var url = '{{ route('laporan.farmasi.stok-tanggal.excel') }}';
        url += '?selected_date=' + encodeURIComponent(selectedDate);
        if (kategori) {
            url += '&kategori=' + encodeURIComponent(kategori);
        }
        window.location.href = url;
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
            { data: 'principal', name: 'principal' },
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
