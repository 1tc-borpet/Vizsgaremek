import { Component, OnInit, inject, ElementRef, ViewChild } from '@angular/core';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { CommonModule } from '@angular/common';
import { MenuService } from '../../services/menu.service';
import { RestaurantService } from '../../services/restaurant.service';
import { CartService } from '../../services/cart.service';
import { MenuCategory, MenuItem, Restaurant } from '../../models';
import QRCode from 'qrcode';

@Component({
  selector: 'app-qr-menu',
  standalone: true,
  imports: [CommonModule, RouterLink],
  templateUrl: './qr-menu.component.html',
  styleUrl: './qr-menu.component.scss',
})
export class QrMenuComponent implements OnInit {
  private route = inject(ActivatedRoute);
  private menuService = inject(MenuService);
  private restaurantService = inject(RestaurantService);
  public cart = inject(CartService);

  @ViewChild('qrCanvas', { static: false }) qrCanvas!: ElementRef<HTMLCanvasElement>;

  restaurant: Restaurant | null = null;
  categories: MenuCategory[] = [];
  itemsByCategory: Map<number, MenuItem[]> = new Map();
  loading = true;
  restaurantId = 1;
  qrUrl = '';

  ngOnInit(): void {
    this.restaurantId = +this.route.snapshot.paramMap.get('restaurantId')!;
    this.qrUrl = `${window.location.origin}/qr-menu/${this.restaurantId}`;

    this.restaurantService.getById(this.restaurantId).subscribe({ next: r => this.restaurant = r });
    this.menuService.getCategoriesByRestaurant(this.restaurantId).subscribe({
      next: (cats) => {
        this.categories = cats;
        cats.forEach(cat => {
          this.menuService.getItemsByCategory(cat.id).subscribe({
            next: items => this.itemsByCategory.set(cat.id, items)
          });
        });
        this.loading = false;
        setTimeout(() => this.generateQR(), 300);
      },
      error: () => { this.loading = false; }
    });
  }

  generateQR(): void {
    if (this.qrCanvas?.nativeElement) {
      QRCode.toCanvas(this.qrCanvas.nativeElement, this.qrUrl, { width: 200, margin: 2, color: { dark: '#ff6b35', light: '#1e1e1e' } });
    }
  }

  getItems(categoryId: number): MenuItem[] {
    return this.itemsByCategory.get(categoryId) || [];
  }
}
