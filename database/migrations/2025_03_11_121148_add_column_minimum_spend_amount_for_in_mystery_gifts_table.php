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
        Schema::table('mystery_gifts', function (Blueprint $table): void {
            $table->decimal('minimum_spend_amount_for_flat_amount', 10, 2)->nullable()->after('minimum_spend');
            $table->decimal('minimum_spend_amount_for_percentage', 10, 2)->nullable()->after('minimum_spend');
            $table->decimal('minimum_spend_amount_for_free_product', 10, 2)->nullable()->after('minimum_spend');
        });
    }
};
