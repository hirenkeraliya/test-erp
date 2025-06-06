<?php

declare(strict_types=1);

namespace App\Domains\Product\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum ProductPrices: string
{
    use PrepareEnumDataMethods;

    case RETAIL_PRICE = 'retail_price';
    case FRANCHISE_PRICE_1 = 'franchise_price_1';
    case FRANCHISE_PRICE_2 = 'franchise_price_2';
    case FRANCHISE_PRICE_3 = 'franchise_price_3';
    case WHOLESALE_PRICE = 'wholesale_price';
    case COMPANY_OR_TENDER_PRICE = 'company_or_tender_price';
    case BRANCH_PRICE = 'branch_price';
    case MINIMUM_PRICE = 'minimum_price';
    case ORIGINAL_CAPITAL_PRICE = 'original_capital_price';
    case CAPITAL_PRICE = 'capital_price';
    case STAFF_PRICE = 'staff_price';
}
