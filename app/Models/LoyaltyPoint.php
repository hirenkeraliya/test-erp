<?php

declare(strict_types=1);

namespace App\Models;

use App\Domains\LoyaltyPoint\Events\LoyaltyPointCreateEvent;
use App\Domains\LoyaltyPoint\Events\LoyaltyPointUpdateEvent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoyaltyPoint extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'member_id',
        'sale_id',
        'order_id',
        'loyalty_campaign_id',
        'expiry_date',
        'points',
        'available_points',
        'minimum_spend_amount',
    ];

    public function loyaltyCampaign(): BelongsTo
    {
        return $this->belongsTo(LoyaltyCampaign::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::updated(function ($loyaltyPoint): void {
            event(new LoyaltyPointCreateEvent($loyaltyPoint));
        });

        static::created(function ($loyaltyPoint): void {
            event(new LoyaltyPointUpdateEvent($loyaltyPoint));
        });
    }
}
