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
        Schema::table('sell_through_aggregates', function (Blueprint $table): void {
            $table->decimal('sale_amount', 20, 4)->after('sold')->nullable();
            $table->decimal('sale_return_amounts', 20, 4)->after('return')->nullable();
            $table->decimal('sold_online', 20, 4)->after('sale_amount')->nullable();
            $table->decimal('foc_sold_online', 20, 4)->after('sold_online')->nullable();
            $table->decimal('total_online_sold_amount', 20, 4)->after('foc_sold_online')->nullable();
        });
    }
};
