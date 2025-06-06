<?php

declare(strict_types=1);

namespace App\Domains\Order\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum OrderChannels: int
{
    use PrepareEnumDataMethods;

    case B2B_ORDERS = 1;
    case E_COMMERCE = 2;
    case SOCIAL_COMMERCE = 3;
    case TIKTOK = 4;
    case SHOPEE = 5;
    case MOBILE_APPS_ANDROID = 6;
    case MOBILE_APPS_IOS = 7;
    case E_COMMERCE_WEBSITE = 8;
}
