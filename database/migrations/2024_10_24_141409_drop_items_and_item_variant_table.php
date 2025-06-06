<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('assembly_child_items');
        Schema::dropIfExists('category_item');
        Schema::dropIfExists('item_tag');
        Schema::dropIfExists('item_inventories');
        Schema::dropIfExists('item_inventory_updates');
        Schema::dropIfExists('item_variant_loyalty_points');
        Schema::dropIfExists('bundle_item_variant_loyalty_points');
        Schema::dropIfExists('item_variant_bundles');
        Schema::dropIfExists('item_variant_values');
        Schema::dropIfExists('item_variants');
        Schema::dropIfExists('items');
    }
};
