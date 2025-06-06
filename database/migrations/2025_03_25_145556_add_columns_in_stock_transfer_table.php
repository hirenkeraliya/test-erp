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
        Schema::table('stock_transfers', function (Blueprint $table): void {
            $table->integer('average_days')->default(0)->after('received_date');
            $table->boolean('is_transit_target_achieved')->nullable()->after('average_days');
        });
    }
};
