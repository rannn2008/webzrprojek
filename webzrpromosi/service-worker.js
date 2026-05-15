const CACHE_VERSION = "zr-cache-v2";
const STATIC_CACHE = `${CACHE_VERSION}-static`;
const RUNTIME_CACHE = `${CACHE_VERSION}-runtime`;

const STATIC_ASSETS = [
    "./",
    "index.html",
    "manifest.json",
    "smart-ai-chatbot.js",
    "foto2/logo_zr.png",
    "foto2/logo_zr.webp",
    "foto2/herobackground.webp",
    "foto2/estelleroriginal.webp"
];

self.addEventListener("install", (event) => {
    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then((cache) => cache.addAll(STATIC_ASSETS))
            .then(() => self.skipWaiting())
    );
});

self.addEventListener("activate", (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(
                keys
                    .filter((key) => !key.startsWith(CACHE_VERSION))
                    .map((key) => caches.delete(key))
            ))
            .then(() => self.clients.claim())
    );
});

self.addEventListener("fetch", (event) => {
    const { request } = event;

    if (request.method !== "GET") return;

    const url = new URL(request.url);
    if (url.origin !== self.location.origin) return;

    if (request.mode === "navigate") {
        event.respondWith(networkFirst(request));
        return;
    }

    if (/\.(?:png|jpg|jpeg|webp|gif|svg|mp4|js|css|json)$/i.test(url.pathname)) {
        event.respondWith(staleWhileRevalidate(request));
    }
});

async function networkFirst(request) {
    try {
        const fresh = await fetch(request);
        const cache = await caches.open(RUNTIME_CACHE);
        cache.put(request, fresh.clone());
        return fresh;
    } catch (error) {
        const cached = await caches.match(request);
        return cached || caches.match("index.html");
    }
}

async function staleWhileRevalidate(request) {
    const cache = await caches.open(RUNTIME_CACHE);
    const cached = await cache.match(request);

    const fresh = fetch(request)
        .then((response) => {
            if (response.ok) cache.put(request, response.clone());
            return response;
        })
        .catch(() => cached);

    return cached || fresh;
}
