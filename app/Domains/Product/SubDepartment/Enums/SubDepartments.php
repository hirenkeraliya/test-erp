<?php

declare(strict_types=1);

namespace App\Domains\Product\SubDepartment\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum SubDepartments: int
{
    use PrepareEnumDataMethods;

    case GDS = 1;
    case OPS = 2;
}
