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
        Schema::table('genuine_receipt_verifications', function (Blueprint $table): void {
            $table->string('name')->nullable()->change();
            $table->string('email')->nullable()->change();
            $table->string('mobile_number')->nullable()->change();
        });
    }
};
