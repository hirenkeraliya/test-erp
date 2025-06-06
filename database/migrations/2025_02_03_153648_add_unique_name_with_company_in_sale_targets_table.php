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
        Schema::table('sale_targets', function (Blueprint $table): void {
            $table->dropUnique(['name']);
            $table->unique(['name', 'company_id']);
        });
    }
};
