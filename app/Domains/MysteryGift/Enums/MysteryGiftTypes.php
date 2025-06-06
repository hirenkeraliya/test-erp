<?php

declare(strict_types=1);

namespace App\Domains\MysteryGift\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum MysteryGiftTypes: int
{
    use PrepareEnumDataMethods;

    case IS_FLAT_AMOUNT = 1;
    case IS_PERCENTAGE = 2;
    case IS_FREE_PRODUCT = 3;
}
