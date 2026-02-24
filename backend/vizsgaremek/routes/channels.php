<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The required channels are declared here, and you
| may register additional channels as needed for your application.
|
*/

// Admin orders channel - csak admin felhasználók
Broadcast::channel('admin-orders', function ($user) {
    return $user && $user->isAdmin();
});

// Admin reservations channel - csak admin felhasználók
Broadcast::channel('admin-reservations', function ($user) {
    return $user && $user->isAdmin();
});

// Order channel - az ügyfél és adminok
Broadcast::channel('order.{orderId}', function ($user, $orderId) {
    // TODO: ellenőrizni, hogy a felhasználó tulajdonosa-e a rendelésnek
    return $user ? true : false;
});

// Restaurant orders - az étterem alkalmazottai és adminok
Broadcast::channel('restaurant.{restaurantId}.orders', function ($user, $restaurantId) {
    return $user && $user->isAdmin();
});

// Restaurant reservations - az étterem alkalmazottai és adminok
Broadcast::channel('restaurant.{restaurantId}.reservations', function ($user, $restaurantId) {
    return $user && $user->isAdmin();
});

// Reservation channel - a foglalást létrehozó felhasználó
Broadcast::channel('reservation.{reservationId}', function ($user, $reservationId) {
    // TODO: ellenőrizni, hogy a felhasználó készítette-e a foglalást
    return $user ? true : false;
});
