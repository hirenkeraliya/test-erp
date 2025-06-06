<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class() extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('
            DELETE FROM product_channel_references
            WHERE id NOT IN (
                SELECT latest_id FROM (
                    SELECT MAX(id) AS latest_id
                    FROM product_channel_references
                    WHERE external_variant_id IS NOT NULL
                    GROUP BY sale_channel_id, product_id, external_product_id, external_variant_id
                ) AS sub
            )
        ');
    }
};
