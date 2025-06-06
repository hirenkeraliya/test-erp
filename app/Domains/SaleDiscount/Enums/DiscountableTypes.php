<?php

declare(strict_types=1);

namespace App\Domains\SaleDiscount\Enums;

use App\Domains\Common\Enums\ModelMapping;
use App\Http\Traits\PrepareEnumDataMethods;
use App\Models\Cashback;
use App\Models\Promotion;
use App\Models\SaleDiscount;
use App\Models\Voucher;

enum DiscountableTypes: string
{
    use PrepareEnumDataMethods;

    case PROMOTION = 'Promotion';
    case CASHBACK = 'Cashback';
    case VOUCHER = 'Voucher';
    case SALE_PRICE_OVERRIDE = 'Sale Price Override';

    public static function getDiscountableClass(string $discountableType): string
    {
        if ($discountableType === self::PROMOTION->value) {
            return Promotion::class;
        }

        if ($discountableType === self::CASHBACK->value) {
            return Cashback::class;
        }

        if ($discountableType === self::SALE_PRICE_OVERRIDE->value) {
            return SaleDiscount::class;
        }

        return Voucher::class;
    }

    public static function getDiscountableType(string $discountableClass): string
    {
        if (ModelMapping::PROMOTION->name === $discountableClass) {
            return self::PROMOTION->value;
        }

        if (ModelMapping::CASHBACK->name === $discountableClass) {
            return self::CASHBACK->value;
        }

        if (ModelMapping::SALE_PRICE_OVERRIDE->name === $discountableClass) {
            return self::SALE_PRICE_OVERRIDE->value;
        }

        return self::VOUCHER->value;
    }
}
