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
        Schema::table('automated_notifications', function (Blueprint $table): void {
            $table->string('name')->after('type_id');
            $table->text('description')->after('name')->nullable();
        });
    }
};
