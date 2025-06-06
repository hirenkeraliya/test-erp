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
        Schema::table('order_channel_references', function (Blueprint $table): void {
            $table->unique(
                ['sale_channel_id', 'external_order_id'],
                'order_channel_references_order_id_channel_id_unique'
            );
        });
    }
};
