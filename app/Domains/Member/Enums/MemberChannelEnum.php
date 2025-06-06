<?php

declare(strict_types=1);

namespace App\Domains\Member\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum MemberChannelEnum: int
{
    use PrepareEnumDataMethods;

    case ADMIN = 1;
    case POS = 2;
    case QR_CODE = 3;
    case PROMOTER = 4;
    case STORE_MANAGER = 5;
    case M_COMMERCE = 6;
    case E_COMMERCE = 7;
}
