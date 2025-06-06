<?php

declare(strict_types=1);

namespace App\Domains\Store\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum StoreTimings: string
{
    use PrepareEnumDataMethods;

    case OPEN_TIME = '10:00';
    case CLOSE_TIME = '18:00';
}
