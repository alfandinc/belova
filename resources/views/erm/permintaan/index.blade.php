@extends('layouts.erm.app')
@section('title', 'Farmasi | Permintaan Pembelian')
@section('navbar')
    @include('layouts.erm.navbar-farmasi')
@endsection

@section('content')
<div class="container-fluid">
    <style>
        #permintaan-table td { vertical-align: top; }
        #permintaan-table tbody tr:hover { background-color: #f3f7ff; }
        #permintaan-table tr.pr-waiting td:first-child { box-shadow: inset 3px 0 0 #ffc107; }
        #permintaan-table .permintaan-col-obat { min-width: 300px; }
        #permintaan-table .permintaan-stack-row {
            min-height: 48px;
            padding: .35rem 0;
            border-bottom: 1px solid #e9edf4;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: .15rem;
        }
        #permintaan-table .permintaan-stack-row:last-child { border-bottom: 0; }
        #permintaan-table .font-weight-medium { font-weight: 500; }
        #permintaan-table .badge, #nilaiPembelianModal .badge { font-size: .72rem; }
        .pr-tabs .nav-link { padding: .35rem .8rem; font-size: .875rem; }
        .pr-total { border: 1px solid #dbe4f0; border-radius: .5rem; background: #f8fbff; padding: .5rem .9rem; }
        .pr-total-label { font-size: .75rem; text-transform: uppercase; letter-spacing: .04em; color: #6c7a92; }
        .pr-total-value { font-size: 1.2rem; font-weight: 700; color: #1f3b73; }
    </style>

    <div class="d-flex flex-wrap align-items-center justify-content-between mt-3 mb-3">
        <div>
            <h4 class="page-title mb-0">Permintaan Pembelian</h4>
            <small class="text-muted">Setelah disetujui, permintaan menjadi Faktur Pembelian (status diminta) per pemasok.</small>
        </div>
        <button type="button" class="btn btn-primary mt-2 mt-md-0" id="btnBuatPermintaan"><i class="fas fa-plus"></i> Buat Permintaan</button>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-start">
                <ul class="nav nav-pills pr-tabs mb-3" id="status-tabs">
                    <li class="nav-item"><a class="nav-link active" href="#" data-status="">Semua <span class="badge badge-light ml-1" data-count="all">0</span></a></li>
                    <li class="nav-item"><a class="nav-link" href="#" data-status="waiting_approval">Menunggu approval <span class="badge badge-warning ml-1" data-count="waiting_approval">0</span></a></li>
                    <li class="nav-item"><a class="nav-link" href="#" data-status="approved">Disetujui <span class="badge badge-light ml-1" data-count="approved">0</span></a></li>
                    <li class="nav-item"><a class="nav-link" href="#" data-status="rejected">Ditolak <span class="badge badge-light ml-1" data-count="rejected">0</span></a></li>
                </ul>
                <div class="pr-total mb-3 text-right" title="Perkiraan dari harga Master Pembelian + PPN {{ rtrim(rtrim(number_format(config('pembelian.ppn_persen', 11), 2, ',', '.'), '0'), ',') }}%">
                    <div class="pr-total-label">Total nilai (sesuai filter)</div>
                    <div class="pr-total-value" id="permintaanTotalNilaiPembelian">Rp 0</div>
                </div>
            </div>

            <div class="form-row align-items-end mb-2">
                <div class="col-md-3 mb-2">
                    <label class="small text-muted mb-1" for="requestDateRange">Tanggal permintaan</label>
                    <div class="input-group input-group-sm">
                        <div class="input-group-prepend"><span class="input-group-text"><i class="fa fa-calendar"></i></span></div>
                        <input type="text" id="requestDateRange" class="form-control" autocomplete="off">
                    </div>
                </div>
                <div class="col-md-3 mb-2">
                    <label class="small text-muted mb-1" for="principalFilter">Principal</label>
                    <select id="principalFilter" class="form-control form-control-sm">
                        <option value="">Semua Principal</option>
                        @foreach(($principals ?? collect()) as $principal)
                            <option value="{{ $principal->id }}">{{ $principal->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 mb-2">
                    <label class="small text-muted mb-1" for="searchPermintaan">Cari</label>
                    <input type="search" id="searchPermintaan" class="form-control form-control-sm" placeholder="Nomor permintaan, pemasok, atau nama obat">
                </div>
                <div class="col-md-2 mb-2">
                    <button type="button" class="btn btn-light btn-sm btn-block" id="resetFilter"><i class="fas fa-undo"></i> Reset</button>
                </div>
            </div>

            <table class="table table-bordered table-sm w-100" id="permintaan-table">
                <thead class="thead-light">
                    <tr>
                        <th>Nomor</th>
                        <th class="permintaan-col-obat">Obat</th>
                        <th class="text-right">Diminta</th>
                        <th class="text-right">Terpenuhi</th>
                        <th class="text-right">Nilai</th>
                        <th>Status</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    <div class="modal fade" id="nilaiPembelianModal" tabindex="-1" role="dialog" aria-labelledby="nilaiPembelianModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="nilaiPembelianModalLabel">Nilai Pembelian</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <div><strong>No Permintaan:</strong> <span id="nilaiPembelianNoPermintaan">-</span></div>
                        <div><strong>Tanggal Permintaan:</strong> <span id="nilaiPembelianTanggal">-</span></div>
                        <div class="small text-muted">Perkiraan dari harga Master Pembelian. Harga final mengikuti faktur dari pemasok.</div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead>
                                <tr>
                                    <th>Obat Diminta</th>
                                    <th class="text-right">Qty</th>
                                    <th class="text-right">Harga</th>
                                    <th class="text-right">Diskon</th>
                                    <th class="text-right">Setelah Diskon</th>
                                    <th class="text-right">PPN</th>
                                    <th class="text-right">Total Harga</th>
                                </tr>
                            </thead>
                            <tbody id="nilaiPembelianTableBody"></tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="4" class="text-right">Total Nilai Permintaan Pembelian</th>
                                    <th class="text-right" id="nilaiPembelianSubtotalDiskon">Rp 0</th>
                                    <th class="text-right" id="nilaiPembelianPpn">Rp 0</th>
                                    <th class="text-right" id="nilaiPembelianGrandTotal">Rp 0</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
@include('erm.permintaan._form-modal')
<script>
$(document).ready(function() {
    var CSRF = '{{ csrf_token() }}';
    var BASE = '{{ url('erm/permintaan') }}';
    var FAKTUR_BASE = '{{ url('erm/fakturpembelian') }}';
    var currentStatus = '';

    @if(session('success'))
        Swal.fire({ icon: 'success', title: 'Berhasil', text: @json(session('success')), timer: 2200, showConfirmButton: false });
    @endif
    @if(session('error'))
        Swal.fire({ icon: 'warning', title: 'Perhatian', text: @json(session('error')) });
    @endif

    function esc(value) {
        return String(value === undefined || value === null ? '' : value).replace(/[&<>"']/g, function(c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function formatRupiah(value) {
        return 'Rp ' + Number(value || 0).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
    }

    function formatTanggal(value) {
        if (!value) return '-';
        var p = String(value).substring(0, 10).split('-');
        return p.length === 3 ? p[2] + '/' + p[1] + '/' + p[0] : value;
    }

    function xhrMessage(xhr, fallback) {
        var json = xhr && xhr.responseJSON;
        if (json && json.errors) {
            return Object.values(json.errors).map(function(m) { return m[0]; }).join('\n');
        }
        return (json && json.message) || fallback;
    }

    // Keep the stacked item rows of the Obat / Diminta / Terpenuhi columns on the same height
    function alignStackRows() {
        $('#permintaan-table tbody tr').each(function() {
            var $cells = $(this).children('td');
            var groups = [$cells.eq(1).find('.permintaan-stack-row'), $cells.eq(2).find('.permintaan-stack-row'), $cells.eq(3).find('.permintaan-stack-row')];
            groups.forEach(function($rows) { $rows.css('min-height', '48px'); });
            var maxRows = Math.max(groups[0].length, groups[1].length, groups[2].length);
            for (var i = 0; i < maxRows; i++) {
                var h = 48;
                groups.forEach(function($rows) { if ($rows.eq(i).length) h = Math.max(h, $rows.eq(i).outerHeight()); });
                groups.forEach(function($rows) { if ($rows.eq(i).length) $rows.eq(i).css('min-height', h + 'px'); });
            }
        });
    }

    var defaultStart = moment().subtract(3, 'months');
    var defaultEnd = moment();
    $('#requestDateRange').daterangepicker({
        startDate: defaultStart,
        endDate: defaultEnd,
        autoUpdateInput: true,
        locale: { format: 'YYYY-MM-DD', cancelLabel: 'Semua tanggal', applyLabel: 'Terapkan' }
    });

    var STATUS_BADGE = { waiting_approval: 'badge-warning', approved: 'badge-success', rejected: 'badge-danger' };

    var table = $('#permintaan-table').DataTable({
        processing: true,
        serverSide: true,
        searchDelay: 400,
        dom: 'rt<"d-flex flex-wrap justify-content-between align-items-center mt-2"lip>',
        pageLength: 25,
        ordering: false,
        ajax: {
            url: '{{ route('erm.permintaan.data') }}',
            data: function(d) {
                d.request_date_range = $('#requestDateRange').val();
                d.principal_id = $('#principalFilter').val();
                d.status = currentStatus;
            }
        },
        createdRow: function(row, data) {
            if (data.status === 'waiting_approval') $(row).addClass('pr-waiting');
        },
        columns: [
            {
                data: 'no_permintaan',
                render: function(data, type, row) {
                    return '<div class="font-weight-bold text-nowrap">' + esc(data || '-') + '</div>'
                        + '<div class="small">' + esc(row.pemasok) + '</div>'
                        + '<div class="small text-muted">' + formatTanggal(row.request_date) + (row.dibuat_oleh ? ' · ' + esc(row.dibuat_oleh) : '') + '</div>'
                        + (row.notes ? '<div class="small text-muted font-italic" title="Catatan">' + esc(row.notes) + '</div>' : '');
                }
            },
            { data: 'obats' },
            { data: 'qty_diminta' },
            { data: 'qty_terpenuhi' },
            {
                data: 'total_nilai_pembelian',
                className: 'text-right text-nowrap',
                render: function(data, type, row) {
                    return '<div class="font-weight-medium">' + formatRupiah(data) + '</div>'
                        + '<a href="#" class="small btn-nilai-pembelian" data-id="' + row.id + '"><i class="fa fa-eye"></i> rincian</a>';
                }
            },
            {
                data: 'status',
                render: function(data, type, row) {
                    var html = '<span class="badge ' + (STATUS_BADGE[data] || 'badge-secondary') + '">' + esc(row.status_label) + '</span>';
                    if (data === 'approved' && row.approved_by_name) {
                        html += '<div class="small text-muted">oleh ' + esc(row.approved_by_name) + (row.approved_date ? '<br>' + esc(row.approved_date) : '') + '</div>';
                    }
                    if (data === 'rejected') {
                        html += '<div class="small text-muted">' + (row.rejected_by_name ? 'oleh ' + esc(row.rejected_by_name) : '') + '</div>'
                            + (row.rejected_reason ? '<div class="small text-danger">' + esc(row.rejected_reason) + '</div>' : '');
                    }
                    // Link to the faktur(s) this permintaan became
                    (row.fakturs || []).forEach(function(f) {
                        var label = f.no_faktur ? esc(f.no_faktur) : 'Faktur #' + f.id;
                        html += '<div class="small mt-1"><i class="fas fa-file-invoice text-muted"></i> '
                            + (f.status === 'diminta'
                                ? '<a href="' + FAKTUR_BASE + '/' + f.id + '/edit" title="Barang datang? Isi faktur dari pemasok">' + label + ' · input faktur</a>'
                                : label + ' <span class="text-muted">(' + esc(f.status) + ')</span>')
                            + '</div>';
                    });
                    return html;
                }
            },
            {
                data: null,
                className: 'text-right text-nowrap',
                render: function(data, type, row) {
                    var html = '';
                    if (row.status === 'waiting_approval') {
                        html += '<button class="btn btn-sm btn-success btn-approve mb-1" data-id="' + row.id + '" data-nomor="' + esc(row.no_permintaan) + '" data-nilai="' + row.total_nilai_pembelian + '" data-items="' + row.jumlah_item + '"><i class="fas fa-check"></i> Setujui</button> ';
                        html += '<button type="button" class="btn btn-sm btn-outline-primary btn-ubah mb-1" data-id="' + row.id + '" title="Ubah"><i class="fas fa-pen"></i></button> '
                            + '<button type="button" class="btn btn-sm btn-outline-warning btn-reject mb-1" data-id="' + row.id + '" data-nomor="' + esc(row.no_permintaan) + '" title="Tolak"><i class="fas fa-times"></i> Tolak</button> '
                            + '<button type="button" class="btn btn-sm btn-outline-danger btn-delete mb-1" data-id="' + row.id + '" data-nomor="' + esc(row.no_permintaan) + '" title="Hapus"><i class="fas fa-trash"></i></button>';
                    } else if (row.status === 'approved') {
                        html += '<div class="btn-group btn-group-sm">'
                            + '<a href="' + BASE + '/' + row.id + '/print" target="_blank" class="btn btn-outline-secondary" title="Surat Permintaan"><i class="fa fa-print"></i> Permintaan</a>'
                            + '<a href="' + BASE + '/' + row.id + '/print-surat-pemesanan" target="_blank" class="btn btn-outline-primary" title="Surat Pemesanan"><i class="fa fa-print"></i> Pemesanan</a>'
                            + '</div>';
                    } else if (row.status === 'rejected') {
                        html += '<button class="btn btn-sm btn-outline-danger btn-delete" data-id="' + row.id + '" data-nomor="' + esc(row.no_permintaan) + '"><i class="fas fa-trash"></i> Hapus</button>';
                    }
                    return html;
                }
            }
        ],
        language: {
            processing: '<i class="fas fa-spinner fa-spin"></i> Memuat...',
            zeroRecords: 'Tidak ada permintaan yang cocok',
            emptyTable: 'Belum ada permintaan',
            info: '_START_–_END_ dari _TOTAL_ permintaan',
            infoEmpty: '0 permintaan',
            infoFiltered: '',
            lengthMenu: 'Tampil _MENU_',
            paginate: { previous: '‹', next: '›' }
        },
        drawCallback: function() {
            alignStackRows();
            var json = this.api().ajax.json() || {};
            var counts = json.counts || {};
            $('#status-tabs [data-count]').each(function() { $(this).text(counts[$(this).data('count')] || 0); });
            $('#permintaanTotalNilaiPembelian').text(formatRupiah(json.total_nilai_pembelian_filtered));
        }
    });

    $(window).on('resize', alignStackRows);

    // ---------- Form (modal) ----------
    $('#btnBuatPermintaan').on('click', function() { PermintaanForm.open(); });
    $('#permintaan-table').on('click', '.btn-ubah', function(e) {
        e.preventDefault();
        PermintaanForm.open({ id: $(this).data('id') });
    });
    PermintaanForm.onSaved(function(res) {
        // A new permintaan appears on top of the "Menunggu approval" tab
        table.draw(false);
        Swal.fire({ icon: 'success', title: 'Tersimpan', text: res.message, timer: 2500, showConfirmButton: false });
    });
    // Old links (/create, /edit) land here with the modal to open
    (function() {
        var params = new URLSearchParams(window.location.search);
        if (params.get('ubah')) PermintaanForm.open({ id: params.get('ubah') });
        else if (params.get('baru')) {
            PermintaanForm.open();
            // From the Forecast page: fill the new form with the forecast suggestions
            if (params.get('forecast')) $('#modalPermintaan').one('shown.bs.modal', function () { $('#pr_forecast').trigger('click'); });
        }
        if (params.toString()) window.history.replaceState({}, '', window.location.pathname);
    })();

    $('#status-tabs').on('click', '.nav-link', function(e) {
        e.preventDefault();
        $('#status-tabs .nav-link').removeClass('active');
        $(this).addClass('active');
        currentStatus = $(this).data('status') || '';
        table.draw();
    });

    $('#requestDateRange').on('apply.daterangepicker', function(ev, picker) {
        $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
        table.draw();
    });
    // "Semua tanggal": an explicit marker so the server does not fall back to the last 3 months
    $('#requestDateRange').on('cancel.daterangepicker', function() {
        $(this).val('semua');
        table.draw();
    });
    $('#principalFilter').on('change', function() { table.draw(); });

    var searchTimer;
    $('#searchPermintaan').on('input', function() {
        var value = this.value;
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function() { table.search(value).draw(); }, 400);
    });

    $('#resetFilter').on('click', function() {
        var picker = $('#requestDateRange').data('daterangepicker');
        picker.setStartDate(defaultStart);
        picker.setEndDate(defaultEnd);
        $('#requestDateRange').val(defaultStart.format('YYYY-MM-DD') + ' - ' + defaultEnd.format('YYYY-MM-DD'));
        $('#principalFilter').val('');
        $('#searchPermintaan').val('');
        $('#status-tabs .nav-link').removeClass('active').first().addClass('active');
        currentStatus = '';
        table.search('').draw();
    });

    function post(url, data, fallback) {
        return $.ajax({ url: url, type: 'POST', data: $.extend({ _token: CSRF }, data || {}) })
            .done(function(res) {
                table.draw(false);
                Swal.fire({ icon: 'success', title: 'Berhasil', text: res.message, timer: 2500, showConfirmButton: false });
            })
            .fail(function(xhr) {
                Swal.fire({ icon: 'error', title: 'Gagal', text: xhrMessage(xhr, fallback) });
            });
    }

    $('#permintaan-table').on('click', '.btn-approve', function() {
        var $b = $(this);
        Swal.fire({
            icon: 'question',
            title: 'Setujui ' + esc($b.data('nomor')) + '?',
            html: $b.data('items') + ' obat, perkiraan nilai <strong>' + formatRupiah($b.data('nilai')) + '</strong>.'
                + '<div class="small text-muted mt-2">Faktur Pembelian (status diminta) dibuat untuk pemasoknya, lalu diisi saat barang datang.</div>',
            showCancelButton: true,
            confirmButtonText: 'Ya, setujui',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#28a745',
            reverseButtons: true
        }).then(function(result) {
            if (!result.value) return;
            $b.prop('disabled', true);
            post(BASE + '/' + $b.data('id') + '/approve', {}, 'Gagal menyetujui permintaan.').always(function() { $b.prop('disabled', false); });
        });
    });

    $('#permintaan-table').on('click', '.btn-reject', function(e) {
        e.preventDefault();
        var $b = $(this);
        Swal.fire({
            icon: 'warning',
            title: 'Tolak ' + esc($b.data('nomor')) + '?',
            input: 'textarea',
            inputPlaceholder: 'Alasan penolakan...',
            inputAttributes: { maxlength: 500 },
            inputValidator: function(value) { if (!value || !value.trim()) return 'Alasan penolakan wajib diisi.'; },
            showCancelButton: true,
            confirmButtonText: 'Tolak',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#d33',
            reverseButtons: true
        }).then(function(result) {
            if (!result.value) return;
            post(BASE + '/' + $b.data('id') + '/reject', { reason: result.value.trim() }, 'Gagal menolak permintaan.');
        });
    });

    $('#permintaan-table').on('click', '.btn-delete', function(e) {
        e.preventDefault();
        var $b = $(this);
        Swal.fire({
            icon: 'warning',
            title: 'Hapus ' + esc($b.data('nomor')) + '?',
            text: 'Permintaan dan itemnya dihapus permanen.',
            showCancelButton: true,
            confirmButtonText: 'Ya, hapus',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#d33',
            reverseButtons: true,
            focusCancel: true
        }).then(function(result) {
            if (!result.value) return;
            $.ajax({ url: BASE + '/' + $b.data('id'), type: 'DELETE', data: { _token: CSRF } })
                .done(function(res) {
                    table.draw(false);
                    Swal.fire({ icon: 'success', title: 'Berhasil', text: res.message, timer: 2000, showConfirmButton: false });
                })
                .fail(function(xhr) { Swal.fire({ icon: 'error', title: 'Gagal', text: xhrMessage(xhr, 'Gagal menghapus permintaan.') }); });
        });
    });

    $('#permintaan-table').on('click', '.btn-nilai-pembelian', function(e) {
        e.preventDefault();
        $.get(BASE + '/' + $(this).data('id') + '/nilai-pembelian').done(function(res) {
            $('#nilaiPembelianNoPermintaan').text(res.no_permintaan || '-');
            $('#nilaiPembelianTanggal').text(formatTanggal(res.request_date));
            var rows = (res.items || []).map(function(item) {
                var diskonLabel = ['percent', '%', 'persen'].indexOf(item.diskon_type) !== -1 ? item.diskon + '%' : formatRupiah(item.diskon);
                return '<tr>'
                    + '<td>' + esc(item.nama_obat)
                        + '<div class="mt-1">' + (item.principal_name && item.principal_name !== '-' ? '<span class="badge badge-info mr-1">' + esc(item.principal_name) + '</span>' : '')
                        + '<span class="badge ' + (item.jenis_obat === 'Generik' ? 'badge-success' : 'badge-primary') + '">' + esc(item.jenis_obat) + '</span>'
                        + (item.harga_ditemukan ? '' : ' <span class="badge badge-warning" title="Obat + pemasok ini belum ada di Master Pembelian">harga belum ada</span>')
                        + '</div></td>'
                    + '<td class="text-right">' + Number(item.qty || 0).toLocaleString('id-ID') + ' (' + Number(item.box || 0).toLocaleString('id-ID') + ' box)</td>'
                    + '<td class="text-right">' + formatRupiah(item.harga) + '</td>'
                    + '<td class="text-right">' + diskonLabel + '</td>'
                    + '<td class="text-right">' + formatRupiah(item.setelah_diskon) + '</td>'
                    + '<td class="text-right">' + formatRupiah(item.ppn) + '<br><small class="text-muted">' + item.ppn_rate + '%</small></td>'
                    + '<td class="text-right font-weight-bold">' + formatRupiah(item.total_harga) + '</td>'
                    + '</tr>';
            }).join('');
            $('#nilaiPembelianTableBody').html(rows || '<tr><td colspan="7" class="text-center text-muted">Belum ada item.</td></tr>');
            $('#nilaiPembelianSubtotalDiskon').text(formatRupiah(res.summary && res.summary.setelah_diskon));
            $('#nilaiPembelianPpn').text(formatRupiah(res.summary && res.summary.ppn));
            $('#nilaiPembelianGrandTotal').text(formatRupiah(res.summary && res.summary.total_harga));
            $('#nilaiPembelianModal').modal('show');
        }).fail(function(xhr) {
            Swal.fire({ icon: 'error', title: 'Gagal', text: xhrMessage(xhr, 'Gagal mengambil data nilai pembelian.') });
        });
    });
});
</script>
@endsection
