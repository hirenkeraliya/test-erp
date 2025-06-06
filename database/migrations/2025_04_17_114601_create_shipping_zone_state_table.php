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
        Schema::create('shipping_zone_state', function (Blueprint $table): void {
            $table->foreignId('shipping_zone_id')->constrained();
            $table->foreignId('state_id')->constrained();
        });
    }
};
