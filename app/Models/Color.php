<?php

declare(strict_types=1);

namespace App\Models;

use App\Domains\Color\Events\ColorCreateEvent;
use App\Domains\Color\Events\ColorUpdateEvent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Color extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['name', 'code', 'company_id', 'group_id', 'color_code'];

    public function getName(): string
    {
        return $this->name;
    }

    public function colorGroup(): BelongsTo
    {
        return $this->belongsTo(ColorGroup::class, 'group_id');
    }

    public function colorChannelReferences(): HasMany
    {
        return $this->hasMany(ColorChannelReference::class);
    }

    protected static function boot()
    {
        parent::boot();
        static::updated(function ($color): void {
            event(new ColorUpdateEvent($color));
        });

        static::created(function ($color): void {
            event(new ColorCreateEvent($color));
        });
    }
}
