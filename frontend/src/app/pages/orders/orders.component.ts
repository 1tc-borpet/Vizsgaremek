import { Component, OnInit, inject } from '@angular/core';
import { RouterLink } from '@angular/router';
import { CommonModule } from '@angular/common';
import { OrderService } from '../../services/order.service';
import { Order } from '../../models';

@Component({
  selector: 'app-orders',
  standalone: true,
  imports: [RouterLink, CommonModule],
  templateUrl: './orders.component.html',
  styleUrl: './orders.component.scss',
})
export class OrdersComponent implements OnInit {
  private orderService = inject(OrderService);
  orders: Order[] = [];
  loading = true;

  ngOnInit(): void {
    this.orderService.getMyOrders().subscribe({
      next: (o) => { this.orders = o; this.loading = false; },
      error: () => { this.loading = false; }
    });
  }

  statusLabel(status: string): string {
    const map: Record<string, string> = {
      pending: '⏳ Várakozó', confirmed: '✅ Megerősítve',
      preparing: '👨‍🍳 Készítés', ready: '🔔 Kész',
      delivered: '🚀 Kiszállítva', cancelled: '❌ Törölve'
    };
    return map[status] || status;
  }

  orderTypeLabel(type: string): string {
    const map: Record<string, string> = { dine_in: '🍽️ Helyben', takeaway: '🥡 Elvitelre', delivery: '🚀 Kiszállítás' };
    return map[type] || type;
  }
}
