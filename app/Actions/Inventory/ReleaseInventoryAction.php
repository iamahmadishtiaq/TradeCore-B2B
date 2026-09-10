<?php

namespace App\Actions\Inventory;

use App\Enums\StockMovementType;
use App\Models\InventoryBatch;
use App\Models\OrderItem;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class ReleaseInventoryAction
{
    /**
     * Releases reserved stock back to active quantity.
     */
    public function execute(int $batchId, int $quantityToRelease, ?OrderItem $orderItem = null, string $reason = 'Hold released'): void
    {
        DB::transaction(function () use ($batchId, $quantityToRelease, $orderItem, $reason) {
            $batch = InventoryBatch::where('id', $batchId)->lockForUpdate()->firstOrFail();

            // Guard against underflow
            $actualRelease = min($batch->quantity_reserved, $quantityToRelease);

            $batch->decrement('quantity_reserved', $actualRelease);

            StockMovement::create([
                'inventory_batch_id' => $batch->id,
                'user_id' => auth()->id(),
                'quantity_change' => $actualRelease,
                'movement_type' => StockMovementType::RESTOCK,
                'reference_type' => $orderItem ? get_class($orderItem) : null,
                'reference_id' => $orderItem?->id,
                'notes' => $reason,
            ]);
        });
    }
}