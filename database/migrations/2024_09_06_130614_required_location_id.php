<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // NOTE: This migration take time 15 mints +
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        Schema::table('automated_notification_products', function (Blueprint $table): void {
            $table->unsignedBigInteger('location_id')->nullable(false)->change();
        });

        Schema::table('automated_notification_stores', function (Blueprint $table): void {
            $table->unsignedBigInteger('location_id')->nullable(false)->change();
        });

        Schema::table('category_wise_daily_totals', function (Blueprint $table): void {
            $table->unsignedBigInteger('location_id')->nullable(false)->change();
        });

        Schema::table('counters', function (Blueprint $table): void {
            $table->unsignedBigInteger('location_id')->nullable(false)->change();
        });

        Schema::table('goods_received_notes', function (Blueprint $table): void {
            $table->unsignedBigInteger('location_id')->nullable(false)->change();
        });

        Schema::table('happy_hour_discounts', function (Blueprint $table): void {
            $table->unsignedBigInteger('location_id')->nullable(false)->change();
        });

        Schema::table('inventories', function (Blueprint $table): void {
            $table->unsignedBigInteger('location_id')->nullable(false)->change();
        });

        Schema::table('inventory_updates', function (Blueprint $table): void {
            $table->unsignedBigInteger('location_id')->nullable(false)->change();
        });

        Schema::table('order_credit_note_transactions', function (Blueprint $table): void {
            $table->unsignedBigInteger('location_id')->nullable(false)->change();
        });

        Schema::table('order_credit_notes', function (Blueprint $table): void {
            $table->unsignedBigInteger('location_id')->nullable(false)->change();
        });

        Schema::table('order_payments', function (Blueprint $table): void {
            $table->unsignedBigInteger('location_id')->nullable(false)->change();
        });

        Schema::table('order_returns', function (Blueprint $table): void {
            $table->unsignedBigInteger('location_id')->nullable(false)->change();
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->unsignedBigInteger('location_id')->nullable(false)->change();
        });

        Schema::table('past_year_data', function (Blueprint $table): void {
            $table->unsignedBigInteger('location_id')->nullable(false)->change();
        });

        Schema::table('product_ageings', function (Blueprint $table): void {
            $table->unsignedBigInteger('location_id')->nullable(false)->change();
        });

        Schema::table('purchase_orders', function (Blueprint $table): void {
            $table->unsignedBigInteger('location_id')->nullable(false)->change();
        });

        Schema::table('sale_channels', function (Blueprint $table): void {
            $table->unsignedBigInteger('default_location_id')->nullable(false)->change();
        });

        Schema::table('stock_adjustment_items', function (Blueprint $table): void {
            $table->unsignedBigInteger('location_id')->nullable(false)->change();
        });

        Schema::table('stock_transfer_average_lead_days', function (Blueprint $table): void {
            $table->unsignedBigInteger('from_location_id')->nullable(false)->change();
            $table->unsignedBigInteger('to_location_id')->nullable(false)->change();
        });

        Schema::table('stock_transfers', function (Blueprint $table): void {
            $table->unsignedBigInteger('source_location_id')->nullable(false)->change();
            $table->unsignedBigInteger('destination_location_id')->nullable(false)->change();
        });

        Schema::table('store_day_closes', function (Blueprint $table): void {
            $table->unsignedBigInteger('location_id')->nullable(false)->change();
        });

        Schema::table('store_wise_daily_totals', function (Blueprint $table): void {
            $table->unsignedBigInteger('location_id')->nullable(false)->change();
        });

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
};
