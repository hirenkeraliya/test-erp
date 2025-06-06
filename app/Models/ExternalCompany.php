<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ExternalCompany extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;
    use SoftDeletes;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'external_connection_id',
        'external_company_id',
        'name',
        'code',
        'email',
        'fax',
        'address',
        'social_security_number',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('light_logo')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/gif', 'image/png']);

        $this->addMediaCollection('dark_logo')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/gif', 'image/png']);

        $this->addMediaCollection('email_footer_logo')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/gif', 'image/png']);
    }

    public function externalConnection(): BelongsTo
    {
        return $this->belongsTo(ExternalConnection::class);
    }

    public function externalLocations(): HasMany
    {
        return $this->hasMany(ExternalLocation::class);
    }

    public function getExternalLocations(): Collection
    {
        return $this->externalLocations;
    }

    public function getNameWithCode(): string
    {
        return $this->name . ' (' . $this->code . ')';
    }
}
