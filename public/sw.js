/* Talashow SW (simple cache + video warm-cache) */
const CACHE_NAME = 'talashow-v8';
const OFFLINE_URL = '/offline.html';

// Cache séparé pour les vidéos (HLS) — attention quota navigateur.
const VIDEO_CACHE = 'talashow-video-v1';
const VIDEO_MAX_ENTRIES = 80; // garde-fou (LRU approximatif)

// Ne pas précacher '/' : HTML localisé (FR/EN) — sinon la langue “colle”
const PRECACHE = [
  OFFLINE_URL,
  '/manifest.json',
  '/icons/icon-192.png',
  '/icons/icon-512.png'
];

function isVideoHost(hostname) {
  // Bunny CDN + player (segments HLS)
  return (
    hostname.endsWith('.b-cdn.net') ||
    hostname.endsWith('bunnycdn.com') ||
    hostname.endsWith('mediadelivery.net')
  );
}

function isHlsLike(pathname) {
  return (
    pathname.endsWith('.m3u8') ||
    pathname.endsWith('.ts') ||
    pathname.endsWith('.m4s') ||
    pathname.endsWith('.mp4') ||
    pathname.endsWith('.cmfv') ||
    pathname.endsWith('.cmfa')
  );
}

function isSensitiveAppPath(pathname, method) {
  if (method !== 'GET') return true;
  const p = pathname;
  return (
    p.startsWith('/talashow-admin') ||
    p.startsWith('/login') ||
    p.startsWith('/register') ||
    p.startsWith('/verify-otp') ||
    p.startsWith('/forgot-password') ||
    p.startsWith('/reset-password') ||
    p.startsWith('/payment') ||
    p.startsWith('/webhooks') ||
    p.startsWith('/api') ||
    p.startsWith('/profile') ||
    p.startsWith('/auth/') ||
    p.startsWith('/lang/')
  );
}

function isDocumentRequest(req) {
  if (req.mode === 'navigate') return true;
  const accept = req.headers.get('accept') || '';
  return accept.includes('text/html');
}

async function limitCacheEntries(cacheName, maxEntries) {
  try {
    const cache = await caches.open(cacheName);
    const keys = await cache.keys();
    if (keys.length <= maxEntries) return;
    const toDelete = keys.length - maxEntries;
    for (let i = 0; i < toDelete; i++) {
      await cache.delete(keys[i]);
    }
  } catch (_) {
    // no-op
  }
}

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => cache.addAll(PRECACHE)).then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(
        keys
          .filter((k) => k !== CACHE_NAME && k !== VIDEO_CACHE)
          .map((k) => caches.delete(k))
      )
    ).then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (event) => {
  const req = event.request;
  const url = new URL(req.url);

  // --- Video warm-cache (HLS / CDN) ---
  if (
    req.method === 'GET' &&
    isVideoHost(url.hostname) &&
    isHlsLike(url.pathname) &&
    !req.headers.has('range')
  ) {
    const isManifest = url.pathname.endsWith('.m3u8');
    event.respondWith((async () => {
      const cache = await caches.open(VIDEO_CACHE);

      if (!isManifest) {
        const cached = await cache.match(req);
        if (cached) return cached;
      }

      try {
        const res = await fetch(req);
        if (res && (res.ok || res.type === 'opaque')) {
          cache.put(req, res.clone());
          limitCacheEntries(VIDEO_CACHE, VIDEO_MAX_ENTRIES);
        }
        return res;
      } catch (e) {
        const cached = await cache.match(req);
        if (cached) return cached;
        return Response.error();
      }
    })());
    return;
  }

  // Only handle same-origin (reste de l'app)
  if (url.origin !== self.location.origin) return;

  if (isSensitiveAppPath(url.pathname, req.method)) {
    event.respondWith(
      fetch(req).catch(() => Response.error())
    );
    return;
  }

  // Network-first for HTML / navigate / prefetch document — jamais de cache HTML localisé
  if (isDocumentRequest(req)) {
    event.respondWith(
      fetch(req, { cache: 'no-store' })
        .then((res) => res)
        .catch(async () => (await caches.match(OFFLINE_URL)) || Response.error())
    );
    return;
  }

  // Cache-first for assets (safe catch — évite Uncaught TypeError Failed to fetch)
  event.respondWith(
    caches.match(req).then(async (cached) => {
      if (cached) return cached;
      try {
        const res = await fetch(req);
        if (res && res.ok) {
          const copy = res.clone();
          caches.open(CACHE_NAME).then((cache) => cache.put(req, copy));
        }
        return res;
      } catch (_) {
        return Response.error();
      }
    })
  );
});
