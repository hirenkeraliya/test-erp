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
        Schema::create('online_sales_charge_tiers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('online_sales_charges_id')->constrained();
            $table->decimal('min_weight', 10, 2)->comment('kg');
            $table->decimal('max_weight', 10, 2)->comment('kg');
            $table->decimal('amount', 10, 2);
            $table->timestamps();
        });
    }
};
