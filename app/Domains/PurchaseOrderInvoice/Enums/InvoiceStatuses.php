<?php

declare(strict_types=1);

namespace App\Domains\PurchaseOrderInvoice\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum InvoiceStatuses: int
{
    use PrepareEnumDataMethods;

    case DRAFT = 1;
    case SENT = 2;
    case RECEIVED = 3;
    case PAID = 4;
    case CANCELLED = 5;

    public static function getStatuses(): array
    {
        return [
            'draft' => self::DRAFT->value,
            'sent' => self::SENT->value,
            'received' => self::RECEIVED->value,
            'paid' => self::PAID->value,
            'cancelled' => self::CANCELLED->value,
        ];
    }
}
