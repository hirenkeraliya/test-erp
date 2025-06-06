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
        Schema::create('member_product_reviews', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('member_id')->constrained('members');
            $table->foreignId('product_id')->constrained('products');
            $table->text('review');
            $table->unsignedTinyInteger('rating');
            $table->timestamps();
        });
    }
};
