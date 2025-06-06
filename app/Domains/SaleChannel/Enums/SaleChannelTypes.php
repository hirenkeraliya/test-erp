<?php

declare(strict_types=1);

namespace App\Domains\SaleChannel\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum SaleChannelTypes: int
{
    use PrepareEnumDataMethods;

    case ECOMMERCE = 1;
    case WEBSPERT_ECOMMERCE = 2;
}
