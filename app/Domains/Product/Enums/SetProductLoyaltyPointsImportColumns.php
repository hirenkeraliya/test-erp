<?php

declare(strict_types=1);

namespace App\Domains\Product\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum SetProductLoyaltyPointsImportColumns: string
{
    use PrepareEnumDataMethods;

    case UPC = 'upc';
    case MEMBERSHIP = 'membership';
    case LOYALTY_POINTS = 'loyalty_points';
}
