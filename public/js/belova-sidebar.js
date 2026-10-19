document.addEventListener('DOMContentLoaded', function () {
    // Jadwal viewer is fetched on first use (it is ~30KB of markup + script nobody needs on most page views)
    if (window.jQuery) {
        var jadwalLoading = null;
        window.jQuery(document).on('click', '.js-open-jadwal', function (e) {
            e.preventDefault();
            if (window.belovaOpenJadwal) { window.belovaOpenJadwal(); return; }
            if (!jadwalLoading) {
                jadwalLoading = window.jQuery.get(window.BelovaSidebarConfig.routes["jadwal.viewer"])
                    .done(function (html) { window.jQuery('body').append(html); })
                    .fail(function () { jadwalLoading = null; });
            }
            jadwalLoading.done(function () { if (window.belovaOpenJadwal) window.belovaOpenJadwal(); });
        });

        // Daily Journal is a modal too, loaded the same way
        var journalLoading = null;
        var openJournal = window.belovaJournalOpen = function (options) {
            if (window.belovaOpenJournal) { window.belovaOpenJournal(options); return; }
            if (!journalLoading) {
                // Versioned URL: the browser keeps the markup between pages (see DailyJournalController::modal)
                journalLoading = window.jQuery.ajax({ url: window.BelovaSidebarConfig.journalModalUrl, cache: true })
                    .done(function (html) { window.jQuery('body').append(html); })
                    .fail(function () { journalLoading = null; });
            }
            journalLoading.done(function () { if (window.belovaOpenJournal) window.belovaOpenJournal(options); });
        };
        window.jQuery(document).on('click', '.js-open-journal, a[href=' + JSON.stringify(window.BelovaSidebarConfig.routes["daily-journal.index"]) + ']', function (e) {
            e.preventDefault();
            openJournal();
        });
        // Old /daily-journal links land on the dashboard with ?journal=1
        if (/[?&]journal=1(&|$)/.test(window.location.search)) {
            window.history.replaceState({}, '', window.location.pathname + window.location.search.replace(/([?&])journal=1(&|$)/, '$1').replace(/[?&]$/, '') + window.location.hash);
            openJournal();
        }
    }

    var sidenav = document.getElementById('belovaSidenav');
    var input = document.getElementById('belovaNavSearch');
    if (!sidenav || !input) return;

    var menu = document.getElementById('belovaMainMenu');
    var empty = document.getElementById('belovaNavEmpty');
    var modules = Array.prototype.slice.call(menu.querySelectorAll('[data-nav-module]'));
    var initial = modules.map(function (li) {
        var ul = li.querySelector('ul');
        return { open: li.classList.contains('mm-active'), shown: ul ? ul.classList.contains('mm-show') : false };
    });

    function setOpen(li, open) {
        var ul = li.querySelector('ul');
        li.classList.toggle('mm-active', open);
        if (ul) {
            ul.classList.toggle('mm-show', open);
            ul.classList.remove('mm-collapse');
            ul.style.height = '';
        }
    }

    function apply() {
        var q = input.value.trim().toLowerCase();
        sidenav.classList.toggle('is-filtering', q !== '');
        var anyVisible = false;

        modules.forEach(function (li, index) {
            var items = Array.prototype.slice.call(li.querySelectorAll('[data-nav-item]'));
            var headings = Array.prototype.slice.call(li.querySelectorAll('[data-nav-heading]'));

            if (!q) {
                li.style.display = '';
                items.forEach(function (item) { item.style.display = ''; });
                headings.forEach(function (h) { h.style.display = ''; });
                var ul = li.querySelector('ul');
                li.classList.toggle('mm-active', initial[index].open);
                if (ul) { ul.classList.toggle('mm-show', initial[index].shown); ul.style.height = ''; }
                return;
            }

            var moduleMatch = (li.getAttribute('data-nav-label') || '').indexOf(q) !== -1;
            var itemMatches = 0;
            items.forEach(function (item) {
                var match = moduleMatch || (item.getAttribute('data-nav-label') || '').indexOf(q) !== -1;
                item.style.display = match ? '' : 'none';
                if (match) itemMatches++;
            });
            headings.forEach(function (h) { h.style.display = moduleMatch ? '' : 'none'; });

            var visible = moduleMatch || itemMatches > 0;
            li.style.display = visible ? '' : 'none';
            if (items.length) setOpen(li, visible);
            if (visible) anyVisible = true;
        });

        Array.prototype.slice.call(menu.querySelectorAll('[data-nav-section]')).forEach(function (label) {
            var next = label.nextElementSibling, show = !q;
            while (!show && next && !next.hasAttribute('data-nav-section')) {
                if (next.hasAttribute('data-nav-module') && next.style.display !== 'none') show = true;
                next = next.nextElementSibling;
            }
            label.style.display = show ? '' : 'none';
        });

        empty.classList.toggle('show', !!q && !anyVisible);
    }

    input.addEventListener('input', apply);
    input.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') { input.value = ''; apply(); input.blur(); }
        if (e.key === 'Enter') {
            var first = Array.prototype.slice.call(menu.querySelectorAll('[data-nav-item] a, li.belova-nav-link > a'))
                .find(function (a) { return a.offsetParent !== null; });
            if (first) { e.preventDefault(); first.click(); }
        }
    });

    // "/" focuses the menu search (unless already typing somewhere)
    document.addEventListener('keydown', function (e) {
        var tag = (document.activeElement && document.activeElement.tagName || '').toLowerCase();
        if (e.key === '/' && tag !== 'input' && tag !== 'textarea' && tag !== 'select' && !document.activeElement.isContentEditable) {
            e.preventDefault();
            document.body.classList.remove('enlarge-menu');
            input.focus();
            input.select();
        }
    });
});
