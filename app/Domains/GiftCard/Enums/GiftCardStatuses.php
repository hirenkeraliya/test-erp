<?php

declare(strict_types=1);

namespace App\Domains\GiftCard\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum GiftCardStatuses: int
{
    use PrepareEnumDataMethods;

    case ACTIVE = 1;
    case USED = 2;
    case EXPIRED = 3;
}
