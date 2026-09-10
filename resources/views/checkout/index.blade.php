<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Commercial Checkout & Invoice Generation') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('checkout.store') }}" class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                @csrf

                <!-- Left: Delivery & Billing Details -->
                <div class="lg:col-span-7 space-y-6">
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 space-y-4">
                        <h3 class="text-base font-bold text-gray-900 dark:text-white border-b border-gray-100 dark:border-gray-700 pb-3">
                            Consignee & Shipping Profile
                        </h3>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-semibold uppercase text-gray-600 dark:text-gray-400 mb-1">Company / Organization Name</label>
                                <input type="text" name="shipping_company" 
                                       value="{{ old('shipping_company', $companyProfile?->company_name ?? auth()->user()->name) }}" 
                                       required class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 text-sm">
                            </div>

                            <div>
                                <label class="block text-xs font-semibold uppercase text-gray-600 dark:text-gray-400 mb-1">Commercial Delivery Address</label>
                                <textarea name="shipping_address" rows="3" required
                                          class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 text-sm">{{ old('shipping_address', $companyProfile?->shipping_address ?? '') }}</textarea>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold uppercase text-gray-600 dark:text-gray-400 mb-1">City / Logistics Hub</label>
                                    <input type="text" name="shipping_city" value="{{ old('shipping_city', 'Islamabad') }}" required
                                           class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold uppercase text-gray-600 dark:text-gray-400 mb-1">Contact Phone</label>
                                    <input type="text" name="shipping_contact" value="{{ old('shipping_contact', '+923001122334') }}" required
                                           class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 text-sm">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Mode -->
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 space-y-4">
                        <h3 class="text-base font-bold text-gray-900 dark:text-white border-b border-gray-100 dark:border-gray-700 pb-3">
                            Payment Settlement
                        </h3>
                        <div class="space-y-2">
                            <label class="flex items-center p-3 border rounded-xl border-gray-200 dark:border-gray-700 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <input type="radio" name="payment_method" value="bank_transfer" checked class="text-indigo-600">
                                <span class="ml-3 text-sm font-medium text-gray-900 dark:text-white">Direct Corporate Wire / Bank Transfer</span>
                            </label>
                            <label class="flex items-center p-3 border rounded-xl border-gray-200 dark:border-gray-700 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <input type="radio" name="payment_method" value="company_credit" class="text-indigo-600">
                                <span class="ml-3 text-sm font-medium text-gray-900 dark:text-white">B2B Net-30 Trade Credit Line</span>
                            </label>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase text-gray-600 dark:text-gray-400 mb-1">PO Notes / Packaging Instructions</label>
                            <input type="text" name="notes" placeholder="e.g. Pallet packaging required." 
                                   class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 text-sm">
                        </div>
                    </div>
                </div>

                <!-- Right: Final Order Confirmation -->
                <div class="lg:col-span-5 space-y-4">
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 space-y-4">
                        <h3 class="text-base font-bold text-gray-900 dark:text-white border-b border-gray-100 dark:border-gray-700 pb-3">Purchase Overview</h3>
                        <div class="divide-y divide-gray-100 dark:divide-gray-700/60 max-h-60 overflow-y-auto">
                            @foreach($items as $item)
                                <div class="py-2.5 flex justify-between items-center text-sm">
                                    <div>
                                        <div class="font-medium text-gray-900 dark:text-white">{{ $item->product->name }}</div>
                                        <div class="text-xs text-gray-500">{{ $item->quantity }} units &times; PKR {{ number_format($item->unit_price, 2) }}</div>
                                    </div>
                                    <div class="font-semibold text-gray-900 dark:text-white">
                                        PKR {{ number_format($item->line_total, 2) }}
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="border-t border-gray-100 dark:border-gray-700 pt-3 space-y-2 text-sm text-gray-600 dark:text-gray-400">
                            <div class="flex justify-between">
                                <span>Subtotal</span>
                                <span>PKR {{ number_format($subtotal, 2) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span>GST (5%)</span>
                                <span>PKR {{ number_format($tax, 2) }}</span>
                            </div>
                            <div class="flex justify-between text-base font-bold text-gray-900 dark:text-white pt-2 border-t border-gray-100 dark:border-gray-700">
                                <span>Grand Total</span>
                                <span class="text-indigo-600 dark:text-indigo-400">PKR {{ number_format($total, 2) }}</span>
                            </div>
                        </div>

                        <button type="submit" 
                                class="w-full py-3.5 px-4 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl shadow-lg shadow-emerald-600/20 transition text-sm">
                            Confirm Commercial Purchase Order
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>