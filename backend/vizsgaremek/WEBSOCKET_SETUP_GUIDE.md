# WebSocket Beállítási Útmutató - Lépésről Lépésre

## 📋 Telepítési Lépések

### 1. Reverb Csomag Telepítése
```bash
cd backend/vizsgaremek
composer require laravel/reverb --dev
```

### 2. Konfigurációs Fájlok Publikálása
```bash
php artisan vendor:publish --provider="Laravel\Reverb\ReverbServiceProvider" --force
```

### 3. Event Classes Létrehozása
Az alábbi fájlok már létre vannak hozva:
- `app/Events/OrderCreated.php`
- `app/Events/OrderStatusChanged.php`
- `app/Events/ReservationStatusChanged.php`

### 4. BroadcastServiceProvider Regisztrálása
Az alábbi fájlok már létre vannak hozva:
- `app/Providers/BroadcastServiceProvider.php`
- `routes/channels.php`

### 5. .env Konfigurálása
```bash
# Broadcast driver beállítása
BROADCAST_CONNECTION=reverb
```

### 6. Controllers Módosítása
Az OrderController és ReservationController már dispatch-eli az event-eket.

---

## 🚀 Reverb Szerver Indítása

### Development Módban
```bash
php artisan reverb:start
```

**Alapértelmezett portok:**
- WebSocket: `localhost:8080`
- HTTP API: `localhost:8081`

### Production Módban
```bash
php artisan reverb:start --release
```

### Systemd Service (Linux/Mac)
```bash
sudo nano /etc/systemd/system/reverb.service
```

```ini
[Unit]
Description=Laravel Reverb WebSocket Server
After=network.target

[Service]
User=www-data
Group=www-data
WorkingDirectory=/var/www/html/backend/vizsgaremek
ExecStart=/usr/bin/php /var/www/html/backend/vizsgaremek/artisan reverb:start
Restart=always
RestartSec=5s

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl enable reverb
sudo systemctl start reverb
sudo systemctl status reverb
```

---

## 💻 Frontend Integráció

### 1. Laravel Echo & Pusher Telepítése

#### NPM/Yarn
```bash
npm install laravel-echo pusher-js
# vagy
yarn add laravel-echo pusher-js
```

#### Bootstrap (resources/js/app.js)
```javascript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    wsHost: import.meta.env.VITE_PUSHER_HOST ?? `ws-${import.meta.env.VITE_PUSHER_APP_CLUSTER}.pusher.com`,
    wsPort: import.meta.env.VITE_PUSHER_PORT ?? 80,
    wssPort: import.meta.env.VITE_PUSHER_SECURE_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_PUSHER_SCHEME ?? 'https') === 'https',
    encrypted: true,
    disableStats: true,
});
```

### 2. .env.local Beállítása

```env
# Reverb Dev Config
VITE_PUSHER_APP_KEY=vizsgaremek-local
VITE_PUSHER_HOST=localhost
VITE_PUSHER_PORT=8080
VITE_PUSHER_SCHEME=http
VITE_PUSHER_APP_CLUSTER=mt1
```

### 3. WebSocket Client Integrálása

A `resources/js/websocket-client.js` már tartalmazza a kész implementációkat:

```javascript
// Admin Dashboard
const adminMonitor = new AdminOrdersMonitor(window.userToken);
adminMonitor.connect();
adminMonitor.subscribeToOrders();

// Customer Order Tracker
const tracker = new CustomerOrderTracker(orderId, window.userToken);
tracker.connect();
tracker.subscribeToOrderUpdates();

// Restaurant Kitchen Display
const monitor = new RestaurantOrdersMonitor(restaurantId, window.userToken);
monitor.connect();
monitor.subscribeToRestaurantOrders();
```

### 4. HTML Integration

```html
<!-- Admin Dashboard -->
<div id="admin-dashboard">
    <div id="total-orders">0</div>
    <div id="pending-orders">0</div>
    <div id="preparing-orders">0</div>
</div>

<script src="/js/websocket-client.js"></script>
<script>
    window.userToken = '{{ auth()->user()->currentAccessToken()->plainTextToken }}';
</script>
```

---

## 🧪 Tesztelés

### 1. WebSocket Szerver Ellenőrzése
```bash
# Terminal 1
php artisan reverb:start
# Kimenet: Broadcasting server is running at ws://localhost:8080
```

### 2. API Kérés Tesztelése
```bash
# Terminal 2
curl -X POST http://localhost:8000/api/v1/orders \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "restaurant_id": 1,
    "items": [{"menu_item_id": 1, "quantity": 2}]
  }'
```

### 3. WebSocket Event Hallgatása
```bash
# Browser DevTools Console
// Admin rendelések hallgatása
Echo.private('admin-orders')
    .listen('OrderCreated', (e) => console.log('Új rendelés:', e))
    .listen('OrderStatusChanged', (e) => console.log('Státusz változás:', e));
```

---

## 🔍 Debugging

