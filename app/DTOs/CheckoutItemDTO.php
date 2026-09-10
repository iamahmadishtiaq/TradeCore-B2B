<?php

namespace App\DTOs;

class CheckoutItemDTO
{
    public function __construct(
        public int $productVariantId,
        public int $quantity
    ) {}
}