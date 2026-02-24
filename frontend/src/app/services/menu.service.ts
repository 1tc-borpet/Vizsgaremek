import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../environments/environment';
import { MenuCategory, MenuItem } from '../models';

@Injectable({ providedIn: 'root' })
export class MenuService {
  constructor(private http: HttpClient) {}

  getCategoriesByRestaurant(restaurantId: number): Observable<MenuCategory[]> {
    return this.http.get<MenuCategory[]>(`${environment.apiUrl}/restaurants/${restaurantId}/categories`);
  }

  getItemsByCategory(categoryId: number): Observable<MenuItem[]> {
    return this.http.get<MenuItem[]>(`${environment.apiUrl}/categories/${categoryId}/items`);
  }

  getItemById(id: number): Observable<MenuItem> {
    return this.http.get<MenuItem>(`${environment.apiUrl}/menu-items/${id}`);
  }
}
