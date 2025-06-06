<?php

declare(strict_types=1);

namespace App\Domains\Common\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum PurchasingReport: int
{
    use PrepareEnumDataMethods;

    case INTER_COMPANY_TRANSFER = 1;
    case INTER_COMPANY_TRANSFER_INVOICES = 2;
}
