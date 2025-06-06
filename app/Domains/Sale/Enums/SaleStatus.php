<?php

declare(strict_types=1);

namespace App\Domains\Sale\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum SaleStatus: int
{
    use PrepareEnumDataMethods;

    case REGULAR_SALE = 1;
    case VOID_SALE = 2;
    case PENDING_LAYAWAY_SALE = 3;
    case COMPLETE_LAYAWAY_SALE = 4;
    case CANCEL_LAYAWAY_SALE = 5;
    case PENDING_CREDIT_SALE = 6;
    case COMPLETE_CREDIT_SALE = 7;
    case CANCEL_CREDIT_SALE = 8;
    // NOTE: Whenever a new status is added, it's essential to go through all connected parts of the system, like queries and global scopes in models using sale statuses. This includes reviewing and adjusting these areas to make sure they work well with the new status. This way, the new status seamlessly fits into the system without any issues.

    public static function getCommonActiveSaleStatusValues(): array
    {
        return [
            self::REGULAR_SALE->value,
            self::PENDING_LAYAWAY_SALE->value,
            self::COMPLETE_LAYAWAY_SALE->value,
            self::PENDING_CREDIT_SALE->value,
            self::COMPLETE_CREDIT_SALE->value,
        ];
    }

    public static function getRegularPendingCancelAndCompleteActiveSaleStatusValues(): array
    {
        return [
            self::REGULAR_SALE->value,
            self::PENDING_LAYAWAY_SALE->value,
            self::COMPLETE_LAYAWAY_SALE->value,
            self::CANCEL_LAYAWAY_SALE->value,
            self::PENDING_CREDIT_SALE->value,
            self::COMPLETE_CREDIT_SALE->value,
        ];
    }

    public static function getOnlyRegularAndCompleteLayawaySaleStatusValues(): array
    {
        return [self::REGULAR_SALE->value, self::COMPLETE_LAYAWAY_SALE->value];
    }

    public static function getOnlyPendingAndCompleteLayawaySaleStatusValues(): array
    {
        return [self::PENDING_LAYAWAY_SALE->value, self::COMPLETE_LAYAWAY_SALE->value];
    }

    public static function getOnlyPendingAndCompleteCreditSaleStatusValues(): array
    {
        return [self::PENDING_CREDIT_SALE->value, self::COMPLETE_CREDIT_SALE->value];
    }

    public static function getOnlyLayawayPendingAndCompleteSaleStatusValues(): array
    {
        return [self::REGULAR_SALE->value, self::PENDING_LAYAWAY_SALE->value, self::COMPLETE_LAYAWAY_SALE->value];
    }

    public static function getOnlyLayawayAndCreditCompleteSaleStatusValues(): array
    {
        return [self::REGULAR_SALE->value, self::COMPLETE_LAYAWAY_SALE->value, self::COMPLETE_CREDIT_SALE->value];
    }
}
