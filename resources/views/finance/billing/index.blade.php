@extends('layouts.finance.app')
@section('title', 'Finance | Billing')
@section('navbar')
    @include('layouts.finance.navbar')
@endsection
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm mb-4">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
                    <h4 class="card-title mb-0">Daftar Billing</h4>
                    <div class="row g-2 flex-wrap">
                        <div class="col-12 col-sm-6 col-md-3 mb-2">
                            <select id="filter-dokter" class="form-control w-100">
                                <option value="">Semua Dokter</option>
                                {{-- Options will be loaded via AJAX or server-side rendering --}}
                            </select>
                        </div>
                        <div class="col-12 col-sm-6 col-md-3 mb-2">
                            <select id="filter-klinik" class="form-control w-100">
                                <option value="">Semua Klinik</option>
                                {{-- Options will be loaded via AJAX or server-side rendering --}}
                            </select>
                        </div>
                        <div class="col-12 col-sm-6 col-md-3 mb-2">
                            <div class="date-filter w-100">
                                <div class="input-group w-100">
                                    <input type="text" class="form-control" id="daterange" placeholder="Pilih Rentang Tanggal" readonly>
                                    <span class="input-group-text"><i class="ti-calendar"></i></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 col-md-3 mb-2">
                            <select id="filter-status" class="form-control w-100">
                                <option value="belum">Belum Dibayar</option>
                                <option value="sudah">Sudah Bayar</option>
                                <option value="">Semua Status</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                                            <style>
                                                /* Allow table cells with class .wrap-column to wrap into multiple lines */
                                                .wrap-column {
                                                    white-space: normal !important;
                                                    word-wrap: break-word !important;
                                                    overflow-wrap: break-word !important;
                                                    max-width: 220px; /* sensible max width for dokter column */
                                                    vertical-align: middle;
                                                }
                                                /* Keep action buttons aligned and prevent wrapping inside action cell */
                                                .no-wrap-cell {
                                                    white-space: nowrap !important;
                                                }

                                                /* Keep status column fixed width and prevent badge text from splitting */
                                                .status-cell {
                                                    white-space: nowrap !important;
                                                    width: 120px; /* adjust as needed */
                                                    text-align: center;
                                                }
                                            </style>

                                            <table id="datatable-billing" class="table table-bordered table-hover table-striped dt-responsive" style="width:100%;">
                            <thead class="thead-light">
                                <tr>
                                    <th>No. RM</th>
                                    <th>Nama Pasien</th>
                                    <th>Nomor Invoice</th>
                                    <th>Dokter</th>
                                    <th>Jenis Kunjungan</th>
                                    <th>Tanggal Visit</th>
                                    <th>Klinik</th>
                                    <th>Aksi</th>
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
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // Set up date variables
        var today = moment().format('YYYY-MM-DD');
        var startDate = today;
        var endDate = today;
        var dokterId = '';
        var klinikId = '';
       var statusFilter = 'belum';
        
        // Initialize date range picker
        $('#daterange').daterangepicker({
            startDate: moment(),
            endDate: moment(),
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
            billingTable.ajax.reload();
        });

        // Load dokter and klinik options (AJAX or server-side rendering)
        function loadFilters() {
            $.getJSON("{{ route('finance.billing.filters') }}", function(data) {
                // Dokter
                var dokterSelect = $('#filter-dokter');
                dokterSelect.empty().append('<option value="">Semua Dokter</option>');
                $.each(data.dokters, function(i, dokter) {
                    dokterSelect.append('<option value="'+dokter.id+'">'+dokter.name+'</option>');
                });
                // Klinik
                var klinikSelect = $('#filter-klinik');
                klinikSelect.empty().append('<option value="">Semua Klinik</option>');
                $.each(data.kliniks, function(i, klinik) {
                    klinikSelect.append('<option value="'+klinik.id+'">'+klinik.nama+'</option>');
                });
            });
        }
        loadFilters();

        $('#filter-dokter, #filter-klinik').on('change', function() {
            dokterId = $('#filter-dokter').val();
            klinikId = $('#filter-klinik').val();
            billingTable.ajax.reload();
        });

       $('#filter-status').on('change', function() {
           statusFilter = $(this).val();
           billingTable.ajax.reload();
       });
        
        // Initialize DataTable with date and filter
        var billingTable = $('#datatable-billing').DataTable({
            processing: true,
            serverSide: true,
            // responsive: true,
            ajax: {
                url: "{{ route('finance.billing.data') }}",
                data: function(d) {
                    d.start_date = startDate;
                    d.end_date = endDate;
                    d.dokter_id = dokterId;
                    d.klinik_id = klinikId;
                    d.status_filter = statusFilter;
                }
            },
            columnDefs: [
                // make the dokter column wrap and set a preferred width
                { targets: 3, className: 'wrap-column', width: '220px' },
                // keep action column compact and no-wrap
                { targets: 7, className: 'no-wrap-cell', width: '140px' },
                // keep status badges on one line and fix width
                { targets: 8, className: 'status-cell', width: '120px' }
            ],

            columns: [
                { data: 'no_rm', name: 'no_rm' },
                { data: 'nama_pasien', name: 'nama_pasien' },
                { data: 'invoice_number', name: 'invoice_number' },
                { data: 'dokter', name: 'dokter' },
                { data: 'jenis_kunjungan', name: 'jenis_kunjungan' },
                { data: 'tanggal_visit', name: 'tanggal_visit' },
                { data: 'nama_klinik', name: 'nama_klinik' },
                { data: 'action', name: 'action', orderable: false, searchable: false, responsivePriority: 1,
                    render: function(data, type, row, meta) {
                        if (type === 'display' && data) {
                            // create a temporary container to manipulate the HTML safely
                            var $container = $('<div>').html(data);
                            $container.find('a, button').each(function() {
                                var $el = $(this);
                                var text = $el.text().trim();
                                // map button text to icon classes (using Themify icons already used in project)
                                if (/lihat\s*billing/i.test(text)) {
                                    $el.html('<i class="ti-eye" aria-hidden="true"></i>');
                                    $el.attr('title', 'Lihat Billing');
                                } else if (/cetak\s*nota\s*v?2/i.test(text)) {
                                    $el.html('<i class="ti-printer" aria-hidden="true"></i>');
                                    $el.attr('title', 'Cetak Nota v2');
                                } else if (/cetak\s*nota/i.test(text)) {
                                    $el.html('<i class="ti-printer" aria-hidden="true"></i>');
                                    $el.attr('title', 'Cetak Nota');
                                } else if (/edit/i.test(text)) {
                                    $el.html('<i class="ti-pencil" aria-hidden="true"></i>');
                                    $el.attr('title', 'Edit');
                                } else if (/hapus|delete|remove/i.test(text)) {
                                    $el.html('<i class="ti-trash" aria-hidden="true"></i>');
                                    $el.attr('title', 'Hapus');
                                } else {
                                    // fallback: keep original text but add a small icon
                                    // do nothing or optionally shorten
                                }
                                // ensure buttons remain accessible
                                $el.attr('aria-label', $el.attr('title') || text);
                            });
                            return $container.html();
                        }
                        return data;
                    }
                },
                { data: 'status', name: 'status', orderable: false, searchable: false, responsivePriority: 2 }
            ],
            language: {
                search: "Cari:",
                lengthMenu: "Tampilkan _MENU_ data per halaman",
                zeroRecords: "Tidak ada data yang ditemukan",
                info: "Menampilkan halaman _PAGE_ dari _PAGES_",
                infoEmpty: "Tidak ada data yang tersedia",
                infoFiltered: "(difilter dari _MAX_ total data)",
                paginate: {
                    first: "Pertama",
                    last: "Terakhir",
                    next: "Selanjutnya",
                    previous: "Sebelumnya"
                },
                processing: "Memproses..."
            },
            order: [[4, 'desc']]
        });

        // Auto-reload DataTable every 15 seconds
        setInterval(function() {
            billingTable.ajax.reload(null, false); // false keeps current page position
        }, 15000); // 15000 milliseconds = 15 seconds
    });
</script>
@endsection