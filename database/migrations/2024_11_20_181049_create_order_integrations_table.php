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
        Schema::create('order_integrations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained();
            $table->foreignId('courier_id')->constrained();
            $table->tinyInteger('status');
            $table->string('tracking_number')->nullable();
            $table->json('response')->nullable();
            $table->timestamps();
        });
    }
};
