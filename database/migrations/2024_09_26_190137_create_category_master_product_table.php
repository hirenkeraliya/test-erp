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
        Schema::create('category_master_product', function (Blueprint $table): void {
            $table->foreignId('category_id')->constrained();
            $table->foreignId('master_product_id')->constrained();
            $table->tinyInteger('sort_order');
        });
    }
};
