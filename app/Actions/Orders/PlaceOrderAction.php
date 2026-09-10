<?php

namespace App\Actions\Orders;

use App\Actions\Inventory\ReserveInventoryAction;
use App\Actions\Pricing\CalculateTierPriceAction;
use App\Enums\OrderStatus;
use App\Exceptions\InsufficientStockException;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Events\NewVendorOrderConsignmentEvent;
use App\Mail\OrderPlacedInvoiceMail;
use Illuminate\Support\Facades\Mail;

class PlaceOrderAction
{
    public function __construct(
        protected CalculateTierPriceAction $tierPriceAction,
        protected ReserveInventoryAction $reserveInventoryAction
    ) {}

    /**
     * Places a multi-vendor B2B order within an atomic transaction.
     *
     * @param User $buyer
     * @param array<\App\DTOs\CheckoutItemDTO> $items
     * @param array $shippingAddress
     * @param array $billingAddress
     * @param string $paymentMethod
     * @return Order
     * @throws InsufficientStockException
     */
    public function execute(
        User $buyer,
        array $items,
        array $shippingAddress,
        array $billingAddress,
        string $paymentMethod = 'bank_transfer',
        ?string $notes = null
    ): Order {
        return DB::transaction(function () use ($buyer, $items, $shippingAddress, $billingAddress, $paymentMethod, $notes) {
            // Generate Master Order Reference
            $order = Order::create([
                'buyer_id' => $buyer->id,
                'order_number' => 'TC-' . strtoupper(Str::random(4)) . '-' . date('YmdHis'),
                'status' => OrderStatus::PENDING_PAYMENT,
                'subtotal' => 0,
                'tax_amount' => 0,
                'shipping_cost' => 0,
                'total_amount' => 0,
                'payment_method' => $paymentMethod,
                'payment_status' => 'pending',
                'shipping_address' => $shippingAddress,
                'billing_address' => $billingAddress,
                'notes' => $notes,
            ]);

            $subtotal = 0.00;
            $vendorIds = [];

            foreach ($items as $itemDTO) {
                // Lock variant record
                $variant = ProductVariant::with(['product.vendor'])->findOrFail($itemDTO->productVariantId);

                // Enforce B2B Minimum Order Quantity (MOQ)
                if ($itemDTO->quantity < $variant->product->moq) {
                    throw new \InvalidArgumentException(
                        "Quantity for [{$variant->product->name}] must be at least MOQ of {$variant->product->moq}."
                    );
                }

                // 1. Calculate dynamic bulk tier price
                $pricing = $this->tierPriceAction->execute($variant, $itemDTO->quantity);
                $vendor = $variant->product->vendor;

                // Calculate marketplace commission
                $commission = round(($pricing['line_total'] * (float) $vendor->commission_rate) / 100, 2);

                // 2. Create OrderItem
                $orderItem = OrderItem::create([
                    'order_id' => $order->id,
                    'vendor_id' => $vendor->id,
                    'product_variant_id' => $variant->id,
                    'quantity' => $itemDTO->quantity,
                    'unit_price' => $pricing['unit_price'],
                    'line_total' => $pricing['line_total'],
                    'commission_amount' => $commission,
                ]);

                // 3. Atomically lock & reserve inventory batch (FIFO)
                $this->reserveInventoryAction->execute($variant, $itemDTO->quantity, $orderItem);

                $subtotal += $pricing['line_total'];
                $vendorIds[$vendor->id] = true;
            }

            // 4. Multi-Vendor Shipment Partitioning
            // Generate distinct shipment record per unique vendor involved
            foreach (array_keys($vendorIds) as $vendorId) {
                Shipment::create([
                    'order_id' => $order->id,
                    'vendor_id' => $vendorId,
                    'tracking_number' => null,
                    'courier_name' => null,
                    'status' => 'ready_to_ship',
                ]);
            }

            foreach ($order->shipments as $shipment) {
                $consignmentTotal = $order->items()
                    ->where('vendor_id', $shipment->vendor_id)
                    ->sum('line_total');

                broadcast(new NewVendorOrderConsignmentEvent($shipment, (float) $consignmentTotal));
            }

            // Tax calculation (e.g. 5% sales tax standard B2B invoice)
            $taxAmount = round($subtotal * 0.05, 2);
            $totalAmount = $subtotal + $taxAmount;

            $order->update([
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
            ]);
            Mail::to($buyer->email)->queue(new OrderPlacedInvoiceMail($order));

            return $order->load(['items.variant.product', 'shipments.vendor']);
        });
    }
}
