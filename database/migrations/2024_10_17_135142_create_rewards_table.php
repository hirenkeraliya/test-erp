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
        Schema::create('rewards', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->foreignId('company_id')->constrained();
            $table->tinyInteger('type');
            $table->tinyInteger('target_type')->nullable();
            $table->tinyInteger('discount_type')->nullable();
            $table->tinyInteger('status')->nullable();
            $table->decimal('minimum_point', 10, 2)->nullable();
            $table->decimal('maximum_point', 10, 2)->nullable();
            $table->decimal('loyalty_point', 10, 2)->nullable();
            $table->decimal('discount', 10, 2)->nullable();
            $table->integer('created_by_id')->nullable();
            $table->string('created_by_type')->nullable();
            $table->timestamps();
        });
    }
};
