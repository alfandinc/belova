@extends('layouts.erm.app')

@section('title', 'ERM | Rekap Jasa Medis')
@section('navbar')
    @include('layouts.erm.navbar')
@endsection

@section('content')
<style>
    .jm-stat .h4 { font-weight: 700; }
    #rkTable td, #rkPending td, #rkDetailTable td { vertical-align: middle; }
</style>
<div class="container-fluid">
    <div class="d-flex flex-wrap align-items-center justify-content-between mt-3 mb-3" style="gap:8px">
        <div>
            <h4 class="page-title mb-0">Rekap Jasa Medis</h4>
            <small class="text-muted">Dihitung bila petugas sudah diisi di SPK <strong>dan invoice tindakan sudah lunas</strong>. Periode memakai tanggal tindakan di SPK.</small>
        </div>
        @if(auth()->user()->hasAnyRole(['Admin', 'Finance']))
        <a href="{{ route('erm.jasa-medis.index') }}" class="btn btn-light btn-sm"><i class="fas fa-tags"></i> Tarif Jasa Medis</a>
        @endif
    </div>

    <div class="card">
        <div class="card-body py-2">
            <div class="d-flex flex-wrap align-items-center" style="gap:8px">
                <div class="input-group input-group-sm" style="width:auto">
                    <div class="input-group-prepend"><span class="input-group-text">Bulan</span></div>
                    <input type="month" id="rkBulan" class="form-control" value="{{ now()->format('Y-m') }}">
                </div>
                <select id="rkKlinik" class="custom-select custom-select-sm" style="width:auto">
                    <option value="">Semua klinik</option>
                    @foreach($kliniks as $k)
                        <option value="{{ $k->id }}">{{ $k->nama }}</option>
                    @endforeach
                </select>
                <select id="rkTipe" class="custom-select custom-select-sm" style="width:auto">
                    <option value="">Dokter & petugas</option>
                    <option value="dokter">Dokter saja</option>
                    <option value="employee">Petugas saja</option>
                </select>
                <button type="button" class="btn btn-success btn-sm ml-auto" id="rkExport"><i class="fas fa-file-excel"></i> Export Excel</button>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="card jm-stat"><div class="card-body py-3">
                <div class="text-muted small">Total jasa medis dihitung</div>
                <div class="h4 mb-0" id="stTotal">-</div>
                <div class="small text-muted" id="stPeriode"></div>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card jm-stat"><div class="card-body py-3">
                <div class="text-muted small">Tertunda (belum dihitung)</div>
                <div class="h4 mb-0" id="stPending">-</div>
                <div class="small text-muted" id="stPendingInfo"></div>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card jm-stat"><div class="card-body py-3">
                <div class="text-muted small">Tindakan dengan petugas belum lengkap</div>
                <div class="h4 mb-0" id="stTanpa">-</div>
                <div class="small text-muted">Jasa medisnya belum tercatat. Lengkapi di <a href="{{ route('erm.spk-tindakan.index') }}" target="_blank">SPK Tindakan</a>.</div>
            </div></div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h6 class="mb-2">Per petugas</h6>
            <table id="rkTable" class="table table-sm table-hover w-100">
                <thead class="thead-light">
                    <tr>
                        <th>Petugas</th>
                        <th>Tipe</th>
                        <th class="text-right">Pasien</th>
                        <th class="text-right">Tindakan</th>
                        <th class="text-right">Total Jasa Medis</th>
                        <th class="text-right" style="width:1%">Aksi</th>
                    </tr>
                </thead>
                <tfoot>
                    <tr><th colspan="4" class="text-right">Total</th><th class="text-right" id="rkFootTotal">-</th><th></th></tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex align-items-center mb-2">
                <h6 class="mb-0">Tertunda</h6>
                <span class="small text-muted ml-2">Masuk rekap otomatis setelah invoice-nya lunas.</span>
            </div>
            <table id="rkPending" class="table table-sm table-hover w-100">
                <thead class="thead-light">
                    <tr>
                        <th>Tanggal</th>
                        <th>Pasien</th>
                        <th>Tindakan</th>
                        <th>Petugas</th>
                        <th class="text-right">Jasa Medis</th>
                        <th>Status Invoice</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="rkDetailModal" tabindex="-1" role="dialog" aria-labelledby="rkDetailLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="rkDetailLabel">Detail</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <table id="rkDetailTable" class="table table-sm table-bordered mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Tanggal</th>
                            <th>Pasien</th>
                            <th>Paket</th>
                            <th>Tindakan</th>
                            <th class="text-right">Tarif</th>
                            <th class="text-center">Petugas</th>
                            <th class="text-right">Jasa Medis</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                    <tfoot><tr><th colspan="6" class="text-right">Total</th><th class="text-right" id="rkDetailTotal"></th></tr></tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(function () {
    var state = { rows: [] };

    function esc(v) { return $('<div>').text(v == null ? '' : String(v)).html(); }
    function rupiah(n) { return 'Rp' + Number(n || 0).toLocaleString('id-ID', { maximumFractionDigits: 2 }); }
    function tgl(d) { return d ? moment(d).format('DD MMM YYYY') : '-'; }
    function tipeBadge(t) { return t === 'dokter' ? '<span class="badge badge-primary">Dokter</span>' : '<span class="badge badge-info">Petugas</span>'; }
    function invoiceBadge(status, label) {
        var cls = { paid: 'success', partial: 'warning', issued: 'secondary' }[status] || 'light';
        return '<span class="badge badge-' + cls + '">' + esc(label) + '</span>';
    }
    function filters() { return { bulan: $('#rkBulan').val(), klinik_id: $('#rkKlinik').val(), tipe: $('#rkTipe').val() }; }

    var lang = {
        zeroRecords: 'Tidak ada data', emptyTable: 'Tidak ada data pada periode ini',
        info: '_START_–_END_ dari _TOTAL_', infoEmpty: '0 data', infoFiltered: '',
        lengthMenu: 'Tampil _MENU_', paginate: { previous: '‹', next: '›' }
    };

    var table = $('#rkTable').DataTable({
        dom: 'rt<"d-flex flex-wrap justify-content-between align-items-center mt-2"lip>',
        data: [], order: [[4, 'desc']], pageLength: 50, language: lang,
        columns: [
            { data: 'penerima', render: function (d, t) { return t === 'display' ? '<strong>' + esc(d) + '</strong>' : d; } },
            { data: 'tipe', render: function (d, t) { return t === 'display' ? tipeBadge(d) : d; } },
            { data: 'pasien', className: 'text-right' },
            { data: 'jumlah', className: 'text-right' },
            { data: 'total', className: 'text-right', render: function (d, t) { return t === 'display' ? '<strong>' + rupiah(d) + '</strong>' : d; } },
            {
                data: 'penerima_key', orderable: false, className: 'text-right text-nowrap',
                render: function (d) { return '<button type="button" class="btn btn-sm btn-outline-primary btn-detail" data-key="' + esc(d) + '"><i class="fas fa-list"></i> Detail</button>'; }
            }
        ]
    });

    var pending = $('#rkPending').DataTable({
        dom: 'rt<"d-flex flex-wrap justify-content-between align-items-center mt-2"lip>',
        data: [], order: [[0, 'asc']], pageLength: 10, language: lang,
        columns: [
            { data: 'tanggal', render: function (d, t) { return t === 'display' ? tgl(d) : d; } },
            { data: 'pasien_nama', render: function (d, t, r) { return esc(d) + '<div class="small text-muted">RM ' + esc(r.pasien_id) + '</div>'; } },
            { data: 'tindakan', render: function (d, t, r) { return esc(d) + '<div class="small text-muted">' + esc(r.paket || '') + '</div>'; } },
            { data: 'penerima', render: function (d, t, r) { return esc(d) + ' ' + tipeBadge(r.tipe); } },
            { data: 'nominal', className: 'text-right', render: function (d, t) { return t === 'display' ? rupiah(d) : d; } },
            { data: 'invoice', render: function (d, t, r) { return t === 'display' ? invoiceBadge(r.invoice_status, d) : d; } }
        ]
    });

    function load() {
        $.get('{{ route('erm.jasa-medis.rekap.data') }}', filters())
            .done(function (res) {
                state.rows = res.rows;
                $('#stTotal').text(rupiah(res.total));
                $('#stPeriode').text(res.periode);
                $('#stPending').text(rupiah(res.tertunda.total));
                $('#stPendingInfo').text(res.tertunda.jumlah + ' baris' + $.map(res.tertunda.per_status, function (n, label) { return ' · ' + n + ' ' + label.toLowerCase(); }).join(''));
                $('#stTanpa').text(res.tanpa_petugas + ' tindakan');
                $('#rkFootTotal').text(rupiah(res.total));
                table.clear().rows.add(res.ringkasan).draw();
                pending.clear().rows.add(res.rows.filter(function (r) { return !r.eligible; })).draw();
            })
            .fail(function (xhr) { Swal.fire({ icon: 'error', title: 'Gagal memuat rekap', html: belovaAjaxError(xhr) }); });
    }

    $('#rkBulan, #rkKlinik, #rkTipe').on('change', load);
    $('#rkExport').on('click', function () { window.location = '{{ route('erm.jasa-medis.rekap.export') }}?' + $.param(filters()); });

    $('#rkTable').on('click', '.btn-detail', function () {
        var key = String($(this).data('key'));
        var rows = state.rows.filter(function (r) { return r.eligible && r.penerima_key === key; });
        if (!rows.length) return;
        $('#rkDetailLabel').html(esc(rows[0].penerima) + ' ' + tipeBadge(rows[0].tipe));
        var total = 0;
        $('#rkDetailTable tbody').html(rows.map(function (r) {
            total += Number(r.nominal);
            return '<tr><td>' + tgl(r.tanggal) + '</td><td>' + esc(r.pasien_nama) + '<div class="small text-muted">RM ' + esc(r.pasien_id) + '</div></td>'
                + '<td>' + esc(r.paket || '-') + '</td><td>' + esc(r.tindakan) + '</td><td class="text-right">' + rupiah(r.tarif) + '</td>'
                + '<td class="text-center">' + r.jumlah_petugas + '</td><td class="text-right">' + rupiah(r.nominal) + '</td></tr>';
        }).join(''));
        $('#rkDetailTotal').text(rupiah(total));
        $('#rkDetailModal').modal('show');
    });

    load();
});
</script>
@endsection
