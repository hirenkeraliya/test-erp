<?php

declare(strict_types=1);

namespace App\Domains\CreditNote\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum CreditNoteStatuses: int
{
    use PrepareEnumDataMethods;

    case ACTIVE = 1;
    case USED = 2;
    case EXPIRED = 3;
    case REFUNDED = 4;
}
