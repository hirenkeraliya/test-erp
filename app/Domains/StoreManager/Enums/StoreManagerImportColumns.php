<?php

declare(strict_types=1);

namespace App\Domains\StoreManager\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum StoreManagerImportColumns: string
{
    use PrepareEnumDataMethods;

    case FIRST_NAME = 'first_name';
    case MOBILE_NUMBER = 'mobile_number';
    case USERNAME = 'username';
    case PASSWORD = 'password';
    case PASSCODE = 'passcode';
    case PRICE_OVERRIDE_TYPE = 'price_override_type';
    case PRICE_OVERRIDE_LIMIT_PERCENTAGE_FOR_ITEM = 'price_override_limit_percentage_for_item';
    case PRICE_OVERRIDE_LIMIT_PERCENTAGE_FOR_CART = 'price_override_limit_percentage_for_cart';
    case CAN_MANAGE_WHOLESALE = 'can_manage_wholesale';
    case LOCATIONS = 'locations';
    case ROLES = 'roles';
    case BRANDS = 'brands';
}
