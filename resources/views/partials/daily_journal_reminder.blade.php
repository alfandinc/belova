{{-- Daily Journal reminder: tasks given by a manager, daily reports from the team, and deadlines. Included by layouts/shared/sidebar. --}}
@auth
<style>
.djr-card { position: fixed; left: 20px; bottom: 20px; z-index: 1150; width: min(360px, calc(100vw - 32px)); background: #fff; color: #0f172a; border: 1px solid rgba(148,163,184,.28); border-radius: 16px; box-shadow: 0 18px 44px rgba(15,23,42,.22); overflow: hidden; animation: djrIn .25s ease-out; }
.theme-dark .djr-card, html.theme-dark .djr-card, html[data-theme="dark"] .djr-card, body.dark-sidenav .djr-card { background: #0f172a; color: #e2e8f0; border-color: rgba(148,163,184,.2); box-shadow: 0 18px 44px rgba(2,6,23,.55); }
@keyframes djrIn { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: none; } }
.djr-head { display: flex; align-items: center; gap: 10px; padding: 12px 12px 10px 14px; background: linear-gradient(90deg, rgba(30,136,229,.12), rgba(100,181,246,.06)); }
.djr-head-icon { width: 32px; height: 32px; border-radius: 10px; background: #1e88e5; color: #fff; display: inline-flex; align-items: center; justify-content: center; flex: none; }
.djr-head-title { flex: 1; font-weight: 700; font-size: 14px; }
.djr-close { border: 0; background: transparent; color: inherit; opacity: .6; width: 28px; height: 28px; border-radius: 8px; font-size: 18px; line-height: 1; }
.djr-close:hover { opacity: 1; background: rgba(128,128,128,.14); }
.djr-body { padding: 8px 14px 4px; max-height: 260px; overflow-y: auto; }
.djr-item { padding: 8px 0; border-bottom: 1px solid rgba(128,128,128,.16); font-size: 13px; }
.djr-item:last-child { border-bottom: 0; }
.djr-item b { display: block; font-size: 13px; }
.djr-item span { opacity: .78; font-size: 12.5px; }
.djr-warn { display: flex; gap: 8px; align-items: center; padding: 8px 10px; margin: 6px 0; border-radius: 10px; background: rgba(231,76,60,.1); color: #e74c3c; font-size: 12.5px; font-weight: 600; }
.djr-foot { display: flex; justify-content: flex-end; gap: 6px; padding: 8px 12px 12px; }
@media (max-width: 767.98px) { .djr-card { left: 12px; bottom: 12px; } }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (!window.jQuery || window.__belovaJournalReminder) return;
    window.__belovaJournalReminder = true;
    var $ = window.jQuery;
    var storageKey = 'belova-journal-reminder-' + @json(auth()->id());
    var endpoints = {
        reminders: @json(route('daily-journal.reminders')),
        updates: @json(route('daily-journal.updates')),
        read: @json(route('daily-journal.reminders.read')),
    };

    function esc(v) {
        return String(v == null ? '' : v).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[c];
        });
    }
    function readStore(key) { try { return window.localStorage.getItem(key || storageKey) || ''; } catch (e) { return ''; } }
    function writeStore(v, key) { try { window.localStorage.setItem(key || storageKey, v); } catch (e) { /* private mode */ } }

    // ------------------------------------------------------------ team updates (toast, right top)
    // "X menyelesaikan task" / "Laporan harian X" for managers and task givers. Every 30s while the
    // tab is visible; the server answers poll=false for people without a team and polling stops.
    var swalLoading = null;
    function ensureSwal() {
        if (window.Swal) return $.Deferred().resolve().promise();
        if (!swalLoading) {
            $('<link rel="stylesheet">').attr('href', @json(asset('dastone/plugins/sweet-alert2/sweetalert2.min.css'))).appendTo('head');
            swalLoading = $.ajax({ url: @json(asset('dastone/plugins/sweet-alert2/sweetalert2.min.js')), dataType: 'script', cache: true });
        }
        return swalLoading;
    }

    var pending = [];
    function showUpdates() {
        if (!pending.length) return;
        ensureSwal().done(function () {
            // Never replace a dialog the page itself is showing; try again on the next poll
            if (!window.Swal || (window.Swal.isVisible() && !document.querySelector('.swal2-toast'))) return;
            var items = pending.splice(0, pending.length);
            var first = items[0];
            var single = items.length === 1;
            var allDone = items.every(function (i) { return i.kind === 'daily_journal_completed'; });
            window.Swal.fire({
                toast: true,
                position: 'top-end',
                icon: allDone ? 'success' : 'info',
                title: single ? first.title : items.length + ' kabar dari tim',
                html: single
                    ? esc(first.message)
                    : items.slice(0, 4).map(function (i) { return '<div style="text-align:left">• <b>' + esc(i.title) + '</b><br><small>' + esc(i.message) + '</small></div>'; }).join('')
                        + (items.length > 4 ? '<small>dan ' + (items.length - 4) + ' lainnya</small>' : ''),
                showConfirmButton: true,
                confirmButtonText: 'Lihat',
                showCloseButton: true,
                timer: 9000,
                timerProgressBar: true
            }).then(function (r) {
                if ((r.isConfirmed || r.value) && window.belovaJournalOpen) {
                    window.belovaJournalOpen({ tab: 'team', date: first.date });
                }
            });
        });
    }

    var updatesTimer = null;
    function pollUpdates() {
        if (document.hidden) return;
        $.ajax({ url: endpoints.updates, headers: { Accept: 'application/json' } }).done(function (res) {
            if (res.items && res.items.length) {
                pending = pending.concat(res.items);
            }
            showUpdates();
            if (!res.poll) {
                clearInterval(updatesTimer);
                updatesTimer = null;
            }
        });
    }
    setTimeout(pollUpdates, 2500);
    updatesTimer = setInterval(pollUpdates, 30000);
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden && updatesTimer) pollUpdates();
    });

    // ------------------------------------------------------------ reminder card (left bottom)
    // Pages are full reloads: ask the server at most every 5 minutes, unless a reminder is still waiting to be closed
    var checkKey = storageKey + '-checked';
    var CHECK_EVERY_MS = 5 * 60 * 1000;
    function markChecked() { writeStore(String(Date.now()), checkKey); }
    if (Date.now() - Number(readStore(checkKey) || 0) < CHECK_EVERY_MS) return;

    // After the page settles, so it does not compete with the page's own requests
    setTimeout(function () {
        $.ajax({ url: endpoints.reminders, headers: { Accept: 'application/json' } }).done(show);
    }, 1500);

    function show(data) {
        var notes = data.notifications || [];
        var deadlineCount = (data.overdue || 0) + (data.due_today || 0);
        // Deadline nudge: once per day, again only when the numbers change
        var signature = data.today + ':' + data.overdue + ':' + data.due_today;
        var showDeadline = deadlineCount > 0 && readStore() !== signature;
        if (!notes.length && !showDeadline) { markChecked(); return; }
        if (showDeadline) writeStore(signature);

        var items = notes.slice(0, 4).map(function (n) {
            return '<div class="djr-item"><b>' + esc(n.title) + '</b><span>' + esc(n.message) + '</span></div>';
        }).join('');
        if (notes.length > 4) items += '<div class="djr-item"><span>dan ' + (notes.length - 4) + ' lainnya</span></div>';

        var warn = '';
        if (showDeadline) {
            var parts = [];
            if (data.overdue) parts.push(data.overdue + ' task lewat deadline');
            if (data.due_today) parts.push(data.due_today + ' deadline hari ini');
            warn = '<div class="djr-warn"><i class="fas fa-flag"></i>' + esc(parts.join(' · ')) + '</div>';
        }

        var $card = $(''
            + '<div class="djr-card" role="status">'
            + '  <div class="djr-head">'
            + '    <span class="djr-head-icon"><i class="fas fa-book-open"></i></span>'
            + '    <div class="djr-head-title">Daily Journal</div>'
            + '    <button type="button" class="djr-close" aria-label="Tutup">&times;</button>'
            + '  </div>'
            + '  <div class="djr-body">' + warn + items + '</div>'
            + '  <div class="djr-foot">'
            + '    <button type="button" class="btn btn-sm btn-light" data-djr="later">Nanti</button>'
            + '    <button type="button" class="btn btn-sm btn-primary" data-djr="open">Buka Journal</button>'
            + '  </div>'
            + '</div>');

        function done() {
            var ids = notes.map(function (n) { return n.id; });
            if (ids.length) {
                $.ajax({
                    url: endpoints.read, type: 'POST', data: { ids: ids },
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), Accept: 'application/json' }
                });
            }
            $card.remove();
            markChecked();
        }

        $card.on('click', '.djr-close, [data-djr="later"]', done);
        $card.on('click', '[data-djr="open"]', function () {
            done();
            if (window.belovaJournalOpen) {
                window.belovaJournalOpen({ tab: 'mine' });
            }
        });
        $('body').append($card);
    }
});
</script>
@endauth
