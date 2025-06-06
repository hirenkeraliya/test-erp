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
        Schema::table('partially_receive_fulfillments', function (Blueprint $table): void {
            $table->string('partially_receive_number')->nullable()->after('status');
        });
    }
};
