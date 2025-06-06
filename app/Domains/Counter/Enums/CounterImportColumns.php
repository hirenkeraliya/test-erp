<?php

declare(strict_types=1);

namespace App\Domains\Counter\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum CounterImportColumns: string
{
    use PrepareEnumDataMethods;

    case NAME = 'name';
    case LOCATION = 'location';
    case IS_LOCKED = 'is_locked';
}
