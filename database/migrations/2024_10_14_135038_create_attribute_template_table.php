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
        Schema::create('attribute_template', function (Blueprint $table): void {
            $table->foreignId('template_id')->constrained();
            $table->foreignId('attribute_id')->constrained();
        });
    }
};
