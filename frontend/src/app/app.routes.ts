import { Routes } from '@angular/router';
import { authGuard } from './guards/auth.guard';
import { adminGuard } from './guards/admin.guard';

export const routes: Routes = [
  {
    path: '',
    loadComponent: () => import('./pages/home/home.component').then(m => m.HomeComponent),
  },
  {
    path: 'menu',
    loadComponent: () => import('./pages/menu/menu.component').then(m => m.MenuComponent),
  },
  {
    path: 'menu/:restaurantId',
    loadComponent: () => import('./pages/menu/menu.component').then(m => m.MenuComponent),
  },
  {
    path: 'cart',
    loadComponent: () => import('./pages/cart/cart.component').then(m => m.CartComponent),
  },
  {
    path: 'checkout',
    loadComponent: () => import('./pages/checkout/checkout.component').then(m => m.CheckoutComponent),
    canActivate: [authGuard],
  },
  {
    path: 'reservation',
    loadComponent: () => import('./pages/reservation/reservation.component').then(m => m.ReservationComponent),
    canActivate: [authGuard],
  },
  {
    path: 'qr-menu/:restaurantId',
    loadComponent: () => import('./pages/qr-menu/qr-menu.component').then(m => m.QrMenuComponent),
  },
  {
    path: 'payment/:orderId',
    loadComponent: () => import('./pages/payment/payment.component').then(m => m.PaymentComponent),
    canActivate: [authGuard],
  },
  {
    path: 'orders',
    loadComponent: () => import('./pages/orders/orders.component').then(m => m.OrdersComponent),
    canActivate: [authGuard],
  },
  {
    path: 'profile',
    loadComponent: () => import('./pages/profile/profile.component').then(m => m.ProfileComponent),
    canActivate: [authGuard],
  },
  {
    path: 'auth/login',
    loadComponent: () => import('./pages/auth/login/login.component').then(m => m.LoginComponent),
  },
  {
    path: 'auth/register',
    loadComponent: () => import('./pages/auth/register/register.component').then(m => m.RegisterComponent),
  },
  {
    path: 'admin',
    canActivate: [adminGuard],
    children: [
      { path: '', redirectTo: 'dashboard', pathMatch: 'full' },
      {
        path: 'dashboard',
        loadComponent: () => import('./pages/admin/dashboard/dashboard.component').then(m => m.AdminDashboardComponent),
      },
      {
        path: 'orders',
        loadComponent: () => import('./pages/admin/orders/admin-orders.component').then(m => m.AdminOrdersComponent),
      },
      {
        path: 'reservations',
        loadComponent: () => import('./pages/admin/reservations/admin-reservations.component').then(m => m.AdminReservationsComponent),
      },
      {
        path: 'menu-management',
        loadComponent: () => import('./pages/admin/menu-management/menu-management.component').then(m => m.MenuManagementComponent),
      },
    ],
  },
  { path: '**', redirectTo: '' },
];
