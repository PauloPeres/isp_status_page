// REBRAND: Update 'ISP Status Alert' title in push handler below when rebranding.
const CACHE_NAME = 'isp-status-v1';
const OFFLINE_URL = '/offline.html';

// Assets to pre-cache
const PRECACHE_ASSETS = [
    '/css/admin.css',
    '/css/auth.css',
    '/css/public.css',
    '/js/monitor-form.js',
    '/js/charts.js',
    '/img/icon_isp_status_page.png',
    OFFLINE_URL,
];

// Install — pre-cache essential assets
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(PRECACHE_ASSETS);
        })
    );
    self.skipWaiting();
});

// Activate — clean old caches
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => {
            return Promise.all(
                keys.filter(key => key !== CACHE_NAME).map(key => caches.delete(key))
            );
        })
    );
    self.clients.claim();
});

// Fetch — network first, fallback to cache, then offline page
self.addEventListener('fetch', (event) => {
    // Skip non-GET requests
    if (event.request.method !== 'GET') return;

    // Skip API requests — always go to network
    if (event.request.url.includes('/api/')) return;

    event.respondWith(
        fetch(event.request)
            .then((response) => {
                // Cache successful responses for static assets
                if (response.ok && isStaticAsset(event.request.url)) {
                    const clone = response.clone();
                    caches.open(CACHE_NAME).then(cache => cache.put(event.request, clone));
                }
                return response;
            })
            .catch(() => {
                // Try cache
                return caches.match(event.request).then(cached => {
                    if (cached) return cached;
                    // If HTML request, show offline page
                    if (event.request.headers.get('accept')?.includes('text/html')) {
                        return caches.match(OFFLINE_URL);
                    }
                });
            })
    );
});

function isStaticAsset(url) {
    return url.match(/\.(css|js|png|jpg|svg|woff2?)$/);
}

// Push notification support
self.addEventListener('push', (event) => {
    const data = event.data?.json() || { title: 'ISP Status Alert', body: 'A monitor needs attention' };
    event.waitUntil(
        self.registration.showNotification(data.title, {
            body: data.body,
            icon: '/img/icon_isp_status_page.png',
            badge: '/img/icon_isp_status_page.png',
            tag: data.tag || 'isp-alert',
            data: { url: data.url || '/dashboard' },
        })
    );
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    event.waitUntil(
        clients.openWindow(event.notification.data.url)
    );
});
