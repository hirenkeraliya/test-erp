<?php

declare(strict_types=1);

namespace App\Domains\QuantitySold\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum ReportTypes: int
{
    use PrepareEnumDataMethods;

    case BY_PARENT_ARTICLE_NUMBER = 1;
    case BY_UPC = 2;
}
