<?php

declare(strict_types=1);

namespace App\Domains\Member\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum StaticMembers: String
{
    use PrepareEnumDataMethods;

    case STATIC_MEMBER = '601999999999';
}
