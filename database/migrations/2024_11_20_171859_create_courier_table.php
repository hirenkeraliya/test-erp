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
        Schema::create('couriers', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code');
            $table->tinyInteger('type_id');
            $table->string('url');
            $table->string('client_id');
            $table->string('client_secret');
            $table->timestamps();
        });
    }
};
