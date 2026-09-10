<?php

namespace App\Http\Controllers;

use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function __construct(
        protected CartService $cartService
    ) {}

    public function index(): View
    {
        $items = $this->cartService->items();
        $subtotal = $this->cartService->subtotal();
        $tax = $this->cartService->tax();
        $total = $this->cartService->total();

        return view('cart.index', compact('items', 'subtotal', 'tax', 'total'));
    }

    public function add(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'variant_id' => ['required', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $this->cartService->add($validated['variant_id'], $validated['quantity']);

        return redirect()->route('cart.index')->with('success', 'Item added to wholesale cart.');
    }

    public function update(Request $request, int $variantId): RedirectResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:0'],
        ]);

        $this->cartService->update($variantId, $validated['quantity']);

        return back()->with('success', 'Cart updated successfully.');
    }

    public function remove(int $variantId): RedirectResponse
    {
        $this->cartService->remove($variantId);

        return back()->with('success', 'Item removed from cart.');
    }
}