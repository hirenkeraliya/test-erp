<?php

declare(strict_types=1);

namespace App\Domains\Size\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum SizeImportColumns: string
{
    use PrepareEnumDataMethods;

    case NAME = 'name';
    case CODE = 'code';
    case SIZE_GROUP = 'size_group';
    case CREATE_AFTER = 'create_after';
}
