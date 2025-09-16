@extends('layouts.erm.app')

@section('title', 'Kartu Stok')

@section('navbar')
    @include('layouts.erm.navbar-farmasi')
@endsection

@section('content')
<div class="container-fluid mt-4">
    <div class="card">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between">
            <h4>Kartu Stok</h4>
            <div class="d-flex align-items-center">
                <div class="form-check mr-3">
                    <input class="form-check-input" type="checkbox" id="onlyWithTransactions">
                    <label class="form-check-label" for="onlyWithTransactions">
                        Hanya yang ada transaksi
                    </label>
                </div>
                <input type="text" id="mainDateRange" class="form-control" style="min-width:220px;" readonly />
            </div>
        </div>
        <div class="card-body">
            <table class="table table-bordered" id="kartuStokTable">
                <thead>
                    <tr>
                        <th>Nama Obat</th>
                        <th>Masuk</th>
                        <th>Keluar</th>
                        <th>Detail</th>
                    </tr>
                </thead>
            </table>
            <!-- Modal -->
            <div class="modal fade" id="detailModal" tabindex="-1" role="dialog" aria-labelledby="detailModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl" role="document">
                    <div class="modal-content">
                        <div class="modal-header bg-light">
                            <div>
                                <h5 class="modal-title mb-1" id="detailModalLabel">Detail Kartu Stok</h5>
                                <small class="text-muted" id="detailModalSubtitle">Riwayat transaksi stok dalam periode yang dipilih</small>
                            </div>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                            <div id="detailModalContent">
                                <div class="text-center py-4">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="sr-only">Loading...</span>
                                    </div>
                                    <p class="mt-2 text-muted">Memuat data transaksi...</p>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer bg-light">
                            <small class="text-muted mr-auto">
                                <i class="fas fa-info-circle"></i> 
                                Transaksi diurutkan berdasarkan tanggal (terbaru ke terlama)
                            </small>
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function() {
    var mainDateRange = $('#mainDateRange');
    var mainDefaultStart = moment().startOf('month');
    var mainDefaultEnd = moment().endOf('month');
    mainDateRange.daterangepicker({
        locale: { format: 'YYYY-MM-DD' },
        startDate: mainDefaultStart,
        endDate: mainDefaultEnd,
        autoUpdateInput: true,
        opens: 'left',
        ranges: {
            'Today': [moment(), moment()],
            'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
            'Last 7 Days': [moment().subtract(6, 'days'), moment()],
            'Last 30 Days': [moment().subtract(29, 'days'), moment()],
            'This Month': [moment().startOf('month'), moment().endOf('month')],
            'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
        }
    });

    var table = $('#kartuStokTable').DataTable({
        processing: true,
        serverSide: false,
        ajax: {
            url: '{{ url('/erm/kartu-stok/data') }}',
            data: function(d) {
                var drp = mainDateRange.data('daterangepicker');
                d.start = drp.startDate.format('YYYY-MM-DD');
                d.end = drp.endDate.format('YYYY-MM-DD');
                d.only_with_transactions = $('#onlyWithTransactions').is(':checked') ? 1 : 0;
            },
            dataSrc: 'data'
        },
        columns: [
            { data: 'nama_obat', name: 'nama_obat' },
            { data: 'masuk', name: 'masuk' },
            { data: 'keluar', name: 'keluar' },
            { data: 'detail', name: 'detail', orderable: false, searchable: false }
        ]
    });

    mainDateRange.on('apply.daterangepicker', function(ev, picker) {
        table.ajax.reload();
    });

    $('#onlyWithTransactions').on('change', function() {
        table.ajax.reload();
    });

    // Handle detail button click
    var lastObatId = null;
    function loadDetailKartuStok(obatId) {
        var drp = mainDateRange.data('daterangepicker');
        var start = drp.startDate.format('YYYY-MM-DD');
        var end = drp.endDate.format('YYYY-MM-DD');
        $('#detailModalContent').html('<div class="text-center"><span class="spinner-border"></span> Loading...</div>');
        $.ajax({
            url: '{{ url('/erm/kartu-stok/detail') }}',
            method: 'GET',
            data: { obat_id: obatId, start: start, end: end },
            success: function(res) {
                $('#detailModalContent').html(res);
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error, xhr.responseText);
                $('#detailModalContent').html('<div class="text-danger text-center">Gagal memuat detail. Silakan cek koneksi atau hubungi admin.<br>AJAX Error: ' + error + '</div>');
            }
        });
    }

    $('#kartuStokTable').on('click', '.btn-detail', function() {
        lastObatId = $(this).data('obat-id');
        var namaObat = $(this).closest('tr').find('td:first').text();
        var drp = mainDateRange.data('daterangepicker');
        var periode = drp.startDate.format('DD/MM/YYYY') + ' - ' + drp.endDate.format('DD/MM/YYYY');
        
        $('#detailModalLabel').html('<i class="fas fa-pills"></i> Detail Kartu Stok: ' + namaObat);
        $('#detailModalSubtitle').text('Periode: ' + periode);
        $('#detailModal').modal('show');
        loadDetailKartuStok(lastObatId);
    });
});
</script>
@endpush
