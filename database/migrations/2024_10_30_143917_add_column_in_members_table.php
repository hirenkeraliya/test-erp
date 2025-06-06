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
        Schema::table('members', function (Blueprint $table): void {
            $table->dateTime('first_purchase_date')->nullable()->after('last_purchase_date');
            $table->integer('total_return_orders')->default(0)->after('total_orders');
            $table->decimal('total_return_amount', 10, 2)->default(0)->after('total_orders');
        });
    }
};
