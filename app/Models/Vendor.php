<?php

declare(strict_types=1);

namespace App\Models;

use App\Domains\Common\Jobs\EmailVerificationJob;
use App\Domains\Vendor\Events\VendorCreateEvent;
use App\Domains\Vendor\Events\VendorUpdateEvent;
use App\Http\Traits\EmailVerifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Vendor extends Model
{
    use HasFactory;
    use EmailVerifiable;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id', 'name', 'code', 'sst_number', 'registration_number', 'email', 'is_email_verified', 'phone', 'mobile', 'fax', 'address_line_1', 'address_line_2', 'city', 'area_code', 'website', 'is_consignment', 'commission_percentage',
    ];

    protected static function boot()
    {
        parent::boot();

        static::updated(function ($vendor): void {
            if ($vendor->isDirty('email')) {
                $vendor->updateQuietly([
                    'is_email_verified' => false,
                ]);
                EmailVerificationJob::dispatch($vendor->fresh())->delay(now()->addSeconds(10))->onQueue('high');
            }

            event(new VendorUpdateEvent($vendor));
        });

        static::created(function ($vendor): void {
            EmailVerificationJob::dispatch($vendor)->delay(now()->addSeconds(10))->onQueue('high');
            event(new VendorCreateEvent($vendor));
        });
    }
}
