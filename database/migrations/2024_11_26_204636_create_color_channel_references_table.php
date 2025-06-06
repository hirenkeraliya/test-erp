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
        Schema::create('color_channel_references', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sale_channel_id')->constrained();
            $table->foreignId('color_id')->constrained();
            $table->integer('external_color_id');
            $table->timestamps();
        });
    }
};
