<?php

declare(strict_types=1);

namespace App\Domains\Sale\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum PromoterCommissionReportTypes: int
{
    use PrepareEnumDataMethods;

    case BY_ITEM = 1;
}
