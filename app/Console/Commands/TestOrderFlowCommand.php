<?php

namespace App\Console\Commands;

use App\Actions\Orders\PlaceOrderAction;
use App\Actions\Orders\TransitionOrderStatusAction;
use App\DTOs\CheckoutItemDTO;
use App\Enums\OrderStatus;
use App\Models\InventoryBatch;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Console\Command;

class TestOrderFlowCommand extends Command
{
    protected $signature = 'test:order-flow';
    protected $description = 'Run a full verification of checkout, state machine, and vendor payouts';

    public function handle(
        PlaceOrderAction $placeOrderAction,
        TransitionOrderStatusAction $transitionAction
    ): void {
        $this->info('Starting order flow test...');

        $buyer = User::where('email', 'buyer@tradecore.test')->firstOrFail();
        $drillVariant = ProductVariant::where('sku', 'TP-DRL-850W')->firstOrFail();
        $cableVariant = ProductVariant::where('sku', 'EM-CAT6-305M-BLU')->firstOrFail();

        $checkoutItems = [
            new CheckoutItemDTO($drillVariant->id, 15),
            new CheckoutItemDTO($cableVariant->id, 10),
        ];

        $shippingAddress = [
            'company' => 'Crescent Tech Solutions',
            'address' => 'Plot 12, Industrial Area, Sector I-9',
            'city' => 'Islamabad',
            'contact' => '+923001122334',
        ];

        // 1. Checkout
        $order = $placeOrderAction->execute(
            $buyer,
            $checkoutItems,
            $shippingAddress,
            $shippingAddress,
            'bank_transfer',
            'Urgent wholesale dispatch required.'
        );

        $this->info("Order Placed: {$order->order_number} [Status: {$order->status->value}]");

        // 2. Transition: Payment Received
        $order = $transitionAction->execute($order, OrderStatus::PAYMENT_CONFIRMED);
        $this->info("Payment Confirmed: [Status: {$order->status->value}] [Payment: {$order->payment_status}]");

        // 3. Transition: Processing
        $order = $transitionAction->execute($order, OrderStatus::PROCESSING);
        $this->info("Processing Started: [Status: {$order->status->value}]");

        // 4. Transition: Completed (Trigger stock deduction + vendor wallet credit)
        $order = $transitionAction->execute($order, OrderStatus::COMPLETED);
        $this->info("Order Completed: [Status: {$order->status->value}]");

        // 5. Verification Output
        $this->line('-----------------------------------------');
        $this->info("FINANCIAL & INVENTORY SETTLEMENT SUMMARY");

        $vendors = Vendor::all();
        foreach ($vendors as $v) {
            $this->line("Vendor [{$v->store_name}] Wallet Balance: PKR " . number_format($v->wallet_balance, 2));
        }

        $batches = InventoryBatch::all();
        foreach ($batches as $b) {
            $this->line("Batch [{$b->batch_number}] -> On Hand: {$b->quantity_on_hand} | Reserved: {$b->quantity_reserved}");
        }
        $this->line('-----------------------------------------');
    }
}