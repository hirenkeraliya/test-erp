<?php

declare(strict_types=1);

namespace App\Domains\Cashier\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum CashierImportColumns: string
{
    use PrepareEnumDataMethods;

    case FIRST_NAME = 'first_name';
    case MOBILE_NUMBER = 'mobile_number';
    case USERNAME = 'username';
    case PIN = 'pin';
    case CASHIER_GROUP = 'cashier_group';
    case LOCATIONS = 'locations';
}
