<?php

declare(strict_types=1);

namespace App\Models;

use App\Domains\MemberAddress\Events\MemberAddressCreateEvent;
use App\Domains\MemberAddress\Events\MemberAddressDeletedEvent;
use App\Domains\MemberAddress\Events\MemberAddressUpdateEvent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MemberAddress extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'member_id',
        'name',
        'first_name',
        'last_name',
        'contact_mobile_number',
        'contact_email',
        'address_line_1',
        'address_line_2',
        'city_name',
        'area_code',
        'is_primary',
        'country_id',
        'state_id',
        'city_id',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'is_primary' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::created(function ($memberAddress): void {
            event(new MemberAddressCreateEvent($memberAddress));
        });

        static::updated(function ($memberAddress): void {
            event(new MemberAddressUpdateEvent($memberAddress));
        });

        static::deleted(function ($memberAddress): void {
            event(new MemberAddressDeletedEvent($memberAddress));
        });
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
}
