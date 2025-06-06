<?php

declare(strict_types=1);

namespace App\Domains\GoodsReceivedNote\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum GoodsReceivedNoteFilterTypes: int
{
    use PrepareEnumDataMethods;

    case BY_BRAND = 1;
    case BY_DEPARTMENT = 2;
    case BY_PRODUCT = 3;
    case BY_ARTICLE_NUMBER = 4;
    case BY_VENDOR = 5;
    case BY_PRODUCT_COLLECTION = 6;
}
