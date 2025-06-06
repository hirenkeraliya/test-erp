<?php

declare(strict_types=1);

use App\Models\OnlineSalesCharges;
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
        DB::table('online_sales_charge_channel_references')->truncate();
        DB::table('online_sales_charges_sale_channel')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        OnlineSalesCharges::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        Schema::table('online_sales_charges', function (Blueprint $table): void {
            $table->dropColumn('charge_type_id');
            $table->foreignId('shipping_zone_id')->after('company_id')->constrained();
            $table->decimal('amount', 10, 2)->nullable()->change();
            $table->decimal('minimum_value', 10, 2)->nullable()->change();
            $table->decimal('maximum_value', 10, 2)->nullable()->change();
        });
    }
};
