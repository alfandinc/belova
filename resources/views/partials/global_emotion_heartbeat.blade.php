@auth
<script>
    (function () {
        var heartbeatIntervalMs = 300000;
        var heartbeatUrl = '{{ route('user-emotions.heartbeat') }}';
        var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        var isSending = false;

        if (!heartbeatUrl || !csrfToken) {
            return;
        }

        function sendHeartbeat() {
            if (isSending) {
                return;
            }

            isSending = true;

            fetch(heartbeatUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new URLSearchParams({
                    _token: csrfToken
                })
            }).catch(function () {
                // Ignore heartbeat failures and retry on next interval.
            }).finally(function () {
                isSending = false;
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            sendHeartbeat();
            window.setInterval(sendHeartbeat, heartbeatIntervalMs);
        });

        document.addEventListener('visibilitychange', function () {
            if (document.visibilityState === 'visible') {
                sendHeartbeat();
            }
        });

        window.addEventListener('focus', sendHeartbeat);
    })();
</script>
@endauth