<?php

declare(strict_types=1);

namespace App\Domains\Promotion\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum ProductUploadTypes: int
{
    use PrepareEnumDataMethods;

    case REGULAR = 1;
    case BUY_PRODUCT = 2;
    case GET_PRODUCT = 3;
}
