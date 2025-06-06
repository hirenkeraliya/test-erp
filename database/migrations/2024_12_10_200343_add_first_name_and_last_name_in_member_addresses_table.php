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
        Schema::table('member_addresses', function (Blueprint $table): void {
            $table->string('first_name')->nullable()->after('name')->default(null);
            $table->string('last_name')->nullable()->after('first_name')->default(null);
        });
    }
};
