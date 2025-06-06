<?php

declare(strict_types=1);

namespace App\Models;

use App\Domains\Size\Events\SizeCreateEvent;
use App\Domains\Size\Events\SizeUpdateEvent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Size extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['company_id', 'group_id', 'name', 'code', 'sort_order'];

    public function getName(): string
    {
        return $this->name;
    }

    public function sizeGroup(): BelongsTo
    {
        return $this->belongsTo(SizeGroup::class, 'group_id');
    }

    public function sizeChannelReferences(): HasMany
    {
        return $this->hasMany(SizeChannelReference::class);
    }

    public function sortingSize(): BelongsTo
    {
        return $this->belongsTo(self::class, 'sort_order');
    }

    protected static function boot()
    {
        parent::boot();
        static::updated(function ($size): void {
            event(new SizeUpdateEvent($size));
        });

        static::created(function ($size): void {
            event(new SizeCreateEvent($size));
        });
    }
}
