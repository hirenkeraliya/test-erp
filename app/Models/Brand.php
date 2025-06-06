<?php

declare(strict_types=1);

namespace App\Models;

use App\Domains\Brand\Events\BrandCreateEvent;
use App\Domains\Brand\Events\BrandUpdateEvent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Brand extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['name', 'code'];

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class);
    }

    public function brandChannelReferences(): HasMany
    {
        return $this->hasMany(BrandChannelReference::class);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    protected static function boot()
    {
        parent::boot();
        static::updated(function ($brand): void {
            event(new BrandUpdateEvent($brand));
        });

        static::created(function ($brand): void {
            event(new BrandCreateEvent($brand));
        });
    }
}
