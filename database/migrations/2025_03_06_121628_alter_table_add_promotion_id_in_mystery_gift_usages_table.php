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
        Schema::table('mystery_gift_usages', function (Blueprint $table): void {
            if (Schema::hasColumn('mystery_gift_usages', 'promotion_tier_id')) {
                $table->dropForeign(['promotion_tier_id']);
                $table->dropColumn('promotion_tier_id');
            }

            $table->foreignId('promotion_id')->after('sale_id')->nullable()->constrained();
        });
    }
};
