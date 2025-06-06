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
        Schema::table('product_bundles', function (Blueprint $table): void {
            $table->decimal('minimum_price', 10, 2)->nullable()->after('staff_price');
            $table->decimal('purchase_cost', 10, 2)->nullable()->after('minimum_price');
            $table->decimal('wholesale_price', 10, 2)->nullable()->after('purchase_cost');
        });
    }
};
