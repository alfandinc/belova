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
            <div class="mb-3 d-flex justify-content-end">
                <button id="exportExcelBtn" class="btn btn-success btn-sm mr-2"><i class="fas fa-file-excel"></i> Export Excel</button>
                <button id="exportStokTerakhirBtn" class="btn btn-primary btn-sm"><i class="fas fa-download"></i> Export Stok Terakhir</button>
            </div>
            <table class="table table-bordered" id="kartuStokTable">
                <thead>
                    <tr>
                        <th>Nama Obat</th>
                        <th>Masuk</th>
                        <th>Keluar</th>
                        <th>Total Stok Terakhir</th>
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
            <!-- Reference detail modal (loads faktur or invoice) -->
            <div class="modal fade" id="refDetailModal" tabindex="-1" role="dialog" aria-labelledby="refDetailModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header bg-light">
                            <h5 class="modal-title" id="refDetailModalLabel">Detail Referensi</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body" id="refDetailModalContent" style="max-height:70vh; overflow:auto;">
                            <div class="text-center py-4">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="sr-only">Loading...</span>
                                </div>
                                <p class="mt-2 text-muted">Memuat detail...</p>
                            </div>
                        </div>
                        <div class="modal-footer bg-light">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Analytics modal (monthly masuk/keluar summary) -->
            <div class="modal fade" id="analyticsModal" tabindex="-1" role="dialog" aria-labelledby="analyticsModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header bg-light">
                            <h5 class="modal-title" id="analyticsModalLabel"><i class="fas fa-chart-line"></i> Analytics Kartu Stok</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body" id="analyticsModalContent" style="max-height:70vh; overflow:auto;">
                            <div class="text-center py-4">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="sr-only">Loading...</span>
                                </div>
                                <p class="mt-2 text-muted">Memuat analytics...</p>
                            </div>
                        </div>
                        <div class="modal-footer bg-light">
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
            { data: 'masuk', name: 'masuk', render: function(data, type, row) { var v = (data !== undefined && data !== null) ? Number(data) : 0; return v.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 4 }); } },
            { data: 'keluar', name: 'keluar', render: function(data, type, row) { var v = (data !== undefined && data !== null) ? Number(data) : 0; return v.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 4 }); } },
            { data: 'stok_terakhir', name: 'stok_terakhir', render: function(data, type, row) { var v = (data !== undefined && data !== null) ? Number(data) : 0; return v.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 4 }); } },
            { data: 'detail', name: 'detail', orderable: false, searchable: false }
        ]
    });

    mainDateRange.on('apply.daterangepicker', function(ev, picker) {
        table.ajax.reload();
    });

    $('#onlyWithTransactions').on('change', function() {
        table.ajax.reload();
    });

    // Export button
    $('#exportExcelBtn').on('click', function() {
        var drp = mainDateRange.data('daterangepicker');
        var start = drp.startDate.format('YYYY-MM-DD');
        var end = drp.endDate.format('YYYY-MM-DD');
        // Build url and open in new tab to trigger download
        var url = '{{ url("/erm/kartu-stok/export") }}' + '?start=' + encodeURIComponent(start) + '&end=' + encodeURIComponent(end);
        // Provide brief feedback by disabling button while the download starts
        var $btn = $(this);
        $btn.prop('disabled', true).text('Preparing...');
        // Use window.location to trigger download
        window.location = url;
        setTimeout(function() { $btn.prop('disabled', false).html('<i class="fas fa-file-excel"></i> Export Excel'); }, 2000);
    });

    // Export stok terakhir summary
    $('#exportStokTerakhirBtn').on('click', function() {
        var drp = mainDateRange.data('daterangepicker');
        var start = drp.startDate.format('YYYY-MM-DD');
        var end = drp.endDate.format('YYYY-MM-DD');
        var url = '{{ url("/erm/kartu-stok/export-stok-terakhir") }}' + '?start=' + encodeURIComponent(start) + '&end=' + encodeURIComponent(end);
        var $btn = $(this);
        $btn.prop('disabled', true).text('Preparing...');
        window.location = url;
        setTimeout(function() { $btn.prop('disabled', false).html('<i class="fas fa-download"></i> Export Stok Terakhir'); }, 2000);
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

    // Handle view reference button inside detail modal (delegated)
    (function() {
        var refLoading = false; // guard to prevent multiple simultaneous loads

        $(document).off('click', '.btn-view-ref');
        $(document).on('click', '.btn-view-ref', function(e) {
            e.preventDefault();
            if (refLoading) return; // ignore if a load is in progress
            refLoading = true;

            var $btn = $(this);
            var refType = $btn.data('ref-type');
            var refId = $btn.data('ref-id');

            var url = null;
            if (refType === 'faktur_pembelian') {
                // Use print view which is usually a minimal layout suitable for embedding
                url = '{{ route("erm.fakturbeli.print", "__ID__") }}'.replace('__ID__', refId);
            } else if (refType === 'invoice_penjualan' || refType === 'invoice_return') {
                // Use printable invoice view
                url = '{{ route("finance.invoice.print", "__ID__") }}'.replace('__ID__', refId);
            }
            if (refType === 'retur_pembelian') {
                // For retur, open printable PDF in iframe so it behaves like invoice print
                if ($('#detailModal').hasClass('show')) { $('#detailModal').modal('hide'); }
                $('#refDetailModal').modal({ backdrop: 'static', keyboard: false });
                var url = '{{ route("finance.retur-pembelian.print", "__ID__") }}'.replace('__ID__', refId);
                try {
                    var iframe = document.createElement('iframe');
                    iframe.src = url;
                    iframe.style.width = '100%';
                    iframe.style.height = '70vh';
                    iframe.style.border = '0';
                    $('#refDetailModalContent').empty().append(iframe);
                } catch (err) {
                    console.error('Failed to create iframe for retur:', err);
                    $('#refDetailModal').modal('hide');
                    window.open(url, '_blank');
                } finally {
                    refLoading = false;
                    $('#refDetailModal').modal({ backdrop: true, keyboard: true });
                }
            } else {
                // Other reference types: open in iframe (printable pages)
                if (refType === 'faktur_pembelian') {
                    url = '{{ route("erm.fakturbeli.print", "__ID__") }}'.replace('__ID__', refId);
                } else if (refType === 'invoice_penjualan' || refType === 'invoice_return') {
                    url = '{{ route("finance.invoice.print", "__ID__") }}'.replace('__ID__', refId);
                } else {
                    url = '/';
                }

                // Provide immediate feedback
                $('#refDetailModalContent').html('<div class="text-center py-4"><div class="spinner-border text-primary"></div><p class="mt-2 text-muted">Memuat detail...</p></div>');

                // Hide the parent detail modal to avoid stacking multiple modals on top
                if ($('#detailModal').hasClass('show')) {
                    $('#detailModal').modal('hide');
                }

                // Show ref modal
                $('#refDetailModal').modal({ backdrop: 'static', keyboard: false });

                // Embed the target page inside an iframe to isolate layout and scripts
                try {
                    // Create iframe via DOM API to avoid jQuery attribute quirks
                    var iframe = document.createElement('iframe');
                    iframe.src = url;
                    iframe.style.width = '100%';
                    iframe.style.height = '70vh';
                    iframe.style.border = '0';
                    iframe.onload = function() {
                        // you could remove spinner or do other things here
                    };

                    // Replace content with iframe (wrap in jQuery for convenience)
                    $('#refDetailModalContent').empty().append(iframe);
                } catch (err) {
                    console.error('Failed to create iframe for ref:', err);
                    // If iframe fails, hide modal and open in new tab as fallback
                    $('#refDetailModal').modal('hide');
                    window.open(url, '_blank');
                } finally {
                    refLoading = false;
                    $('#refDetailModal').modal({ backdrop: true, keyboard: true });
                }
            }
        });
    })();
    // Analytics button (delegated) - opens analytics modal for current obat
    $(document).off('click', '.btn-analytics');
    $(document).on('click', '.btn-analytics', function(e) {
        e.preventDefault();
        var $btn = $(this);
        var obatId = $btn.data('obat-id') || lastObatId;
        if (!obatId) {
            alert('Obat tidak tersedia untuk analytics. Buka detail obat terlebih dahulu.');
            return;
        }

        var drp = mainDateRange.data('daterangepicker');
        var start = drp.startDate.format('YYYY-MM-DD');
        var end = drp.endDate.format('YYYY-MM-DD');

        // show analytics modal and spinner
        $('#analyticsModalContent').html('<div class="text-center py-4"><div class="spinner-border text-primary"></div><p class="mt-2 text-muted">Memuat analytics...</p></div>');
        $('#analyticsModal').modal('show');

        $.ajax({
            url: '{{ url('/erm/kartu-stok/analytics') }}',
            method: 'GET',
            data: { obat_id: obatId, start: start, end: end },
            success: function(res) {
                // server returns HTML fragment to inject
                $('#analyticsModalContent').html(res);

                // After injecting, try to read the embedded JSON payload and render chart
                var payloadEl = document.getElementById('analytics-data');
                if (payloadEl) {
                    var payload = {};
                    try {
                        payload = JSON.parse(payloadEl.textContent || payloadEl.innerText);
                    } catch (err) {
                        console.error('Failed to parse analytics payload:', err);
                        return;
                    }

                    function renderChart() {
                        try {
                            var ctx = document.getElementById('analyticsChart');
                            if (!ctx) return;
                            // destroy existing instance if present
                            if (window.analyticsChartInstance) {
                                try { window.analyticsChartInstance.destroy(); } catch(e){}
                            }

                            window.analyticsChartInstance = new Chart(ctx.getContext('2d'), {
                                type: 'line',
                                data: {
                                    labels: payload.labels,
                                    datasets: [
                                        {
                                            label: 'Masuk',
                                            data: payload.masuk,
                                            borderColor: 'rgba(40,167,69,0.9)',
                                            backgroundColor: 'rgba(40,167,69,0.15)',
                                            fill: true,
                                            tension: 0.3,
                                            pointRadius: 3
                                        },
                                        {
                                            label: 'Keluar',
                                            data: payload.keluar,
                                            borderColor: 'rgba(220,53,69,0.9)',
                                            backgroundColor: 'rgba(220,53,69,0.15)',
                                            fill: true,
                                            tension: 0.3,
                                            pointRadius: 3
                                        }
                                    ]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    scales: {
                                        y: { beginAtZero: true }
                                    },
                                    plugins: {
                                        legend: { position: 'top' }
                                    }
                                }
                            });
                        } catch (e) {
                            console.error('Chart render error', e);
                        }
                    }

                    // Ensure Chart.js is loaded; if not, load from CDN and then render
                    if (typeof Chart === 'undefined') {
                        var s = document.createElement('script');
                        s.src = 'https://cdn.jsdelivr.net/npm/chart.js';
                        s.onload = function() { renderChart(); };
                        s.onerror = function() { console.error('Failed to load Chart.js from CDN'); };
                        document.head.appendChild(s);
                    } else {
                        renderChart();
                    }
                }
            },
            error: function(xhr, status, err) {
                console.error('Analytics load error:', status, err, xhr.responseText);
                $('#analyticsModalContent').html('<div class="text-danger">Gagal memuat analytics. ' + (xhr.responseText || '') + '</div>');
            }
        });
    });
});
</script>
@endpush
