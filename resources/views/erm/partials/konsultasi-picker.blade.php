{{-- "Pilih Biaya Konsultasi" popup (asesmen dokter & CPPT): clickable cards with the harga on the right instead of
     a native select where long names were cut off. window.belovaPilihKonsultasi(items, selectedId) -> Promise<id|null>,
     items = [{id, nama, harga}]. --}}
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
            return current;
        }
    };
    // The bundled SweetAlert2 (9.5.3) calls it onOpen; v10+ renamed it didOpen. Without it the cards can't be clicked.
    var openHook = (Swal.isValidParameter && Swal.isValidParameter('didOpen')) ? 'didOpen' : 'onOpen';
    options[openHook] = function (popup) {
        var $p = $(popup);
        var select = function ($btn) {
            current = String($btn.data('id'));
            $p.find('.kp-item').removeClass('active').attr('aria-checked', 'false');
            $btn.addClass('active').attr('aria-checked', 'true');
            Swal.resetValidationMessage();
        };
        $p.on('click', '.kp-item', function () { select($(this)); });
        // Double-click = choose and continue
        $p.on('dblclick', '.kp-item', function () { select($(this)); Swal.clickConfirm(); });
        $p.on('input', '.kp-search', function () {
            var q = $(this).val().trim().toLowerCase();
            var any = false;
            $p.find('.kp-item').each(function () {
                var show = !q || String($(this).data('search')).indexOf(q) !== -1;
                $(this).toggle(show);
                any = any || show;
            });
            $p.find('.kp-empty').toggle(!any);
        });
        var $active = $p.find('.kp-item.active');
        if ($active.length) $active[0].scrollIntoView({ block: 'nearest' });
        ($p.find('.kp-search')[0] || $active[0] || popup).focus();
    };

    return Swal.fire(options)
        .then(function (result) { return result.value ? String(result.value) : null; }); // 9.5.3 has no isConfirmed
};
</script>
