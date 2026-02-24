# WebSocket Valós Idejű Rendeléskezelés - Dokumentáció

## 📡 Áttekintés

A WebSocket implementáció valós idejű értesítéseket biztosít a rendelés és foglalás státusz változásokról. Az adminisztrátorok és ügyfelek azonnal értesülhetnek az eseményekről anélkül, hogy frissíteniük kellene az oldalt.

## 🛠️ Technológia Stack

- **Laravel Reverb** - WebSocket szerver (Laravel 12)
- **Sanctum** - Authentikáció
- **Private Channels** - Biztonságos kommunikáció
- **Events Broadcasting** - Event-driven architektúra

## 📦 Telepítésre volt szükség

```bash
composer require laravel/reverb --dev
php artisan vendor:publish --provider="Laravel\Reverb\ReverbServiceProvider" --force
```

## 🔌 WebSocket Szerver Indítása

```bash
# Reverb szerver indítása (development)
php artisan reverb:start

# Reverb szerver indítása release módban
php artisan reverb:start --release
```

**Alapértelmezett portok:**
- WebSocket: `localhost:8080`
- API: `localhost:8081`

## 📨 Implementált Events

### 1. OrderCreated Event
**Trigger:** Amikor új rendelés jön létre
**Channels:**
- `admin-orders` - Admin felhasználók
- `restaurant.{restaurantId}.orders` - Az étterem rendeléseire
**Adatok:**
```json
{
  "order_id": 1,
  "order_number": "ORD-1709042940-abc123",
  "status": "pending",
  "restaurant_id": 1,
  "user_id": 5,
  "user_name": "Kovács János",
  "total_amount": 2850.50,
  "type": "dine_in",
  "items_count": 3,
  "timestamp": "2026-02-24T10:30:00Z"
}
```

### 2. OrderStatusChanged Event
**Trigger:** Rendelés státusza megváltozik
**Channels:**
- `admin-orders` - Admin felhasználók
- `order.{orderId}` - Az ügyfél saját rendeléséről
- `restaurant.{restaurantId}.orders` - Az étterem rendeléseire
**Adatok:**
```json
{
  "order_id": 1,
  "order_number": "ORD-1709042940-abc123",
  "old_status": "pending",
  "new_status": "confirmed",
  "restaurant_id": 1,
  "user_id": 5,
  "total_amount": 2850.50,
  "timestamp": "2026-02-24T10:30:00Z"
}
```

### 3. ReservationStatusChanged Event
**Trigger:** Foglalás státusza megváltozik
**Channels:**
- `admin-reservations` - Admin felhasználók
- `reservation.{reservationId}` - A foglalást létrehozó felhasználó
- `restaurant.{restaurantId}.reservations` - Az étterem foglalásaira
**Adatok:**
```json
{
  "reservation_id": 1,
  "old_status": "pending",
  "new_status": "confirmed",
  "restaurant_id": 1,
  "user_id": 5,
  "table_id": 3,
  "reservation_time": "2026-02-25T19:00:00Z",
  "guest_count": 4,
  "timestamp": "2026-02-24T10:30:00Z"
}
```

## 🔐 Broadcast Channels Authentikáció

A `routes/channels.php` fájl definiálja a channel hozzáféréseket:

```php
// Csak adminok
Broadcast::channel('admin-orders', function ($user) {
    return $user && $user->isAdmin();
});

// Bármelyik bejelentkezettesen felhasználó (TODO: jobbá tenni)
Broadcast::channel('order.{orderId}', function ($user, $orderId) {
    return $user ? true : false;
});

// Csak adminok
Broadcast::channel('restaurant.{restaurantId}.orders', function ($user, $restaurantId) {
    return $user && $user->isAdmin();
});
```

## 📝 Konfigurációs Fájlok

### config/reverb.php
Az alapértelmezett Reverb konfiguráció az installálás után:
```php
return [
    'default' => 'reverb',
    'channels' => [
        'reverb' => [
            'driver' => 'reverb',
            'host' => env('REVERB_HOST', 'localhost'),
            'port' => env('REVERB_PORT', 8080),
            'scheme' => env('REVERB_SCHEME', 'http'),
            'app_id' => env('REVERB_APP_ID'),
            'app_key' => env('REVERB_APP_KEY'),
            'secret' => env('REVERB_APP_SECRET'),
        ],
    ],
];
```

### .env Beállítások
```
BROADCAST_CONNECTION=reverb
```

## 💻 Frontend Integráció (JavaScript)

### Reverb/Pusher kliens telepítése
```bash
npm install laravel-reverb
```

### WebSocket Subscribe - Admin Orders
```javascript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const echo = new Echo({
    broadcaster: 'pusher',
    key: process.env.REACT_APP_REVERB_APP_KEY,
    host: process.env.REACT_APP_REVERB_HOST,
    port: process.env.REACT_APP_REVERB_PORT,
    wsPath: '/app',
    wssPort: 443,
    forceTLS: (process.env.REACT_APP_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
});

// Admin: összes rendelés hallgatása
echo.private('admin-orders')
    .listen('OrderStatusChanged', (event) => {
        console.log('Rendelés státusza megváltozott:', event);
        // UI frissítés
    })
    .listen('OrderCreated', (event) => {
        console.log('Új rendelés érkezett:', event);
        // UI frissítés
    });
```

