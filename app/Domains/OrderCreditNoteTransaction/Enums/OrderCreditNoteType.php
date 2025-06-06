<?php

declare(strict_types=1);

namespace App\Domains\OrderCreditNoteTransaction\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum OrderCreditNoteType: int
{
    use PrepareEnumDataMethods;

    case USES = 1;
    case EXPIRED = 2;
    case REFUNDS = 3;
    case CANCEL = 4;
}
