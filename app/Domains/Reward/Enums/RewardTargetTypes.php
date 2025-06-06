<?php

declare(strict_types=1);

namespace App\Domains\Reward\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum RewardTargetTypes: int
{
    use PrepareEnumDataMethods;

    case PRODUCTS = 1;
    case CATEGORIES = 2;
    case BRANDS = 3;
    case DEPARTMENTS = 4;
}
