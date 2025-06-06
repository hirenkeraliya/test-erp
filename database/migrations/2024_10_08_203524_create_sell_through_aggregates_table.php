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
        Schema::create('sell_through_aggregates', function (Blueprint $table): void {
            $table->id();
            $table->date('date')->index();
            $table->foreignId('location_id')->constrained();
            $table->foreignId('product_id')->constrained();
            $table->decimal('goods_receive_note_in', 10, 2)->default(0.0);
            $table->decimal('goods_receive_note_out', 10, 2)->default(0.0);
            $table->decimal('stock_adjustment_in', 10, 2)->default(0.0);
            $table->decimal('stock_adjustment_out', 10, 2)->default(0.0);
            $table->decimal('stock_transfer_in', 10, 2)->default(0.0);
            $table->decimal('stock_transfer_out', 10, 2)->default(0.0);
            $table->decimal('delivery_order_in', 10, 2)->default(0.0);
            $table->decimal('delivery_order_out', 10, 2)->default(0.0);
            $table->decimal('foc_sold', 10, 2)->default(0.0);
            $table->decimal('sold', 10, 2)->default(0.0);
            $table->decimal('return', 10, 2)->default(0.0);
            $table->decimal('balance', 10, 2)->default(0.0);
            $table->timestamps();

            $table->unique(['date', 'location_id', 'product_id']);

            $table->index(['date', 'location_id']);
            $table->index(['product_id', 'location_id']);
            $table->index(['product_id', 'date']);
        });
    }
};
