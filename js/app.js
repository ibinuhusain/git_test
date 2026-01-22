// Client-side JavaScript for Apparels Collection App

// Function to handle offline data synchronization
function syncOfflineData() {
    // In a real implementation, this would sync data when connection is restored
    console.log('Checking for offline data to sync...');
    
    // Check if we have any pending data to sync
    const pendingData = JSON.parse(localStorage.getItem('pending_sync_data')) || [];
    
    if (pendingData.length > 0 && navigator.onLine) {
        console.log(`Found ${pendingData.length} items to sync`);
        
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
    }
}

// Function to queue data for sync when offline
function queueForSync(url, method, data) {
    let pendingData = JSON.parse(localStorage.getItem('pending_sync_data')) || [];
    pendingData.push({
        url: url,
        method: method,
        data: data,
        timestamp: new Date().toISOString()
    });
    localStorage.setItem('pending_sync_data', JSON.stringify(pendingData));
    console.log('Data queued for sync:', {url, method, data});
}

// Function to periodically save data locally (every 5 minutes as requested)
function saveLocalBackup() {
    // Save important data to localStorage
    const userData = {
        timestamp: new Date().toISOString(),
        // In a real app, we would save form data, collected amounts, etc.
    };
    
    localStorage.setItem('local_backup', JSON.stringify(userData));
    console.log('Local backup saved at: ' + new Date().toLocaleTimeString());
}

// Initialize the app
document.addEventListener('DOMContentLoaded', function() {
    // Set up periodic local backup (every 5 minutes = 300000 ms)
    setInterval(saveLocalBackup, 300000);
    
    // Set up periodic sync check (every 5 minutes = 300000 ms)
    setInterval(syncOfflineData, 300000);
    
    // Initial backup
    saveLocalBackup();
    
    // Initial sync check
    syncOfflineData();
    
    // Set up offline/online status indicators
    window.addEventListener('online', function() {
        console.log('Connection restored. Syncing data...');
        syncOfflineData();
    });
    
    window.addEventListener('offline', function() {
        console.log('Offline mode detected.');
        // Show offline indicator to user
        const offlineIndicator = document.createElement('div');
        offlineIndicator.id = 'offline-indicator';
        offlineIndicator.innerHTML = '⚠️ Offline Mode Active';
        offlineIndicator.style.cssText = `
            position: fixed;
            top: 10px;
            right: 10px;
            background: #ff9800;
            color: white;
            padding: 10px 15px;
            border-radius: 4px;
            z-index: 9999;
            font-weight: bold;
        `;
        
        document.body.appendChild(offlineIndicator);
    });
    
    // Remove offline indicator when online
    window.addEventListener('online', function() {
        const indicator = document.getElementById('offline-indicator');
        if (indicator) {
            indicator.remove();
        }
    });
    
    // Auto-refresh assignment list every few minutes if needed
    if (window.location.href.includes('dashboard.php')) {
        // Refresh page every 10 minutes to get latest assignments
        setInterval(function() {
            location.reload();
        }, 600000); // 10 minutes
    }
});

// Function to handle image previews for file inputs
function previewImages(input, containerId) {
    const container = document.getElementById(containerId);
    container.innerHTML = '';
    
    if (input.files) {
        for (let i = 0; i < input.files.length; i++) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                const img = document.createElement('img');
                img.src = e.target.result;
                img.style.cssText = `
                    width: 100px;
                    height: 100px;
                    object-fit: cover;
                    border-radius: 4px;
                    margin: 5px;
                    border: 1px solid #ddd;
                `;
                
                container.appendChild(img);
            }
            
            reader.readAsDataURL(input.files[i]);
        }
    }
}

// Password protection for sensitive data (conceptual)
function encryptData(data, password) {
    // In a real implementation, we would use proper encryption
    // This is just a placeholder for the concept
    console.log('Data would be encrypted with password:', password);
    return btoa(JSON.stringify(data)); // Base64 encoding as placeholder
}

function decryptData(encryptedData, password) {
    // In a real implementation, we would properly decrypt
    try {
        return JSON.parse(atob(encryptedData));
    } catch (e) {
        console.error('Failed to decrypt data');
        return null;
    }
}

// Auto-delete old local data (older than 2 days)
function cleanupOldData() {
    const twoDaysAgo = new Date();
    twoDaysAgo.setDate(twoDaysAgo.getDate() - 2);
    
    // Check stored backups and remove if older than 2 days
    const backup = localStorage.getItem('local_backup');
    if (backup) {
        const backupObj = JSON.parse(backup);
        const backupTime = new Date(backupObj.timestamp);
        
        if (backupTime < twoDaysAgo) {
            localStorage.removeItem('local_backup');
            console.log('Old backup data removed');
        }
    }
    
    // Also cleanup old pending sync data
    const pendingData = JSON.parse(localStorage.getItem('pending_sync_data')) || [];
    const filteredPendingData = pendingData.filter(item => {
        const itemTime = new Date(item.timestamp);
        return itemTime >= twoDaysAgo; // Keep only recent items
    });
    
    if (filteredPendingData.length !== pendingData.length) {
        localStorage.setItem('pending_sync_data', JSON.stringify(filteredPendingData));
        console.log('Cleaned up old pending sync data');
    }
}

// Run cleanup when app loads
cleanupOldData();