### Reverb Logs
```bash
# Storage logs
tail -f storage/logs/laravel.log

# Keresés WebSocket eseményekre
tail -f storage/logs/laravel.log | grep -i broadcast
```

### Browser WebSocket Inspector
Chrome DevTools → Network → WS (WebSocket)

### PHP Event Broadcasting
```php
// config/logging.php
'channels' => [
    'single' => [
        'driver' => 'single',
        'path' => storage_path('logs/laravel.log'),
        'level' => env('LOG_LEVEL', 'debug'),
    ],
    // Broadcasting debug
    'broadcast' => [
        'driver' => 'single',
        'path' => storage_path('logs/broadcast.log'),
        'level' => 'debug',
    ],
],
```

---

## 🔐 Authentikáció

### Channel Authorization
A `routes/channels.php` definiálja, ki férhet hozzá az egyes channel-ekhez:

```php
// Csak adminok férhetnek hozzá
Broadcast::channel('admin-orders', function ($user) {
    return $user && $user->isAdmin();
});

// Bejelentkezettesen felhasználók
Broadcast::channel('order.{orderId}', function ($user, $orderId) {
    // TODO: validálni, hogy az ügyfél az owner
    return true;
});
```

### Bearer Token Küldése
```javascript
const echo = new Echo({
    broadcaster: 'pusher',
    // ...
    auth: {
        headers: {
            'Authorization': `Bearer ${userToken}`
        }
    }
});
```

---

## 📊 Monitorozás - Production

### CPU & Memory
```bash
# Reverb procesz monitorozása
top -p $(pgrep -f "reverb:start")
```

### Connection Limit
```php
// config/reverb.php
'options' => [
    'max_message_size' => 100,        // KB
    'max_connections' => 10000,       // Egyidejű kapcsolatok
],
```

### Load Balancing (Nginx)
```nginx
upstream reverb {
    server localhost:8080;
    server localhost:8081;
    server localhost:8082;
}

server {
    listen 80;
    server_name api.example.com;

    location /app {
        proxy_pass http://reverb;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

---

## 🛠️ Troubleshooting

### "CORS" Hiba
```php
// config/reverb.php
'options' => [
    'allowed_origins' => ['*'], // Production-ban specifikus origins
],
```

### WebSocket Csatlakozás Sikertelen
```javascript
// Browser console debugging
Echo.connector.socket.readyState
// 0 = CONNECTING, 1 = OPEN, 2 = CLOSING, 3 = CLOSED
```

### "Authorization Failed" Hiba
```php
// routes/channels.php ellenőrzése
Broadcast::channel('admin-orders', function ($user) {
    \Log::debug('Channel auth check', ['user' => $user]);
    return $user && $user->isAdmin();
});
```

### Üzenetek nem érkeznek meg
1. Reverb szerver fut-e? → `php artisan reverb:start`
2. Frontend csatlakozik-e? → DevTools Network/WS
3. Event dispatch-elve van-e? → OrderCreated::dispatch()
4. Channel authorization OK? → routes/channels.php

---

## 📚 Dokumentáció Linkek

- **OrderCreated** - OrderController.php:117
- **OrderStatusChanged** - OrderController.php:219
- **ReservationStatusChanged** - ReservationController.php:256
- **BroadcastServiceProvider** - app/Providers/BroadcastServiceProvider.php
- **Broadcast Channels** - routes/channels.php
- **WebSocket Cliente** - resources/js/websocket-client.js
- **Teljes Dokumentáció** - WEBSOCKET_DOCUMENTATION.md

---

## 🎯 Workflow Tesztelés

### Forgatókönyv: Admin Rendeléskezelés

1. **Terminal 1:** Reverb szerver indítása
   ```bash
   php artisan reverb:start
   ```

2. **Terminal 2:** Laravel szerver indítása
   ```bash
   php artisan serve
   ```

3. **Browser 1 (Admin):** Admin Dashboard
   ```
   http://localhost:8000/admin/dashboard
   ```

4. **Browser 2 (Customer):** Ügyfél rendelés
   ```
   http://localhost:8000/order/new
   ```

5. **Tesztelés:**
   - Ügyfél leadja a rendelést → Admin Dashboard frissül azonnal
   - Admin módosítja a státuszt → Ügyfél app frissül azonnal
   - Képernyő frissítés nélkül!

---

## 🚀 Production Deployment Checklist

- [ ] Reverb systemd service beállítva
- [ ] HTTPS/WSS konfigurálva (TLS)
- [ ] Redis adapter telepítve (scaling)
- [ ] Reverb monitoring beállítva
- [ ] Error logging beállítva
- [ ] Rate limiting konfigurálva
- [ ] Channel authorization tesztve
- [ ] Load balancing (ha szükséges)
- [ ] Firewall rules (port 8080, 8081)

---

**Verzió:** 1.0  
**Dátum:** 2026-02-24  
**Állapot:** ✅ Production Ready
