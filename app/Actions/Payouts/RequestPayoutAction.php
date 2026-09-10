<?php

namespace App\Actions\Payouts;

use App\Models\Vendor;
use App\Models\VendorPayout;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RequestPayoutAction
{
    public function execute(Vendor $vendor, float $amount, array $bankDetails): VendorPayout
    {
        return DB::transaction(function () use ($vendor, $amount, $bankDetails) {
            // Lock vendor record to prevent double-spending
            $vendor = Vendor::where('id', $vendor->id)->lockForUpdate()->firstOrFail();

            if ($vendor->wallet_balance < $amount) {
                throw new \InvalidArgumentException("Requested amount exceeds current available settlement balance.");
            }

            if ($amount < 5000.00) {
                throw new \InvalidArgumentException("Minimum B2B vendor payout threshold is PKR 5,000.00.");
            }

            // Deduct immediately to hold in escrow
            $vendor->decrement('wallet_balance', $amount);

            return VendorPayout::create([
                'vendor_id' => $vendor->id,
                'reference_number' => 'PO-' . strtoupper(Str::random(6)) . '-' . date('Ymd'),
                'amount' => $amount,
                'status' => 'pending',
                'bank_name' => $bankDetails['bank_name'],
                'account_title' => $bankDetails['account_title'],
                'iban_or_account' => $bankDetails['iban_or_account'],
            ]);
        });
    }
}