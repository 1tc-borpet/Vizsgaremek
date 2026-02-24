import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { MenuService } from '../../../services/menu.service';
import { MenuCategory, MenuItem } from '../../../models';

@Component({
  selector: 'app-menu-management',
  standalone: true,
  imports: [CommonModule, RouterLink],
  templateUrl: './menu-management.component.html',
  styleUrl: './menu-management.component.scss',
})
export class MenuManagementComponent implements OnInit {
  private menuService = inject(MenuService);
  categories: MenuCategory[] = [];
  itemsByCategory: Map<number, MenuItem[]> = new Map();
  loading = true;

  ngOnInit(): void {
    this.menuService.getCategoriesByRestaurant(1).subscribe({
      next: (cats) => {
        this.categories = cats;
        cats.forEach(cat => {
          this.menuService.getItemsByCategory(cat.id).subscribe({
            next: items => this.itemsByCategory.set(cat.id, items)
          });
        });
        this.loading = false;
      },
      error: () => { this.loading = false; }
    });
  }

  getItems(categoryId: number): MenuItem[] {
    return this.itemsByCategory.get(categoryId) || [];
  }
}
