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
        Schema::create('online_sales_charges_sale_channel', function (Blueprint $table): void {
            $table->foreignId('online_sales_charges_id')->constrained()->index('idx_online_sales');
            $table->foreignId('sale_channel_id')->constrained();
        });
    }
};
