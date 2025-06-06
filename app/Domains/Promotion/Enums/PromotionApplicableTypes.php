<?php

declare(strict_types=1);

namespace App\Domains\Promotion\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum PromotionApplicableTypes: int
{
    use PrepareEnumDataMethods;

    case CART_WIDE = 1;
    case ITEM_WISE = 2;
}
