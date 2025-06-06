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
        Schema::create('mystery_gift_usages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('mystery_gift_id')->nullable()->constrained();
            $table->foreignId('member_id')->nullable()->constrained();
            $table->foreignId('sale_id')->nullable()->constrained();
            $table->foreignId('promotion_id')->nullable()->constrained();
            $table->string('coupon_code', 50)->nullable();
            $table->timestamp('used_at')->nullable();
            $table->integer('used_sale_id')->nullable();
            $table->timestamps();
        });
    }
};
