<?php

declare(strict_types=1);

namespace App\Domains\SaleTarget\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum TargetType: int
{
    use PrepareEnumDataMethods;

    case COMPANY_WISE = 1;
    case STORE_WISE = 2;
    case PROMOTER_WISE = 3;
}
