<?php

declare(strict_types=1);

namespace App\Domains\Color\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum ColorImportColumns: string
{
    use PrepareEnumDataMethods;

    case NAME = 'name';
    case CODE = 'code';
    case COLOR_CODE = 'color_code';
    case COLOR_GROUP = 'color_group';
}
