<?php

declare(strict_types=1);

namespace App\Domains\PurchaseOrder\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum InterCompanyTransferType: int
{
    use PrepareEnumDataMethods;
    case PURCHASE_REQUEST = 1;
    case TRANSFER_REQUEST = 2;
    case SALES_ORDER = 3;
    case PURCHASE_ORDER = 4;
    case DELIVERY_ORDER = 5;
}
