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
        Schema::create('dream_price_channel_references', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sale_channel_id')->constrained();
            $table->foreignId('dream_price_id')->constrained()->name('dream_price_fk');
            $table->integer('external_dream_price_id');
            $table->timestamps();
        });
    }
};
