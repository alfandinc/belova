@extends('layouts.erm.app')
@section('title', 'Farmasi | Forecast Stok')
@section('navbar')
    @include('layouts.erm.navbar-farmasi')
@endsection

@section('content')
@php
    $forecastToday = \Carbon\Carbon::today();
    $canEdit = auth()->user() && auth()->user()->hasAnyRole(['Admin', 'Farmasi']);
@endphp
<div class="container-fluid">
    <style>
        .fc-card { border: 1px solid #e3e8f0; border-radius: .5rem; padding: .6rem .8rem; background: #fff; cursor: pointer; height: 100%; transition: box-shadow .15s, border-color .15s; }
        .fc-card:hover { box-shadow: 0 2px 8px rgba(0,0,0,.06); }
        .fc-card.active { border-color: #4e73df; box-shadow: 0 0 0 2px rgba(78,115,223,.25); }
        .fc-card .fc-num { font-size: 1.5rem; font-weight: 700; line-height: 1.1; }
        .fc-card .fc-lbl { font-size: .78rem; color: #6c7a92; }
        .fc-card .fc-hint { font-size: .72rem; color: #8a94a6; }
        #forecastTable td { vertical-align: middle; }
        #forecastTable tbody tr { cursor: pointer; }
        .fc-name { font-weight: 600; }
        .fc-meta .badge { font-size: .68rem; margin-right: 3px; }
        .fc-cover { height: 6px; background: #e9eef6; border-radius: 3px; overflow: hidden; margin-top: 3px; min-width: 80px; }
        .fc-cover > span { display: block; height: 100%; }
        .fc-explain dt { font-weight: 600; font-size: .85rem; }
        .fc-explain dd { font-size: .85rem; margin-bottom: .6rem; color: #495057; }
        .fc-step { border-left: 3px solid #e3e8f0; padding-left: .75rem; margin-bottom: .6rem; }
        .fc-step.fc-step-hit { border-left-color: #e74a3b; }
        .badge-soft { background: #eef2f7; color: #4a5568; }
    </style>

    <div class="d-flex flex-wrap align-items-center justify-content-between mt-3 mb-2">
        <div>
            <h4 class="page-title mb-0">Forecast Stok</h4>
            <small class="text-muted">Kapan dan berapa banyak obat perlu dipesan, dihitung dari pemakaian sebenarnya, waktu tunggu supplier, dan stok yang sedang dipesan.</small>
        </div>
        <div class="mt-2 mt-md-0">
            @if($canEdit)
            <a href="{{ route('erm.permintaan.index', ['baru' => 1, 'forecast' => 1]) }}" class="btn btn-primary" title="Buka form permintaan dan isi otomatis dengan obat Kritis / Perlu Pesan">
                <i class="fas fa-file-invoice"></i> Buat Permintaan dari Forecast
            </a>
            @endif
        </div>
    </div>

    <div class="row mb-2" id="fcCards">
        <div class="col-6 col-md-3 col-xl mb-2"><div class="fc-card" data-filter="perlu"><div class="fc-lbl">Perlu perhatian</div><div class="fc-num text-dark" data-count="perlu">-</div><div class="fc-hint">kritis + pesan + menunggu</div></div></div>
        <div class="col-6 col-md-3 col-xl mb-2"><div class="fc-card" data-filter="kritis"><div class="fc-lbl"><span class="badge badge-danger">Kritis</span></div><div class="fc-num text-danger" data-count="kritis">-</div><div class="fc-hint">habis sebelum barang datang</div></div></div>
        <div class="col-6 col-md-3 col-xl mb-2"><div class="fc-card" data-filter="pesan"><div class="fc-lbl"><span class="badge badge-warning">Perlu Pesan</span></div><div class="fc-num text-warning" data-count="pesan">-</div><div class="fc-hint">sudah di titik pesan</div></div></div>
        <div class="col-6 col-md-3 col-xl mb-2"><div class="fc-card" data-filter="menunggu"><div class="fc-lbl"><span class="badge badge-primary">Menunggu Barang</span></div><div class="fc-num text-primary" data-count="menunggu">-</div><div class="fc-hint">sudah dipesan, belum datang</div></div></div>
        <div class="col-6 col-md-3 col-xl mb-2"><div class="fc-card" data-filter="kontrol"><div class="fc-lbl"><i class="fas fa-user-clock" style="color:#6f42c1"></i> Pasien kontrol</div><div class="fc-num" style="color:#6f42c1" data-count="kontrol">-</div><div class="fc-hint"><span class="text-danger font-weight-bold" data-count="kontrol_kurang">-</span> stoknya kurang</div></div></div>
        <div class="col-6 col-md-3 col-xl mb-2"><div class="fc-card" data-filter="overstock"><div class="fc-lbl"><span class="badge badge-info">Overstock</span> / <span class="badge badge-secondary">Diam</span></div><div class="fc-num text-info" data-count="lebih">-</div><div class="fc-hint">modal tertahan</div></div></div>
        <div class="col-6 col-md-3 col-xl mb-2"><div class="fc-card" data-filter="stop"><div class="fc-lbl"><span class="badge badge-dark"><i class="fas fa-ban"></i> Stop Pengadaan</span></div><div class="fc-num text-dark" data-count="stop">-</div><div class="fc-hint">tidak dibeli lagi</div></div></div>
        <div class="col-6 col-md-3 col-xl mb-2"><div class="fc-card" data-filter="semua"><div class="fc-lbl">Semua obat aktif</div><div class="fc-num text-muted" data-count="semua">-</div><div class="fc-hint"><span data-count="aman">-</span> aman</div></div></div>
    </div>

    <div class="alert alert-light border py-2 mb-3" id="fcEstimasi" style="display:none !important">
        <i class="fas fa-coins text-warning"></i> Perkiraan nilai pesanan untuk obat <strong>Kritis + Perlu Pesan</strong>: <strong id="fcEstimasiNilai">-</strong> <small class="text-muted" id="fcEstimasiNote"></small>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-center mb-2" style="gap:8px">
                <input type="search" id="fcSearch" class="form-control form-control-sm" style="max-width:280px" placeholder="Cari nama obat atau zat aktif...">
                <div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input" id="fcFavorit">
                    <label class="custom-control-label small" for="fcFavorit"><i class="fas fa-star text-warning"></i> Favorit saja</label>
                </div>
                <div class="d-flex align-items-center" style="gap:6px" title="Pasien yang dijadwalkan kontrol di periode ini; kebutuhannya diambil dari resep kunjungan sebelumnya">
                    <label class="small text-muted mb-0 text-nowrap" for="forecast_keluar_period"><i class="fas fa-user-clock" style="color:#6f42c1"></i> Pasien kontrol</label>
                    <select class="custom-select custom-select-sm" id="forecast_keluar_period" style="width:auto">
                        <option value="today">hari ini</option>
                        <option value="week" selected>sampai akhir minggu</option>
                        <option value="month">sampai akhir bulan</option>
                        <option value="next_month">sampai akhir bulan depan</option>
                    </select>
                    <span class="spinner-border spinner-border-sm text-muted d-none" id="forecastKeluarLoadingState" role="status"></span>
                </div>
                <div class="ml-auto d-flex align-items-center" style="gap:6px">
                    <button class="btn btn-sm btn-outline-secondary" type="button" data-toggle="collapse" data-target="#fcSettings" aria-expanded="false"><i class="fas fa-sliders-h"></i> Pengaturan perhitungan</button>
                    <button type="button" class="btn btn-sm btn-outline-success" id="downloadForecastTable"><i class="fas fa-file-excel"></i> Excel</button>
                </div>
            </div>

            <div class="collapse mb-3" id="fcSettings">
                <div class="border rounded p-3 bg-light">
                    <div class="form-row">
                        <div class="col-md-3 mb-2">
                            <label class="small font-weight-bold mb-1" for="forecast_period_months">Histori pemakaian</label>
                            <select class="form-control form-control-sm" id="forecast_period_months">
                                <option value="1">1 bulan</option>
                                <option value="3" selected>3 bulan</option>
                                <option value="6">6 bulan</option>
                                <option value="12">12 bulan</option>
                            </select>
                            <small class="text-muted">Data penjualan − retur yang dipakai untuk menghitung rata-rata.</small>
                        </div>
                        <div class="col-md-3 mb-2">
                            <label class="small font-weight-bold mb-1" for="forecast_pengadaan_frequency">Seberapa sering belanja</label>
                            <select class="form-control form-control-sm" id="forecast_pengadaan_frequency">
                                <option value="monthly">Sebulan sekali</option>
                                <option value="weekly">Seminggu sekali</option>
                                <option value="twice_weekly" selected>Seminggu 2x</option>
                            </select>
                            <small class="text-muted">Stok maks = cukup sampai belanja berikutnya.</small>
                        </div>
                        <div class="col-md-3 mb-2">
                            <label class="small font-weight-bold mb-1" for="forecast_lead_time_days">Waktu tunggu supplier</label>
                            <select class="form-control form-control-sm" id="forecast_lead_time_days">
                                <option value="auto" selected>Otomatis dari riwayat faktur</option>
                                @foreach (\App\Services\ERM\ForecastService::LEAD_TIME_OPTIONS as $leadTimeOption)
                                    <option value="{{ $leadTimeOption }}">{{ $leadTimeOption }} hari (semua obat)</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Dari pesan sampai barang datang.</small>
                        </div>
                        <div class="col-md-3 mb-2">
                            <label class="small font-weight-bold mb-1" for="forecast_service_level">Tingkat keamanan stok</label>
                            <select class="form-control form-control-sm" id="forecast_service_level">
                                <option value="90">90% (hemat modal)</option>
                                <option value="95" selected>95% (standar)</option>
                                <option value="98">98% (jarang kosong)</option>
                            </select>
                            <small class="text-muted">Makin tinggi, cadangan stok makin besar.</small>
                        </div>
                    </div>
                    <div class="small text-muted mt-1" id="forecastFormulaInfo"></div>
                </div>
            </div>

            <div id="forecastLoadingState" class="text-center py-5 d-none">
                <div class="spinner-border text-primary" role="status"></div>
                <div class="mt-2 text-muted">Menghitung forecast...</div>
            </div>

            <div id="forecastContent">
                <div class="small text-muted mb-2"><span id="forecastPeriodInfo"></span> <span id="forecastKeluarPeriodInfo"></span></div>
                <table id="forecastTable" class="table table-sm table-hover w-100">
                    <thead class="thead-light">
                        <tr>
                            <th>Obat</th>
                            <th class="text-right" title="Stok semua gudang (tanpa gudang ED). Kecil: pesanan yang belum datang">Stok</th>
                            <th class="text-right" title="Rata-rata pemakaian per bulan (penjualan − retur)">Pemakaian / bln</th>
                            <th class="text-right" title="Perkiraan kebutuhan pasien yang dijadwalkan kontrol pada periode yang dipilih (dari resep kunjungan sebelumnya)">Pasien kontrol</th>
                            <th title="Berapa hari stok + pesanan yang belum datang cukup">Cukup untuk</th>
                            <th class="text-right" title="Pesan saat stok + pending sampai angka ini. Kecil: target stok setelah barang datang">Titik pesan</th>
                            <th class="text-right" title="Jumlah yang disarankan dipesan, dibulatkan per box">Saran pesan</th>
                            <th>Status</th>
                            <th class="text-right">Aksi</th>
                            <th></th>
                        </tr>
                    </thead>
                </table>
                <small class="text-muted">Klik baris untuk melihat cara hitungnya. Klik angka <span style="color:#6f42c1">pasien kontrol</span> untuk melihat daftar pasiennya. <i class="fas fa-shield-alt text-danger"></i> = Wajib Ada.</small>
            </div>
        </div>
    </div>

    {{-- Explanation of one obat's forecast --}}
    <div class="modal fade" id="fcDetailModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="fcDetailTitle">-</h5>
                        <div id="fcDetailBadges"></div>
                    </div>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body" id="fcDetailBody"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-primary btn-sm mr-auto btn-forecast-similar" id="fcDetailSimilar"><i class="fas fa-random"></i> Obat serupa</button>
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="similarObatModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Obat Serupa <small class="text-muted" id="similarObatName"></small></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2" id="similarObatSharedZatAktif"></div>
                    <div class="small text-muted mb-2" id="similarObatSelectedPrices"></div>
                    <div class="alert alert-light border d-none small" id="similarObatMessage"></div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm mb-0" id="similarObatTable">
                            <thead class="thead-light">
                                <tr><th>Nama Obat</th><th class="text-right">Stok</th><th class="text-right">Isi/box</th><th class="text-right">Harga beli</th><th class="text-right">Harga jual</th><th>Zat aktif sama</th></tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                    <small class="text-muted">Panah hijau = lebih murah dari obat yang dipilih, merah = lebih mahal.</small>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="forecastKeluarDetailModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="forecastKeluarDetailObatName">-</h5>
                        <small class="text-muted" id="forecastKeluarDetailPeriodInfo"></small>
                    </div>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <table class="table table-bordered table-sm mb-0" id="forecastKeluarDetailTable">
                        <thead class="thead-light"><tr><th>Pasien</th><th>Jadwal kontrol</th><th>Resep sebelumnya</th><th class="text-right">Jumlah</th></tr></thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(function () {
    var CSRF = '{{ csrf_token() }}';
    var CAN_EDIT = @json($canEdit);
    var URLS = {
        all: '{{ route('erm.obat.forecast-all') }}',
        export: '{{ route('erm.obat.forecast-all.export') }}',
        keluar: '{{ route('erm.obat.forecast-keluar') }}',
        obat: '{{ url('/erm/obat') }}',
        stopPengadaan: '{{ url('/erm/obat') }}/__ID__/stop-pengadaan'
    };
    var STATUS = {
        kritis: { label: 'Kritis', badge: 'badge-danger', rank: 0, color: '#e74a3b', text: 'Stok diperkirakan habis sebelum pesanan baru bisa datang. Pesan sekarang.' },
        pesan: { label: 'Perlu Pesan', badge: 'badge-warning', rank: 1, color: '#f6c23e', text: 'Stok + pesanan yang belum datang sudah sampai titik pesan.' },
        menunggu: { label: 'Menunggu Barang', badge: 'badge-primary', rank: 2, color: '#4e73df', text: 'Stok fisik menipis, tetapi pesanan yang belum datang sudah cukup. Pantau kedatangannya.' },
        aman: { label: 'Aman', badge: 'badge-success', rank: 3, color: '#1cc88a', text: 'Stok cukup sampai belanja berikutnya.' },
        overstock: { label: 'Overstock', badge: 'badge-info', rank: 4, color: '#36b9cc', text: 'Stok cukup untuk lebih dari 90 hari pemakaian. Tahan pembelian.' },
        tidak_bergerak: { label: 'Tidak Bergerak', badge: 'badge-secondary', rank: 5, color: '#858796', text: 'Ada stok, tetapi tidak terpakai selama periode histori.' },
        tidak_ada_pemakaian: { label: 'Tidak Ada Pemakaian', badge: 'badge-soft', rank: 6, color: '#d1d3e2', text: 'Tidak ada stok dan tidak ada pemakaian.' },
        stop_pengadaan: { label: 'Stop Pengadaan', badge: 'badge-dark', rank: 7, color: '#5a5c69', text: 'Tidak dibeli lagi. Sisa stok tetap dijual sampai habis, tidak ada saran pesan.' }
    };
    var LEAD_TIME_SOURCE = { supplier: 'dari riwayat faktur supplier', global: 'median semua supplier', 'default': 'nilai default', manual: 'diatur manual' };
    var FILTERS = {
        perlu: ['kritis', 'pesan', 'menunggu'],
        kritis: ['kritis'],
        pesan: ['pesan'],
        menunggu: ['menunggu'],
        overstock: ['overstock', 'tidak_bergerak'],
        stop: ['stop_pengadaan'],
        semua: null
    };
    var state = { filter: 'perlu', rows: [], kontrol: {} };
    var forecastTable = null;

    function esc(v) { return $('<div>').text(v == null ? '' : v).html(); }
    function num(v) { var n = Number(v || 0); return n.toLocaleString('id-ID', { maximumFractionDigits: Number.isInteger(n) ? 0 : 1 }); }
    function rupiah(v) { return v === null || v === undefined || v === '' ? '-' : 'Rp ' + Number(v).toLocaleString('id-ID', { maximumFractionDigits: 0 }); }
    function days(d) { return Number(d) > 365 ? '> 1 tahun' : num(Math.floor(Number(d))) + ' hari'; }
    function status(row) { return STATUS[row && row.status] || STATUS.tidak_ada_pemakaian; }
    function isTrue(v) { return v === true || v === 1 || String(v) === '1'; }
    function notify(icon, title, text) { Swal.fire({ icon: icon, title: title, text: text }); }

    // ---------- Forecast table ----------
    function coverHtml(row) {
        var cover = row.days_of_cover;
        if (cover === null || cover === undefined) return '<span class="text-muted small">-</span>';
        var st = status(row);
        var target = Math.max(Number(row.max_stock || 0) / Math.max(Number(row.daily_demand || 0), 0.0001), 1);
        var pct = Math.max(3, Math.min(100, cover / target * 100));
        return '<div class="small text-nowrap">' + days(cover) + '</div><div class="fc-cover"><span style="width:' + pct + '%;background:' + st.color + '"></span></div>';
    }

    function nameHtml(row) {
        var badges = [];
        badges.push('<span class="badge ' + (isTrue(row.is_generik) ? 'badge-success' : 'badge-light border') + '">' + (isTrue(row.is_generik) ? 'Generik' : 'Paten') + '</span>');
        (row.principal_names || []).forEach(function (p) { badges.push('<span class="badge badge-soft">' + esc(p) + '</span>'); });
        if (isTrue(row.stop_pengadaan)) badges.push('<span class="badge badge-dark" title="' + esc(row.stop_pengadaan_alasan || '') + '"><i class="fas fa-ban"></i> Stop Pengadaan</span>');
        if (isTrue(row.wajib_stok)) badges.push('<span class="badge badge-danger"><i class="fas fa-shield-alt"></i> Wajib Ada</span>');
        return '<div class="fc-name">' + (isTrue(row.is_favorite) ? '<i class="fas fa-star text-warning small"></i> ' : '') + esc(row.obat_nama) + '</div>'
            + '<div class="fc-meta">' + badges.join('') + '</div>'
            + ((row.warnings || []).length ? '<div class="small text-warning"><i class="fas fa-exclamation-triangle"></i> ' + esc(row.warnings[0]) + (row.warnings.length > 1 ? ' (+' + (row.warnings.length - 1) + ')' : '') + '</div>' : '');
    }

    function orderHtml(row) {
        if (!Number(row.qty_pesan || 0)) return '<span class="text-muted">-</span>';
        var box = row.jumlah_pesan_box !== null && row.jumlah_pesan_box !== undefined
            ? '<strong>' + num(row.jumlah_pesan_box) + ' box</strong>'
            : '<strong>' + num(row.qty_pesan) + '</strong> <span class="text-warning" title="Isi per box belum diketahui">(isi box ?)</span>';
        return box + '<div class="small text-muted">' + num(row.qty_pesan) + ' unit' + (row.estimasi_nilai ? ' · ' + rupiah(row.estimasi_nilai) : '') + '</div>';
    }

    function kontrolOf(row) { return state.kontrol[row.obat_id] || null; }
    function kontrolKurang(row) {
        var k = kontrolOf(row);
        return k ? Math.max(Number(k.dibutuhkan || 0) - Number(row.total_stock || 0), 0) : 0;
    }
    function kontrolHtml(row) {
        var k = kontrolOf(row);
        if (!k) return '<span class="text-muted">-</span>';
        var kurang = kontrolKurang(row);
        return '<a href="#" class="btn-kontrol-detail font-weight-bold" style="color:#6f42c1" data-id="' + row.obat_id + '" title="Lihat pasiennya">' + num(k.dibutuhkan) + '</a>'
            + '<div class="small text-muted">' + num(k.jumlah_kunjungan) + ' pasien</div>'
            + (kurang > 0 ? '<div class="small text-danger font-weight-bold">kurang ' + num(kurang) + '</div>' : '');
    }
    function renderKontrolCounts() {
        var total = 0, kurang = 0;
        state.rows.forEach(function (r) {
            if (!kontrolOf(r)) return;
            total++;
            if (kontrolKurang(r) > 0) kurang++;
        });
        $('#fcCards [data-count="kontrol"]').text(total.toLocaleString('id-ID'));
        $('#fcCards [data-count="kontrol_kurang"]').text(kurang.toLocaleString('id-ID'));
    }

    function actionsHtml(row) {
        var fav = isTrue(row.is_favorite), wajib = isTrue(row.wajib_stok);
        var html = '<button type="button" class="btn btn-sm btn-outline-secondary btn-forecast-similar" data-id="' + row.obat_id + '" title="Obat serupa (zat aktif sama)"><i class="fas fa-random"></i></button> '
            + '<button type="button" class="btn btn-sm ' + (fav ? 'btn-warning' : 'btn-outline-warning') + ' btn-obat-favorite" data-id="' + row.obat_id + '" title="' + (fav ? 'Hapus dari favorit' : 'Jadikan favorit') + '"><i class="' + (fav ? 'fas' : 'far') + ' fa-star"></i></button>';
        var stop = isTrue(row.stop_pengadaan);
        if (CAN_EDIT) {
            html += ' <button type="button" class="btn btn-sm ' + (stop ? 'btn-dark' : 'btn-outline-dark') + ' btn-stop-pengadaan" data-id="' + row.obat_id + '" data-stop="' + (stop ? 1 : 0) + '" data-nama="' + esc(row.obat_nama) + '" title="' + (stop ? 'Batalkan Stop Pengadaan' : 'Stop Pengadaan (tidak dibeli lagi)') + '"><i class="fas fa-ban"></i></button>';
        }
        if (CAN_EDIT && !stop) {
            html += ' <button type="button" class="btn btn-sm ' + (wajib ? 'btn-danger' : 'btn-outline-danger') + ' btn-toggle-wajib-stok" data-id="' + row.obat_id + '" data-wajib="' + (wajib ? 1 : 0) + '" title="' + (wajib ? 'Matikan Wajib Ada' : 'Tandai Wajib Ada') + '"><i class="fas fa-shield-alt"></i></button>';
        }
        return '<div class="text-nowrap">' + html + '</div>';
    }

    $.fn.dataTable.ext.search.push(function (settings, data, index, row) {
        if (settings.nTable.id !== 'forecastTable') return true;
        if (state.filter === 'kontrol') {
            if (!kontrolOf(row)) return false;
        } else {
            var allowed = FILTERS[state.filter];
            if (allowed && allowed.indexOf(row.status) === -1) return false;
        }
        if ($('#fcFavorit').is(':checked') && !isTrue(row.is_favorite)) return false;
        return true;
    });

    function buildTable(rows) {
        if (forecastTable) {
            forecastTable.clear().rows.add(rows).draw(false);
            return;
        }
        forecastTable = $('#forecastTable').DataTable({
            data: rows,
            dom: 'rt<"d-flex flex-wrap justify-content-between align-items-center mt-2"lip>',
            pageLength: 25,
            lengthMenu: [10, 25, 50, 100, 500],
            autoWidth: false,
            order: [[7, 'asc'], [4, 'asc']],
            language: {
                zeroRecords: 'Tidak ada obat di kelompok ini',
                emptyTable: 'Tidak ada obat aktif',
                info: '_START_–_END_ dari _TOTAL_ obat',
                infoEmpty: '0 obat',
                infoFiltered: '',
                lengthMenu: 'Tampil _MENU_',
                paginate: { previous: '‹', next: '›' }
            },
            createdRow: function (tr, row) {
                if (row.status === 'kritis') $(tr).addClass('table-danger');
                if (row.status === 'stop_pengadaan') $(tr).addClass('text-muted');
            },
            columns: [
                { data: 'obat_nama', render: function (d, t, r) { return t === 'display' ? nameHtml(r) : d; } },
                {
                    data: 'total_stock', className: 'text-right text-nowrap',
                    render: function (d, t, r) {
                        if (t !== 'display') return Number(d || 0);
                        return '<strong>' + num(d) + '</strong>' + (Number(r.approved_outstanding_permintaan || 0) > 0 ? '<div class="small text-primary" title="Sudah dipesan, belum datang">+' + num(r.approved_outstanding_permintaan) + ' dipesan</div>' : '');
                    }
                },
                {
                    data: 'average_monthly_keluar', className: 'text-right text-nowrap',
                    render: function (d, t, r) { return t === 'display' ? num(d) + (Number(r.std_monthly_keluar || 0) > 0 ? '<div class="small text-muted">± ' + num(r.std_monthly_keluar) + '</div>' : '') : Number(d || 0); }
                },
                {
                    data: null, className: 'text-right text-nowrap', searchable: false, orderSequence: ['desc', 'asc'],
                    render: function (d, t, r) {
                        if (t === 'display') return kontrolHtml(r);
                        var k = kontrolOf(r);
                        return k ? Number(k.dibutuhkan || 0) : 0;
                    }
                },
                {
                    data: 'days_of_cover',
                    render: function (d, t, r) { return t === 'display' ? coverHtml(r) : (d === null || d === undefined ? 999999 : Number(d)); }
                },
                {
                    data: 'reorder_point', className: 'text-right text-nowrap',
                    render: function (d, t, r) { return t === 'display' ? (Number(r.daily_demand || 0) || isTrue(r.wajib_stok) ? num(d) + '<div class="small text-muted">maks ' + num(r.max_stock) + '</div>' : '<span class="text-muted">-</span>') : Number(d || 0); }
                },
                { data: 'qty_pesan', className: 'text-right text-nowrap', render: function (d, t, r) { return t === 'display' ? orderHtml(r) : Number(d || 0); } },
                {
                    data: 'status',
                    render: function (d, t, r) {
                        var st = status(r);
                        if (t === 'sort' || t === 'type') return st.rank;
                        if (t === 'filter') return st.label;
                        return '<span class="badge ' + st.badge + '" title="' + esc(st.text) + '">' + st.label + '</span>';
                    }
                },
                { data: null, orderable: false, searchable: false, className: 'text-right', render: function (d, t, r) { return t === 'display' ? actionsHtml(r) : ''; } },
                { data: 'search_keywords', visible: false }
            ]
        });
    }

    function renderCounts(res) {
        var c = res.counts || {};
        var get = function (k) { return Number(c[k] || 0); };
        var values = {
            perlu: get('kritis') + get('pesan') + get('menunggu'),
            kritis: get('kritis'), pesan: get('pesan'), menunggu: get('menunggu'),
            lebih: get('overstock') + get('tidak_bergerak'),
            stop: get('stop_pengadaan'),
            aman: get('aman'),
            semua: state.rows.length
        };
        $.each(values, function (k, v) { $('#fcCards [data-count="' + k + '"]').text(v.toLocaleString('id-ID')); });
        renderKontrolCounts();

        var est = Number(res.estimasi_perlu_pesan || 0);
        $('#fcEstimasi').attr('style', est > 0 || res.perlu_pesan_tanpa_harga ? '' : 'display:none !important');
        $('#fcEstimasiNilai').text(rupiah(est));
        $('#fcEstimasiNote').text(res.perlu_pesan_tanpa_harga ? '(' + res.perlu_pesan_tanpa_harga + ' obat belum punya harga di Master Pembelian, tidak ikut dihitung)' : '(harga termurah di Master Pembelian)');
    }

    function setFilter(filter) {
        state.filter = filter;
        $('#fcCards .fc-card').removeClass('active').filter('[data-filter="' + filter + '"]').addClass('active');
        if (forecastTable) forecastTable.draw();
    }

    function loadForecast() {
        $('#forecastLoadingState').removeClass('d-none');
        $('#forecastContent').addClass('d-none');
        $.get(URLS.all, {
            period_months: $('#forecast_period_months').val(),
            pengadaan_frequency: $('#forecast_pengadaan_frequency').val(),
            lead_time_days: $('#forecast_lead_time_days').val(),
            service_level: $('#forecast_service_level').val()
        }).done(function (res) {
            state.rows = res.rows || [];
            state.params = res;
            $('#forecastPeriodInfo').text('Pemakaian dihitung dari ' + moment(res.period_start).format('DD/MM/YYYY') + ' sampai ' + moment(res.period_end).format('DD/MM/YYYY') + '.');
            $('#forecastFormulaInfo').html('<i class="fas fa-info-circle"></i> ' + esc(res.formula_label || ''));
            renderCounts(res);
            $('#forecastContent').removeClass('d-none');
            buildTable(state.rows);
            // First load: start on "Perlu perhatian" when something needs attention
            if (!state.loadedOnce) {
                state.loadedOnce = true;
                var c = res.counts || {};
                setFilter((Number(c.kritis || 0) + Number(c.pesan || 0) + Number(c.menunggu || 0)) > 0 ? 'perlu' : 'semua');
            } else {
                setFilter(state.filter);
            }
        }).fail(function (xhr) {
            notify('error', 'Gagal', (xhr.responseJSON && xhr.responseJSON.message) || 'Forecast tidak dapat dihitung.');
        }).always(function () {
            $('#forecastLoadingState').addClass('d-none');
            $('#forecastContent').removeClass('d-none');
        });
    }

    $('#fcCards').on('click', '.fc-card', function () { setFilter($(this).data('filter')); });
    $('#fcFavorit').on('change', function () { if (forecastTable) forecastTable.draw(); });
    var searchTimer;
    $('#fcSearch').on('input', function () {
        var v = this.value;
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function () { if (forecastTable) forecastTable.search(v).draw(); }, 250);
    });
    $('#forecast_period_months, #forecast_pengadaan_frequency, #forecast_lead_time_days, #forecast_service_level').on('change', loadForecast);

    // ---------- Explanation of one row ----------
    function explain(row) {
        var st = status(row);
        var daily = Number(row.daily_demand || 0);
        var posisi = Number(row.inventory_position != null ? row.inventory_position : row.total_stock_with_approved_permintaan || 0);
        var steps = [];
        steps.push('<div class="fc-step"><strong>1. Pemakaian</strong><br>Rata-rata <strong>' + num(row.average_monthly_keluar) + '</strong> per bulan'
            + (Number(row.std_monthly_keluar || 0) > 0 ? ' (naik-turun ± ' + num(row.std_monthly_keluar) + ')' : '')
            + ', dari ' + num(row.active_months) + ' bulan yang ada pemakaian. Sekitar <strong>' + num(daily) + '</strong> per hari.</div>');
        steps.push('<div class="fc-step"><strong>2. Waktu tunggu supplier</strong><br><strong>' + num(row.lead_time_days) + ' hari</strong> (' + esc(LEAD_TIME_SOURCE[row.lead_time_source] || '-') + ').</div>');
        steps.push('<div class="fc-step"><strong>3. Titik pesan: ' + num(row.reorder_point) + '</strong><br>Kebutuhan selama menunggu barang datang, termasuk cadangan <strong>' + num(row.safety_stock) + '</strong> untuk hari-hari ramai.'
            + (isTrue(row.wajib_stok) ? ' Obat ini <strong>Wajib Ada</strong>, jadi ada batas minimal walau jarang dipakai.' : '') + '</div>');
        steps.push('<div class="fc-step"><strong>4. Stok maksimal: ' + num(row.max_stock) + '</strong><br>Cukup sampai belanja berikutnya ditambah waktu tunggu.</div>');
        var hit = Number(row.qty_pesan || 0) > 0;
        steps.push('<div class="fc-step' + (hit ? ' fc-step-hit' : '') + '"><strong>5. Posisi stok sekarang: ' + num(posisi) + '</strong><br>'
            + num(row.total_stock) + ' di gudang' + (Number(row.approved_outstanding_permintaan || 0) > 0 ? ' + ' + num(row.approved_outstanding_permintaan) + ' sudah dipesan belum datang' : '') + '.'
            + (row.days_of_cover !== null && row.days_of_cover !== undefined ? ' Cukup untuk sekitar <strong>' + days(row.days_of_cover) + '</strong>.' : '')
            + '<br>' + (hit
                ? 'Sudah di bawah titik pesan → <strong>pesan ' + num(row.qty_pesan) + ' unit</strong>' + (row.jumlah_pesan_box != null ? ' = <strong>' + num(row.jumlah_pesan_box) + ' box</strong> (isi ' + num(row.isi_per_box) + '/box' + (row.isi_per_box_source === 'permintaan' ? ', dari permintaan terakhir' : '') + ')' : ' (isi per box belum diketahui)') + ' supaya kembali ke stok maksimal.'
                    + (row.estimasi_nilai ? ' Perkiraan biaya <strong>' + rupiah(row.estimasi_nilai) + '</strong> (' + rupiah(row.harga_satuan) + '/unit).' : '')
                : isTrue(row.stop_pengadaan)
                    ? 'Obat ini <strong>Stop Pengadaan</strong> → tidak dipesan lagi.'
                    : 'Masih di atas titik pesan → <strong>belum perlu pesan</strong>.') + '</div>');
        var k = kontrolOf(row);
        if (k) {
            var kurang = kontrolKurang(row);
            steps.push('<div class="fc-step' + (kurang > 0 ? ' fc-step-hit' : '') + '" style="border-left-color:' + (kurang > 0 ? '#e74a3b' : '#6f42c1') + '"><strong><i class="fas fa-user-clock"></i> Pasien kontrol (' + esc(state.kontrolPeriod || '') + ')</strong><br>'
                + '<strong>' + num(k.jumlah_kunjungan) + ' pasien</strong> dijadwalkan kontrol, perkiraan butuh <strong>' + num(k.dibutuhkan) + '</strong> (dari resep kunjungan sebelumnya). '
                + (kurang > 0 ? '<span class="text-danger">Stok gudang sekarang kurang <strong>' + num(kurang) + '</strong> untuk mereka.</span>' : 'Stok gudang sekarang cukup.')
                + ' <a href="#" class="btn-kontrol-detail" data-id="' + row.obat_id + '">Lihat pasien</a></div>');
        }

        var warnings = (row.warnings || []).map(function (w) { return '<li>' + esc(w) + '</li>'; }).join('');
        $('#fcDetailTitle').text(row.obat_nama);
        $('#fcDetailBadges').html('<span class="badge ' + st.badge + '">' + st.label + '</span> <small class="text-muted">' + esc(st.text) + '</small>');
        $('#fcDetailBody').html(
            (isTrue(row.stop_pengadaan)
                ? '<div class="alert alert-dark small"><i class="fas fa-ban"></i> <strong>Stop Pengadaan</strong>' + (row.stop_pengadaan_info ? ' sejak ' + esc(row.stop_pengadaan_info) : '') + '. Alasan: ' + esc(row.stop_pengadaan_alasan || '-') + '<br>Tidak ada saran pesan. Angka di bawah hanya untuk memantau sisa stok.</div>'
                : '')
            + (daily <= 0 && !isTrue(row.wajib_stok)
                ? '<div class="alert alert-light border small">Tidak ada pemakaian pada periode histori, jadi tidak ada titik pesan. Tandai <strong>Wajib Ada</strong> bila obat ini harus selalu tersedia.</div>'
                : '')
            + steps.join('')
            + (warnings ? '<div class="alert alert-warning small mb-0"><strong>Perhatian:</strong><ul class="mb-0 pl-3">' + warnings + '</ul></div>' : '')
            + (row.master_faktur_notes ? '<div class="small text-muted mt-2">Catatan Master Pembelian: ' + esc(row.master_faktur_notes) + '</div>' : '')
        );
        $('#fcDetailSimilar').data('id', row.obat_id);
        $('#fcDetailModal').modal('show');
    }

    $('#forecastTable tbody').on('click', 'tr', function (e) {
        if ($(e.target).closest('a, button').length) return;
        var row = forecastTable.row(this).data();
        if (row) explain(row);
    });

    // ---------- Row actions ----------
    function updateRow(obatId, changes) {
        forecastTable.rows().every(function () {
            var d = this.data();
            if (String(d.obat_id) === String(obatId)) { $.extend(d, changes); this.data(d); }
        });
        forecastTable.draw(false);
    }

    $(document).on('click', '.btn-obat-favorite', function (e) {
        e.preventDefault();
        var id = $(this).data('id');
        $.ajax({ url: URLS.obat + '/' + id + '/toggle-favorite', type: 'PATCH', data: { _token: CSRF } })
            .done(function (res) { updateRow(id, { is_favorite: !!res.is_favorite }); })
            .fail(function (xhr) { notify('error', 'Gagal', (xhr.responseJSON && xhr.responseJSON.message) || 'Favorit tidak dapat diubah.'); });
    });

    $(document).on('click', '.btn-toggle-wajib-stok', function (e) {
        e.preventDefault();
        var id = $(this).data('id');
        var aktif = String($(this).data('wajib')) === '1';
        Swal.fire({
            icon: 'question',
            title: aktif ? 'Matikan Wajib Ada?' : 'Tandai Wajib Ada?',
            text: aktif ? 'Forecast kembali murni mengikuti pemakaian.' : 'Stok obat ini akan selalu dijaga minimal (mis. obat emergency), walau jarang dipakai.',
            showCancelButton: true,
            confirmButtonText: 'Ya',
            cancelButtonText: 'Batal',
            reverseButtons: true
        }).then(function (r) {
            if (!r.value) return;
            $.ajax({ url: URLS.obat + '/' + id + '/toggle-wajib-stok', type: 'PATCH', data: { _token: CSRF } })
                .done(function () { loadForecast(); })
                .fail(function (xhr) { notify('error', 'Gagal', (xhr.responseJSON && xhr.responseJSON.message) || 'Status Wajib Ada tidak dapat diubah.'); });
        });
    });

    $(document).on('click', '.btn-stop-pengadaan', function (e) {
        e.preventDefault();
        var id = $(this).data('id');
        var aktif = String($(this).data('stop')) === '1';
        var nama = $(this).data('nama');
        var options = aktif
            ? {
                icon: 'question',
                title: 'Batalkan Stop Pengadaan?',
                text: nama + ' akan kembali dihitung forecast dan bisa muncul di saran pesan.',
                confirmButtonText: 'Ya, batalkan'
            }
            : {
                icon: 'warning',
                title: 'Stop Pengadaan ' + nama + '?',
                html: 'Obat ini <strong>tidak akan disarankan untuk dipesan lagi</strong>. Sisa stok tetap bisa dijual sampai habis.'
                    + (CAN_EDIT ? '<div class="small text-muted mt-1">Tanda Wajib Ada akan dilepas.</div>' : ''),
                input: 'textarea',
                inputPlaceholder: 'Alasan, mis. diganti obat lain / discontinue dari principal / tidak laku...',
                inputAttributes: { maxlength: 500 },
                inputValidator: function (v) { if (!v || !v.trim()) return 'Alasan wajib diisi.'; },
                confirmButtonText: 'Stop Pengadaan',
                confirmButtonColor: '#343a40'
            };
        Swal.fire($.extend({ showCancelButton: true, cancelButtonText: 'Batal', reverseButtons: true }, options)).then(function (r) {
            if (!r.value) return;
            $.ajax({
                url: URLS.stopPengadaan.replace('__ID__', id),
                type: 'PATCH',
                data: { _token: CSRF, aktif: aktif ? 0 : 1, alasan: aktif ? '' : String(r.value).trim() },
                headers: { Accept: 'application/json' }
            }).done(function (res) {
                Swal.fire({ icon: 'success', title: 'Tersimpan', text: res.message, timer: 1600, showConfirmButton: false });
                loadForecast();
            }).fail(function (xhr) {
                var json = xhr.responseJSON || {};
                notify('error', 'Gagal', json.errors ? Object.values(json.errors)[0][0] : (json.message || 'Status Stop Pengadaan tidak dapat diubah.'));
            });
        });
    });

    // ---------- Similar obat ----------
    function priceCompare(value, base) {
        if (value === null || value === undefined) return '-';
        var cls = '', icon = '';
        if (base !== null && base !== undefined) {
            if (Number(value) < Number(base)) { cls = 'text-success'; icon = ' <i class="fas fa-arrow-down"></i>'; }
            else if (Number(value) > Number(base)) { cls = 'text-danger'; icon = ' <i class="fas fa-arrow-up"></i>'; }
        }
        return '<span class="' + cls + '">' + rupiah(value) + icon + '</span>';
    }

    $(document).on('click', '.btn-forecast-similar', function (e) {
        e.preventDefault();
        var id = $(this).data('id');
        $('#fcDetailModal').modal('hide');
        $.get(URLS.obat + '/' + id + '/similar').done(function (res) {
            $('#similarObatName').text(res.obat_nama || '');
            $('#similarObatSharedZatAktif').html((res.shared_zat_aktif || []).map(function (z) { return '<span class="badge badge-soft mr-1">' + esc(z) + '</span>'; }).join(''));
            $('#similarObatSelectedPrices').html('Obat ini: stok <strong>' + num(res.total_stock) + '</strong> · isi/box <strong>' + (res.isi_per_box ? num(res.isi_per_box) : '-') + '</strong> · harga beli <strong>' + rupiah(res.harga_beli) + '</strong> · harga jual <strong>' + rupiah(res.harga_jual) + '</strong>');
            $('#similarObatMessage').toggleClass('d-none', !res.message).text(res.message || '');
            var rows = (res.rows || []).map(function (r) {
                return '<tr><td><div class="fc-name">' + esc(r.obat_nama) + '</div><div class="fc-meta"><span class="badge ' + (isTrue(r.is_generik) ? 'badge-success' : 'badge-light border') + '">' + (isTrue(r.is_generik) ? 'Generik' : 'Paten') + '</span>'
                    + (r.principal_names || []).map(function (p) { return '<span class="badge badge-soft">' + esc(p) + '</span>'; }).join('') + '</div></td>'
                    + '<td class="text-right">' + num(r.total_stock) + '</td>'
                    + '<td class="text-right">' + (r.isi_per_box ? num(r.isi_per_box) : '-') + '</td>'
                    + '<td class="text-right text-nowrap">' + priceCompare(r.harga_beli, res.harga_beli) + '</td>'
                    + '<td class="text-right text-nowrap">' + priceCompare(r.harga_jual, res.harga_jual) + '</td>'
                    + '<td class="small">' + esc((r.matched_zat_aktif || []).join(', ')) + '</td></tr>';
            }).join('');
            $('#similarObatTable tbody').html(rows || '<tr><td colspan="6" class="text-center text-muted">Tidak ada obat lain dengan zat aktif yang sama.</td></tr>');
            $('#similarObatModal').modal('show');
        }).fail(function () { notify('error', 'Gagal', 'Obat serupa tidak dapat dimuat.'); });
    });

    // ---------- Excel (rows shown, in the order shown) ----------
    $('#downloadForecastTable').on('click', function () {
        if (!forecastTable) return;
        var rows = forecastTable.rows({ search: 'applied', order: 'applied' }).data().toArray().map(function (r) {
            // Order must match the headings in ObatController::exportForecastDisplayed
            return [
                r.obat_nama || '', Number(r.total_stock || 0), Number(r.approved_outstanding_permintaan || 0), Number(r.total_stock_with_approved_permintaan || 0),
                Number(r.obat_keluar || 0), Number(r.average_monthly_keluar || 0), Number(r.std_monthly_keluar || 0), Number(r.safety_stock || 0),
                Number(r.reorder_point || 0), Number(r.max_stock || 0), Number(r.qty_pesan || 0),
                r.isi_per_box == null ? '' : Number(r.isi_per_box), r.jumlah_pesan_box == null ? '' : Number(r.jumlah_pesan_box),
                r.days_of_cover == null ? '' : Number(r.days_of_cover), status(r).label, Number(r.lead_time_days || 0),
                LEAD_TIME_SOURCE[r.lead_time_source] || '', (r.warnings || []).join('; '),
                kontrolOf(r) ? Number(kontrolOf(r).dibutuhkan || 0) : '', kontrolOf(r) ? Number(kontrolOf(r).jumlah_kunjungan || 0) : ''
            ];
        });
        if (!rows.length) { notify('info', 'Tidak ada data', 'Tidak ada obat di tampilan ini untuk diunduh.'); return; }
        fetch(URLS.export, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ rows: rows })
        }).then(function (response) {
            if (!response.ok) throw new Error('Gagal mengunduh file Excel forecast.');
            var m = (response.headers.get('Content-Disposition') || '').match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/i);
            var filename = m ? m[1].replace(/['"]/g, '') : 'forecast_pembelian.xlsx';
            return response.blob().then(function (blob) { return { blob: blob, filename: filename }; });
        }).then(function (file) {
            var url = window.URL.createObjectURL(file.blob);
            var link = document.createElement('a');
            link.href = url; link.download = file.filename;
            document.body.appendChild(link); link.click(); document.body.removeChild(link);
            window.URL.revokeObjectURL(url);
        }).catch(function (err) { notify('error', 'Gagal', err.message); });
    });

    // ---------- Kebutuhan pasien kontrol (column in the forecast table) ----------
    function loadKeluar() {
        $('#forecastKeluarLoadingState').removeClass('d-none');
        $.get(URLS.keluar, { period: $('#forecast_keluar_period').val() }).done(function (res) {
            state.kontrol = {};
            (res.rows || []).forEach(function (r) { state.kontrol[r.obat_id] = r; });
            state.kontrolPeriod = moment(res.period_start).format('DD/MM') + ' – ' + moment(res.period_end).format('DD/MM/YYYY');
            $('#forecastKeluarPeriodInfo').html('· Pasien kontrol: kunjungan ' + esc(state.kontrolPeriod) + '.');
            renderKontrolCounts();
            if (forecastTable) forecastTable.rows().invalidate('data').draw(false);
        }).fail(function () {
            notify('error', 'Gagal', 'Kebutuhan pasien kontrol tidak dapat dimuat.');
        }).always(function () { $('#forecastKeluarLoadingState').addClass('d-none'); });
    }
    $('#forecast_keluar_period').on('change', loadKeluar);

    $(document).on('click', '.btn-kontrol-detail', function (e) {
        e.preventDefault();
        var id = $(this).data('id');
        $('#fcDetailModal').modal('hide');
        $.get(URLS.obat + '/forecast-keluar/' + id + '/detail', { period: $('#forecast_keluar_period').val() }).done(function (res) {
            $('#forecastKeluarDetailObatName').text(res.obat_nama);
            $('#forecastKeluarDetailPeriodInfo').text('Pasien yang dijadwalkan kontrol ' + moment(res.period_start).format('DD/MM/YYYY') + ' – ' + moment(res.period_end).format('DD/MM/YYYY') + ', dengan jumlah dari resep sebelumnya');
            var html = (res.rows || []).map(function (r) {
                return '<tr><td>' + esc(r.pasien_nama) + '</td><td>' + (r.target_tanggal_visitation ? moment(r.target_tanggal_visitation).format('DD/MM/YYYY') : '-') + '</td>'
                    + '<td>' + (r.tanggal_visitation ? moment(r.tanggal_visitation).format('DD/MM/YYYY') : '-') + '</td><td class="text-right">' + num(r.jumlah) + '</td></tr>';
            }).join('');
            $('#forecastKeluarDetailTable tbody').html(html || '<tr><td colspan="4" class="text-center text-muted">Tidak ada data.</td></tr>');
            $('#forecastKeluarDetailModal').modal('show');
        }).fail(function () { notify('error', 'Gagal', 'Detail tidak dapat dimuat.'); });
    });

    loadForecast();
    loadKeluar();
});
</script>
@endsection
