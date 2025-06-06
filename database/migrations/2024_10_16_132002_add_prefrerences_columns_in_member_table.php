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
        Schema::table('members', function (Blueprint $table): void {
            $table->foreignId('preferred_product_id')->nullable()->after('pic_contact')->constrained('products');
            $table->foreignId('preferred_color_id')->nullable()->after('preferred_product_id')->constrained('colors');
            $table->foreignId('preferred_size_id')->nullable()->after('preferred_color_id')->constrained('sizes');
            $table->foreignId('preferred_category_id')->nullable()->after('preferred_size_id')->constrained(
                'categories'
            );
            $table->integer('preferred_date')->nullable()->after('preferred_category_id');
            $table->string('preferred_day')->nullable()->after('preferred_date');
            $table->decimal('total_sale_qty', 16, 6)->default(0)->nullable()->after('preferred_day');
        });
    }
};
