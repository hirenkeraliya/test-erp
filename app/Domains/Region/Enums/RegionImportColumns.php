<?php

declare(strict_types=1);

namespace App\Domains\Region\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum RegionImportColumns: string
{
    use PrepareEnumDataMethods;

    case NAME = 'name';
    case CODE = 'code';
    case MANAGER_NAME = 'manager_name';
    case MANAGER_EMAIL = 'manager_email';
}
