<?php

declare(strict_types=1);

namespace App\Domains\CounterUpdate\Enums;

enum CounterStatus: int
{
    case ALL = 1;
    case OPEN = 2;
    case CLOSE = 3;
}
