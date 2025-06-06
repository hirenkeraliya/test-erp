<?php

declare(strict_types=1);

namespace App\Domains\Promoter\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum SalesByPromoterReportExcludeTypes: int
{
    use PrepareEnumDataMethods;

    case VOID_SALE = 1;
    case PENDING_LAYAWAY_SALE = 2;
    case COMPLETED_LAYAWAY_SALES = 3;
    case EXCHANGE_SALES = 4;
    case RETURN_WITH_NEW_SALE = 5;
    case PENDING_CREDIT_SALE = 6;
    case COMPLETE_CREDIT_SALE = 7;
}
