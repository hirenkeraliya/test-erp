<?php

declare(strict_types=1);

namespace App\Models;

use App\Domains\Order\Enums\OrderChannels;
use App\Domains\Order\Enums\OrderStatus;
use App\Domains\Order\Enums\OrderTypes;
use App\Domains\Order\Jobs\OrderStatusUpdateJob;
use App\Domains\OrderAddress\Enums\OrderAddressesType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

class Order extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'store_manager_id',
        'location_id',
        'member_id',
        'receipt_number',
        'total_tax_amount',
        'cart_discount_amount',
        'item_discount_amount',
        'total_discount_amount',
        'layaway_pending_amount',
        'credit_pending_amount',
        'total_amount_before_round_off',
        'round_off',
        'total_amount_paid',
        'delivery_charges',
        'type_id',
        'channel_id',
        'sale_channel_id',
        'cancel_order_reason_id',
        'notes',
        'bill_reference_number',
        'order_return_id',
        'layaway_completed_at',
        'credit_completed_at',
        'store_day_close_id',
        'status',
        'pickup_location_id',
        'tracking_number',
        'tracking_url',
        'shipment_order_number',
        'courier_name',
        'digital_invoice_number',
        'digital_invoice_submitted',
        'happened_at',
    ];

    protected $casts = [
        'type_id' => OrderTypes::class,
        'channel_id' => OrderChannels::class,
        'status' => OrderStatus::class,
        'digital_invoice_submitted' => 'boolean',
    ];

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function orderPickingListItems(): HasMany
    {
        return $this->hasMany(OrderPickingListItem::class);
    }

    public function storeManager(): BelongsTo
    {
        return $this->belongsTo(StoreManager::class)
            ->select('id', 'employee_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class)
            ->select('id', 'name', 'code', 'company_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class)
            ->select('id', 'first_name', 'last_name');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(OrderPayment::class);
    }

    public function checkHasOrderReturn(): HasOne
    {
        return $this->hasOne(OrderReturn::class, 'original_order_id', 'id')
            ->select('id', 'original_order_id');
    }

    public function billingAddress(): HasOne
    {
        return $this->hasOne(OrderAddress::class)->where('type_id', OrderAddressesType::BILLING_ADDRESS->value);
    }

    public function shippingAddress(): HasOne
    {
        return $this->hasOne(OrderAddress::class)->where('type_id', OrderAddressesType::SHIPPING_ADDRESS->value);
    }

    // All The Get Methods

    public function getOrderItems(): Collection
    {
        return $this->orderItems;
    }

    public function getStoreManager(): ?StoreManager
    {
        return $this->storeManager;
    }

    public function getLocation(): ?Location
    {
        return $this->location;
    }

    public function getMember(): ?Member
    {
        return $this->member;
    }

    public function getCreatedAt(): ?Carbon
    {
        return $this->created_at;
    }

    public function getGrossTotal(): float
    {
        return (float) ($this->total_amount_before_round_off + $this->total_discount_amount - $this->total_tax_amount + $this->layaway_pending_amount + $this->credit_pending_amount);
    }

    public function getTotalDiscountAmount(): float
    {
        return (float) $this->total_discount_amount;
    }

    public function getDeliveryCharges(): float
    {
        return (float) $this->delivery_charges;
    }

    public function getLayawayPendingAmount(): float
    {
        return (float) $this->layaway_pending_amount;
    }

    public function getLayawayCompletedAt(): ?string
    {
        return $this->layaway_completed_at;
    }

    public function getTotalTaxAmount(): float
    {
        return (float) $this->total_tax_amount;
    }

    public function getTotalAmountPaid(): float
    {
        return (float) $this->total_amount_paid;
    }

    public function getTotalAmountPaidForLayawayAndCreditOrder(): float
    {
        return (float) $this->total_amount_paid + $this->credit_pending_amount + $this->layaway_pending_amount;
    }

    public function getRoundOff(): float
    {
        return (float) $this->round_off;
    }

    public function getTotalAmountBeforeRoundOff(): float
    {
        return (float) $this->total_amount_before_round_off;
    }

    public function netAmount(): float
    {
        return (float) $this->total_amount_before_round_off + $this->credit_pending_amount + $this->layaway_pending_amount;
    }

    public function getPayments(): Collection
    {
        return $this->payments;
    }

    public function getTypeId(): OrderTypes
    {
        return $this->type_id;
    }

    public function getStatus(): ?OrderStatus
    {
        return $this->status;
    }

    public function getMemberId(): ?int
    {
        return $this->member_id;
    }

    public function getReceiptNumber(): string
    {
        return $this->receipt_number;
    }

    public function getCreditPendingAmount(): float
    {
        return (float) $this->credit_pending_amount;
    }

    public function getCreditCompletedAt(): ?string
    {
        return $this->credit_completed_at;
    }

    public function getLocationId(): int
    {
        return $this->location_id;
    }

    public function getBillReferenceNumber(): ?string
    {
        return $this->bill_reference_number;
    }

    public function getCheckHasOrderReturn(): ?OrderReturn
    {
        return $this->checkHasOrderReturn;
    }

    public function getPickupLocationId(): ?int
    {
        return $this->pickup_location_id;
    }

    public function getTrackingNumber(): ?string
    {
        return $this->tracking_number;
    }

    public function getCourierName(): ?string
    {
        return $this->courier_name;
    }

    public function getHappenedAt(): ?string
    {
        return $this->happened_at;
    }

    public function getOrderChannelReference(): ?OrderChannelReference
    {
        return $this->orderChannelReference;
    }

    public function orderChannelReference(): HasOne
    {
        return $this->hasOne(OrderChannelReference::class);
    }

    public function saleChannel(): BelongsTo
    {
        return $this->belongsTo(SaleChannel::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::updated(function ($order): void {
            if ($order->isDirty('status')) {
                OrderStatusUpdateJob::dispatch($order->id)->onQueue(config('horizon.default_queue_name'));
            }
        });
    }
}
