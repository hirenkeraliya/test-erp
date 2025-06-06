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
        Schema::create('vehicles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->string('name');
            $table->string('plate_no', 50);
            $table->string('type_of_vehicle')->nullable();
            $table->boolean('status')->default(true);
            $table->string('created_by_type');
            $table->bigInteger('created_by_id');
            $table->timestamps();

            $table->unique(['plate_no', 'company_id']);
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'name']);
        });
    }
};
