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
        Schema::create('product_channel_reference_categories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_channel_references_id')
                ->constrained('product_channel_references', 'id', 'fk_product_channel_ref_cat')
                ->onDelete('cascade');
            $table->bigInteger('external_category_id')->nullable();
            $table->timestamps();
        });
    }
};
