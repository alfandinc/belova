@extends('layouts.erm.app')
@section('title', 'Farmasi | Saran Isi Ulang Apotek')
@section('navbar')
    @include('layouts.erm.navbar-farmasi')
@endsection
@section('content')
<div class="container-fluid iu-page">
    <div class="d-flex flex-wrap align-items-center justify-content-between mt-3 mb-3">
        <div>
            <h4 class="page-title mb-0">Saran Isi Ulang Apotek</h4>
            <small class="text-muted">Obat yang perlu dimutasi ke <strong>{{ $gudangResep ?? 'apotek' }}</strong> supaya cukup untuk pasien yang sudah terdaftar.</small>
        </div>
        <a href="{{ url('/erm/mutasi-gudang') }}" class="btn btn-outline-secondary mt-2 mt-md-0"><i class="fas fa-truck mr-1"></i>Transfer Antar Gudang</a>
    </div>

    @if (!$gudangResep)
        <div class="alert alert-warning">Belum ada gudang yang dimapping ke <strong>Resep Farmasi</strong> (Gudang Mapping). Saran tidak bisa dihitung.</div>
    @else
    <div class="card">
        <div class="card-body">
            {{-- Filters --}}
            <div class="form-row align-items-end">
                <div class="form-group col-md-3 col-6">
                    <label for="f-hari" class="mb-1">Pasien terdaftar</label>
                    <select id="f-hari" class="form-control form-control-sm">
                        @foreach ($hariKunjungan as $h)
                            <option value="{{ $h }}" {{ $h === 7 ? 'selected' : '' }}>{{ $h === 1 ? 'Hari ini' : $h . ' hari ke depan' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-3 col-6">
                    <label for="f-asal" class="mb-1">Mutasi dari</label>
                    <select id="f-asal" class="form-control form-control-sm">
                        <option value="">Otomatis (gudang paling lengkap)</option>
                        @foreach ($gudangLain as $g)
                            <option value="{{ $g->id }}">{{ $g->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-4 col-9">
                    <label for="f-cari" class="mb-1">Cari obat</label>
                    <input type="search" id="f-cari" class="form-control form-control-sm" placeholder="Nama / kode obat">
                </div>
                <div class="form-group col-md-2 col-3">
                    <button type="button" class="btn btn-sm btn-outline-secondary btn-block" id="btn-muat-ulang" title="Hitung ulang"><i class="fas fa-sync-alt"></i><span class="d-none d-md-inline ml-1">Hitung ulang</span></button>
                </div>
            </div>

            {{-- Summary --}}
            <div class="iu-summary" id="iu-summary"></div>

            <details class="iu-howto mb-3">
                <summary>Cara menghitung</summary>
                <ul class="mb-0">
                    <li><strong>Akan dipakai</strong> = obat yang diperkirakan keluar untuk pasien terdaftar pada periode ini.
                        Pasien yang sudah punya resep dihitung dari resepnya. Pasien yang belum diperiksa dihitung dari rata-rata obat pada {{ $riwayatKunjungan }} kunjungan terakhirnya yang ada resep, di klinik yang sama.</li>
                    <li>Pasien baru (belum pernah dapat resep di klinik itu dalam setahun) belum bisa diperkirakan, jadi tidak dihitung.</li>
                    <li><strong>Kurang</strong> = akan dipakai − stok apotek − transfer yang masih menunggu approval. Angka dibulatkan ke atas.</li>
                    <li>Halaman ini hanya membuat mutasi antar gudang (Transfer Antar Gudang), tetap harus disetujui di sana. Obat yang stoknya habis di semua gudang hanya ditampilkan sebagai informasi.</li>
                </ul>
            </details>

            {{-- Status tabs + action --}}
            <div class="d-flex flex-wrap align-items-center justify-content-between mb-2">
                <ul class="nav nav-pills iu-tabs mb-2 mb-md-0" id="iu-tabs"></ul>
                @if ($canRequest)
                    <button type="button" class="btn btn-primary" id="btn-buat-transfer" disabled>
                        <i class="fas fa-truck mr-1"></i>Buat Mutasi ke Apotek <span id="iu-terpilih"></span>
                    </button>
                @endif
            </div>

            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0" id="iu-table">
                    <thead class="thead-light">
                        <tr>
                            <th class="text-center" style="width:36px;"><input type="checkbox" id="iu-semua" title="Pilih semua yang bisa dimutasi"></th>
                            <th>Obat</th>
                            <th class="text-right">Akan dipakai</th>
                            <th class="text-right">Stok apotek</th>
                            <th class="text-right">Kurang</th>
                            <th class="text-right" style="width:150px;">Jumlah mutasi</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody id="iu-body">
                        <tr><td colspan="7" class="text-center text-muted py-4"><i class="fas fa-spinner fa-spin mr-1"></i>Menghitung...</td></tr>
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
    var COLS = 7;
    var state = { rows: [], gudangResep: null, gudangAsal: null, ringkasan: {}, periode: {}, tab: 'semua' };
    var request = null;

    // One plain-language label per status; "tab" groups them for the filter
    var STATUS = {
        transfer:    { label: 'Bisa dimutasi', cls: 'badge-success', tab: 'kirim' },
        sebagian:    { label: 'Mutasi sebagian', cls: 'badge-warning', tab: 'kirim' },
        gudang_lain: { label: 'Ada di gudang lain', cls: 'badge-info', tab: 'lain' },
        pengadaan:   { label: 'Stok habis', cls: 'badge-secondary', tab: 'pesan' }
    };

    function esc(value) {
        return String(value === undefined || value === null ? '' : value).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }
    function fmt(n) { return Number(n || 0).toLocaleString('id-ID', { maximumFractionDigits: 1 }); }
    function bulat(n) { return Math.ceil(Number(n || 0) - 0.0001).toLocaleString('id-ID'); }

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
                renderSummary();
                render();
            })
            .fail(function (xhr, status) {
                if (status !== 'abort') message('Gagal memuat saran. Muat ulang halaman.', 'text-danger');
            })
            .always(function () { request = null; });
    }

    function count(tab) {
        return state.rows.filter(function (r) { return tab === 'semua' || (STATUS[r.status] || STATUS.pengadaan).tab === tab; }).length;
    }

    function renderSummary() {
        var r = state.ringkasan;
        var asal = state.gudangAsal ? state.gudangAsal.nama : 'gudang lain';
        var periode = state.periode.mulai === state.periode.sampai ? state.periode.mulai : (state.periode.mulai + ' – ' + state.periode.sampai);
        var dihitung = (r.dengan_resep || 0) + (r.diprediksi || 0);
        var card = function (cls, icon, angka, judul, sub) {
            return '<div class="iu-card ' + cls + '"><i class="fas ' + icon + '"></i><div>'
                + '<div class="iu-card-num">' + angka + '</div><div class="iu-card-title">' + judul + '</div>'
                + (sub ? '<div class="iu-card-sub">' + sub + '</div>' : '') + '</div></div>';
        };
        $('#iu-summary').html(
            card('is-neutral', 'fa-user-friends', (r.kunjungan || 0), 'pasien terdaftar', esc(periode) + ' · ' + dihitung + ' dihitung'
                + (r.tanpa_riwayat ? ', ' + r.tanpa_riwayat + ' pasien baru belum bisa diperkirakan' : ''))
            + card('is-warn', 'fa-exclamation-circle', state.rows.length, 'obat akan kurang', 'stok apotek tidak cukup')
            + card('is-ok', 'fa-truck', count('kirim'), 'bisa dimutasi', 'dari ' + esc(asal))
            + card('is-bad', 'fa-ban', count('pesan'), 'tidak bisa dimutasi', 'stok habis di semua gudang')
        );

        var tabs = [['semua', 'Semua'], ['kirim', 'Bisa dimutasi'], ['lain', 'Ada di gudang lain'], ['pesan', 'Stok habis']];
        if (!tabs.some(function (t) { return t[0] === state.tab && (t[0] === 'semua' || count(t[0]) > 0); })) state.tab = 'semua';
        $('#iu-tabs').html(tabs.filter(function (t) { return t[0] === 'semua' || count(t[0]) > 0; }).map(function (t) {
            return '<li class="nav-item"><a class="nav-link' + (state.tab === t[0] ? ' active' : '') + '" href="#" data-tab="' + t[0] + '">'
                + t[1] + ' <span class="badge badge-light ml-1">' + count(t[0]) + '</span></a></li>';
        }).join(''));
    }

    function keterangan(row) {
        var asal = state.gudangAsal ? state.gudangAsal.nama : 'gudang asal';
        var lain = (row.gudang_lain || []).map(function (g) { return esc(g.nama) + ' ' + fmt(g.stok); }).join(', ');
        if (row.status === 'transfer') return 'Stok ' + esc(asal) + ' ' + fmt(row.stok_asal);
        if (row.status === 'sebagian') return esc(asal) + ' hanya punya ' + fmt(row.stok_asal) + ', masih kurang ' + bulat(row.kurang - row.saran);
        if (row.status === 'gudang_lain') return 'Tidak ada di ' + esc(asal) + '. Ganti "Mutasi dari" ke: ' + lain;
        return 'Tidak ada stok di gudang mana pun untuk dimutasi';
    }

    function render() {
        var cari = $.trim($('#f-cari').val()).toLowerCase();
        var rows = state.rows.filter(function (row) {
            var st = STATUS[row.status] || STATUS.pengadaan;
            return (state.tab === 'semua' || st.tab === state.tab)
                && (!cari || (row.nama || '').toLowerCase().indexOf(cari) !== -1 || (row.kode || '').toLowerCase().indexOf(cari) !== -1);
        });

        if (!rows.length) {
            message(state.rows.length ? 'Tidak ada obat yang cocok.' : '<i class="fas fa-check-circle text-success mr-1"></i>Stok apotek cukup untuk semua pasien terdaftar pada periode ini.');
            updateTerpilih();
            return;
        }

        $('#iu-body').html(rows.map(function (row) {
            var st = STATUS[row.status] || STATUS.pengadaan;
            var bisaPilih = CAN_REQUEST && row.saran > 0;
            var sumber = [];
            if (row.pasti > 0) sumber.push(bulat(row.pasti) + ' dari resep yang sudah ada');
            if (row.prediksi > 0) sumber.push(bulat(row.prediksi) + ' perkiraan dari riwayat pasien');
            return '<tr data-obat-id="' + row.obat_id + '">'
                + '<td class="text-center">' + (bisaPilih ? '<input type="checkbox" class="iu-pilih" checked>' : '') + '</td>'
                + '<td><div class="font-weight-bold">' + esc(row.nama) + '</div>'
                +   '<small class="text-muted">untuk ' + row.kunjungan + ' pasien' + (row.kode ? ' · ' + esc(row.kode) : '') + '</small></td>'
                + '<td class="text-right" title="' + esc(sumber.join('\n')) + '">' + bulat(row.kebutuhan) + ' <small class="text-muted">' + esc(row.satuan || '') + '</small>'
                +   (row.pasti > 0 ? '<div><small class="text-primary">' + bulat(row.pasti) + ' sudah diresepkan</small></div>' : '') + '</td>'
                + '<td class="text-right">' + fmt(row.stok_resep)
                +   (row.dalam_transfer > 0 ? '<div><small class="text-info">+' + fmt(row.dalam_transfer) + ' mutasi menunggu approval</small></div>' : '') + '</td>'
                + '<td class="text-right iu-kurang">' + bulat(row.kurang) + '</td>'
                + '<td class="text-right">' + (bisaPilih
                        ? '<input type="number" class="form-control form-control-sm text-right iu-jumlah" min="1" max="' + row.stok_asal + '" step="1" value="' + row.saran + '">'
                        : '<span class="text-muted">-</span>') + '</td>'
                + '<td><span class="badge ' + st.cls + '">' + st.label + '</span><div><small class="text-muted">' + keterangan(row) + '</small></div></td>'
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
        $('#iu-terpilih').text(n ? '(' + n + ' obat)' : '');
        $('#btn-buat-transfer').prop('disabled', n === 0);
        var $boxes = $('#iu-body .iu-pilih');
        $('#iu-semua').prop('checked', $boxes.length > 0 && $boxes.filter(':checked').length === $boxes.length);
    }

    $('#f-asal, #f-hari').on('change', load);
    $('#btn-muat-ulang').on('click', load);
    $('#f-cari').on('input', render);
    $('#iu-tabs').on('click', 'a[data-tab]', function (e) {
        e.preventDefault();
        state.tab = $(this).data('tab');
        $('#iu-tabs .nav-link').removeClass('active');
        $(this).addClass('active');
        render();
    });
    $('#iu-semua').on('change', function () {
        $('#iu-body .iu-pilih').prop('checked', this.checked);
        updateTerpilih();
    });
    $('#iu-body').on('change input', '.iu-pilih, .iu-jumlah', updateTerpilih);

    $('#btn-buat-transfer').on('click', function () {
        // Only the rows on screen (tab + search) are sent, so the confirmation says how many
        var items = terpilih();
        if (!items.length || !state.gudangAsal || !state.gudangResep) return;
        var $btn = $(this);

        // Preview: every line that will go into the transfer, with the stok before / after on both sides
        var byId = {};
        state.rows.forEach(function (r) { byId[r.obat_id] = r; });
        var lebih = [];
        var baris = items.map(function (it, i) {
            var r = byId[it.obat_id] || {};
            var sisaAsal = (r.stok_asal || 0) - it.jumlah;
            var apotekSetelah = (r.stok_resep || 0) + (r.dalam_transfer || 0) + it.jumlah;
            var cukup = apotekSetelah + 0.0001 >= (r.kebutuhan || 0);
            if (sisaAsal < -0.0001) lebih.push(r.nama);
            return '<tr' + (sisaAsal < -0.0001 ? ' class="iu-pv-error"' : '') + '>'
                + '<td class="text-muted">' + (i + 1) + '</td>'
                + '<td class="text-left">' + esc(r.nama) + '</td>'
                + '<td class="text-right font-weight-bold">' + fmt(it.jumlah) + ' <small class="text-muted">' + esc(r.satuan || '') + '</small></td>'
                + '<td class="text-right">' + fmt(r.stok_asal) + ' &rarr; ' + fmt(sisaAsal) + '</td>'
                + '<td class="text-right">' + fmt(r.stok_resep) + ' &rarr; ' + fmt(apotekSetelah)
                +   (cukup ? '' : ' <span class="iu-pv-kurang" title="Masih kurang dari yang akan dipakai (' + bulat(r.kebutuhan) + ')">masih kurang</span>') + '</td>'
                + '</tr>';
        }).join('');
        var total = items.reduce(function (s, it) { return s + it.jumlah; }, 0);

        Swal.fire({
            title: 'Preview Mutasi ke Apotek',
            width: 900,
            html: '<div class="iu-pv-route"><strong>' + esc(state.gudangAsal.nama) + '</strong> &rarr; <strong>' + esc(state.gudangResep.nama) + '</strong>'
                + ' <span class="text-muted">· ' + items.length + ' obat · total ' + fmt(total) + '</span></div>'
                + (lebih.length ? '<div class="iu-pv-alert">Jumlah melebihi stok gudang asal: ' + esc(lebih.join(', ')) + '. Ubah jumlahnya dulu.</div>' : '')
                + '<div class="iu-pv-wrap"><table class="table table-sm iu-pv-table mb-0"><thead><tr>'
                + '<th>#</th><th class="text-left">Obat</th><th class="text-right">Jumlah mutasi</th>'
                + '<th class="text-right">Stok ' + esc(state.gudangAsal.nama) + '</th><th class="text-right">Stok apotek</th>'
                + '</tr></thead><tbody>' + baris + '</tbody></table></div>'
                + '<div class="iu-pv-note">Dibuat sebagai Transfer Antar Gudang dengan status menunggu approval. Stok baru pindah setelah disetujui.</div>',
            showCancelButton: true,
            showConfirmButton: lebih.length === 0,
            confirmButtonText: 'Buat mutasi',
            cancelButtonText: lebih.length ? 'Tutup' : 'Batal',
            reverseButtons: true,
            focusConfirm: false
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
                    title: 'Mutasi diajukan',
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

@section('styles')
<style>
.iu-page { --iu-line: #e2e8f0; --iu-muted: #64748b; --iu-text: #1e293b; --iu-bg: #f8fafc;
    --iu-ok: #059669; --iu-warn: #d97706; --iu-bad: #dc2626; --iu-neutral: #2563eb; }
html.theme-dark .iu-page { --iu-line: #334155; --iu-muted: #94a3b8; --iu-text: #e2e8f0; --iu-bg: rgba(148, 163, 184, .08);
    --iu-ok: #34d399; --iu-warn: #fbbf24; --iu-bad: #f87171; --iu-neutral: #93c5fd; }

.iu-summary { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; margin: 4px 0 12px; }
@media (max-width: 991px) { .iu-summary { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
.iu-card { display: flex; gap: 12px; align-items: flex-start; padding: 12px 14px; border: 1px solid var(--iu-line);
    border-left-width: 4px; border-radius: 10px; background: var(--iu-bg); }
.iu-card > i { font-size: 1.1rem; margin-top: 4px; }
.iu-card.is-neutral { border-left-color: var(--iu-neutral); } .iu-card.is-neutral > i { color: var(--iu-neutral); }
.iu-card.is-warn { border-left-color: var(--iu-warn); } .iu-card.is-warn > i { color: var(--iu-warn); }
.iu-card.is-ok { border-left-color: var(--iu-ok); } .iu-card.is-ok > i { color: var(--iu-ok); }
.iu-card.is-bad { border-left-color: var(--iu-bad); } .iu-card.is-bad > i { color: var(--iu-bad); }
.iu-card-num { font-size: 1.4rem; font-weight: 800; line-height: 1.1; color: var(--iu-text); }
.iu-card-title { font-weight: 700; color: var(--iu-text); }
.iu-card-sub { font-size: .75rem; color: var(--iu-muted); }

.iu-howto { font-size: .8rem; color: var(--iu-muted); }
.iu-howto summary { cursor: pointer; font-weight: 700; width: max-content; }
.iu-howto ul { margin-top: 6px; padding-left: 18px; line-height: 1.6; }

.iu-tabs .nav-link { padding: .3rem .75rem; font-size: .85rem; }
#iu-table td { vertical-align: middle; }
#iu-table .iu-jumlah { min-width: 84px; }
#iu-table .iu-kurang { color: var(--iu-bad); font-weight: 800; }

/* Preview modal (SweetAlert): wide table, scrolls when many obat are selected */
.iu-pv-route { margin-bottom: 8px; font-size: .95rem; }
.iu-pv-wrap { max-height: 50vh; overflow: auto; border: 1px solid #e2e8f0; border-radius: 8px; }
.iu-pv-table { font-size: .85rem; }
.iu-pv-table th { position: sticky; top: 0; background: #f1f5f9; white-space: nowrap; }
.iu-pv-table td { vertical-align: middle; white-space: nowrap; }
.iu-pv-table td:nth-child(2) { white-space: normal; }
.iu-pv-table tr.iu-pv-error td { background: rgba(220, 38, 38, .08); color: #b91c1c; }
.iu-pv-kurang { font-size: .72rem; font-weight: 700; color: #b45309; }
.iu-pv-alert { margin-bottom: 8px; padding: 6px 10px; border-radius: 8px; background: rgba(220, 38, 38, .08); color: #b91c1c; font-size: .85rem; }
.iu-pv-note { margin-top: 8px; font-size: .8rem; color: #64748b; }
html.theme-dark .iu-pv-wrap { border-color: #334155; }
html.theme-dark .iu-pv-table th { background: #1e293b; }
html.theme-dark .iu-pv-table tr.iu-pv-error td, html.theme-dark .iu-pv-alert { color: #fca5a5; }
html.theme-dark .iu-pv-kurang { color: #fbbf24; }
html.theme-dark .iu-pv-note { color: #94a3b8; }
</style>
@endsection
