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
        Schema::create('dynamic_menus', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->foreignId('parent_id')->nullable()->constrained('dynamic_menus', 'id');
            $table->string('title', 255);
            $table->string('slug', 255);
            $table->integer('type')->comment('1:Brand, 2: Category, 3: Product Collection, 4: Static Page');
            $table->integer('module_id')->nullable();
            $table->longText('content')->nullable();
            $table->timestamps();
        });
    }
};
