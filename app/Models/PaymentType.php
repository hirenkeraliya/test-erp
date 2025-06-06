<?php

declare(strict_types=1);

namespace App\Models;

use App\Domains\PaymentType\Enums\PaymentRestrictionTypes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentType extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'name',
        'image_name',
        'parent_payment_type_id',
        'is_member_required',
        'is_available_for_refund',
        'trigger_card_payment_machine',
        'trigger_qr_code_payment_machine',
        'trigger_card_affin_payment_machine',
        'is_card_payment',
        'status',
        'payment_terminal_key',
        'trigger_card_bank_rakyat_terminal',
        'is_available_in_ecommerce',
        'site_key',
        'secret_key',
        'url',
        'restrict_by_zone',
        'restriction_type',
        'is_available_in_pos',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'is_member_required' => 'boolean',
        'is_available_for_refund' => 'boolean',
        'trigger_card_payment_machine' => 'boolean',
        'trigger_qr_code_payment_machine' => 'boolean',
        'trigger_card_affin_payment_machine' => 'boolean',
        'status' => 'boolean',
        'is_card_payment' => 'boolean',
        'trigger_card_bank_rakyat_terminal' => 'boolean',
        'is_available_in_ecommerce' => 'boolean',
        'restriction_type' => PaymentRestrictionTypes::class,
        'is_available_in_pos' => 'boolean',
    ];

    public function saleChannels(): BelongsToMany
    {
        return $this->belongsToMany(SaleChannel::class);
    }

    public function shippingZones(): BelongsToMany
    {
        return $this->belongsToMany(ShippingZone::class);
    }

    public function activeSubPaymentTypes(): HasMany
    {
        return $this->hasMany(self::class, 'parent_payment_type_id')->onlyActive();
    }

    public function scopeOnlyActive(Builder $query): void
    {
        $query->where('status', true);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getStatus(): bool
    {
        return $this->status;
    }

    public function getIsMemberRequired(): bool
    {
        return $this->is_member_required;
    }

    public function salePayment(): HasMany
    {
        return $this->hasMany(SalePayment::class);
    }

    public function bookingPaymentPayment(): HasMany
    {
        return $this->hasMany(BookingPaymentPayment::class);
    }

    public function bookingPaymentRefund(): HasMany
    {
        return $this->hasMany(BookingPaymentRefund::class);
    }

    public function closeCounterPayment(): HasMany
    {
        return $this->hasMany(CloseCounterPayment::class);
    }

    public function creditNoteRefund(): HasMany
    {
        return $this->hasMany(CreditNoteRefund::class);
    }

    public function OrderPayment(): HasMany
    {
        return $this->hasMany(OrderPayment::class);
    }

    public function StoreDayClosePayment(): HasMany
    {
        return $this->hasMany(StoreDayClosePayment::class);
    }
}
