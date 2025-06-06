<?php

declare(strict_types=1);

namespace App\Domains\Product\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum SetProductBoxUnitsImportColumns: string
{
    use PrepareEnumDataMethods;

    case UPC = 'upc';
    case PACKAGE_TYPE_NAME = 'package_type_name';
    case UNITS = 'units';
    case RETAIL_PRICE = 'retail_price';
    case STAFF_PRICE = 'staff_price';
}
