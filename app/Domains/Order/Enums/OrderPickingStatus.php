<?php

declare(strict_types=1);

namespace App\Domains\Order\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum OrderPickingStatus: int
{
    use PrepareEnumDataMethods;

    case DRAFT = 1;
    case IN_PROGRESS = 2;
    case COMPLETED = 3;
    case CANCELLED = 4;
}
