<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RestaurantTable extends Model
{
    protected $table = 'restaurant_tables';

    protected $fillable = [
        'restaurant_id',
        'table_number',
        'capacity',
        'status',
        'qr_code',
    ];

    protected $casts = [
        'capacity' => 'integer',
    ];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'table_id');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'table_id');
    }
}

