<?php

namespace App\Services;

use App\Actions\Pricing\CalculateTierPriceAction;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;

class CartService
{
    protected const SESSION_KEY = 'tradecore_b2b_cart';

    public function __construct(
        protected CalculateTierPriceAction $tierPriceAction
    ) {}

    /**
     * Add or update variant in cart with volume validation.
     */
    public function add(int $variantId, int $quantity): void
    {
        $cart = Session::get(self::SESSION_KEY, []);
        $variant = ProductVariant::with('product')->findOrFail($variantId);

        $newQty = isset($cart[$variantId]) ? $cart[$variantId] + $quantity : $quantity;

        // Ensure MOQ is satisfied
        $cart[$variantId] = max($variant->product->moq, $newQty);

        Session::put(self::SESSION_KEY, $cart);
    }

    /**
     * Update item quantity directly.
     */
    public function update(int $variantId, int $quantity): void
    {
        $cart = Session::get(self::SESSION_KEY, []);
        $variant = ProductVariant::with('product')->findOrFail($variantId);

        if ($quantity <= 0) {
            unset($cart[$variantId]);
        } else {
            $cart[$variantId] = max($variant->product->moq, $quantity);
        }

        Session::put(self::SESSION_KEY, $cart);
    }

    public function remove(int $variantId): void
    {
        $cart = Session::get(self::SESSION_KEY, []);
        unset($cart[$variantId]);
        Session::put(self::SESSION_KEY, $cart);
    }

    public function clear(): void
    {
        Session::forget(self::SESSION_KEY);
    }

    /**
     * Returns evaluated cart items with dynamic bulk pricing tiers.
     */
    public function items(): Collection
    {
        $cart = Session::get(self::SESSION_KEY, []);
        if (empty($cart)) {
            return collect();
        }

        $variants = ProductVariant::with(['product.vendor', 'priceTiers'])
            ->whereIn('id', array_keys($cart))
            ->get();

        return $variants->map(function ($variant) use ($cart) {
            $qty = $cart[$variant->id];
            $pricing = $this->tierPriceAction->execute($variant, $qty);

            return (object) [
                'variant' => $variant,
                'product' => $variant->product,
                'vendor' => $variant->product->vendor,
                'quantity' => $qty,
                'unit_price' => $pricing['unit_price'],
                'line_total' => $pricing['line_total'],
                'is_tier_applied' => $pricing['is_tier_applied'],
                'discount_per_unit' => $pricing['discount_per_unit'],
            ];
        });
    }

    public function subtotal(): float
    {
        return round($this->items()->sum('line_total'), 2);
    }

    public function tax(): float
    {
        return round($this->subtotal() * 0.05, 2); // 5% GST
    }

    public function total(): float
    {
        return round($this->subtotal() + $this->tax(), 2);
    }

    public function count(): int
    {
        return count(Session::get(self::SESSION_KEY, []));
    }
}