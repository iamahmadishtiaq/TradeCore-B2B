<?php

namespace App\Http\Controllers;

use App\Actions\Orders\PlaceOrderAction;
use App\DTOs\CheckoutItemDTO;
use App\Exceptions\InsufficientStockException;
use App\Http\Requests\CheckoutOrderRequest;
use App\Models\Order;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use App\Services\InvoiceService;

class CheckoutController extends Controller
{
    public function __construct(
        protected CartService $cartService,
        protected PlaceOrderAction $placeOrderAction
    ) {}

    public function index(): View|RedirectResponse
    {
        if ($this->cartService->count() === 0) {
            return redirect()->route('catalog.index')->with('error', 'Your wholesale cart is empty.');
        }

        $items = $this->cartService->items();
        $subtotal = $this->cartService->subtotal();
        $tax = $this->cartService->tax();
        $total = $this->cartService->total();
        $companyProfile = auth()->user()->companyProfile;

        return view('checkout.index', compact('items', 'subtotal', 'tax', 'total', 'companyProfile'));
    }

    public function store(CheckoutOrderRequest $request): RedirectResponse
    {
        $items = $this->cartService->items();

        if ($items->isEmpty()) {
            return redirect()->route('catalog.index')->with('error', 'Cart is empty.');
        }

        $checkoutItemDTOs = $items->map(fn($item) => new CheckoutItemDTO(
            $item->variant->id,
            $item->quantity
        ))->all();

        $shippingAddress = [
            'company' => $request->shipping_company,
            'address' => $request->shipping_address,
            'city' => $request->shipping_city,
            'contact' => $request->shipping_contact,
        ];

        try {
            $order = $this->placeOrderAction->execute(
                auth()->user(),
                $checkoutItemDTOs,
                $shippingAddress,
                $shippingAddress,
                $request->payment_method,
                $request->notes
            );

            $this->cartService->clear();

            return redirect()->route('orders.show', $order->id)->with('success', "Order #{$order->order_number} successfully placed!");
        } catch (InsufficientStockException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return back()->with('error', 'Order processing failed: ' . $e->getMessage());
        }
    }

    public function show(int $id): View
    {
        $order = Order::with(['items.variant.product', 'items.vendor', 'shipments.vendor'])
            ->where('buyer_id', auth()->id())
            ->findOrFail($id);

        return view('orders.show', compact('order'));
    }

    public function downloadInvoice(int $id, \App\Services\InvoiceService $invoiceService)
    {
        $order = Order::where('buyer_id', auth()->id())->findOrFail($id);

        return $invoiceService->download($order);
    }
}
