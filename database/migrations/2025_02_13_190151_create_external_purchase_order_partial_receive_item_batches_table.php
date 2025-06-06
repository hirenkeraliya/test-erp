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
        Schema::create('external_purchase_order_partial_receive_item_batches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('external_purchase_order_partial_receive_item_id')->constrained(
                indexName: 'epoprib_epopri_id'
            );
            $table->string('batch_number');
            $table->dateTime('expiry_date');
            $table->decimal('quantity', 10, 2);
            $table->string('notes')->nullable();
            $table->timestamps();
        });
    }
};
