<?php

declare(strict_types=1);

namespace App\Domains\Promoter\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum PromoterBulkUpdateCommissionImportColumns: string
{
    use PrepareEnumDataMethods;

    case FIRST_NAME = 'first_name';
    case MOBILE_NUMBER = 'mobile_number';
    case USERNAME = 'username';
    case CODE = 'code';
    case GROUP = 'group';
    case LOCATIONS = 'locations';
    case MONTHLY_SALES_TARGET = 'monthly_sales_target';
    case DEFAULT_COMMISSION_AMOUNT_PERCENTAGE = 'default_commission_amount_percentage';
    case MONTHLY_TARGET_COMMISSION_PERCENTAGE = 'monthly_target_commission_percentage';
}
