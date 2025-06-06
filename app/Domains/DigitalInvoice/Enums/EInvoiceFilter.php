<?php

declare(strict_types=1);

namespace App\Domains\DigitalInvoice\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum EInvoiceFilter: int
{
    use PrepareEnumDataMethods;

    case YES = 1;
    case NO = 0;
}
