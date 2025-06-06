<?php

declare(strict_types=1);

namespace App\Models;

use App\Domains\Banner\Events\BannerCreateEvent;
use App\Domains\Banner\Events\BannerUpdateEvent;
use App\Domains\Banner\Jobs\BannerUpdateJob;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Banner extends Model implements HasMedia
{
    use InteractsWithMedia;
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['name', 'description', 'company_id', 'action_type_id', 'custom_url', 'status'];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'status' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function getStatus(): bool
    {
        return $this->status;
    }

    public function getCompanyId(): int
    {
        return $this->company_id;
    }

    public function bannerChannelReferences(): HasMany
    {
        return $this->hasMany(BannerChannelReference::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('banner')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png']);
    }

    protected static function boot()
    {
        parent::boot();

        static::updated(function ($banner): void {
            if ($banner->isDirty('status')) {
                BannerUpdateJob::dispatch($banner, $banner->status)->onQueue(config('horizon.default_queue_name'));
            }

            event(new BannerUpdateEvent($banner));
        });

        static::created(function ($banner): void {
            event(new BannerCreateEvent($banner));
        });
    }
}
