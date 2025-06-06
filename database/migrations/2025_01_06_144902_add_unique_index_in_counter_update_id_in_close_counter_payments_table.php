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
        Schema::table('close_counter_payments', function (Blueprint $table): void {
            $table->unique(['counter_update_id', 'payment_type_id'], 'unique_counter_update_payment_type_id');
        });
    }
};
