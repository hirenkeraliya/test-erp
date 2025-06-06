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
        Schema::create('product_collection_filter_attribute_values', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('product_collection_filter_id');
            $table->foreign('product_collection_filter_id', 'pcf_attr_val_pcf_id_foreign')->references('id')->on(
                'product_collection_filters'
            );
            $table->foreignId('attribute_id')->constrained();
            $table->string('value');
            $table->timestamps();
        });
    }
};
