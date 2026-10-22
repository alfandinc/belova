{{-- Shared helpers of the marketing workflow pages: escaping, status pills, Indonesian dates,
     the "Riwayat" timeline and the note dialog used by every workflow button. --}}
<script>
// A Bootstrap 4 modal pulls keyboard focus back into itself on every focusin, so the note field of a
// SweetAlert dialog (or a Select2 search box) opened on top of it could not be typed in.
// Let focus stay in those overlays; everything else keeps the normal modal focus trap.
(function () {
    if (!$.fn.modal || !$.fn.modal.Constructor || $.fn.modal.Constructor.prototype._wfFocusPatched) return;
    var proto = $.fn.modal.Constructor.prototype;
    proto._enforceFocus = function () {
        var self = this;
        $(document).off('focusin.bs.modal').on('focusin.bs.modal', function (e) {
            if ($(e.target).closest('.swal2-container, .select2-container').length) return;
            if (document !== e.target && self._element !== e.target && $(self._element).has(e.target).length === 0) {
                self._element.focus();
            }
        });
    };
    proto._wfFocusPatched = true;
})();

window.MkWf = (function () {
    var HARI = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    var BULAN = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    var BULAN_PANJANG = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

    // status value -> color family of the pill
    var PILL = {
        plan: { Draft: 'gray', Scheduled: 'gray', Produksi: 'blue', Review: 'amber', Revisi: 'red', Done: 'indigo', Published: 'green', Cancelled: 'muted' },
        ide: { Pending: 'amber', Approved: 'green', Rejected: 'red', Scheduled: 'indigo' },
        design: { 'Baru': 'gray', 'Dikerjakan': 'blue', 'Review': 'amber', 'Revisi': 'red', 'Selesai': 'green', 'Dibatalkan': 'muted' }
    };
    var IDE_LABEL = { Pending: 'Menunggu Approval', Approved: 'Approved', Rejected: 'Rejected', Scheduled: 'Sudah Dijadwalkan' };

    function esc(s) {
        return String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function pill(kind, status, label) {
        var color = (PILL[kind] || {})[status] || 'gray';
        if (kind === 'ide' && !label) label = IDE_LABEL[status] || status;
        return '<span class="wf-pill st-' + color + '">' + esc(label || status || '-') + '</span>';
    }

    function m(value) { return value ? moment(value) : null; }

    /** "Sen, 20 Okt" / with year / with time */
    function date(value, opts) {
        var d = m(value); if (!d || !d.isValid()) return '-';
        opts = opts || {};
        var out = (opts.weekday === false ? '' : HARI[d.day()].slice(0, 3) + ', ') + d.date() + ' ' + BULAN[d.month()];
        if (opts.year || d.year() !== moment().year()) out += ' ' + d.year();
        if (opts.time) out += ' ' + d.format('HH.mm');
        return out;
    }

    function dayTitle(d) { return HARI[d.day()] + ', ' + d.date() + ' ' + BULAN_PANJANG[d.month()] + ' ' + d.year(); }
    function monthTitle(d) { return BULAN_PANJANG[d.month()] + ' ' + d.year(); }

    /** Deadline in words + whether it is late / close */
    function deadline(value) {
        if (!value) return { text: 'Tanpa deadline', state: '' };
        var days = moment(value, 'YYYY-MM-DD').startOf('day').diff(moment().startOf('day'), 'days');
        if (days < 0) return { text: 'Terlambat ' + (-days) + ' hari', state: 'late' };
        if (days === 0) return { text: 'Hari ini', state: 'soon' };
        if (days === 1) return { text: 'Besok', state: 'soon' };
        return { text: days + ' hari lagi (' + date(value, { weekday: false }) + ')', state: days <= 2 ? 'soon' : '' };
    }

    function timeline(rows, actionLabels) {
        if (!rows || !rows.length) return '<div class="wf-empty">Belum ada riwayat.</div>';
        actionLabels = actionLabels || {};
        return '<ul class="wf-timeline">' + rows.map(function (r) {
            var label = r.action_label || actionLabels[r.action] || r.action;
            var change = (r.from_label || r.to_label)
                ? ' <span class="wf-muted">(' + esc(r.from_label || '-') + ' → ' + esc(r.to_label || '-') + ')</span>' : '';
            return '<li><div class="wf-tl-head"><strong>' + esc(r.user) + '</strong> · ' + esc(label) + change + '</div>'
                + '<div class="wf-meta">' + date(r.at, { time: true, year: true }) + '</div>'
                + (r.notes ? '<div class="wf-tl-note">' + esc(r.notes) + '</div>' : '')
                + '</li>';
        }).join('') + '</ul>';
    }

    /**
     * Confirm a workflow step, asking for a note when needed.
     * note: null (just confirm) | 'optional' | 'required'. Resolves to the note text, or null when cancelled.
     */
    function askNote(opts) {
        var needsInput = opts.note === 'optional' || opts.note === 'required';
        return Swal.fire({
            title: opts.title,
            html: opts.html || undefined,
            text: opts.text || undefined,
            input: needsInput ? 'textarea' : undefined,
            inputLabel: needsInput ? (opts.note === 'required' ? 'Catatan (wajib)' : 'Catatan (opsional)') : undefined,
            inputPlaceholder: opts.placeholder || (opts.note === 'required' ? 'Tulis alasan / apa yang perlu diperbaiki…' : 'Tambahkan catatan bila perlu…'),
            showCancelButton: true,
            confirmButtonText: opts.confirmText || 'Ya, lanjutkan',
            cancelButtonText: 'Batal',
            confirmButtonColor: opts.confirmColor || '#0d6efd',
            reverseButtons: true,
            focusConfirm: !needsInput,
            preConfirm: opts.preConfirm,
            didOpen: opts.didOpen,
            inputValidator: opts.note === 'required' ? function (v) { if (!v || !v.trim()) return 'Catatan wajib diisi.'; } : undefined
        }).then(function (res) {
            if (!res.isConfirmed) return null;
            return { notes: needsInput ? (res.value || '').trim() : '', value: res.value };
        });
    }

    function errorMessage(xhr, fallback) {
        var j = xhr && xhr.responseJSON;
        if (j && j.errors) {
            return Object.values(j.errors).map(function (v) { return Array.isArray(v) ? v.join('<br>') : v; }).join('<br>');
        }
        if (j && j.message) return j.message;
        if (xhr && xhr.status === 413) return 'File terlalu besar untuk server. Gunakan link Google Drive.';
        return fallback || 'Terjadi kesalahan.';
    }

    function toast(text) {
        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: text, showConfirmButton: false, timer: 2200, timerProgressBar: true });
    }

    /**
     * Embeddable player for an Instagram / TikTok / YouTube link (official embed pages, no API key).
     * Returns null for other links or short links without a post id (vt.tiktok.com/…).
     */
    function embedInfo(url) {
        url = $.trim(url || '');
        if (!url) return null;
        var m;
        if ((m = url.match(/instagram\.com\/(?:[\w.]+\/)?(p|reel|reels|tv)\/([\w-]+)/i))) {
            var kind = m[1].toLowerCase() === 'reels' ? 'reel' : m[1].toLowerCase();
            return { provider: 'Instagram', icon: 'fab fa-instagram', src: 'https://www.instagram.com/' + kind + '/' + m[2] + '/embed/', width: 400, height: 640 };
        }
        if ((m = url.match(/tiktok\.com\/.*?\/video\/(\d+)/i)) || (m = url.match(/tiktok\.com\/.*?[?&]item_id=(\d+)/i))) {
            // Font Awesome 5.13 in the app has no fa-tiktok
            return { provider: 'TikTok', icon: 'fas fa-music', src: 'https://www.tiktok.com/embed/v2/' + m[1], width: 340, height: 740 };
        }
        if ((m = url.match(/youtube\.com\/shorts\/([\w-]{6,})/i))) {
            return { provider: 'YouTube', icon: 'fab fa-youtube', src: 'https://www.youtube.com/embed/' + m[1], width: 340, height: 600 };
        }
        if ((m = url.match(/(?:youtube\.com\/(?:watch\?(?:.*&)?v=|embed\/)|youtu\.be\/)([\w-]{6,})/i))) {
            return { provider: 'YouTube', icon: 'fab fa-youtube', src: 'https://www.youtube.com/embed/' + m[1], width: 560, height: 315 };
        }
        return null;
    }

    /** Preview block under a reference-link field: embedded post when possible, otherwise a plain link. */
    function linkPreview($box, url) {
        url = $.trim(url || '');
        if ($box.data('url') === url) return;
        $box.data('url', url);
        if (!url) { $box.empty().hide(); return; }
        var href = /^https?:\/\//i.test(url) ? url : 'https://' + url;
        var info = embedInfo(url);
        var open = '<a href="' + esc(href) + '" target="_blank" rel="noopener" class="small"><i class="fas fa-external-link-alt mr-1"></i>Buka ' + (info ? 'di ' + info.provider : 'link') + '</a>';
        if (!info) {
            var shortTiktok = /(vt|vm)\.tiktok\.com/i.test(url);
            $box.html('<div class="wf-embed-head">' + open + (shortTiktok ? '<span class="wf-muted small">Link pendek TikTok tidak bisa dipreview. Pakai link lengkap (…/video/123…).</span>' : '') + '</div>').show();
            return;
        }
        $box.html('<div class="wf-embed-head"><span><i class="' + info.icon + ' mr-1"></i>Pratinjau ' + info.provider + '</span>'
            + '<button type="button" class="btn btn-sm btn-link p-0 js-embed-toggle">Sembunyikan</button>' + open + '</div>'
            + '<div class="wf-embed-frame"><iframe src="' + esc(info.src) + '" loading="lazy" allowfullscreen scrolling="no"'
            + ' allow="autoplay; encrypted-media; picture-in-picture" style="max-width:' + info.width + 'px;height:' + info.height + 'px"'
            + ' title="Pratinjau ' + info.provider + '"></iframe></div>').show();
    }
    $(document).on('click', '.js-embed-toggle', function () {
        var $frame = $(this).closest('.wf-embed').find('.wf-embed-frame').toggle();
        $(this).text($frame.is(':visible') ? 'Sembunyikan' : 'Tampilkan');
    });

    function buttonStyleColor(style) {
        return { success: '#198754', warning: '#d9480f', 'outline-danger': '#c92a2a', 'outline-warning': '#d9480f' }[style] || '#0d6efd';
    }

    return {
        esc: esc, pill: pill, date: date, dayTitle: dayTitle, monthTitle: monthTitle, deadline: deadline,
        timeline: timeline, askNote: askNote, errorMessage: errorMessage, toast: toast, buttonStyleColor: buttonStyleColor,
        embedInfo: embedInfo, linkPreview: linkPreview,
        HARI: HARI, BULAN: BULAN, BULAN_PANJANG: BULAN_PANJANG
    };
})();

