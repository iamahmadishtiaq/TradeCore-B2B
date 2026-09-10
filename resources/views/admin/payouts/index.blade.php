<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    {{ __('Commercial Settlement & Payout Desk') }}
                </h2>
                <p class="text-xs text-gray-500 mt-1">Double-entry audit & bank disbursement clearance terminal</p>
            </div>
            <div class="flex items-center gap-4">
                <div class="text-right">
                    <span class="text-[10px] uppercase font-bold text-amber-500 block">Pending Clearance</span>
                    <span class="text-lg font-black text-gray-900 dark:text-white">PKR {{ number_format($pendingTotal, 2) }}</span>
                </div>
                <div class="text-right border-l pl-4 border-gray-200 dark:border-gray-700">
                    <span class="text-[10px] uppercase font-bold text-emerald-500 block">Total Disbursed</span>
                    <span class="text-lg font-black text-gray-900 dark:text-white">PKR {{ number_format($processedTotal, 2) }}</span>
                </div>
            </div>
        </div>
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

            <!-- Settlement Requests Table -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="p-6 border-b border-gray-100 dark:border-gray-700 flex flex-wrap items-center justify-between gap-4">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">Vendor Withdrawal Claims</h3>
                    
                    <div class="flex items-center gap-2">
                        <a href="{{ route('admin.payouts.index') }}" 
                           class="px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ !request('status') ? 'bg-indigo-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300' }}">
                            All
                        </a>
                        <a href="{{ route('admin.payouts.index', ['status' => 'pending']) }}" 
                           class="px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ request('status') === 'pending' ? 'bg-indigo-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300' }}">
                            Pending
                        </a>
                        <a href="{{ route('admin.payouts.index', ['status' => 'processed']) }}" 
                           class="px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ request('status') === 'processed' ? 'bg-indigo-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300' }}">
                            Processed
                        </a>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
                        <thead class="bg-gray-50 dark:bg-gray-900/60 text-xs font-semibold uppercase text-gray-500 border-b border-gray-200 dark:border-gray-700">
                            <tr>
                                <th class="px-6 py-3">Claim Ref & Date</th>
                                <th class="px-6 py-3">Vendor Entity</th>
                                <th class="px-6 py-3">Banking Coordinates</th>
                                <th class="px-6 py-3 text-right">Amount (PKR)</th>
                                <th class="px-6 py-3 text-center">Clearance</th>
                                <th class="px-6 py-3 text-right">Administrative Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                            @forelse($payouts as $payout)
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-900/20 transition">
                                    <td class="px-6 py-4">
                                        <div class="font-mono font-bold text-indigo-600 dark:text-indigo-400 text-xs">{{ $payout->reference_number }}</div>
                                        <div class="text-[11px] text-gray-400">{{ $payout->created_at->format('M d, Y h:i A') }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-gray-900 dark:text-white">{{ $payout->vendor->store_name }}</div>
                                        <div class="text-[11px] text-gray-400">NTN: {{ $payout->vendor->tax_number ?? 'Reg-Verified' }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-xs font-medium text-gray-900 dark:text-white">{{ $payout->bank_name }}</div>
                                        <div class="text-[11px] text-gray-500 font-mono">{{ $payout->iban_or_account }}</div>
                                        <div class="text-[10px] text-gray-400">Title: {{ $payout->account_title }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-right font-black text-gray-900 dark:text-white">
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
                                    <td class="px-6 py-4 text-right">
                                        @if($payout->status === 'pending')
                                            <div class="flex items-center justify-end gap-2" x-data="{ showReject: false }">
                                                <!-- Quick Approve Form -->
                                                <form method="POST" action="{{ route('admin.payouts.approve', $payout->id) }}" onsubmit="return confirm('Confirm bank wire disbursement?');">
                                                    @csrf
                                                    <button type="submit" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold transition shadow-sm">
                                                        Disburse
                                                    </button>
                                                </form>

                                                <!-- Trigger Reject Modal -->
                                                <button type="button" @click="showReject = !showReject" class="px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-xs font-bold transition shadow-sm">
                                                    Reject
                                                </button>

                                                <!-- Modal for Rejection Reason -->
                                                <div x-show="showReject" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
                                                    <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-2xl max-w-md w-full text-left space-y-4">
                                                        <h4 class="font-bold text-gray-900 dark:text-white text-base">Reject Claim {{ $payout->reference_number }}</h4>
                                                        <p class="text-xs text-gray-500">Funds will be unlocked and returned directly to the vendor's wallet balance.</p>
                                                        
                                                        <form method="POST" action="{{ route('admin.payouts.reject', $payout->id) }}" class="space-y-4">
                                                            @csrf
                                                            <textarea name="rejection_reason" rows="3" required placeholder="State exact discrepancy (e.g. IBAN title mismatch)..."
                                                                      class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"></textarea>

                                                            <div class="flex justify-end gap-2">
                                                                <button type="button" @click="showReject = false" class="px-3 py-1.5 bg-gray-200 dark:bg-gray-700 text-xs rounded-lg font-semibold">Cancel</button>
                                                                <button type="submit" class="px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-lg">Confirm Refund</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        @else
                                            <div class="text-[11px] text-gray-400">
                                                <div>Settled by: {{ $payout->processor?->name ?? 'Automated Desk' }}</div>
                                                @if($payout->admin_notes)
                                                    <div class="italic text-[10px] text-gray-500 truncate max-w-xs">{{ $payout->admin_notes }}</div>
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                        No settlement records currently matched.
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