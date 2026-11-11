@extends('layouts.finance.app')
@section('title', 'Finance | Kepuasan Pasien')
@section('navbar')
    @include('layouts.finance.navbar')
@endsection
@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12">
            <h3 class="mb-0 font-weight-bold">Kepuasan Pasien</h3>
            <div class="text-muted small">Hasil survei dari layar pasien di kasir (rating 1–5 dan keluhan).</div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body py-3">
            <div class="d-flex flex-wrap align-items-end" style="gap:.75rem;">
                <div>
                    <label class="small text-muted mb-1" for="fFrom">Dari</label>
                    <input type="date" id="fFrom" class="form-control form-control-sm" value="{{ now()->startOfMonth()->toDateString() }}">
                </div>
                <div>
                    <label class="small text-muted mb-1" for="fTo">Sampai</label>
                    <input type="date" id="fTo" class="form-control form-control-sm" value="{{ now()->toDateString() }}">
                </div>
                <div>
                    <label class="small text-muted mb-1" for="fKlinik">Klinik</label>
                    <select id="fKlinik" class="form-control form-control-sm" style="min-width:170px;">
                        <option value="">Semua Klinik</option>
                        @foreach($kliniks as $k)
                            <option value="{{ $k->id }}">{{ $k->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="small text-muted mb-1" for="fRating">Rating</label>
                    <select id="fRating" class="form-control form-control-sm" style="min-width:170px;">
                        <option value="">Semua Rating</option>
                        @foreach(\App\Models\Finance\KepuasanPasien::LABELS as $r => $label)
                            <option value="{{ $r }}">{{ \App\Models\Finance\KepuasanPasien::EMOJI[$r] }} {{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="custom-control custom-checkbox mb-1">
                    <input type="checkbox" class="custom-control-input" id="fKeluhan">
                    <label class="custom-control-label" for="fKeluhan">Hanya yang ada keluhan</label>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 col-xl-3 mb-3">
            <div class="card shadow-sm border-0 h-100"><div class="card-body">
                <div class="text-muted small">Jumlah Survei</div>
                <div class="h3 mb-0 font-weight-bold" id="sTotal">-</div>
            </div></div>
        </div>
        <div class="col-md-6 col-xl-3 mb-3">
            <div class="card shadow-sm border-0 h-100"><div class="card-body">
                <div class="text-muted small">Rata-rata Rating</div>
                <div class="h3 mb-0 font-weight-bold" id="sAvg">-</div>
            </div></div>
        </div>
        <div class="col-md-6 col-xl-3 mb-3">
            <div class="card shadow-sm border-0 h-100"><div class="card-body">
                <div class="text-muted small">Puas &amp; Sangat Puas</div>
                <div class="h3 mb-0 font-weight-bold text-success" id="sPuas">-</div>
            </div></div>
        </div>
        <div class="col-md-6 col-xl-3 mb-3">
            <div class="card shadow-sm border-0 h-100"><div class="card-body">
                <div class="text-muted small">Dengan Keluhan / Saran</div>
                <div class="h3 mb-0 font-weight-bold text-warning" id="sKeluhan">-</div>
            </div></div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body">
            <div class="font-weight-bold mb-2">Sebaran Rating</div>
            <div id="distribusi"></div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table id="tblKepuasan" class="table table-bordered table-hover mb-0" style="width:100%;">
                    <thead>
                        <tr>
                            <th style="width:120px;">Tanggal</th>
                            <th>Pasien</th>
                            <th>Klinik</th>
                            <th>Dokter</th>
                            <th style="width:170px;">Rating</th>
                            <th>Keluhan / Saran</th>
                            <th>Kasir</th>
                            <th>Invoice</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(function () {
    const LABELS = @json(\App\Models\Finance\KepuasanPasien::LABELS);
    const EMOJI = @json(\App\Models\Finance\KepuasanPasien::EMOJI);
    const COLORS = { 1: '#dc3545', 2: '#fd7e14', 3: '#adb5bd', 4: '#20c997', 5: '#28a745' };

    function esc(v) { return $('<div>').text(v == null ? '' : v).html(); }
    function filters() {
        return {
            from: $('#fFrom').val(), to: $('#fTo').val(), klinik_id: $('#fKlinik').val(),
            rating: $('#fRating').val(), hanya_keluhan: $('#fKeluhan').is(':checked') ? 1 : 0
        };
    }

    const table = $('#tblKepuasan').DataTable({
        processing: true,
        serverSide: true,
        order: [[0, 'desc']],
        ajax: { url: @json(route('finance.kepuasan-pasien.data')), data: function (d) { Object.assign(d, filters()); } },
        columns: [
            { data: 'tanggal', name: 'tanggal', searchable: false },
            { data: 'pasien', name: 'pasien', orderable: false, render: (d, t) => t === 'display' ? esc(d) : d },
            { data: 'klinik', name: 'klinik', orderable: false, searchable: false, render: (d, t) => t === 'display' ? esc(d || '-') : d },
            { data: 'dokter', name: 'dokter', orderable: false, searchable: false, render: (d, t) => t === 'display' ? esc(d || '-') : d },
            { data: 'rating', name: 'rating', searchable: false, render: function (d, t, row) {
                if (t !== 'display') return d;
                return '<span class="badge" style="background:' + (COLORS[d] || '#6c757d') + ';color:#fff;font-size:12px;">' + esc(row.rating_label) + '</span>';
            } },
            { data: 'keluhan', name: 'keluhan', orderable: false, render: (d, t) => t === 'display' ? (d ? '<div style="white-space:pre-wrap;">' + esc(d) + '</div>' : '<span class="text-muted">-</span>') : d },
            { data: 'kasir', name: 'kasir', orderable: false, searchable: false, render: (d, t) => t === 'display' ? esc(d || '-') : d },
            { data: 'invoice', name: 'invoice', orderable: false, searchable: false, render: (d, t) => t === 'display' ? esc(d || '-') : d },
        ],
        language: { search: 'Cari pasien / keluhan:' }
    });

    function loadSummary() {
        $.getJSON(@json(route('finance.kepuasan-pasien.summary')), filters()).then(function (s) {
            $('#sTotal').text(s.total);
            $('#sAvg').text(s.avg == null ? '-' : String(s.avg).replace('.', ',') + ' / 5');
            $('#sPuas').text(s.puas_pct == null ? '-' : String(s.puas_pct).replace('.', ',') + '%');
            $('#sKeluhan').text(s.keluhan);
            let html = '';
            for (let r = 5; r >= 1; r--) {
                const n = (s.distribusi && s.distribusi[r]) || 0;
                const pct = s.total ? Math.round(n / s.total * 100) : 0;
                html += '<div class="d-flex align-items-center mb-1" style="gap:.6rem;">'
                    + '<div style="width:170px;" class="small">' + EMOJI[r] + ' ' + esc(LABELS[r]) + '</div>'
                    + '<div class="flex-fill rounded" style="background:rgba(127,127,127,.15);height:14px;overflow:hidden;">'
                    + '<div style="width:' + pct + '%;height:100%;background:' + COLORS[r] + ';"></div></div>'
                    + '<div class="small text-right" style="width:80px;">' + n + ' (' + pct + '%)</div></div>';
            }
            $('#distribusi').html(html);
        });
    }

    $('#fFrom, #fTo, #fKlinik, #fRating, #fKeluhan').on('change', function () {
        table.ajax.reload();
        loadSummary();
    });
    loadSummary();
});
</script>
@endsection
