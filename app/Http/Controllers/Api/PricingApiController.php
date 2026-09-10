<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Cache\PriceTierCacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PricingApiController extends Controller
{
    public function __construct(
        protected PriceTierCacheService $priceTierCacheService
    ) {}

    public function quote(Request $request, int $variantId): JsonResponse
    {
        $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $quantity = (int) $request->query('quantity', 1);

        $calculation = $this->priceTierCacheService->calculatePrice($variantId, $quantity);

        return response()->json([
            'status' => 'success',
            'data' => $calculation,
        ]);
    }
}