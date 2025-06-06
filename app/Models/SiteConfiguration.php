<?php

declare(strict_types=1);

namespace App\Models;

use App\Domains\SiteConfiguration\Enums\SiteConfigurationTypes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class SiteConfiguration extends Model implements HasMedia
{
    use InteractsWithMedia;
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['type_id', 'value'];

    protected $casts = [
        'type_id' => SiteConfigurationTypes::class,
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('navbar_logo')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/gif', 'image/png']);

        $this->addMediaCollection('favicon_icon')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/gif', 'image/png']);

        $this->addMediaCollection('login_page_logo')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/gif', 'image/png']);

        $this->addMediaCollection('ecommerce_company_logo')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/gif', 'image/png']);

        $this->addMediaCollection('ecommerce_favicon')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/gif', 'image/png']);
    }
}
