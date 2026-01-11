const CACHE_NAME = "musiqueapprox-v1";
const STATIC_ASSETS = [
  "/frontend/assets/stylesheets/main.css",
  "/frontend/assets/stylesheets/reset.css",
  "/frontend/assets/stylesheets/layout.css",
  "/frontend/assets/javascripts/jquery.js",
  "/frontend/assets/player/jquery.jplayer.min.js",
  "/images/logo.png",
];

self.addEventListener("install", (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(STATIC_ASSETS);
    }),
  );
});

self.addEventListener("fetch", (event) => {
  const requestUrl = new URL(event.request.url);

  // Network First for HTML and JSON (content)
  if (
    event.request.headers.get("Accept").includes("text/html") ||
    requestUrl.pathname.startsWith("/posts/")
  ) {
    event.respondWith(
      fetch(event.request)
        .then((response) => {
          const responseToCache = response.clone();
          caches.open(CACHE_NAME).then((cache) => {
            if (event.request.method === "GET") {
              cache.put(event.request, responseToCache);
            }
          });
          return response;
        })
        .catch(() => {
          return caches.match(event.request);
        }),
    );
    return;
  }

  // Cache First for everything else (static assets)
  event.respondWith(
    caches.match(event.request).then((response) => {
      return (
        response ||
        fetch(event.request).then((response) => {
          const responseToCache = response.clone();
          caches.open(CACHE_NAME).then((cache) => {
            if (event.request.method === "GET") {
              cache.put(event.request, responseToCache);
            }
          });
          return response;
        })
      );
    }),
  );
});

self.addEventListener("activate", (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => {
          if (cacheName !== CACHE_NAME) {
            return caches.delete(cacheName);
          }
        }),
      );
    }),
  );
});
