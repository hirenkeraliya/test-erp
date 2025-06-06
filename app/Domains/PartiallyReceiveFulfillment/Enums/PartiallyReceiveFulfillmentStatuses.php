<?php

declare(strict_types=1);

namespace App\Domains\PartiallyReceiveFulfillment\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum PartiallyReceiveFulfillmentStatuses: int
{
    use PrepareEnumDataMethods;

    case DRAFT = 1;
    case APPROVED = 2;
    case COMPLETED = 3;
    case CANCELLED = 4;

    public static function getStatuses(): array
    {
        return [
            'draft' => self::DRAFT->value,
            'approved' => self::APPROVED->value,
            'completed' => self::COMPLETED->value,
            'cancelled' => self::CANCELLED->value,
        ];
    }
}
