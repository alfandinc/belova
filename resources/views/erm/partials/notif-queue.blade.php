{{--
    BelovaNotifQueue: realtime notification popups that are never lost.
    Pollers push every unread notification the server returns (?queue=1); the queue shows them one at a time,
    only while no other popup is open, oldest first. A notification counts as shown once its popup is up
    (remembered per browser in localStorage, so other tabs do not repeat it); if another popup replaces it before
    the user answered, it goes back to the front of the queue. Read state stays on the server: a popup marks its
    notification read when the user clicks OK (or "Buka Dokumen").
--}}
<script>
(function (window, $) {
    'use strict';
    if (window.BelovaNotifQueue) return;

    var SHOWN_KEY = 'belova-notif-shown';
    var SHOWN_TTL_MS = 24 * 60 * 60 * 1000;
    var queue = [];          // [{item, render}]
    var queued = {};         // id => true while waiting or on screen
    var active = null;       // {entry, settled}
    var watchdog = null;

    function readShown() {
        try { return JSON.parse(window.localStorage.getItem(SHOWN_KEY) || '{}') || {}; } catch (e) { return {}; }
    }
    function writeShown(map) {
        var now = Date.now();
        Object.keys(map).forEach(function (id) { if (now - map[id] > SHOWN_TTL_MS) delete map[id]; });
        try { window.localStorage.setItem(SHOWN_KEY, JSON.stringify(map)); } catch (e) { /* blocked: in-memory only */ }
    }
    function setShown(id, shown) {
        var map = readShown();
        if (shown) map[id] = Date.now(); else delete map[id];
        writeShown(map);
    }

    function otherPopupOpen() {
        return !!(window.Swal && Swal.isVisible && Swal.isVisible());
    }

    function ensureWatchdog() {
        if (!watchdog) watchdog = setInterval(pump, 700);
    }
    function stopWatchdogIfIdle() {
        if (watchdog && !queue.length && !active) {
            clearInterval(watchdog);
            watchdog = null;
        }
    }

    function finish(entry, preempted) {
        if (!active || active.entry !== entry || active.settled) return;
        active.settled = true;
        active = null;
        var id = String(entry.item.id);
        if (preempted) {
            // Replaced by another popup before the user answered: show it again later
            setShown(id, false);
            queue.unshift(entry);
        } else {
            delete queued[id];
        }
        pump();
    }

    function pump() {
        if (active) {
            // Our popup is gone but its promise never settled (another Swal.fire replaced it): requeue
            if (!otherPopupOpen() && Date.now() - active.since > 1500) finish(active.entry, true);
            ensureWatchdog();
            return;
        }
        if (!queue.length) { stopWatchdogIfIdle(); return; }
        if (otherPopupOpen()) { ensureWatchdog(); return; }

        var entry = queue.shift();
        var id = String(entry.item.id);
        active = { entry: entry, settled: false, since: Date.now() };
        setShown(id, true);
        ensureWatchdog();

        Promise.resolve(entry.render(entry.item)).then(function (result) {
            // SweetAlert2 9.5.3 resolves {value} on confirm and {dismiss} otherwise; neither = replaced
            var answered = result && (result.value !== undefined || result.dismiss !== undefined || result.isConfirmed);
            finish(entry, !answered);
        }, function () {
            finish(entry, false);
        });
    }

    /** Queue notifications (each needs an id); ones already queued, on screen or shown before are skipped. */
    function push(items, render) {
        (items || []).forEach(function (item) {
            if (!item || item.id === undefined || item.id === null) return;
            var id = String(item.id);
            if (queued[id] || readShown()[id]) return;
            queued[id] = true;
            queue.push({ item: item, render: render });
        });
        pump();
    }

    function playSound(file) {
        if (!window.soundEnabled) return;
        try { new Audio(file).play(); } catch (e) { /* autoplay blocked */ }
    }

    if (!document.getElementById('belova-notif-popup-style')) {
        $('head').append(
            '<style id="belova-notif-popup-style">'
            + '.swal2-icon.swal2-info.swal2-door-icon{border-color:#17a2b8;color:#17a2b8;}'
            + '.swal2-door-icon .fas{font-size:2.2rem;color:#17a2b8;line-height:1;}'
            + '.swal2-icon.swal2-info.swal2-call-icon{border-color:#f59e0b;color:#f59e0b;}'
            + '.swal2-call-icon .fas{font-size:2.2rem;color:#f59e0b;line-height:1;}'
            + '</style>'
        );
    }

    /**
     * Popup of an /erm/get-notif item (dokter ↔ perawat, rujukan, lab, radiologi, surat). Marks it read on OK /
     * "Buka Dokumen" (then opens the document); window.belovaNotifBadge(count), when a page defines it, gets the new unread count.
     */
    function ermPopup(data) {
        var soundFile = '/sounds/notif.mp3';
        var text = data.message || 'Ada notifikasi baru.';
        var iconHtml = null;
        var customClass = {};
        if (data.message === 'Mohon buka pintu untuk pasien.') {
            soundFile = '/sounds/bell.wav';
            iconHtml = '<i class="fas fa-door-open"></i>';
            customClass = { icon: 'swal2-door-icon' };
        } else if (data.message === 'Mohon datang ke ruang dokter.') {
            iconHtml = '<i class="fas fa-user-md"></i>';
            customClass = { icon: 'swal2-call-icon' };
        }
        // Rujukan / lab / radiologi / surat already list Dari / Dokter as lines
        var multiline = !!data.multiline && window.rujukanNotifHtml;
        if (data.sender && !multiline) text += '\n(Dari: ' + data.sender + ')';

        var popup = Swal.fire({
            title: data.title || 'Notifikasi',
            text: multiline ? undefined : text,
            html: multiline ? window.rujukanNotifHtml(data.message) : undefined,
            icon: 'info',
            iconHtml: iconHtml,
            customClass: customClass,
            confirmButtonText: 'OK',
            showCancelButton: !!data.dokumen_url,
            cancelButtonText: 'Buka Dokumen',
            cancelButtonColor: '#28a745',
            reverseButtons: true
        });
        // A popup that opens a document is announced by the document link, without a sound (as before)
        if (!data.dokumen_url) playSound(soundFile);

        return popup.then(function (result) {
            var openDoc = result && result.dismiss === Swal.DismissReason.cancel && data.dokumen_url;
            if (result && (result.value === true || result.isConfirmed || openDoc)) {
                $.post('{{ route("erm.rawatjalans.notification-mark-read") }}', {
                    _token: '{{ csrf_token() }}',
                    notification_id: data.id
                }).done(function (res) {
                    if (typeof window.belovaNotifBadge === 'function') window.belovaNotifBadge(res.unread_count || 0);
                }).always(function () {
                    if (openDoc) window.location.href = data.dokumen_url;
                });
            }
            return result;
        });
    }

    window.BelovaNotifQueue = { push: push, ermPopup: ermPopup, playSound: playSound };
})(window, jQuery);
</script>
