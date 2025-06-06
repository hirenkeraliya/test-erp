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
        Schema::create('payment_type_shipping_zone', function (Blueprint $table): void {
            $table->foreignId('payment_type_id')->constrained('payment_types');
            $table->foreignId('shipping_zone_id')->constrained('shipping_zones');
        });
    }
};
