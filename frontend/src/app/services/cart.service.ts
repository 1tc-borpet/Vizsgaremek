import { Injectable, signal, computed } from '@angular/core';
import { CartItem, MenuItem } from '../models';

@Injectable({ providedIn: 'root' })
export class CartService {
  private items = signal<CartItem[]>([]);

  cartItems = this.items.asReadonly();

  totalItems = computed(() =>
    this.items().reduce((sum, item) => sum + item.quantity, 0)
  );

  totalPrice = computed(() =>
    this.items().reduce((sum, item) => sum + item.menuItem.price * item.quantity, 0)
  );

  addToCart(menuItem: MenuItem, quantity = 1): void {
    this.items.update((current) => {
      const existing = current.find((i) => i.menuItem.id === menuItem.id);
      if (existing) {
        return current.map((i) =>
          i.menuItem.id === menuItem.id ? { ...i, quantity: i.quantity + quantity } : i
        );
      }
      return [...current, { menuItem, quantity }];
    });
  }

  updateQuantity(menuItemId: number, quantity: number): void {
    if (quantity <= 0) {
      this.removeFromCart(menuItemId);
      return;
    }
    this.items.update((current) =>
      current.map((i) => (i.menuItem.id === menuItemId ? { ...i, quantity } : i))
    );
  }

  removeFromCart(menuItemId: number): void {
    this.items.update((current) => current.filter((i) => i.menuItem.id !== menuItemId));
  }

  clearCart(): void {
    this.items.set([]);
  }
}