### WebSocket Subscribe - Saját Rendelés
```javascript
// Ügyfél: saját rendelésének követése
echo.private(`order.${orderId}`)
    .listen('OrderStatusChanged', (event) => {
        console.log('Az Ön rendelésének státusza:', event.new_status);
        // Felhasználónak szóló értesítés
    });
```

### WebSocket Subscribe - Étterem Rendelések
```javascript
// Étterem alkalmazott: az étterem rendeléseinek követése
echo.private(`restaurant.${restaurantId}.orders`)
    .listen('OrderCreated', (event) => {
        console.log('Új rendelés az étteremhez:', event);
        // Dashboard frissítés
    })
    .listen('OrderStatusChanged', (event) => {
        console.log('Rendelés státusza:', event.new_status);
        // UI frissítés
    });
```

## 🎯 Backend Event Dispatch

### OrderCreated Dispatch
```php
// OrderController.php - store() metódus
use App\Events\OrderCreated;

OrderCreated::dispatch($order->load('items.menuItem'));
```

### OrderStatusChanged Dispatch
```php
// OrderController.php - updateStatus() metódus
use App\Events\OrderStatusChanged;

$oldStatus = $order->status;
$order->update(['status' => $newStatus]);
OrderStatusChanged::dispatch($order, $oldStatus, $newStatus);
```

### ReservationStatusChanged Dispatch
```php
// ReservationController.php - confirm() metódus
use App\Events\ReservationStatusChanged;

$oldStatus = $reservation->status;
$reservation->update(['status' => 'confirmed']);
ReservationStatusChanged::dispatch($reservation, $oldStatus, 'confirmed');
```

## 🧪 Tesztelés

### WebSocket Events Testing
```php
// tests/Feature/WebSocketTest.php
use App\Events\OrderCreated;
use App\Events\OrderStatusChanged;
use Illuminate\Support\Facades\Event;

public function test_order_created_broadcasts()
{
    Event::fake();
    
    // Rendelés létrehozása
    $response = $this->postJson('/api/v1/orders', [
        'restaurant_id' => 1,
        'items' => [
            ['menu_item_id' => 1, 'quantity' => 2]
        ]
    ]);
    
    Event::assertDispatched(OrderCreated::class);
}
```

## 🔄 Valós Idejű Workflow

### Admin Dashboard - Rendelések
1. Admin megnyitja az admin panelt
2. WebSocket csatlakozik `admin-orders` channelhez
3. Új rendelés érkezik → OrderCreated event broadcast
4. Admin Dashboard frissül (nincs szükség refresh-re)
5. Admin megerősíti a rendelést → PATCH /api/v1/admin/orders/1/status
6. OrderStatusChanged event broadcast → `admin-orders` + `order.{id}`
7. Admin Dashboard & Customer app frissül

### Customer App - Rendelés Követés
1. Ügyfél leadja a rendelést
2. App csatlakozik `order.{orderId}` channelhez
3. Adminisztrátor frissíti a státuszt
4. OrderStatusChanged event érkezik
5. Ügyfél app frissül valós időben

## 📊 Monitorozás & Debugging

### Reverb Logs
```bash
# Terminal 1: Reverb szerver
php artisan reverb:start

# Terminal 2: Log monitoring
tail -f storage/logs/laravel.log | grep -i reverb
```

### WebSocket Connector Debug
```php
// Debugging helper
if (env('APP_DEBUG')) {
    echo "WebSocket Status: Connected";
}
```

## 🚀 Production Deployment

### Redis Adapter (Scaling)
```bash
composer require predis/predis
```

### config/reverb.php (Production)
```php
'channels' => [
    'reverb' => [
        'driver' => 'reverb',
        'host' => env('REVERB_HOST', '0.0.0.0'),
        'port' => env('REVERB_PORT', 8080),
        'scheme' => env('REVERB_SCHEME', 'https'),
        'app_id' => env('REVERB_APP_ID'),
        'app_key' => env('REVERB_APP_KEY'),
        'secret' => env('REVERB_APP_SECRET'),
    ],
],
```

### .env (Production)
```
REVERB_HOST=0.0.0.0
REVERB_PORT=8080
REVERB_SCHEME=https
BROADCAST_CONNECTION=reverb
```

### Systemd Service File
```ini
[Unit]
Description=Laravel Reverb WebSocket Server
After=network.target

[Service]
User=www-data
WorkingDirectory=/var/www/html/vizsgaremek
ExecStart=/usr/bin/php artisan reverb:start --host=0.0.0.0
Restart=always

[Install]
WantedBy=multi-user.target
```

## 🔗 Kapcsolódó Endpoints

Az ezek az API endpointok trigger-elik a WebSocket eventeket:

```
POST   /api/v1/orders                    → OrderCreated broadcast
PATCH  /api/v1/admin/orders/{id}/status → OrderStatusChanged broadcast
PATCH  /api/v1/admin/reservations/{id}/confirm → ReservationStatusChanged broadcast
```

## ⚠️ Ismert Korlátozások

- ❌ Üzenet history nem kerül tárolásra (connection után új üzenetek)
- ❌ Offline mode nem támogatott (sync-re van szükség)
- ❌ File-based session nem működik cluster módban

## 🎓 További Recursos

- [Laravel Reverb Dokumentáció](https://laravel.com/docs/11.x/reverb)
- [Pusher protokoll](https://pusher.com/docs/channels/library_auth_reference/auth-signatures/)
- [Echo JavaScript Library](https://laravel.com/docs/11.x/broadcasting#client-side-installation)
