<?php

declare(strict_types=1);

namespace App\Domains\DynamicMenus\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum DynamicMenuTypesEnum: int
{
    use PrepareEnumDataMethods;

    case BRAND = 1;
    case CATEGORIES = 2;
    case PRODUCT_COLLECTION = 3;
    case STATIC_PAGE = 4;
}
