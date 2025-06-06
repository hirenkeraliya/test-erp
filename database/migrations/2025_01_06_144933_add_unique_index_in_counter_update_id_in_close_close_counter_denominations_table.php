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
        Schema::table('close_counter_denominations', function (Blueprint $table): void {
            $table->unique(['counter_update_id', 'denomination'], 'unique_counter_update_id_denomination');
        });
    }
};
