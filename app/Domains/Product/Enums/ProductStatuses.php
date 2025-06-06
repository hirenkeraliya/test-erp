<?php

declare(strict_types=1);

namespace App\Domains\Product\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum ProductStatuses: string
{
    use PrepareEnumDataMethods;

    case ALL = 'all';
    case ACTIVE = 'active';
    case ARCHIVED = 'archived';
}
