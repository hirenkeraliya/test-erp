<?php

declare(strict_types=1);

namespace App\Domains\PosAdvertisement\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum PosAdvertisementTypes: int
{
    use PrepareEnumDataMethods;

    case IMAGE = 1;
    case VIDEO = 2;
}
