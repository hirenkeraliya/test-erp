<?php

declare(strict_types=1);

namespace App\Domains\StockTake\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum StockTakeImportColumns: string
{
    use PrepareEnumDataMethods;

    case PRODUCT_NAME = 'product_name';
    case UPC = 'upc';
    case ARTICLE_NUMBER = 'article_number';
    case SIZE = 'size';
    case COLOR = 'color';
    case UNIT_OF_MEASURE = 'unit_of_measure';
    case SUBMITTED_STOCK = 'submitted_stock';
}
