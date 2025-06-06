<?php

declare(strict_types=1);

namespace App\Domains\ProductAgeingReport\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum ProductAgeingReportTypes: int
{
    use PrepareEnumDataMethods;

    case BASIC_PRODUCT_AGING_REPORT = 1;
    case PRODUCT_AGING_REPORT_BY_MONTH_AND_YEAR = 2;
    case PRODUCT_AGING_BASED_ON_ARTICLE_NUMBER = 3;
    case PRODUCT_AGING_BASED_ON_UPC = 4;
}
