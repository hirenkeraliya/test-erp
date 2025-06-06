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
        Schema::create('integrations', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 50);
            $table->foreignId('company_id');
            $table->string('url');
            $table->string('connection_type');
            $table->string('secret');
            $table->boolean('status')->default(1);
            $table->timestamps();
        });
    }
};
