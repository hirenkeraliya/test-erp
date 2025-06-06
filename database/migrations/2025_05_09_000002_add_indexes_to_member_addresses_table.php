<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('member_addresses', function (Blueprint $table): void {
            $table->index(['country_id', 'state_id', 'city_id']);
            $table->index('city_name');
        });
    }
};
