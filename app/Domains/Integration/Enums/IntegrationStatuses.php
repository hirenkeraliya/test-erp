<?php

declare(strict_types=1);

namespace App\Domains\Integration\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum IntegrationStatuses: int
{
    use PrepareEnumDataMethods;

    case ACTIVE = 1;
    case INACTIVE = 0;
}
