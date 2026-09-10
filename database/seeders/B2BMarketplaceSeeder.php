<?php

namespace Database\Seeders;

use App\Enums\StockMovementType;
use App\Models\Category;
use App\Models\InventoryBatch;
use App\Models\PriceTier;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\Vendor;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class B2BMarketplaceSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Categories
        $catHardware = Category::create(['name' => 'Industrial Hardware', 'slug' => 'industrial-hardware']);
        $catTools = Category::create(['parent_id' => $catHardware->id, 'name' => 'Power Tools', 'slug' => 'power-tools']);
        $catElectronics = Category::create(['name' => 'Electronics & Networking', 'slug' => 'electronics-networking']);
        $catCables = Category::create(['parent_id' => $catElectronics->id, 'name' => 'Cables & Wiring', 'slug' => 'cables-wiring']);

        $vendor1 = Vendor::where('slug', 'apex-industrial-tools')->first();
        $vendor2 = Vendor::where('slug', 'nova-electronics-hub')->first();

        // 2. Warehouses
        $wh1 = Warehouse::create([
            'vendor_id' => $vendor1->id,
            'name' => 'Lahore Central Depot',
            'code' => 'WH-LHE-01',
            'city' => 'Lahore',
            'address' => 'Sundar Industrial Estate, Multan Road',
        ]);

        $wh2 = Warehouse::create([
            'vendor_id' => $vendor2->id,
            'name' => 'Karachi Port Logistics',
            'code' => 'WH-KHI-01',
            'city' => 'Karachi',
            'address' => 'West Wharf, Dockyard Road',
        ]);

        // 3. Product 1 (Apex): Heavy Duty Impact Drill
        $drill = Product::create([
            'vendor_id' => $vendor1->id,
            'category_id' => $catTools->id,
            'name' => 'Titan 850W Heavy Duty Impact Drill Machine',
            'slug' => 'titan-850w-impact-drill',
            'description' => 'Commercial grade high torque drill with brushless motor for industrial use.',
            'brand' => 'TitanPro',
            'moq' => 10,
            'status' => 'published',
        ]);

        $drillVariant = ProductVariant::create([
            'product_id' => $drill->id,
            'sku' => 'TP-DRL-850W',
            'title' => '850W Corded / Standard Chuck',
            'base_price' => 8500.00,
            'cost_price' => 5500.00,
            'barcode' => '784512963001',
        ]);

        // Bulk wholesale tiers
        PriceTier::create(['product_variant_id' => $drillVariant->id, 'min_quantity' => 10, 'max_quantity' => 49, 'unit_price' => 8000.00]);
        PriceTier::create(['product_variant_id' => $drillVariant->id, 'min_quantity' => 50, 'max_quantity' => 199, 'unit_price' => 7400.00]);
        PriceTier::create(['product_variant_id' => $drillVariant->id, 'min_quantity' => 200, 'max_quantity' => null, 'unit_price' => 6900.00]);

        // Stock Batch
        $batch1 = InventoryBatch::create([
            'product_variant_id' => $drillVariant->id,
            'warehouse_id' => $wh1->id,
            'batch_number' => 'BATCH-2026-DRL-A',
            'quantity_on_hand' => 500,
            'quantity_reserved' => 0,
            'unit_cost' => 5500.00,
            'expires_at' => null,
        ]);

        StockMovement::create([
            'inventory_batch_id' => $batch1->id,
            'user_id' => $vendor1->user_id,
            'quantity_change' => 500,
            'movement_type' => StockMovementType::RESTOCK,
            'notes' => 'Initial factory import container consignment.',
        ]);

        // 4. Product 2 (Nova): Cat6 Pure Copper Cable Roll
        $cable = Product::create([
            'vendor_id' => $vendor2->id,
            'category_id' => $catCables->id,
            'name' => 'EtherMax Cat6 UTP 305M Pure Copper Cable Roll',
            'slug' => 'ethermax-cat6-utp-305m-cable-roll',
            'description' => 'Gigabit ready high-density 23AWG twisted pair indoor networking cable roll.',
            'brand' => 'EtherMax',
            'moq' => 5,
            'status' => 'published',
        ]);

        $cableVariantBlue = ProductVariant::create([
            'product_id' => $cable->id,
            'sku' => 'EM-CAT6-305M-BLU',
            'title' => '305 Meters / Blue',
            'base_price' => 14500.00,
            'cost_price' => 9800.00,
            'barcode' => '784512963002',
        ]);

        PriceTier::create(['product_variant_id' => $cableVariantBlue->id, 'min_quantity' => 5, 'max_quantity' => 24, 'unit_price' => 13800.00]);
        PriceTier::create(['product_variant_id' => $cableVariantBlue->id, 'min_quantity' => 25, 'max_quantity' => null, 'unit_price' => 12900.00]);

        $batch2 = InventoryBatch::create([
            'product_variant_id' => $cableVariantBlue->id,
            'warehouse_id' => $wh2->id,
            'batch_number' => 'BATCH-2026-CBL-01',
            'quantity_on_hand' => 300,
            'quantity_reserved' => 0,
            'unit_cost' => 9800.00,
            'expires_at' => null,
        ]);

        StockMovement::create([
            'inventory_batch_id' => $batch2->id,
            'user_id' => $vendor2->user_id,
            'quantity_change' => 300,
            'movement_type' => StockMovementType::RESTOCK,
            'notes' => 'Imported via Karachi Port consignee.',
        ]);
    }
}