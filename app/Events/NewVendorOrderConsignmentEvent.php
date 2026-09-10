<?php

namespace App\Events;

use App\Models\Shipment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewVendorOrderConsignmentEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Shipment $shipment,
        public float $totalConsignmentValue
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('vendor.' . $this->shipment->vendor_id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'shipment_id' => $this->shipment->id,
            'order_number' => $this->shipment->order->order_number,
            'consignee_company' => $this->shipment->order->shipping_address['company'] ?? 'B2B Client',
            'city' => $this->shipment->order->shipping_address['city'] ?? 'N/A',
            'value' => $this->totalConsignmentValue,
            'timestamp' => now()->format('H:i:s'),
        ];
    }
}