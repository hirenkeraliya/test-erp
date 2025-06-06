<?php

declare(strict_types=1);

namespace App\Domains\Common\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum CourierWebhookUrls: int
{
    use PrepareEnumDataMethods;

    case ACCESS_TOKEN = 1;
    case CREATE_ORDER = 2;
    case GENERATE_WAY_BILL = 3;
}
