<?php

namespace App\Services;

use App\Jobs\FinalizeInventoryImportJob;
use App\Jobs\ProcessInventoryBatchChunkJob;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Throwable;

class InventoryImportService
{
    protected const CHUNK_SIZE = 250;

    /**
     * Streams CSV, splits into chunk jobs, and dispatches a Bus Batch.
     */
    public function dispatchBatch(string $filePath, int $vendorId, int $warehouseId, int $userId): Batch
    {
        $fullPath = Storage::path($filePath);
        $handle = fopen($fullPath, 'r');

        if (! $handle) {
            throw new \RuntimeException("Unable to open CSV file at [{$fullPath}].");
        }

        // Skip header row
        fgetcsv($handle);

        $jobs = [];
        $currentChunk = [];

        while (($row = fgetcsv($handle)) !== false) {
            $currentChunk[] = $row;

            if (count($currentChunk) >= self::CHUNK_SIZE) {
                $jobs[] = new ProcessInventoryBatchChunkJob($currentChunk, $vendorId, $warehouseId, $userId);
                $currentChunk = [];
            }
        }

        if (! empty($currentChunk)) {
            $jobs[] = new ProcessInventoryBatchChunkJob($currentChunk, $vendorId, $warehouseId, $userId);
        }

        fclose($handle);

        // Dispatch Batched Jobs with Then / Catch callbacks and chained cleanup
        return Bus::batch($jobs)
            ->name("inventory_import_vendor_{$vendorId}_wh_{$warehouseId}")
            ->allowFailures()
            ->then(function (Batch $batch) use ($filePath, $vendorId) {
                FinalizeInventoryImportJob::dispatch($filePath, $vendorId);
            })
            ->catch(function (Batch $batch, Throwable $e) {
                \Illuminate\Support\Facades\Log::error("Batch [{$batch->id}] encountered failure: " . $e->getMessage());
            })
            ->dispatch();
    }
}