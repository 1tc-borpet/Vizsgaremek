import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { OrderService } from '../../../services/order.service';
import { Order } from '../../../models';

@Component({
  selector: 'app-admin-orders',
  standalone: true,
  imports: [CommonModule, RouterLink],
  templateUrl: './admin-orders.component.html',
  styleUrl: './admin-orders.component.scss',
})
export class AdminOrdersComponent implements OnInit {
  private orderService = inject(OrderService);
  orders: Order[] = [];
  loading = true;

  statuses = ['pending', 'confirmed', 'preparing', 'ready', 'delivered', 'cancelled'];

  ngOnInit(): void {
    this.orderService.getAllOrders().subscribe({
      next: (o) => { this.orders = o; this.loading = false; },
      error: () => { this.loading = false; }
    });
  }

  updateStatus(id: number, status: string): void {
    this.orderService.updateStatus(id, status).subscribe({
      next: (updated) => {
        const idx = this.orders.findIndex(o => o.id === id);
        if (idx !== -1) this.orders[idx] = updated;
      }
    });
  }

  statusLabel(s: string): string {
    const map: Record<string, string> = {
      pending: 'Várakozó', confirmed: 'Megerősítve', preparing: 'Készítés',
      ready: 'Kész', delivered: 'Kiszállítva', cancelled: 'Törölve'
    };
    return map[s] || s;
  }
}
