<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Order #{{ $order->order_number }}
        </h2>
    </x-slot>

    @php
        $initialShipments = $order->shipments->map(function ($s) {
            return [
                'id' => $s->id,
                'vendor_name' => $s->vendor->store_name,
                'status' => $s->status,
                'courier_name' => $s->courier_name ?? 'Awaiting courier assignment',
                'tracking_number' => $s->tracking_number ?? 'Pending tracking ID',
            ];
        });
    @endphp

    <div class="py-12"
         x-data="{
             currentOrderStatus: '{{ $order->status->value }}',
             shipments: {{ Js::from($initialShipments) }},
             liveToast: null,
             
             formatLabel(val) {
                 return val.replace(/_/g, ' ').toUpperCase();
             }
         }"
         x-init="
             if (window.Echo) {
                 window.Echo.private('orders.{{ $order->id }}')
                     .listen('ShipmentStatusUpdatedEvent', (e) => {
                         let target = shipments.find(s => s.id === e.shipment_id);
                         if (target) {
                             target.status = e.status;
                             target.courier_name = e.courier_name || target.courier_name;
                             target.tracking_number = e.tracking_number || target.tracking_number;
                         }
                         if (e.order_status) {
                             currentOrderStatus = e.order_status;
                         }
                         liveToast = `Logistics update from ${e.vendor_name}: ${e.status.replace(/_/g, ' ').toUpperCase()}`;
                         setTimeout(() => liveToast = null, 6000);
                     });
             }
         ">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Real-Time WebSocket Toast Alert -->
            <div x-show="liveToast" 
                 x-cloak
                 class="p-4 bg-indigo-600 text-white rounded-2xl shadow-xl flex items-center justify-between text-sm">
                <div class="flex items-center gap-3">
                    <span class="relative flex h-3 w-3">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-200 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-white"></span>
                    </span>
                    <span x-text="liveToast" class="font-semibold"></span>
                </div>
                <button @click="liveToast = null" class="text-indigo-200 hover:text-white font-bold text-lg">&times;</button>
            </div>

            <!-- Top Action Header Card -->
            <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <div class="text-xs text-gray-500 uppercase tracking-wider font-semibold">Commercial Purchase Order</div>
                    <div class="text-2xl font-black text-gray-900 dark:text-white">{{ $order->order_number }}</div>
                    <div class="text-xs text-gray-400 mt-1">
                        Placed on {{ $order->created_at->format('M d, Y \a\t h:i A') }} &bull; Terms: {{ strtoupper(str_replace('_', ' ', $order->payment_method)) }}
                    </div>
                </div>

                <div class="flex items-center gap-3 self-start sm:self-center">
                    <!-- Download PDF Invoice Button -->
                    <a href="{{ route('orders.invoice.download', $order->id) }}" 
                       class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition shadow-lg shadow-indigo-600/30">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Download Tax Invoice (PDF)
                    </a>

                    <!-- Status Pill -->
                    <span class="px-4 py-2 text-xs font-bold rounded-xl uppercase tracking-wider transition"
                          :class="{
                              'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-700': currentOrderStatus === 'completed',
                              'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/50 dark:text-indigo-300 border border-indigo-300 dark:border-indigo-700': currentOrderStatus === 'processing' || currentOrderStatus === 'payment_confirmed',
                              'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300 border border-amber-300 dark:border-amber-700': currentOrderStatus === 'pending_payment',
                              'bg-rose-100 text-rose-800 dark:bg-rose-900/50 dark:text-rose-300 border border-rose-300 dark:border-rose-700': currentOrderStatus === 'cancelled'
                          }"
                          x-text="formatLabel(currentOrderStatus)">
                        {{ $order->status->label() }}
                    </span>
                </div>
            </div>

            <!-- Multi-Vendor Consignment Split Breakdown -->
            <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 space-y-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Multi-Vendor Split Fulfillment</h3>
                        <p class="text-xs text-gray-500">Each vendor dispatches from their regional warehouse</p>
                    </div>
                    <span class="inline-flex items-center gap-1.5 text-xs text-emerald-600 dark:text-emerald-400 font-semibold bg-emerald-50 dark:bg-emerald-950/60 px-3 py-1 rounded-full border border-emerald-200 dark:border-emerald-800">
                        <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        Reverb Active
                    </span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <template x-for="shipment in shipments" :key="shipment.id">
                        <div class="p-5 rounded-xl border border-gray-200 dark:border-gray-700 space-y-3 bg-gray-50/50 dark:bg-gray-900/30 transition-all">
                            <div class="flex justify-between items-center">
                                <span class="font-bold text-indigo-600 dark:text-indigo-400 text-base" x-text="shipment.vendor_name"></span>
                                <span class="text-xs px-2.5 py-0.5 rounded font-bold uppercase tracking-wider"
                                      :class="{
                                          'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300': shipment.status === 'delivered',
                                          'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-300': shipment.status === 'dispatched' || shipment.status === 'in_transit',
                                          'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300': shipment.status === 'ready_to_ship'
                                      }"
                                      x-text="formatLabel(shipment.status)">
                                </span>
                            </div>

                            <div class="space-y-1.5 text-xs text-gray-600 dark:text-gray-300">
                                <div>Logistics Courier: <strong class="text-gray-900 dark:text-white" x-text="shipment.courier_name"></strong></div>
                                <div>Tracking Consignment #: <strong class="font-mono text-gray-900 dark:text-white" x-text="shipment.tracking_number"></strong></div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Order Line Items & Tax Breakdown -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
                    <h4 class="font-bold text-gray-900 dark:text-white">Order Line Items</h4>
                    <span class="text-xs text-gray-500 font-medium">B2B Wholesale Tiers Applied</span>
                </div>
                
                <div class="divide-y divide-gray-100 dark:divide-gray-700/60 p-6">
                    @foreach($order->items as $item)
                        <div class="py-3.5 first:pt-0 last:pb-0 flex justify-between items-center text-sm">
                            <div>
                                <div class="font-semibold text-gray-900 dark:text-white">{{ $item->variant->product->name }}</div>
                                <div class="text-xs text-gray-500 mt-0.5">
                                    Qty: <strong>{{ $item->quantity }}</strong> &times; PKR {{ number_format($item->unit_price, 2) }} 
                                    <span class="mx-1.5">&bull;</span> Authorized Supplier: <strong>{{ $item->vendor->store_name }}</strong>
                                </div>
                            </div>
                            <div class="font-bold text-gray-900 dark:text-white text-base">
                                PKR {{ number_format($item->line_total, 2) }}
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="bg-gray-50 dark:bg-gray-900/40 p-6 border-t border-gray-100 dark:border-gray-700 space-y-2 text-sm text-gray-600 dark:text-gray-400">
                    <div class="flex justify-between">
                        <span>Consolidated Subtotal</span>
                        <span class="font-medium text-gray-900 dark:text-white">PKR {{ number_format($order->subtotal, 2) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Provincial Sales Tax / GST (5%)</span>
                        <span class="font-medium text-gray-900 dark:text-white">PKR {{ number_format($order->tax_amount, 2) }}</span>
                    </div>
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-3 flex justify-between items-baseline font-bold text-gray-900 dark:text-white">
                        <span class="text-base">Total Order Value</span>
                        <span class="text-2xl font-black text-indigo-600 dark:text-indigo-400">PKR {{ number_format($order->total_amount, 2) }}</span>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>