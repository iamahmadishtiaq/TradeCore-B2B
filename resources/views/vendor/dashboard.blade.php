<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    {{ $vendor->store_name }} — Logistics & Inventory Terminal
                </h2>
                <p class="text-xs text-gray-500 mt-1">Platform Commission Rate: {{ $vendor->commission_rate }}%</p>
            </div>
            <div class="text-right flex items-center gap-4">
                <div>
                    <span class="text-xs text-gray-500 uppercase tracking-wider block font-semibold">Available Settlement</span>
                    <span class="text-xl font-black text-emerald-600 dark:text-emerald-400">
                        PKR {{ number_format($vendor->wallet_balance, 2) }}
                    </span>
                </div>
                <a href="{{ route('vendor.payouts.index') }}" 
                   class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition shadow-sm">
                    Request Payout &rarr;
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12" x-data="batchProgressWatcher('{{ session('active_batch_id') }}')">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            
            @if(session('success'))
                <div class="p-4 bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 rounded-xl text-emerald-800 dark:text-emerald-200 text-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="p-4 bg-rose-50 dark:bg-rose-900/30 border border-rose-200 dark:border-rose-800 rounded-xl text-rose-800 dark:text-rose-200 text-sm">
                    {{ session('error') }}
                </div>
            @endif

            <!-- Queued CSV Bulk Inventory Importer -->
            <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-4 border-b border-gray-100 dark:border-gray-700">
                    <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">Bulk Warehouse Batch Importer (Queued)</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Stream-process inventory batches via queued chunks: <code>sku, batch_number, quantity, unit_cost, expires_at</code></p>
                    </div>

                    <form method="POST" action="{{ route('vendor.inventory.import') }}" enctype="multipart/form-data" class="flex flex-wrap items-center gap-3">
                        @csrf
                        <select name="warehouse_id" required class="text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 py-2">
                            <option value="">Select Target Warehouse</option>
                            @foreach($warehouses as $wh)
                                <option value="{{ $wh->id }}">{{ $wh->name }} ({{ $wh->code }})</option>
                            @endforeach
                        </select>

                        <input type="file" name="csv_file" accept=".csv,.txt" required 
                               class="text-xs text-gray-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-indigo-950 dark:file:text-indigo-300">

                        <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold transition shadow-sm">
                            Queue Import
                        </button>
                    </form>
                </div>

                <!-- Real-time Polling Progress Bar -->
                <div x-show="activeBatchId" x-cloak class="mt-4 p-4 bg-gray-50 dark:bg-gray-900/60 rounded-xl space-y-3">
                    <div class="flex justify-between items-center text-xs font-semibold">
                        <span class="text-gray-700 dark:text-gray-300">
                            Processing Job Batch: <span x-text="activeBatchId" class="font-mono text-indigo-500"></span>
                        </span>
                        <span x-text="progress + '% Completed'" class="text-emerald-600 dark:text-emerald-400 font-bold"></span>
                    </div>

                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5 overflow-hidden">
                        <div class="bg-indigo-600 h-2.5 rounded-full transition-all duration-300" :style="`width: ${progress}%`"></div>
                    </div>

                    <div class="flex justify-between text-[11px] text-gray-500">
                        <span>Jobs Dispatched: <span x-text="processedJobs"></span> / <span x-text="totalJobs"></span></span>
                        <span x-show="finished" class="text-emerald-600 dark:text-emerald-400 font-bold">All batch chunks finalized! Refresh to inspect reconciled stock.</span>
                        <span x-show="failedJobs > 0" class="text-rose-500 font-bold" x-text="failedJobs + ' job(s) failed.'"></span>
                    </div>
                </div>
            </div>

            <!-- Top Cards: Warehouses & Logistics -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                @foreach($warehouses as $wh)
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 space-y-2">
                        <div class="flex justify-between items-center">
                            <span class="text-xs uppercase font-bold text-indigo-500">{{ $wh->code }}</span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold {{ $wh->is_active ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-gray-100 text-gray-800' }}">
                                {{ $wh->is_active ? 'Operational' : 'Disabled' }}
                            </span>
                        </div>
                        <h4 class="text-lg font-bold text-gray-900 dark:text-white">{{ $wh->name }}</h4>
                        <p class="text-xs text-gray-500">{{ $wh->address }}, {{ $wh->city }}</p>
                        <div class="text-xs font-semibold text-gray-600 dark:text-gray-400 pt-2 border-t border-gray-100 dark:border-gray-700/60">
                            Active Batches: {{ $wh->inventory_batches_count }}
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Shipments Management -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="p-6 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">Assigned Consignment Shipments</h3>
                    <span class="text-xs text-gray-500">Orders split across vendors</span>
                </div>

                <div class="divide-y divide-gray-100 dark:divide-gray-700/60">
                    @forelse($shipments as $shipment)
                        <div class="p-6 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                            <div class="space-y-2">
                                <div class="flex items-center gap-3">
                                    <span class="text-xs font-bold px-2.5 py-0.5 rounded bg-indigo-50 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-300">
                                        Shipment #{{ $shipment->id }}
                                    </span>
                                    <span class="text-sm font-semibold text-gray-900 dark:text-white">
                                        Order: {{ $shipment->order->order_number }}
                                    </span>
                                    <span class="text-xs uppercase px-2 py-0.5 rounded font-bold 
                                        {{ $shipment->status === 'delivered' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300' }}">
                                        {{ str_replace('_', ' ', $shipment->status) }}
                                    </span>
                                </div>

                                <div class="text-xs text-gray-600 dark:text-gray-400 space-y-1">
                                    @foreach($shipment->order->items as $item)
                                        <div>&bull; {{ $item->variant->product->name }} (SKU: {{ $item->variant->sku }}) &times; <strong>{{ $item->quantity }} units</strong></div>
                                    @endforeach
                                </div>
                            </div>

                            <!-- Shipment Tracking Form -->
                            <form method="POST" action="{{ route('vendor.shipments.update', $shipment->id) }}" class="flex flex-wrap items-center gap-3 bg-gray-50 dark:bg-gray-900/50 p-3 rounded-xl border border-gray-200 dark:border-gray-700">
                                @csrf
                                @method('PATCH')

                                <div>
                                    <label class="block text-[10px] uppercase font-bold text-gray-500">Courier</label>
                                    <input type="text" name="courier_name" value="{{ old('courier_name', $shipment->courier_name ?? 'TCS Logistics') }}" required
                                           class="text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 py-1.5 px-2">
                                </div>

                                <div>
                                    <label class="block text-[10px] uppercase font-bold text-gray-500">Tracking Code</label>
                                    <input type="text" name="tracking_number" value="{{ old('tracking_number', $shipment->tracking_number ?? '') }}" placeholder="TRK-..." required
                                           class="text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 py-1.5 px-2">
                                </div>

                                <div>
                                    <label class="block text-[10px] uppercase font-bold text-gray-500">Status</label>
                                    <select name="status" class="text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 py-1.5 px-2">
                                        <option value="ready_to_ship" {{ $shipment->status === 'ready_to_ship' ? 'selected' : '' }}>Ready to Ship</option>
                                        <option value="dispatched" {{ $shipment->status === 'dispatched' ? 'selected' : '' }}>Dispatched</option>
                                        <option value="in_transit" {{ $shipment->status === 'in_transit' ? 'selected' : '' }}>In Transit</option>
                                        <option value="delivered" {{ $shipment->status === 'delivered' ? 'selected' : '' }}>Delivered (Release Wallet)</option>
                                    </select>
                                </div>

                                <div class="self-end">
                                    <button type="submit" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold transition">
                                        Update
                                    </button>
                                </div>
                            </form>
                        </div>
                    @empty
                        <div class="p-8 text-center text-gray-500 text-sm">
                            No shipments assigned yet.
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Inventory Batches Table & Fast Restock -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="p-6 border-b border-gray-100 dark:border-gray-700">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">Warehouse Batches & Real-time Stock Allocation</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
                        <thead class="bg-gray-50 dark:bg-gray-900/60 text-xs font-semibold uppercase text-gray-500 border-b border-gray-200 dark:border-gray-700">
                            <tr>
                                <th class="px-6 py-3">Product / SKU</th>
                                <th class="px-6 py-3">Warehouse</th>
                                <th class="px-6 py-3">Batch Number</th>
                                <th class="px-6 py-3 text-center">On Hand</th>
                                <th class="px-6 py-3 text-center">Reserved</th>
                                <th class="px-6 py-3 text-center">Available</th>
                                <th class="px-6 py-3 text-right">Quick Restock</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                            @foreach($batches as $batch)
                                <tr>
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-gray-900 dark:text-white">{{ $batch->variant->product->name }}</div>
                                        <div class="text-xs text-gray-500">SKU: {{ $batch->variant->sku }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-xs font-medium">{{ $batch->warehouse->name }}</td>
                                    <td class="px-6 py-4 font-mono text-xs">{{ $batch->batch_number }}</td>
                                    <td class="px-6 py-4 text-center font-semibold">{{ $batch->quantity_on_hand }}</td>
                                    <td class="px-6 py-4 text-center text-amber-600 font-semibold">{{ $batch->quantity_reserved }}</td>
                                    <td class="px-6 py-4 text-center font-bold text-emerald-600">{{ $batch->available_quantity }}</td>
                                    <td class="px-6 py-4 text-right">
                                        <form method="POST" action="{{ route('vendor.batches.restock', $batch->id) }}" class="inline-flex items-center gap-2">
                                            @csrf
                                            <input type="number" name="quantity" min="1" placeholder="+Qty" required
                                                   class="w-20 text-xs text-center rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 py-1">
                                            <button type="submit" class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold">
                                                Add
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <!-- Alpine.js Polling Watcher Script -->
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('batchProgressWatcher', (initialBatchId) => ({
                activeBatchId: initialBatchId || null,
                progress: 0,
                totalJobs: 0,
                processedJobs: 0,
                failedJobs: 0,
                finished: false,
                pollInterval: null,

                init() {
                    if (this.activeBatchId) {
                        this.pollStatus();
                        this.pollInterval = setInterval(() => this.pollStatus(), 1500);
                    }
                },

                async pollStatus() {
                    if (!this.activeBatchId || this.finished) return;

                    try {
                        let response = await fetch(`/vendor/inventory/batches/${this.activeBatchId}/progress`);
                        if (!response.ok) return;

                        let payload = await response.json();
                        this.progress = payload.progress;
                        this.totalJobs = payload.total_jobs;
                        this.processedJobs = payload.processed_jobs;
                        this.failedJobs = payload.failed_jobs;
                        this.finished = payload.finished;

                        if (this.finished) {
                            clearInterval(this.pollInterval);
                        }
                    } catch (err) {
                        console.error('Batch polling connection interrupted:', err);
                    }
                }
            }));
        });
    </script>
</x-app-layout>