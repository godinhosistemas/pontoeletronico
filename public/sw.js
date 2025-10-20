const CACHE_NAME = 'ponto-digital-v1';
const urlsToCache = [
  '/',
  '/pwa/clock',
  '/manifest.json',
  '/build/assets/app.css',
  '/build/assets/app.js',
  '/images/icon-192x192.png',
  '/images/icon-512x512.png'
];

// Instala o service worker e cacheia os recursos
self.addEventListener('install', event => {
  console.log('[Service Worker] Instalando...');
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        console.log('[Service Worker] Cacheando recursos');
        return cache.addAll(urlsToCache);
      })
      .catch(err => {
        console.log('[Service Worker] Erro ao cachear:', err);
      })
  );
  self.skipWaiting();
});

// Ativa o service worker e limpa caches antigos
self.addEventListener('activate', event => {
  console.log('[Service Worker] Ativando...');
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (cacheName !== CACHE_NAME) {
            console.log('[Service Worker] Removendo cache antigo:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
  return self.clients.claim();
});

// Intercepta requisições e serve do cache quando possível
self.addEventListener('fetch', event => {
  // Ignora requisições que não são GET
  if (event.request.method !== 'GET') {
    return;
  }

  // Ignora requisições de API para sempre buscar dados atualizados
  if (event.request.url.includes('/api/') || event.request.url.includes('/livewire/')) {
    return fetch(event.request);
  }

  event.respondWith(
    caches.match(event.request)
      .then(response => {
        // Cache hit - retorna resposta do cache
        if (response) {
          return response;
        }

        // Clone da requisição
        const fetchRequest = event.request.clone();

        return fetch(fetchRequest).then(response => {
          // Verifica se recebeu resposta válida
          if (!response || response.status !== 200 || response.type !== 'basic') {
            return response;
          }

          // Clone da resposta
          const responseToCache = response.clone();

          caches.open(CACHE_NAME)
            .then(cache => {
              cache.put(event.request, responseToCache);
            });

          return response;
        });
      })
      .catch(() => {
        // Se falhar, tenta servir uma página offline
        return caches.match('/offline.html');
      })
  );
});

// Sincronização em background para enviar pontos offline
self.addEventListener('sync', event => {
  if (event.tag === 'sync-clock-entries') {
    console.log('[Service Worker] Sincronizando registros de ponto...');
    event.waitUntil(syncClockEntries());
  }
});

async function syncClockEntries() {
  try {
    // Busca registros pendentes do IndexedDB
    const db = await openDatabase();
    const pendingEntries = await getPendingEntries(db);

    for (const entry of pendingEntries) {
      try {
        const response = await fetch('/api/clock/sync', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify(entry)
        });

        if (response.ok) {
          // Remove do IndexedDB após sucesso
          await removeEntry(db, entry.id);
        }
      } catch (error) {
        console.error('[Service Worker] Erro ao sincronizar registro:', error);
      }
    }
  } catch (error) {
    console.error('[Service Worker] Erro na sincronização:', error);
  }
}

// Helpers para IndexedDB
function openDatabase() {
  return new Promise((resolve, reject) => {
    const request = indexedDB.open('PontoDigitalDB', 1);

    request.onerror = () => reject(request.error);
    request.onsuccess = () => resolve(request.result);

    request.onupgradeneeded = event => {
      const db = event.target.result;
      if (!db.objectStoreNames.contains('pendingEntries')) {
        db.createObjectStore('pendingEntries', { keyPath: 'id', autoIncrement: true });
      }
    };
  });
}

function getPendingEntries(db) {
  return new Promise((resolve, reject) => {
    const transaction = db.transaction(['pendingEntries'], 'readonly');
    const objectStore = transaction.objectStore('pendingEntries');
    const request = objectStore.getAll();

    request.onerror = () => reject(request.error);
    request.onsuccess = () => resolve(request.result);
  });
}

function removeEntry(db, id) {
  return new Promise((resolve, reject) => {
    const transaction = db.transaction(['pendingEntries'], 'readwrite');
    const objectStore = transaction.objectStore('pendingEntries');
    const request = objectStore.delete(id);

    request.onerror = () => reject(request.error);
    request.onsuccess = () => resolve();
  });
}

// Notificação push
self.addEventListener('push', event => {
  const data = event.data ? event.data.json() : {};
  const title = data.title || 'Ponto Digital';
  const options = {
    body: data.body || 'Você tem uma nova notificação',
    icon: '/images/icon-192x192.png',
    badge: '/images/icon-96x96.png',
    vibrate: [200, 100, 200],
    data: data.url || '/',
    actions: [
      {
        action: 'open',
        title: 'Abrir'
      },
      {
        action: 'close',
        title: 'Fechar'
      }
    ]
  };

  event.waitUntil(
    self.registration.showNotification(title, options)
  );
});

// Clique na notificação
self.addEventListener('notificationclick', event => {
  event.notification.close();

  if (event.action === 'open' || !event.action) {
    event.waitUntil(
      clients.openWindow(event.notification.data)
    );
  }
});