/**
 * Workflow notifications (perlu approval, siap publish, revisi masuk, ditugaskan, …) while the page is open:
 * polls the status history every 10 seconds, shows a card in the corner and, when allowed, a system
 * notification (also on the phone's lock screen while the app/tab is still running). Without a WA bot or
 * push server nothing arrives when the page is fully closed.
 */
window.MkNotify = (function () {
    var opts = {}, busy = false, memSince = 0, key = '', swReg = null;
    var TONE_ICON = { warning: 'fas fa-hourglass-half', success: 'fas fa-check-circle', danger: 'fas fa-undo', info: 'fas fa-bell' };

    function readSince() { try { return parseInt(localStorage.getItem(key), 10) || 0; } catch (e) { return 0; } }
    function writeSince(v) { try { localStorage.setItem(key, String(v)); } catch (e) {} }
    function supported() { return 'Notification' in window; }

    function open(item) {
        if (opts.open && opts.open(item)) return;
        window.location.href = item.url;
    }

    function stack() {
        var $s = $('#wfNotifStack');
        if (!$s.length) $s = $('<div id="wfNotifStack" class="wf-notif-stack" aria-live="polite"></div>').appendTo('body');
        return $s;
    }

    // Every card stays (kept in this browser, also after a reload) until it is clicked or closed
    var SHOW_MAX = 4, KEEP_MAX = 30;
    // falls back to memory when browser storage is blocked (private mode)
    var memList = [], canStore = (function () { try { localStorage.setItem('mkwf-t', '1'); localStorage.removeItem('mkwf-t'); return true; } catch (e) { return false; } })();
    function listKey() { return key + '-list'; }
    function getList() {
        if (!canStore) return memList;
        try { return JSON.parse(localStorage.getItem(listKey())) || []; } catch (e) { return []; }
    }
    function saveList(list, newId) {
        list = list.slice(0, KEEP_MAX);
        memList = list;
        if (canStore) { try { localStorage.setItem(listKey(), JSON.stringify(list)); } catch (e) {} }
        render(newId);
    }

    function remove(id) {
        saveList(getList().filter(function (x) { return String(x.id) !== String(id); }));
    }

    function render(newId) {
        var list = getList();
        var $s = stack().empty();
        list.slice(0, SHOW_MAX).forEach(function (item) {
            var $n = $('<div class="wf-notif tone-' + item.tone + (String(item.id) === String(newId) ? ' is-new' : '') + '" role="button" tabindex="0">'
                + '<i class="wf-notif-ico ' + (TONE_ICON[item.tone] || TONE_ICON.info) + '"></i>'
                + '<div class="wf-notif-body"><strong>' + MkWf.esc(item.title) + '</strong><span>' + MkWf.esc(item.text) + '</span></div>'
                + '<button type="button" class="wf-notif-close" aria-label="Tutup">&times;</button></div>');
            $n.on('click', function (e) {
                remove(item.id);
                if (!$(e.target).closest('.wf-notif-close').length) open(item);
            });
            $s.append($n);
        });
        if (list.length > SHOW_MAX) {
            $('<div class="wf-notif-more"><span>+' + (list.length - SHOW_MAX) + ' notifikasi lainnya</span>'
                + '<button type="button">Tutup semua</button></div>').appendTo($s)
                .find('button').on('click', function () { saveList([]); });
        } else if (list.length > 1) {
            $('<div class="wf-notif-more"><span></span><button type="button">Tutup semua</button></div>').appendTo($s)
                .find('button').on('click', function () { saveList([]); });
        }
    }

    function showCard(item) {
        var list = getList().filter(function (x) { return String(x.id) !== String(item.id); });
        list.unshift(item);
        saveList(list, item.id);
    }

    /**
     * Konten rutin reminders ("Waktunya upload", "Belum diupload") come with every poll while they are open:
     * each id is shown once, and the card goes away by itself once the upload is recorded.
     */
    function handleReminders(reminders) {
        var shownKey = key + '-rem', shown = [];
        try { shown = JSON.parse(localStorage.getItem(shownKey)) || []; } catch (e) {}
        var open = {};
        reminders.forEach(function (item) {
            open[item.id] = true;
            if (shown.indexOf(item.id) !== -1) return;
            shown.push(item.id);
            showCard(item);
            if (document.hidden) showSystem(item);
        });
        try { localStorage.setItem(shownKey, JSON.stringify(shown.slice(-100))); } catch (e) {}
        var list = getList();
        // a reminder that no longer applies (uploaded, job taken) leaves by itself
        var kept = list.filter(function (x) { return !/^(rt|dj)-/.test(String(x.id)) || open[x.id]; });
        if (kept.length !== list.length) saveList(kept);
    }

    function showSystem(item) {
        if (!supported() || Notification.permission !== 'granted') return;
        var o = { body: item.text, tag: 'mkwf-' + item.id, icon: '/img/pwa/content-plan-192.png', badge: '/img/pwa/content-plan-192.png', data: { url: item.url } };
        // phones only show notifications through a service worker
        if (swReg && swReg.showNotification) { swReg.showNotification(item.title, o).catch(function () {}); return; }
        try {
            var n = new Notification(item.title, o);
            n.onclick = function () { window.focus(); n.close(); open(item); };
        } catch (e) {}
    }

    function poll() {
        if (busy) return;
        busy = true;
        var since = Math.max(readSince(), memSince);
        $.getJSON(opts.url, { since: since }).done(function (r) {
            handleReminders(r.reminders || []);
            // another tab of this browser may have shown these already
            if (readSince() > since) { memSince = readSince(); return; }
            memSince = r.latest || since;
            writeSince(memSince);
            var items = r.items || [];
            items.forEach(function (item) {
                showCard(item);
                // a system notification when the user is looking elsewhere (other tab, app in background)
                if (document.hidden) showSystem(item);
            });
            if (items.length && opts.onEvents) opts.onEvents(items);
        }).always(function () { busy = false; });
    }

    function updateButton() {
        var $b = $(opts.button);
        if (!$b.length) return;
        $b.toggle(supported() && Notification.permission === 'default');
    }

    function start(o) {
        opts = o || {};
        key = 'mkwf-notif-since-' + (opts.userId || 'x');
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/workflow-sw.js').then(function (reg) { swReg = reg; }).catch(function () {});
            navigator.serviceWorker.addEventListener('message', function (e) {
                if (e.data && e.data.type === 'mkwf-open' && e.data.url) open({ url: e.data.url });
            });
        }
        $(opts.button).on('click', function () {
            if (!supported()) return;
            Notification.requestPermission().then(function (p) {
                updateButton();
                if (p === 'granted') {
                    // a sample right away, so the user sees what a notification looks like
                    var sample = { id: 'test', tone: 'success', title: 'Notifikasi aktif', text: 'Contoh: begini tampilannya saat ada konten perlu approval, siap publish, atau revisi.', url: window.location.href };
                    showCard(sample);
                    showSystem(sample);
                }
                else if (p === 'denied') Swal.fire('Notifikasi diblokir', 'Izinkan notifikasi untuk situs ini di pengaturan browser.', 'info');
            });
        });
        updateButton();
        // unread cards from before (reload, other tab) come back; other tabs closing a card update this one
        render();
        window.addEventListener('storage', function (e) { if (e.key === listKey()) render(); });
        poll();
        setInterval(poll, 10000);
    }

    return { start: start, poll: poll };
})();
</script>
