{{-- Permintaan Pembelian form in a modal (create / edit a waiting permintaan).
     window.PermintaanForm.open() / open({ id }) ; PermintaanForm.onSaved(fn) --}}
<style>
    #modalPermintaan .modal-dialog { max-width: 1200px; width: 96%; }
    #prItems th { font-size: .78rem; white-space: nowrap; }
    #prItems td { vertical-align: top; padding: .35rem; }
    #prItems .pr-meta { font-size: .75rem; color: #6c757d; margin-top: .2rem; }
    #prItems .pr-meta .badge { font-size: .68rem; }
    #prItems .form-control, #prItems .custom-select { font-size: .85rem; }
    .pr-total-box { border: 1px solid #dbe4f0; border-radius: .5rem; background: #f8fbff; }
</style>

<div class="modal fade" id="modalPermintaan" tabindex="-1" role="dialog" aria-labelledby="modalPermintaanTitle" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-dialog-scrollable" role="document">
        <form class="modal-content" id="permintaanForm" autocomplete="off">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="modalPermintaanTitle">Buat Permintaan Pembelian</h5>
                    <small class="text-muted">Satu permintaan dibuat per pemasok. Setelah disetujui menjadi Faktur Pembelian (status diminta).</small>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group col-md-3">
                        <label class="small font-weight-bold mb-1" for="pr_request_date">Tanggal Permintaan <span class="text-danger">*</span></label>
                        <input type="date" id="pr_request_date" class="form-control" required>
                    </div>
                    <div class="form-group col-md-9">
                        <label class="small font-weight-bold mb-1" for="pr_notes">Catatan <small class="text-muted">(opsional)</small></label>
                        <input type="text" id="pr_notes" class="form-control" maxlength="1000" placeholder="mis. stok menipis menjelang akhir bulan">
                    </div>
                </div>

                <div class="d-flex flex-wrap justify-content-between align-items-end mb-2">
                    <div class="font-weight-bold">Obat yang dipesan</div>
                    <div class="d-flex align-items-center" style="gap:6px">
                        <label class="small text-muted mb-0" for="pr_period">Rata-rata keluar</label>
                        <select id="pr_period" class="custom-select custom-select-sm" style="width:auto">
                            <option value="1">1 bulan</option>
                            <option value="3" selected>3 bulan</option>
                            <option value="6">6 bulan</option>
                            <option value="12">12 bulan</option>
                        </select>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm mb-2" id="prItems">
                        <thead class="thead-light">
                            <tr>
                                <th style="min-width:260px">Obat</th>
                                <th style="min-width:200px">Pemasok</th>
                                <th style="width:80px">Box</th>
                                <th style="width:95px">Qty</th>
                                <th style="min-width:150px">Harga (Master Pembelian)</th>
                                <th class="text-right" style="width:120px">Estimasi</th>
                                <th style="width:34px"></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                <div class="d-flex flex-wrap justify-content-between align-items-start">
                    <div class="mb-2">
                        <button type="button" class="btn btn-sm btn-outline-primary" id="pr_add"><i class="fas fa-plus"></i> Tambah Obat</button>
                        <button type="button" class="btn btn-sm btn-outline-success ml-1" id="pr_forecast" title="Obat yang menurut forecast perlu dipesan sekarang, dengan jumlah saran per box dan pemasok termurah"><i class="fas fa-magic"></i> Isi dari Forecast</button>
                        <div class="small text-muted mt-2">Isi jumlah box; qty dihitung dari isi per box di Master Pembelian.</div>
                    </div>
                    <div class="pr-total-box p-2 mb-2" style="min-width:300px">
                        <div class="d-flex justify-content-between small"><span>Setelah diskon</span><strong id="pr_sub">Rp 0</strong></div>
                        <div class="d-flex justify-content-between small"><span>PPN <span id="pr_ppn_rate"></span>%</span><strong id="pr_ppn">Rp 0</strong></div>
                        <div class="d-flex justify-content-between mt-1 pt-1 border-top"><span class="font-weight-bold">Perkiraan total</span><strong class="h5 mb-0" id="pr_total">Rp 0</strong></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary" id="pr_submit"><i class="fas fa-paper-plane"></i> Ajukan Permintaan</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="similarObatModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Obat Serupa <small class="text-muted" id="similarObatName"></small></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="small text-muted mb-2" id="similarObatInfo"></div>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm mb-0">
                        <thead class="thead-light"><tr><th>Nama Obat</th><th class="text-right">Stok</th><th class="text-right">Isi/Box</th><th class="text-right">Harga</th><th class="text-right">Setelah diskon</th><th>Zat aktif sama</th><th></th></tr></thead>
                        <tbody id="similarObatBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
window.PermintaanForm = (function () {
    var URLS = {
        store: '{{ route('erm.permintaan.store') }}',
        base: '{{ url('/erm/permintaan') }}',
        obat: '{{ url('/erm/ajax/obat') }}',
        info: '{{ route('erm.permintaan.obat-info') }}',
        saran: '{{ route('erm.permintaan.saran-forecast') }}',
        obatBase: '{{ url('/erm/obat') }}'
    };
    var CSRF = '{{ csrf_token() }}';
    var PPN = @json((float) config('pembelian.ppn_persen', 11));
    // Row info per obat (pemasok + price, principal, stock / usage), filled by form-data, saran-forecast or obat-info
    var state = { id: null, info: {}, similarRow: null };
    var savedCallback = function () {};
    var $modal, $tbody;

    function esc(v) { return $('<div>').text(v == null ? '' : v).html(); }
    function num(v) { var n = parseFloat(String(v == null ? '' : v).replace(',', '.')); return isNaN(n) ? 0 : n; }
    function rupiah(v) { return 'Rp ' + Number(v || 0).toLocaleString('id-ID', { maximumFractionDigits: 0 }); }
    function fmt(v) { return Number(v || 0).toLocaleString('id-ID', { maximumFractionDigits: 2 }); }
    function isPercent(t) { return ['persen', 'percent', '%'].indexOf(String(t || '').toLowerCase()) !== -1; }

    function fetchInfo(obatIds) {
        return $.get(URLS.info, { obat_ids: obatIds, period_months: $('#pr_period').val() }).done(function (res) {
            $.extend(state.info, res.info || {});
        });
    }

    function addRow(item) {
        item = item || {};
        var $tr = $('<tr>'
            + '<td><div class="d-flex" style="gap:4px"><select class="pr-obat" style="width:100%"></select>'
                + '<button type="button" class="btn btn-sm btn-outline-info pr-similar" title="Obat serupa (zat aktif sama)" disabled><i class="fa fa-random"></i></button></div>'
                + '<div class="pr-meta pr-info"></div><input type="hidden" class="pr-principal" value="' + (item.principal_id || '') + '"></td>'
            + '<td><select class="custom-select custom-select-sm pr-pemasok" disabled><option value="">Pilih obat dulu</option></select></td>'
            + '<td><input type="number" class="form-control form-control-sm pr-box" min="0" step="1" value="' + (item.jumlah_box != null ? item.jumlah_box : '') + '"></td>'
            + '<td><input type="number" class="form-control form-control-sm pr-qty" min="1" step="1" value="' + (item.qty_total != null ? item.qty_total : '') + '"><div class="pr-meta pr-isi"></div></td>'
            + '<td class="pr-harga small text-muted">-</td>'
            + '<td class="text-right font-weight-bold pr-est">-</td>'
            + '<td class="text-center"><button type="button" class="btn btn-sm btn-link text-danger p-0 pr-remove" title="Hapus baris"><i class="fas fa-times"></i></button></td>'
            + '</tr>');
        $tbody.append($tr);

        var $obat = $tr.find('.pr-obat').select2({
            dropdownParent: $modal,
            placeholder: 'Cari obat...',
            minimumInputLength: 2,
            width: '100%',
            ajax: { url: URLS.obat, dataType: 'json', delay: 250, data: function (p) { return { q: p.term }; }, processResults: function (data) { return { results: data }; } }
        });
        if (item.obat_id) {
            $obat.append(new Option(item.obat_nama || ('Obat #' + item.obat_id), item.obat_id, true, true));
            applyInfo($tr, item.pemasok_id, true);
        }
        $obat.on('select2:select', function () { pickObat($tr); });
        return $tr;
    }

    // A new obat chosen in a row: one request for its pemasok, price and stock
    function pickObat($tr) {
        var obatId = $tr.find('.pr-obat').val();
        $tr.find('.pr-principal').val('');
        $tr.find('.pr-pemasok').html('<option value="">Memuat...</option>').prop('disabled', true);
        if (state.info[obatId]) { applyInfo($tr, null, false); return; }
        fetchInfo([obatId]).always(function () { applyInfo($tr, null, false); });
    }

    // Fill a row from the info already loaded; no request
    function applyInfo($tr, pemasokId, keepQty) {
        var obatId = $tr.find('.pr-obat').val();
        var info = state.info[obatId];
        var $p = $tr.find('.pr-pemasok');
        $tr.find('.pr-similar').prop('disabled', !obatId);
        if (!info) { renderRow($tr); return; }

        if (info.principal && !$tr.find('.pr-principal').val()) $tr.find('.pr-principal').val(info.principal.id);
        var list = info.pemasoks || [];
        if (!list.length) {
            $p.html('<option value="">Belum ada di Master Pembelian</option>').prop('disabled', true);
        } else {
            var chosen = pemasokId || (list.length === 1 ? list[0].id : '');
            $p.html('<option value="">Pilih pemasok</option>' + list.map(function (o) {
                return '<option value="' + o.id + '"' + (String(o.id) === String(chosen) ? ' selected' : '') + '>' + esc(o.text) + '</option>';
            }).join('')).prop('disabled', false);
        }
        if (!keepQty) syncQty($tr);
        renderRow($tr);
    }

    function master($tr) {
        var info = state.info[$tr.find('.pr-obat').val()];
        var pemasokId = $tr.find('.pr-pemasok').val();
        if (!info || !pemasokId) return null;
        return (info.pemasoks || []).filter(function (o) { return String(o.id) === String(pemasokId); })[0] || null;
    }

    function syncQty($tr) {
        var m = master($tr);
        var box = num($tr.find('.pr-box').val());
        if (m && m.qty_per_box > 0 && box > 0) $tr.find('.pr-qty').val(box * m.qty_per_box);
    }

    function lineValue($tr) {
        var m = master($tr);
        if (!m) return null;
        var base = num(m.harga) * num($tr.find('.pr-qty').val());
        var diskon = isPercent(m.diskon_type) ? base * num(m.diskon) / 100 : num(m.diskon);
        return Math.max(base - diskon, 0);
    }

    function renderRow($tr) {
        var obatId = $tr.find('.pr-obat').val();
        var info = state.info[obatId] || {};
        var m = master($tr);
        var metric = info.metric;
        var parts = [];
        if (info.principal) parts.push('<span class="badge badge-info">' + esc(info.principal.text) + '</span>');
        if (metric) {
            parts.push('Stok ' + fmt(metric.total_stock));
            parts.push('keluar ±' + fmt(metric.average_monthly_keluar) + '/bln');
            if (metric.ordered_this_month_qty > 0) parts.push('<span class="text-warning">sudah dipesan bulan ini ' + fmt(metric.ordered_this_month_qty) + '</span>');
        }
        $tr.find('.pr-info').html(parts.join(' · '));
        $tr.find('.pr-isi').text(m && m.qty_per_box ? 'isi ' + fmt(m.qty_per_box) + '/box' : '');
        $tr.find('.pr-harga').html(m
            ? rupiah(m.harga) + '<span class="text-muted">/unit</span>' + (num(m.diskon) > 0 ? '<br>diskon ' + (isPercent(m.diskon_type) ? fmt(m.diskon) + '%' : rupiah(m.diskon)) : '')
            : (obatId && $tr.find('.pr-pemasok').val() ? '<span class="text-warning">harga belum ada</span>' : '-'));
        var v = lineValue($tr);
        $tr.find('.pr-est').text(v === null ? '-' : rupiah(v));
        renderTotals();
    }

    function renderTotals() {
        var sub = 0;
        $tbody.find('tr').each(function () { sub += lineValue($(this)) || 0; });
        var ppn = sub * PPN / 100;
        $('#pr_sub').text(rupiah(sub));
        $('#pr_ppn').text(rupiah(ppn));
        $('#pr_total').text(rupiah(sub + ppn));
    }

    function clearRows() {
        $tbody.find('.pr-obat').each(function () { if ($(this).data('select2')) $(this).select2('destroy'); });
        $tbody.empty();
    }

    function reset(data) {
        data = data || {};
        state.id = data.id || null;
        state.info = $.extend({}, data.info || {});
        $('#modalPermintaanTitle').text(data.id ? 'Ubah Permintaan ' + data.no_permintaan : 'Buat Permintaan Pembelian');
        $('#pr_submit').html(data.id ? '<i class="fas fa-save"></i> Simpan Perubahan' : '<i class="fas fa-paper-plane"></i> Ajukan Permintaan').prop('disabled', false);
        $('#pr_request_date').val(data.request_date || moment().format('YYYY-MM-DD'));
        $('#pr_notes').val(data.notes || '');
        clearRows();
        (data.items || []).forEach(function (item) { addRow(item); });
        if (!(data.items || []).length) addRow();
        renderTotals();
    }

    function open(opts) {
        opts = opts || {};
        if (!opts.id) {
            reset();
            $modal.modal('show');
            return;
        }
        // Open at once with a loading row; the single form-data request fills everything
        reset({ id: opts.id, no_permintaan: '' });
        clearRows();
        $tbody.html('<tr><td colspan="7" class="text-center text-muted py-4"><i class="fas fa-spinner fa-spin"></i> Memuat permintaan...</td></tr>');
        $('#pr_submit').prop('disabled', true);
        $modal.modal('show');
        $.get(URLS.base + '/' + opts.id + '/form-data').done(function (data) {
            reset(data);
        }).fail(function (xhr) {
            $modal.modal('hide');
            Swal.fire({ icon: 'warning', title: 'Tidak bisa diubah', text: (xhr.responseJSON && xhr.responseJSON.message) || 'Data permintaan tidak dapat dimuat.' });
        });
    }

    function collect() {
        var items = [];
        var problems = [];
        $tbody.find('tr').each(function () {
            var $tr = $(this);
            if (!$tr.find('.pr-obat').length) return;
            var obatId = $tr.find('.pr-obat').val();
            var nama = $tr.find('.pr-obat option:selected').text();
            if (!obatId) { if (num($tr.find('.pr-qty').val()) > 0) problems.push('Ada baris tanpa obat.'); return; }
            if (!$tr.find('.pr-pemasok').val()) problems.push(nama + ': pilih pemasok (obat harus ada di Master Pembelian).');
            if (num($tr.find('.pr-qty').val()) < 1) problems.push(nama + ': qty minimal 1.');
            items.push({
                obat_id: obatId,
                pemasok_id: $tr.find('.pr-pemasok').val(),
                principal_id: $tr.find('.pr-principal').val(),
                jumlah_box: Math.max(0, parseInt($tr.find('.pr-box').val() || 0, 10)),
                qty_total: parseInt($tr.find('.pr-qty').val() || 0, 10)
            });
        });
        var seen = {};
        items.forEach(function (it) {
            var key = it.obat_id + '|' + it.pemasok_id;
            if (seen[key]) problems.push('Obat yang sama dari pemasok yang sama muncul dua kali; gabungkan jumlahnya.');
            seen[key] = true;
        });
        if (!items.length) problems.push('Tambahkan minimal satu obat.');
        return { items: items, problems: problems.filter(function (v, i, a) { return a.indexOf(v) === i; }) };
    }

    function openSimilar($tr) {
        state.similarRow = $tr;
        $('#similarObatName').text($tr.find('.pr-obat option:selected').text());
        $('#similarObatInfo').text('Memuat...');
        $('#similarObatBody').html('');
        $('#similarObatModal').modal('show');
        $.get(URLS.obatBase + '/' + $tr.find('.pr-obat').val() + '/similar').done(function (res) {
            $('#similarObatInfo').text((res.shared_zat_aktif || []).length ? 'Kandungan: ' + res.shared_zat_aktif.join(', ') : (res.message || 'Obat ini belum punya data kandungan.'));
            var rows = (res.rows || []).map(function (r) {
                return '<tr><td>' + esc(r.obat_nama) + '</td>'
                    + '<td class="text-right">' + fmt(r.total_stock) + '</td>'
                    + '<td class="text-right">' + (r.isi_per_box ? fmt(r.isi_per_box) : '-') + '</td>'
                    + '<td class="text-right">' + (r.harga != null ? rupiah(r.harga) : '-') + '</td>'
                    + '<td class="text-right">' + (r.setelah_diskon != null ? rupiah(r.setelah_diskon) : '-') + '</td>'
                    + '<td class="small">' + esc((r.matched_zat_aktif || []).join(', ')) + '</td>'
                    + '<td><button type="button" class="btn btn-sm btn-primary pr-pick-similar" data-id="' + r.obat_id + '" data-nama="' + esc(r.obat_nama) + '">Pilih</button></td></tr>';
            }).join('');
            $('#similarObatBody').html(rows || '<tr><td colspan="7" class="text-center text-muted">Tidak ada obat serupa.</td></tr>');
        }).fail(function () { $('#similarObatInfo').text('Obat serupa tidak dapat dimuat.'); });
    }

    function init() {
        $modal = $('#modalPermintaan');
        $tbody = $('#prItems tbody');
        $('#pr_ppn_rate').text(PPN);

        // Price of another pemasok is already loaded
        $tbody.on('change', '.pr-pemasok', function () { var $tr = $(this).closest('tr'); syncQty($tr); renderRow($tr); });
        $tbody.on('input', '.pr-box', function () { var $tr = $(this).closest('tr'); syncQty($tr); renderRow($tr); });
        $tbody.on('input', '.pr-qty', function () { renderRow($(this).closest('tr')); });
        $tbody.on('click', '.pr-remove', function () {
            if ($tbody.find('tr').length <= 1) return;
            $(this).closest('tr').remove();
            renderTotals();
        });
        $tbody.on('click', '.pr-similar', function () { openSimilar($(this).closest('tr')); });
        $('#similarObatBody').on('click', '.pr-pick-similar', function () {
            var $tr = state.similarRow;
            if (!$tr) return;
            $tr.find('.pr-obat').empty().append(new Option($(this).data('nama'), $(this).data('id'), true, true)).trigger('change');
            pickObat($tr);
            $('#similarObatModal').modal('hide');
        });
        $('#pr_add').on('click', function () { addRow().find('.pr-obat').select2('open'); });

        // Usage period changed: one request for every obat in the form
        $('#pr_period').on('change', function () {
            var ids = $tbody.find('.pr-obat').map(function () { return $(this).val(); }).get().filter(Boolean);
            if (!ids.length) return;
            fetchInfo(ids).done(function () { $tbody.find('tr').each(function () { renderRow($(this)); }); });
        });

        $('#pr_forecast').on('click', function () {
            var $btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Menghitung...');
            $.get(URLS.saran).done(function (res) {
                $.extend(state.info, res.info || {});
                var existing = {};
                $tbody.find('.pr-obat').each(function () { if ($(this).val()) existing[$(this).val()] = true; });
                var rows = (res.rows || []).filter(function (r) { return !existing[r.obat_id]; });
                var notes = '';
                if ((res.sudah_diminta || []).length) notes += '<div class="small text-muted mt-2">' + res.sudah_diminta.length + ' obat dilewati karena sudah ada di permintaan yang menunggu approval.</div>';
                if ((res.tanpa_pemasok || []).length) notes += '<div class="small text-warning mt-1">' + res.tanpa_pemasok.length + ' obat dilewati karena belum ada di Master Pembelian: ' + esc(res.tanpa_pemasok.slice(0, 5).join(', ')) + (res.tanpa_pemasok.length > 5 ? ', …' : '') + '</div>';
                if (!rows.length) {
                    Swal.fire({ icon: 'info', title: 'Tidak ada saran baru', html: 'Tidak ada obat yang perlu dipesan menurut forecast, atau semuanya sudah ada di daftar.' + notes });
                    return;
                }
                var list = rows.slice(0, 15).map(function (r) {
                    return '<li>' + esc(r.obat_nama) + ': <strong>' + r.jumlah_box + ' box</strong> (' + r.qty_total + ') dari ' + esc(r.pemasok_nama) + (r.status === 'kritis' ? ' <span class="badge badge-danger">kritis</span>' : '') + '</li>';
                }).join('') + (rows.length > 15 ? '<li>… dan ' + (rows.length - 15) + ' obat lain</li>' : '');
                Swal.fire({
                    icon: 'question',
                    title: 'Tambahkan ' + rows.length + ' obat dari forecast?',
                    html: '<ul class="text-left small mb-1">' + list + '</ul>' + notes,
                    showCancelButton: true,
                    confirmButtonText: 'Ya, tambahkan',
                    cancelButtonText: 'Batal',
                    reverseButtons: true
                }).then(function (result) {
                    if (!result.value) return;
                    $tbody.find('tr').each(function () { if (!$(this).find('.pr-obat').val()) $(this).remove(); });
                    rows.forEach(function (r) { addRow(r); });
                });
            }).fail(function () {
                Swal.fire({ icon: 'error', title: 'Gagal', text: 'Saran forecast tidak dapat dimuat.' });
            }).always(function () { $btn.prop('disabled', false).html('<i class="fas fa-magic"></i> Isi dari Forecast'); });
        });

        $('#permintaanForm').on('submit', function (e) {
            e.preventDefault();
            var data = collect();
            if (data.problems.length) {
                Swal.fire({ icon: 'warning', title: 'Periksa lagi', html: '<div class="text-left small">' + data.problems.map(esc).join('<br>') + '</div>' });
                return;
            }
            var $btn = $('#pr_submit').prop('disabled', true);
            $.ajax({
                url: state.id ? URLS.base + '/' + state.id : URLS.store,
                type: state.id ? 'PUT' : 'POST',
                data: { _token: CSRF, request_date: $('#pr_request_date').val(), notes: $('#pr_notes').val(), items: data.items }
            }).done(function (res) {
                $modal.modal('hide');
                savedCallback(res);
            }).fail(function (xhr) {
                var json = xhr.responseJSON || {};
                var list = json.errors ? [].concat.apply([], Object.values(json.errors)) : [json.message || 'Permintaan gagal disimpan.'];
                Swal.fire({ icon: 'warning', title: 'Belum tersimpan', html: '<div class="text-left small">' + list.filter(function (v, i, a) { return a.indexOf(v) === i; }).map(esc).join('<br>') + '</div>' });
            }).always(function () { $btn.prop('disabled', false); });
        });
    }

    $(init);

    return {
        open: open,
        onSaved: function (fn) { savedCallback = fn; }
    };
})();
</script>
