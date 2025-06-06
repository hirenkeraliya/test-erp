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
        Schema::create('voucher_configuration_sale_channel', function (Blueprint $table): void {
            $table->foreignId('voucher_configuration_id');
            $table->foreignId('sale_channel_id')->constrained();

            $table->foreign('voucher_configuration_id', 'voucher_configuration_id_foreign')
                ->references('id')
                ->on('voucher_configurations');
        });
    }
};
