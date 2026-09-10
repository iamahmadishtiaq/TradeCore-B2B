<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class InsufficientStockException extends Exception
{
    public function __construct(
        string $message = "Requested quantity exceeds available stock across all warehouses.",
        int $code = 422,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Named constructor for clear, domain-driven errors
     */
    public static function forVariant(string $sku, int $available, int $requested): self
    {
        return new self("Insufficient stock for SKU [{$sku}]. Available: {$available}, Requested: {$requested}.");
    }

    /**
     * Render the exception into an HTTP response.
     */
    public function render(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'error' => 'INSUFFICIENT_STOCK',
                'message' => $this->getMessage(),
            ], 422);
        }

        return back()->withInput()->with('error', $this->getMessage());
    }
}