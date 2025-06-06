<?php

declare(strict_types=1);

namespace App\Models;

use App\Domains\LoyaltyPointUpdate\Events\LoyaltyPointUpdatesCreateEvent;
use App\Domains\LoyaltyPointUpdate\Events\LoyaltyPointUpdatesUpdateEvent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class LoyaltyPointUpdate extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'member_id', 'loyalty_point_id', 'affected_by_id', 'affected_by_type', 'type_id', 'points', 'closing_loyalty_points_balance', 'happened_at', 'remarks',
    ];

    // Sale, Sale Return, Void Sale,
    public function affectedBy(): MorphTo
    {
        return $this->morphTo();
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function loyaltyPoint(): BelongsTo
    {
        return $this->belongsTo(LoyaltyPoint::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::updated(function ($loyaltyPointUpdate): void {
            event(new LoyaltyPointUpdatesCreateEvent($loyaltyPointUpdate));
        });

        static::created(function ($loyaltyPointUpdate): void {
            event(new LoyaltyPointUpdatesUpdateEvent($loyaltyPointUpdate));
        });
    }
}
