<?php

declare(strict_types=1);

namespace App\Domains\StockTransfer\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum ShippedTypes: int
{
    use PrepareEnumDataMethods;

    case DIRECT = 1;
    case TRANSIT = 2;

    public static function getTypes(): array
    {
        return [
            'direct' => self::DIRECT->value,
            'transit' => self::TRANSIT->value,
        ];
    }
}
