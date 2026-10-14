{{--
    "Alternatif kandungan sama" for an obat field (E-Resep dokter + farmasi).
    After an obat is chosen in a Select2 obat field, obat.alternatif is asked (one small request, the search itself
    stays fast) and a single button "N alternatif kandungan sama" appears under the field's row. The button opens a
    modal comparing the alternatives with the chosen obat (kandungan, generik / paten, harga, stok); "Pilih" swaps
    the field's selection.

    ObatAlternatif.bind(selector, {
        params:  function ($select) { return { visitation_id: ..., metode_bayar_id: ... }; },
        mapItem: function (item) { return item; }   // to the page's Select2 item shape (dosis, stok, harga...)
    });
--}}
@once
<style>
    .obat-alt { margin: -4px 0 12px; }
    .obat-alt-btn {
        display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; border-radius: 999px; cursor: pointer; font-size: .78rem; font-weight: 600;
        border: 1px solid rgba(var(--ef-accent-rgb, 37, 99, 235), .35); background: rgba(var(--ef-accent-rgb, 37, 99, 235), .07);
        color: var(--ef-accent-text, #1d4ed8);
    }
    .obat-alt-btn:hover { background: rgba(var(--ef-accent-rgb, 37, 99, 235), .14); }
    .obat-alt-btn .obat-alt-hint { font-weight: 500; color: var(--sb-muted, #8a94ad); }

    #obatAltModal .modal-content { border-radius: 14px; background: var(--sb-bg, #fff); color: var(--sb-text-strong, #1e2538); border: 1px solid var(--sb-line, #e2e8f0); }
    #obatAltModal .modal-header, #obatAltModal .modal-footer { border-color: var(--sb-line, #e2e8f0); }
    #obatAltModal .close { color: var(--sb-text-strong, #1e2538); }
    #obatAltModal .oa-sub { font-size: .8rem; color: var(--sb-muted, #8a94ad); font-weight: 500; }
    #obatAltModal .oa-tools { display: flex; align-items: center; flex-wrap: wrap; gap: 10px 16px; margin-bottom: 10px; font-size: .82rem; }
    #obatAltModal .oa-tools select { width: auto; display: inline-block; height: 32px; padding: 2px 8px; font-size: .82rem; border-radius: 8px;
        background: var(--sb-input, #f4f6fa); color: var(--sb-text-strong, #1e2538); border-color: var(--sb-line, #e2e8f0); }
    #obatAltModal .oa-tools label { margin: 0; font-weight: 600; }
    #obatAltModal .oa-scroll { max-height: 60vh; overflow: auto; border: 1px solid var(--sb-line, #e2e8f0); border-radius: 12px; }
    #obatAltModal table { margin: 0; font-size: .84rem; color: inherit; }
    #obatAltModal thead th { position: sticky; top: 0; z-index: 1; background: var(--sb-tile, #f1f5f9); color: var(--sb-muted, #8a94ad);
        font-size: .72rem; text-transform: uppercase; letter-spacing: .03em; border-bottom: 1px solid var(--sb-line, #e2e8f0); white-space: nowrap; }
    #obatAltModal td, #obatAltModal th { border-top: 1px solid var(--sb-line, #e2e8f0); vertical-align: middle; padding: 8px 10px; }
    #obatAltModal tr.oa-ref td { background: rgba(var(--ef-accent-rgb, 37, 99, 235), .07); }
    #obatAltModal .oa-nama { font-weight: 700; }
    #obatAltModal .oa-dosis { font-size: .75rem; color: var(--sb-muted, #8a94ad); }
    #obatAltModal .oa-zat { display: inline-block; margin: 1px 3px 1px 0; padding: 1px 7px; border-radius: 6px; font-size: .72rem;
        background: var(--sb-tile, #eef1f6); color: var(--sb-muted, #8a94ad); }
    #obatAltModal .oa-zat.is-sama { background: rgba(16, 185, 129, .14); color: #059669; font-weight: 700; }
    #obatAltModal .oa-badge { display: inline-block; padding: 2px 8px; border-radius: 6px; font-size: .7rem; font-weight: 800; letter-spacing: .03em; }
    #obatAltModal .oa-generik { background: rgba(16, 185, 129, .14); color: #059669; }
    #obatAltModal .oa-paten { background: rgba(124, 58, 237, .12); color: #7c3aed; }
    #obatAltModal .oa-persis { background: rgba(16, 185, 129, .14); color: #059669; margin-left: 4px; }
    #obatAltModal .oa-selisih { display: block; font-size: .72rem; font-weight: 600; }
    #obatAltModal .oa-lebih-murah { color: #059669; }
    #obatAltModal .oa-lebih-mahal { color: #dc2626; }
    #obatAltModal .oa-stok-ok { color: #059669; font-weight: 700; }
    #obatAltModal .oa-stok-empty { color: #dc2626; font-weight: 700; }
    #obatAltModal .oa-empty { padding: 24px; text-align: center; color: var(--sb-muted, #8a94ad); }
    html.theme-dark #obatAltModal .oa-zat.is-sama, html.theme-dark #obatAltModal .oa-generik,
    html.theme-dark #obatAltModal .oa-persis, html.theme-dark #obatAltModal .oa-lebih-murah, html.theme-dark #obatAltModal .oa-stok-ok { color: #34d399; }
    html.theme-dark #obatAltModal .oa-paten { color: #c4b5fd; }
    html.theme-dark #obatAltModal .oa-lebih-mahal, html.theme-dark #obatAltModal .oa-stok-empty { color: #f87171; }
</style>
<script>
window.ObatAlternatif = (function () {
    const url = @json(route('obat.alternatif'));
    const cache = {};
    let modalState = null; // { $select, data, opts }

    function esc(value) {
        return $('<div>').text(value == null ? '' : String(value)).html();
    }
    function rupiah(value) {
        const n = Number(value);
        return Number.isFinite(n) && n > 0 ? 'Rp ' + new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(n) : '-';
    }
    function stokOf(item) {
        return parseInt(item.stok_gudang !== undefined ? item.stok_gudang : item.stok, 10) || 0;
    }

    // The button goes right under the row that holds the obat field
    function holderOf($select) {
        return $select.closest('.eresep-add-row, .racikan-add-row, .add-obat-row, .eresep-subcard .row').first();
    }

    function removePanel($select) {
        const panel = $select.data('obatAltPanel');
        if (panel) panel.remove();
        $select.removeData('obatAltPanel');
    }

    function renderButton($select, data, opts) {
        removePanel($select);
        const items = data.results || [];
        if (!items.length) return;
        const holder = holderOf($select);
        if (!holder.length) return;

        const withStok = items.filter(item => stokOf(item) > 0).length;
        const panel = $(`<div class="obat-alt">
            <button type="button" class="obat-alt-btn">
                <i class="fas fa-exchange-alt"></i>${items.length} alternatif kandungan sama
                <span class="obat-alt-hint">· ${withStok} ada stok</span>
            </button>
        </div>`);
        panel.on('click', '.obat-alt-btn', function () {
            openModal($select, data, opts);
        });
        holder.after(panel);
        $select.data('obatAltPanel', panel);
    }

    function ensureModal() {
        if ($('#obatAltModal').length) return;
        $('body').append(`
            <div class="modal fade" id="obatAltModal" tabindex="-1" role="dialog" aria-labelledby="obatAltModalTitle" aria-hidden="true">
              <div class="modal-dialog modal-xl" role="document">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="obatAltModalTitle">Alternatif kandungan sama<div class="oa-sub"></div></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
                  </div>
                  <div class="modal-body">
                    <div class="oa-tools">
                      <label><input type="checkbox" class="oa-only-stok"> Hanya yang ada stok</label>
                      <label>Jenis
                        <select class="form-control oa-jenis">
                          <option value="">Semua</option><option value="generik">Generik</option><option value="paten">Paten</option>
                        </select>
                      </label>
                      <label>Urutkan
                        <select class="form-control oa-sort">
                          <option value="cocok">Kandungan paling cocok</option>
                          <option value="murah">Harga termurah</option>
                          <option value="stok">Stok terbanyak</option>
                        </select>
                      </label>
                    </div>
                    <div class="oa-scroll">
                      <table class="table">
                        <thead><tr><th>Obat</th><th>Kandungan</th><th>Jenis</th><th class="text-right">Harga Jual</th><th class="text-center">Stok</th><th></th></tr></thead>
                        <tbody></tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>
            </div>`);

        $('#obatAltModal').on('change', '.oa-only-stok, .oa-jenis, .oa-sort', renderTable);
        $('#obatAltModal').on('click', '.oa-pick', function () {
            if (!modalState) return;
            const item = modalState.data.results.find(r => String(r.id) === String($(this).data('id')));
            if (!item) return;
            const { $select, opts } = modalState;
            $('#obatAltModal').modal('hide');
            pick($select, item, opts);
        });
    }

    function rowHtml(item, ref, isRef) {
        const stok = stokOf(item);
        const harga = Number(item.harga_nonfornas) || 0;
        const refHarga = Number(ref.harga_nonfornas) || 0;
        let selisih = '';
        if (!isRef && harga > 0 && refHarga > 0 && harga !== refHarga) {
            const diff = harga - refHarga;
            selisih = `<span class="oa-selisih ${diff < 0 ? 'oa-lebih-murah' : 'oa-lebih-mahal'}">${diff < 0 ? '▼' : '▲'} ${rupiah(Math.abs(diff))} ${diff < 0 ? 'lebih murah' : 'lebih mahal'}</span>`;
        }
        const zat = (item.kandungan || []).map(z => `<span class="oa-zat${isRef || z.sama ? ' is-sama' : ''}">${esc(z.nama)}</span>`).join('') || '-';
        return `<tr class="${isRef ? 'oa-ref' : ''}">
            <td><div class="oa-nama">${esc(item.nama)}${item.kandungan_persis ? '<span class="oa-badge oa-persis">Persis</span>' : ''}</div>
                <div class="oa-dosis">${isRef ? 'Obat dipilih · ' : ''}${esc([item.dosis, item.satuan].filter(Boolean).join(' '))}</div></td>
            <td>${zat}</td>
            <td><span class="oa-badge ${item.is_generik ? 'oa-generik' : 'oa-paten'}">${item.is_generik ? 'GENERIK' : 'PATEN'}</span></td>
            <td class="text-right text-nowrap">${rupiah(harga)}${selisih}</td>
            <td class="text-center ${stok > 0 ? 'oa-stok-ok' : 'oa-stok-empty'}">${stok > 0 ? stok : 'Kosong'}</td>
            <td class="text-right">${isRef ? '' : `<button type="button" class="btn btn-sm btn-primary oa-pick" data-id="${esc(item.id)}">Pilih</button>`}</td>
        </tr>`;
    }

    function renderTable() {
        if (!modalState) return;
        const modal = $('#obatAltModal');
        const ref = modalState.data.obat || {};
        let items = modalState.data.results.slice();
        if (modal.find('.oa-only-stok').is(':checked')) items = items.filter(item => stokOf(item) > 0);
        const jenis = modal.find('.oa-jenis').val();
        if (jenis) items = items.filter(item => (jenis === 'generik') === !!item.is_generik);
        const sort = modal.find('.oa-sort').val();
        if (sort === 'murah') items.sort((a, b) => (Number(a.harga_nonfornas) || Infinity) - (Number(b.harga_nonfornas) || Infinity));
        if (sort === 'stok') items.sort((a, b) => stokOf(b) - stokOf(a));

        const rows = items.map(item => rowHtml(item, ref, false)).join('')
            || '<tr><td colspan="6" class="oa-empty">Tidak ada alternatif yang cocok dengan filter.</td></tr>';
        modal.find('tbody').html(rowHtml(ref, ref, true) + rows);
    }

    function openModal($select, data, opts) {
        ensureModal();
        modalState = { $select, data, opts };
        const modal = $('#obatAltModal');
        modal.find('.oa-sub').text(`${data.results.length} obat yang punya minimal satu kandungan sama dengan ${data.obat ? data.obat.nama : 'obat dipilih'}. Hijau = kandungan yang sama.`);
        modal.find('.oa-only-stok').prop('checked', false);
        modal.find('.oa-jenis').val('');
        modal.find('.oa-sort').val('cocok');
        renderTable();
        modal.modal('show');
    }

    // Replace the field's selection with the alternative, as if it had been picked from the dropdown
    function pick($select, item, opts) {
        const data = Object.assign({}, opts.mapItem ? opts.mapItem(item) : item, { selected: true });
        const option = new Option(data.text, data.id, true, true);
        $(option).data('data', data);
        $select.find('option').not('[value=""]').remove();
        $select.append(option).trigger('change');
        $select.trigger({ type: 'select2:select', params: { data: data } });
    }

    function load($select, obatId, opts) {
        const params = Object.assign({ obat_id: obatId }, opts.params ? opts.params($select) : {});
        const key = JSON.stringify(params);
        removePanel($select);
        if (cache[key]) {
            renderButton($select, cache[key], opts);
            return;
        }
        $.get(url, params).done(function (res) {
            cache[key] = { obat: res && res.obat, results: (res && res.results) || [] };
            // still the same obat selected?
            if (String($select.val()) === String(obatId)) renderButton($select, cache[key], opts);
        });
    }

    function bind(selector, opts) {
        opts = opts || {};
        $(document).on('select2:select', selector, function (e) {
            const id = e.params && e.params.data ? e.params.data.id : $(this).val();
            if (id) load($(this), id, opts);
        });
        // cleared after "Tambah" or by the user
        $(document).on('change select2:clear', selector, function () {
            if (!$(this).val()) removePanel($(this));
        });
    }

    return { bind: bind };
})();
</script>
@endonce
