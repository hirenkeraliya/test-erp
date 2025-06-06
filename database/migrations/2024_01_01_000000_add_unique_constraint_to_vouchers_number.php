<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('vouchers', function (Blueprint $table) {
            // Add unique constraint to the number column to prevent race conditions
            // This ensures that only one voucher can have a specific number at the database level
            $table->unique('number', 'vouchers_number_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('vouchers', function (Blueprint $table) {
            // Remove the unique constraint
            $table->dropUnique('vouchers_number_unique');
        });
    }
};