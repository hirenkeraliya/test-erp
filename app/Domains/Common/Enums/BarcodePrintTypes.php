<?php

declare(strict_types=1);

namespace App\Domains\Common\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum BarcodePrintTypes: String
{
    use PrepareEnumDataMethods;

    case MANUAL = 'Manual';
    case BY_MODULE = 'By Module';
}
