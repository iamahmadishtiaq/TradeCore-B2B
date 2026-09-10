<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inventory_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->string('batch_number',100);
            $table->integer('quantity_on_hand')->default(0);
            $table->integer('quantity_reserved')->default(0); // While checkout is in progress
            $table->decimal('unit_cost', 12, 2)->default(0.00);
            $table->date('expires_at')->nullable();
            $table->timestamps();

            $table->unique(['product_variant_id', 'warehouse_id', 'batch_number'], 'inv_batches_variant_wh_batch_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_batches');
    }
};
