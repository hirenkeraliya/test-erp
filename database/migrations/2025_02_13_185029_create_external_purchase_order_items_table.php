<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('external_purchase_order_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('external_purchase_order_id')->constrained();
            $table->foreignId('purchase_plan_item_id')->constrained();
            $table->foreignId('product_id')->constrained();
            $table->decimal('quantity', 10, 2);
            $table->decimal('received_quantity', 10, 2)->nullable();
            $table->decimal('cost_price', 10, 2);
            $table->decimal('charge_per_unit', 10, 2);
            $table->decimal('total_price', 10, 2);
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }
};
