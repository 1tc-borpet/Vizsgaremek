/**
 * WebSocket Client Examples - Laravel Reverb
 * WebSocket szerver: php artisan reverb:start
 */

// ============================================
// 1. Admin Dashboard - Összes Rendelés Monitoring
// ============================================

class AdminOrdersMonitor {
    constructor(token) {
        this.token = token;
        this.echo = null;
        this.orders = [];
    }

    /**
     * WebSocket kapcsolódás inicializálása
     */
    connect() {
        // Reverb Echo kliens beállítása
        this.echo = new Echo({
            broadcaster: 'pusher',
            key: 'vizsgaremek-local',
            wsHost: 'localhost',
            wsPort: 8080,
            wssPort: 443,
            forceTLS: false,
            encrypted: false,
            disableStats: true,
            enabledTransports: ['ws'],
            auth: {
                headers: {
                    'Authorization': `Bearer ${this.token}`
                }
            }
        });

        console.log('Admin WebSocket csatlakozás inicializálva');
    }

    /**
     * Összes rendelés hallgatása
     */
    subscribeToOrders() {
        this.echo
            .private('admin-orders')
            .listen('OrderCreated', (event) => {
                console.log('📦 Új rendelés érkezett:', event);
                this.orders.push(event);
                this.displayOrderNotification(event, 'Új rendelés!');
                this.updateDashboard();
            })
            .listen('OrderStatusChanged', (event) => {
                console.log('🔄 Rendelés státusza megváltozott:', event);
                this.updateOrderStatus(event);
                this.displayStatusNotification(event);
                this.updateDashboard();
            });
    }

    /**
     * Rendelés státuszának frissítése a listában
     */
    updateOrderStatus(event) {
        const orderIndex = this.orders.findIndex(o => o.order_id === event.order_id);
        if (orderIndex !== -1) {
            this.orders[orderIndex].status = event.new_status;
        }
    }

    /**
     * Üzenet megjelenítése
     */
    displayOrderNotification(event, title) {
        console.log(`
        ⚡ ${title}
        Rendelés szám: ${event.order_number}
        Ügyfél: ${event.user_name}
        Összeg: ${event.total_amount} Ft
        Ételek száma: ${event.items_count}
        `);

        // Browser notification (ha engedélyezett)
        if (Notification.permission === 'granted') {
            new Notification(title, {
                body: `${event.user_name} - ${event.order_number}`,
                icon: '/images/order-icon.png'
            });
        }
    }

    /**
     * Státusz változás értesítés
     */
    displayStatusNotification(event) {
        const statusMap = {
            'pending': '⏳ Feldolgozás alatt',
            'confirmed': '✅ Megerősítve',
            'preparing': '👨‍🍳 Készítés alatt',
            'ready': '🍽️ Kész',
            'served': '📦 Felszolgálva',
            'completed': '🎉 Befejezve',
            'cancelled': '❌ Lemondva'
        };

        console.log(`
        📋 Rendelés: ${event.order_number}
        Status: ${statusMap[event.old_status]} → ${statusMap[event.new_status]}
        `);
    }

    /**
     * Dashboard frissítése
     */
    updateDashboard() {
        const totalOrders = this.orders.length;
        const pendingOrders = this.orders.filter(o => o.status === 'pending').length;
        const preparingOrders = this.orders.filter(o => o.status === 'preparing').length;

        console.log(`
        📊 Dashboard Update:
        Összes rendelés: ${totalOrders}
        Feldolgozás alatt: ${pendingOrders}
        Készítés alatt: ${preparingOrders}
        `);

        // UI frissítés
        document.getElementById('total-orders').innerText = totalOrders;
        document.getElementById('pending-orders').innerText = pendingOrders;
        document.getElementById('preparing-orders').innerText = preparingOrders;
    }

    /**
     * Rendelés státuszának frissítése API-n keresztül
     */
    async updateOrderStatus(orderId, newStatus) {
        try {
            const response = await fetch(`/api/v1/admin/orders/${orderId}/status`, {
                method: 'PATCH',
                headers: {
                    'Authorization': `Bearer ${this.token}`,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ status: newStatus })
            });

            const data = await response.json();
            console.log('Rendelés frissítve:', data);
            // WebSocket broadcast automatikusan megtörténik
        } catch (error) {
            console.error('Hiba a rendelés frissítésekor:', error);
        }
    }

    disconnect() {
        if (this.echo) {
            this.echo.leave('admin-orders');
        }
    }
}

