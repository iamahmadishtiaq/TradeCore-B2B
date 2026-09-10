<nav x-data="{ open: false }" class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('catalog.index') }}" class="font-extrabold text-xl tracking-tight text-indigo-600 dark:text-indigo-400">
                        Trade<span class="text-gray-900 dark:text-white">Core</span> <span class="text-xs px-2 py-0.5 rounded bg-indigo-100 dark:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300 font-bold uppercase">B2B</span>
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('catalog.index')" :active="request()->routeIs('catalog.*')">
                        {{ __('Wholesale Catalog') }}
                    </x-nav-link>

                    @auth
                        <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                            {{ __('Dashboard') }}
                        </x-nav-link>

                        @if(Auth::user()->vendor)
                            <x-nav-link :href="route('vendor.dashboard')" :active="request()->routeIs('vendor.*')">
                                {{ __('Vendor Logistics') }}
                            </x-nav-link>
                        @endif

                        @if(Auth::user()->role === 'admin' || str_contains(strtolower(Auth::user()->email), 'admin'))
                            <x-nav-link :href="route('admin.payouts.index')" :active="request()->routeIs('admin.*')">
                                {{ __('Finance Settlement Desk') }}
                            </x-nav-link>
                        @endif
                    @endauth
                </div>
            </div>

            <!-- Right Nav: Cart & Auth Buttons -->
            <div class="hidden sm:flex sm:items-center sm:ms-6 sm:space-x-4">
                <!-- Cart Link -->
                <a href="{{ route('cart.index') }}" class="relative inline-flex items-center p-2 text-gray-600 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    @php $cartCount = count(session('tradecore_b2b_cart', [])); @endphp
                    @if($cartCount > 0)
                        <span class="absolute -top-1 -right-1 inline-flex items-center justify-center px-2 py-0.5 text-xs font-bold leading-none text-white bg-indigo-600 rounded-full">
                            {{ $cartCount }}
                        </span>
                    @endif
                </a>

                @auth
                    <!-- Settings Dropdown for Authenticated User -->
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none transition ease-in-out duration-150">
                                <div>{{ Auth::user()->name }}</div>

                                <div class="ms-1">
                                    <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <x-dropdown-link :href="route('profile.edit')">
                                {{ __('Profile') }}
                            </x-dropdown-link>

                            @if(Auth::user()->vendor)
                                <x-dropdown-link :href="route('vendor.dashboard')">
                                    {{ __('Vendor Terminal') }}
                                </x-dropdown-link>
                            @endif

                            @if(Auth::user()->role === 'admin' || str_contains(strtolower(Auth::user()->email), 'admin'))
                                <x-dropdown-link :href="route('admin.payouts.index')">
                                    {{ __('Finance Settlement Desk') }}
                                </x-dropdown-link>
                            @endif

                            <!-- Authentication -->
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <x-dropdown-link :href="route('logout')"
                                        onclick="event.preventDefault();
                                                    this.closest('form').submit();">
                                    {{ __('Log Out') }}
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                @else
                    <!-- Guest Links -->
                    <div class="flex items-center space-x-3">
                        <a href="{{ route('login') }}" class="text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400 transition">
                            Log In
                        </a>
                        <a href="{{ route('register') }}" class="px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg transition shadow-sm">
                            Register B2B Account
                        </a>
                    </div>
                @endauth
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-900 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-900 focus:text-gray-500 dark:focus:text-gray-400 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('catalog.index')" :active="request()->routeIs('catalog.*')">
                {{ __('Wholesale Catalog') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('cart.index')">
                {{ __('Procurement Cart') }}
            </x-responsive-nav-link>

            @auth
                <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                    {{ __('Dashboard') }}
                </x-responsive-nav-link>

                @if(Auth::user()->vendor)
                    <x-responsive-nav-link :href="route('vendor.dashboard')" :active="request()->routeIs('vendor.*')">
                        {{ __('Vendor Logistics') }}
                    </x-responsive-nav-link>
                @endif

                @if(Auth::user()->role === 'admin' || str_contains(strtolower(Auth::user()->email), 'admin'))
                    <x-responsive-nav-link :href="route('admin.payouts.index')" :active="request()->routeIs('admin.*')">
                        {{ __('Finance Settlement Desk') }}
                    </x-responsive-nav-link>
                @endif
            @endauth
        </div>

        @auth
            <div class="pt-4 pb-1 border-t border-gray-200 dark:border-gray-600">
                <div class="px-4">
                    <div class="font-medium text-base text-gray-800 dark:text-gray-200">{{ Auth::user()->name }}</div>
                    <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
                </div>

                <div class="mt-3 space-y-1">
                    <x-responsive-nav-link :href="route('profile.edit')">
                        {{ __('Profile') }}
                    </x-responsive-nav-link>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-responsive-nav-link :href="route('logout')"
                                onclick="event.preventDefault();
                                            this.closest('form').submit();">
                            {{ __('Log Out') }}
                        </x-responsive-nav-link>
                    </form>
                </div>
            </div>
        @else
            <div class="pt-4 pb-3 border-t border-gray-200 dark:border-gray-600 px-4 space-y-2">
                <a href="{{ route('login') }}" class="block w-full text-center py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 rounded-lg">
                    Log In
                </a>
                <a href="{{ route('register') }}" class="block w-full text-center py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg">
                    Register B2B Account
                </a>
            </div>
        @endauth
    </div>
</nav>