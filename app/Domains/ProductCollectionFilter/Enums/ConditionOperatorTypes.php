<?php

declare(strict_types=1);

namespace App\Domains\ProductCollectionFilter\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum ConditionOperatorTypes: int
{
    use PrepareEnumDataMethods;

    case CONTAINS = 1;
    case LESS_THAN = 2;
    case GREATER_THAN = 3;
    case EQUAL = 4;
}
