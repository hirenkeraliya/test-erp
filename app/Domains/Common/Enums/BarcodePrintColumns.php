<?php

declare(strict_types=1);

namespace App\Domains\Common\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum BarcodePrintColumns: string
{
    use PrepareEnumDataMethods;

    case BRAND_NAME = 'brand_name';
    case ARTICLE_NUMBER = 'article_number';
    case COLOR = 'color';
    case SIZE = 'size';
    case PRICE = 'price';
    case STYLE = 'style';
    case ATTRIBUTES = 'attributes';
}
