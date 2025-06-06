<?php

declare(strict_types=1);

namespace App\Models;

use App\Domains\City\Events\CityCreateEvent;
use App\Domains\City\Events\CityUpdateEvent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class City extends Model
{
    use HasFactory;

    public $timestamps = false;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['country_id', 'state_id', 'name', 'country_code'];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    protected static function boot()
    {
        parent::boot();
        static::updated(function ($city): void {
            event(new CityUpdateEvent($city));
        });

        static::created(function ($city): void {
            event(new CityCreateEvent($city));
        });
    }
}
