<?php

namespace Tests\Feature;

use App\Actions\Orders\PlaceOrderAction;
use App\Actions\Payouts\ProcessPayoutAction;
use App\Actions\Payouts\RequestPayoutAction;
use App\Enums\OrderStatus;
use App\Models\Category;
use App\Models\InventoryBatch;
use App\Models\Order;
use App\Models\PriceTier;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Warehouse;
use App\Services\Cache\PriceTierCacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class B2BMarketplaceCorePipelineTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $buyer;
    protected Vendor $vendorA;
    protected Vendor $vendorB;
    protected ProductVariant $variantA;
    protected ProductVariant $variantB;
    protected Warehouse $warehouseA;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Setup Admin & Buyer
        $this->admin = User::factory()->create([
            'email' => 'admin@tradecore.test',
            'role' => 'admin',
        ]);

        $this->buyer = User::factory()->create([
            'email' => 'corporate@buyer.test',
            'role' => 'buyer',
        ]);

        // 2. Setup Category
        $category = Category::create([
            'name' => 'Industrial Hardware',
            'slug' => 'industrial-hardware',
        ]);

        // 3. Setup Vendor A
        $vendorUserA = User::factory()->create(['role' => 'vendor']);
        $this->vendorA = Vendor::create([
            'user_id' => $vendorUserA->id,
            'store_name' => 'Apex Industrial',
            'slug' => 'apex-industrial',
            'business_email' => 'supply@apexindustrial.test',
            'business_phone' => '+923001234567',
            'tax_number' => 'NTN-7890123-1',
            'commission_rate' => 10.00,
            'status' => 'approved',
            'wallet_balance' => 0.00,
        ]);

        $this->warehouseA = Warehouse::create([
            'vendor_id' => $this->vendorA->id,
            'name' => 'Apex Central Warehouse',
            'code' => 'WH-APX-01',
            'address' => 'Plot 44, Industrial Area',
            'city' => 'Lahore',
            'is_active' => true,
        ]);

        $productA = Product::create([
            'vendor_id' => $this->vendorA->id,
            'category_id' => $category->id,
            'name' => 'Impact Drill 850W',
            'slug' => 'impact-drill-850w',
            'description' => 'Heavy duty drill',
            'moq' => 10,
            'status' => 'published',
        ]);

        $this->variantA = ProductVariant::create([
            'product_id' => $productA->id,
            'sku' => 'DRL-850-IND',
            'title' => 'Standard Industrial Pack',
            'base_price' => 10000.00,
        ]);

        PriceTier::create([
            'product_variant_id' => $this->variantA->id,
            'min_quantity' => 50,
            'max_quantity' => 99,
            'unit_price' => 8500.00,
        ]);

        PriceTier::create([
            'product_variant_id' => $this->variantA->id,
            'min_quantity' => 100,
            'max_quantity' => null,
            'unit_price' => 7500.00,
        ]);

        InventoryBatch::create([
            'product_variant_id' => $this->variantA->id,
            'warehouse_id' => $this->warehouseA->id,
            'batch_number' => 'BATCH-TEST-001',
            'quantity_on_hand' => 500,
            'quantity_reserved' => 0,
            'unit_cost' => 6000.00,
        ]);

        // 4. Setup Vendor B (For Split Shipment Validation)
        $vendorUserB = User::factory()->create(['role' => 'vendor']);
        $this->vendorB = Vendor::create([
            'user_id' => $vendorUserB->id,
            'store_name' => 'Nova Electronics',
            'slug' => 'nova-electronics',
            'business_email' => 'contact@novaelectronics.test',
            'business_phone' => '+923009876543',
            'tax_number' => 'NTN-4567890-2',
            'commission_rate' => 8.00,
            'status' => 'approved',
            'wallet_balance' => 0.00,
        ]);

        $warehouseB = Warehouse::create([
            'vendor_id' => $this->vendorB->id,
            'name' => 'Nova Logistics Hub',
            'code' => 'WH-NOV-01',
            'address' => 'Federal B Area',
            'city' => 'Karachi',
            'is_active' => true,
        ]);

        $productB = Product::create([
            'vendor_id' => $this->vendorB->id,
            'category_id' => $category->id,
            'name' => 'Cat6 Network Spool 305M',
            'slug' => 'cat6-network-spool',
            'description' => 'Pure copper network cable',
            'moq' => 5,
            'status' => 'published',
        ]);

        $this->variantB = ProductVariant::create([
            'product_id' => $productB->id,
            'sku' => 'CAT6-305M-COP',
            'title' => 'Blue Spool',
            'base_price' => 15000.00,
        ]);

        InventoryBatch::create([
            'product_variant_id' => $this->variantB->id,
            'warehouse_id' => $warehouseB->id,
            'batch_number' => 'BATCH-NOV-001',
            'quantity_on_hand' => 200,
            'quantity_reserved' => 0,
            'unit_cost' => 10000.00,
        ]);
    }

    #[Test]
    public function test_it_accurately_computes_wholesale_tiered_pricing_with_redis_cache(): void
    {
        $cacheService = app(PriceTierCacheService::class);

        // Test tier 1 (quantity 60 => rate 8,500)
        $quoteTier1 = $cacheService->calculatePrice($this->variantA->id, 60);
        $this->assertEquals(8500.00, $quoteTier1['unit_price']);
        $this->assertEquals(510000.00, $quoteTier1['line_total']);
        $this->assertTrue($quoteTier1['is_tier_applied']);

        // Test tier 2 (quantity 120 => rate 7,500)
        $quoteTier2 = $cacheService->calculatePrice($this->variantA->id, 120);
        $this->assertEquals(7500.00, $quoteTier2['unit_price']);
        $this->assertEquals(900000.00, $quoteTier2['line_total']);

        // Test sub-tier base rate (quantity 15 => base rate 10,000)
        $quoteBase = $cacheService->calculatePrice($this->variantA->id, 15);
        $this->assertEquals(10000.00, $quoteBase['unit_price']);
        $this->assertFalse($quoteBase['is_tier_applied']);
    }

    #[Test]
    public function test_it_splits_order_into_distinct_vendor_shipments_and_reserves_inventory_batches(): void
    {
        $orderAction = app(PlaceOrderAction::class);

        $cartItems = [
            (object) [
                'productVariantId' => $this->variantA->id,
                'quantity' => 50,
                'unitPrice' => 8500.00,
                'lineTotal' => 425000.00,
                'vendorId' => $this->vendorA->id,
            ],
            (object) [
                'productVariantId' => $this->variantB->id,
                'quantity' => 10,
                'unitPrice' => 15000.00,
                'lineTotal' => 150000.00,
                'vendorId' => $this->vendorB->id,
            ],
        ];

        $shippingAddress = [
            'company' => 'Crescent Tech Solutions',
            'contact' => '03001234567',
            'address' => 'Gulberg III',
            'city' => 'Lahore',
        ];

        $billingAddress = [
            'company' => 'Crescent Tech Solutions',
            'contact' => '03001234567',
            'address' => 'Gulberg III',
            'city' => 'Lahore',
        ];

        $order = $orderAction->execute(
            $this->buyer,
            $cartItems,
            $shippingAddress,
            $billingAddress,
            'bank_wire'
        );

        $this->assertInstanceOf(Order::class, $order);
        $this->assertEquals(OrderStatus::PENDING_PAYMENT, $order->status);
        $this->assertEquals(2, $order->shipments()->count());

        $batchA = InventoryBatch::where('product_variant_id', $this->variantA->id)->first();
        $this->assertEquals(50, $batchA->quantity_reserved);
        $this->assertEquals(450, $batchA->available_quantity);
    }

    #[Test]
    public function test_it_handles_vendor_payout_escrow_deduction_and_admin_settlement_workflow(): void
    {
        $this->vendorA->update(['wallet_balance' => 80000.00]);

        $requestPayoutAction = app(RequestPayoutAction::class);
        $processPayoutAction = app(ProcessPayoutAction::class);

        $payout = $requestPayoutAction->execute(
            $this->vendorA,
            50000.00,
            [
                'bank_name' => 'Meezan Bank',
                'account_title' => 'Apex Industrial Tools PVT',
                'iban_or_account' => 'PK44MEZN0001234567890123',
            ]
        );

        $this->vendorA->refresh();
        $this->assertEquals(30000.00, $this->vendorA->wallet_balance);
        $this->assertEquals('pending', $payout->status);

        $processPayoutAction->approve($payout, $this->admin, '1-Link wire transacted');

        $payout->refresh();
        $this->assertEquals('processed', $payout->status);
        $this->assertEquals($this->admin->id, $payout->processed_by);
        $this->assertNotNull($payout->processed_at);
    }

    #[Test]
    public function test_it_refunds_escrow_funds_when_admin_rejects_payout(): void
    {
        $this->vendorA->update(['wallet_balance' => 20000.00]);

        $requestPayoutAction = app(RequestPayoutAction::class);
        $processPayoutAction = app(ProcessPayoutAction::class);

        $payout = $requestPayoutAction->execute(
            $this->vendorA,
            15000.00,
            [
                'bank_name' => 'HBL',
                'account_title' => 'Apex Industrial',
                'iban_or_account' => 'PK00HABB0000000000000000',
            ]
        );

        $this->vendorA->refresh();
        $this->assertEquals(5000.00, $this->vendorA->wallet_balance);

        $processPayoutAction->reject($payout, $this->admin, 'Account title verification failed with branch');

        $payout->refresh();
        $this->vendorA->refresh();

        $this->assertEquals('rejected', $payout->status);
        $this->assertEquals(20000.00, $this->vendorA->wallet_balance);
    }
}