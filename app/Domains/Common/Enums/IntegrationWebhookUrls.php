<?php

declare(strict_types=1);

namespace App\Domains\Common\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum IntegrationWebhookUrls: int
{
    use PrepareEnumDataMethods;

    case COMPANY_CREATE = 1;
    case COMPANY_UPDATES = 2;
    case BRAND_CREATE = 3;
    case BRAND_UPDATES = 4;
    case SEASON_CREATE = 5;
    case SEASON_UPDATES = 6;
    case STYLE_CREATE = 7;
    case STYLE_UPDATES = 8;
    case VENDOR_CREATE = 9;
    case VENDOR_UPDATES = 10;
    case LOCATION_CREATE = 11;
    case LOCATION_UPDATES = 12;
    case REGION_CREATE = 13;
    case REGION_UPDATES = 14;
    case TEMPLATE_CREATE = 15;
    case TEMPLATE_UPDATES = 16;
    case ATTRIBUTE_CREATE = 17;
    case ATTRIBUTE_UPDATES = 18;
    case MASTER_PRODUCT_CREATE_OR_UPDATES = 19;
    case COUNTRY_CREATE = 21;
    case COUNTRY_UPDATES = 22;
    case STATE_CREATE = 23;
    case STATE_UPDATES = 24;
    case CITY_CREATE = 25;
    case CITY_UPDATES = 26;
    case ATTRIBUTE_DELETE = 27;
    case PRODUCT_CREATE_OR_UPDATES = 28;
}
