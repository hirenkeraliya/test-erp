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
        Schema::table('goods_received_notes', function (Blueprint $table): void {
            if (Schema::hasColumn('goods_received_notes', 'old_location_id')) {
                $table->dropColumn('old_location_id');
            }
        });

        Schema::table('inventories', function (Blueprint $table): void {
            if ($this->uniqueKeyExists('products', ['product_id', 'location_id', 'location_type'])) {
                $table->dropUnique(['product_id', 'location_id', 'location_type']);
            }

            if (Schema::hasColumn('goods_received_notes', 'old_location_id')) {
                $table->dropColumn('old_location_id');
            }
        });

        Schema::table('inventory_updates', function (Blueprint $table): void {
            if (Schema::hasColumn('inventory_updates', 'old_location_id')) {
                $table->dropColumn('old_location_id');
            }
        });

        Schema::table('purchase_orders', function (Blueprint $table): void {
            if (Schema::hasColumn('purchase_orders', 'old_location_id')) {
                $table->dropColumn('old_location_id');
            }
        });

        Schema::table('sale_return_reasons', function (Blueprint $table): void {
            if (Schema::hasColumn('sale_return_reasons', 'old_location_id')) {
                $table->dropColumn('old_location_id');
            }
        });

        Schema::table('sequences', function (Blueprint $table): void {
            if (Schema::hasColumn('sequences', 'old_location_id')) {
                $table->dropColumn('old_location_id');
            }
        });

        Schema::table('stock_adjustment_items', function (Blueprint $table): void {
            if (Schema::hasColumn('stock_adjustment_items', 'old_location_id')) {
                $table->dropColumn('old_location_id');
            }
        });

        Schema::table('stock_takes', function (Blueprint $table): void {
            if (Schema::hasColumn('stock_takes', 'old_location_id')) {
                $table->dropColumn('old_location_id');
            }
        });

        Schema::table('stock_transfer_average_lead_days', function (Blueprint $table): void {
            if (Schema::hasColumn('stock_transfer_average_lead_days', 'old_from_location_id')) {
                $table->dropColumn('old_from_location_id');
            }

            if (Schema::hasColumn('stock_transfer_average_lead_days', 'old_to_location_id')) {
                $table->dropColumn('old_to_location_id');
            }
        });

        Schema::table('stock_transfers', function (Blueprint $table): void {
            if (Schema::hasColumn('stock_transfers', 'old_source_location_id')) {
                $table->dropColumn('old_source_location_id');
            }

            if (Schema::hasColumn('stock_transfers', 'old_destination_location_id')) {
                $table->dropColumn('old_destination_location_id');
            }

            if (Schema::hasColumn('stock_transfers', 'old_created_by_location_id')) {
                $table->dropColumn('old_created_by_location_id');
            }

            if (Schema::hasColumn('stock_transfers', 'old_transit_location_id')) {
                $table->dropColumn('old_transit_location_id');
            }
        });

        Schema::table('external_locations', function (Blueprint $table): void {
            if (Schema::hasColumn('external_locations', 'old_external_location_id')) {
                $table->dropColumn('old_external_location_id');
            }
        });

        Schema::table('automated_notification_products', function (Blueprint $table): void {
            if ($this->foreignKeyExists('automated_notification_products', 'store_id')) {
                $table->dropForeign(['store_id']);
            }

            if (Schema::hasColumn('automated_notification_products', 'store_id')) {
                $table->dropColumn('store_id');
            }
        });

        Schema::table('automated_notification_stores', function (Blueprint $table): void {
            if ($this->foreignKeyExists('automated_notification_stores', 'store_id')) {
                $table->dropForeign(['store_id']);
            }

            if (Schema::hasColumn('automated_notification_stores', 'store_id')) {
                $table->dropColumn('store_id');
            }
        });

        Schema::table('category_wise_daily_totals', function (Blueprint $table): void {
            if ($this->foreignKeyExists('category_wise_daily_totals', 'store_id')) {
                $table->dropForeign(['store_id']);
            }

            if (Schema::hasColumn('category_wise_daily_totals', 'store_id')) {
                $table->dropColumn('store_id');
            }
        });

        Schema::table('counters', function (Blueprint $table): void {
            $uniqueKeyExists = DB::selectOne("
                SELECT COUNT(*) AS count
                FROM information_schema.statistics
                WHERE TABLE_NAME = 'counters'
                AND INDEX_NAME = 'counters_name_store_id_unique'
                AND TABLE_SCHEMA = DATABASE()
            ");

            if ($uniqueKeyExists && $uniqueKeyExists->count > 0) {
                DB::statement('ALTER TABLE counters DROP INDEX counters_name_store_id_unique');
            }

            if ($this->foreignKeyExists('counters', 'store_id')) {
                $table->dropForeign(['store_id']);
            }

            if (Schema::hasColumn('counters', 'store_id')) {
                $table->dropColumn('store_id');
            }

            $locationUniqueKeyExists = DB::selectOne("
                SELECT COUNT(*) AS count
                FROM information_schema.statistics
                WHERE TABLE_NAME = 'counters'
                AND INDEX_NAME = 'counters_name_location_id_unique'
                AND TABLE_SCHEMA = DATABASE()
            ");

            if (! $locationUniqueKeyExists && $locationUniqueKeyExists->count <= 0) {
                $table->unique(['name', 'location_id']);
            }
        });

        Schema::table('happy_hour_discounts', function (Blueprint $table): void {
            if ($this->foreignKeyExists('happy_hour_discounts', 'store_id')) {
                $table->dropForeign(['store_id']);
            }

            if (Schema::hasColumn('happy_hour_discounts', 'store_id')) {
                $table->dropColumn('store_id');
            }
        });

        Schema::table('order_credit_note_transactions', function (Blueprint $table): void {
            if ($this->foreignKeyExists('order_credit_note_transactions', 'store_id')) {
                $table->dropForeign(['store_id']);
            }

            if (Schema::hasColumn('order_credit_note_transactions', 'store_id')) {
                $table->dropColumn('store_id');
            }
        });

        Schema::table('order_credit_notes', function (Blueprint $table): void {
            if ($this->foreignKeyExists('order_credit_notes', 'store_id')) {
                $table->dropForeign(['store_id']);
            }

            if (Schema::hasColumn('order_credit_notes', 'store_id')) {
                $table->dropColumn('store_id');
            }
        });

        Schema::table('order_payments', function (Blueprint $table): void {
            if ($this->foreignKeyExists('order_payments', 'store_id')) {
                $table->dropForeign(['store_id']);
            }

            if (Schema::hasColumn('order_payments', 'store_id')) {
                $table->dropColumn('store_id');
            }
        });

        Schema::table('order_returns', function (Blueprint $table): void {
            if ($this->foreignKeyExists('order_returns', 'store_id')) {
                $table->dropForeign(['store_id']);
            }

            if (Schema::hasColumn('order_returns', 'store_id')) {
                $table->dropColumn('store_id');
            }
        });

        Schema::table('orders', function (Blueprint $table): void {
            if ($this->foreignKeyExists('orders', 'store_id')) {
                $table->dropForeign(['store_id']);
            }

            $foreignKeyExists = DB::selectOne("
                SELECT COUNT(*) AS count
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_NAME = 'orders'
                AND CONSTRAINT_NAME = 'fk_store_pickup_store_id'
                AND TABLE_SCHEMA = DATABASE()
            ");

            if ($foreignKeyExists && $foreignKeyExists->count > 0) {
                DB::statement('ALTER TABLE orders DROP FOREIGN KEY fk_store_pickup_store_id');
            }

            if (Schema::hasColumn('orders', 'store_id')) {
                $table->dropColumn('store_id');
            }

            if (Schema::hasColumn('orders', 'pickup_store_id')) {
                $table->dropColumn('pickup_store_id');
            }
        });

        Schema::table('past_year_data', function (Blueprint $table): void {
            if ($this->foreignKeyExists('past_year_data', 'store_id')) {
                $table->dropForeign(['store_id']);
            }

            if (Schema::hasColumn('past_year_data', 'store_id')) {
                $table->dropColumn('store_id');
            }
        });

        Schema::table('product_ageings', function (Blueprint $table): void {
            if ($this->foreignKeyExists('product_ageings', 'store_id')) {
                $table->dropForeign(['store_id']);
            }

            if (Schema::hasColumn('product_ageings', 'store_id')) {
                $table->dropColumn('store_id');
            }
        });

        Schema::table('promoter_commission_updates', function (Blueprint $table): void {
            if ($this->foreignKeyExists('promoter_commission_updates', 'store_id')) {
                $table->dropForeign(['store_id']);
            }

            if (Schema::hasColumn('promoter_commission_updates', 'store_id')) {
                $table->dropColumn('store_id');
            }
        });

        Schema::table('store_day_closes', function (Blueprint $table): void {
            if ($this->foreignKeyExists('store_day_closes', 'store_id')) {
                $table->dropForeign(['store_id']);
            }

            if (Schema::hasColumn('store_day_closes', 'store_id')) {
                $table->dropColumn('store_id');
            }
        });

        Schema::table('store_wise_daily_totals', function (Blueprint $table): void {
            if ($this->foreignKeyExists('store_wise_daily_totals', 'store_id')) {
                $table->dropForeign(['store_id']);
            }

            if (Schema::hasColumn('store_wise_daily_totals', 'store_id')) {
                $table->dropColumn('store_id');
            }
        });

        Schema::table('voucher_transactions', function (Blueprint $table): void {
            if ($this->foreignKeyExists('voucher_transactions', 'store_id')) {
                $table->dropForeign(['store_id']);
            }

            if (Schema::hasColumn('voucher_transactions', 'store_id')) {
                $table->dropColumn('store_id');
            }
        });

        Schema::table('inventory_rollback_order_statuses', function (Blueprint $table): void {
            if ($this->foreignKeyExists('inventory_rollback_order_statuses', 'ecommerce_store_id')) {
                $table->dropForeign(['ecommerce_store_id']);
            }

            if (Schema::hasColumn('inventory_rollback_order_statuses', 'ecommerce_store_id')) {
                $table->dropColumn('ecommerce_store_id');
            }
        });

        $foreignKeyExists = DB::selectOne("
            SELECT COUNT(*) AS count
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_NAME = 'members'
            AND CONSTRAINT_NAME = 'customers_created_store_id_foreign'
            AND TABLE_SCHEMA = DATABASE()
        ");

        if ($foreignKeyExists && $foreignKeyExists->count > 0) {
            DB::statement('ALTER TABLE members DROP FOREIGN KEY customers_created_store_id_foreign');
        }

        $memberStoreForeignKeyExists = DB::selectOne("
            SELECT COUNT(*) AS count
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_NAME = 'members'
            AND CONSTRAINT_NAME = 'members_created_store_id_foreign'
            AND TABLE_SCHEMA = DATABASE()
        ");

        if ($memberStoreForeignKeyExists && $memberStoreForeignKeyExists->count > 0) {
            DB::statement('ALTER TABLE members DROP FOREIGN KEY members_created_store_id_foreign');
        }

        Schema::table('members', function (Blueprint $table): void {
            if (Schema::hasColumn('members', 'created_store_id')) {
                $table->dropColumn('created_store_id');
            }
        });

        Schema::table('vouchers', function (Blueprint $table): void {
            if ($this->foreignKeyExists('vouchers', 'created_by_store_id')) {
                $table->dropForeign(['created_by_store_id']);
            }

            if (Schema::hasColumn('vouchers', 'created_by_store_id')) {
                $table->dropColumn('created_by_store_id');
            }
        });

        Schema::table('companies', function (Blueprint $table): void {
            if ($this->foreignKeyExists('companies', 'default_store_id')) {
                $table->dropForeign(['default_store_id']);
            }

            if (Schema::hasColumn('companies', 'default_store_id')) {
                $table->dropColumn('default_store_id');
            }
        });

        Schema::table('sale_channels', function (Blueprint $table): void {
            if ($this->foreignKeyExists('sale_channels', 'default_store_id')) {
                $table->dropForeign(['default_store_id']);
            }

            if (Schema::hasColumn('sale_channels', 'default_store_id')) {
                $table->dropColumn('default_store_id');
            }
        });

        Schema::dropIfExists('brand_store');
        Schema::dropIfExists('cashback_store');
        Schema::dropIfExists('cashier_store');
        Schema::dropIfExists('director_store');
        Schema::dropIfExists('dream_price_store');
        Schema::dropIfExists('ecommerce_stores');
        Schema::dropIfExists('loyalty_campaign_configuration_store');
        Schema::dropIfExists('manual_notification_store');
        Schema::dropIfExists('pos_advertisement_store');
        Schema::dropIfExists('promoter_store');
        Schema::dropIfExists('promotion_store');
        Schema::dropIfExists('sale_channel_store');
        Schema::dropIfExists('sale_target_store');
        Schema::dropIfExists('store_store_manager');
        Schema::dropIfExists('warehouse_warehouse_manager');
        Schema::dropIfExists('automated_notification_store');
        Schema::dropIfExists('stores');
        Schema::dropIfExists('warehouses');
    }

    private function uniqueKeyExists(string $tableName, array $columns): bool
    {
        $indexName = implode('_', $columns) . '_unique';

        $result = DB::select(
            'SELECT COUNT(*) as count
             FROM information_schema.statistics
             WHERE table_schema = DATABASE()
             AND table_name = ?
             AND index_name = ?',
            [$tableName, $indexName]
        );

        return $result[0]->count > 0;
    }

    private function foreignKeyExists(string $tableName, string $columnName): bool
    {
        $constraintName = $tableName . '_' . $columnName . '_foreign';

        $result = DB::select(
            "SELECT COUNT(*) as count
             FROM information_schema.table_constraints
             WHERE table_schema = DATABASE()
             AND table_name = ?
             AND constraint_name = ?
             AND constraint_type = 'FOREIGN KEY'",
            [$tableName, $constraintName]
        );

        return $result[0]->count > 0;
    }
};
