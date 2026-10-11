@extends('layouts.erm.app')
@section('title', 'Farmasi | Laporan Pembelian')
@section('navbar')
    @include('layouts.erm.navbar-farmasi')
@endsection

@section('content')
<div class="container-fluid">
    <style>
        .lp-card { border: 1px solid #e3e8f0; border-radius: .5rem; padding: .75rem 1rem; background: #fff; height: 100%; }
        .lp-card .lp-label { font-size: .75rem; text-transform: uppercase; letter-spacing: .04em; color: #6c7a92; }
        .lp-card .lp-value { font-size: 1.35rem; font-weight: 700; color: #1f3b73; line-height: 1.2; }
        .lp-dim .btn { min-width: 100px; }
        .lp-bar { height: 6px; background: #e9eef6; border-radius: 3px; overflow: hidden; margin-top: 3px; }
        .lp-bar > span { display: block; height: 100%; background: #4e73df; }
        #lpTable td { vertical-align: middle; }
        #lpTable tbody tr { cursor: pointer; }
        .lp-trend-up { color: #c0392b; }
        .lp-trend-down { color: #1e8449; }
    </style>

    <div class="d-flex flex-wrap align-items-center justify-content-between mt-3 mb-3">
        <div>
            <h4 class="page-title mb-0">Laporan Pembelian</h4>
            <small class="text-muted">Nilai pembelian dari faktur yang sudah disetujui (berdasarkan tanggal terima), dikurangi retur ke supplier yang disetujui di periode yang sama.</small>
        </div>
        <div class="mt-2 mt-md-0">
            <button type="button" class="btn btn-outline-success" id="lpExport" title="Ringkasan sesuai tabel"><i class="fas fa-file-excel"></i> Excel Ringkasan</button>
            <button type="button" class="btn btn-outline-success" id="lpExportDetail" title="Semua baris faktur di periode ini"><i class="fas fa-file-excel"></i> Excel Detail</button>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body pb-2">
            <div class="form-row align-items-end">
                <div class="col-md-3 mb-2">
                    <label class="small text-muted mb-1" for="lpPreset">Periode</label>
                    <select id="lpPreset" class="form-control form-control-sm">
                        <option value="bulan_ini">Bulan ini</option>
                        <option value="bulan_lalu" selected>Bulan lalu</option>
                        <option value="3_bulan">3 bulan terakhir</option>
                        <option value="6_bulan">6 bulan terakhir</option>
                        <option value="tahun_ini">Tahun ini</option>
                        <option value="tahun_lalu">Tahun lalu</option>
                        <option value="kustom">Pilih tanggal...</option>
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <label class="small text-muted mb-1" for="lpRange">Tanggal</label>
                    <input type="text" id="lpRange" class="form-control form-control-sm" readonly>
                </div>
                <div class="col-md-6 mb-2">
                    <label class="small text-muted mb-1 d-block">Kelompokkan per</label>
                    <div class="btn-group btn-group-sm lp-dim" role="group">
                        <button type="button" class="btn btn-primary" data-dim="principal"><i class="fas fa-industry"></i> Principal</button>
                        <button type="button" class="btn btn-outline-primary" data-dim="pemasok"><i class="fas fa-truck"></i> Pemasok</button>
                        <button type="button" class="btn btn-outline-primary" data-dim="obat"><i class="fas fa-pills"></i> Obat</button>
                    </div>
                </div>
            </div>
            <div class="form-row align-items-end">
                <div class="col-md-3 mb-2">
                    <label class="small text-muted mb-1">Hanya principal</label>
                    <select id="lpPrincipal" class="form-control form-control-sm" style="width:100%"></select>
                </div>
                <div class="col-md-3 mb-2">
                    <label class="small text-muted mb-1">Hanya pemasok</label>
                    <select id="lpPemasok" class="form-control form-control-sm" style="width:100%"></select>
                </div>
                <div class="col-md-4 mb-2">
                    <label class="small text-muted mb-1">Hanya obat</label>
                    <select id="lpObat" class="form-control form-control-sm" style="width:100%"></select>
                </div>
                <div class="col-md-2 mb-2">
                    <button type="button" class="btn btn-light btn-sm btn-block" id="lpReset"><i class="fas fa-undo"></i> Reset filter</button>
                </div>
            </div>
        </div>
    </div>

    <div class="alert alert-warning small d-none" id="lpAnomali"></div>

    <div class="row mb-3">
        <div class="col-6 col-lg mb-2"><div class="lp-card"><div class="lp-label">Total pembelian</div><div class="lp-value" id="lpTotal">-</div><div class="small text-muted">sebelum PPN <span id="lpDpp">-</span> · PPN <span id="lpPpn">-</span></div></div></div>
        <div class="col-6 col-lg mb-2"><div class="lp-card"><div class="lp-label">Retur ke supplier</div><div class="lp-value text-danger" id="lpRetur">-</div><div class="small text-muted">disetujui di periode ini</div></div></div>
        <div class="col-6 col-lg mb-2"><div class="lp-card"><div class="lp-label">Netto</div><div class="lp-value" id="lpNetto">-</div><div class="small" id="lpTrend">-</div></div></div>
        <div class="col-6 col-lg mb-2"><div class="lp-card"><div class="lp-label">Faktur</div><div class="lp-value" id="lpFaktur">-</div><div class="small text-muted"><span id="lpGrup">-</span></div></div></div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-2">
                <div class="font-weight-bold" id="lpTableTitle">Per principal</div>
                <input type="search" id="lpSearch" class="form-control form-control-sm" style="max-width:260px" placeholder="Cari di tabel...">
            </div>
            <table class="table table-sm table-hover w-100" id="lpTable">
                <thead class="thead-light">
                    <tr>
                        <th style="width:40px">#</th>
                        <th id="lpColNama">Principal</th>
                        <th class="text-right">Faktur</th>
                        <th class="text-right">Qty</th>
                        <th class="text-right">Total pembelian</th>
                        <th class="text-right">Retur</th>
                        <th class="text-right">Netto</th>
                        <th style="width:160px">Porsi</th>
                        <th class="text-right" title="Netto periode sebelumnya dengan panjang yang sama">vs sebelumnya</th>
                    </tr>
                </thead>
                <tfoot>
                    <tr class="font-weight-bold">
                        <td></td>
                        <td>Total</td>
                        <td class="text-right" id="lpFootFaktur"></td>
                        <td class="text-right" id="lpFootQty"></td>
                        <td class="text-right" id="lpFootTotal"></td>
                        <td class="text-right" id="lpFootRetur"></td>
                        <td class="text-right" id="lpFootNetto"></td>
                        <td></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
            <small class="text-muted">Klik baris untuk melihat faktur-fakturnya. Total pembelian = harga setelah diskon + PPN sesuai faktur.</small>
        </div>
    </div>
</div>

<div class="modal fade" id="lpDetailModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable" role="document" style="max-width:1400px;width:96%">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="lpDetailTitle">Detail</h5>
                    <small class="text-muted" id="lpDetailSub"></small>
                </div>
                <div class="ml-auto d-flex align-items-start">
                    <button type="button" class="btn btn-sm btn-success mr-2" id="lpDetailExport" title="Semua baris dari pilihan ini, lengkap dengan gudang, kadaluarsa, diskon, pajak dan jatuh tempo"><i class="fas fa-file-excel"></i> Download Excel</button>
                    <button type="button" class="close ml-0" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
            </div>
            <div class="modal-body">
                {{-- Summary of the rows currently shown (follows search and filters) --}}
                <div class="row mb-2" id="lpDetailStats">
                    <div class="col-6 col-md mb-2"><div class="lp-card py-2"><div class="lp-label">Baris / faktur</div><div class="h5 mb-0 font-weight-bold" id="lpStatRows">-</div></div></div>
                    <div class="col-6 col-md mb-2"><div class="lp-card py-2"><div class="lp-label">Qty</div><div class="h5 mb-0 font-weight-bold" id="lpStatQty">-</div></div></div>
                    <div class="col-6 col-md mb-2"><div class="lp-card py-2"><div class="lp-label">Total</div><div class="h5 mb-0 font-weight-bold" id="lpStatTotal">-</div></div></div>
                    <div class="col-6 col-md mb-2"><div class="lp-card py-2"><div class="lp-label" title="Total dibagi qty (setelah diskon, termasuk PPN)">Harga per unit dibayar</div><div class="h5 mb-0 font-weight-bold" id="lpStatUnit">-</div><div class="small text-muted" id="lpStatRange"></div></div></div>
                </div>

                <div class="form-row align-items-end mb-2">
                    <div class="col-md-4 mb-2">
                        <input type="search" id="lpDetailSearch" class="form-control form-control-sm" placeholder="Cari no faktur, obat, batch, pemasok...">
                    </div>
                    <div class="col-md-2 mb-2 lp-detail-filter" data-col="pemasok">
                        <select id="lpDetailPemasok" class="custom-select custom-select-sm"><option value="">Semua pemasok</option></select>
                    </div>
                    <div class="col-md-3 mb-2 lp-detail-filter" data-col="obat">
                        <select id="lpDetailObat" class="custom-select custom-select-sm"><option value="">Semua obat</option></select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <select id="lpDetailBulan" class="custom-select custom-select-sm"><option value="">Semua bulan</option></select>
                    </div>
                    <div class="col-md-1 mb-2">
                        <button type="button" class="btn btn-sm btn-light btn-block" id="lpDetailReset" title="Kosongkan pencarian dan filter"><i class="fas fa-undo"></i></button>
                    </div>
                </div>
                <div class="alert alert-warning small py-2 d-none" id="lpDetailAnomali"></div>

                <table class="table table-bordered table-sm table-hover w-100" id="lpDetailTable">
                    <thead class="thead-light">
                        <tr>
                            <th>Tanggal</th>
                            <th>No faktur</th>
                            <th>Pemasok</th>
                            <th>Obat</th>
                            <th>Principal</th>
                            <th>Batch / ED</th>
                            <th class="text-right">Qty</th>
                            <th class="text-right">Harga</th>
                            <th class="text-right">Diskon</th>
                            <th class="text-right">PPN</th>
                            <th class="text-right">Total</th>
                            <th class="text-right" title="Total / qty">Per unit</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr class="font-weight-bold">
                            <td colspan="6" class="text-right">Total (sesuai filter)</td>
                            <td class="text-right" id="lpDetailQty"></td>
                            <td></td><td></td>
                            <td class="text-right" id="lpDetailPpn"></td>
                            <td class="text-right" id="lpDetailTotal"></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(function () {
    var URLS = {
        data: '{{ route('erm.laporan-pembelian.data') }}',
        detail: '{{ route('erm.laporan-pembelian.detail') }}',
        export: '{{ route('erm.laporan-pembelian.export') }}',
        exportDetail: '{{ route('erm.laporan-pembelian.export-detail') }}',
        faktur: '{{ url('/erm/fakturpembelian') }}'
    };
    var DIM_LABEL = { principal: 'Principal', pemasok: 'Pemasok', obat: 'Obat' };
    var state = { dim: 'principal', start: null, end: null, last: null, detailRow: null };

    function esc(v) { return $('<div>').text(v == null ? '' : v).html(); }
    function rupiah(v) { return 'Rp ' + Number(v || 0).toLocaleString('id-ID', { maximumFractionDigits: 0 }); }
    function fmt(v) { return Number(v || 0).toLocaleString('id-ID', { maximumFractionDigits: 2 }); }
    function tgl(v) { return v ? moment(v).format('DD/MM/YYYY') : '-'; }

    // ---------- Period ----------
    var PRESETS = {
        bulan_ini: function () { return [moment().startOf('month'), moment().endOf('month')]; },
        bulan_lalu: function () { return [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]; },
        '3_bulan': function () { return [moment().subtract(2, 'month').startOf('month'), moment().endOf('month')]; },
        '6_bulan': function () { return [moment().subtract(5, 'month').startOf('month'), moment().endOf('month')]; },
        tahun_ini: function () { return [moment().startOf('year'), moment().endOf('year')]; },
        tahun_lalu: function () { return [moment().subtract(1, 'year').startOf('year'), moment().subtract(1, 'year').endOf('year')]; }
    };
    function setRange(start, end) {
        state.start = start.format('YYYY-MM-DD');
        state.end = end.format('YYYY-MM-DD');
        $('#lpRange').val(start.format('DD/MM/YYYY') + ' - ' + end.format('DD/MM/YYYY'));
        var picker = $('#lpRange').data('daterangepicker');
        if (picker) { picker.setStartDate(start); picker.setEndDate(end); }
    }
    $('#lpRange').daterangepicker({ autoUpdateInput: false, locale: { format: 'DD/MM/YYYY', applyLabel: 'Terapkan', cancelLabel: 'Batal' } });
    $('#lpRange').on('apply.daterangepicker', function (ev, picker) {
        $('#lpPreset').val('kustom');
        setRange(picker.startDate, picker.endDate);
        load();
    });
    $('#lpPreset').on('change', function () {
        var preset = PRESETS[$(this).val()];
        if (!preset) { $('#lpRange').trigger('click'); return; }
        var r = preset();
        setRange(r[0], r[1]);
        load();
    });

    // ---------- Filters ----------
    function select2For($el, url, placeholder) {
        $el.select2({
            placeholder: placeholder, allowClear: true, width: '100%', minimumInputLength: 2,
            ajax: { url: url, dataType: 'json', delay: 250, data: function (p) { return { q: p.term }; }, processResults: function (d) { return { results: d }; } }
        }).on('change', load);
    }
    select2For($('#lpPrincipal'), '{{ url('/erm/ajax/principal') }}', 'Semua principal');
    select2For($('#lpPemasok'), '{{ url('/erm/ajax/pemasok') }}', 'Semua pemasok');
    select2For($('#lpObat'), '{{ url('/erm/ajax/obat') }}', 'Semua obat');

    $('.lp-dim').on('click', '.btn', function () {
        state.dim = $(this).data('dim');
        $('.lp-dim .btn').removeClass('btn-primary').addClass('btn-outline-primary');
        $(this).removeClass('btn-outline-primary').addClass('btn-primary');
        load();
    });
    $('#lpReset').on('click', function () {
        $('#lpPrincipal, #lpPemasok, #lpObat').val(null).trigger('change.select2');
        load();
    });

    function params(extra) {
        return $.extend({
            dim: state.dim, start: state.start, end: state.end,
            principal_id: $('#lpPrincipal').val() || '',
            pemasok_id: $('#lpPemasok').val() || '',
            obat_id: $('#lpObat').val() || ''
        }, extra || {});
    }

    // ---------- Table ----------
    var table = $('#lpTable').DataTable({
        data: [],
        dom: 'rt<"d-flex flex-wrap justify-content-between align-items-center mt-2"lip>',
        pageLength: 25,
        lengthMenu: [10, 25, 50, 100, 500],
        order: [[6, 'desc']],
        language: {
            zeroRecords: 'Tidak ada pembelian di periode ini',
            emptyTable: 'Tidak ada pembelian di periode ini',
            info: '_START_–_END_ dari _TOTAL_',
            infoEmpty: '',
            lengthMenu: 'Tampil _MENU_',
            paginate: { previous: '‹', next: '›' }
        },
        columns: [
            { data: null, orderable: false, render: function (d, t, r, meta) { return meta.row + 1; } },
            { data: 'nama', render: function (d, t) { return t === 'display' ? '<strong>' + esc(d) + '</strong>' : d; } },
            { data: 'faktur', className: 'text-right' },
            { data: 'qty', className: 'text-right', render: function (d, t) { return t === 'display' ? fmt(d) : d; } },
            { data: 'total', className: 'text-right text-nowrap', render: function (d, t) { return t === 'display' ? rupiah(d) : d; } },
            { data: 'retur', className: 'text-right text-nowrap', render: function (d, t) { return t === 'display' ? (d > 0 ? '<span class="text-danger">' + rupiah(d) + '</span>' : '<span class="text-muted">-</span>') : d; } },
            { data: 'netto', className: 'text-right text-nowrap font-weight-bold', render: function (d, t) { return t === 'display' ? rupiah(d) : d; } },
            { data: 'persen', render: function (d, t) { return t === 'display' ? '<div class="small">' + fmt(d) + '%</div><div class="lp-bar"><span style="width:' + Math.min(100, Math.max(0, d)) + '%"></span></div>' : d; } },
            {
                data: 'netto_sebelumnya', className: 'text-right text-nowrap small',
                render: function (d, t, r) {
                    if (t !== 'display') return d;
                    if (!d) return r.netto > 0 ? '<span class="text-muted">baru</span>' : '-';
                    var change = (r.netto - d) / d * 100;
                    return '<span class="' + (change > 0 ? 'lp-trend-up' : 'lp-trend-down') + '">' + (change > 0 ? '▲ ' : '▼ ') + fmt(Math.abs(change)) + '%</span><div class="text-muted">' + rupiah(d) + '</div>';
                }
            }
        ],
        footerCallback: function () {
            var rows = this.api().rows({ search: 'applied' }).data().toArray();
            var sum = function (f) { return rows.reduce(function (a, r) { return a + (r[f] || 0); }, 0); };
            $('#lpFootQty').text(fmt(sum('qty')));
            $('#lpFootTotal').text(rupiah(sum('total')));
            $('#lpFootRetur').text(rupiah(sum('retur')));
            $('#lpFootNetto').text(rupiah(sum('netto')));
            $('#lpFootFaktur').text(state.last ? state.last.summary.faktur : '');
        }
    });
    var searchTimer;
    $('#lpSearch').on('input', function () {
        var v = this.value;
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function () { table.search(v).draw(); }, 200);
    });

    function load() {
        if (!state.start) return;
        $('#lpTable').closest('.card').css('opacity', .6);
        $.get(URLS.data, params()).done(function (res) {
            state.last = res;
            var s = res.summary;
            $('#lpTotal').text(rupiah(s.total));
            $('#lpDpp').text(rupiah(s.dpp));
            $('#lpPpn').text(rupiah(s.ppn));
            $('#lpRetur').text(rupiah(s.retur));
            $('#lpNetto').text(rupiah(s.netto));
            $('#lpFaktur').text(Number(s.faktur).toLocaleString('id-ID'));
            $('#lpGrup').text(s.jumlah_grup + ' ' + DIM_LABEL[res.dim].toLowerCase());
            var prev = s.netto_sebelumnya;
            var prevLabel = tgl(res.periode_sebelumnya.start) + ' – ' + tgl(res.periode_sebelumnya.end);
            if (prev > 0) {
                var change = (s.netto - prev) / prev * 100;
                $('#lpTrend').html('<span class="' + (change > 0 ? 'lp-trend-up' : 'lp-trend-down') + '">' + (change > 0 ? '▲ ' : '▼ ') + fmt(Math.abs(change)) + '%</span> <span class="text-muted">vs ' + prevLabel + ' (' + rupiah(prev) + ')</span>');
            } else {
                $('#lpTrend').html('<span class="text-muted">tidak ada pembelian ' + prevLabel + '</span>');
            }

            var anomali = res.anomali_pajak || [];
            $('#lpAnomali').toggleClass('d-none', !anomali.length).html(anomali.length
                ? '<i class="fas fa-exclamation-triangle"></i> <strong>' + anomali.length + ' faktur punya PPN tidak wajar</strong> (lebih dari 20% subtotal), kemungkinan salah input dan membuat total membengkak: '
                    + anomali.map(function (a) { return esc(a.no_faktur || ('#' + a.id)) + ' (PPN ' + rupiah(a.pajak) + ' dari subtotal ' + rupiah(a.subtotal) + ')'; }).join(', ')
                    + '. Periksa faktur tersebut.'
                : '');

            $('#lpTableTitle').text('Per ' + DIM_LABEL[res.dim].toLowerCase() + ' · ' + tgl(res.periode.start) + ' – ' + tgl(res.periode.end));
            $(table.column(1).header()).text(DIM_LABEL[res.dim]);
            table.clear().rows.add(res.rows).draw();
        }).fail(function () {
            Swal.fire({ icon: 'error', title: 'Gagal', text: 'Laporan tidak dapat dimuat.' });
        }).always(function () {
            $('#lpTable').closest('.card').css('opacity', 1);
        });
    }

    // ---------- Detail (modal) ----------
    var DETAIL_COL = { pemasok: 2, obat: 3, principal: 4 };
    var detailTable = $('#lpDetailTable').DataTable({
        data: [],
        dom: 'rt<"d-flex flex-wrap justify-content-between align-items-center mt-2"lip>',
        pageLength: 25,
        lengthMenu: [10, 25, 50, 100, 500],
        order: [[0, 'desc']],
        autoWidth: false,
        language: {
            zeroRecords: 'Tidak ada baris yang cocok',
            emptyTable: 'Tidak ada faktur',
            info: '_START_–_END_ dari _TOTAL_ baris',
            infoEmpty: '',
            infoFiltered: '(dari _MAX_)',
            lengthMenu: 'Tampil _MENU_',
            paginate: { previous: '‹', next: '›' }
        },
        createdRow: function (row, d) {
            if (d.pajak_tidak_wajar) $(row).addClass('table-warning');
        },
        columns: [
            { data: 'received_date', className: 'text-nowrap', render: function (d, t) { return t === 'display' ? tgl(d) : d; } },
            {
                data: 'no_faktur',
                render: function (d, t, r) {
                    if (t !== 'display') return (d || '') + ' ' + (r.no_permintaan || '');
                    return '<a href="' + URLS.faktur + '/' + r.faktur_id + '/print" target="_blank" title="Buka PDF faktur">' + esc(d || ('#' + r.faktur_id)) + ' <i class="fas fa-external-link-alt small"></i></a>'
                        + (r.no_permintaan ? '<div class="small text-muted">' + esc(r.no_permintaan) + '</div>' : '')
                        + (r.pajak_tidak_wajar ? '<div><span class="badge badge-warning" title="PPN faktur lebih dari 20% subtotal, kemungkinan salah input">PPN tidak wajar</span></div>' : '');
                }
            },
            { data: 'pemasok', render: function (d, t) { return t === 'display' ? esc(d || '-') : (d || ''); } },
            {
                data: 'obat',
                render: function (d, t, r) {
                    if (t !== 'display') return (d || '') + ' ' + (r.kode_obat || '');
                    return esc(d) + (r.kode_obat ? '<div class="small text-muted">' + esc(r.kode_obat) + '</div>' : '');
                }
            },
            { data: 'principal', render: function (d, t) { return t === 'display' ? esc(d || '-') : (d || ''); } },
            {
                data: 'batch',
                render: function (d, t, r) {
                    if (t !== 'display') return (d || '') + ' ' + (r.gudang || '');
                    return (d ? '<code>' + esc(d) + '</code>' : '<span class="text-muted">-</span>')
                        + '<div class="small text-muted">' + (r.expiration_date ? 'ED ' + tgl(r.expiration_date) : '') + (r.gudang ? (r.expiration_date ? ' · ' : '') + esc(r.gudang) : '') + '</div>';
                }
            },
            { data: 'qty', className: 'text-right text-nowrap', render: function (d, t) { return t === 'display' ? fmt(d) : d; } },
            { data: 'harga', className: 'text-right text-nowrap', render: function (d, t) { return t === 'display' ? rupiah(d) : d; } },
            {
                data: 'diskon', className: 'text-right text-nowrap',
                render: function (d, t, r) {
                    if (t !== 'display') return d;
                    if (!d) return '<span class="text-muted">-</span>';
                    return r.diskon_type === 'percent' ? fmt(d) + '%' : rupiah(d);
                }
            },
            { data: 'ppn', className: 'text-right text-nowrap', render: function (d, t, r) { return t === 'display' ? (d ? '<span class="' + (r.pajak_tidak_wajar ? 'text-danger font-weight-bold' : '') + '">' + rupiah(d) + '</span>' : '<span class="text-muted">-</span>') : d; } },
            { data: 'total', className: 'text-right text-nowrap font-weight-bold', render: function (d, t) { return t === 'display' ? rupiah(d) : d; } },
            { data: 'per_unit', className: 'text-right text-nowrap small', render: function (d, t) { return t === 'display' ? rupiah(d) : d; } }
        ],
        // detailTable is not assigned yet on the first draw (during init), so pass the API in
        drawCallback: function () { refreshDetailStats(this.api()); }
    });

    // Totals and price range of the rows that pass the search and filters
    function refreshDetailStats(api) {
        var rows = (api || detailTable).rows({ search: 'applied' }).data().toArray();
        var qty = 0, total = 0, ppn = 0, fakturs = {}, units = [];
        rows.forEach(function (r) {
            qty += r.qty; total += r.total; ppn += r.ppn;
            fakturs[r.faktur_id] = true;
            if (r.qty > 0 && !r.pajak_tidak_wajar) units.push(r.per_unit);
        });
        $('#lpStatRows').text(rows.length + ' / ' + Object.keys(fakturs).length + ' faktur');
        $('#lpStatQty').text(fmt(qty));
        $('#lpStatTotal').text(rupiah(total));
        $('#lpStatUnit').text(qty > 0 ? rupiah(total / qty) : '-');
        $('#lpStatRange').text(units.length > 1 ? 'termurah ' + rupiah(Math.min.apply(null, units)) + ' · termahal ' + rupiah(Math.max.apply(null, units)) : '');
        $('#lpDetailQty').text(fmt(qty));
        $('#lpDetailPpn').text(rupiah(ppn));
        $('#lpDetailTotal').text(rupiah(total));
    }

    function fillSelect($el, values, placeholder) {
        $el.html('<option value="">' + placeholder + '</option>' + values.map(function (v) {
            return '<option value="' + esc(v.value) + '">' + esc(v.label) + '</option>';
        }).join(''));
    }

    // Pemasok / obat / month filters on the raw row values (exact match)
    var detailFilter = { pemasok: '', obat: '', bulan: '' };
    $.fn.dataTable.ext.search.push(function (settings, data, index, raw) {
        if (settings.nTable.id !== 'lpDetailTable') return true;
        if (detailFilter.pemasok && raw.pemasok !== detailFilter.pemasok) return false;
        if (detailFilter.obat && raw.obat !== detailFilter.obat) return false;
        if (detailFilter.bulan && String(raw.received_date || '').substring(0, 7) !== detailFilter.bulan) return false;
        return true;
    });

    function resetDetailFilters() {
        $('#lpDetailSearch').val('');
        $('#lpDetailPemasok, #lpDetailObat, #lpDetailBulan').val('');
        detailFilter = { pemasok: '', obat: '', bulan: '' };
        detailTable.search('');
    }

    $('#lpDetailSearch').on('input', function () {
        var v = this.value;
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function () { detailTable.search(v).draw(); }, 200);
    });
    $('#lpDetailPemasok').on('change', function () { detailFilter.pemasok = this.value; detailTable.draw(); });
    $('#lpDetailObat').on('change', function () { detailFilter.obat = this.value; detailTable.draw(); });
    $('#lpDetailBulan').on('change', function () { detailFilter.bulan = this.value; detailTable.draw(); });
    $('#lpDetailReset').on('click', function () { resetDetailFilters(); detailTable.draw(); });

    $('#lpTable tbody').on('click', 'tr', function () {
        var row = table.row(this).data();
        if (!row) return;
        state.detailRow = row;
        $('#lpDetailTitle').text(DIM_LABEL[state.dim] + ': ' + row.nama);
        $('#lpDetailSub').text(tgl(state.start) + ' – ' + tgl(state.end) + ' · ' + rupiah(row.total) + ' dari ' + row.faktur + ' faktur');
        resetDetailFilters();
        detailTable.clear().draw();
        $('#lpStatRows, #lpStatQty, #lpStatTotal, #lpStatUnit').html('<i class="fas fa-spinner fa-spin small"></i>');
        $('#lpStatRange').text('');
        $('#lpDetailAnomali').addClass('d-none');
        // The grouped column is the same on every row: hide it and its filter
        $.each(DETAIL_COL, function (dim, col) { detailTable.column(col).visible(dim !== state.dim, false); });
        $('.lp-detail-filter').each(function () { $(this).toggle($(this).data('col') !== state.dim); });
        detailTable.columns.adjust();
        $('#lpDetailModal').modal('show');

        $.get(URLS.detail, params({ key: row.key })).done(function (res) {
            var lines = res.lines || [];
            var uniq = function (field, label) {
                var seen = {};
                return lines.reduce(function (list, l) {
                    var v = l[field];
                    if (v && !seen[v]) { seen[v] = true; list.push({ value: v, label: label ? label(v) : v }); }
                    return list;
                }, []).sort(function (a, b) { return String(a.label).localeCompare(String(b.label)); });
            };
            fillSelect($('#lpDetailPemasok'), uniq('pemasok'), 'Semua pemasok');
            fillSelect($('#lpDetailObat'), uniq('obat'), 'Semua obat');
            var months = {};
            lines.forEach(function (l) { if (l.received_date) months[String(l.received_date).substring(0, 7)] = true; });
            fillSelect($('#lpDetailBulan'), Object.keys(months).sort().reverse().map(function (m) {
                return { value: m, label: moment(m + '-01').format('MMMM YYYY') };
            }), 'Semua bulan');

            var anomali = {};
            lines.forEach(function (l) { if (l.pajak_tidak_wajar) anomali[l.no_faktur || ('#' + l.faktur_id)] = true; });
            var nomor = Object.keys(anomali);
            $('#lpDetailAnomali').toggleClass('d-none', !nomor.length).html(nomor.length
                ? '<i class="fas fa-exclamation-triangle"></i> Baris kuning berasal dari faktur dengan PPN tidak wajar (' + nomor.map(esc).join(', ') + '). Harga per unit termurah/termahal tidak menghitung baris ini.'
                : '');
            if (res.dibatasi) $('#lpDetailSub').append(' · ditampilkan 1000 baris pertama, gunakan Download Excel untuk semua');

            detailTable.rows.add(lines).draw();
            detailTable.columns.adjust();
        }).fail(function () {
            Swal.fire({ icon: 'error', title: 'Gagal', text: 'Detail tidak dapat dimuat.' });
        });
    });
    $('#lpDetailModal').on('shown.bs.modal', function () { detailTable.columns.adjust(); });

    $('#lpExport').on('click', function (e) {
        e.preventDefault();
        window.location = URLS.export + '?' + $.param(params());
    });
    // Every faktur line under the current period and filters
    $('#lpExportDetail').on('click', function (e) {
        e.preventDefault();
        window.location = URLS.exportDetail + '?' + $.param(params());
    });
    // Only the lines of the row opened in the modal
    $('#lpDetailExport').on('click', function () {
        if (!state.detailRow) return;
        window.location = URLS.exportDetail + '?' + $.param(params({ key: state.detailRow.key, nama: state.detailRow.nama }));
    });

    // Default: last month
    var r = PRESETS.bulan_lalu();
    setRange(r[0], r[1]);
    load();
});
</script>
@endsection
