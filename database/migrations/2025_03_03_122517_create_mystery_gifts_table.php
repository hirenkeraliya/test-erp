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
        Schema::create('mystery_gifts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->string('name');
            $table->decimal('min_flat_amount', 10, 2)->default(1);
            $table->decimal('max_flat_amount', 10, 2)->nullable();
            $table->decimal('min_percentage', 5, 2)->default(1);
            $table->decimal('max_percentage', 5, 2)->nullable();
            $table->tinyInteger('is_flat_amount')->default(0);
            $table->tinyInteger('is_percentage')->default(0);
            $table->tinyInteger('is_free_product')->default(0);
            $table->date('start_date');
            $table->date('end_date');
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
        });
    }
};
