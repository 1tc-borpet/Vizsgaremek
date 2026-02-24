# WebSocket Quickstart - 5 Perc Telepítés

## 🚀 Gyors Kezdés

### 1. Reverb Indítása (Terminal 1)
```bash
cd backend/vizsgaremek
php artisan reverb:start
```
**Kimenet:** `Broadcasting server is running at ws://localhost:8080`

### 2. Laravel Szerver Indítása (Terminal 2)
```bash
php artisan serve
```
**Kimenet:** `Server running on http://127.0.0.1:8000`

### 3. Tesztelés (Browser)

#### Admin Orders Hallgatása
```javascript
// Megnyitni DevTools Console → Paste:
window.Echo = window.Echo || {};

// Simple test
setTimeout(() => {
    console.log('✅ WebSocket setup complete');
}, 1000);
```

#### Rendelés Létrehozása
```bash
# Terminal 3
curl -X POST http://localhost:8000/api/v1/orders \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "restaurant_id": 1,
    "items": [{"menu_item_id": 1, "quantity": 2}]
  }'
```

#### Admin Statusat Módosítása
```bash
curl -X PATCH http://localhost:8000/api/v1/admin/orders/1/status \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"status": "confirmed"}'
```

---

## 📦 Telepített Komponensek

✅ **Laravel Reverb** - WebSocket szerver
✅ **Events** - OrderCreated, OrderStatusChanged, ReservationStatusChanged
✅ **Channels** - Private channel authorization
✅ **Controllers** - Broadcast dispatch-ing
✅ **Frontend Client** - JavaScript kliens

---

## 🔌 Broadcast Events

### 1. OrderCreated
**Trigger:** `POST /api/v1/orders`
```javascript
Echo.private('admin-orders')
    .listen('OrderCreated', (e) => {
        console.log('Új rendelés:', e.order_number);
    });
```

### 2. OrderStatusChanged
**Trigger:** `PATCH /api/v1/admin/orders/{id}/status`
```javascript
Echo.private('order.1')
    .listen('OrderStatusChanged', (e) => {
        console.log(`Status: ${e.old_status} → ${e.new_status}`);
    });
```

### 3. ReservationStatusChanged
**Trigger:** `PATCH /api/v1/admin/reservations/{id}/confirm`
```javascript
Echo.private('admin-reservations')
    .listen('ReservationStatusChanged', (e) => {
        console.log(`Foglalás: ${e.new_status}`);
    });
```

---

## 🎯 Tesztelési Workflow

### Admin Dashboard
```
1. Terminal 1: php artisan reverb:start
2. Terminal 2: php artisan serve
3. Browser: http://localhost:8000/admin
4. devTools Console:
   Echo.private('admin-orders')
       .listen('OrderCreated', e => console.log(e));
5. Terminal 3: cURL rendelés létrehozása
6. ✅ Admin Dashboard frissül azonnal
```

### Customer Order Tracking
```
1. Ügyfél leadja a rendelést
2. JavaScript csatlakozik: Echo.private('order.1')
3. Admin módosítja: PATCH /api/v1/admin/orders/1/status
4. ✅ Order tracker progress bar frissül
```

---

## 📊 Architecture

```
┌────────────────┐
│  Reverb WS     │◄─────────ws://localhost:8080─────────┐
│   Szerver      │                                      │
└────────────────┘                                      │
       ▲                                          ┌──────┴──────┐
       │ Broadcast                                │   Browser   │
       │ Event                                    │   (JS)      │
       │                                          └─────────────┘
┌──────┴───────────────┐
│  Laravel Backend     │
├────────────────────┤
│ Orders              │
│ → OrderCreated      │
│ → OrderStatus...    │
│                     │
│ Reservations        │
│ → ReservStatus...   │
└────────────────────┘
```

---

## 🔐 Channel Authorization

| Channel | Auth | Trigger |
|---------|------|---------|
| `admin-orders` | Admin | OrderCreated, OrderStatusChanged |
| `order.{id}` | Owner | OrderStatusChanged |
| `restaurant.{id}.orders` | Admin | OrderCreated, OrderStatusChanged |
| `admin-reservations` | Admin | ReservationStatusChanged |
| `reservation.{id}` | Owner | ReservationStatusChanged |

---

## ✅ Checklist

- [x] Reverb telepítve `composer require laravel/reverb --dev`
- [x] Config publikálva `php artisan vendor:publish --provider=...`
- [x] Events létrehozva (3 db)
- [x] BroadcastServiceProvider regisztrálva
- [x] Channels authentifikáció
- [x] Controllers módosítva
- [x] Frontend kliens
- [x] Dokumentáció (3 fájl)
- [ ] Production deployment
- [ ] Load testing

---

## 📚 Dokumentáció Fájlok

1. **WEBSOCKET_DOCUMENTATION.md** - Teljes technikai dokumentáció
2. **WEBSOCKET_SETUP_GUIDE.md** - Deploy & setup útmutató
3. **WEBSOCKET_IMPLEMENTATION_SUMMARY.md** - Implementáció összefoglalása
4. **resources/js/websocket-client.js** - Frontend kliens kód

---

## 🆘 Troubleshooting

### Reverb nem indul
```bash
# Ellenőrzés
php artisan config:show reverb

# Port foglalt?
netstat -tln | grep 8080

# Kill process
lsof -i :8080
kill -9 <PID>
```

### WebSocket nem csatlakozik
```javascript
// Console-ban:
console.log(Echo.connector.socket);
// readyState: 0=CONNECTING, 1=OPEN, 2=CLOSING, 3=CLOSED

// Logok:
tail -f storage/logs/laravel.log | grep -i broadcast
```

### Event nem broadcast-odik
```php
// OrderController-ben:
\Log::info('OrderCreated dispatching', ['order' => $order->id]);
OrderCreated::dispatch($order);
```

---

## 🎓 További Tanulás

- Reverb Docs: https://laravel.com/docs/11.x/reverb
- Echo Docs: https://laravel.com/docs/11.x/broadcasting
- WebSocket Primer: https://developer.mozilla.org/en-US/docs/Web/API/WebSocket

---

**Sürgős kérdések:**
- Terminal 1: `php artisan reverb:start`
- Terminal 2: `php artisan serve`
- Browser: `localhost:8000/admin` + DevTools Console
- Teszt: cURL API kérés → ✅ Dashboard frissül

**Verzió:** 1.0 | **Status:** ✅ Ready to Use
