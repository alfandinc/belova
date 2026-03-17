@extends('layouts.finance.app')
@section('title', 'Finance | Transaksi')
@section('navbar')
    @include('layouts.finance.navbar')
@endsection
@section('content')
<div class="container-fluid">
    <div class="card shadow-sm">
        <div class="card-header">
            <h4 class="card-title mb-0">Daftar Transaksi</h4>
        </div>
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-end mb-3" style="gap:.5rem;">
                <div style="flex:0 0 260px;">
                    <input type="text" id="daterange-transactions" class="form-control form-control-sm" />
                </div>
                <div style="flex:0 0 180px;">
                    <select id="filter-jenis-transaksi" class="form-control form-control-sm">
                        <option value="">Semua Jenis</option>
                        <option value="in">In</option>
                        <option value="out">Out</option>
                    </select>
                </div>
                <div style="flex:0 0 180px;">
                    <select id="filter-metode-bayar" class="form-control form-control-sm">
                        <option value="">Semua Metode</option>
                        <option value="cash">Tunai</option>
                        <option value="piutang">Piutang</option>
                        <option value="edc_bca">EDC BCA</option>
                        <option value="edc_bni">EDC BNI</option>
                        <option value="edc_bri">EDC BRI</option>
                        <option value="edc_mandiri">EDC Mandiri</option>
                        <option value="qris">QRIS</option>
                        <option value="transfer">Transfer</option>
                        <option value="shopee">Shopee</option>
                        <option value="tiktokshop">Tiktokshop</option>
                        <option value="tokopedia">Tokopedia</option>
                        <option value="asuransi_inhealth">Asuransi InHealth</option>
                        <option value="asuransi_brilife">Asuransi Brilife</option>
                        <option value="asuransi_admedika">Asuransi Admedika</option>
                        <option value="asuransi_bcalife">Asuransi BCA Life</option>
                    </select>
                </div>
            </div>

            <div class="table-responsive">
                <table id="datatable-transactions" class="table table-bordered table-striped" style="width:100%">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Tanggal</th>
                            <th>Nama Pasien</th>
                            <th>No Invoice</th>
                            <th>Jumlah</th>
                            <th>Jenis Transaksi</th>
                            <th>Metode Bayar</th>
                            <th>Deskripsi</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(function() {
        var startDate = moment().startOf('month').format('YYYY-MM-DD');
        var endDate = moment().endOf('month').format('YYYY-MM-DD');
        var jenisTransaksi = '';
        var metodeBayar = '';

        $('#daterange-transactions').daterangepicker({
            startDate: moment().startOf('month'),
            endDate: moment().endOf('month'),
            locale: {
                format: 'DD MMMM YYYY',
                applyLabel: 'Pilih',
                cancelLabel: 'Batal',
                fromLabel: 'Dari',
                toLabel: 'Hingga',
                customRangeLabel: 'Custom Range',
                weekLabel: 'W',
                daysOfWeek: ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'],
                monthNames: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
                firstDay: 1
            },
            ranges: {
               'Hari Ini': [moment(), moment()],
               'Kemarin': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
               'Minggu Ini': [moment().startOf('week'), moment().endOf('week')],
               'Bulan Ini': [moment().startOf('month'), moment().endOf('month')],
               'Bulan Lalu': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
            }
        }, function(start, end) {
            startDate = start.format('YYYY-MM-DD');
            endDate = end.format('YYYY-MM-DD');
            transactionTable.ajax.reload();
        });

        var transactionTable = $('#datatable-transactions').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ route("finance.transactions.data") }}',
                data: function(d) {
                    d.start_date = startDate;
                    d.end_date = endDate;
                    d.jenis_transaksi = jenisTransaksi;
                    d.metode_bayar = metodeBayar;
                }
            },
            columns: [
                {
                    data: null,
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row, meta) {
                        return meta.row + meta.settings._iDisplayStart + 1;
                    }
                },
                { data: 'tanggal_display', name: 'tanggal' },
                { data: 'pasien_display', name: 'visitation.pasien.nama' },
                { data: 'invoice_display', name: 'invoice_id' },
                { data: 'jumlah_display', name: 'jumlah' },
                { data: 'jenis_transaksi_display', name: 'jenis_transaksi' },
                { data: 'metode_bayar_display', name: 'metode_bayar' },
                { data: 'deskripsi', name: 'deskripsi' }
            ],
            order: [[1, 'desc']],
            language: {
                emptyTable: 'Belum ada transaksi',
                info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ transaksi',
                search: 'Cari:',
                paginate: {
                    first: 'Pertama',
                    last: 'Terakhir',
                    next: 'Selanjutnya',
                    previous: 'Sebelumnya'
                },
                lengthMenu: 'Tampilkan _MENU_ transaksi'
            }
        });

        $('#filter-jenis-transaksi').on('change', function() {
            jenisTransaksi = $(this).val();
            transactionTable.ajax.reload();
        });

        $('#filter-metode-bayar').on('change', function() {
            metodeBayar = $(this).val();
            transactionTable.ajax.reload();
        });
    });
</script>
@endsection