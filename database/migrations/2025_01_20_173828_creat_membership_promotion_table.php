<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('membership_promotion', function (Blueprint $table): void {
            $table->foreignId('membership_id')->constrained();
            $table->foreignId('promotion_id')->constrained();
        });
    }
};
