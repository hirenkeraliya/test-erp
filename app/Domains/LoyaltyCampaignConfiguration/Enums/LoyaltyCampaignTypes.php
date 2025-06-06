<?php

declare(strict_types=1);

namespace App\Domains\LoyaltyCampaignConfiguration\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum LoyaltyCampaignTypes: int
{
    use PrepareEnumDataMethods;

    case PER_VISIT = 1;
    case AMOUNT_SPENT = 2;
    case SPECIFIC_PRODUCTS = 3;
    case PRODUCT_CATEGORIES = 4;
    case PRODUCT_BRANDS = 5;
}
