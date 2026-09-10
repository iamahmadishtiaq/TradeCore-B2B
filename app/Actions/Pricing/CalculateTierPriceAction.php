<?php

namespace App\Actions\Pricing;

use App\Models\ProductVariant;

class CalculateTierPriceAction
{
    /**
     * Resolves the wholesale tier price based on order volume.
     */
    public function execute(ProductVariant $variant, int $quantity): array
    {
        // Load tiers ordered ascending
        $tiers = $variant->priceTiers()->orderBy('min_quantity', 'asc')->get();

        $selectedTier = null;
        $unitPrice = (float) $variant->base_price;

        foreach ($tiers as $tier) {
            if ($quantity >= $tier->min_quantity) {
                if (is_null($tier->max_quantity) || $quantity <= $tier->max_quantity) {
                    $unitPrice = (float) $tier->unit_price;
                    $selectedTier = $tier;
                    break;
                }
            }
        }

        $lineTotal = round($unitPrice * $quantity, 2);

        return [
            'unit_price' => $unitPrice,
            'line_total' => $lineTotal,
            'is_tier_applied' => $selectedTier !== null,
            'tier_id' => $selectedTier?->id,
            'discount_per_unit' => round((float) $variant->base_price - $unitPrice, 2),
        ];
    }
}