<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Vendor Earnings Settlement & Payout Requests') }}
            </h2>
            <a href="{{ route('vendor.dashboard') }}" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">
                &larr; Return to Logistics Terminal
            </a>
        </div>
    </x-slot>

    <div class="py-12">
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

            <!-- Settlement Stats & Payout Request Form -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                
                <!-- Financial Overview Card -->
                <div class="lg:col-span-5 space-y-6">
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 space-y-4">
                        <span class="text-xs uppercase font-bold text-gray-500 tracking-wider">Unencumbered Balance</span>
                        <div class="text-3xl font-black text-emerald-600 dark:text-emerald-400">
                            PKR {{ number_format($vendor->wallet_balance, 2) }}
                        </div>
                        <p class="text-xs text-gray-500 leading-relaxed">
                            Net earnings accumulated from completed, delivered consignment orders after deduction of {{ $vendor->commission_rate }}% platform facilitation fee.
                        </p>
                        <div class="p-4 bg-indigo-50 dark:bg-indigo-950/40 rounded-xl border border-indigo-100 dark:border-indigo-800 text-xs text-indigo-800 dark:text-indigo-300 space-y-1">
                            <div>&bull; Minimum withdrawal threshold: <strong>PKR 5,000.00</strong></div>
                            <div>&bull; Settlement turnaround: <strong>24-48 business hours</strong></div>
                        </div>
                    </div>
                </div>

                <!-- Withdrawal Form -->
                <div class="lg:col-span-7">
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
                        <h3 class="text-base font-bold text-gray-900 dark:text-white pb-3 border-b border-gray-100 dark:border-gray-700 mb-4">
                            Submit Bank Transfer Request
                        </h3>

                        <form method="POST" action="{{ route('vendor.payouts.store') }}" class="space-y-4">
                            @csrf
                            <div>
                                <label class="block text-xs font-semibold uppercase text-gray-600 dark:text-gray-400 mb-1">Withdrawal Amount (PKR)</label>
                                <input type="number" step="0.01" name="amount" value="{{ old('amount') }}" max="{{ $vendor->wallet_balance }}" placeholder="e.g. 50000" required
                                       class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 text-sm">
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold uppercase text-gray-600 dark:text-gray-400 mb-1">Bank Name</label>
                                    <input type="text" name="bank_name" value="{{ old('bank_name') }}" placeholder="Meezan Bank / HBL" required
                                           class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold uppercase text-gray-600 dark:text-gray-400 mb-1">Beneficiary Account Title</label>
                                    <input type="text" name="account_title" value="{{ old('account_title', $vendor->store_name) }}" required
                                           class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 text-sm">
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold uppercase text-gray-600 dark:text-gray-400 mb-1">IBAN / Account Number</label>
                                <input type="text" name="iban_or_account" value="{{ old('iban_or_account') }}" placeholder="PK00MEZN0000000000000000" required
                                       class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 text-sm font-mono">
                            </div>

                            <button type="submit" 
                                    :disabled="{{ $vendor->wallet_balance < 5000 ? 'true' : 'false' }}"
                                    class="w-full py-3 px-4 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white font-bold rounded-xl text-sm transition shadow-sm">
                                Confirm Settlement Withdrawal
                            </button>
                        </form>
                    </div>
                </div>

            </div>

            <!-- Historical Payout Ledger -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="p-6 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">Withdrawal History & Clearance Ledger</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
                        <thead class="bg-gray-50 dark:bg-gray-900/60 text-xs font-semibold uppercase text-gray-500 border-b border-gray-200 dark:border-gray-700">
                            <tr>
                                <th class="px-6 py-3">Reference</th>
                                <th class="px-6 py-3">Settlement Bank</th>
                                <th class="px-6 py-3">Beneficiary</th>
                                <th class="px-6 py-3 text-right">Amount (PKR)</th>
                                <th class="px-6 py-3 text-center">Clearance Status</th>
                                <th class="px-6 py-3">Processed Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                            @forelse($payouts as $payout)
                                <tr>
                                    <td class="px-6 py-4 font-mono font-bold text-xs text-indigo-600 dark:text-indigo-400">
                                        {{ $payout->reference_number }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="font-medium text-gray-900 dark:text-white">{{ $payout->bank_name }}</div>
                                        <div class="text-xs text-gray-400 font-mono">{{ $payout->iban_or_account }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-xs font-medium">{{ $payout->account_title }}</td>
                                    <td class="px-6 py-4 text-right font-bold text-gray-900 dark:text-white">
                                        PKR {{ number_format($payout->amount, 2) }}
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="text-xs px-2.5 py-0.5 rounded-full font-bold uppercase tracking-wider
                                            {{ $payout->status === 'processed' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300' : '' }}
                                            {{ $payout->status === 'pending' ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300' : '' }}
                                            {{ $payout->status === 'rejected' ? 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300' : '' }}">
                                            {{ $payout->status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-xs text-gray-500">
                                        {{ $payout->processed_at ? $payout->processed_at->format('M d, Y') : 'Pending Bank Clearance' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-8 text-center text-gray-500 text-sm">
                                        No settlement requests submitted yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="p-4 border-t border-gray-100 dark:border-gray-700">
                    {{ $payouts->links() }}
                </div>
            </div>

        </div>
    </div>
</x-app-layout>