<?php

declare(strict_types=1);

namespace App\Models;

use App\Domains\State\Events\StateCreateEvent;
use App\Domains\State\Events\StateUpdateEvent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class State extends Model
{
    use HasFactory;

    public $timestamps = false;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['country_id', 'name', 'country_code'];

    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::updated(function ($state): void {
            event(new StateUpdateEvent($state));
        });

        static::created(function ($state): void {
            event(new StateCreateEvent($state));
        });
    }
}
