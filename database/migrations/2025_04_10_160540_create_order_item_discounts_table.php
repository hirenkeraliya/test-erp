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
        Schema::create('order_item_discounts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_item_id')->constrained('order_items');
            $table->string('discountable_type');
            $table->bigInteger('discountable_id')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('promo_code')->nullable();
            $table->timestamps();
        });
    }
};
