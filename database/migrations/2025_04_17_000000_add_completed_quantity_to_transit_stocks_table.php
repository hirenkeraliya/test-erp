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
        Schema::table('transit_stocks', function (Blueprint $table): void {
            $table->decimal('completed_quantity', 14, 6)->after('quantity')->default(0);
        });
    }
};
