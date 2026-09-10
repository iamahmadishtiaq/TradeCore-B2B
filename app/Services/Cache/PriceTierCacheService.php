<?php

namespace App\Services\Cache;

use App\Models\ProductVariant;
use Illuminate\Support\Facades\Cache;

class PriceTierCacheService
{
    protected const TTL_SECONDS = 86400; // 24 Hours

    /**
     * Get pricing tiers for a variant from Redis cache or fall back to DB.
     */
    public function getCachedTiers(int $variantId): array
    {
        $cacheKey = "variant_tiers_{$variantId}";

        return Cache::remember($cacheKey, self::TTL_SECONDS, function () use ($variantId) {
            $variant = ProductVariant::with(['priceTiers'])->findOrFail($variantId);

            return [
                'base_price' => (float) $variant->base_price,
                'sku' => $variant->sku,
                'tiers' => $variant->priceTiers->map(fn ($tier) => [
                    'id' => $tier->id,
                    'min_quantity' => (int) $tier->min_quantity,
                    'max_quantity' => $tier->max_quantity ? (int) $tier->max_quantity : null,
                    'unit_price' => (float) $tier->unit_price,
                ])->toArray(),
            ];
        });
    }

    /**
     * Calculate price directly from cached tiers without querying DB.
     */
    public function calculatePrice(int $variantId, int $quantity): array
    {
        $cachedData = $this->getCachedTiers($variantId);
        $basePrice = $cachedData['base_price'];
        $tiers = $cachedData['tiers'];

        $unitPrice = $basePrice;
        $appliedTier = null;

        foreach ($tiers as $tier) {
            if ($quantity >= $tier['min_quantity']) {
                if (is_null($tier['max_quantity']) || $quantity <= $tier['max_quantity']) {
                    $unitPrice = $tier['unit_price'];
                    $appliedTier = $tier;
                    break;
                }
            }
        }

        $lineTotal = round($unitPrice * $quantity, 2);

        return [
            'variant_id' => $variantId,
            'unit_price' => $unitPrice,
            'line_total' => $lineTotal,
            'is_tier_applied' => $appliedTier !== null,
            'tier_id' => $appliedTier['id'] ?? null,
            'discount_per_unit' => round($basePrice - $unitPrice, 2),
            'source' => 'redis_cache',
        ];
    }

    /**
     * Purge cache for a specific variant.
     */
    public function forget(int $variantId): void
    {
        Cache::forget("variant_tiers_{$variantId}");
    }
}