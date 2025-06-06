<?php

declare(strict_types=1);

namespace App\Domains\SellThroughAggregate\Enums;

use App\CommonFunctions;
use App\Http\Traits\PrepareEnumDataMethods;

enum SellThroughTypes: int
{
    use PrepareEnumDataMethods;

    case SIZES = 1;
    case COLORS = 2;
    case STYLES = 3;
    case BY_MASTER_PRODUCT = 4;
    case BY_UPC = 5;
    case LOCATIONS = 6;
    case DEPARTMENTS = 7;
    case BRANDS = 8;
    case CATEGORIES = 9;
    case SUMMARY = 10;
    case BY_ATTRIBUTES = 11;

    public static function getList(): array
    {
        $variantsEnabled = config('app.product_variant');

        return collect(self::cases())
            ->filter(function ($type) use ($variantsEnabled): bool {
                if ($variantsEnabled) {
                    return ! in_array($type, [self::SIZES, self::COLORS, self::STYLES]);
                }

                return self::BY_ATTRIBUTES != $type;
            })
            ->map(fn ($type): array => [
                'id' => $type->value,
                'name' => CommonFunctions::stringTitleLowerCase($type->name),
                'key' => $type->name,
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
                    return ! in_array($type, [self::SIZES, self::COLORS, self::STYLES]);
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
