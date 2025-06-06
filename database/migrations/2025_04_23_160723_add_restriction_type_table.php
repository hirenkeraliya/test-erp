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
        Schema::table('payment_types', function (Blueprint $table): void {
            $table->tinyInteger('restrict_by_zone')->default(0)->nullable()->comment('1 = YES, 0 = NO')->after(
                'url'
            );
            $table->tinyInteger('restriction_type')->nullable()->comment('1 = INCLUSION, 2 = EXCLUSION')->after(
                'restrict_by_zone'
            );
        });
    }
};
