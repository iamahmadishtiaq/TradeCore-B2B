<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'buyer_id',
        'order_number',
        'status',
        'subtotal',
        'tax_amount',
        'shipping_cost',
        'total_amount',
        'payment_method',
        'payment_status',
        'shipping_address',
        'billing_address',
        'notes',
    ];

    protected $casts = [
        'status' => OrderStatus::class,
        'shipping_address' => 'array',
        'billing_address' => 'array',
    ];

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }
}