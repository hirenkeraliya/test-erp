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
        Schema::create('online_sales_charge_channel_references', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sale_channel_id')->constrained();
            $table->foreignId('online_sales_charges_id')->constrained()->name('online_sales_charges_fk');
            $table->integer('external_online_sales_charges_id');
            $table->timestamps();
        });
    }
};
