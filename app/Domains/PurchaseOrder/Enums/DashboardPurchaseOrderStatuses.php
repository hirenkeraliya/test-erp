<?php

declare(strict_types=1);

namespace App\Domains\PurchaseOrder\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum DashboardPurchaseOrderStatuses: int
{
    use PrepareEnumDataMethods;

    case APPROVED = 3;
    case PARTIAL_FULFILLMENT = 7;
    case FULFILLMENT_COMPLETED = 8;
    case REJECTED = 4;
    case CANCELLED = 5;
    case CLOSED = 6;

    public static function getStatuses(): array
    {
        return [
            'approved' => self::APPROVED->value,
            'partial_fulfillment' => self::PARTIAL_FULFILLMENT->value,
            'fulfillment_completed' => self::FULFILLMENT_COMPLETED->value,
            'rejected' => self::REJECTED->value,
            'cancelled' => self::CANCELLED->value,
            'closed' => self::CLOSED->value,
        ];
    }
}
