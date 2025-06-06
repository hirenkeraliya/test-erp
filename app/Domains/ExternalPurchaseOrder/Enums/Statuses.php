<?php

declare(strict_types=1);

namespace App\Domains\ExternalPurchaseOrder\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum Statuses: int
{
    use PrepareEnumDataMethods;

    case PENDING = 1;
    case PARTIAL = 2;
    case CANCELLED = 3;
    case COMPLETED = 4;
    case APPROVED = 5;

    public static function getStatuses(): array
    {
        return [
            'pending' => self::PENDING->value,
            'partial' => self::PARTIAL->value,
            'cancelled' => self::CANCELLED->value,
            'completed' => self::COMPLETED->value,
            'approved' => self::APPROVED->value,
        ];
    }
}
