<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuItem extends Model
{
    protected $fillable = [
        'category_id',
        'name',
        'description',
        'price',
        'image_url',
        'preparation_time',
        'is_available',
        'rating',
        'rating_count',
        'order',
    ];

    protected $casts = [
        'is_available' => 'boolean',
        'price' => 'decimal:2',
        'rating' => 'decimal:2',
        'preparation_time' => 'integer',
        'rating_count' => 'integer',
        'order' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(MenuCategory::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'menu_item_id');
    }
}

