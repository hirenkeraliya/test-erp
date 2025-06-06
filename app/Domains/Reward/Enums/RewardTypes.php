<?php

declare(strict_types=1);

namespace App\Domains\Reward\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum RewardTypes: int
{
    use PrepareEnumDataMethods;

    case DISCOUNT_ON_ENTIRE_SALE = 1;
    case FREE_ITEM = 2;
}
