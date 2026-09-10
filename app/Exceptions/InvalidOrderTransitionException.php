<?php

namespace App\Exceptions;

use App\Enums\OrderStatus;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class InvalidOrderTransitionException extends Exception
{
    public function __construct(
        string $message = "This order status transition is not permitted.",
        int $code = 409,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Named constructor for state machine transitions
     */
    public static function fromStatus(string $orderNumber, OrderStatus $from, OrderStatus $to): self
    {
        return new self(
            "Cannot transition order [{$orderNumber}] from [{$from->label()}] to [{$to->label()}]. Action violates lifecycle rules."
        );
    }

    /**
     * Render the exception into an HTTP response.
     */
    public function render(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'error' => 'INVALID_ORDER_TRANSITION',
                'message' => $this->getMessage(),
            ], 409);
        }

        return back()->with('error', $this->getMessage());
    }
}