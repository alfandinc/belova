{{--
    E-Resep (dokter + farmasi): stok of an obat in the gudangs other than the resep gudang.
    The stok cells show the resep gudang only (that is where payment takes the stok from), so an obat can look
    "habis" while the gudang utama still has it. This partial adds "+N gudang lain" under such a cell and
    gives the pages the same wording for the obat dropdown. Expired batches in the other gudangs are shown too,
    as a warning ("⚠ +N ED"): they are there, but must not be counted on.

    Works on every <tr data-obat-id> inside .eresep-page whose stok cell has .stok-low / .stok-mid / .stok-ok
    (not .stok-dikurangi). The text is put in a data attribute and drawn with ::after, so the cell text the
    pages read back (stok tersedia) is untouched. Rows added later are picked up by a MutationObserver.

    window.StokLain.optionText(stok, lain, ed)  -> " · Stok 0 (+50 gudang lain, ⚠ +100 ED)" for a Select2 option
    window.StokLain.refresh()                   -> re-read the rows now
--}}
@once
<style>
    .eresep-page { --er-lain: #1d4ed8; --er-lain-ed: #b45309; }
    html.theme-dark .eresep-page { --er-lain: #93c5fd; --er-lain-ed: #fbbf24; }
    .eresep-page td[data-stok-lain]::after {
        content: attr(data-stok-lain);
        display: block; margin-top: 1px; font-size: .72rem; font-weight: 700; line-height: 1.2;
        white-space: nowrap; color: var(--er-lain); cursor: help;
    }
    /* Only expired stok elsewhere: nothing usable to transfer */
    .eresep-page td[data-stok-lain][data-stok-lain-ed="only"]::after { color: var(--er-lain-ed); }
    .stok-lain-opt { font-weight: 700; }
    .stok-lain-opt-ed { font-weight: 700; color: #b45309; }
    .select2-results__option--highlighted .stok-lain-opt-ed { color: inherit; }
    html.theme-dark .stok-lain-opt-ed { color: #fbbf24; }
</style>
<script>
    window.StokLain = (function () {
        const ENDPOINT = @json(route('obat.stok-gudang-lain'));
        const MAX_AGE = 60000;          // re-ask the server for an obat after a minute
        const cache = {};               // obat_id -> { at, info: { total, ed, gudang: [] } | null }
        let timer = null;
        let loading = false;

        function fmt(n) {
            return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 }).format(Number(n) || 0);
        }

        function stokCell($tr) {
            return $tr.children('td.stok-low, td.stok-mid, td.stok-ok').not('.stok-dikurangi').first();
        }

        function rows() {
            return $('.eresep-page tr[data-obat-id]');
        }

        // "+50 gudang lain", "+50 gudang lain · ⚠ +100 ED" or "⚠ +100 ED gudang lain"
        function label(lain, ed) {
            if (lain > 0) return '+' + fmt(lain) + ' gudang lain' + (ed > 0 ? ' · ⚠ +' + fmt(ed) + ' ED' : '');
            return ed > 0 ? '⚠ +' + fmt(ed) + ' ED gudang lain' : '';
        }

        function apply() {
            rows().each(function () {
                const entry = cache[$(this).attr('data-obat-id')];
                if (!entry) return;
                const td = stokCell($(this))[0];
                if (!td) return;
                const info = entry.info;
                const lain = info ? Number(info.total) || 0 : 0;
                const ed = info ? Number(info.ed) || 0 : 0;
                if (lain > 0 || ed > 0) {
                    const text = label(lain, ed);
                    const title = 'Stok di gudang lain (belum di gudang resep, perlu transfer): '
                        + info.gudang.map(g => g.nama + ' ' + fmt(g.stok) + (g.stok_ed > 0 ? ' + ' + fmt(g.stok_ed) + ' sudah ED' : '')).join(', ')
                        + (ed > 0 ? '. Batch yang sudah ED tidak boleh diandalkan untuk resep.' : '');
                    const edFlag = lain > 0 ? (ed > 0 ? 'some' : '') : 'only';
                    if (td.getAttribute('data-stok-lain') !== text) td.setAttribute('data-stok-lain', text);
                    if ((td.getAttribute('data-stok-lain-ed') || '') !== edFlag) td.setAttribute('data-stok-lain-ed', edFlag);
                    if (td.getAttribute('title') !== title) td.setAttribute('title', title);
                } else if (td.hasAttribute('data-stok-lain')) {
                    td.removeAttribute('data-stok-lain');
                    td.removeAttribute('data-stok-lain-ed');
                    td.removeAttribute('title');
                }
            });
        }

        function refresh() {
            apply();
            if (loading) return schedule();
            const now = Date.now();
            const ids = [];
            rows().each(function () {
                const id = $(this).attr('data-obat-id');
                if (!id || ids.indexOf(id) !== -1 || !stokCell($(this)).length) return;
                if (!cache[id] || now - cache[id].at > MAX_AGE) ids.push(id);
            });
            if (!ids.length) return;

            loading = true;
            $.ajax({ url: ENDPOINT, data: { ids: ids }, global: false })
                .done(function (res) {
                    const data = (res && res.data) || {};
                    const at = Date.now();
                    ids.forEach(function (id) { cache[id] = { at: at, info: data[id] || null }; });
                    apply();
                })
                .always(function () { loading = false; });
        }

        function schedule() {
            clearTimeout(timer);
            timer = setTimeout(refresh, 200);
        }

        $(function () {
            refresh();
            const area = document.querySelector('.eresep-page');
            if (area && window.MutationObserver) {
                new MutationObserver(schedule).observe(area, { childList: true, subtree: true });
            }
        });

        return {
            refresh: refresh,
            format: fmt,
            // Dropdown wording: the resep gudang stok, plus what the other gudangs still hold (expired = warning)
            optionText: function (stok, lain, ed) {
                stok = parseInt(stok, 10) || 0;
                lain = Number(lain) || 0;
                ed = Number(ed) || 0;
                const parts = [];
                if (lain > 0) parts.push('<span class="stok-lain-opt">+' + fmt(lain) + ' gudang lain</span>');
                if (ed > 0) parts.push('<span class="stok-lain-opt-ed">⚠ +' + fmt(ed) + ' ED' + (lain > 0 ? '' : ' gudang lain') + '</span>');
                return ' · Stok ' + stok + (parts.length ? ' (' + parts.join(', ') + ')' : '');
            }
        };
    })();
</script>
@endonce
