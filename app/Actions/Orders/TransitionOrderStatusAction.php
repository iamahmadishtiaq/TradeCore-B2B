<?php

namespace App\Actions\Orders;

use App\Actions\Inventory\ReleaseInventoryAction;
use App\Enums\OrderStatus;
use App\Enums\StockMovementType;
use App\Exceptions\InvalidOrderTransitionException;
use App\Models\InventoryBatch;
use App\Models\Order;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class TransitionOrderStatusAction
{
    public function __construct(
        protected ReleaseInventoryAction $releaseInventoryAction
    ) {}

    /**
     * Executes order status transitions with domain rules & balance settlements.
     *
     * @throws InvalidOrderTransitionException
     */
    public function execute(Order $order, OrderStatus $targetStatus, ?string $reason = null): Order
    {
        if (! $order->status->canTransitionTo($targetStatus)) {
            throw new InvalidOrderTransitionException(
                "Cannot transition order [{$order->order_number}] from [{$order->status->value}] to [{$targetStatus->value}]."
            );
        }

        return DB::transaction(function () use ($order, $targetStatus, $reason) {
            $previousStatus = $order->status;

            // 1. If Payment is confirmed, mark payment_status = paid
            if ($targetStatus === OrderStatus::PAYMENT_CONFIRMED) {
                $order->payment_status = 'paid';
            }

            // 2. If Order is completed/delivered:
            // Settle stock (turn reserved stock into permanent deduction)
            // Settle Vendor Wallets (Crediting net amount after commission)
            if ($targetStatus === OrderStatus::COMPLETED) {
                $this->settleInventoryAndPayouts($order);
            }

            // 3. If Order is cancelled: Release reserved stock back to active inventory
            if ($targetStatus === OrderStatus::CANCELLED) {
                $this->rollbackReservedStock($order, $reason ?? 'Order cancelled');
            }

            $order->status = $targetStatus;
            $order->save();

            return $order->fresh(['items', 'shipments']);
        });
    }

    /**
     * Permanent stock deduction and vendor wallet crediting.
     */
    protected function settleInventoryAndPayouts(Order $order): void
    {
        foreach ($order->items as $item) {
            // Find batches where reservations were placed
            $movements = StockMovement::where('reference_type', get_class($item))
                ->where('reference_id', $item->id)
                ->where('movement_type', StockMovementType::RESERVED)
                ->get();

            foreach ($movements as $movement) {
                $batch = InventoryBatch::where('id', $movement->inventory_batch_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $reservedUnits = abs($movement->quantity_change);

                // Permanent deduction from stock and clearance of reservation
                $batch->decrement('quantity_on_hand', $reservedUnits);
                $batch->decrement('quantity_reserved', $reservedUnits);

                StockMovement::create([
                    'inventory_batch_id' => $batch->id,
                    'user_id' => auth()->id(),
                    'quantity_change' => -$reservedUnits,
                    'movement_type' => StockMovementType::SALE,
                    'reference_type' => get_class($item),
                    'reference_id' => $item->id,
                    'notes' => "Sale finalized for Order #{$order->order_number}",
                ]);
            }

            // Vendor Net Payout: Line Total - Commission
            $netVendorShare = $item->line_total - $item->commission_amount;
            $vendor = $item->vendor()->lockForUpdate()->firstOrFail();
            $vendor->increment('wallet_balance', $netVendorShare);
        }
    }

    /**
     * Release all reserved stock if order fails or gets cancelled.
     */
    protected function rollbackReservedStock(Order $order, string $reason): void
    {
        foreach ($order->items as $item) {
            $movements = StockMovement::where('reference_type', get_class($item))
                ->where('reference_id', $item->id)
                ->where('movement_type', StockMovementType::RESERVED)
                ->get();

            foreach ($movements as $movement) {
                $this->releaseInventoryAction->execute(
                    $movement->inventory_batch_id,
                    abs($movement->quantity_change),
                    $item,
                    $reason
                );
            }
        }
    }
}