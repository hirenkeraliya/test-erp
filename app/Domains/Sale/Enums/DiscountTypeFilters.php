<?php

declare(strict_types=1);

namespace App\Domains\Sale\Enums;

use App\CommonFunctions;
use App\Http\Traits\PrepareEnumDataMethods;

enum DiscountTypeFilters: int
{
    use PrepareEnumDataMethods;

    case BY_BRAND = 1;
    case BY_DEPARTMENT = 2;
    case BY_PRODUCT = 3;
    case BY_MASTER_PRODUCT = 4;
    case BY_STYLE = 5;
    case BY_TAG = 6;
    case BY_PRODUCT_COLLECTION = 7;
    case BY_ATTRIBUTES = 8;

    public static function formattedForSelection(bool $nameInTitleCase = true): array
    {
        $variantsEnabled = config('app.product_variant');

        return collect(self::cases())
            ->filter(function ($type) use ($variantsEnabled): bool {
                if ($variantsEnabled) {
                    return self::BY_STYLE != $type;
                }

                return self::BY_ATTRIBUTES != $type;
            })
            ->map(fn ($type): array => [
                'id' => $type->value,
                'name' => $nameInTitleCase ? CommonFunctions::stringTitleLowerCase($type->name) : $type->name,
            ])
            ->values()
            ->toArray();
    }

    public static function getFormattedArrayForStaticUse(): array
    {
        $variantsEnabled = config('app.product_variant');

        return collect(self::cases())
            ->filter(function ($type) use ($variantsEnabled): bool {
                if ($variantsEnabled) {
                    return self::BY_STYLE != $type;
                }

                return self::BY_ATTRIBUTES != $type;
            })
            ->map(fn ($type): array => [
                CommonFunctions::stringToCamelCase($type->name) => $type->value,
            ])
            ->collapse()
            ->toArray();
    }
}
