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
            $table->integer('last_editor_by_id')->nullable()->after('original_created_at');
            $table->string('last_editor_by_type')->nullable()->after('last_editor_by_id');
        });
    }
};
