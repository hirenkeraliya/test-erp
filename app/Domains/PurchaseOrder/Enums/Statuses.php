<?php

declare(strict_types=1);

namespace App\Domains\PurchaseOrder\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum Statuses: int
{
    use PrepareEnumDataMethods;

    case DRAFT = 1;
    case OPENED = 2;
    case APPROVED = 3;
    case REJECTED = 4;
    case CANCELLED = 5;
    case CLOSED = 6;
    case PARTIAL_FULFILLMENT = 7;
    case FULFILLMENT_COMPLETED = 8;

    public static function getStatuses(): array
    {
        return [
            'draft' => self::DRAFT->value,
            'opened' => self::OPENED->value,
            'approved' => self::APPROVED->value,
            'rejected' => self::REJECTED->value,
            'cancelled' => self::CANCELLED->value,
            'closed' => self::CLOSED->value,
            'partial_fulfillment' => self::PARTIAL_FULFILLMENT->value,
            'fulfillment_completed' => self::FULFILLMENT_COMPLETED->value,
        ];
    }
}
