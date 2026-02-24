<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\RestaurantController;
use App\Http\Controllers\Api\MenuCategoryController;
use App\Http\Controllers\Api\MenuItemController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ReservationController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\DashboardController;

// Nyilvános auth route-ok (autentifikáció nélkül)
Route::prefix('v1/auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
});

// Nyilvános route-ok (autentifikáció nélkül)
Route::prefix('v1')->group(function () {
    // Éttermek listázása és részletei
    Route::get('restaurants', [RestaurantController::class, 'index']);
    Route::get('restaurants/{id}', [RestaurantController::class, 'show']);

    // Menükategóriák és ételek
    Route::get('restaurants/{id}/categories', [MenuCategoryController::class, 'byRestaurant']);
    Route::get('categories/{id}/items', [MenuItemController::class, 'byCategory']);
    Route::get('menu-items/{id}', [MenuItemController::class, 'show']);

    // Autentifikáció szükséges route-ok
    Route::middleware('auth:sanctum')->group(function () {
        // Auth
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::post('auth/refresh-token', [AuthController::class, 'refreshToken']);
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::post('auth/change-password', [AuthController::class, 'changePassword']);

        // User profil
        Route::get('user/profile', [UserController::class, 'show']);
        Route::patch('user/profile', [UserController::class, 'update']);

        // Rendelések
        Route::post('orders', [OrderController::class, 'store']);
        Route::get('orders/{id}', [OrderController::class, 'show']);
        Route::get('my-orders', [OrderController::class, 'userOrders']);
        Route::patch('orders/{id}', [OrderController::class, 'update']);

        // Asztalfoglalások
        Route::post('reservations', [ReservationController::class, 'store']);
        Route::get('my-reservations', [ReservationController::class, 'userReservations']);
        Route::patch('reservations/{id}', [ReservationController::class, 'update']);
        Route::delete('reservations/{id}', [ReservationController::class, 'destroy']);

        // Fizetések
        Route::post('payments', [PaymentController::class, 'store']);
        Route::get('payments/{id}', [PaymentController::class, 'show']);
    });
});

// Admin route-ok
Route::prefix('v1/admin')->middleware('auth:sanctum')->group(function () {
    // Dashboard statisztikák és jelentések
    Route::get('dashboard/stats', [DashboardController::class, 'stats']);
    Route::get('dashboard/recent-orders', [DashboardController::class, 'recentOrders']);
    Route::get('dashboard/recent-reservations', [DashboardController::class, 'recentReservations']);
    Route::get('dashboard/revenue-report', [DashboardController::class, 'revenueReport']);
    Route::get('dashboard/order-status-breakdown', [DashboardController::class, 'orderStatusBreakdown']);
    Route::get('dashboard/reservation-status-breakdown', [DashboardController::class, 'reservationStatusBreakdown']);
    Route::get('dashboard/popular-items', [DashboardController::class, 'popularMenuItems']);
    Route::get('dashboard/top-customers', [DashboardController::class, 'topCustomers']);

    // Felhasználók kezelése
    Route::get('users', [UserController::class, 'index']);
    Route::get('users/{id}', [UserController::class, 'show']);
    Route::patch('users/{id}', [UserController::class, 'update']);
    Route::post('users/{id}/deactivate', [UserController::class, 'deactivate']);
    Route::post('users/{id}/activate', [UserController::class, 'activate']);
    Route::delete('users/{id}', [UserController::class, 'destroy']);

    // Rendeléskezelés
    Route::get('orders', [OrderController::class, 'index']);
    Route::patch('orders/{id}/status', [OrderController::class, 'updateStatus']);

    // Asztalfoglalások kezelése
    Route::get('reservations', [ReservationController::class, 'index']);
    Route::patch('reservations/{id}/confirm', [ReservationController::class, 'confirm']);

    // Étterem kezelés
    Route::resource('restaurants', RestaurantController::class)->except(['show']);
    Route::resource('restaurants.categories', MenuCategoryController::class);
    Route::resource('categories.items', MenuItemController::class);
});

