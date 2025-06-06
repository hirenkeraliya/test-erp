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
        Schema::create('drivers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->string('name');
            $table->string('id_number');
            $table->string('email')->nullable();
            $table->string('country_code', 10);
            $table->string('mobile_number');
            $table->boolean('status')->default(true);
            $table->string('created_by_type');
            $table->bigInteger('created_by_id');
            $table->timestamps();

            $table->unique(['id_number', 'company_id']);
            $table->unique(['mobile_number', 'company_id']);
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'name']);
        });
    }
};
