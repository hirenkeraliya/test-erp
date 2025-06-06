<?php

declare(strict_types=1);

namespace App\Models;

use App\Domains\Membership\Events\MembershipCreateEvent;
use App\Domains\Membership\Events\MembershipUpdateEvent;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Membership extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'name',
        'lifetime_value',
        'loyalty_points_per_currency_unit',
        'min_loyalty_points_for_redemption',
        'max_loyalty_points_for_redemption',
        'created_by_id',
        'created_by_type',
    ];

    protected static function boot()
    {
        parent::boot();

        static::updated(function ($membership): void {
            event(new MembershipUpdateEvent($membership));
        });

        static::created(function ($membership): void {
            event(new MembershipCreateEvent($membership));
        });
    }
}
