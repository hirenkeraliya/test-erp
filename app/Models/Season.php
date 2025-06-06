<?php

declare(strict_types=1);

namespace App\Models;

use App\Domains\Season\Events\SeasonCreateEvent;
use App\Domains\Season\Events\SeasonUpdateEvent;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Season extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['name', 'code', 'company_id'];

    public function getName(): string
    {
        return $this->name;
    }

    protected static function boot()
    {
        parent::boot();
        static::updated(function ($season): void {
            event(new SeasonUpdateEvent($season));
        });

        static::created(function ($season): void {
            event(new SeasonCreateEvent($season));
        });
    }
}
