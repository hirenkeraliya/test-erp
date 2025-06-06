<?php

declare(strict_types=1);

namespace App\Domains\Common\Enums;

use App\CommonFunctions;
use App\Http\Traits\PrepareEnumDataMethods;

enum ReportTypes: int
{
    use PrepareEnumDataMethods;

    case SALES = 1;
    case INVENTORY = 2;
    case MERCHANDISING = 3;
    case PURCHASING = 4;
    case ORDERS = 5;
    case OTHERS = 6;

    /**
     * @return mixed[]
     */
    public static function formattedCustomReportForSelectionStoreManager(bool $nameInTitleCase = true): array
    {
        return collect(self::cases())->map(
            function ($type) use ($nameInTitleCase) {
                if ($type->value !== self::OTHERS->value) {
                    return [
                        'id' => $type->value,
                        'name' => $nameInTitleCase ? CommonFunctions::stringTitleLowerCase($type->name) : $type->name,
                    ];
                }
            }
        )->filter()->values()->toArray();
    }

    public static function formattedCustomReportForSelectionWarehouseManager(bool $nameInTitleCase = true): array
    {
        return collect(self::cases())->map(
            function ($type) use ($nameInTitleCase) {
                if ($type->value === self::INVENTORY->value || $type->value === self::PURCHASING->value) {
                    return [
                        'id' => $type->value,
                        'name' => $nameInTitleCase ? CommonFunctions::stringTitleLowerCase($type->name) : $type->name,
                    ];
                }
            }
        )->filter()->values()->toArray();
    }
}
