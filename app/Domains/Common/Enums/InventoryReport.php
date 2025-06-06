<?php

declare(strict_types=1);

namespace App\Domains\Common\Enums;

use App\CommonFunctions;
use App\Http\Traits\PrepareEnumDataMethods;

enum InventoryReport: int
{
    use PrepareEnumDataMethods;

    case GOODS_RECEIVED_NOTES = 1;
    case STOCK_TRANSFER = 2;
    case STOCK_TRANSFER_BY_STATUS = 3;
    case STOCK_TRANSFER_DISCREPANCY = 4;
    case STOCK_CARD = 5;
    case STOCK_MOVEMENTS = 6;
    case STOCK_ADJUSTMENT = 7;
    case STOCK_TRANSFER_STATUS_SUMMARY = 8;
    case STOCK_SUMMARY_BY_MODULE = 9;

    public static function getMenuForStoreManagerAndWarehouseManager(bool $nameInTitleCase = true): array
    {
        return collect(self::cases())
            ->filter(fn ($type): bool => $type->value !== self::STOCK_TRANSFER_STATUS_SUMMARY->value)
            ->map(fn ($type): array => [
                'id' => $type->value,
                'name' => $nameInTitleCase ? CommonFunctions::stringTitleLowerCase($type->name) : $type->name,
            ])
        ->toArray();
    }
}
