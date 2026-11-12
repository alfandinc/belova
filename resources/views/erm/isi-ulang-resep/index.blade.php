@extends('layouts.erm.app')
@section('title', 'Farmasi | Saran Isi Ulang Apotek')
@section('navbar')
    @include('layouts.erm.navbar-farmasi')
@endsection
@section('content')
<div class="container-fluid">
    <div class="d-flex flex-wrap align-items-center justify-content-between mt-3 mb-3">
        <div>
            <h4 class="page-title mb-0">Saran Isi Ulang Apotek</h4>
            <small class="text-muted">
                Obat yang akan kurang di <strong>{{ $gudangResep ?? 'gudang resep' }}</strong> untuk pasien yang sudah terdaftar.
                Dihitung otomatis dari resep dan riwayat pasien, tidak perlu mengisi stok minimum per obat.
            </small>
        </div>
        <a href="{{ url('/erm/mutasi-gudang') }}" class="btn btn-outline-secondary mt-2 mt-md-0"><i class="fas fa-truck mr-1"></i>Transfer Antar Gudang</a>
    </div>

    @if (!$gudangResep)
        <div class="alert alert-warning">Belum ada gudang yang dimapping ke <strong>Resep Farmasi</strong> (Gudang Mapping). Saran tidak bisa dihitung.</div>
    @else
    <div class="card">
        <div class="card-body">
            <div class="form-row align-items-end mb-2">
                <div class="form-group col-md-3 col-6">
                    <label for="f-hari" class="mb-1">Kunjungan terdaftar</label>
                    <select id="f-hari" class="form-control form-control-sm">
                        @foreach ($hariKunjungan as $h)
                            <option value="{{ $h }}" {{ $h === 7 ? 'selected' : '' }}>{{ $h === 1 ? 'Hari ini saja' : 'Hari ini s.d. ' . $h . ' hari ke depan' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-3 col-6">
                    <label for="f-asal" class="mb-1">Ambil dari gudang</label>
                    <select id="f-asal" class="form-control form-control-sm">
                        <option value="">Otomatis (paling lengkap)</option>
                        @foreach ($gudangLain as $g)
                            <option value="{{ $g->id }}">{{ $g->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-4 col-12">
                    <label for="f-cari" class="mb-1">Cari obat</label>
                    <input type="search" id="f-cari" class="form-control form-control-sm" placeholder="Nama / kode obat">
                </div>
                <div class="form-group col-md-2 col-12">
                    <button type="button" class="btn btn-sm btn-outline-secondary btn-block" id="btn-muat-ulang"><i class="fas fa-sync-alt mr-1"></i>Hitung ulang</button>
                </div>
            </div>

            <div class="iu-rumus text-muted mb-3">
                <strong>Pasti</strong> = resep yang sudah dibuat (farmasi, atau dokter bila belum disalin) untuk kunjungan yang belum dibayar.
                <strong>Prediksi</strong> = pasien terdaftar yang belum diperiksa: rata-rata obat pada {{ $riwayatKunjungan }} kunjungan terakhirnya yang ada resep di klinik yang sama.
                Obat muncul bila kebutuhan &gt; stok apotek + transfer yang masih menunggu approval. Pasien tanpa riwayat resep tidak diprediksi.
            </div>

            <div class="d-flex flex-wrap align-items-center justify-content-between mb-2">
                <div id="iu-ringkasan" class="mb-2 mb-md-0"></div>
                @if ($canRequest)
                    <button type="button" class="btn btn-primary" id="btn-buat-transfer" disabled>
                        <i class="fas fa-truck mr-1"></i>Buat Transfer <span id="iu-terpilih">(0 obat)</span>
                    </button>
                @endif
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover table-sm mb-0" id="iu-table">
                    <thead class="thead-light">
                        <tr>
                            <th class="text-center" style="width:36px;"><input type="checkbox" id="iu-semua" title="Pilih semua yang bisa ditransfer"></th>
                            <th>Obat</th>
                            <th class="text-right">Pasti</th>
                            <th class="text-right">Prediksi</th>
                            <th class="text-right">Kebutuhan</th>
                            <th class="text-right">Stok apotek</th>
                            <th class="text-right">Kurang</th>
                            <th class="text-right">Stok gudang asal</th>
                            <th class="text-right" style="width:120px;">Transfer</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="iu-body">
                        <tr><td colspan="10" class="text-center text-muted py-4"><i class="fas fa-spinner fa-spin mr-1"></i>Menghitung...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
$(function () {
    if (!$('#iu-table').length) return;

    var CSRF = "{{ csrf_token() }}";
    var DATA_URL = "{{ route('erm.isi-ulang-resep.data') }}";
    var STORE_URL = "{{ route('erm.mutasi-gudang.store') }}";
    var CAN_REQUEST = @json($canRequest);
    var COLS = 10;
    var state = { rows: [], gudangResep: null, gudangAsal: null, ringkasan: {}, periode: {} };
    var request = null;

    var STATUS = {
        transfer: ['Bisa ditransfer', 'badge-success'],
        sebagian: ['Transfer sebagian', 'badge-warning'],
        gudang_lain: ['Ada di gudang lain', 'badge-info'],
        pengadaan: ['Kosong di semua gudang', 'badge-danger']
    };

    function esc(value) {
        return String(value === undefined || value === null ? '' : value).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function fmt(n) {
        return Number(n || 0).toLocaleString('id-ID', { maximumFractionDigits: 2 });
    }

    function message(text, cls) {
        $('#iu-body').html('<tr><td colspan="' + COLS + '" class="text-center py-4 ' + (cls || 'text-muted') + '">' + text + '</td></tr>');
    }

    function load() {
        if (request) request.abort();   // a newer filter wins
        message('<i class="fas fa-spinner fa-spin mr-1"></i>Menghitung...');
        request = $.get(DATA_URL, { gudang_asal_id: $('#f-asal').val(), hari_kunjungan: $('#f-hari').val() })
            .done(function (res) {
                state.rows = res.rows || [];
                state.gudangResep = res.gudang_resep;
                state.gudangAsal = res.gudang_asal;
                state.ringkasan = res.ringkasan || {};
                state.periode = res.periode || {};
                render();
            })
            .fail(function (xhr, status) {
                if (status !== 'abort') message('Gagal memuat saran. Muat ulang halaman.', 'text-danger');
            })
            .always(function () { request = null; });
    }

    function render() {
        var r = state.ringkasan;
        var asal = state.gudangAsal ? state.gudangAsal.nama : '-';
        var periode = state.periode.mulai === state.periode.sampai ? state.periode.mulai : (state.periode.mulai + ' - ' + state.periode.sampai);
        $('#iu-ringkasan').html(
            '<span class="badge badge-secondary mr-1" title="Kunjungan terdaftar yang belum dibayar dan tidak dibatalkan">' + esc(periode) + ': ' + (r.kunjungan || 0) + ' kunjungan</span>'
            + '<span class="badge badge-primary mr-1">' + (r.dengan_resep || 0) + ' sudah ada resep</span>'
            + '<span class="badge badge-info mr-1">' + (r.diprediksi || 0) + ' diprediksi dari riwayat</span>'
            + (r.tanpa_riwayat ? '<span class="badge badge-light mr-1" title="Belum pernah mendapat resep di klinik itu dalam setahun terakhir">' + r.tanpa_riwayat + ' tanpa riwayat</span>' : '')
            + '<span class="badge badge-warning">' + state.rows.length + ' obat kurang</span>'
            + ' <small class="text-muted ml-1">Gudang asal: <strong>' + esc(asal) + '</strong></small>'
        );

        var cari = $.trim($('#f-cari').val()).toLowerCase();
        var rows = state.rows.filter(function (row) {
            return !cari || (row.nama || '').toLowerCase().indexOf(cari) !== -1 || (row.kode || '').toLowerCase().indexOf(cari) !== -1;
        });

        if (!rows.length) {
            message(state.rows.length ? 'Tidak ada obat yang cocok dengan pencarian.' : 'Stok apotek cukup untuk semua pasien yang terdaftar pada periode ini.');
            updateTerpilih();
            return;
        }

        $('#iu-body').html(rows.map(function (row) {
            var st = STATUS[row.status] || STATUS.pengadaan;
            var lain = (row.gudang_lain || []).map(function (g) { return g.nama + ' ' + fmt(g.stok); }).join(', ');
            var bisaPilih = CAN_REQUEST && row.saran > 0;
            return '<tr data-obat-id="' + row.obat_id + '">'
                + '<td class="text-center">' + (bisaPilih ? '<input type="checkbox" class="iu-pilih" checked>' : '') + '</td>'
                + '<td><div class="font-weight-bold">' + esc(row.nama) + '</div>'
                +   '<small class="text-muted">' + esc([row.kode, row.satuan].filter(Boolean).join(' · ')) + ' · ' + row.kunjungan + ' kunjungan</small></td>'
                + '<td class="text-right">' + (row.pasti > 0 ? fmt(row.pasti) : '-') + '</td>'
                + '<td class="text-right">' + (row.prediksi > 0 ? fmt(row.prediksi) : '-') + '</td>'
                + '<td class="text-right font-weight-bold">' + fmt(row.kebutuhan) + '</td>'
                + '<td class="text-right ' + (row.stok_resep <= 0 ? 'text-danger font-weight-bold' : '') + '">' + fmt(row.stok_resep)
                +   (row.dalam_transfer > 0 ? '<div><small class="text-info" title="Transfer menunggu approval">+' + fmt(row.dalam_transfer) + ' dalam transfer</small></div>' : '') + '</td>'
                + '<td class="text-right text-danger font-weight-bold">' + fmt(row.kurang) + '</td>'
                + '<td class="text-right" title="' + esc(lain ? 'Semua gudang lain: ' + lain : 'Tidak ada di gudang lain') + '">' + fmt(row.stok_asal)
                +   (row.stok_lain > row.stok_asal ? '<div><small class="text-muted">total gudang lain ' + fmt(row.stok_lain) + '</small></div>' : '') + '</td>'
                + '<td class="text-right">' + (bisaPilih
                        ? '<input type="number" class="form-control form-control-sm text-right iu-jumlah" min="1" max="' + row.stok_asal + '" step="1" value="' + row.saran + '">'
                        : (row.saran > 0 ? fmt(row.saran) : '-')) + '</td>'
                + '<td><span class="badge ' + st[1] + '">' + st[0] + '</span>'
                +   (row.status === 'gudang_lain' ? '<div><small class="text-muted">' + esc(lain) + '</small></div>' : '') + '</td>'
                + '</tr>';
        }).join(''));
        updateTerpilih();
    }

    function terpilih() {
        var items = [];
        $('#iu-body tr').each(function () {
            var $tr = $(this);
            if (!$tr.find('.iu-pilih').is(':checked')) return;
            var jumlah = parseFloat($tr.find('.iu-jumlah').val());
            if (jumlah > 0) items.push({ obat_id: $tr.data('obat-id'), jumlah: jumlah, keterangan: 'Saran isi ulang apotek' });
        });
        return items;
    }

    function updateTerpilih() {
        var n = terpilih().length;
        $('#iu-terpilih').text('(' + n + ' obat)');
        $('#btn-buat-transfer').prop('disabled', n === 0);
        var $boxes = $('#iu-body .iu-pilih');
        $('#iu-semua').prop('checked', $boxes.length > 0 && $boxes.filter(':checked').length === $boxes.length);
    }

    $('#f-asal, #f-hari').on('change', load);
    $('#btn-muat-ulang').on('click', load);
    $('#f-cari').on('input', render);
    $('#iu-semua').on('change', function () {
        $('#iu-body .iu-pilih').prop('checked', this.checked);
        updateTerpilih();
    });
    $('#iu-body').on('change input', '.iu-pilih, .iu-jumlah', updateTerpilih);

    $('#btn-buat-transfer').on('click', function () {
        // The search box hides rows: only what is on screen is sent, so say how many
        var items = terpilih();
        if (!items.length || !state.gudangAsal || !state.gudangResep) return;
        var $btn = $(this);
        Swal.fire({
            icon: 'question',
            title: 'Buat transfer?',
            html: '<strong>' + esc(state.gudangAsal.nama) + '</strong> &rarr; <strong>' + esc(state.gudangResep.nama) + '</strong><br>'
                + items.length + ' obat. Stok baru berpindah setelah transfer disetujui.',
            showCancelButton: true,
            confirmButtonText: 'Ya, buat transfer',
            cancelButtonText: 'Batal'
        }).then(function (result) {
            if (!result.value) return;
            $btn.prop('disabled', true);
            $.ajax({
                url: STORE_URL,
                type: 'POST',
                data: { _token: CSRF, gudang_asal_id: state.gudangAsal.id, gudang_tujuan_id: state.gudangResep.id, keterangan: 'Saran isi ulang apotek', items: items }
            }).done(function (res) {
                Swal.fire({
                    icon: 'success',
                    title: 'Transfer diajukan',
                    html: esc(res.message) + '<div class="mt-3"><a href="{{ url('/erm/mutasi-gudang') }}" class="btn btn-sm btn-primary">Buka Transfer Antar Gudang</a></div>'
                });
                load();
            }).fail(function (xhr) {
                var json = xhr.responseJSON || {};
                var msg = json.errors ? Object.values(json.errors).flat().join('\n') : (json.message || 'Transfer gagal dibuat.');
                Swal.fire({ icon: 'error', title: 'Gagal', text: msg });
                $btn.prop('disabled', false);
            });
        });
    });

    load();
});
</script>
@endpush

@push('styles')
<style>
#iu-table td { vertical-align: middle; }
#iu-table .iu-jumlah { min-width: 84px; }
.iu-rumus { font-size: .8rem; line-height: 1.5; }
</style>
@endpush
