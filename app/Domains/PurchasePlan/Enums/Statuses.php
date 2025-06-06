<?php

declare(strict_types=1);

namespace App\Domains\PurchasePlan\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum Statuses: int
{
    use PrepareEnumDataMethods;

    case PENDING = 1;
    case APPROVED = 2;
    case CANCELLED = 3;
    case COMPLETED = 4;

    public static function getStatuses(): array
    {
        return [
            'pending' => self::PENDING->value,
            'approved' => self::APPROVED->value,
            'cancelled' => self::CANCELLED->value,
            'completed' => self::COMPLETED->value,
        ];
    }
}
