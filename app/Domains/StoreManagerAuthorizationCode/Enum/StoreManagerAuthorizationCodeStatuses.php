<?php

namespace App\Domains\StoreManagerAuthorizationCode\Enum;

enum StoreManagerAuthorizationCodeStatuses: int
{
    case ACTIVE = 1;
    case CANCELLED = 2;
    case EXPIRED = 3;
}
