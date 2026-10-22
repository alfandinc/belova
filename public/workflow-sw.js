// Service worker of the Content Plan / Request Desain pages: only shows notifications (phones need a
// service worker for that) and opens the page when one is tapped. It does not touch any request.
self.addEventListener('install', function () { self.skipWaiting(); });
self.addEventListener('activate', function (event) { event.waitUntil(self.clients.claim()); });

self.addEventListener('notificationclick', function (event) {
    event.notification.close();
    var url = (event.notification.data && event.notification.data.url) || '/marketing/content-plan';
    event.waitUntil(self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (list) {
        for (var i = 0; i < list.length; i++) {
            var c = list[i];
            if (new URL(c.url).pathname === new URL(url, self.location.origin).pathname && 'focus' in c) {
                c.postMessage({ type: 'mkwf-open', url: url });
                return c.focus();
            }
        }
        return self.clients.openWindow(url);
    }));
});
