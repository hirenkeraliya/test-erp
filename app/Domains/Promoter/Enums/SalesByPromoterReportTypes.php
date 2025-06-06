<?php

declare(strict_types=1);

namespace App\Domains\Promoter\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum SalesByPromoterReportTypes: int
{
    use PrepareEnumDataMethods;

    case BY_DETAILS = 1;
    case BY_SUMMARY = 2;
}
