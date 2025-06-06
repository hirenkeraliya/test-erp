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
        Schema::table('goods_received_notes', function (Blueprint $table): void {
            $table->dropColumn('location_type');
        });

        Schema::table('inventories', function (Blueprint $table): void {
            $table->dropColumn('location_type');
        });

        Schema::table('inventory_updates', function (Blueprint $table): void {
            $table->dropColumn('location_type');
        });

        Schema::table('purchase_orders', function (Blueprint $table): void {
            $table->dropColumn('location_type');
            $table->dropColumn('external_location_type');
        });

        Schema::table('sale_return_reasons', function (Blueprint $table): void {
            $table->dropColumn('location_type');
        });

        Schema::table('sequences', function (Blueprint $table): void {
            $table->dropColumn('location_type');
        });

        Schema::table('stock_adjustment_items', function (Blueprint $table): void {
            $table->dropColumn('location_type');
        });

        Schema::table('stock_takes', function (Blueprint $table): void {
            $table->dropColumn('location_type');
        });

        Schema::table('stock_transfer_average_lead_days', function (Blueprint $table): void {
            $table->dropColumn('from_location_type');
            $table->dropColumn('to_location_type');
        });

        Schema::table('stock_transfers', function (Blueprint $table): void {
            $table->dropColumn('created_by_location_type');
            $table->dropColumn('destination_location_type');
            $table->dropColumn('source_location_type');
            $table->dropColumn('transit_location_type');
        });

        Schema::table('external_locations', function (Blueprint $table): void {
            $table->dropColumn('location_type');
        });
    }
};
