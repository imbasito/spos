const CACHE_NAME = 'spos-cache-v2-scanner-fix';
const urlsToCache = [
  '/admin',
  '/css/custom-style.css',
  '/dist/css/adminlte.min.css'
];

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => cache.addAll(urlsToCache))
  );
});

self.addEventListener('fetch', event => {
  event.respondWith(
    caches.match(event.request)
      .then(response => response || fetch(event.request))
  );
});
