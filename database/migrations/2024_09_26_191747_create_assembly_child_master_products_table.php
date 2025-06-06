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
        Schema::create('assembly_child_master_products', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('master_product_id')->constrained('master_products');
            $table->foreignId('child_master_product_id')->constrained('master_products');
            $table->decimal('units', 14, 6);
            $table->timestamps();
            $table->softDeletes();
        });
    }
};
