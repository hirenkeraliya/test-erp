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
        Schema::table('products', function (Blueprint $table): void {
            $table->decimal('height', 10, 2)->default(0.0);
            $table->decimal('width', 10, 2)->default(0.0);
            $table->decimal('weight', 10, 2)->default(0.0);
        });
    }
};
