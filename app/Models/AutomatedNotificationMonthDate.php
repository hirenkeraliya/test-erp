<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class AutomatedNotificationMonthDate extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['automated_notification_id', 'month_date'];
}
