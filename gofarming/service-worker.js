const CACHE = 'gofarming-v4';

const ARQUIVOS = [
  'FRONT/dashboard.html',
  'FRONT/scan.html',
  'FRONT/planta.html',
  'FRONT/login.html',
  'FRONT/cadastro.html',
  'FRONT/css/style.css',
  'FRONT/js/api.js',
  'FRONT/js/dashboard.js',
  'FRONT/js/scan.js',
  'FRONT/js/planta.js',
  'FRONT/js/auth.js'
].map(p => new URL(p, self.registration.scope).href);

self.addEventListener('install', e => {
  e.waitUntil((async () => {
    const cache = await caches.open(CACHE);
    await Promise.allSettled(ARQUIVOS.map(async a => {
      const r = await fetch(a, { cache: 'reload' });
      if (r.ok) await cache.put(a, r);
    }));
    await self.skipWaiting();
  })());
});

self.addEventListener('activate', e => {
  e.waitUntil((async () => {
    const keys = await caches.keys();
    await Promise.all(keys.filter(k => k !== CACHE).map(k => caches.delete(k)));
    await self.clients.claim();
  })());
});

self.addEventListener('fetch', e => {
  const req = e.request;
  if (req.method !== 'GET') return;

  const url = new URL(req.url);
  if (url.origin !== location.origin) return;          // ignora CDN/externos
  if (!url.protocol.startsWith('http')) return;        // ignora chrome-extension etc.

  const isApi = url.pathname.includes('/PUBLIC/') || url.pathname.endsWith('.php');
  const isHtml = req.mode === 'navigate' || url.pathname.endsWith('.html');

  // API e páginas: rede primeiro, cache só como reserva
  if (isApi || isHtml) {
    e.respondWith((async () => {
      try {
        const res = await fetch(req);
        if (!isApi && res.ok) (await caches.open(CACHE)).put(req, res.clone());
        return res;
      } catch {
        return (await caches.match(req)) || Response.error();
      }
    })());
    return;
  }

  // CSS/JS/imagens: cache primeiro
  e.respondWith((async () => {
    const cached = await caches.match(req);
    if (cached) return cached;
    const res = await fetch(req);
    if (res.ok) (await caches.open(CACHE)).put(req, res.clone());
    return res;
  })());
});
