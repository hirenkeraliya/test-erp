<?php

declare(strict_types=1);

namespace App\Domains\Common\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum OthersReport: int
{
    use PrepareEnumDataMethods;

    case PROMOTER_COMMISSION = 1;
    case SEASONAL_SALES = 2;
    case ACCUMULATED_SELL_THROUGH = 3;
}
