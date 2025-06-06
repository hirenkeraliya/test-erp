<?php

declare(strict_types=1);

namespace App\Domains\Product\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum ProductSyncTypes: int
{
    use PrepareEnumDataMethods;

    case ALL_PRODUCT = 1;
    case SYNC_WITH_ECOMMERCE = 2;
    case SYNC_WITH_WEBSPERT = 3;
    case NOT_SYNC_WITH_WEBSPERT = 4;
    case NOT_SYNC_WITH_ECOMMERCE = 5;
}
