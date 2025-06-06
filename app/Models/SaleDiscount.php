<?php

declare(strict_types=1);

namespace App\Models;

use App\Domains\Sale\SaleQueries;
use App\Domains\SaleDiscount\Enums\DiscountableTypes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Collection;

class SaleDiscount extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['sale_id', 'discountable_id', 'discountable_type', 'amount', 'promo_code'];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    // Can be Promotion, Voucher, Cashback
    public function discountable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getAmount(): float
    {
        return (float) $this->amount;
    }

    public function getDiscountableType(): string
    {
        return $this->discountable_type;
    }

    /**
     * @return mixed[]
     */
    public static function getPreparedSaleDiscounts(?Collection $saleDiscounts): array
    {
        if (! $saleDiscounts instanceof Collection) {
            return [];
        }

        return $saleDiscounts->map(fn ($saleDiscount): array => [
            'id' => $saleDiscount->getKey(),
            'discount_type' => DiscountableTypes::getDiscountableType($saleDiscount->getDiscountableType()),
            'amount' => $saleDiscount->getAmount(),
        ])->toArray();
    }

    public function loadRelationAndGetReferenceNumber(): ?string
    {
        $saleQueries = resolve(SaleQueries::class);
        $this->refresh();
        $saleDiscount = $this->load('sale:' . $saleQueries->getOfflineSaleId());

        /** @var Sale $sale */
        $sale = $saleDiscount->sale;

        return $sale->offline_sale_id;
    }
}
