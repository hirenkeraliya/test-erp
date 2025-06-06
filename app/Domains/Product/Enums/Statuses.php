<?php

declare(strict_types=1);

namespace App\Domains\Product\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum Statuses: int
{
    use PrepareEnumDataMethods;

    case DRAFT = 1;
    case ACTIVE = 2;
    case ARCHIVED = 3;
}
