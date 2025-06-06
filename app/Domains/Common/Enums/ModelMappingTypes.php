<?php

declare(strict_types=1);

namespace App\Domains\Common\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum ModelMappingTypes: int
{
    use PrepareEnumDataMethods;

    case BASE_MODULES = 1;
    case CHILD_MODULES = 2;
}
