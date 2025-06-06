<?php

declare(strict_types=1);

namespace App\Domains\PaymentType\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum PaymentRestrictionTypes: int
{
    use PrepareEnumDataMethods;

    case INCLUSION = 1;
    case EXCLUSION = 2;
}
