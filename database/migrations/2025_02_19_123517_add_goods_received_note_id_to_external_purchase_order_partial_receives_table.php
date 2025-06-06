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
        Schema::table('external_purchase_order_partial_receives', function (Blueprint $table): void {
            $table->foreignId('goods_received_note_id')->nullable()->after('external_purchase_order_id')->constrained(
                indexName: 'fk_ext_po_partial_recv_grn'
            );
        });
    }
};
