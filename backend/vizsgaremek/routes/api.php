<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\RestaurantController;
use App\Http\Controllers\Api\MenuCategoryController;
use App\Http\Controllers\Api\MenuItemController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ReservationController;
use App\Http\Controllers\Api\PaymentController;

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

        // Felhasználó profil
        Route::get('user', function (Request $request) {
            return $request->user();
        });
    });
});

// Admin route-ok
Route::prefix('v1/admin')->middleware('auth:sanctum')->group(function () {
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

