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
        Schema::create('aggregate_process_trackers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->tinyInteger('job_type')->comment('Refer to AggregateProcessTrackerModules.php');
            $table->tinyInteger('status')->comment('Refer to AggregateProcessTrackerStatuses.php');
            $table->dateTime('last_refreshed_at')->nullable();
            $table->timestamps();
        });
    }
};
