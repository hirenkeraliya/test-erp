<?php

declare(strict_types=1);

namespace App\Domains\Product\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum ProductBatches: string
{
    use PrepareEnumDataMethods;

    case ALL = 'all';
    case HAS_BATCH = 'has_batch';
    case NO_BATCH = 'no_batch';
}
