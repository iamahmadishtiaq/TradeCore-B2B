<?php

namespace App\Observers;

use App\Models\PriceTier;
use App\Services\Cache\PriceTierCacheService;

class PriceTierObserver
{
    public function __construct(
        protected PriceTierCacheService $cacheService
    ) {}

    public function saved(PriceTier $priceTier): void
    {
        $this->cacheService->forget($priceTier->product_variant_id);
    }

    public function deleted(PriceTier $priceTier): void
    {
        $this->cacheService->forget($priceTier->product_variant_id);
    }
}