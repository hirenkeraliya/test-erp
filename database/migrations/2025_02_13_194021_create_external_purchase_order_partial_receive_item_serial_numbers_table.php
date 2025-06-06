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
        Schema::create(
            'external_purchase_order_partial_receive_item_serial_numbers',
            function (Blueprint $table): void {
                $table->id();
                $table->foreignId('external_purchase_order_partial_receive_item_id')->constrained(
                    indexName: 'epopris_epopri_id'
                );
                $table->string('serial_number');
                $table->timestamps();
            }
        );
    }
};
