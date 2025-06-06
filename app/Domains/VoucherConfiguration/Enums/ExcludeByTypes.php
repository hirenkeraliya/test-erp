<?php

declare(strict_types=1);

namespace App\Domains\VoucherConfiguration\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum ExcludeByTypes: int
{
    use PrepareEnumDataMethods;

    case NONE = 1;
    case PRODUCTS = 2;
    case CATEGORIES = 3;
}
