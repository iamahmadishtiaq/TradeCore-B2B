<?php

namespace App\Actions\Payouts;

use App\Models\User;
use App\Models\VendorPayout;
use Illuminate\Support\Facades\DB;

class ProcessPayoutAction
{
    public function approve(VendorPayout $payout, User $admin, ?string $notes = null): VendorPayout
    {
        return DB::transaction(function () use ($payout, $admin, $notes) {
            $payout->update([
                'status' => 'processed',
                'processed_by' => $admin->id,
                'processed_at' => now(),
                'admin_notes' => $notes ?? 'Wire transfer settled via corporate 1-Link clearing.',
            ]);

            return $payout;
        });
    }

    public function reject(VendorPayout $payout, User $admin, string $reason): VendorPayout
    {
        return DB::transaction(function () use ($payout, $admin, $reason) {
            // Refund the deducted amount back to vendor's active wallet balance
            $vendor = $payout->vendor()->lockForUpdate()->firstOrFail();
            $vendor->increment('wallet_balance', $payout->amount);

            $payout->update([
                'status' => 'rejected',
                'processed_by' => $admin->id,
                'processed_at' => now(),
                'admin_notes' => "Payout request rejected: " . $reason,
            ]);

            return $payout;
        });
    }
}