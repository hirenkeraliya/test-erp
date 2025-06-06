<?php

declare(strict_types=1);

namespace App\Models;

use App\Domains\Common\Jobs\EmailVerificationJob;
use App\Domains\Region\Events\RegionCreateEvent;
use App\Domains\Region\Events\RegionUpdateEvent;
use App\Http\Traits\EmailVerifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Region extends Model
{
    use HasFactory;
    use EmailVerifiable;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['name', 'code', 'company_id', 'manager_name', 'manager_email', 'is_email_verified'];

    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }

    public function getEmailAttribute(): ?string
    {
        return $this->manager_email;
    }

    protected static function boot()
    {
        parent::boot();

        static::updated(function ($region): void {
            event(new RegionUpdateEvent($region));

            if ($region->isDirty('manager_email')) {
                $region->updateQuietly([
                    'is_email_verified' => false,
                ]);
                EmailVerificationJob::dispatch($region->fresh())->delay(now()->addSeconds(10))->onQueue('high');
            }
        });

        static::created(function ($region): void {
            event(new RegionCreateEvent($region));

            if ($region->manager_email) {
                EmailVerificationJob::dispatch($region)->delay(now()->addSeconds(10))->onQueue('high');
            }
        });
    }
}
