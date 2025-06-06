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
        Schema::table('sale_item_units', function (Blueprint $table): void {
            $table->foreignId('serial_number_id')->after('batch_id')->nullable()->constrained();
        });
    }
};
