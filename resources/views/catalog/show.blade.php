<x-app-layout>
    <x-slot name="header">
        <nav class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400">
            <a href="{{ route('catalog.index') }}" class="hover:text-indigo-600 transition">Catalog</a>
            <span>/</span>
            <span class="text-gray-800 dark:text-gray-200">{{ $product->category->name }}</span>
            <span>/</span>
            <span class="text-gray-400 truncate max-w-xs">{{ $product->name }}</span>
        </nav>
    </x-slot>

    @php
        $defaultVariant = $product->variants->first();
        $variantsJson = $product->variants->map(function ($variant) use ($product) {
            return [
                'id' => $variant->id,
                'sku' => $variant->sku,
                'title' => $variant->title,
                'base_price' => (float) $variant->base_price,
                'available_stock' => $variant->total_stock,
                'moq' => (int) $product->moq,
                'tiers' => $variant->priceTiers->map(fn ($tier) => [
                    'id' => $tier->id,
                    'min' => (int) $tier->min_quantity,
                    'max' => $tier->max_quantity ? (int) $tier->max_quantity : null,
                    'unit_price' => (float) $tier->unit_price,
                ])->values(),
            ];
        });
    @endphp

    <div class="py-12" 
         x-data="b2bCalculator({ 
             variants: {{ Js::from($variantsJson) }},
             selectedVariantId: {{ $defaultVariant ? $defaultVariant->id : 'null' }}
         })">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('error'))
                <div class="p-4 bg-rose-50 dark:bg-rose-900/30 border border-rose-200 dark:border-rose-800 rounded-xl text-rose-800 dark:text-rose-200 text-sm">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="grid grid-cols-1 lg:grid-cols-12">
                    
                    <!-- Left: Product Information & Specifications -->
                    <div class="lg:col-span-7 p-8 border-b lg:border-b-0 lg:border-r border-gray-100 dark:border-gray-700 space-y-6">
                        <div>
                            <div class="flex items-center gap-3 mb-2">
                                <span class="bg-indigo-50 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-300 text-xs font-semibold px-2.5 py-1 rounded-md">
                                    {{ $product->brand ?? 'Verified Supplier' }}
                                </span>
                                <span class="text-xs text-gray-500">Vendor: <strong>{{ $product->vendor->store_name }}</strong></span>
                            </div>
                            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white">
                                {{ $product->name }}
                            </h1>
                        </div>

                        <div class="prose dark:prose-invert max-w-none text-gray-600 dark:text-gray-300 text-sm leading-relaxed">
                            {{ $product->description }}
                        </div>

                        <!-- Variant Selector -->
                        @if($product->variants->count() > 1)
                            <div class="space-y-2">
                                <label class="block text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                                    Select Variant / Specification
                                </label>
                                <div class="grid grid-cols-2 gap-3">
                                    <template x-for="v in variants" :key="v.id">
                                        <button type="button" 
                                                @click="selectedVariantId = v.id; quantity = Math.max(quantity, v.moq)"
                                                :class="selectedVariantId === v.id ? 'border-indigo-600 ring-2 ring-indigo-500/20 bg-indigo-50/50 dark:bg-indigo-900/20' : 'border-gray-200 dark:border-gray-700'"
                                                class="p-3 rounded-xl border text-left transition">
                                            <div class="font-medium text-sm text-gray-900 dark:text-white" x-text="v.title"></div>
                                            <div class="text-xs text-gray-500" x-text="'SKU: ' + v.sku"></div>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        @endif

                        <!-- Wholesale Tier Rates Table -->
                        <div class="space-y-3">
                            <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                                Bulk Quantity Price Breakdown
                            </h3>
                            <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
                                <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
                                    <thead class="bg-gray-50 dark:bg-gray-900/60 text-xs font-semibold uppercase text-gray-500 border-b border-gray-200 dark:border-gray-700">
                                        <tr>
                                            <th class="px-4 py-3">Order Volume</th>
                                            <th class="px-4 py-3">Unit Rate</th>
                                            <th class="px-4 py-3">Savings / Unit</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                                        <!-- Base Rate -->
                                        <tr :class="!currentTier ? 'bg-amber-50/60 dark:bg-amber-900/20 font-semibold text-amber-900 dark:text-amber-200' : ''">
                                            <td class="px-4 py-2.5" x-text="'Base MOQ (' + activeVariant.moq + ' - ' + (firstTierMin ? (firstTierMin - 1) : '+') + ' units)'"></td>
                                            <td class="px-4 py-2.5">PKR <span x-text="formatNumber(activeVariant.base_price)"></span></td>
                                            <td class="px-4 py-2.5 text-xs text-gray-400">-</td>
                                        </tr>
                                        <!-- Configured Tiers -->
                                        <template x-for="tier in activeVariant.tiers" :key="tier.id">
                                            <tr :class="currentTier && currentTier.id === tier.id ? 'bg-emerald-50 dark:bg-emerald-900/20 font-semibold text-emerald-900 dark:text-emerald-200' : ''">
                                                <td class="px-4 py-2.5" x-text="tier.min + (tier.max ? ' - ' + tier.max : '+') + ' units'"></td>
                                                <td class="px-4 py-2.5">PKR <span x-text="formatNumber(tier.unit_price)"></span></td>
                                                <td class="px-4 py-2.5 text-xs text-emerald-600 dark:text-emerald-400" x-text="'Save PKR ' + formatNumber(activeVariant.base_price - tier.unit_price)"></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>

                    <!-- Right: Live Pricing Calculator & Cart Addition Form -->
                    <div class="lg:col-span-5 bg-gray-50 dark:bg-gray-900/40 p-8 flex flex-col justify-between space-y-6">
                        <div class="space-y-6">
                            <div class="flex items-center justify-between pb-4 border-b border-gray-200 dark:border-gray-700">
                                <div>
                                    <span class="text-xs text-gray-500 uppercase tracking-wider block font-semibold">Active Unit Rate</span>
                                    <div class="text-3xl font-extrabold text-emerald-600 dark:text-emerald-400">
                                        PKR <span x-text="formatNumber(calculatedUnitPrice)"></span>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="text-xs text-gray-500 block">Warehouse Stock</span>
                                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold" 
                                          :class="activeVariant.available_stock > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-500'">
                                        <span class="h-2 w-2 rounded-full" :class="activeVariant.available_stock > 0 ? 'bg-emerald-500' : 'bg-rose-500'"></span>
                                        <span x-text="activeVariant.available_stock + ' units ready'"></span>
                                    </span>
                                </div>
                            </div>

                            <!-- Quantity Input Box -->
                            <div class="space-y-2">
                                <div class="flex justify-between items-center text-xs font-semibold text-gray-700 dark:text-gray-300">
                                    <span>ORDER QUANTITY</span>
                                    <span class="text-gray-500" x-text="'Min Required: ' + activeVariant.moq + ' pcs'"></span>
                                </div>
                                <div class="flex items-center rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 p-1">
                                    <button type="button" 
                                            @click="adjustQty(-10)" 
                                            class="w-10 h-10 flex items-center justify-center rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-200 font-bold hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                                        -
                                    </button>
                                    <input type="number" 
                                           x-model.number="quantity" 
                                           :min="activeVariant.moq"
                                           class="w-full text-center border-0 bg-transparent text-lg font-bold text-gray-900 dark:text-white focus:ring-0">
                                    <button type="button" 
                                            @click="adjustQty(10)" 
                                            class="w-10 h-10 flex items-center justify-center rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-200 font-bold hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                                        +
                                    </button>
                                </div>
                            </div>

                            <!-- Order Summary Box -->
                            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700 space-y-3">
                                <div class="flex justify-between text-sm text-gray-600 dark:text-gray-300">
                                    <span>Subtotal</span>
                                    <span class="font-medium">PKR <span x-text="formatNumber(subtotal)"></span></span>
                                </div>
                                <div class="flex justify-between text-sm text-emerald-600 dark:text-emerald-400 font-medium" x-show="totalSavings > 0">
                                    <span>Wholesale Volume Savings</span>
                                    <span>- PKR <span x-text="formatNumber(totalSavings)"></span></span>
                                </div>
                                <div class="flex justify-between text-sm text-gray-600 dark:text-gray-300">
                                    <span>Estimated GST / Tax (5%)</span>
                                    <span class="font-medium">PKR <span x-text="formatNumber(estimatedTax)"></span></span>
                                </div>
                                <div class="border-t border-gray-100 dark:border-gray-700 pt-3 flex justify-between items-baseline">
                                    <span class="text-base font-bold text-gray-900 dark:text-white">Estimated Total</span>
                                    <span class="text-xl font-black text-indigo-600 dark:text-indigo-400">
                                        PKR <span x-text="formatNumber(totalWithTax)"></span>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Action Form: Posts directly to CartService -->
                        <form method="POST" action="{{ route('cart.add') }}" class="space-y-2">
                            @csrf
                            <input type="hidden" name="variant_id" :value="activeVariant.id">
                            <input type="hidden" name="quantity" :value="quantity">

                            <button type="submit" 
                                    :disabled="quantity < activeVariant.moq || quantity > activeVariant.available_stock"
                                    class="w-full py-3.5 px-4 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold rounded-xl shadow-lg shadow-indigo-600/30 transition text-sm">
                                Add Volume to Wholesale Order
                            </button>
                            <p class="text-center text-xs text-gray-400">
                                Commercial trade invoice generated under company NTN.
                            </p>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('b2bCalculator', ({ variants, selectedVariantId }) => ({
                variants: variants,
                selectedVariantId: selectedVariantId,
                quantity: 1,

                init() {
                    if (this.activeVariant) {
                        this.quantity = this.activeVariant.moq;
                    }
                },

                get activeVariant() {
                    return this.variants.find(v => v.id === this.selectedVariantId) || this.variants[0];
                },

                get firstTierMin() {
                    return this.activeVariant.tiers.length > 0 ? this.activeVariant.tiers[0].min : null;
                },

                get currentTier() {
                    return this.activeVariant.tiers.find(t => {
                        if (this.quantity >= t.min) {
                            return t.max === null || this.quantity <= t.max;
                        }
                        return false;
                    }) || null;
                },

                get calculatedUnitPrice() {
                    return this.currentTier ? this.currentTier.unit_price : this.activeVariant.base_price;
                },

                get subtotal() {
                    return this.calculatedUnitPrice * this.quantity;
                },

                get totalSavings() {
                    return (this.activeVariant.base_price - this.calculatedUnitPrice) * this.quantity;
                },

                get estimatedTax() {
                    return this.subtotal * 0.05;
                },

                get totalWithTax() {
                    return this.subtotal + this.estimatedTax;
                },

                adjustQty(amount) {
                    let next = this.quantity + amount;
                    if (next >= this.activeVariant.moq) {
                        this.quantity = next;
                    }
                },

                formatNumber(val) {
                    return new Intl.NumberFormat('en-PK', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(val);
                }
            }));
        });
    </script>
</x-app-layout>