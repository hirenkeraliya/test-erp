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
        Schema::table('booking_payment_refunds', function (Blueprint $table): void {
            $table->foreignId('currency_id')->nullable()->after('payment_type_id')->constrained('currencies');
            $table->decimal('currency_rate', 10, 2)->nullable()->after('currency_id');
            $table->decimal('currency_amount', 10, 2)->nullable()->after('currency_rate');
        });
    }
};
