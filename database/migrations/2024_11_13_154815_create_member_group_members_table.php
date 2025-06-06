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
        Schema::create('member_group_members', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('member_group_id')->constrained();
            $table->foreignId('member_id')->constrained();
            $table->boolean('is_synced')->default(1);
            $table->timestamps();
        });
    }
};
