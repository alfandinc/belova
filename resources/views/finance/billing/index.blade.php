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
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col-md-4 col-12">
                            <h4 class="card-title mb-0">Daftar Billing</h4>
                        </div>
                        <div class="col-md-8 col-12">
                            <div class="d-flex flex-wrap align-items-center justify-content-end" style="gap: .5rem;">
                                <div class="d-flex align-items-center" style="flex:0 0 auto;">
                                    <div class="btn-group btn-group-sm" role="group" aria-label="Header actions">
                                        <button id="btn-send-farmasi-notif" class="btn btn-primary" title="Kirim Notif ke Farmasi"><i class="fas fa-bell me-1"></i> Kirim Notif ke Farmasi</button>
                                        <button id="btn-old-notifs-finance" type="button" class="btn btn-light" title="Lihat Notifikasi Lama">
                                            <span style="color:#007bff; font-size:14px;">&#10084;</span>
                                        </button>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center" style="flex:0 0 220px;">
                                    <select id="filter-dokter" class="form-control form-control-sm w-100">
                                        <option value="">Semua Dokter</option>
                                    </select>
                                </div>
                                <div class="d-flex align-items-center" style="flex:0 0 220px;">
                                    <select id="filter-klinik" class="form-control form-control-sm w-100">
                                        <option value="">Semua Klinik</option>
                                    </select>
                                </div>
                                <div class="d-flex align-items-center" style="flex:0 0 260px;">
                                    <div class="input-group input-group-sm w-100">
                                        <input type="text" class="form-control form-control-sm" id="daterange" placeholder="Pilih Rentang Tanggal" readonly>
                                        <span class="input-group-text"><i class="ti-calendar"></i></span>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center" style="flex:0 0 160px;">
                                    <select id="filter-status" class="form-control form-control-sm w-100">
                                            <option value="belum">Belum Dibayar</option>
                                            <option value="belum_lunas">Belum Lunas</option>
                                            <option value="sudah">Sudah Bayar</option>
                                            <option value="piutang">Piutang</option>
                                            <option value="">Semua Status</option>
                                        </select>
                                </div>
                                <div class="d-flex align-items-center" style="flex:0 0 auto;">
                                    <div class="form-check d-flex align-items-center">
                                        <input class="form-check-input" type="checkbox" value="1" id="show-deleted">
                                        <label class="form-check-label small ms-2 mb-0" for="show-deleted">Tampilkan Terhapus</label>
                                    </div>
                                </div>
                            </div>
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
                                                    /* allow column to grow/shrink based on content */
                                                    max-width: none !important;
                                                    min-width: 160px; /* prevent collapsing too small */
                                                    vertical-align: middle;
                                                }
                                                /* allow long doctor names to wrap gracefully */
                                                .dokter-cell { word-break: break-word; }
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
                                                /* custom pink badge for klinik id 2 */
                                                .badge-pink {
                                                    background: #e83e8c;
                                                    color: #fff;
                                                }
                                                /* Ensure specialization (small) inside dokter-cell is not bold */
                                                .dokter-cell small { font-weight: 400 !important; }
                                                /* Make patient RM muted and normal weight */
                                                .patient-name-cell small { font-weight: 400; color: #6c757d; }
                                            </style>

                                            <table id="datatable-billing" class="table table-bordered table-hover table-striped dt-responsive" style="width:100%;">
                            <thead class="thead-light">
                                <tr>
                                    <th>Nama Pasien</th>
                                    <th>Dokter</th>
                                    <th>Tanggal Visit</th>
                                    <th>Nomor Invoice</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                    <!-- Modal: Old Notifications (Finance) -->
                    <div class="modal fade" id="modalOldNotificationsFinance" tabindex="-1" role="dialog" aria-labelledby="modalOldNotificationsFinanceLabel" aria-hidden="true">
                        <div class="modal-dialog modal-lg" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="modalOldNotificationsFinanceLabel">Notifikasi Lama (Finance)</h5>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-mark-all-finance" style="margin-right:1rem;">Tandai semua sudah dibaca</button>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <div id="old-fin-notifs-loading" style="display:none; text-align:center; padding:20px;">
                                        <div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div>
                                    </div>
                                    <div id="old-fin-notifs-empty" style="display:none; text-align:center; color:#666;">Belum ada notifikasi lama.</div>
                                    <ul class="list-group" id="old-fin-notifs-list"></ul>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                                </div>
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
                    d.include_deleted = $('#show-deleted').is(':checked') ? 1 : 0;
                }
            },
            columnDefs: [
                // make the dokter column wrap and allow flexible width (index 1)
                { targets: 1, className: 'wrap-column', responsivePriority: 2 },
                // keep action column compact and no-wrap (now at index 4)
                { targets: 4, className: 'no-wrap-cell', width: '140px', responsivePriority: 1 }
            ],

            columns: [
                { data: null, name: 'nama_pasien', render: function(data, type, row, meta) {
                        if (type === 'display') {
                            var name = row.nama_pasien || '';
                            var noRm = row.no_rm || '';
                            var html = '<div class="patient-name-cell">';
                            html += '<div class="font-weight-bold">' + escapeHtml(name) + '</div>';
                            if (noRm) html += '<small class="text-muted d-block">' + escapeHtml(noRm) + '</small>';
                            html += '</div>';
                            return html;
                        }
                        return row.nama_pasien;
                    }
                },
                { data: 'dokter', name: 'dokter', render: function(data, type, row, meta) {
                        if (type === 'display') {
                            var dokterName = data || row.dokter || '';
                            var klinikName = row.nama_klinik || '';
                            var klinikId = row.klinik_id || (row.klinik && row.klinik.id) || '';
                            var badgeClass = 'badge-secondary';
                            if (String(klinikId) === '1') badgeClass = 'badge-primary';
                            else if (String(klinikId) === '2') badgeClass = 'badge-pink';

                            var decodeHtml = function(str) { return $('<textarea/>').html(str || '').text(); };
                            var dokterDecoded = decodeHtml(dokterName);
                            var klinikDecoded = decodeHtml(klinikName);
                            var spesialis = row.spesialisasi || row.spesialis || row.dokter_spesialisasi || '';
                            var spesialisDecoded = decodeHtml(spesialis);

                            var dokterClean = dokterDecoded;
                            if (!spesialisDecoded) {
                                var m = dokterDecoded.match(/\s*\(([^)]+)\)\s*$/);
                                if (m) {
                                    dokterClean = dokterDecoded.replace(/\s*\([^)]+\)\s*$/, '').trim();
                                    spesialisDecoded = m[1];
                                }
                            }

                            var html = '<div class="dokter-cell">';
                            html += '<div class="font-weight-bold">' + escapeHtml(dokterClean) + '</div>';
                            if (spesialisDecoded) html += '<div class="mt-1"><span class="badge badge-secondary">' + escapeHtml(spesialisDecoded) + '</span></div>';
                            html += '</div>';
                            // klinik moved to tanggal_visit column
                            return html;
                        }
                        return data;
                    }
                },
                { data: 'tanggal_visit', name: 'tanggal_visit', render: function(data, type, row, meta) {
                        if (type === 'display') {
                            var dateText = data || row.tanggal_visit || '';
                            var jenis = row.jenis_kunjungan || '';
                            var klinikId = row.klinik_id || (row.klinik && row.klinik.id) || '';
                            var klinikLabel = '';
                            var klinikBadgeClass = 'badge-secondary';
                            if (String(klinikId) === '1') { klinikLabel = 'Premiere Belova'; klinikBadgeClass = 'badge-primary'; }
                            else if (String(klinikId) === '2') { klinikLabel = 'Belova Skin'; klinikBadgeClass = 'badge-pink'; }
                            else if (row.nama_klinik) { klinikLabel = row.nama_klinik; }

                            var html = '<div class="tanggal-cell"><span class="font-weight-bold">' + escapeHtml(dateText) + '</span>';
                            if (jenis || klinikLabel) {
                                html += '<div class="mt-1">';
                                if (jenis) html += '<span class="badge badge-info">' + escapeHtml(jenis) + '</span>';
                                if (klinikLabel) html += ' <span class="badge ' + klinikBadgeClass + ' ms-1">' + escapeHtml(klinikLabel) + '</span>';
                                html += '</div>';
                            }
                            html += '</div>';
                            return html;
                        }
                        return data || row.tanggal_visit;
                    }
                },
                { data: 'invoice_number', name: 'invoice_number', render: function(data, type, row, meta) {
                        if (type === 'display') {
                            var inv = data || row.invoice_number || '';
                            var statusHtml = row.status || '';
                            var badge = '';
                            // If invoice payment method is piutang, always show Piutang badge
                            if (row.payment_method && String(row.payment_method).toLowerCase() === 'piutang') {
                                badge = '<span class="badge badge-warning">Piutang</span>';
                            } else if (statusHtml) {
                                // Strip any HTML coming from server and use plain text
                                var plain = $('<div>').html(statusHtml).text();
                                var s = String(plain).toLowerCase();
                                var cls = 'badge-secondary';
                                // Explicit 'belum lunas' (contains both words) => yellow
                                if (s.indexOf('belum') !== -1 && s.indexOf('lunas') !== -1) {
                                    cls = 'badge-warning';
                                }
                                // Fully paid or explicitly 'sudah' OR 'lunas' without 'belum' => green
                                else if (s.indexOf('sudah') !== -1 || (s.indexOf('lunas') !== -1 && s.indexOf('belum') === -1)) {
                                    cls = 'badge-success';
                                }
                                // Any other 'belum' (unpaid) => red
                                else if (s.indexOf('belum') !== -1) {
                                    cls = 'badge-danger';
                                }
                                badge = '<span class="badge ' + cls + '">' + escapeHtml(plain) + '</span>';
                            }
                            var html = '<div class="invoice-cell">';
                            html += '<div class="font-weight-bold">' + escapeHtml(inv) + '</div>';
                            if (badge) html += '<div class="mt-1">' + badge + '</div>';
                            html += '</div>';
                            return html;
                        }
                        return data;
                    }
                },
                { data: 'action', name: 'action', orderable: false, searchable: false, responsivePriority: 1,
                    render: function(data, type, row, meta) {
                        if (type === 'display' && data) {
                            // create a temporary container to manipulate the HTML safely
                            var $container = $('<div>').html(data);
                            // Map text to icons and set accessible titles
                            $container.find('a, button').each(function() {
                                var $el = $(this);
                                if ($el.data('no-icon')) return;
                                // remove spacing utilities and inline margins so btn-group packs buttons tightly
                                $el.css({ 'margin-left': '', 'margin-right': '' });
                                $el.removeClass('me-1 ms-1 ml-1 mr-1 ms-2 me-2');
                                // ensure consistent small button styling inside group
                                $el.addClass('btn btn-sm');
                                var text = $el.text().trim();
                                if (/lihat\s*billing/i.test(text)) { $el.html('<i class="ti-eye" aria-hidden="true"></i>'); $el.attr('title', 'Lihat Billing'); }
                                else if (/cetak\s*nota\s*v?2/i.test(text)) { $el.html('<i class="ti-printer" aria-hidden="true"></i>'); $el.attr('title', 'Cetak Nota v2'); }
                                else if (/cetak\s*nota/i.test(text)) { $el.html('<i class="ti-printer" aria-hidden="true"></i>'); $el.attr('title', 'Cetak Nota'); }
                                else if (/edit/i.test(text)) { $el.html('<i class="ti-pencil" aria-hidden="true"></i>'); $el.attr('title', 'Edit'); }
                                else if (/hapus|delete|remove/i.test(text) && $el.find('i').length === 0) { $el.html('<i class="ti-trash" aria-hidden="true"></i>'); $el.attr('title', 'Hapus'); }
                                $el.attr('aria-label', $el.attr('title') || text);
                            });

                            // Group action buttons into a btn-group for compact layout
                            var $buttons = $container.find('a, button');
                            if ($buttons.length) {
                                var $group = $('<div class="btn-group" role="group"></div>');
                                $buttons.each(function() { $group.append($(this)); });
                                return $group.prop('outerHTML');
                            }

                            return $container.html();
                        }
                        return data;
                    }
                },
                
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
            // Adjusted ordering index after merging No. RM into Nama Pasien
            order: [[3, 'desc']]
        });

        // Auto-reload DataTable every 15 seconds
        setInterval(function() {
            billingTable.ajax.reload(null, false); // false keeps current page position
        }, 15000); // 15000 milliseconds = 15 seconds

        // Toggle show deleted
        $('#show-deleted').on('change', function() {
            billingTable.ajax.reload();
        });

        // Delegate handlers for visitation-level actions (trash/restore/force)
        $(document).on('click', '.btn-trash-visitation', function() {
            var id = $(this).data('id');
            Swal.fire({
                title: 'Pindahkan billing ke trash?',
                showCancelButton: true,
                confirmButtonText: 'Ya, pindahkan',
                cancelButtonText: 'Batal'
            }).then(function(result) {
                if (result.value) {
                    $.post("{{ url('/finance/billing/visitation/') }}/" + id + "/trash", {_token: '{{ csrf_token() }}'})
                    .done(function(res) {
                        Swal.fire('Sukses', res.message, 'success');
                        billingTable.ajax.reload();
                    }).fail(function() {
                        Swal.fire('Gagal', 'Terjadi kesalahan', 'error');
                    });
                }
            });
        });

        // Finance: Old Notifications modal handlers
        var oldFinNotifsUrl = '{{ route("finance.notifications.old") }}';
        var markFinReadBase = '{{ url("finance/notifications") }}';
        var csrfToken = '{{ csrf_token() }}';

        $(document).on('click', '#btn-old-notifs-finance', function () {
            $('#modalOldNotificationsFinance').modal('show');
            loadOldFinNotifications();
        });

        function loadOldFinNotifications() {
            $('#old-fin-notifs-list').empty();
            $('#old-fin-notifs-empty').hide();
            $('#old-fin-notifs-loading').show();

            $.get(oldFinNotifsUrl, function (res) {
                $('#old-fin-notifs-loading').hide();
                var items = Array.isArray(res) ? res : (res && Array.isArray(res.data) ? res.data : []);
                if (!items || items.length === 0) {
                    $('#old-fin-notifs-empty').show();
                    return;
                }

                items.forEach(function (n) {
                    var message = n.message || n.title || n.text || JSON.stringify(n);
                    var time = n.created_at || '';
                    var $li = $("<li class='list-group-item d-flex justify-content-between align-items-start'></li>");
                    var left = '<div class="notif-content">';
                    if (n.read) left += '<div class="text-muted">' + escapeHtml(message) + '</div>';
                    else left += '<div class="font-weight-bold">' + escapeHtml(message) + '</div>';
                    if (time) left += '<small class="text-muted">' + escapeHtml(time) + '</small>';
                    left += '</div>';

                    var right = '<div class="notif-actions">';
                    if (!n.read) {
                        right += '<button class="btn btn-sm btn-primary btn-mark-read-fin" data-id="' + n.id + '">Tandai sudah dibaca</button>';
                    } else {
                        right += '<span class="badge badge-secondary">Sudah dibaca</span>';
                    }
                    right += '</div>';

                    $li.html(left + right);
                    $('#old-fin-notifs-list').append($li);
                });
            }).fail(function () {
                $('#old-fin-notifs-loading').hide();
                $('#old-fin-notifs-empty').text('Gagal memuat notifikasi.').show();
            });
        }

        $(document).on('click', '.btn-mark-read-fin', function (e) {
            e.preventDefault();
            var $btn = $(this);
            var id = $btn.data('id');
            if (!id) return;
            $btn.prop('disabled', true).text('Memproses...');

            $.ajax({
                url: markFinReadBase + '/' + id + '/mark-read',
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken },
                success: function (res) {
                    if (res && res.success) loadOldFinNotifications();
                    else { alert('Gagal menandai notifikasi.'); $btn.prop('disabled', false).text('Tandai sudah dibaca'); }
                },
                error: function () { alert('Gagal menandai notifikasi.'); $btn.prop('disabled', false).text('Tandai sudah dibaca'); }
            });
        });

        // Mark all read
        $(document).on('click', '#btn-mark-all-finance', function () {
            if (!confirm('Tandai semua notifikasi sebagai sudah dibaca?')) return;
            // fetch ids then post each (simple approach)
            $.get(oldFinNotifsUrl, function (res) {
                var items = Array.isArray(res) ? res : (res && Array.isArray(res.data) ? res.data : []);
                var unread = items.filter(function(i) { return !i.read; });
                if (!unread.length) { alert('Tidak ada notifikasi belum dibaca.'); return; }
                var requests = [];
                unread.forEach(function (n) {
                    requests.push($.ajax({ url: markFinReadBase + '/' + n.id + '/mark-read', method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken } }));
                });
                $.when.apply($, requests).always(function() { loadOldFinNotifications(); });
            });
        });

        function escapeHtml(unsafe) {
            return String(unsafe).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
        }

        $(document).on('click', '.btn-restore-visitation', function() {
            var id = $(this).data('id');
            Swal.fire({
                title: 'Kembalikan billing dari trash?',
                showCancelButton: true,
                confirmButtonText: 'Ya, kembalikan',
                cancelButtonText: 'Batal'
            }).then(function(result) {
                if (result.value) {
                    $.post("{{ url('/finance/billing/visitation/') }}/" + id + "/restore", {_token: '{{ csrf_token() }}'})
                    .done(function(res) {
                        Swal.fire('Sukses', res.message, 'success');
                        billingTable.ajax.reload();
                    }).fail(function() {
                        Swal.fire('Gagal', 'Terjadi kesalahan', 'error');
                    });
                }
            });
        });

        $(document).on('click', '.btn-force-visitation', function() {
            var id = $(this).data('id');
            Swal.fire({
                title: 'Hapus permanen billing untuk kunjungan ini?',
                text: 'Tindakan ini tidak dapat dibatalkan.',
                showCancelButton: true,
                confirmButtonText: 'Hapus Permanen',
                cancelButtonText: 'Batal'
            }).then(function(result) {
                if (result.value) {
                    $.ajax({
                        url: "{{ url('/finance/billing/visitation/') }}/" + id + "/force",
                        method: 'DELETE',
                        data: {_token: '{{ csrf_token() }}'},
                    }).done(function(res) {
                        Swal.fire('Dihapus', res.message, 'success');
                        billingTable.ajax.reload();
                    }).fail(function() {
                        Swal.fire('Gagal', 'Terjadi kesalahan', 'error');
                    });
                }
            });
        });

        // Finance: Send notification to Farmasi
        $('#btn-send-farmasi-notif').click(function() {
            // Ask for a short message to send
            Swal.fire({
                title: 'Kirim notifikasi ke Farmasi',
                input: 'text',
                inputPlaceholder: 'Masukkan pesan singkat...',
                showCancelButton: true,
                confirmButtonText: 'Kirim',
                cancelButtonText: 'Batal',
                preConfirm: (value) => {
                    if (!value) {
                        Swal.showValidationMessage('Pesan tidak boleh kosong');
                    }
                    return value;
                }
            }).then(function(result) {
                if (result.value && result.value) {
                    var message = result.value;
                    $.post("{{ url('/finance/send-notif-farmasi') }}", {
                        message: message,
                        _token: '{{ csrf_token() }}'
                    }, function(res) {
                        if (res && res.success) {
                            var info = 'Notifikasi berhasil dikirim ke Farmasi.';
                            if (res.total !== undefined) {
                                info += "\nTerkirim: " + (res.sent || 0) + " dari " + (res.total || 0);
                            }
                            if (res.failed && res.failed.length > 0) {
                                info += "\nGagal: " + res.failed.join(', ');
                            }
                            Swal.fire('Terkirim!', info, 'success');
                            // set sound type so Farmasi page can play a sound
                            localStorage.setItem('notifSoundType', 'notif');
                        } else {
                            Swal.fire('Gagal', 'Tidak dapat mengirim notifikasi.', 'error');
                        }
                    }).fail(function() {
                        Swal.fire('Gagal', 'Terjadi kesalahan saat mengirim notifikasi.', 'error');
                    });
                }
            });
        });
    });
</script>
@endsection