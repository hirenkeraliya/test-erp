<?php

declare(strict_types=1);

namespace App\Domains\DreamPrice\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum DreamPriceImportColumns: string
{
    use PrepareEnumDataMethods;

    case UPC = 'upc';
    case PRICE = 'price';
}
