<?php

declare(strict_types=1);

namespace App\Models;

use App\Domains\AggregateProcessTracker\Enums\AggregateProcessTrackerModules;
use App\Domains\AggregateProcessTracker\Enums\AggregateProcessTrackerStatuses;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AggregateProcessTracker extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['company_id', 'job_type', 'status', 'last_refreshed_at'];

    protected $casts = [
        'job_type' => AggregateProcessTrackerModules::class,
        'status' => AggregateProcessTrackerStatuses::class,
    ];
}
