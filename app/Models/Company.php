<?php

declare(strict_types=1);

namespace App\Models;

use App\Domains\Common\Jobs\EmailVerificationJob;
use App\Domains\Company\Enums\CommissionTypes;
use App\Domains\Company\Events\CompanyCreateEvent;
use App\Domains\Company\Events\CompanyUpdateEvent;
use App\Http\Traits\EmailVerifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Company extends Model implements HasMedia
{
    use SoftDeletes;
    use InteractsWithMedia;
    use HasFactory;
    use EmailVerifiable;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'name',
        'code',
        'grn_format',
        'legal_name',
        'website',
        'email',
        'fax',
        'address',
        'employer_identification_number',
        'social_security_number',
        'void_sale_number_prefix',
        'send_sale_email_to_member',
        'new_member_free_loyalty_points',
        'commission_type_id',
        'min_promoters_per_item',
        'is_bill_reference_number_mandatory',
        'allow_exchange_to_different_store',
        'allow_price_override_cart_level',
        'allow_negative_inventory',
        'is_employee_booking_payment_allowed',
        'allow_only_return',
        'allow_credit_sale',
        'allow_employee_credit_sale',
        'yearly_target',
        'discount_applicable_type',
        'booking_payment_use_type',
        'booking_payment_refund_type',
        'auto_birthday_voucher_generation',
        'enable_ioi_city_mall_integration',
        'default_location_id',
        'location_assignment_type',
        'enable_trx_mall_integration',
        'allow_happy_hour_discount',
        'auto_include_in_collections',
        'creator_can_approve_draft_product',
        'order_picking_list_prefix',
        'default_country_id',
        'enable_e_invoice',
        'show_e_invoice_qr_on_receipt',
        'loyalty_point_expiration_days',
        'number_of_receipts',
        'currency_rate_auto_update',
        'auto_include_in_member_group',
        'is_email_verified',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'send_sale_email_to_member' => 'boolean',
        'is_bill_reference_number_mandatory' => 'boolean',
        'commission_type_id' => CommissionTypes::class,
        'allow_price_override_cart_level' => 'boolean',
        'allow_negative_inventory' => 'boolean',
        'is_employee_booking_payment_allowed' => 'boolean',
        'allow_only_return' => 'boolean',
        'allow_credit_sale' => 'boolean',
        'allow_employee_credit_sale' => 'boolean',
        'enable_ioi_city_mall_integration' => 'boolean',
        'enable_trx_mall_integration' => 'boolean',
        'allow_happy_hour_discount' => 'boolean',
        'auto_birthday_voucher_generation' => 'boolean',
        'allow_exchange_to_different_store' => 'boolean',
        'auto_include_in_collections' => 'boolean',
        'auto_include_in_member_group' => 'boolean',
        'creator_can_approve_draft_product' => 'boolean',
        'enable_e_invoice' => 'boolean',
        'show_e_invoice_qr_on_receipt' => 'boolean',
        'currency_rate_auto_update' => 'boolean',
    ];

    public function brands(): BelongsToMany
    {
        return $this->belongsToMany(Brand::class);
    }

    public function companySetting(): HasOne
    {
        return $this->hasOne(CompanySetting::class);
    }

    public function locations(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'default_location_id');
    }

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

    public function getVoidSaleNumberPrefix(): string
    {
        return $this->void_sale_number_prefix;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getNameWithCode(): string
    {
        return $this->name . '(' . $this->code . ')';
    }

    public function getSocialSecurityNumber(): ?string
    {
        return $this->social_security_number;
    }

    public function countries(): BelongsToMany
    {
        return $this->belongsToMany(Country::class);
    }

    public function defaultCountry(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'default_country_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::updated(function ($company): void {
            if ($company->isDirty('email')) {
                $company->updateQuietly([
                    'is_email_verified' => false,
                ]);
                EmailVerificationJob::dispatch($company->fresh())->delay(now()->addSeconds(10))->onQueue('high');
            }

            event(new CompanyUpdateEvent($company));
        });

        static::created(function ($company): void {
            EmailVerificationJob::dispatch($company)->delay(now()->addSeconds(10))->onQueue('high');
            event(new CompanyCreateEvent($company));
        });
    }
}
