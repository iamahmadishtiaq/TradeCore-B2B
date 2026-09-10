<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id',
        'sku',
        'title',
        'base_price',
        'cost_price',
        'barcode',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function priceTiers(): HasMany
    {
        return $this->hasMany(PriceTier::class)->orderBy('min_quantity');
    }

    public function inventoryBatches(): HasMany
    {
        return $this->hasMany(InventoryBatch::class);
    }

    // Dynamic Tier Price Resolver (Quantity ke mutabiq best rate return karega)
    public function getPriceForQuantity(int $quantity): float
    {
        $tier = $this->priceTiers()
            ->where('min_quantity', '<=', $quantity)
            ->where(function ($query) use ($quantity) {
                $query->whereNull('max_quantity')
                    ->orWhere('max_quantity', '>=', $quantity);
            })
            ->first();

        return (float) ($tier ? $tier->unit_price : $this->base_price);
    }

    // Total Available Stock Across All Warehouses
    public function getTotalStockAttribute(): int
    {
        return (int) $this->inventoryBatches()
            ->selectRaw('SUM(quantity_on_hand - quantity_reserved) as available')
            ->value('available') ?? 0;
    }
}