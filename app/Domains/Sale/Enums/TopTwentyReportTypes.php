<?php

declare(strict_types=1);

namespace App\Domains\Sale\Enums;

use App\CommonFunctions;
use App\Http\Traits\PrepareEnumDataMethods;

enum TopTwentyReportTypes: int
{
    use PrepareEnumDataMethods;

    case BY_CATEGORIES = 1;
    case BY_STYLES = 2;
    case BY_BRANDS = 3;
    case BY_PRODUCTS = 4;
    case BY_COLORS = 5;
    case BY_MASTER_PRODUCT = 6;
    case BY_ATTRIBUTES = 7;

    public static function formattedForSelection(bool $nameInTitleCase = true): array
    {
        $variantsEnabled = config('app.product_variant');

        return collect(self::cases())
            ->filter(function ($type) use ($variantsEnabled): bool {
                if ($variantsEnabled) {
                    return ! in_array($type, [self::BY_STYLES, self::BY_COLORS]);
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
                    return ! in_array($type, [self::BY_STYLES, self::BY_COLORS]);
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
