<?php

declare(strict_types=1);

namespace App\Domains\PurchaseOrder\Enums;

use App\CommonFunctions;
use App\Http\Traits\PrepareEnumDataMethods;

enum OrderTypes: int
{
    use PrepareEnumDataMethods;

    case PURCHASE_REQUEST = 1;
    case TRANSFER_REQUEST = 2;
    case SALES_ORDER = 3;
    case PURCHASE_ORDER = 4;

    /**
     * @return mixed[]
     */
    public static function formattedOrderForSelection(bool $nameInTitleCase = true): array
    {
        return collect(self::cases())->map(
            function ($type) use ($nameInTitleCase) {
                if ($type->value === self::SALES_ORDER->value || $type->value === self::PURCHASE_ORDER->value) {
                    return [
                        'id' => $type->value,
                        'name' => $nameInTitleCase ? CommonFunctions::stringTitleLowerCase($type->name) : $type->name,
                        'key' => $type->name,
                    ];
                }
            }
        )->filter()->values()->toArray();
    }
}
