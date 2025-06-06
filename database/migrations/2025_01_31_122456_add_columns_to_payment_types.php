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
        Schema::table('payment_types', function (Blueprint $table): void {
            $table->string('site_key')->nullable()->after('is_available_in_ecommerce');
            $table->string('secret_key')->nullable()->after('site_key');
        });
    }
};
