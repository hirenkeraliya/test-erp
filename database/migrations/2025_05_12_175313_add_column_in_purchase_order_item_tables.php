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
        Schema::table('purchase_order_items', function (Blueprint $table): void {
            $table->foreignId('parent_purchase_order_item_id')->after('purchase_order_id')->nullable()->constrained(
                'purchase_order_items'
            );
        });
    }
};
