<?php

declare(strict_types=1);

namespace App\Domains\MysteryGift\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum Statuses: int
{
    use PrepareEnumDataMethods;

    case ACTIVE = 1;
    case INACTIVE = 0;
}
