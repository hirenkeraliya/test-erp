<?php

declare(strict_types=1);

namespace App\Domains\Company\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum CompanyStatuses: string
{
    use PrepareEnumDataMethods;

    case ALL = 'all';
    case ACTIVE = 'active';
    case ARCHIVED = 'archived';
}
