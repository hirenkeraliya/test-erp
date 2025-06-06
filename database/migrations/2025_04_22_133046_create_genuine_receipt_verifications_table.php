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
        Schema::create('genuine_receipt_verifications', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('mobile_number')->size(20);
            $table->foreignId('member_id')->nullable();
            $table->string('receipt_number')->size(50);
            $table->foreignId('sale_id')->nullable();
            $table->boolean('is_genuine')->default(false);
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }
};
