<?php

declare(strict_types=1);

namespace App\Domains\Common\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum SaleReturnOrVoidSaleReasonTypes: int
{
    use PrepareEnumDataMethods;

    case POS = 1;
    case ORDERS = 2;
}
