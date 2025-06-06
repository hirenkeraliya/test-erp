<?php

declare(strict_types=1);

namespace App\Domains\ProductCollection\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum LogicalConnectorTypes: int
{
    use PrepareEnumDataMethods;

    case AND = 1;
    case OR = 2;
}
