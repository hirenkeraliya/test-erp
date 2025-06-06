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
        Schema::create('external_purchase_order_partial_receives', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('external_purchase_order_id')->constrained(indexName: 'epopr_epoi_id');
            $table->tinyInteger('status');
            $table->dateTime('received_date');
            $table->string('notes');
            $table->timestamps();
        });
    }
};
