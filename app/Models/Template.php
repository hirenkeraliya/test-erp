<?php

declare(strict_types=1);

namespace App\Models;

use App\Domains\Template\Events\TemplateCreateEvent;
use App\Domains\Template\Events\TemplateUpdateEvent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Template extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['company_id', 'name', 'description', 'is_variant'];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'is_variant' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function attributes(): BelongsToMany
    {
        return $this->belongsToMany(Attribute::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::updated(function ($template): void {
            event(new TemplateUpdateEvent($template));
        });

        static::created(function ($template): void {
            event(new TemplateCreateEvent($template));
        });
    }
}
