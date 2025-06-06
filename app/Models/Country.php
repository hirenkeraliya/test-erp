<?php

declare(strict_types=1);

namespace App\Models;

use App\Domains\Country\Events\CountryCreateEvent;
use App\Domains\Country\Events\CountryUpdateEvent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Country extends Model
{
    use HasFactory;

    public $timestamps = false;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['iso2', 'name', 'status', 'phone_code', 'iso3', 'region', 'subregion'];

    public function states(): HasMany
    {
        return $this->hasMany(State::class);
    }

    public function currency(): HasOne
    {
        return $this->hasOne(Currency::class);
    }

    public function company(): HasOne
    {
        return $this->hasOne(Company::class, 'default_country_id');
    }

    public function getName(): string
    {
        return $this->name;
    }

    protected static function boot()
    {
        parent::boot();

        static::updated(function ($country): void {
            event(new CountryUpdateEvent($country));
        });

        static::created(function ($country): void {
            event(new CountryCreateEvent($country));
        });
    }
}
