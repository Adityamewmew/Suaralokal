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

// Fetch — network-first for navigation, stale-while-revalidate for assets
self.addEventListener('fetch', (event) => {
    const { request } = event;

    // Skip non-GET requests
    if (request.method !== 'GET') return;

    // Navigation requests — network-first falling back to PWA shell
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request).catch(() => caches.match('/app/discovery') || caches.match('/'))
        );
        return;
    }

    // Dynamic asset caching for Vite builds, fonts, and images
    const url = new URL(request.url);
    const isAsset = url.pathname.startsWith('/build/') || 
                    url.pathname.startsWith('/images/') || 
                    request.destination === 'font' || 
                    request.destination === 'style' || 
                    request.destination === 'script';

    if (isAsset) {
        event.respondWith(
            caches.open(CACHE_NAME).then((cache) => {
                return cache.match(request).then((cached) => {
                    const fetchPromise = fetch(request).then((networkResponse) => {
                        if (networkResponse.status === 200) {
                            cache.put(request, networkResponse.clone());
                        }
                        return networkResponse;
                    }).catch(() => null);
                    return cached || fetchPromise;
                });
            })
        );
        return;
    }

    // Fallback cache-first for other requests
    event.respondWith(
        caches.match(request).then((cached) => cached || fetch(request))
    );
});
