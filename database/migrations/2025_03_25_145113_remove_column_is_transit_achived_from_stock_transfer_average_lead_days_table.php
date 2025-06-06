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
        Schema::table('stock_transfer_average_lead_days', function (Blueprint $table): void {
            if (Schema::hasColumn('stock_transfer_average_lead_days', 'is_transit_target_achieved')) {
                $table->dropColumn('is_transit_target_achieved');
            }
        });
    }
};
