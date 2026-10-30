{{-- Popup as soon as the user is no longer logged in (session expired, logout in another tab / device, account set
     nonaktif, sessions cleared by an update in production) instead of finding out when a save fails.
     - asks route('session.status') every minute while the tab is visible, right away when the tab is shown again,
       and after any AJAX 401/419; that route does not keep the session alive
     - "Login di tab baru" keeps this page (and its unsaved input): once logged in again the page gets the new
       CSRF token and the user can just save again
     Plain JS + own markup, so it works in every layout (with or without jQuery / SweetAlert). --}}
@auth
<style>
    #belova-session-overlay { position: fixed; inset: 0; z-index: 200000; background: rgba(0, 0, 0, .55); display: flex; align-items: center; justify-content: center; padding: 16px; }
    #belova-session-overlay .bsw-card { background: #1e2235; color: #e5e7eb; border-radius: 8px; max-width: 440px; width: 100%; padding: 24px; box-shadow: 0 10px 40px rgba(0, 0, 0, .4); font-size: 14px; line-height: 1.5; }
    #belova-session-overlay .bsw-icon { width: 72px; height: 72px; margin: 0 auto 14px; border-radius: 50%; border: 4px solid currentColor; display: flex; align-items: center; justify-content: center; font-size: 32px; }
    #belova-session-overlay .bsw-icon.warning { color: #f59e0b; }
    #belova-session-overlay .bsw-icon.danger { color: #ef4444; }
    #belova-session-overlay .bsw-icon.info { color: #3b82f6; }
    #belova-session-overlay .bsw-icon.success { color: #22c55e; }
    #belova-session-overlay .bsw-title { font-size: 19px; font-weight: 600; margin: 0 0 8px; color: inherit; text-align: center; }
    #belova-session-overlay .bsw-message { text-align: center; }
    #belova-session-overlay .bsw-note { font-size: 12.5px; opacity: .85; margin-top: 12px; padding: 8px 10px; border-radius: 6px; background: rgba(245, 158, 11, .12); }
    #belova-session-overlay .bsw-actions { display: flex; flex-wrap: wrap; gap: 8px; justify-content: center; margin-top: 18px; }
    #belova-session-banner { position: fixed; right: 16px; bottom: 16px; z-index: 199999; background: #f59e0b; color: #1f2937; border-radius: 6px; padding: 8px 12px; font-size: 13px; box-shadow: 0 4px 16px rgba(0, 0, 0, .3); }
    #belova-session-banner a { color: #1f2937; font-weight: 600; text-decoration: underline; margin-left: 6px; cursor: pointer; }
    html.theme-light #belova-session-overlay .bsw-card { background: #fff; color: #303e67; }
