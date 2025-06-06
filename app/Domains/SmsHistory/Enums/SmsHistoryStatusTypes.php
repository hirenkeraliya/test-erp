<?php

declare(strict_types=1);

namespace App\Domains\SmsHistory\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum SmsHistoryStatusTypes: int
{
    use PrepareEnumDataMethods;

    case PENDING = 1;
    case SUCCESS = 2;
}
