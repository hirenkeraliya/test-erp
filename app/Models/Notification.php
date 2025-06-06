<?php

declare(strict_types=1);

namespace App\Models;

use App\Domains\Notification\Events\NotificationFirebaseEvent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Notification extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'from_user_id',
        'from_user_type',
        'to_user_id',
        'to_user_type',
        'title',
        'message',
        'mark_as_read_at',
        'mark_as_read_by_id',
        'mark_as_read_by_type',
        'text_message',
        'payload',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'payload' => 'json',
    ];

    // Can be StoreManager, Admin, WarehouseManager
    public function fromUser(): MorphTo
    {
        return $this->morphTo();
    }

    // Can be StoreManager, Admin, WarehouseManager, Member
    public function toUser(): MorphTo
    {
        return $this->morphTo();
    }

    protected static function boot()
    {
        parent::boot();

        // Event listener for the "created" event
        static::created(function ($notification): void {
            event(new NotificationFirebaseEvent($notification)); // Dispatch the event with the member object
        });
    }
}
