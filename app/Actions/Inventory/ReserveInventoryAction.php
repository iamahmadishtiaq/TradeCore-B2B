<?php

namespace App\Actions\Inventory;

use App\Enums\StockMovementType;
use App\Exceptions\InsufficientStockException;
use App\Models\InventoryBatch;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class ReserveInventoryAction
{
    /**
     * Reserves stock across available batches using pessimistic locking (FIFO).
     *
     * @throws InsufficientStockException
     */
    public function execute(ProductVariant $variant, int $requestedQty, ?OrderItem $orderItem = null): array
    {
        return DB::transaction(function () use ($variant, $requestedQty, $orderItem) {
            // Lock all available batches for this variant across active warehouses
            $batches = InventoryBatch::where('product_variant_id', $variant->id)
                ->whereHas('warehouse', fn ($q) => $q->where('is_active', true))
                ->whereRaw('(quantity_on_hand - quantity_reserved) > 0')
                ->orderBy('created_at', 'asc') // FIFO
                ->lockForUpdate()
                ->get();

            $totalAvailable = $batches->sum(fn ($b) => $b->quantity_on_hand - $b->quantity_reserved);

            if ($totalAvailable < $requestedQty) {
                throw new InsufficientStockException(
                    "Insufficient stock for SKU [{$variant->sku}]. Available: {$totalAvailable}, Requested: {$requestedQty}."
                );
            }

            $remainingToReserve = $requestedQty;
            $allocations = [];

            foreach ($batches as $batch) {
                if ($remainingToReserve <= 0) {
                    break;
                }

                $availableInBatch = $batch->quantity_on_hand - $batch->quantity_reserved;
                $reserveFromThisBatch = min($availableInBatch, $remainingToReserve);

                // Increment reserved quantity atomically
                $batch->increment('quantity_reserved', $reserveFromThisBatch);

                // Audit trail movement
                StockMovement::create([
                    'inventory_batch_id' => $batch->id,
                    'user_id' => auth()->id(),
                    'quantity_change' => -$reserveFromThisBatch,
                    'movement_type' => StockMovementType::RESERVED,
                    'reference_type' => $orderItem ? get_class($orderItem) : null,
                    'reference_id' => $orderItem?->id,
                    'notes' => "Hold placed for {$reserveFromThisBatch} units.",
                ]);

                $allocations[] = [
                    'batch_id' => $batch->id,
                    'reserved_qty' => $reserveFromThisBatch,
                    'batch_number' => $batch->batch_number,
                ];

                $remainingToReserve -= $reserveFromThisBatch;
            }

            return $allocations;
        });
    }
}