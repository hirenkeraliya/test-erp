<?php

declare(strict_types=1);

namespace App\Domains\ExportRecord\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum ExportRecordStatuses: int
{
    use PrepareEnumDataMethods;

    case PENDING = 1;
    case IN_PROGRESS = 2;
    case FAILED = 3;
    case GENERATED = 4;
}
