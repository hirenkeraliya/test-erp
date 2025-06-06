<?php

declare(strict_types=1);

namespace App\Domains\StockTransfer\Enums;

use App\CommonFunctions;
use App\Http\Traits\PrepareEnumDataMethods;

enum StatusTypes: int
{
    use PrepareEnumDataMethods;

    case DRAFT = 1;
    case OPEN = 2;
    case SHIPPED = 3;
    case RECEIVED = 4;
    case DISCREPANCY = 5;
    case CLOSED = 6;
    case CANCELLED = 7;
    case REJECTED = 8;
    case APPROVED = 9;
    case TRANSIT = 10;
    case TRANSIT_IN = 11;
    case TRANSIT_OUT = 12;
    case SYSTEM_GENERATED = 13;

    public static function getStatuses(): array
    {
        return [
            'draft' => self::DRAFT->value,
            'open' => self::OPEN->value,
            'approved' => self::APPROVED->value,
            'shipped' => self::SHIPPED->value,
            'received' => self::RECEIVED->value,
            'discrepancy' => self::DISCREPANCY->value,
            'closed' => self::CLOSED->value,
            'cancelled' => self::CANCELLED->value,
            'rejected' => self::REJECTED->value,
            'transit' => self::TRANSIT->value,
            'transit_in' => self::TRANSIT_IN->value,
            'transit_out' => self::TRANSIT_OUT->value,
            'system_generated' => self::SYSTEM_GENERATED->value,
        ];
    }

    public static function getTitleStatuses(): array
    {
        return collect(self::getStatuses())->map(
            fn ($value, $key): string => CommonFunctions::stringTitleLowerCase($key)
        )->toArray();
    }

    public static function getStockTransferStatusSummary(bool $nameInTitleCase = true): array
    {
        return collect(self::cases())
            ->filter(fn ($type): bool => $type->value !== self::SYSTEM_GENERATED->value)
            ->map(fn ($type): array => [
                'id' => $type->value,
                'name' => $nameInTitleCase ? CommonFunctions::stringTitleLowerCase($type->name) : $type->name,
            ])
        ->toArray();
    }
}
