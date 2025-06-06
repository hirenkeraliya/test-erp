<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('member_addresses', function (Blueprint $table): void {
            // Add new columns but make them nullable for backward compatibility
            $table->foreignId('country_id')->nullable()->after('area_code');
            $table->foreignId('state_id')->nullable()->after('country_id');
            $table->foreignId('city_id')->nullable()->after('state_id');
            // Keep existing city column but rename it to city_name for clarity
            $table->renameColumn('city', 'city_name');

            // Add foreign key constraints
            $table->foreign('country_id')->references('id')->on('countries');
            $table->foreign('state_id')->references('id')->on('states');
            $table->foreign('city_id')->references('id')->on('cities');
        });
    }
};
