// SuaraLokal Service Worker — PWA shell caching
const CACHE_NAME = 'suaralokal-v1';
const PRECACHE_URLS = [
    '/',
    '/manifest.webmanifest',
    '/images/logo-light.png',
    '/images/logo-dark.png',
];

// Install — precache core static assets
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => cache.addAll(PRECACHE_URLS))
    );
    self.skipWaiting();
});

// Activate — clean up old caches
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(keys.filter((k) => k !== CACHE_NAME).map((k) => caches.delete(k)))
        )
    );
    self.clients.claim();
});

// Fetch — network-first for navigation, cache-first for static assets
self.addEventListener('fetch', (event) => {
    const { request } = event;

    // Skip non-GET requests
    if (request.method !== 'GET') return;

    // Navigation requests — network-first
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request).catch(() => caches.match('/'))
        );
        return;
    }

    // Static assets — cache-first
    event.respondWith(
        caches.match(request).then((cached) => cached || fetch(request))
    );
});
