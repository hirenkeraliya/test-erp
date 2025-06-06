<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class PosAdvertisement extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['company_id', 'type_id', 'name', 'status'];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'status' => 'boolean',
    ];

    public function locations(): BelongsToMany
    {
        return $this->belongsToMany(Location::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photo')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/gif', 'image/png']);

        $this->addMediaCollection('video')
            ->singleFile()
            ->acceptsMimeTypes(['video/mp4', 'video/avi', 'video/mpeg']);
    }
}
