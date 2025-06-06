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
        Schema::create('external_purchase_orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('purchase_plan_id')->constrained();
            $table->string('order_number');
            $table->date('date');
            $table->text('notes')->nullable();
            $table->decimal('fob', 10, 2)->nullable();
            $table->decimal('freight_charges', 10, 2)->nullable();
            $table->decimal('insurance_charges', 10, 2)->nullable();
            $table->decimal('duty', 10, 2)->nullable();
            $table->decimal('sst', 10, 2)->nullable();
            $table->decimal('handling_charges', 10, 2)->nullable();
            $table->decimal('other_charges', 10, 2)->nullable();
            $table->decimal('total_amount', 10, 2)->nullable();
            $table->tinyInteger('status')->nullable();
            $table->timestamps();
        });
    }
};
