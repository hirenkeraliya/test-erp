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
        Schema::create('online_order_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained();
            $table->tinyInteger('old_status');
            $table->tinyInteger('new_status');
            $table->json('response')->nullable();
            $table->timestamps();
        });
    }
};
