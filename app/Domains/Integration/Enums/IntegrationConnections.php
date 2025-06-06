<?php

declare(strict_types=1);

namespace App\Domains\Integration\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum IntegrationConnections: int
{
    use PrepareEnumDataMethods;

    case NETSUITE = 1;
    case RETAIL_PLANNING = 2;
    case ONE_ERP = 3;
}
