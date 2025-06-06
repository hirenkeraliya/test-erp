<?php

declare(strict_types=1);

namespace App\Domains\OrderDiscount\Enums;

use App\Http\Traits\PrepareEnumDataMethods;
use App\Models\Voucher;

enum DiscountableTypes: string
{
    use PrepareEnumDataMethods;

    case VOUCHER = 'Voucher';

    public static function getDiscountableClass(string $discountableType): string
    {
        return Voucher::class;
    }

    public static function getDiscountableType(string $discountableClass): string
    {
        return self::VOUCHER->value;
    }
}
