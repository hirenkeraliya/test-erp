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
        Schema::create('purchase_plan_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('purchase_plan_id')->constrained();
            $table->tinyInteger('old_status')->nullable();
            $table->tinyInteger('new_status');
            $table->bigInteger('user_id')->nullable();
            $table->string('user_type')->nullable();
            $table->timestamps();
        });
    }
};
