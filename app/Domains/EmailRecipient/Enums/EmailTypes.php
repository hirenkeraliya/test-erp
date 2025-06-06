<?php

declare(strict_types=1);

namespace App\Domains\EmailRecipient\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum EmailTypes: int
{
    use PrepareEnumDataMethods;

    case EXPORT_INVENTORY_REPORT = 1;
    case IMPORT_RECORDS_STATUS_UPDATES = 2;
    case AUTOMATED_NOTIFICATION = 3;
    case EXPORT_MEMBERS = 4;
}