</style>
<script>
(function () {
    if (window.belovaSessionWatch) return;
    var STATUS_URL = "{{ route('session.status') }}";
    var TOKEN_URL = "{{ route('csrf.token') }}";
    var LOGIN_URL = "{{ url('/') }}";
    var USER_ID = {{ (int) auth()->id() }};
    var MESSAGES = {
        expired: 'Sesi login Anda berakhir karena terlalu lama tidak aktif.',
        nonaktif: 'Akun Anda dinonaktifkan. Hubungi Admin.',
        logged_out: 'Anda sudah tidak login. Bisa karena logout di tab / perangkat lain, atau ada pembaruan sistem.'
    };
    var loggedOut = false, busy = false, lastCheck = Date.now();

    function check() {
        if (busy) return;
        busy = true;
        lastCheck = Date.now();
        fetch(STATUS_URL, { credentials: 'same-origin', cache: 'no-store', headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (res) {
                // Offline, server error or maintenance during a deploy: no verdict, ask again later
                if (!res) return;
                if (res.active) {
                    if (res.user_id !== USER_ID) showOtherUser();
                    else if (loggedOut) resume();
                } else if (!loggedOut) {
                    showLoggedOut(res.reason);
                }
            })
            .catch(function () {})
            .then(function () { busy = false; });
    }

    function removeEl(id) {
        var el = document.getElementById(id);
        if (el) el.parentNode.removeChild(el);
    }

    var ICONS = {
        warning: 'fa-exclamation-triangle',
        danger: 'fa-user-lock',
        info: 'fa-user-friends',
        success: 'fa-check'
    };

    function overlay(type, title, message, note, buttons) {
        removeEl('belova-session-overlay');
        var wrap = document.createElement('div');
        wrap.id = 'belova-session-overlay';
        wrap.innerHTML = '<div class="bsw-card" role="alertdialog" aria-modal="true">'
            + '<div class="bsw-icon ' + type + '"><i class="fas ' + ICONS[type] + '"></i></div>'
            + '<div class="bsw-title">' + title + '</div><div class="bsw-message">' + message + '</div>'
            + (note ? '<div class="bsw-note">' + note + '</div>' : '')
            + '<div class="bsw-actions"></div></div>';
        var actions = wrap.querySelector('.bsw-actions');
        buttons.forEach(function (b) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'btn btn-sm ' + b.cls;
            btn.textContent = b.label;
            btn.addEventListener('click', b.onClick);
            actions.appendChild(btn);
        });
        document.body.appendChild(wrap);
        actions.lastChild.focus();
    }

    function relogin() { window.location.reload(); }

    function showBanner() {
        removeEl('belova-session-banner');
        var banner = document.createElement('div');
        banner.id = 'belova-session-banner';
        banner.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Anda belum login lagi.<a>Login ulang</a>';
        banner.querySelector('a').addEventListener('click', relogin);
        document.body.appendChild(banner);
    }

    function showLoggedOut(reason) {
        loggedOut = true;
        overlay(
            reason === 'nonaktif' ? 'danger' : 'warning',
            reason === 'expired' ? 'Sesi login berakhir' : 'Anda sudah logout',
            MESSAGES[reason] || MESSAGES.logged_out,
            reason === 'nonaktif' ? '' : 'Ada isian yang belum disimpan? Pilih <b>Login di tab baru</b>: halaman ini tetap terbuka, setelah login kembali ke sini lalu simpan lagi.',
            reason === 'nonaktif'
                ? [{ label: 'OK', cls: 'btn-primary', onClick: relogin }]
                : [
                    { label: 'Login di tab baru', cls: 'btn-outline-secondary', onClick: function () {
                        window.open(LOGIN_URL, '_blank');
                        removeEl('belova-session-overlay');
                        showBanner();
                    } },
                    { label: 'Login ulang', cls: 'btn-primary', onClick: relogin }
                ]
        );
    }

    function showOtherUser() {
        loggedOut = true;
        overlay(
            'info',
            'Login sebagai user lain',
            'Browser ini sekarang login dengan akun lain. Muat ulang halaman supaya data yang tampil sesuai akun tersebut.',
            '',
            [{ label: 'Muat ulang', cls: 'btn-primary', onClick: relogin }]
        );
    }

    // Logged in again (e.g. in another tab): new session, so the page needs the new CSRF token
    function resume() {
        fetch(TOKEN_URL, { credentials: 'same-origin', cache: 'no-store', headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (res) {
                if (!res || !res.token) return;
                var meta = document.querySelector('meta[name="csrf-token"]');
                if (meta) meta.setAttribute('content', res.token);
                document.querySelectorAll('input[name="_token"]').forEach(function (input) { input.value = res.token; });
                if (window.jQuery) window.jQuery.ajaxSetup({ headers: { 'X-CSRF-TOKEN': res.token } });
                loggedOut = false;
                removeEl('belova-session-overlay');
                removeEl('belova-session-banner');
                overlay('success', 'Sudah login kembali', 'Silakan lanjutkan dan simpan lagi isian Anda.', '',
                    [{ label: 'OK', cls: 'btn-primary', onClick: function () { removeEl('belova-session-overlay'); } }]);
            })
            .catch(function () {});
    }

    window.belovaSessionWatch = { check: check };

    setInterval(function () {
        if (document.visibilityState === 'visible') check();
    }, 60 * 1000);
    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible' && Date.now() - lastCheck > 10 * 1000) check();
    });
    window.addEventListener('online', check);
    // Any AJAX call refused for auth / CSRF: find out right away whether the session is gone
    if (window.jQuery) {
        window.jQuery(document).ajaxError(function (e, xhr) {
            if (xhr && (xhr.status === 401 || xhr.status === 419)) check();
        });
    }
})();
</script>
@endauth
