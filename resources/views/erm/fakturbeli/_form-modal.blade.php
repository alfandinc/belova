{{-- Faktur Pembelian form in a modal: input a faktur "diminta", edit one that is not approved yet,
     a replacement for an approved retur, or a new faktur without permintaan.
     window.FakturForm.open({ id }) / open({ pengganti: returId }) / open({}) ; FakturForm.onSaved(fn) --}}
<style>
    #modalFaktur .modal-dialog { max-width: 1320px; width: 96%; }
    #fakturItems th { font-size: .78rem; white-space: nowrap; vertical-align: bottom; }
    #fakturItems td { vertical-align: top; padding: .3rem; }
    #fakturItems .form-control, #fakturItems .custom-select { font-size: .85rem; }
    #fakturItems .input-group-sm .custom-select { max-width: 58px; padding-left: .3rem; }
    #fakturItems tr.fk-kurang td { background: #fff8e1; }
    #fakturItems tr.fk-kosong td { background: #fafafa; color: #6c757d; }
    #fakturItems .fk-obat { min-width: 200px; }
    .fk-total-box { border: 1px solid #dbe4f0; border-radius: .5rem; background: #f8fbff; }
</style>

<div class="modal fade" id="modalFaktur" tabindex="-1" role="dialog" aria-labelledby="modalFakturTitle" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-dialog-scrollable" role="document">
        <form class="modal-content" id="fakturForm" enctype="multipart/form-data" autocomplete="off">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="modalFakturTitle">Faktur Pembelian</h5>
                    <small class="text-muted" id="fakturSubtitle"></small>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="permintaan_id" id="fk_permintaan_id">
                <input type="hidden" name="replaced_fakturbeli_id" id="fk_replaced_id">
                <input type="hidden" name="source_retur_id" id="fk_source_retur_id">
                <input type="hidden" name="requested_date" id="fk_requested_date">
                <input type="hidden" name="global_diskon" id="fk_global_diskon_value">
                <input type="hidden" name="global_pajak" id="fk_global_pajak_value">

                <div class="alert alert-info small py-2 d-none" id="fakturInfo"></div>

                {{-- 1. Faktur header, as printed on the supplier invoice --}}
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label class="small font-weight-bold mb-1">Pemasok <span class="text-danger">*</span></label>
                        <select id="fk_pemasok" class="form-control" style="width:100%"></select>
                        <input type="hidden" name="pemasok_id" id="fk_pemasok_id">
                    </div>
                    <div class="form-group col-md-3">
                        <label class="small font-weight-bold mb-1" for="fk_no_faktur">No Faktur <span class="text-danger">*</span></label>
                        <input type="text" name="no_faktur" id="fk_no_faktur" class="form-control" maxlength="191" required placeholder="Sesuai faktur pemasok">
                    </div>
                    <div class="form-group col-6 col-md-2">
                        <label class="small font-weight-bold mb-1" for="fk_received_date">Tanggal Terima <span class="text-danger">*</span></label>
                        <input type="date" name="received_date" id="fk_received_date" class="form-control" required>
                    </div>
                    <div class="form-group col-6 col-md-3">
                        <label class="small font-weight-bold mb-1" for="fk_due_date">Jatuh Tempo</label>
                        <div class="input-group">
                            <input type="date" name="due_date" id="fk_due_date" class="form-control">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-outline-secondary btn-due" data-days="30" title="30 hari dari tanggal terima">+30</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-2">
                        <label class="small font-weight-bold mb-1" for="fk_ship_date">Tanggal Kirim</label>
                        <input type="date" name="ship_date" id="fk_ship_date" class="form-control">
                    </div>
                    <div class="form-group col-md-6">
                        <label class="small font-weight-bold mb-1" for="fk_notes">Catatan</label>
                        <input type="text" name="notes" id="fk_notes" class="form-control" maxlength="1000">
                    </div>
                    <div class="form-group col-md-4">
                        <label class="small font-weight-bold mb-1" for="fk_bukti">Foto faktur <small class="text-muted" id="fk_bukti_link"></small></label>
                        <input type="file" name="bukti" id="fk_bukti" class="form-control-file" accept="image/*">
                    </div>
                </div>

                {{-- 2. Lines --}}
                <div class="d-flex flex-wrap justify-content-between align-items-end mb-2">
                    <div class="font-weight-bold">Barang diterima</div>
                    <div class="d-flex flex-wrap align-items-center" style="gap:6px">
                        <label class="small mb-0 text-muted" for="fk_gudang_default">Gudang semua baris</label>
                        <select id="fk_gudang_default" class="custom-select custom-select-sm" style="width:auto"></select>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="fk_terima_semua" title="Isi qty diterima = qty diminta untuk semua baris">Terima semua sesuai diminta</button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm mb-2" id="fakturItems">
                        <thead class="thead-light">
                            <tr>
                                <th class="fk-obat">Obat</th>
                                <th class="text-right">Diminta</th>
                                <th style="width:85px">Diterima <span class="text-danger">*</span></th>
                                <th style="width:120px">Harga satuan</th>
                                <th style="width:130px">Diskon</th>
                                <th style="width:130px">Pajak item</th>
                                <th class="text-right" style="width:110px">Total</th>
                                <th style="width:140px">Gudang</th>
                                <th style="width:120px">Batch <span class="text-danger">*</span></th>
                                <th style="width:140px">Kadaluarsa <span class="text-danger">*</span></th>
                                <th style="width:34px"></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                <div class="d-flex flex-wrap justify-content-between align-items-start">
                    <div class="mb-2">
                        <button type="button" class="btn btn-sm btn-outline-primary" id="fk_add_item"><i class="fas fa-plus"></i> Tambah Obat</button>
                        <div class="small text-muted mt-2">Batch dan tanggal kadaluarsa wajib untuk baris dengan qty diterima lebih dari 0.<br>Baris dengan qty 0 dianggap belum datang dan dibuat faktur lanjutan saat approve.</div>
                    </div>
                    {{-- 3. Totals --}}
                    <div class="fk-total-box p-2 mb-2" style="min-width:330px">
                        <div class="d-flex justify-content-between small"><span>Subtotal</span><strong id="fk_subtotal">Rp 0</strong></div>
                        <div class="d-flex justify-content-between align-items-center mt-1">
                            <span class="small">Diskon global</span>
                            <div class="input-group input-group-sm" style="width:160px">
                                <input type="number" id="fk_global_diskon" class="form-control" step="0.01" min="0" value="0">
                                <div class="input-group-append"><select id="fk_global_diskon_type" class="custom-select custom-select-sm"><option value="nominal">Rp</option><option value="percent">%</option></select></div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-1">
                            <span class="small">Pajak (PPN) global</span>
                            <div class="input-group input-group-sm" style="width:160px">
                                <input type="number" id="fk_global_pajak" class="form-control" step="0.01" min="0" value="0">
                                <div class="input-group-append"><select id="fk_global_pajak_type" class="custom-select custom-select-sm"><option value="nominal">Rp</option><option value="percent">%</option></select></div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between mt-2 pt-2 border-top"><span class="font-weight-bold">Total faktur</span><strong class="h5 mb-0" id="fk_total">Rp 0</strong></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <small class="text-muted mr-auto">Stok baru masuk ke gudang setelah faktur di-approve.</small>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary" id="fk_submit"><i class="fas fa-save"></i> Simpan Faktur</button>
            </div>
        </form>
    </div>
</div>

<script>
window.FakturForm = (function () {
    var URLS = {
        data: '{{ route('erm.fakturbeli.form-data') }}',
        store: '{{ route('erm.fakturbeli.store') }}',
        update: '{{ url('/erm/fakturpembelian') }}/__ID__/update',
        pemasok: '{{ url('/get-pemasok-select2') }}',
        obat: '{{ url('/get-obat-select2') }}'
    };
    var CSRF = '{{ csrf_token() }}';
    var state = { id: null, gudangs: [], defaultGudang: null };
    var savedCallback = function () {};
    var $modal, $tbody;

    function esc(v) { return $('<div>').text(v == null ? '' : v).html(); }
    function num(v) { var n = parseFloat(String(v == null ? '' : v).replace(',', '.')); return isNaN(n) ? 0 : n; }
    function rupiah(v) { return 'Rp ' + Number(v || 0).toLocaleString('id-ID', { maximumFractionDigits: 2 }); }

    function gudangOptions(selected) {
        return state.gudangs.map(function (g) {
            return '<option value="' + g.id + '"' + (String(g.id) === String(selected) ? ' selected' : '') + '>' + esc(g.nama) + '</option>';
        }).join('');
    }

    // One line; obat of an existing line is fixed (it belongs to the permintaan / retur)
    function addRow(item) {
        item = item || {};
        var fixed = !!item.obat_id && !item.baru;
        var $tr = $('<tr>'
            + '<td class="fk-obat">'
                + (fixed
                    ? '<div class="font-weight-bold">' + esc(item.obat_nama) + '</div><small class="text-muted">' + esc(item.satuan || '') + '</small><input type="hidden" class="f-obat" value="' + item.obat_id + '">'
                    : '<select class="form-control form-control-sm f-obat-select" style="width:100%"></select><input type="hidden" class="f-obat" value="">')
                + '<input type="hidden" class="f-pi" value="' + (item.permintaan_item_id || '') + '">'
            + '</td>'
            + '<td class="text-right align-middle"><span class="f-diminta-label">' + (item.diminta ? Number(item.diminta).toLocaleString('id-ID') : '-') + '</span><input type="hidden" class="f-diminta" value="' + (item.diminta || '') + '"></td>'
            + '<td><input type="number" class="form-control form-control-sm f-qty" min="0" step="1" value="' + (item.qty != null ? item.qty : '') + '"></td>'
            + '<td><input type="number" class="form-control form-control-sm f-harga" min="0" step="0.01" value="' + (item.harga != null ? item.harga : '') + '"></td>'
            + '<td><div class="input-group input-group-sm"><input type="number" class="form-control f-diskon" min="0" step="0.01" value="' + (item.diskon || 0) + '">'
                + '<div class="input-group-append"><select class="custom-select f-diskon-type"><option value="nominal"' + (item.diskon_type === 'percent' ? '' : ' selected') + '>Rp</option><option value="percent"' + (item.diskon_type === 'percent' ? ' selected' : '') + '>%</option></select></div></div></td>'
            + '<td><div class="input-group input-group-sm"><input type="number" class="form-control f-tax" min="0" step="0.01" value="' + (item.tax || 0) + '">'
                + '<div class="input-group-append"><select class="custom-select f-tax-type"><option value="nominal"' + (item.tax_type === 'percent' ? '' : ' selected') + '>Rp</option><option value="percent"' + (item.tax_type === 'percent' ? ' selected' : '') + '>%</option></select></div></div></td>'
            + '<td class="text-right align-middle font-weight-bold f-total">Rp 0</td>'
            + '<td><select class="custom-select custom-select-sm f-gudang">' + gudangOptions(item.gudang_id || state.defaultGudang) + '</select></td>'
            + '<td><input type="text" class="form-control form-control-sm f-batch" maxlength="255" value="' + esc(item.batch || '') + '"></td>'
            + '<td><input type="date" class="form-control form-control-sm f-ed" value="' + esc(item.expiration_date || '') + '"></td>'
            + '<td class="text-center align-middle"><button type="button" class="btn btn-sm btn-link text-danger p-0 f-remove" title="Hapus baris"><i class="fas fa-times"></i></button></td>'
            + '</tr>');
        $tbody.append($tr);

        if (!fixed) {
            $tr.find('.f-obat-select').select2({
                dropdownParent: $modal,
                placeholder: 'Cari obat...',
                minimumInputLength: 2,
                width: '100%',
                ajax: {
                    url: URLS.obat, dataType: 'json', delay: 250,
                    data: function (p) { return { q: p.term }; },
                    processResults: function (data) { return { results: data.map(function (o) { return { id: o.id, text: o.nama }; }) }; }
                }
            }).on('select2:select', function (e) {
                $tr.find('.f-obat').val(e.params.data.id);
                $tr.find('.f-qty').trigger('focus');
            });
        }
        refreshRow($tr);
        return $tr;
    }

    function refreshRow($tr) {
        var qty = num($tr.find('.f-qty').val());
        var harga = num($tr.find('.f-harga').val());
        var base = qty * harga;
        var diskon = num($tr.find('.f-diskon').val());
        var tax = num($tr.find('.f-tax').val());
        var diskonValue = $tr.find('.f-diskon-type').val() === 'percent' ? base * diskon / 100 : diskon;
        var taxValue = $tr.find('.f-tax-type').val() === 'percent' ? base * tax / 100 : tax;
        var total = Math.max(base - diskonValue + taxValue, 0);
        $tr.data('total', total);
        $tr.find('.f-total').text(rupiah(total));

        var diminta = num($tr.find('.f-diminta').val());
        $tr.toggleClass('fk-kurang', diminta > 0 && qty > 0 && qty < diminta).toggleClass('fk-kosong', qty <= 0);
        $tr.find('.f-qty').toggleClass('is-invalid', diminta > 0 && qty > diminta);
        // batch / ED only matter for received lines
        $tr.find('.f-batch, .f-ed').prop('required', qty > 0);
        refreshTotals();
    }

    function refreshTotals() {
        var subtotal = 0;
        $tbody.find('tr').each(function () { subtotal += $(this).data('total') || 0; });
        var gd = num($('#fk_global_diskon').val());
        var gp = num($('#fk_global_pajak').val());
        var gdValue = $('#fk_global_diskon_type').val() === 'percent' ? subtotal * gd / 100 : gd;
        var gpValue = $('#fk_global_pajak_type').val() === 'percent' ? subtotal * gp / 100 : gp;
        $('#fk_subtotal').text(rupiah(subtotal));
        $('#fk_total').text(rupiah(Math.max(subtotal - gdValue + gpValue, 0)));
        // The server stores the global amounts as Rupiah
        $('#fk_global_diskon_value').val(gdValue.toFixed(2));
        $('#fk_global_pajak_value').val(gpValue.toFixed(2));
    }

    function fill(d) {
        state.id = d.id;
        state.gudangs = d.gudangs || [];
        state.defaultGudang = d.default_gudang_id;

        var titles = {
            input: 'Input Faktur dari Pemasok',
            edit: 'Edit Faktur ' + (d.no_faktur || ''),
            pengganti: 'Faktur Pengganti Retur ' + (d.no_retur || ''),
            baru: 'Faktur Baru (tanpa permintaan)'
        };
        $('#modalFakturTitle').text(titles[d.mode] || 'Faktur Pembelian');
        $('#fakturSubtitle').text(d.no_permintaan ? 'Dari permintaan ' + d.no_permintaan : '');
        var info = '';
        if (d.lanjutan_dari) info = 'Faktur lanjutan untuk barang yang belum datang dari faktur <strong>' + esc(d.lanjutan_dari) + '</strong>. Isi nomor faktur pengiriman ini.';
        if (d.mode === 'pengganti') info = 'Barang pengganti untuk retur <strong>' + esc(d.no_retur) + '</strong>. Isi batch dan kadaluarsa barang yang baru diterima.';
        $('#fakturInfo').html(info).toggleClass('d-none', !info);

        $('#fk_permintaan_id').val(d.permintaan_id || '');
        $('#fk_replaced_id').val(d.replaced_fakturbeli_id || '');
        $('#fk_source_retur_id').val(d.source_retur_id || '');
        $('#fk_requested_date').val(d.requested_date || '');
        $('#fk_no_faktur').val(d.no_faktur || '');
        $('#fk_received_date').val(d.received_date || '');
        $('#fk_due_date').val(d.due_date || '');
        $('#fk_ship_date').val(d.ship_date || '');
        $('#fk_notes').val(d.notes || '');
        $('#fk_bukti').val('');
        $('#fk_bukti_link').html(d.bukti_url ? '<a href="' + d.bukti_url + '" target="_blank">(lihat yang ada)</a>' : '');

        var $p = $('#fk_pemasok').empty();
        if (d.pemasok) $p.append(new Option(d.pemasok.nama, d.pemasok.id, true, true));
        $p.prop('disabled', !!d.pemasok_terkunci).trigger('change');
        $('#fk_pemasok_id').val(d.pemasok ? d.pemasok.id : '');

        $('#fk_gudang_default').html(gudangOptions(state.defaultGudang));
        $('#fk_add_item').toggle(d.mode === 'baru' || d.mode === 'edit');
        $('#fk_terima_semua').toggle((d.items || []).some(function (i) { return i.diminta > 0; }));

        // Global amounts: stored as Rupiah; a faktur without them starts with PPN in percent
        $('#fk_global_diskon').val(d.global_diskon || 0);
        $('#fk_global_diskon_type').val('nominal');
        if (d.global_pajak === null || d.global_pajak === undefined) {
            $('#fk_global_pajak').val(d.ppn_persen);
            $('#fk_global_pajak_type').val('percent');
        } else {
            $('#fk_global_pajak').val(d.global_pajak);
            $('#fk_global_pajak_type').val('nominal');
        }

        $tbody.empty();
        (d.items || []).forEach(function (item) { addRow(item); });
        if (!(d.items || []).length) addRow({ baru: true });
        refreshTotals();
    }

    function open(opts) {
        opts = opts || {};
        var params = opts.id ? { id: opts.id } : (opts.pengganti ? { replace_retur_id: opts.pengganti } : {});
        $.get(URLS.data, params).done(function (d) {
            fill(d);
            $modal.modal('show');
        }).fail(function (xhr) {
            Swal.fire({ icon: 'warning', title: 'Tidak bisa dibuka', text: (xhr.responseJSON && xhr.responseJSON.message) || 'Data faktur tidak dapat dimuat.' });
        });
    }

    // Field names the controller expects: items[i][...]
    function buildFormData() {
        var fd = new FormData($('#fakturForm')[0]);
        $tbody.find('tr').each(function (i) {
            var $tr = $(this);
            var put = function (k, v) { fd.append('items[' + i + '][' + k + ']', v == null ? '' : v); };
            put('obat_id', $tr.find('.f-obat').val());
            put('permintaan_item_id', $tr.find('.f-pi').val());
            put('diminta', $tr.find('.f-diminta').val());
            put('qty', $tr.find('.f-qty').val() || 0);
            put('harga', $tr.find('.f-harga').val() || 0);
            put('diskon', $tr.find('.f-diskon').val() || 0);
            put('diskon_type', $tr.find('.f-diskon-type').val());
            put('tax', $tr.find('.f-tax').val() || 0);
            put('tax_type', $tr.find('.f-tax-type').val());
            put('gudang_id', $tr.find('.f-gudang').val());
            put('batch', $.trim($tr.find('.f-batch').val()));
            put('expiration_date', $tr.find('.f-ed').val());
        });
        fd.set('_token', CSRF);
        return fd;
    }

    function validate() {
        var problems = [];
        if (!$('#fk_pemasok_id').val()) problems.push('Pilih pemasok.');
        if (!$.trim($('#fk_no_faktur').val())) problems.push('Isi nomor faktur.');
        var received = 0;
        $tbody.find('tr').each(function () {
            var $tr = $(this);
            var nama = $tr.find('.fk-obat .font-weight-bold').text() || $tr.find('.f-obat-select option:selected').text() || 'Baris baru';
            var qty = num($tr.find('.f-qty').val());
            var diminta = num($tr.find('.f-diminta').val());
            if (!$tr.find('.f-obat').val()) { problems.push('Ada baris tanpa obat.'); return; }
            if (diminta > 0 && qty > diminta) problems.push(nama + ': diterima melebihi diminta (' + diminta + ').');
            if (qty > 0) {
                received++;
                if (!$.trim($tr.find('.f-batch').val()) || !$tr.find('.f-ed').val()) problems.push(nama + ': batch dan kadaluarsa wajib diisi.');
                if (num($tr.find('.f-harga').val()) <= 0) problems.push(nama + ': harga satuan belum diisi.');
            }
        });
        if (!received) problems.push('Isi qty diterima minimal untuk satu obat.');
        return problems.filter(function (v, i, a) { return a.indexOf(v) === i; });
    }

    function init() {
        $modal = $('#modalFaktur');
        $tbody = $('#fakturItems tbody');

        $('#fk_pemasok').select2({
            dropdownParent: $modal,
            placeholder: 'Cari pemasok...',
            minimumInputLength: 2,
            width: '100%',
            ajax: {
                url: URLS.pemasok, dataType: 'json', delay: 250,
                data: function (p) { return { q: p.term }; },
                processResults: function (data) { return { results: data.map(function (o) { return { id: o.id, text: o.nama }; }) }; }
            }
        }).on('change', function () { $('#fk_pemasok_id').val($(this).val() || ''); });

        $tbody.on('input change', 'input, select', function () { refreshRow($(this).closest('tr')); });
        $('#fk_global_diskon, #fk_global_pajak, #fk_global_diskon_type, #fk_global_pajak_type').on('input change', refreshTotals);
        $tbody.on('click', '.f-remove', function () {
            if ($tbody.find('tr').length <= 1) return;
            $(this).closest('tr').remove();
            refreshTotals();
        });
        $('#fk_add_item').on('click', function () { addRow({ baru: true }).find('.f-obat-select').select2('open'); });
        $('#fk_gudang_default').on('change', function () { $tbody.find('.f-gudang').val($(this).val()); });
        $('#fk_terima_semua').on('click', function () {
            $tbody.find('tr').each(function () {
                var d = num($(this).find('.f-diminta').val());
                if (d > 0) { $(this).find('.f-qty').val(d); refreshRow($(this)); }
            });
        });
        $('.btn-due').on('click', function () {
            var base = $('#fk_received_date').val() ? moment($('#fk_received_date').val()) : moment();
            $('#fk_due_date').val(base.add($(this).data('days'), 'days').format('YYYY-MM-DD'));
        });
        // Enter moves to the next field instead of submitting half-filled
        $modal.on('keydown', 'input', function (e) {
            if (e.key !== 'Enter') return;
            e.preventDefault();
            var $inputs = $modal.find('input:visible, select:visible').not(':disabled');
            $inputs.eq($inputs.index(this) + 1).trigger('focus');
        });

        $('#fakturForm').on('submit', function (e) {
            e.preventDefault();
            var problems = validate();
            if (problems.length) {
                Swal.fire({ icon: 'warning', title: 'Periksa lagi', html: '<div class="text-left small">' + problems.map(esc).join('<br>') + '</div>' });
                return;
            }
            var $btn = $('#fk_submit').prop('disabled', true);
            $.ajax({
                url: state.id ? URLS.update.replace('__ID__', state.id) : URLS.store,
                method: 'POST',
                data: buildFormData(),
                processData: false,
                contentType: false
            }).done(function (res) {
                $modal.modal('hide');
                savedCallback(res);
            }).fail(function (xhr) {
                var json = xhr.responseJSON || {};
                var list = json.errors ? [].concat.apply([], Object.values(json.errors)) : [json.message || 'Faktur gagal disimpan.'];
                var msg = list.filter(function (v, i, a) { return a.indexOf(v) === i; }).map(esc).join('<br>');
                Swal.fire({ icon: 'warning', title: 'Belum tersimpan', html: '<div class="text-left small">' + msg + '</div>' });
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
