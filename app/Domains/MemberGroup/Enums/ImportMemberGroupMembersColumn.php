<?php

declare(strict_types=1);

namespace App\Domains\MemberGroup\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum ImportMemberGroupMembersColumn: int
{
    use PrepareEnumDataMethods;

    case MOBILE_NUMBER = 1;
    case CARD_NUMBER = 2;
}
