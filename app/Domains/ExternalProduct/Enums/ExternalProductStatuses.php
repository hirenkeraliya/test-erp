<?php

declare(strict_types=1);

namespace App\Domains\ExternalProduct\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum ExternalProductStatuses: int
{
    use PrepareEnumDataMethods;

    case PENDING = 1;
    case APPROVED = 2;
    case REJECTED = 3;
    case CREATED = 4;
    case IN_PROGRESS = 5;
    case DUPLICATE = 6;
}
