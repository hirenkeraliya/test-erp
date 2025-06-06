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
        Schema::create('currency_rates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('currency_id')->constrained('currencies');
            $table->decimal('rate', 10, 4);
            $table->timestamps();
        });
    }
};
