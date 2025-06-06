<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('mystery_gifts', function (Blueprint $table): void {
            $table->decimal('minimum_spend', 10, 2)->default(0)->after('end_date');
        });
    }
};
