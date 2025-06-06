<?php

declare(strict_types=1);

namespace App\Domains\Dashboard\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum StoreRevenueDashboardTableFilterTypes: int
{
    use PrepareEnumDataMethods;

    case CATEGORIES = 1;
    case COLORS = 2;
    case BRANDS = 3;
    case DEPARTMENTS = 4;
    case COLOR_GROUPS = 5;
    case SIZES = 6;
    case STYLES = 7;
}
