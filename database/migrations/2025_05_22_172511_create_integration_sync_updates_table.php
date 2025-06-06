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
        Schema::create('integration_sync_updates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('integration_id')->constrained();
            $table->string('module_type');
            $table->dateTime('last_sync_date');
            $table->timestamps();
        });
    }
};
