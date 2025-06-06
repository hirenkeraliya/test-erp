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
        Schema::table('email_templates', function (Blueprint $table): void {
            $table->integer('usage')->default(0)->after('name');
            $table->integer('clicks')->default(0)->after('usage');
            $table->decimal('revenue', 10, 2)->default(0)->after('clicks');
            $table->decimal('conversion', 10, 2)->default(0)->after('revenue');
        });
    }
};
