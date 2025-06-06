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
        Schema::table('sale_channels', function (Blueprint $table): void {
            $table->tinyInteger('display_dynamic_menus')
                ->default(0)
                ->comment('0: category, 1: dynamic')
                ->after('display_variants');
        });
    }
};
