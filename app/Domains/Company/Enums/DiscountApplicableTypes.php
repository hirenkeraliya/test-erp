<?php

declare(strict_types=1);

namespace App\Domains\Company\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum DiscountApplicableTypes: int
{
    use PrepareEnumDataMethods;

    case ADDITIONAL_DISCOUNT_ON_ALREADY_DISCOUNTED_PRICES = 1;
    case DISCOUNT_APPLIED_TO_THE_ORIGINAL_PRICE = 2;
}
