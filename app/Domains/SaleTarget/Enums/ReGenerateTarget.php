<?php

declare(strict_types=1);

namespace App\Domains\SaleTarget\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum ReGenerateTarget: int
{
    use PrepareEnumDataMethods;

    case COMPLETE = 0;
    case IN_PROGRESS = 1;
}
