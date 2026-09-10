<?php

namespace App\Http\Controllers;

use App\Actions\Payouts\RequestPayoutAction;
use App\Models\VendorPayout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VendorPayoutController extends Controller
{
    public function __construct(
        protected RequestPayoutAction $requestPayoutAction
    ) {}

    public function index(): View
    {
        $vendor = auth()->user()->vendor;
        $payouts = VendorPayout::where('vendor_id', $vendor->id)->latest()->paginate(10);

        return view('vendor.payouts.index', compact('vendor', 'payouts'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:5000'],
            'bank_name' => ['required', 'string', 'max:100'],
            'account_title' => ['required', 'string', 'max:150'],
            'iban_or_account' => ['required', 'string', 'max:50'],
        ]);

        $vendor = auth()->user()->vendor;

        try {
            $payout = $this->requestPayoutAction->execute(
                $vendor,
                (float) $validated['amount'],
                [
                    'bank_name' => $validated['bank_name'],
                    'account_title' => $validated['account_title'],
                    'iban_or_account' => $validated['iban_or_account'],
                ]
            );

            return back()->with('success', "Payout request [{$payout->reference_number}] submitted for clearance.");
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Unable to initiate payout: ' . $e->getMessage());
        }
    }
}