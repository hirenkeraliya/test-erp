<?php

declare(strict_types=1);

namespace App\Domains\SizeGroup\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum SizeGroupImportColumns: string
{
    use PrepareEnumDataMethods;

    case NAME = 'name';
    case CODE = 'code';
}
