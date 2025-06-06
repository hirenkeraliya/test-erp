<?php

declare(strict_types=1);

namespace App\Domains\ExternalPurchaseOrderReceive\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum Statuses: int
{
    use PrepareEnumDataMethods;

    case PENDING = 1;
    case CANCELLED = 2;
    case COMPLETED = 3;

    public static function getStatuses(): array
    {
        return [
            'pending' => self::PENDING->value,
            'cancelled' => self::CANCELLED->value,
            'completed' => self::COMPLETED->value,
        ];
    }
}
