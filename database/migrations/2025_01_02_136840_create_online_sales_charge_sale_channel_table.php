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
        Schema::create('payment_type_sale_channel', function (Blueprint $table): void {
            $table->foreignId('payment_type_id')->constrained();
            $table->foreignId('sale_channel_id')->constrained();
        });
    }
};
