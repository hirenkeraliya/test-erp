<?php

declare(strict_types=1);

namespace App\Domains\SiteConfiguration\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum EcommerceType: int
{
    use PrepareEnumDataMethods;

    case SEPARATE_FOR_EACH_COMPANY = 1;
    case MULTI_SELLER = 2;
}
