<script>
    // Lazy-load stock modal markup only when needed (loaded once, reused)
    window.__stockInfoModalLoadPromise = window.__stockInfoModalLoadPromise || null;

    window.ensureStockInfoModalLoaded = window.ensureStockInfoModalLoaded || function ensureStockInfoModalLoaded() {
        try {
            if (window.jQuery && window.jQuery('#stockInfoModal').length) {
                return window.jQuery.Deferred().resolve(true).promise();
            }

            if (window.__stockInfoModalLoadPromise) {
                return window.__stockInfoModalLoadPromise;
            }

            if (!window.jQuery) {
                return Promise.reject(new Error('jQuery is not loaded'));
            }

            window.__stockInfoModalLoadPromise = window.jQuery.ajax({
                url: "{{ route('finance.billing.stock-info-modal') }}",
                type: 'GET',
                dataType: 'html'
            })
                .then(function(html) {
                    if (!window.jQuery('#stockInfoModal').length) {
                        window.jQuery('body').append(html);
                    }
                    return true;
                })
                .fail(function() {
                    window.__stockInfoModalLoadPromise = null; // allow retry
                });

            return window.__stockInfoModalLoadPromise;
        } catch (e) {
            window.__stockInfoModalLoadPromise = null;
            if (window.jQuery) {
                return window.jQuery.Deferred().reject(e).promise();
            }
            return Promise.reject(e);
        }
    };
</script>
