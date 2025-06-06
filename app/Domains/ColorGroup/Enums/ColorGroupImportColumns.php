<?php

declare(strict_types=1);

namespace App\Domains\ColorGroup\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum ColorGroupImportColumns: string
{
    use PrepareEnumDataMethods;

    case NAME = 'name';
    case CODE = 'code';
    case COLOR_CODE = 'color_code';
}
