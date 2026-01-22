const CACHE_NAME = 'collection-tracking-v1';
const urlsToCache = [
  '/',
  '/index.php',
  '/login.php',
  '/css/style.css',
  '/js/app.js',
  '/manifest.json',
  '/admin/dashboard.php',
  '/admin/assignments.php',
  '/admin/agents.php',
  '/admin/store_data.php',
  '/admin/management.php',
  '/agent/dashboard.php',
  '/agent/store.php',
  '/agent/submissions.php'
];

// Install event - cache resources
self.addEventListener('install', function(event) {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(function(cache) {
        console.log('Opened cache');
        return cache.addAll(urlsToCache);
      })
  );
});

// Fetch event - serve cached resources when offline
self.addEventListener('fetch', function(event) {
  event.respondWith(
    caches.match(event.request)
      .then(function(response) {
        // Return cached version or fetch from network
        return response || fetch(event.request);
      }
    )
  );
});

// Activate event - clean up old caches
self.addEventListener('activate', function(event) {
  event.waitUntil(
    caches.keys().then(function(cacheNames) {
      return Promise.all(
        cacheNames.map(function(cacheName) {
          if (cacheName !== CACHE_NAME) {
            console.log('Deleting old cache:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
});

// Background sync for data synchronization
self.addEventListener('sync', function(event) {
  if (event.tag === 'sync-data') {
    event.waitUntil(syncData());
  }
});

// Function to sync data when back online
function syncData() {
  return new Promise(function(resolve, reject) {
    // Get pending data from IndexedDB or localStorage
    const pendingData = JSON.parse(localStorage.getItem('pending_sync_data')) || [];
    
    if (pendingData.length > 0) {
      // Process each pending item
      pendingData.forEach((item, index) => {
        // Send data to server
        fetch(item.url, {
          method: item.method,
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify(item.data)
        })
        .then(response => {
          if (response.ok) {
            // Remove synced item from pending data
            pendingData.splice(index, 1);
            localStorage.setItem('pending_sync_data', JSON.stringify(pendingData));
            console.log('Data synced successfully:', item);
          } else {
            console.error('Sync failed for item:', item);
          }
        })
        .catch(error => {
          console.error('Sync error:', error);
        });
      });
      
      resolve();
    } else {
      resolve();
    }
  });
}