<?php

declare(strict_types=1);

use App\Domains\Product\Enums\ProductTypes;
use App\Domains\Product\Enums\Statuses;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('master_products', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->foreignId('brand_id')->constrained();
            $table->foreignId('variant_template_id')->constrained('templates');
            $table->string('name');
            $table->string('code')->unique()->nullable();
            $table->text('description')->nullable();
            $table->foreignId('department_id')->nullable()->constrained();
            $table->foreignId('vendor_id')->nullable()->constrained();
            $table->foreignId('unit_of_measure_id')->nullable()->constrained();
            $table->string('article_number')->unique()->nullable();
            $table->tinyInteger('type_id')->default(ProductTypes::REGULAR_PRODUCT->value);
            $table->boolean('has_batch');
            $table->boolean('is_non_inventory');
            $table->boolean('is_non_selling_item')->default(false);
            $table->integer('created_by_id')->nullable();
            $table->string('created_by_type')->nullable();
            $table->dateTime('original_created_at')->nullable();
            $table->tinyInteger('status')->default(Statuses::DRAFT->value);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['article_number', 'company_id']);
        });
    }
};
