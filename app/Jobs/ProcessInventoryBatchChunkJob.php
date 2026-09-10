<?php

namespace App\Jobs;

use App\Enums\StockMovementType;
use App\Models\InventoryBatch;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessInventoryBatchChunkJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    /**
     * @param array $rows Array of parsed CSV rows
     * @param int $vendorId
     * @param int $warehouseId
     * @param int $userId
     */
    public function __construct(
        protected array $rows,
        protected int $vendorId,
        protected int $warehouseId,
        protected int $userId
    ) {}

    public function handle(): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $warehouse = Warehouse::where('id', $this->warehouseId)
            ->where('vendor_id', $this->vendorId)
            ->firstOrFail();

        foreach ($this->rows as $row) {
            // Row format: [sku, batch_number, quantity, unit_cost, expires_at]
            $sku = trim($row[0] ?? '');
            $batchNumber = trim($row[1] ?? '');
            $quantity = (int) ($row[2] ?? 0);
            $unitCost = (float) ($row[3] ?? 0.00);
            $expiresAt = !empty(trim($row[4] ?? '')) ? trim($row[4]) : null;

            if (empty($sku) || empty($batchNumber) || $quantity <= 0) {
                continue;
            }

            // Variant must belong to the vendor's products
            $variant = ProductVariant::where('sku', $sku)
                ->whereHas('product', fn ($q) => $q->where('vendor_id', $this->vendorId))
                ->first();

            if (! $variant) {
                Log::warning("CSV Import skipped row: SKU [{$sku}] not found for Vendor #{$this->vendorId}");
                continue;
            }

            DB::transaction(function () use ($variant, $warehouse, $batchNumber, $quantity, $unitCost, $expiresAt) {
                $batch = InventoryBatch::where('product_variant_id', $variant->id)
                    ->where('warehouse_id', $warehouse->id)
                    ->where('batch_number', $batchNumber)
                    ->lockForUpdate()
                    ->first();

                if ($batch) {
                    $batch->increment('quantity_on_hand', $quantity);
                    $batch->update([
                        'unit_cost' => $unitCost > 0 ? $unitCost : $batch->unit_cost,
                        'expires_at' => $expiresAt ?? $batch->expires_at,
                    ]);
                } else {
                    $batch = InventoryBatch::create([
                        'product_variant_id' => $variant->id,
                        'warehouse_id' => $warehouse->id,
                        'batch_number' => $batchNumber,
                        'quantity_on_hand' => $quantity,
                        'quantity_reserved' => 0,
                        'unit_cost' => $unitCost,
                        'expires_at' => $expiresAt,
                    ]);
                }

                // Audit Trail
                StockMovement::create([
                    'inventory_batch_id' => $batch->id,
                    'user_id' => $this->userId,
                    'quantity_change' => $quantity,
                    'movement_type' => StockMovementType::RESTOCK,
                    'notes' => "Bulk CSV Import via Job Batch [{$this->batch()?->id}]",
                ]);
            });
        }
    }
}