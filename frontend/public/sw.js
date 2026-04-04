self.addEventListener('install', (event) => {
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(self.clients.claim());
});

self.addEventListener('fetch', (event) => {
  const request = event.request;
  const url = new URL(request.url);

  if (
    request.method !== 'GET'
    || url.origin !== self.location.origin
    || url.pathname.startsWith('/api/')
    || url.pathname === '/sw.js'
    || url.pathname === '/manifest.webmanifest'
  ) {
    return;
  }

  event.respondWith(
    caches.open('cqrlog-runtime-v1').then(async (cache) => {
      const cachedResponse = await cache.match(request);

      try {
        const networkResponse = await fetch(request);

        if (networkResponse.ok && request.destination !== 'document') {
          cache.put(request, networkResponse.clone());
        }

        return networkResponse;
      } catch (error) {
        if (cachedResponse) {
          return cachedResponse;
        }

        throw error;
      }
    }),
  );
});
