import { Component, OnInit, inject, Input } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink, ActivatedRoute } from '@angular/router';
import { MenuService } from '../../services/menu.service';
import { RestaurantService } from '../../services/restaurant.service';
import { CartService } from '../../services/cart.service';
import { MenuCategory, MenuItem, Restaurant } from '../../models';

@Component({
  selector: 'app-menu',
  standalone: true,
  imports: [CommonModule, RouterLink],
  templateUrl: './menu.component.html',
  styleUrl: './menu.component.scss',
})
export class MenuComponent implements OnInit {
  private menuService = inject(MenuService);
  private restaurantService = inject(RestaurantService);
  public cart = inject(CartService);
  private route = inject(ActivatedRoute);

  restaurant: Restaurant | null = null;
  categories: MenuCategory[] = [];
  itemsByCategory: Map<number, MenuItem[]> = new Map();
  activeCategory: number | null = null;
  loading = true;
  restaurantId = 1; // default

  ngOnInit(): void {
    const paramId = this.route.snapshot.paramMap.get('restaurantId');
    this.restaurantId = paramId ? +paramId : 1;
    this.loadData();
  }

  loadData(): void {
    this.restaurantService.getById(this.restaurantId).subscribe({
      next: (r) => { this.restaurant = r; },
      error: () => {}
    });

    this.menuService.getCategoriesByRestaurant(this.restaurantId).subscribe({
      next: (cats) => {
        this.categories = cats;
        if (cats.length > 0) {
          this.activeCategory = cats[0].id;
          cats.forEach(cat => this.loadItems(cat.id));
        }
        this.loading = false;
      },
      error: () => { this.loading = false; }
    });
  }

  loadItems(categoryId: number): void {
    this.menuService.getItemsByCategory(categoryId).subscribe({
      next: (items) => { this.itemsByCategory.set(categoryId, items); }
    });
  }

  getItems(categoryId: number): MenuItem[] {
    return this.itemsByCategory.get(categoryId) || [];
  }

  addToCart(item: MenuItem): void {
    this.cart.addToCart(item);
  }

  getCartQuantity(itemId: number): number {
    const cartItem = this.cart.cartItems().find(i => i.menuItem.id === itemId);
    return cartItem?.quantity || 0;
  }

  scrollTo(categoryId: number): void {
    this.activeCategory = categoryId;
    const el = document.getElementById(`cat-${categoryId}`);
    if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }
}
