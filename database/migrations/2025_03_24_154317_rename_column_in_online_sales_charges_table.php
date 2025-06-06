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
        Schema::table('online_sales_charges', function (Blueprint $table): void {
            $table->renameColumn('minimum_amount', 'minimum_value');
            $table->renameColumn('maximum_amount', 'maximum_value');
        });
    }
};
