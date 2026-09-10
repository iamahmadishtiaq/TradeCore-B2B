<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryBatch extends Model
{
    protected $fillable = [
        'product_variant_id',
        'warehouse_id',
        'batch_number',
        'quantity_on_hand',
        'quantity_reserved',
        'unit_cost',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'date',
    ];

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function getAvailableQuantityAttribute(): int
    {
        return max(0, $this->quantity_on_hand - $this->quantity_reserved);
    }
}