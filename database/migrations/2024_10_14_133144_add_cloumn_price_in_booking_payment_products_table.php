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
        Schema::table('booking_payment_products', function (Blueprint $table): void {
            $table->decimal('price', 10, 2)->nullable()->after('quantity');
        });
    }
};
