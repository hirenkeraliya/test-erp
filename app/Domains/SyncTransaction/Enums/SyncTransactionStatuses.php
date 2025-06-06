<?php

declare(strict_types=1);

namespace App\Domains\SyncTransaction\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum SyncTransactionStatuses: int
{
    use PrepareEnumDataMethods;

    case IN_PROGRESS = 1;
    case COMPLETED = 2;
}
