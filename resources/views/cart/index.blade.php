<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Wholesale Order Cart') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
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

            @if($items->isEmpty())
                <div class="bg-white dark:bg-gray-800 p-12 text-center rounded-2xl border border-gray-100 dark:border-gray-700 space-y-4">
                    <p class="text-gray-500">Your commercial procurement cart is empty.</p>
                    <a href="{{ route('catalog.index') }}" class="inline-flex px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700">
                        Browse Catalog
                    </a>
                </div>
            @else
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                    <!-- Items Table -->
                    <div class="lg:col-span-8 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                        <div class="p-6 divide-y divide-gray-100 dark:divide-gray-700/60">
                            @foreach($items as $item)
                                <div class="py-4 first:pt-0 last:pb-0 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                                    <div>
                                        <div class="text-xs text-indigo-500 font-semibold">{{ $item->vendor->store_name }}</div>
                                        <h4 class="text-base font-bold text-gray-900 dark:text-white">{{ $item->product->name }}</h4>
                                        <p class="text-xs text-gray-500">SKU: {{ $item->variant->sku }} | {{ $item->variant->title }}</p>
                                        @if($item->is_tier_applied)
                                            <span class="inline-block mt-1 text-xs bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300 px-2 py-0.5 rounded font-medium">
                                                Wholesale Tier Applied (Save PKR {{ number_format($item->discount_per_unit, 2) }}/unit)
                                            </span>
                                        @endif
                                    </div>

                                    <div class="flex items-center gap-6 self-end sm:self-center">
                                        <form method="POST" action="{{ route('cart.update', $item->variant->id) }}" class="flex items-center gap-2">
                                            @csrf
                                            @method('PATCH')
                                            <input type="number" name="quantity" value="{{ $item->quantity }}" min="{{ $item->product->moq }}" 
                                                   class="w-20 text-center rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 text-sm">
                                            <button type="submit" class="text-xs text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 font-semibold">Update</button>
                                        </form>

                                        <div class="text-right min-w-[100px]">
                                            <div class="text-xs text-gray-500">PKR {{ number_format($item->unit_price, 2) }}/pc</div>
                                            <div class="font-bold text-gray-900 dark:text-white">PKR {{ number_format($item->line_total, 2) }}</div>
                                        </div>

                                        <form method="POST" action="{{ route('cart.remove', $item->variant->id) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-rose-500 hover:text-rose-700 text-sm font-bold">&times;</button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Summary & Checkout Action -->
                    <div class="lg:col-span-4 space-y-4">
                        <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 space-y-4">
                            <h3 class="text-base font-bold text-gray-900 dark:text-white pb-3 border-b border-gray-100 dark:border-gray-700">Order Summary</h3>
                            
                            <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400">
                                <span>Subtotal</span>
                                <span>PKR {{ number_format($subtotal, 2) }}</span>
                            </div>
                            <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400">
                                <span>Sales Tax / GST (5%)</span>
                                <span>PKR {{ number_format($tax, 2) }}</span>
                            </div>
                            <div class="border-t border-gray-100 dark:border-gray-700 pt-3 flex justify-between items-baseline font-bold text-gray-900 dark:text-white">
                                <span>Total Payable</span>
                                <span class="text-xl text-indigo-600 dark:text-indigo-400">PKR {{ number_format($total, 2) }}</span>
                            </div>

                            <a href="{{ route('checkout.index') }}" 
                               class="block w-full py-3 px-4 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-center rounded-xl transition text-sm">
                                Proceed to B2B Checkout
                            </a>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>