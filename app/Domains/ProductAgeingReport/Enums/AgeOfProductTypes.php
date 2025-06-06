<?php

declare(strict_types=1);

namespace App\Domains\ProductAgeingReport\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum AgeOfProductTypes: int
{
    use PrepareEnumDataMethods;

    case CREATED_AT = 1;
    case FIRST_TRANSFER_IN = 2;
    case FIRST_GOODS_RECEIVED_NOTE = 3;
}
