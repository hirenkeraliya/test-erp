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
        Schema::create('external_purchase_order_partial_receive_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('external_purchase_order_partial_receive_id')->constrained(indexName: 'epopri_epri_id');
            $table->foreignId('external_purchase_order_item_id')->constrained(indexName: 'epopri_epoi_id');
            $table->decimal('quantity_received', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }
};
