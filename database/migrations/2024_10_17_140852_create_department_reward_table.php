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
        Schema::create('department_reward', function (Blueprint $table): void {
            $table->foreignId('department_id')->constrained();
            $table->foreignId('reward_id')->constrained();
        });
    }
};
