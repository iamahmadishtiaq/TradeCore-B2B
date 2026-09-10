<?php

namespace App\Events;

use App\Models\Shipment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ShipmentStatusUpdatedEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Shipment $shipment
    ) {}

    public function broadcastOn(): array
    {
        // Broadcasts to the buyer's private order channel
        return [
            new PrivateChannel('orders.' . $this->shipment->order_id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'shipment_id' => $this->shipment->id,
            'order_id' => $this->shipment->order_id,
            'vendor_name' => $this->shipment->vendor->store_name,
            'status' => $this->shipment->status,
            'courier_name' => $this->shipment->courier_name,
            'tracking_number' => $this->shipment->tracking_number,
            'order_status' => $this->shipment->order->fresh()->status->value,
            'updated_at' => now()->toIso8601String(),
        ];
    }
}