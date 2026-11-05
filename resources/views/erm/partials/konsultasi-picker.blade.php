{{-- "Pilih Biaya Konsultasi" popup (asesmen dokter & CPPT): clickable cards with the harga on the right instead of
     a native select where long names were cut off. window.belovaPilihKonsultasi(items, selectedId) -> Promise<id|null>,
     items = [{id, nama, harga}]. opts.tambahan = [{id, nama, harga}] radiologi the dokter may tick under the konsultasi
     (e.g. + USG), opts.tambahanSelected = ids already ticked; the ticked ids are put in opts.tambahanChosen. --}}
<style>
.kp-popup { width: 560px !important; max-width: 95vw; }
.kp-prefix { font-size: 12px; opacity: .7; margin: -4px 0 10px; }
.kp-search { margin-bottom: 10px; }
.kp-list { max-height: 55vh; overflow-y: auto; text-align: left; display: flex; flex-direction: column; gap: 6px; padding: 2px; }
.kp-item { display: flex; align-items: center; gap: 10px; width: 100%; padding: 10px 12px; border: 1px solid rgba(128,128,128,.35); border-radius: 10px; background: transparent; color: inherit; text-align: left; cursor: pointer; transition: border-color .12s, background-color .12s; }
.kp-item:hover { border-color: #1e88e5; background: rgba(30,136,229,.06); }
.kp-item.active { border-color: #1e88e5; background: rgba(30,136,229,.12); box-shadow: 0 0 0 1px #1e88e5 inset; }
.kp-radio { flex: none; width: 18px; height: 18px; border-radius: 50%; border: 2px solid rgba(128,128,128,.6); position: relative; }
.kp-item.active .kp-radio { border-color: #1e88e5; }
.kp-item.active .kp-radio::after { content: ''; position: absolute; inset: 3px; border-radius: 50%; background: #1e88e5; }
.kp-name { flex: 1; min-width: 0; font-size: 14px; line-height: 1.3; }
.kp-price { flex: none; font-weight: 600; font-size: 14px; white-space: nowrap; }
.kp-price.free { color: #16a34a; }
.kp-empty { padding: 16px; text-align: center; opacity: .7; }
.kp-tambahan { margin-top: 12px; text-align: left; }
.kp-tambahan-title { font-size: 12px; font-weight: 600; opacity: .75; margin-bottom: 6px; text-transform: uppercase; letter-spacing: .03em; }
.kp-check { flex: none; width: 18px; height: 18px; border-radius: 4px; border: 2px solid rgba(128,128,128,.6); position: relative; }
.kp-add.active .kp-check { border-color: #1e88e5; background: #1e88e5; }
.kp-add.active .kp-check::after { content: ''; position: absolute; left: 4px; top: 0; width: 6px; height: 11px; border: solid #fff; border-width: 0 2px 2px 0; transform: rotate(45deg); }
.kp-total { display: flex; justify-content: space-between; margin-top: 10px; padding: 8px 12px; border-top: 1px solid rgba(128,128,128,.35); font-weight: 600; }
.kp-note { margin-top: 12px; padding: 8px 10px; border-radius: 8px; background: rgba(23,162,184,.12); color: inherit; font-size: 12.5px; text-align: left; line-height: 1.45; }
</style>
<script>
// opts.note: optional text shown under the list (e.g. CPPT: what "Lanjut simpan" also does)
window.belovaPilihKonsultasi = function (items, selectedId, opts) {
    opts = opts || {};
    var esc = function (s) { return $('<div>').text(s == null ? '' : String(s)).html(); };
    var rupiah = function (n) { return 'Rp ' + Math.round(Number(n) || 0).toLocaleString('id-ID'); };

    // Words every name starts with (e.g. "Jasa Klinik dan Konsultasi Dokter") are shown once above the list
    var words = items.map(function (it) { return String(it.nama).trim().split(/\s+/); });
    var prefix = [];
    if (items.length > 1) {
        for (var i = 0; i < words[0].length - 1; i++) {
            var w = words[0][i];
            if (words.every(function (ws) { return ws.length > i + 1 && ws[i].toLowerCase() === w.toLowerCase(); })) prefix.push(w); else break;
        }
    }
    if (prefix.length < 2) prefix = [];
    var shortName = function (it) {
        var ws = String(it.nama).trim().split(/\s+/);
        var rest = ws.slice(prefix.length).join(' ');
        return rest ? rest.charAt(0).toUpperCase() + rest.slice(1) : it.nama;
    };

    var tambahan = opts.tambahan || [];
    var chosenTambahan = (opts.tambahanSelected || []).map(String);
    var hargaOf = function (list, id) { var it = list.filter(function (x) { return String(x.id) === String(id); })[0]; return it ? Number(it.harga) || 0 : 0; };

    var current = String(selectedId || '');
    if (!items.some(function (it) { return String(it.id) === current; })) current = items.length ? String(items[0].id) : '';

    var html = (prefix.length ? '<div class="kp-prefix">' + esc(prefix.join(' ')) + '</div>' : '')
        + (items.length > 6 ? '<input type="search" class="form-control kp-search" placeholder="Cari jenis konsultasi..." autocomplete="off">' : '')
        + '<div class="kp-list" role="radiogroup">'
        + items.map(function (it) {
            var free = Number(it.harga) <= 0;
            return '<button type="button" class="kp-item' + (String(it.id) === current ? ' active' : '') + '" role="radio" aria-checked="' + (String(it.id) === current) + '"'
                + ' data-id="' + esc(it.id) + '" data-search="' + esc(String(it.nama).toLowerCase()) + '" title="' + esc(it.nama) + '">'
                + '<span class="kp-radio"></span><span class="kp-name">' + esc(shortName(it)) + '</span>'
                + '<span class="kp-price' + (free ? ' free' : '') + '">' + (free ? 'Gratis' : rupiah(it.harga)) + '</span></button>';
        }).join('')
        + '<div class="kp-empty" style="display:none">Tidak ada yang cocok.</div></div>'
        + (tambahan.length
            ? '<div class="kp-tambahan"><div class="kp-tambahan-title">Tambahan radiologi (opsional)</div><div class="kp-list" style="max-height:none">'
                + tambahan.map(function (it) {
                    var on = chosenTambahan.indexOf(String(it.id)) !== -1;
                    return '<button type="button" class="kp-item kp-add' + (on ? ' active' : '') + '" role="checkbox" aria-checked="' + on + '" data-id="' + esc(it.id) + '">'
                        + '<span class="kp-check"></span><span class="kp-name">' + esc(it.nama) + '</span>'
                        + '<span class="kp-price">' + rupiah(it.harga) + '</span></button>';
                }).join('')
                + '</div></div><div class="kp-total"><span>Total</span><span class="kp-total-value"></span></div>'
            : '')
        + (opts.note ? '<div class="kp-note"><i class="fas fa-info-circle mr-1"></i>' + esc(opts.note) + '</div>' : '');

    var options = {
        title: 'Pilih Biaya Konsultasi',
        html: html,
        customClass: { popup: 'kp-popup' },
        showCancelButton: true,
        confirmButtonText: 'Lanjut simpan',
        cancelButtonText: 'Batal',
        reverseButtons: true,
        focusConfirm: false,
        preConfirm: function () {
            if (!current) {
                Swal.showValidationMessage('Pilih biaya konsultasi terlebih dahulu.');
                return false;
            }
            opts.tambahanChosen = chosenTambahan.slice();
            return current;
        }
    };
    // The bundled SweetAlert2 (9.5.3) calls it onOpen; v10+ renamed it didOpen. Without it the cards can't be clicked.
    var openHook = (Swal.isValidParameter && Swal.isValidParameter('didOpen')) ? 'didOpen' : 'onOpen';
    options[openHook] = function (popup) {
        var $p = $(popup);
        var total = function () {
            var sum = hargaOf(items, current) + chosenTambahan.reduce(function (acc, id) { return acc + hargaOf(tambahan, id); }, 0);
            $p.find('.kp-total-value').text(rupiah(sum));
        };
        var select = function ($btn) {
            current = String($btn.data('id'));
            $p.find('.kp-item:not(.kp-add)').removeClass('active').attr('aria-checked', 'false');
            $btn.addClass('active').attr('aria-checked', 'true');
            Swal.resetValidationMessage();
            total();
        };
        $p.on('click', '.kp-item:not(.kp-add)', function () { select($(this)); });
        // Double-click = choose and continue
        $p.on('dblclick', '.kp-item:not(.kp-add)', function () { select($(this)); Swal.clickConfirm(); });
        // Tambahan radiologi: tick / untick
        $p.on('click', '.kp-add', function () {
            var id = String($(this).data('id'));
            var on = chosenTambahan.indexOf(id) === -1;
            chosenTambahan = on ? chosenTambahan.concat([id]) : chosenTambahan.filter(function (x) { return x !== id; });
            $(this).toggleClass('active', on).attr('aria-checked', String(on));
            total();
        });
        total();
        $p.on('input', '.kp-search', function () {
            var q = $(this).val().trim().toLowerCase();
            var any = false;
            $p.find('.kp-item:not(.kp-add)').each(function () {
                var show = !q || String($(this).data('search')).indexOf(q) !== -1;
                $(this).toggle(show);
                any = any || show;
            });
            $p.find('.kp-empty').toggle(!any);
        });
        var $active = $p.find('.kp-item.active:not(.kp-add)');
        if ($active.length) $active[0].scrollIntoView({ block: 'nearest' });
        ($p.find('.kp-search')[0] || $active[0] || popup).focus();
    };

    return Swal.fire(options)
        .then(function (result) { return result.value ? String(result.value) : null; }); // 9.5.3 has no isConfirmed
};
</script>
