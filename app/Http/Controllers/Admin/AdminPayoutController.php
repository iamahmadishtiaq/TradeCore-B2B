<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Payouts\ProcessPayoutAction;
use App\Http\Controllers\Controller;
use App\Models\VendorPayout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminPayoutController extends Controller
{
    public function __construct(
        protected ProcessPayoutAction $processPayoutAction
    ) {}

    /**
     * Display listing of all vendor withdrawal claims.
     */
    public function index(Request $request): View
    {
        $query = VendorPayout::with(['vendor', 'processor'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $payouts = $query->paginate(15)->withQueryString();
        $pendingTotal = VendorPayout::where('status', 'pending')->sum('amount');
        $processedTotal = VendorPayout::where('status', 'processed')->sum('amount');

        return view('admin.payouts.index', compact('payouts', 'pendingTotal', 'processedTotal'));
    }

    /**
     * Approve and mark wire transfer processed.
     */
    public function approve(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'admin_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $payout = VendorPayout::where('status', 'pending')->findOrFail($id);

        $this->processPayoutAction->approve(
            $payout,
            auth()->user(),
            $request->admin_notes
        );

        return back()->with('success', "Payout [{$payout->reference_number}] marked as PROCESSED. Bank settlement finalized.");
    }

    /**
     * Reject claim and refund amount back to vendor wallet.
     */
    public function reject(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
        ]);

        $payout = VendorPayout::where('status', 'pending')->findOrFail($id);

        $this->processPayoutAction->reject(
            $payout,
            auth()->user(),
            $request->rejection_reason
        );

        return back()->with('success', "Payout [{$payout->reference_number}] REJECTED. Funds refunded back to vendor's wallet.");
    }
}