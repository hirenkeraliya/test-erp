<?php

declare(strict_types=1);

namespace App\Domains\OrderIntegration\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum IntegrationStatuses: int
{
    use PrepareEnumDataMethods;

    case CREATE_ORDER = 1;
    case GENERATE_WAY_BILL = 2;
    case COMPLETE = 3;
}
