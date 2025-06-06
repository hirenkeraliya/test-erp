<?php

use App\Domains\SerialNumber\Enums\SerialNumberStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('serial_numbers', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('inventory_unit_id');
        });

        Schema::table('serial_numbers', function (Blueprint $table): void {
            $table->tinyInteger('status')->after('serial_number')->default(SerialNumberStatus::ACTIVE->value);
        });
    }
};
