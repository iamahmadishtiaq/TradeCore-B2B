<?php

namespace App\Http\Controllers;

use App\Actions\Orders\TransitionOrderStatusAction;
use App\Enums\OrderStatus;
use App\Enums\StockMovementType;
use App\Models\InventoryBatch;
use App\Models\ProductVariant;
use App\Models\Shipment;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Services\InventoryImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\View\View;
use App\Events\ShipmentStatusUpdatedEvent;

class VendorDashboardController extends Controller
{
    public function __construct(
        protected TransitionOrderStatusAction $transitionOrderStatusAction,
        protected InventoryImportService $inventoryImportService
    ) {}

    /**
     * Vendor main overview dashboard.
     */
    public function index(): View
    {
        $vendor = auth()->user()->vendor;

        $warehouses = Warehouse::where('vendor_id', $vendor->id)
            ->withCount('inventoryBatches')
            ->get();

        $shipments = Shipment::where('vendor_id', $vendor->id)
            ->with(['order.items' => function ($q) use ($vendor) {
                $q->where('vendor_id', $vendor->id)->with('variant.product');
            }])
            ->latest()
            ->paginate(10);

        $batches = InventoryBatch::whereHas('warehouse', fn ($q) => $q->where('vendor_id', $vendor->id))
            ->with(['variant.product', 'warehouse'])
            ->latest()
            ->get();

        return view('vendor.dashboard', compact('vendor', 'warehouses', 'shipments', 'batches'));
    }

    /**
     * Restock an inventory batch manually.
     */
    public function restockBatch(Request $request, int $batchId): RedirectResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $vendor = auth()->user()->vendor;

        $batch = InventoryBatch::where('id', $batchId)
            ->whereHas('warehouse', fn ($q) => $q->where('vendor_id', $vendor->id))
            ->firstOrFail();

        $batch->increment('quantity_on_hand', $validated['quantity']);

        StockMovement::create([
            'inventory_batch_id' => $batch->id,
            'user_id' => auth()->id(),
            'quantity_change' => $validated['quantity'],
            'movement_type' => StockMovementType::RESTOCK,
            'notes' => $validated['notes'] ?? 'Manual warehouse batch restock via vendor portal',
        ]);

        return back()->with('success', "Batch [{$batch->batch_number}] restocked with {$validated['quantity']} units.");
    }

    /**
     * Update Shipment logistics tracking and status.
     */
    public function updateShipment(Request $request, int $shipmentId): RedirectResponse
    {
        $validated = $request->validate([
            'courier_name' => ['required', 'string', 'max:100'],
            'tracking_number' => ['required', 'string', 'max:100'],
            'status' => ['required', 'in:ready_to_ship,dispatched,in_transit,delivered'],
        ]);

        $vendor = auth()->user()->vendor;

        $shipment = Shipment::where('id', $shipmentId)
            ->where('vendor_id', $vendor->id)
            ->with('order')
            ->firstOrFail();

        $shipment->courier_name = $validated['courier_name'];
        $shipment->tracking_number = $validated['tracking_number'];

        if ($validated['status'] === 'dispatched' && ! $shipment->dispatched_at) {
            $shipment->dispatched_at = now();
        }

        if ($validated['status'] === 'delivered' && ! $shipment->delivered_at) {
            $shipment->delivered_at = now();
        }

        $shipment->status = $validated['status'];
        $shipment->save();

        broadcast(new ShipmentStatusUpdatedEvent($shipment->fresh(['vendor', 'order'])));

        // Check if all shipments of the parent order are delivered
        $order = $shipment->order;
        $allDelivered = $order->shipments()->where('status', '!=', 'delivered')->count() === 0;

        if ($allDelivered && $order->status !== OrderStatus::COMPLETED) {
            // Settle inventory deduction and credit vendor wallet
            $this->transitionOrderStatusAction->execute($order, OrderStatus::COMPLETED);
        }

        return back()->with('success', "Shipment #{$shipment->id} logistics updated successfully.");
    }

    /**
     * Handle CSV File Upload & Trigger Batched Jobs.
     */
    public function uploadInventoryCsv(Request $request): RedirectResponse
    {
        $request->validate([
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'], // 10MB Max
        ]);

        $vendor = auth()->user()->vendor;
        $warehouse = Warehouse::where('id', $request->warehouse_id)
            ->where('vendor_id', $vendor->id)
            ->firstOrFail();

        $path = $request->file('csv_file')->store('imports/inventory');

        $batch = $this->inventoryImportService->dispatchBatch(
            $path,
            $vendor->id,
            $warehouse->id,
            auth()->id()
        );

        return back()->with([
            'success' => "CSV upload queued successfully. Tracking Batch ID: {$batch->id}",
            'active_batch_id' => $batch->id,
        ]);
    }

    /**
     * Real-time Batch Progress Polling API Endpoint.
     */
    public function checkBatchProgress(string $batchId): JsonResponse
    {
        $batch = Bus::findBatch($batchId);

        if (! $batch) {
            return response()->json(['error' => 'Batch not found'], 404);
        }

        return response()->json([
            'id' => $batch->id,
            'progress' => $batch->progress(),
            'total_jobs' => $batch->totalJobs,
            'pending_jobs' => $batch->pendingJobs,
            'failed_jobs' => $batch->failedJobs,
            'processed_jobs' => $batch->processedJobs(),
            'finished' => $batch->finished(),
            'cancelled' => $batch->cancelled(),
        ]);
    }
}