// Service Worker for Offline POS System
// Version 1.0.0

const CACHE_NAME = 'pos-cache-v1';
const RUNTIME_CACHE = 'pos-runtime-v1';

// Critical assets to cache immediately
const PRECACHE_URLS = [
    '/',
    '/admin/cart/index',
    '/assets/css/bootstrap/bootstrap.min.css',
    '/assets/css/style.min.css',
    '/build/assets/app.js',
    '/build/assets/app.css',
];

// Install event - precache critical assets
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => cache.addAll(PRECACHE_URLS))
            .then(() => self.skipWaiting())
    );
});

// Activate event - clean old caches
self.addEventListener('activate', event => {
    const currentCaches = [CACHE_NAME, RUNTIME_CACHE];
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return cacheNames.filter(cacheName => !currentCaches.includes(cacheName));
        }).then(cachesToDelete => {
            return Promise.all(cachesToDelete.map(cacheToDelete => {
                return caches.delete(cacheToDelete);
            }));
        }).then(() => self.clients.claim())
    );
});

// Fetch event - network first, fallback to cache
self.addEventListener('fetch', event => {
    // Skip non-GET requests
    if (event.request.method !== 'GET') {
        return;
    }

    // Skip chrome-extension and other non-http requests
    if (!event.request.url.startsWith('http')) {
        return;
    }

    event.respondWith(
        caches.open(RUNTIME_CACHE).then(cache => {
            return fetch(event.request).then(response => {
                // Cache successful responses
                if (response.status === 200) {
                    cache.put(event.request, response.clone());
                }
                return response;
            }).catch(() => {
                // Network failed, try cache
                return cache.match(event.request).then(cachedResponse => {
                    if (cachedResponse) {
                        return cachedResponse;
                    }
                    // Try precache
                    return caches.match(event.request);
                });
            });
        })
    );
});

// Message event - handle manual cache updates
self.addEventListener('message', event => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});
