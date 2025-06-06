<?php

declare(strict_types=1);

namespace App\Domains\Sale\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum WorstTwentyReportViewTypes: int
{
    use PrepareEnumDataMethods;

    case BY_AMOUNT = 1;
    case BY_QUANTITY = 2;
}
