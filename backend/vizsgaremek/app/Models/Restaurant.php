<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Restaurant extends Model
{
    protected $fillable = [
        'name',
        'description',
        'address',
        'phone',
        'email',
        'image_url',
        'rating',
        'delivery_time',
        'delivery_fee',
        'is_open',
        'opening_time',
        'closing_time',
    ];

    protected $casts = [
        'is_open' => 'boolean',
        'rating' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
    ];

    public function categories(): HasMany
    {
        return $this->hasMany(MenuCategory::class);
    }

    public function tables(): HasMany
    {
        return $this->hasMany(RestaurantTable::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }
}

