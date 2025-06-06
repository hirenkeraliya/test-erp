<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('purchase_plan_items', function (Blueprint $table): void {
            $table->foreignId('unit_of_measure_derivative_id')->nullable()->after('product_id')->constrained();
        });
    }
};
