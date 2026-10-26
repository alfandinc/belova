{{-- Pages left open a long time (e.g. Pengajuan Dana) failed with "CSRF token mismatch" (419): the session expired,
     or the user logged in again in another tab (a new session, so the token of every open page is stale).
     - while the user is active, refresh the token every 10 minutes (also keeps the session alive)
     - when a POST/PUT/DELETE still gets a 419, fetch the current token and send the request once more
     Include after jQuery. --}}
<script>
(function ($) {
    if (!$ || window.belovaCsrfRefresh) return;
    var URL = "{{ route('csrf.token') }}";
    var lastActivity = Date.now();
    var lastRefresh = Date.now();

    function setToken(token) {
        $('meta[name="csrf-token"]').attr('content', token);
        $('input[name="_token"]').val(token);
        $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': token } });
    }

    // Resolves with the current token; rejects when the session is gone (redirected to the login page)
    function refresh() {
        return $.ajax({ url: URL, method: 'GET', dataType: 'json', cache: false, _csrfInternal: true })
            .then(function (res) {
                if (!res || !res.token) return $.Deferred().reject().promise();
                lastRefresh = Date.now();
                setToken(res.token);
                return res.token;
            });
    }
    window.belovaCsrfRefresh = refresh;

    $(document).on('mousemove keydown touchstart scroll', function () { lastActivity = Date.now(); });
    setInterval(function () {
        if (Date.now() - lastActivity < 10 * 60 * 1000) refresh();
    }, 10 * 60 * 1000);
    // Coming back to a tab that was in the background: refresh right away
    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible' && Date.now() - lastRefresh > 5 * 60 * 1000) refresh();
    });

    $.ajaxPrefilter(function (options, original) {
        var type = String(options.type || options.method || 'GET').toUpperCase();
        if (original._csrfInternal || original._csrfRetried || type === 'GET' || type === 'HEAD') return;
        var originalError = options.error;
        options.error = function (xhr) {
            var args = arguments, ctx = this;
            if (xhr.status !== 419) {
                if (originalError) originalError.apply(ctx, args);
                return;
            }
            refresh().then(function (token) {
                var retry = $.extend({}, original, { _csrfRetried: true });
                retry.headers = $.extend({}, original.headers, { 'X-CSRF-TOKEN': token });
                if (typeof FormData !== 'undefined' && retry.data instanceof FormData) {
                    retry.data.set('_token', token);
                } else if (typeof retry.data === 'string') {
                    retry.data = /(^|&)_token=/.test(retry.data)
                        ? retry.data.replace(/(^|&)_token=[^&]*/, '$1_token=' + encodeURIComponent(token))
                        : retry.data;
                } else if (retry.data && typeof retry.data === 'object') {
                    retry.data = $.extend({}, retry.data, retry.data._token !== undefined ? { _token: token } : {});
                }
                $.ajax(retry);
            }, function () {
                // Session really ended (logged out / expired): a new token can't help, the user has to log in
                if (!window.Swal) {
                    if (originalError) originalError.apply(ctx, args);
                    return;
                }
                window.Swal.fire({
                    icon: 'warning',
                    title: 'Sesi login berakhir',
                    html: 'Silakan login ulang. Salin dulu isian yang panjang sebelum memuat ulang halaman.',
                    confirmButtonText: 'Login ulang'
                }).then(function () { window.location.reload(); });
            });
        };
    });
})(window.jQuery);
</script>
