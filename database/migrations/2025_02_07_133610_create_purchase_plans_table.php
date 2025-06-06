<?php

declare(strict_types=1);

use App\Domains\PurchasePlan\Enums\Statuses;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('purchase_plans', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('vendor_id')->constrained();
            $table->foreignId('location_id')->constrained();
            $table->decimal('total_amount', 10, 2);
            $table->string('plan_number');
            $table->string('reference_number')->nullable();
            $table->string('remarks')->nullable();
            $table->tinyInteger('status')->default(Statuses::PENDING->value);
            $table->timestamps();
        });
    }
};
