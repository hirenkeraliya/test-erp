<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Counter extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'location_id',
        'counter_update_id',
        'name',
        'is_locked',
        'is_self_checkout',
        'app_version',
        'app_version_updated_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'is_locked' => 'boolean',
        'is_self_checkout' => 'boolean',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function counterUpdate(): BelongsTo
    {
        return $this->belongsTo(CounterUpdate::class);
    }

    public function getIsLocked(): bool
    {
        return $this->is_locked;
    }

    public function getLocationId(): int
    {
        return $this->location_id;
    }

    public function getLocation(): ?Location
    {
        return $this->location;
    }

    public function getCounterUpdateId(): ?int
    {
        return $this->counter_update_id;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
