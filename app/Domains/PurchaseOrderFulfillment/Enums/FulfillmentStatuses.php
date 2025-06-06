<?php

declare(strict_types=1);

namespace App\Domains\PurchaseOrderFulfillment\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum FulfillmentStatuses: int
{
    use PrepareEnumDataMethods;

    case DRAFT = 1;
    case OPEN = 7;
    case SHIPPED = 2;
    case RECEIVED = 3;
    case DISCREPANCY = 4;
    case CANCELLED = 6;
    case CLOSED = 5;

    public static function getStatuses(): array
    {
        return [
            'draft' => self::DRAFT->value,
            'open' => self::OPEN->value,
            'shipped' => self::SHIPPED->value,
            'received' => self::RECEIVED->value,
            'discrepancy' => self::DISCREPANCY->value,
            'cancelled' => self::CANCELLED->value,
            'closed' => self::CLOSED->value,
        ];
    }
}
