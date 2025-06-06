<?php

declare(strict_types=1);

namespace App\Models;

use App\Domains\Common\Interfaces\SaleUsersInterface;
use App\Domains\Common\Jobs\EmailVerificationJob;
use App\Domains\LoyaltyPointUpdate\Enums\LoyaltyPointUpdateTypes;
use App\Domains\Member\Events\MemberCreateEvent;
use App\Domains\Member\Events\MemberRegisteredEvent;
use App\Domains\Member\Events\MemberUpdateEvent;
use App\Domains\MemberGroup\Jobs\CreateUpdateMemberSyncWithMemberGroupJob;
use App\Http\Traits\DiskBasedFirstMediaUrl;
use App\Http\Traits\EmailVerifiable;
use Carbon\Carbon;
use datetime;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Member extends Authenticatable implements HasMedia, SaleUsersInterface
{
    use InteractsWithMedia;
    use HasFactory;
    use HasApiTokens;
    use CaseSensitiveConditionals;
    use LogsActivity;
    use DiskBasedFirstMediaUrl;
    use EmailVerifiable;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'type_id',
        'title_id',
        'race_id',
        'channel_id',
        'first_name',
        'last_name',
        'gender_id',
        'date_of_birth',
        'mobile_number',
        'email',
        'company_name',
        'company_registration_number',
        'company_tax_number',
        'company_address',
        'company_phone',
        'created_by_id',
        'created_by_type',
        'created_location_id',
        'last_purchase_date',
        'notes',
        'spent_till_now',
        'loyalty_points',
        'membership_id',
        'card_number',
        'birthday_voucher_last_generated_at',
        'last_birthday_voucher_id',
        'welcome_member_voucher_generated_at',
        'welcome_member_voucher_id',
        'otp',
        'otp_expire_date',
        'total_redeemed_points',
        'total_earned_points',
        'total_expired_points',
        'total_sales',
        'status',
        'employee_id',
        'fcm_token',
        'pic_name',
        'pic_contact',
        'preferred_product_id',
        'preferred_color_id',
        'preferred_size_id',
        'preferred_category_id',
        'preferred_date',
        'preferred_day',
        'total_sale_qty',
        'first_purchase_date',
        'total_return_orders',
        'total_return_amount',
        'is_email_verified',
        'is_azentio_member',
    ];

    /**
     * @var array<string, class-string<datetime>>|array<string, string>
     */
    protected $casts = [
        'last_purchase_date' => 'datetime',
        'otp_expire_date' => 'datetime',
        'is_azentio_member' => 'boolean',
    ];

    public const DEFAULT_E_COMMERCE_PASSWORD = 'admin123';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'company_id',
                'type_id',
                'title_id',
                'race_id',
                'channel_id',
                'first_name',
                'last_name',
                'gender_id',
                'date_of_birth',
                'mobile_number',
                'email',
                'company_name',
                'company_registration_number',
                'company_tax_number',
                'company_phone',
                'created_by_id',
                'created_by_type',
                'created_location_id',
                'last_purchase_date',
                'notes',
                'spent_till_now',
                'loyalty_points',
                'membership_id',
                'card_number',
                'birthday_voucher_last_generated_at',
                'last_birthday_voucher_id',
                'welcome_member_voucher_generated_at',
                'welcome_member_voucher_id',
                'otp',
                'employee_id',
                'otp_expire_date',
                'is_email_verified',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photo')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/gif', 'image/png', 'image/webp']);
    }

    public function createdInLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'created_location_id', 'id');
    }

    public function membership(): BelongsTo
    {
        return $this->belongsTo(Membership::class);
    }

    public function memberGroupMembers(): HasMany
    {
        return $this->hasMany(MemberGroupMember::class);
    }

    public function vouchers(): HasMany
    {
        return $this->hasMany(Voucher::class);
    }

    public function birthdayVoucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class, 'last_birthday_voucher_id');
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function memberChannelReferences(): HasMany
    {
        return $this->hasMany(MemberChannelReference::class);
    }

    public function getFullName(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function getMobileNumber(): string
    {
        return $this->mobile_number;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getLastPurchaseDate(): ?Carbon
    {
        return $this->last_purchase_date;
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function latestFiveLoyaltyPointUpdates(): HasMany
    {
        return $this->hasMany(LoyaltyPointUpdate::class)->orderby('id', 'desc')->limit(5);
    }

    public function lastManualUpdateLoyaltyPoint(): BelongsTo
    {
        return $this->belongsTo(LoyaltyPointUpdate::class, 'id', 'member_id')
            ->where('type_id', LoyaltyPointUpdateTypes::MANUAL_UPDATE->value)
            ->orderby('happened_at', 'desc');
    }

    public function firstName(): ?Attribute
    {
        return Attribute::make(set: fn (?string $value) => Str::title((string) $value));
    }

    public function lastName(): ?Attribute
    {
        return Attribute::make(set: fn (?string $value) => Str::title((string) $value));
    }

    public function memberAddresses(): HasMany
    {
        return $this->hasMany(MemberAddress::class);
    }

    public function primaryMemberAddress(): HasOne
    {
        return $this->hasOne(MemberAddress::class)
            ->where('is_primary', true);
    }

    protected static function boot()
    {
        parent::boot();

        // Event listener for the "created" event
        static::created(function ($member): void {
            event(new MemberCreateEvent($member));
            event(new MemberRegisteredEvent($member));
            // Dispatch the event with the member object
            CreateUpdateMemberSyncWithMemberGroupJob::dispatch($member->id, $member->company_id);
            if (! $member->employee_id) {
                EmailVerificationJob::dispatch($member)->delay(now()->addSeconds(10))->onQueue('high');
            }
        });

        static::updated(function ($member): void {
            CreateUpdateMemberSyncWithMemberGroupJob::dispatch($member->id, $member->company_id);
            event(new MemberUpdateEvent($member));

            if ($member->isDirty('email')) {
                $member->updateQuietly([
                    'is_email_verified' => false,
                ]);
                EmailVerificationJob::dispatch($member->fresh())->delay(now()->addSeconds(10))->onQueue('high');
            }
        });
    }

    public function revokeCurrentToken(int $tokenId): void
    {
        $this->tokens()->where('id', $tokenId)->delete();
    }
}
