<?php

declare(strict_types=1);

namespace App\Domains\PurchaseOrder\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum DashboardPurchaseRequestStatuses: int
{
    use PrepareEnumDataMethods;

    case DRAFT = 1;
    case OPENED = 2;
    case REJECTED = 4;
    case CANCELLED = 5;

    public static function getStatuses(): array
    {
        return [
            'draft' => self::DRAFT->value,
            'opened' => self::OPENED->value,
            'rejected' => self::REJECTED->value,
            'cancelled' => self::CANCELLED->value,
        ];
    }
}
