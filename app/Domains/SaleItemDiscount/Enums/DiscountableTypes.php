<?php

declare(strict_types=1);

namespace App\Domains\SaleItemDiscount\Enums;

use App\Http\Traits\PrepareEnumDataMethods;
use App\Models\ComplimentaryItemReason;
use App\Models\DreamPrice;
use App\Models\HappyHourDiscount;
use App\Models\Promotion;
use App\Models\SaleItemPriceOverride;

enum DiscountableTypes: string
{
    use PrepareEnumDataMethods;

    case PROMOTION = 'Promotion';
    case DREAM_PRICE = 'Dream Price';
    case COMPLIMENTARY_ITEM_REASON = 'Complimentary Item Reason';
    case SALE_ITEM_PRICE_OVERRIDE = 'Sale Item Price Override';
    case HAPPY_HOUR_DISCOUNT = 'Happy Hour Discount';

    public static function getDiscountableClass(string $discountableType): string
    {
        if ($discountableType === self::PROMOTION->value) {
            return Promotion::class;
        }

        if ($discountableType === self::DREAM_PRICE->value) {
            return DreamPrice::class;
        }

        if ($discountableType === self::COMPLIMENTARY_ITEM_REASON->value) {
            return ComplimentaryItemReason::class;
        }

        if ($discountableType === self::HAPPY_HOUR_DISCOUNT->value) {
            return HappyHourDiscount::class;
        }

        return SaleItemPriceOverride::class;
    }
}
