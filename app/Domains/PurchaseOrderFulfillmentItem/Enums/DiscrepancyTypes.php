<?php

declare(strict_types=1);

namespace App\Domains\PurchaseOrderFulfillmentItem\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum DiscrepancyTypes: int
{
    use PrepareEnumDataMethods;

    case POSITIVE = 1;
    case NEGATIVE = 2;
    case BATCH_DISCREPANCY = 3;
}
