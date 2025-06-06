<?php

declare(strict_types=1);

namespace App\Models;

use App\Domains\DynamicMenus\Events\DynamicMenuCreateOrUpdateEvent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DynamicMenu extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['company_id', 'parent_id', 'title', 'slug', 'type', 'module_id', 'content', 'status'];

    protected $casts = [
        'content' => 'string',
        'status' => 'boolean',
    ];

    public function dynamicMenus(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->select(
            ['id', 'company_id', 'parent_id', 'title', 'slug', 'type', 'module_id', 'content']
        );
    }

    public function children(): HasMany
    {
        return $this->dynamicMenus()->with('children');
    }

    protected static function boot()
    {
        parent::boot();

        static::updated(function ($dynamicMenu): void {
            event(new DynamicMenuCreateOrUpdateEvent($dynamicMenu));
        });

        static::created(function ($dynamicMenu): void {
            event(new DynamicMenuCreateOrUpdateEvent($dynamicMenu));
        });
    }
}
