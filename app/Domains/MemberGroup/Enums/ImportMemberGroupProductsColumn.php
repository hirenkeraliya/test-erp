<?php

declare(strict_types=1);

namespace App\Domains\MemberGroup\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum ImportMemberGroupProductsColumn: int
{
    use PrepareEnumDataMethods;

    case UPC = 1;
}
