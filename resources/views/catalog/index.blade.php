<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Wholesale B2B Catalog') }}
            </h2>
            <span class="text-xs bg-indigo-100 text-indigo-800 dark:bg-indigo-900/60 dark:text-indigo-300 px-3 py-1 rounded-full font-medium">
                Verified Commercial Pricing
            </span>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <!-- Filters Bar -->
            <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 flex flex-wrap gap-4 items-center justify-between">
                <div class="flex items-center gap-2 overflow-x-auto pb-2 sm:pb-0">
                    <a href="{{ route('catalog.index') }}" 
                       class="px-3 py-1.5 rounded-lg text-sm font-medium transition {{ !request('category') ? 'bg-indigo-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300' }}">
                        All Products
                    </a>
                    @foreach($categories as $category)
                        <a href="{{ route('catalog.index', ['category' => $category->slug]) }}" 
                           class="px-3 py-1.5 rounded-lg text-sm font-medium transition {{ request('category') === $category->slug ? 'bg-indigo-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300' }}">
                            {{ $category->name }}
                        </a>
                    @endforeach
                </div>

                <form method="GET" action="{{ route('catalog.index') }}" class="w-full sm:w-72">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search SKU, equipment, material..." 
                           class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                </form>
            </div>

            <!-- Product Cards Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @forelse($products as $product)
                    @php
                        $defaultVariant = $product->variants->first();
                        $totalStock = $defaultVariant ? $defaultVariant->total_stock : 0;
                    @endphp
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden flex flex-col justify-between hover:border-indigo-500/50 transition">
                        <div class="p-6">
                            <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 mb-2">
                                <span class="uppercase tracking-wider font-semibold text-indigo-500">{{ $product->brand ?? 'Commercial' }}</span>
                                <span class="bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded">MOQ: {{ $product->moq }} pcs</span>
                            </div>

                            <h3 class="text-lg font-bold text-gray-900 dark:text-white line-clamp-2 mb-2">
                                <a href="{{ route('catalog.show', $product->slug) }}" class="hover:text-indigo-600 dark:hover:text-indigo-400">
                                    {{ $product->name }}
                                </a>
                            </h3>

                            <p class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2 mb-4">
                                {{ $product->description }}
                            </p>

                            <div class="border-t border-gray-100 dark:border-gray-700/60 pt-3">
                                <div class="text-xs text-gray-500 dark:text-gray-400">Supplied By:</div>
                                <div class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $product->vendor->store_name }}</div>
                            </div>
                        </div>

                        <div class="bg-gray-50 dark:bg-gray-900/40 p-4 border-t border-gray-100 dark:border-gray-700/60 flex items-center justify-between">
                            <div>
                                <span class="text-xs text-gray-500 block">Wholesale Base Rate</span>
                                <span class="text-lg font-bold text-emerald-600 dark:text-emerald-400">
                                    PKR {{ number_format($defaultVariant?->base_price ?? 0, 2) }}
                                </span>
                            </div>

                            <a href="{{ route('catalog.show', $product->slug) }}" 
                               class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg uppercase tracking-widest transition">
                                View Tiers
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full bg-white dark:bg-gray-800 p-12 text-center rounded-xl border border-gray-200 dark:border-gray-700 text-gray-500">
                        No commercial wholesale listings found.
                    </div>
                @endforelse
            </div>

            <div>
                {{ $products->links() }}
            </div>
        </div>
    </div>
</x-app-layout>