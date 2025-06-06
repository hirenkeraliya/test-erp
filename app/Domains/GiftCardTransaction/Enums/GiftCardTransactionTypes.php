<?php

declare(strict_types=1);

namespace App\Domains\GiftCardTransaction\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum GiftCardTransactionTypes: int
{
    use PrepareEnumDataMethods;

    case USED = 1;
    case VOID_SALE = 2;
}
