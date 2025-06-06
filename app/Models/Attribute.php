<?php

declare(strict_types=1);

namespace App\Models;

use App\Domains\Attribute\Enums\FieldType;
use App\Domains\Attribute\Events\AttributeCreateEvent;
use App\Domains\Attribute\Events\AttributeDeleteEvent;
use App\Domains\Attribute\Events\AttributeUpdateEvent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attribute extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'field_type',
        'default_value',
        'from',
        'to',
        'options',
        'is_required',
        'company_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'field_type' => FieldType::class,
        'options' => 'array',
        'is_required' => 'boolean',
        'status' => 'boolean',
    ];

    public function customFieldValue(): HasOne
    {
        return $this->hasOne(CustomFieldValue::class);
    }

    public function templates(): BelongsToMany
    {
        return $this->belongsToMany(Template::class);
    }

    public function attributeChannelReferences(): HasMany
    {
        return $this->hasMany(AttributeChannelReference::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::updated(function ($attribute): void {
            event(new AttributeUpdateEvent($attribute));
        });

        static::created(function ($attribute): void {
            event(new AttributeCreateEvent($attribute));
        });

        static::deleted(function ($attribute): void {
            event(new AttributeDeleteEvent($attribute));
        });
    }
}
