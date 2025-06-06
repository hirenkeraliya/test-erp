<?php

declare(strict_types=1);

namespace App\Models;

use App\Domains\Style\Events\StyleCreateEvent;
use App\Domains\Style\Events\StyleUpdateEvent;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Style extends Model
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

        static::updated(function ($style): void {
            event(new StyleUpdateEvent($style));
        });

        static::created(function ($style): void {
            event(new StyleCreateEvent($style));
        });
    }
}
