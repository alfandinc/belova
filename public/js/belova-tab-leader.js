/**
 * BelovaTabLeader: run a polling task in only ONE tab per browser.
 *
 * Opening several tabs used to multiply notification polling (each tab every 2s) while only one tab
 * could ever receive a given notification. Now tabs share a localStorage lock per channel:
 *  - the leader runs the task, the other tabs stay idle;
 *  - leadership moves to the tab the user focuses, so popups/sounds appear where the user is looking;
 *  - if the leader closes or freezes, another tab takes over after a few missed intervals.
 * Without localStorage every tab simply polls on its own (previous behaviour).
 *
 * Usage: BelovaTabLeader.run('farmasi-notif', 2000, function () { ...poll... });
 *        BelovaTabLeader.shared('farmasi-notif')            -> value saved by the previous leader
 *        BelovaTabLeader.share('farmasi-notif', value)      -> save a value for the next leader
 */
(function (window) {
    'use strict';

    if (window.BelovaTabLeader) {
        return;
    }

    var tabId = Math.random().toString(36).slice(2) + Date.now().toString(36);
    var storage = null;

    try {
        var probe = '__belova_tab_leader_probe__';
        window.localStorage.setItem(probe, '1');
        window.localStorage.removeItem(probe);
        storage = window.localStorage;
    } catch (e) {
        storage = null;
    }

    function read(key) {
        try {
            return JSON.parse(storage.getItem(key) || 'null');
        } catch (e) {
            return null;
        }
    }

    function write(key, value) {
        try {
            storage.setItem(key, JSON.stringify(value));
        } catch (e) { /* quota / blocked: ignore */ }
    }

    function isUserLookingHere() {
        return document.visibilityState === 'visible' && (typeof document.hasFocus !== 'function' || document.hasFocus());
    }

    function run(channel, intervalMs, task) {
        if (!storage) {
            setInterval(task, intervalMs);
            task();
            return;
        }

        var lockKey = 'belova-leader:' + channel;
        var staleAfterMs = Math.max(intervalMs * 3, 6000);

        function claim() {
            write(lockKey, { id: tabId, ts: Date.now() });
        }

        function amLeader() {
            var lock = read(lockKey);
            var now = Date.now();

            if (!lock || lock.id === tabId || now - (lock.ts || 0) > staleAfterMs) {
                claim();
                return true;
            }

            // The tab the user is looking at takes over from a leader that is in the background
            if (isUserLookingHere()) {
                claim();
                return true;
            }

            return false;
        }

        function tick() {
            if (amLeader()) {
                task();
            }
        }

        function takeOver() {
            if (isUserLookingHere()) {
                claim();
            }
        }

        window.addEventListener('focus', takeOver);
        document.addEventListener('visibilitychange', takeOver);
        window.addEventListener('pagehide', function () {
            var lock = read(lockKey);
            if (lock && lock.id === tabId) {
                try { storage.removeItem(lockKey); } catch (e) { /* ignore */ }
            }
        });

        setInterval(tick, intervalMs);
        tick();
    }

    function share(channel, value) {
        if (storage) {
            write('belova-leader-state:' + channel, { value: value, ts: Date.now() });
        }
    }

    function shared(channel, maxAgeMs) {
        if (!storage) {
            return null;
        }
        var entry = read('belova-leader-state:' + channel);
        if (!entry || Date.now() - (entry.ts || 0) > (maxAgeMs || 60000)) {
            return null;
        }
        return entry.value;
    }

    window.BelovaTabLeader = { run: run, share: share, shared: shared, tabId: tabId };
})(window);
