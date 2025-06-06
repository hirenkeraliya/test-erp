<?php

declare(strict_types=1);

namespace App\Domains\OrderAddress\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum OrderAddressesType: int
{
    use PrepareEnumDataMethods;

    case SHIPPING_ADDRESS = 1;
    case BILLING_ADDRESS = 2;
}
