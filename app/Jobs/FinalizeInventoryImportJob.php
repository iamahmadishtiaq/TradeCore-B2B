<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FinalizeInventoryImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected string $filePath,
        protected int $vendorId
    ) {}

    public function handle(): void
    {
        // Cleanup uploaded CSV file from storage
        if (Storage::exists($this->filePath)) {
            Storage::delete($this->filePath);
        }

        Log::info("Inventory Batch Import finalized successfully for Vendor #{$this->vendorId}. Temp file removed.");
    }
}