// ============================================
// 2. Ügyfél App - Saját Rendelés Követése
// ============================================

class CustomerOrderTracker {
    constructor(orderId, token) {
        this.orderId = orderId;
        this.token = token;
        this.echo = null;
        this.order = null;
    }

    connect() {
        this.echo = new Echo({
            broadcaster: 'pusher',
            key: 'vizsgaremek-local',
            wsHost: 'localhost',
            wsPort: 8080,
            forceTLS: false,
            encrypted: false,
            disableStats: true,
            enabledTransports: ['ws'],
            auth: {
                headers: {
                    'Authorization': `Bearer ${this.token}`
                }
            }
        });

        console.log(`Ügyfél WebSocket csatlakozás: rendelés ${this.orderId}`);
    }

    /**
     * Saját rendelés státuszának követése
     */
    subscribeToOrderUpdates() {
        this.echo
            .private(`order.${this.orderId}`)
            .listen('OrderStatusChanged', (event) => {
                console.log('🔔 Az Ön rendelésének státusza megváltozott:', event);
                this.displayProgressUpdate(event);
                this.updateProgressBar(event.new_status);
            });
    }

    /**
     * Állapot sáv frissítése
     */
    updateProgressBar(status) {
        const statuses = ['pending', 'confirmed', 'preparing', 'ready', 'served', 'completed'];
        const currentStep = statuses.indexOf(status) + 1;
        const progress = (currentStep / statuses.length) * 100;

        document.getElementById('progress-bar').style.width = progress + '%';
        document.getElementById('status-text').innerText = this.getStatusLabel(status);
    }

    /**
     * Felhasználóbarát státusz szöveg
     */
    getStatusLabel(status) {
        const labels = {
            'pending': '⏳ Feldolgozás alatt...',
            'confirmed': '✅ Megerősítve',
            'preparing': '👨‍🍳 Készítés alatt...',
            'ready': '🍽️ Kész - Azonnal felvehető!',
            'served': '📦 Felszolgálva',
            'completed': '🎉 Befejezve - Köszi a rendelést!',
            'cancelled': '❌ Lemondva'
        };
        return labels[status] || 'Ismeretlen státusz';
    }

    /**
     * Részletes frissítés megjelenítése
     */
    displayProgressUpdate(event) {
        const update = `
        🕐 ${new Date(event.timestamp).toLocaleTimeString()}
        Rendelés: ${event.order_number}
        Korábbi: ${event.old_status} → Új: ${event.new_status}
        `;
        
        console.log(update);

        // Timeline-hez hozzáadni
        this.addToTimeline(event);
    }

    /**
     * Feltöltés hozzáadása timeline-hez
     */
    addToTimeline(event) {
        const timeline = document.getElementById('timeline');
        const entry = document.createElement('div');
        entry.className = 'timeline-entry';
        entry.innerHTML = `
            <div class="timestamp">${new Date(event.timestamp).toLocaleTimeString()}</div>
            <div class="status">${this.getStatusLabel(event.new_status)}</div>
        `;
        timeline.appendChild(entry);
    }

    disconnect() {
        if (this.echo) {
            this.echo.leave(`order.${this.orderId}`);
        }
    }
}

// ============================================
// 3. Étterem App - Étterem Rendeléseinek Monitoring
// ============================================

class RestaurantOrdersMonitor {
    constructor(restaurantId, token) {
        this.restaurantId = restaurantId;
        this.token = token;
        this.echo = null;
        this.orders = new Map();
    }

    connect() {
        this.echo = new Echo({
            broadcaster: 'pusher',
            key: 'vizsgaremek-local',
            wsHost: 'localhost',
            wsPort: 8080,
            forceTLS: false,
            encrypted: false,
            disableStats: true,
            enabledTransports: ['ws'],
            auth: {
                headers: {
                    'Authorization': `Bearer ${this.token}`
                }
            }
        });

        console.log(`Étterem WebSocket csatlakozás: ${this.restaurantId}`);
    }

