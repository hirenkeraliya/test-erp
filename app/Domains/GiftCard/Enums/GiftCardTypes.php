<?php

declare(strict_types=1);

namespace App\Domains\GiftCard\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum GiftCardTypes: int
{
    use PrepareEnumDataMethods;

    case SINGLE_USE_ONLY = 1;
    case MULTIPLE_USES = 2;
}
