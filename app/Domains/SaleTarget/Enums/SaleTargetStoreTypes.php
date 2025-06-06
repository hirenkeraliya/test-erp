<?php

declare(strict_types=1);

namespace App\Domains\SaleTarget\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum SaleTargetStoreTypes: int
{
    use PrepareEnumDataMethods;

    case SELECT = 1;
    case UPLOAD = 2;
}
