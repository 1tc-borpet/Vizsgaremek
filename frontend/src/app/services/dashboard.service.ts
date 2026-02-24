import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../environments/environment';

export interface DashboardStats {
  total_orders: number;
  total_revenue: number;
  total_reservations: number;
  total_users: number;
  pending_orders: number;
  pending_reservations: number;
}

@Injectable({ providedIn: 'root' })
export class DashboardService {
  constructor(private http: HttpClient) {}

  getStats(): Observable<DashboardStats> {
    return this.http.get<DashboardStats>(`${environment.apiUrl}/admin/dashboard/stats`);
  }

  getRecentOrders(): Observable<any[]> {
    return this.http.get<any[]>(`${environment.apiUrl}/admin/dashboard/recent-orders`);
  }

  getRecentReservations(): Observable<any[]> {
    return this.http.get<any[]>(`${environment.apiUrl}/admin/dashboard/recent-reservations`);
  }

  getRevenueReport(): Observable<any> {
    return this.http.get<any>(`${environment.apiUrl}/admin/dashboard/revenue-report`);
  }

  getPopularItems(): Observable<any[]> {
    return this.http.get<any[]>(`${environment.apiUrl}/admin/dashboard/popular-items`);
  }
}
