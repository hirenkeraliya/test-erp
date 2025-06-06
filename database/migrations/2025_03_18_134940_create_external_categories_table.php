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
        Schema::create('external_categories', function (Blueprint $table): void {
            $table->id();
            $table->integer('parent_category_id')->nullable()->default(0);
            $table->string('name', 50);
            $table->foreignId('company_id')->constrained();
            $table->bigInteger('sale_channel_id');
            $table->bigInteger('external_category_id');
            $table->timestamps();
        });
    }
};
