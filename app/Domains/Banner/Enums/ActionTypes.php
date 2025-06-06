<?php

declare(strict_types=1);

namespace App\Domains\Banner\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum ActionTypes: int
{
    use PrepareEnumDataMethods;

    case HOME = 1;
    case CUSTOM_URL = 2;
}
