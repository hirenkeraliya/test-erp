<?php

declare(strict_types=1);

namespace App\Domains\Category\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum CategoryImportColumns: string
{
    use PrepareEnumDataMethods;

    case NAME = 'name';
    case CODE = 'code';
    case DESCRIPTION = 'description';
    case STATUS = 'status';
    case IS_AVAILABLE_IN_ECOMMERCE = 'is_available_in_ecommerce';
    case IS_DISPLAY_ON_MENU = 'is_display_on_menu';
}
