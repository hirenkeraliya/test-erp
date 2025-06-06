<?php

declare(strict_types=1);

namespace App\Domains\Common\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum MerchandisingReport: int
{
    use PrepareEnumDataMethods;

    case DISCOUNT_REPORT = 1;
    case DISCOUNT_SUMMARY_REPORT = 2;
    case TOP_20_PRODUCTS = 3;
    case WORST_20_PRODUCTS = 4;
}
