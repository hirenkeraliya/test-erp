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
        Schema::table('dynamic_menus', function (Blueprint $table): void {
            $table->tinyInteger('status')->comment('0 => inactive, 1 => active')->after('content');
        });
    }
};
