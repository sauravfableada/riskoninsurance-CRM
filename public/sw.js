// Basic Service Worker to satisfy Chrome PWA Installability Requirements
self.addEventListener('install', (event) => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(clients.claim());
});

self.addEventListener('fetch', (event) => {
    // Provide a basic offline fallback response to satisfy Chrome's PWA installability criteria
    event.respondWith(
        fetch(event.request).catch(() => {
            return new Response(
                'You are currently offline. Please check your internet connection to use the CRM.', 
                { status: 200, headers: { 'Content-Type': 'text/plain' } }
            );
        })
    );
});
