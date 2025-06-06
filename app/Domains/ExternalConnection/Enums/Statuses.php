<?php

declare(strict_types=1);

namespace App\Domains\ExternalConnection\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum Statuses: int
{
    use PrepareEnumDataMethods;

    case PENDING = 0;
    case APPROVED = 1;
    case REJECTED = 2;
}
