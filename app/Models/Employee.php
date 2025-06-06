<?php

declare(strict_types=1);

namespace App\Models;

use App\Domains\Common\Interfaces\SaleUsersInterface;
use App\Domains\Common\Jobs\EmailVerificationJob;
use App\Http\Traits\EmailVerifiable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Employee extends Model implements HasMedia, SaleUsersInterface
{
    use InteractsWithMedia;
    use HasFactory;
    use EmailVerifiable;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id', 'designation_id', 'group_id', 'first_name', 'last_name', 'email', 'is_email_verified', 'mobile_number', 'home_contact', 'address_line_1', 'address_line_2', 'city', 'area_code', 'date_of_joining', 'primary_contact_name', 'primary_contact_phone', 'staff_id', 'ic_number', 'job_type', 'status', 'spent_till_now', 'membership_id', 'loyalty_points', 'card_number', 'created_by_id', 'created_by_type', 'total_redeemed_points', 'total_earned_points', 'total_expired_points', 'total_sales',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'status' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function membership(): BelongsTo
    {
        return $this->belongsTo(Membership::class);
    }

    public function getFullName(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function getStatus(): bool
    {
        return $this->status;
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photo')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/gif', 'image/png']);
    }

    public function getCompanyId(): int
    {
        return $this->company_id;
    }

    public function getMobileNumber(): string
    {
        return $this->mobile_number;
    }

    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class);
    }

    public function employeeGroup(): BelongsTo
    {
        return $this->belongsTo(EmployeeGroup::class, 'group_id');
    }

    public function member(): HasOne
    {
        return $this->hasOne(Member::class);
    }

    public function scopeOnlyActive(Builder $query): void
    {
        $query->where('status', true);
    }

    protected static function boot()
    {
        parent::boot();

        static::updated(function ($employee): void {
            if ($employee->isDirty('email')) {
                $employee->updateQuietly([
                    'is_email_verified' => false,
                ]);
                EmailVerificationJob::dispatch($employee->fresh())->delay(now()->addSeconds(10))->onQueue('high');
            }
        });

        static::created(function ($employee): void {
            EmailVerificationJob::dispatch($employee)->delay(now()->addSeconds(10))->onQueue('high');
        });
    }
}
