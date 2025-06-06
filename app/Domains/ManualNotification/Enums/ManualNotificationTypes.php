<?php

declare(strict_types=1);

namespace App\Domains\ManualNotification\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum ManualNotificationTypes: int
{
    use PrepareEnumDataMethods;

    case PROMOTERS = 1;
    case MEMBERS = 2;
}
