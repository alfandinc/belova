{{--
    Aturan Pakai: free text with recommendations from resep history ("sering dipakai"), no master needed.
    On focus it lists what is most used for the chosen obat (or the user's racikan); typing filters
    those plus the most used texts across all resep. Pick with click or ↑ ↓ Enter, close with Esc.
    The input itself keeps the value, so existing code that reads, fills, disables or requires it keeps working.
        AturanPakai.mount('#aturan_pakai');                       // then .setObat(id), .reset(), .setValue(text)
        AturanPakai.autoMount('#racikan-container', { racikan: true });
--}}
<style>
    .ap-ac { position: relative; }
    .ap-ac-menu { display: none; position: absolute; top: 100%; left: 0; right: 0; z-index: 1065; margin-top: 2px;
        max-height: 300px; overflow-y: auto; background: #fff; border: 1px solid #ced4da; border-radius: 4px; box-shadow: 0 6px 16px rgba(0,0,0,.12); }
    .ap-ac-menu.show { display: block; }
    .ap-ac-head { padding: 6px 10px 2px; font-size: .7rem; text-transform: uppercase; letter-spacing: .03em; color: #6c757d; }
    .ap-ac-item { display: flex; justify-content: space-between; gap: 8px; padding: 5px 10px; font-size: .86rem; cursor: pointer; }
    .ap-ac-item:hover, .ap-ac-item.active { background: #e7f1ff; }
    .ap-ac-item .ap-ac-count { color: #6c757d; font-size: .75rem; white-space: nowrap; }
    .ap-ac-item mark { padding: 0; background: #fff3cd; }
</style>
<script>
(function ($, window) {
    'use strict';
    if (window.AturanPakai) return;

    const SUGGEST_URL = @json(route('erm.aturan-pakai.list.suggest'));
    const cache = {};

    function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; });
    }

    // Mark the typed words inside a suggestion
    function highlight(text, q) {
        const safe = escapeHtml(text);
        const words = String(q || '').trim().split(/\s+/).filter(function (w) { return w.length >= 2; })
            .map(function (w) { return escapeHtml(w).replace(/[.*+?^${}()|[\]\\]/g, '\\$&'); });
        return words.length ? safe.replace(new RegExp('(' + words.join('|') + ')', 'gi'), '<mark>$1</mark>') : safe;
    }

    function Field($input, opts) {
        const self = this;
        this.$input = $input.attr('autocomplete', 'off');
        this.opts = $.extend({ racikan: false }, opts || {});
        this.obatId = null;
        this.items = [];
        this.active = -1;
        this.requestKey = null;
        this.timer = null;

        $input.wrap('<div class="ap-ac"></div>');
        this.$menu = $('<div class="ap-ac-menu" role="listbox"></div>').insertAfter($input);

        $input.on('focus', function () { self.load(); });
        $input.on('input', function () {
            clearTimeout(self.timer);
            self.timer = setTimeout(function () { self.load(); }, 200);
        });
        $input.on('keydown', function (e) { self.onKey(e); });
        $input.on('blur', function () { setTimeout(function () { self.close(); }, 150); });
        // mousedown (not click) so the input keeps focus while picking
        this.$menu.on('mousedown', '.ap-ac-item', function (e) {
            e.preventDefault();
            self.pick(parseInt($(this).attr('data-index'), 10));
        });
    }

    Field.prototype.params = function () {
        const p = { q: (this.$input.val() || '').trim() };
        if (this.opts.racikan) p.racikan = 1;
        else if (this.obatId) p.obat_id = this.obatId;
        return p;
    };

    Field.prototype.load = function () {
        if (this.$input.prop('disabled') || this.$input.prop('readonly')) return;
        const self = this;
        const params = this.params();
        const key = JSON.stringify(params);
        this.requestKey = key;
        if (!cache[key]) {
            cache[key] = $.get(SUGGEST_URL, params).then(function (r) { return r || {}; }, function () { delete cache[key]; return {}; });
        }
        cache[key].then(function (res) {
            // Ignore answers for text the user already changed, or when the field lost focus
            if (self.requestKey !== key || document.activeElement !== self.$input[0]) return;
            self.render(res, params.q);
        });
    };

    Field.prototype.render = function (res, q) {
        const specificLabel = this.opts.racikan ? 'Racikan yang sering Anda tulis' : 'Sering dipakai untuk obat ini';
        const sections = [[specificLabel, res.obat || []], ['Sering dipakai', res.umum || []]];
        const $menu = this.$menu.empty();
        const typed = String(q || '').trim().toLowerCase();
        this.items = [];
        this.active = -1;
        sections.forEach(function (sec) {
            // Leave out a suggestion that is exactly what is already typed
            const list = sec[1].filter(function (it) { return it.text.toLowerCase() !== typed; });
            if (!list.length) return;
            $menu.append($('<div class="ap-ac-head"></div>').text(sec[0]));
            list.forEach(function (it) {
                const index = this.items.push(it.text) - 1;
                $menu.append('<div class="ap-ac-item" role="option" data-index="' + index + '"><span>' + highlight(it.text, q) +
                    '</span><span class="ap-ac-count">' + parseInt(it.count, 10) + 'x</span></div>');
            }, this);
        }, this);
        $menu.toggleClass('show', this.items.length > 0);
    };

    Field.prototype.onKey = function (e) {
        const open = this.$menu.hasClass('show');
        if (e.key === 'Escape' && open) { e.preventDefault(); e.stopPropagation(); this.close(); return; }
        if (!open || !this.items.length) {
            if (e.key === 'ArrowDown') { e.preventDefault(); this.load(); }
            return;
        }
        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            e.preventDefault();
            const n = this.items.length;
            this.active = e.key === 'ArrowDown' ? (this.active + 1) % n : (this.active - 1 + n) % n;
            const $items = this.$menu.find('.ap-ac-item').removeClass('active');
            const $cur = $items.eq(this.active).addClass('active');
            if ($cur.length && $cur[0].scrollIntoView) $cur[0].scrollIntoView({ block: 'nearest' });
        } else if (e.key === 'Enter' && this.active >= 0) {
            e.preventDefault();
            e.stopPropagation();
            this.pick(this.active);
        }
    };

    Field.prototype.pick = function (index) {
        const text = this.items[index];
        if (text === undefined) return;
        this.$input.val(text).trigger('change');
        this.close();
    };

    Field.prototype.close = function () {
        this.$menu.removeClass('show').empty();
        this.items = [];
        this.active = -1;
    };

    // Methods the resep page calls
    Field.prototype.setObat = function (obatId) {
        this.obatId = obatId || null;
        if (document.activeElement === this.$input[0]) this.load();
    };
    Field.prototype.setValue = function (text) { this.$input.val(text || '').trigger('change'); };
    Field.prototype.reset = function () { this.obatId = null; this.close(); this.setValue(''); };

    window.AturanPakai = {
        mount: function (input, opts) {
            const $input = $(input).first();
            if (!$input.length) return null;
            if (!$input.data('apField')) $input.data('apField', new Field($input, opts));
            return $input.data('apField');
        },
        get: function (input) {
            return $(input).first().data('apField') || null;
        },
        // Mount on every .aturan_pakai input inside the container, now and whenever cards are added
        autoMount: function (container, opts) {
            const $c = $(container);
            if (!$c.length) return;
            const run = function () {
                $c.find('input.aturan_pakai').each(function () { window.AturanPakai.mount(this, opts); });
            };
            run();
            if (window.MutationObserver) {
                new MutationObserver(function (mutations) {
                    if (mutations.some(function (m) { return m.addedNodes.length; })) run();
                }).observe($c[0], { childList: true, subtree: true });
            }
        }
    };
})(jQuery, window);
</script>