    /**
     * Étterem összes rendeléséhez csatlakozás
     */
    subscribeToRestaurantOrders() {
        this.echo
            .private(`restaurant.${this.restaurantId}.orders`)
            .listen('OrderCreated', (event) => {
                console.log('🔔 Új rendelés az Ön étteremhez:', event);
                this.orders.set(event.order_id, event);
                this.displayNewOrderAlert(event);
                this.updateKitchenDisplay();
            })
            .listen('OrderStatusChanged', (event) => {
                console.log('🔄 Rendelés státusza megváltozott:', event);
                if (this.orders.has(event.order_id)) {
                    this.orders.get(event.order_id).status = event.new_status;
                }
                this.updateKitchenDisplay();
            });
    }

    /**
     * Új rendelés riasztás
     */
    displayNewOrderAlert(event) {
        // Audio beep
        this.playSound('/sounds/new-order.mp3');

        // Kijelzésre megjeleníteni
        const alert = `
        🚨 ÚJ RENDELÉS!
        Rendelés száma: ${event.order_number}
        Ügyfél: ${event.user_name}
        Tételek: ${event.items_count} db
        Típus: ${event.type === 'dine_in' ? 'Helyen fogyasztás' : 'Elvitel'}
        `;
        
        console.log(alert);

        if (Notification.permission === 'granted') {
            new Notification('🚨 Új rendelés!', {
                body: `${event.order_number} - ${event.items_count} tétel`,
                tag: 'new-order'
            });
        }
    }

    /**
     * Konyha kijelző frissítése (KDS - Kitchen Display System)
     */
    updateKitchenDisplay() {
        const pending = Array.from(this.orders.values())
            .filter(o => o.status === 'pending');
        const preparing = Array.from(this.orders.values())
            .filter(o => o.status === 'preparing');
        const ready = Array.from(this.orders.values())
            .filter(o => o.status === 'ready');

        console.log(`
        🖥️ KDS Update:
        Várakozásban: ${pending.length}
        Készítés alatt: ${preparing.length}
        Kész: ${ready.length}
        `);

        // UI frissítés
        this.renderOrderCards(pending, 'pending-orders');
        this.renderOrderCards(preparing, 'preparing-orders');
        this.renderOrderCards(ready, 'ready-orders');
    }

    /**
     * Rendelés kártyák renderelése
     */
    renderOrderCards(orders, containerId) {
        const container = document.getElementById(containerId);
        container.innerHTML = orders.map(order => `
            <div class="order-card">
                <div class="order-number">${order.order_number}</div>
                <div class="items-count">${order.items_count} tétel</div>
                <div class="customer">${order.user_name}</div>
            </div>
        `).join('');
    }

    /**
     * Audio jelzés lejátszása
     */
    playSound(src) {
        const audio = new Audio(src);
        audio.play().catch(e => console.log('Audio playback blocked:', e));
    }

    disconnect() {
        if (this.echo) {
            this.echo.leave(`restaurant.${this.restaurantId}.orders`);
        }
    }
}

// ============================================
// 4. HTML Initialization
// ============================================

// Admin
if (document.getElementById('admin-dashboard')) {
    const adminMonitor = new AdminOrdersMonitor(window.userToken);
    adminMonitor.connect();
    adminMonitor.subscribeToOrders();
    window.adminMonitor = adminMonitor;
}

// Customer
if (document.getElementById('order-tracker')) {
    const orderId = document.getElementById('order-tracker').dataset.orderId;
    const tracker = new CustomerOrderTracker(orderId, window.userToken);
    tracker.connect();
    tracker.subscribeToOrderUpdates();
    window.orderTracker = tracker;
}

// Restaurant
if (document.getElementById('kitchen-display')) {
    const restaurantId = document.getElementById('kitchen-display').dataset.restaurantId;
    const monitor = new RestaurantOrdersMonitor(restaurantId, window.userToken);
    monitor.connect();
    monitor.subscribeToRestaurantOrders();
    window.restaurantMonitor = monitor;
}

// Cleanup on page unload
window.addEventListener('beforeunload', () => {
    if (window.adminMonitor) window.adminMonitor.disconnect();
    if (window.orderTracker) window.orderTracker.disconnect();
    if (window.restaurantMonitor) window.restaurantMonitor.disconnect();
});
