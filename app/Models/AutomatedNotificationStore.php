<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutomatedNotificationStore extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['automated_notification_id', 'location_id', 'low_stock_alert_threshold'];

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}
