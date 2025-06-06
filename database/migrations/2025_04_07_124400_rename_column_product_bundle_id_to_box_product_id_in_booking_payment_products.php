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
        Schema::table('booking_payment_products', function (Blueprint $table): void {
            $table->renameColumn('product_bundle_id', 'box_product_id');
            $table->renameColumn('product_bundle_package_type_id', 'product_box_package_type_id');
            $table->renameColumn('product_bundle_units', 'product_box_units');
        });
    }
};
