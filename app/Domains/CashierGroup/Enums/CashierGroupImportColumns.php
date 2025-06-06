<?php

declare(strict_types=1);

namespace App\Domains\CashierGroup\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum CashierGroupImportColumns: string
{
    use PrepareEnumDataMethods;

    case NAME = 'name';
    case PERMISSIONS = 'permissions';
    case PRICE_OVERRIDE_LIMIT_PERCENTAGE_FOR_CART = 'price_override_limit_percentage_for_cart';
    case PRICE_OVERRIDE_TYPE = 'price_override_type';
    case PRICE_OVERRIDE_LIMIT_PERCENTAGE_FOR_ITEM = 'price_override_limit_percentage_for_item';
}
