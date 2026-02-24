import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { DashboardService, DashboardStats } from '../../../services/dashboard.service';

@Component({
  selector: 'app-admin-dashboard',
  standalone: true,
  imports: [CommonModule, RouterLink],
  templateUrl: './dashboard.component.html',
  styleUrl: './dashboard.component.scss',
})
export class AdminDashboardComponent implements OnInit {
  private dashboardService = inject(DashboardService);

  stats: DashboardStats | null = null;
  recentOrders: any[] = [];
  recentReservations: any[] = [];
  popularItems: any[] = [];
  loading = true;

  ngOnInit(): void {
    this.dashboardService.getStats().subscribe({ next: (s) => { this.stats = s; } });
    this.dashboardService.getRecentOrders().subscribe({ next: (o) => { this.recentOrders = o; } });
    this.dashboardService.getRecentReservations().subscribe({ next: (r) => { this.recentReservations = r; } });
    this.dashboardService.getPopularItems().subscribe({
      next: (i) => { this.popularItems = i; this.loading = false; },
      error: () => { this.loading = false; }
    });
  }
}
